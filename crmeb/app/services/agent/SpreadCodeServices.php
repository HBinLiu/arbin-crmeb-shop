<?php
namespace app\services\agent;

use app\dao\agent\SpreadCodeDao;
use app\services\BaseServices;
use app\services\other\QrcodeServices;
use app\services\other\UploadService;
use app\services\system\attachment\SystemAttachmentServices;
use app\services\user\UserServices;
use crmeb\exceptions\AdminException;
use crmeb\exceptions\ApiException;
use crmeb\services\app\MiniProgramService;

class SpreadCodeServices extends BaseServices
{
    public function __construct(SpreadCodeDao $dao)
    {
        $this->dao = $dao;
    }

    public function getList(array $where)
    {
        [$page, $limit] = $this->getPageValue();
        $list = $this->dao->getList($where, $page, $limit);
        $time = time();
        foreach ($list as &$item) {
            $item['state'] = $this->stateText($item, $time);
            $item['expire_time'] = (int)$item['expire_time'] > 0 ? date('Y-m-d H:i:s', $item['expire_time']) : '永不过期';
            $item['add_time'] = date('Y-m-d H:i:s', $item['add_time']);
        }
        $count = $this->dao->getCount($where);
        return compact('list', 'count');
    }

    public function saveCode(array $data)
    {
        $title = trim((string)($data['title'] ?? ''));
        $limit = (int)($data['limit_num'] ?? 0);
        $expireText = trim((string)($data['expire_time'] ?? ''));
        $expire = $expireText === '' ? 0 : strtotime($expireText);
        if ($title === '') throw new AdminException('请输入名称');
        if (mb_strlen($title) > 30) throw new AdminException('名称不能超过30个字');
        if ($limit < 1 || $limit > 100000) throw new AdminException('扫码数量需在1到100000之间');
        if ($expireText !== '' && (!$expire || $expire <= time())) throw new AdminException('过期时间需晚于当前时间');
        $this->dao->save([
            'title' => $title,
            'code' => $this->makeCode(),
            'limit_num' => $limit,
            'used_num' => 0,
            'expire_time' => $expire,
            'status' => 1,
            'add_time' => time(),
            'is_del' => 0,
        ]);
        return true;
    }

    public function deleteCode(int $id)
    {
        $info = $this->alive($id);
        $this->dao->update($info['id'], ['is_del' => 1, 'status' => 0]);
        return true;
    }

    public function setStatus(int $id, int $status)
    {
        $info = $this->alive($id);
        $this->dao->update($info['id'], ['status' => $status ? 1 : 0]);
        return true;
    }

    public function recordList(int $id)
    {
        $info = $this->alive($id);
        [$page, $limit] = $this->getPageValue();
        /** @var SpreadCodeLogDao $logDao */
        $logDao = app()->make(\app\dao\agent\SpreadCodeLogDao::class);
        $list = $logDao->getRecordList((int)$info['id'], $page, $limit);
        foreach ($list as &$item) {
            $item['add_time'] = date('Y-m-d H:i:s', $item['add_time']);
            $item['nickname'] = $item['nickname'] ?: ('用户' . $item['uid']);
        }
        $count = $logDao->getRecordCount((int)$info['id']);
        return compact('list', 'count');
    }

    public function qrcode(int $id)
    {
        $info = $this->alive($id);
        $h5 = $this->h5Code($info);
        if (!$h5) throw new AdminException('二维码生成失败');
        $routine = '';
        $routineError = '';
        try {
            $routine = $this->routineCode($info);
        } catch (\Throwable $e) {
            $routineError = $e->getMessage() ?: '小程序码生成失败';
        }
        return [
            'h5_code' => $h5,
            'routine_code' => $routine,
            'routine_error' => $routineError,
        ];
    }

    /**
     * 扫码开通分销员。同一用户重复扫不重复占用名额。
     */
    public function claim(int $uid, string $code)
    {
        $code = strtoupper(trim($code));
        if ($code === '') throw new ApiException('分销码无效');
        return $this->transaction(function () use ($uid, $code) {
            $info = $this->dao->lockAliveByCode($code);
            if (!$info) throw new ApiException('分销码无效');
            if (!(int)$info['status']) throw new ApiException('分销码已停用');
            if ((int)$info['expire_time'] > 0 && (int)$info['expire_time'] < time()) throw new ApiException('分销码已过期');
            /** @var \app\dao\agent\SpreadCodeLogDao $logDao */
            $logDao = app()->make(\app\dao\agent\SpreadCodeLogDao::class);
            if ($logDao->be(['code_id' => $info['id'], 'uid' => $uid])) {
                return '您已通过该分销码成为分销员';
            }
            if ((int)$info['used_num'] >= (int)$info['limit_num']) throw new ApiException('分销码名额已用完');
            /** @var UserServices $userServices */
            $userServices = app()->make(UserServices::class);
            $user = $userServices->getUserInfo($uid, 'uid,is_promoter,spread_open');
            if (!$user) throw new ApiException('用户不存在');
            if ((int)$user['is_promoter'] === 1 && (int)$user['spread_open'] === 1) {
                throw new ApiException('您已是分销员');
            }
            $affected = $this->dao->incUsed((int)$info['id'], (int)$info['limit_num']);
            if (!$affected) throw new ApiException('分销码名额已用完');
            $userServices->update($uid, ['is_promoter' => 1, 'spread_open' => 1]);
            $logDao->save([
                'code_id' => $info['id'],
                'uid' => $uid,
                'add_time' => time(),
            ]);
            return '已成为分销员';
        });
    }

    protected function alive(int $id): array
    {
        $info = $this->dao->get($id);
        if (!$info || (int)$info['is_del'] === 1) throw new AdminException('分销码不存在');
        return is_array($info) ? $info : $info->toArray();
    }

    protected function stateText(array $item, int $time): string
    {
        if (!(int)$item['status']) return '已停用';
        if ((int)$item['expire_time'] > 0 && (int)$item['expire_time'] < $time) return '已过期';
        if ((int)$item['used_num'] >= (int)$item['limit_num']) return '已用完';
        return '使用中';
    }

    protected function makeCode(): string
    {
        $chars = 'ABCDEFGHJKLMNPQRSTUVWXYZ23456789';
        $max = strlen($chars) - 1;
        for ($n = 0; $n < 8; $n++) {
            $code = '';
            for ($i = 0; $i < 8; $i++) {
                $code .= $chars[random_int(0, $max)];
            }
            if (!$this->dao->be(['code' => $code])) return $code;
        }
        throw new AdminException('分销码生成失败，请重试');
    }

    protected function h5Code(array $info)
    {
        /** @var QrcodeServices $qrcodeService */
        $qrcodeService = app()->make(QrcodeServices::class);
        return $qrcodeService->getWechatQrcodePath(
            'spread_code_h5_' . $info['code'] . '.jpg',
            '/pages/annex/spread_code/index?code=' . $info['code'],
            true
        );
    }

    protected function routineCode(array $info)
    {
        if (!sys_config('routine_appId') || !sys_config('routine_appsecret')) {
            throw new AdminException('请先配置小程序appid、appSecret');
        }
        $name = 'spread_code_mp_' . $info['code'] . '.jpg';
        /** @var SystemAttachmentServices $attachment */
        $attachment = app()->make(SystemAttachmentServices::class);
        $imageInfo = $attachment->getInfo(['name' => $name]);
        $siteUrl = rtrim((string)sys_config('site_url'), '/');
        if ($imageInfo) {
            $url = $imageInfo['att_dir'];
            if ((int)$imageInfo['image_type'] === 1 && $url && strpos($url, 'http') !== 0) $url = $siteUrl . $url;
            return $url;
        }
        $resCode = MiniProgramService::appCodeUnlimitService('c=' . $info['code'], 'pages/annex/spread_code/index', 280);
        if (!$resCode || (method_exists($resCode, 'getSize') && $resCode->getSize() < 100)) {
            throw new AdminException('小程序码生成失败，请先发布包含分销码页面的小程序');
        }
        $upload = UploadService::init();
        if ($upload->to('routine/spread/code')->setAuthThumb(false)->stream((string)$resCode, $name) === false) {
            throw new AdminException($upload->getError() ?: '小程序码上传失败');
        }
        $imageInfo = $upload->getUploadInfo();
        $imageInfo['image_type'] = (int)sys_config('upload_type', 1);
        $attachment->attachmentAdd($imageInfo['name'], $imageInfo['size'], $imageInfo['type'], $imageInfo['dir'], $imageInfo['thumb_path'], 1, $imageInfo['image_type'], $imageInfo['time'], 2);
        $url = $imageInfo['dir'];
        if ((int)$imageInfo['image_type'] === 1 && $url && strpos($url, 'http') !== 0) $url = $siteUrl . $url;
        return $url;
    }
}

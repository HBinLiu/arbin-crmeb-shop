<?php
// +----------------------------------------------------------------------
// | CRMEB [ CRMEB赋能开发者，助力企业发展 ]
// +----------------------------------------------------------------------
// | Copyright (c) 2016~2026 https://www.crmeb.com All rights reserved.
// +----------------------------------------------------------------------
// | Licensed CRMEB并不是自由软件，未经许可不能去掉CRMEB相关版权
// +----------------------------------------------------------------------
// | Author: CRMEB Team <admin@crmeb.com>
// +----------------------------------------------------------------------

namespace app\services\pay;

use app\dao\order\StoreOrderProfitSharingDao;
use app\services\BaseServices;
use app\services\order\OtherOrderServices;
use app\services\order\StoreOrderServices;
use app\services\pay\PayServices;
use crmeb\exceptions\PayException;
use crmeb\services\pay\Pay;
use think\facade\Cache;
use think\facade\Log;

/**
 * 微信服务商分账（单店：平台抽成）
 * Class ProfitSharingServices
 * @package app\services\pay
 */
class ProfitSharingServices extends BaseServices
{
    /** 商品订单 */
    const BIZ_PRODUCT = 'product';
    /** 购买会员 */
    const BIZ_MEMBER = 'member';

    /** 待分账 */
    const STATUS_WAIT = 0;
    /** 分账成功 */
    const STATUS_SUCCESS = 1;
    /** 分账失败 */
    const STATUS_FAIL = 2;
    /** 已回退 */
    const STATUS_RETURNED = 3;
    /** 无需分账（金额过小等），已解冻 */
    const STATUS_SKIP = 4;

    /** 接收方：商户号 */
    const RECEIVER_MERCHANT = 'MERCHANT_ID';
    /** 接收方：个人（openid 属服务商 sp_appid） */
    const RECEIVER_PERSONAL = 'PERSONAL_OPENID';
    /** 接收方：个人（openid 属特约商户 sub_appid，如店铺小程序） */
    const RECEIVER_PERSONAL_SUB = 'PERSONAL_SUB_OPENID';

    public function __construct(StoreOrderProfitSharingDao $dao)
    {
        $this->dao = $dao;
    }

    /**
     * 接收方类型
     * 后台选「微信用户」时用 PERSONAL_SUB_OPENID：openid 取自店铺小程序（支付里的 sub_appid）
     */
    public function getReceiverType(): string
    {
        return (int)sys_config('profit_sharing_receiver_type', 1) === 2
            ? self::RECEIVER_PERSONAL_SUB
            : self::RECEIVER_MERCHANT;
    }

    /**
     * 是否个人接收方（零钱）
     */
    public function isPersonalReceiver(?string $receiverType = null): bool
    {
        $type = $receiverType ?? $this->getReceiverType();
        return $type === self::RECEIVER_PERSONAL || $type === self::RECEIVER_PERSONAL_SUB;
    }

    /**
     * 接收方账号（商户号或 openid）
     */
    public function getReceiverAccount(): string
    {
        if ($this->isPersonalReceiver()) {
            return trim((string)sys_config('profit_sharing_receiver_openid', ''));
        }
        return trim((string)sys_config('profit_sharing_receiver_mchid', ''));
    }

    /**
     * 服务商 AppID（sp_appid）
     */
    public function getSpAppid(): string
    {
        return trim((string)sys_config('sp_appid', ''));
    }

    /**
     * 特约侧 AppID（sub_appid，店铺小程序）
     */
    public function getSubAppid(): string
    {
        return trim((string)sys_config('routine_appId', ''));
    }

    /**
     * 校验后台接收方配置（对照微信添加接收方必填规则）
     */
    protected function assertReceiverConfig(string $receiverType, string $receiverAccount): void
    {
        if ($receiverAccount === '') {
            throw new PayException($this->isPersonalReceiver($receiverType)
                ? '请配置分账接收方OpenID'
                : '请配置分账接收方商户号');
        }
        // 微信：MERCHANT_ID 时 name 为商户全称（必传）
        if ($receiverType === self::RECEIVER_MERCHANT && $this->getReceiverDisplayName($receiverType) === '') {
            throw new PayException('请配置分账接收方商户全称');
        }
    }

    /**
     * 是否启用服务商分账
     */
    public function isEnabled(): bool
    {
        return (int)sys_config('mer_type', 0) === 1
            && (int)sys_config('profit_sharing_open', 0) === 1
            && (int)sys_config('pay_wechat_type', 0) === 1
            && trim((string)sys_config('pay_sub_merchant_id', '')) !== ''
            && $this->getReceiverAccount() !== '';
    }

    /**
     * 抽成比例（百分比）
     */
    public function getRatio(): string
    {
        $ratio = sys_config('profit_sharing_ratio', '');
        if ($ratio === '' || $ratio === null) {
            return '1';
        }
        return (string)$ratio;
    }

    /**
     * 计算分账金额（分）
     */
    public function calcAmountFen(string $payPrice): int
    {
        $payFen = (int)bcmul($payPrice, '100', 0);
        if ($payFen <= 0) {
            return 0;
        }
        $amount = (int)bcmul((string)$payFen, bcdiv($this->getRatio(), '100', 6), 0);
        return max(0, $amount);
    }

    /**
     * 确认收货/会员虚拟发货结算后发起分账（小程序须结算后方可分账）
     * @param bool $finalAttempt 队列最后一轮：分账仍失败则解冻剩余资金，避免商户无法结算
     * @param string $bizType product=商品订单 member=购买会员
     */
    public function handleOrderPaid(int $orderId, bool $finalAttempt = false, string $bizType = self::BIZ_PRODUCT): bool
    {
        if ($orderId <= 0) {
            return true;
        }
        $bizType = $this->normalizeBizType($bizType);
        if (!$this->isEnabled()) {
            Log::info('微信分账跳过:未启用或配置不全 orderId=' . $orderId . ' biz=' . $bizType
                . ' mer_type=' . (int)sys_config('mer_type', 0)
                . ' open=' . (int)sys_config('profit_sharing_open', 0)
                . ' pay_wechat_type=' . (int)sys_config('pay_wechat_type', 0)
                . ' sub_mchid=' . (trim((string)sys_config('pay_sub_merchant_id', '')) !== '' ? '1' : '0')
                . ' receiver=' . ($this->getReceiverAccount() !== '' ? '1' : '0'));
            return true;
        }

        $order = $this->loadOrderForSharing($orderId, $bizType);
        if (!$order) {
            return true;
        }
        if ((int)$order['paid'] !== 1) {
            return true;
        }
        if (($order['pay_type'] ?? '') !== PayServices::WEIXIN_PAY) {
            return true;
        }
        if (empty($order['trade_no'])) {
            Log::error('微信分账失败:订单缺少trade_no order_id=' . ($order['order_id'] ?? '') . ' biz=' . $bizType);
            return false;
        }

        $existSuccess = $this->dao->getOne(['oid' => $orderId, 'biz_type' => $bizType, 'status' => self::STATUS_SUCCESS]);
        $existSkip = $this->dao->getOne(['oid' => $orderId, 'biz_type' => $bizType, 'status' => self::STATUS_SKIP]);
        if ($existSuccess || $existSkip) {
            return true;
        }

        $receiverType = $this->getReceiverType();
        $receiverAccount = $this->getReceiverAccount();
        try {
            $this->assertReceiverConfig($receiverType, $receiverAccount);
        } catch (\Throwable $e) {
            Log::error('微信分账失败:' . $e->getMessage() . ' order_id=' . ($order['order_id'] ?? ''));
            if ($finalAttempt) {
                return $this->unfreezeAfterShareFail($orderId, $e->getMessage(), $bizType);
            }
            return false;
        }
        $spAppid = $this->getSpAppid();
        if ($spAppid === '') {
            Log::error('微信分账失败:缺少sp_appid order_id=' . ($order['order_id'] ?? ''));
            if ($finalAttempt) {
                return $this->unfreezeAfterShareFail($orderId, '缺少sp_appid', $bizType);
            }
            return false;
        }
        if ($receiverType === self::RECEIVER_PERSONAL_SUB && $this->getSubAppid() === '') {
            Log::error('微信分账失败:缺少小程序sub_appid order_id=' . ($order['order_id'] ?? ''));
            if ($finalAttempt) {
                return $this->unfreezeAfterShareFail($orderId, '缺少小程序sub_appid', $bizType);
            }
            return false;
        }

        $amountFen = $this->calcAmountFen((string)$order['pay_price']);
        $outOrderNo = 'PS' . ($bizType === self::BIZ_MEMBER ? 'M' : '') . $order['order_id'] . substr((string)time(), -4);

        // 复用待分账/失败记录，避免队列重试产生多条流水
        $exist = $this->dao->getOne(['oid' => $orderId, 'biz_type' => $bizType, 'status' => self::STATUS_WAIT])
            ?: $this->dao->getOne(['oid' => $orderId, 'biz_type' => $bizType, 'status' => self::STATUS_FAIL]);
        if ($exist) {
            $exist = is_array($exist) ? $exist : $exist->toArray();
            $id = (int)$exist['id'];
            // 上次已受理但处理中：用原单号查询，避免重复请求分账
            if ((int)$exist['status'] === self::STATUS_WAIT && $this->isProcessingResult($exist['result'] ?? '')) {
                try {
                    $pay = $this->getV3Pay();
                    $res = $pay->profitSharingQueryOrder(
                        (string)$exist['out_order_no'],
                        (string)($exist['sub_mchid'] ?: sys_config('pay_sub_merchant_id')),
                        (string)$order['trade_no']
                    );
                    $this->applyProfitSharingResult($id, $res);
                    return true;
                } catch (\Throwable $e) {
                    Log::warning('微信分账查询未完成:' . $e->getMessage() . ' order_id=' . $order['order_id']);
                    return false;
                }
            }
            $this->dao->update($id, [
                'out_order_no' => $outOrderNo,
                'receiver_type' => $receiverType,
                'receiver_mchid' => $receiverAccount,
                'ratio' => $this->getRatio(),
                'amount' => bcdiv((string)$amountFen, '100', 2),
                'amount_fen' => $amountFen,
                'status' => self::STATUS_WAIT,
                'fail_msg' => '',
            ]);
            $record = array_merge($exist, [
                'sub_mchid' => $exist['sub_mchid'] ?: trim((string)sys_config('pay_sub_merchant_id')),
                'out_order_no' => $outOrderNo,
            ]);
        } else {
            $record = [
                'oid' => $orderId,
                'biz_type' => $bizType,
                'order_id' => $order['order_id'],
                'trade_no' => $order['trade_no'],
                'sub_mchid' => trim((string)sys_config('pay_sub_merchant_id')),
                'receiver_type' => $receiverType,
                'receiver_mchid' => $receiverAccount,
                'ratio' => $this->getRatio(),
                'amount' => bcdiv((string)$amountFen, '100', 2),
                'amount_fen' => $amountFen,
                'out_order_no' => $outOrderNo,
                'status' => self::STATUS_WAIT,
                'add_time' => time(),
            ];
            $id = $this->dao->save($record)->id;
        }

        try {
            $pay = $this->getV3Pay();
            $this->runProfitSharing($pay, $order, $record, $id, $receiverType, $receiverAccount, $amountFen, $outOrderNo);
            return true;
        } catch (\Throwable $e) {
            if (strpos($e->getMessage(), '分账处理中') !== false) {
                Log::warning('微信分账处理中，等待查询 order_id=' . $order['order_id']);
                return false;
            }
            if ($this->isSettlementFrozenError($e->getMessage())) {
                $this->dao->update($id, [
                    'status' => self::STATUS_WAIT,
                    'fail_msg' => mb_substr($e->getMessage(), 0, 500),
                ]);
                Log::warning('微信分账等待结算:' . $e->getMessage() . ' order_id=' . $order['order_id']);
                return false;
            }
            $this->clearReceiverCache($receiverType, $receiverAccount);
            Log::warning('微信分账首次失败，清缓存后重试:' . $e->getMessage() . ' order_id=' . $order['order_id']);
            try {
                $outOrderNo = 'PS' . ($bizType === self::BIZ_MEMBER ? 'M' : '') . $order['order_id'] . substr((string)time(), -4);
                $this->dao->update($id, ['out_order_no' => $outOrderNo, 'status' => self::STATUS_WAIT, 'fail_msg' => '', 'result' => '']);
                $pay = $this->getV3Pay();
                $this->ensureReceiver($pay, $receiverType, $receiverAccount, true);
                $this->runProfitSharing($pay, $order, $record, $id, $receiverType, $receiverAccount, $amountFen, $outOrderNo);
                return true;
            } catch (\Throwable $e2) {
                if (strpos($e2->getMessage(), '分账处理中') !== false) {
                    Log::warning('微信分账重试后处理中，等待查询 order_id=' . $order['order_id']);
                    return false;
                }
                if ($this->isSettlementFrozenError($e2->getMessage())) {
                    $this->dao->update($id, [
                        'status' => self::STATUS_WAIT,
                        'fail_msg' => mb_substr($e2->getMessage(), 0, 500),
                    ]);
                    Log::warning('微信分账重试仍等待结算:' . $e2->getMessage() . ' order_id=' . $order['order_id']);
                    return false;
                }
                $this->clearReceiverCache($receiverType, $receiverAccount);
                $this->dao->update($id, [
                    'status' => self::STATUS_FAIL,
                    'fail_msg' => mb_substr($e2->getMessage(), 0, 500),
                    'finish_time' => time(),
                ]);
                Log::error('微信分账重试仍失败:' . $e2->getMessage() . ' order_id=' . $order['order_id']);
                if ($finalAttempt) {
                    return $this->unfreezeRemainingFunds($order, $record, $id, $e2->getMessage());
                }
                return false;
            }
        }
    }

    protected function normalizeBizType(string $bizType): string
    {
        return $bizType === self::BIZ_MEMBER ? self::BIZ_MEMBER : self::BIZ_PRODUCT;
    }

    protected function loadOrderForSharing(int $orderId, string $bizType): ?array
    {
        if ($bizType === self::BIZ_MEMBER) {
            /** @var OtherOrderServices $otherOrderServices */
            $otherOrderServices = app()->make(OtherOrderServices::class);
            $order = $otherOrderServices->get($orderId);
            if (!$order) {
                return null;
            }
            return is_array($order) ? $order : $order->toArray();
        }
        /** @var StoreOrderServices $orderServices */
        $orderServices = app()->make(StoreOrderServices::class);
        $order = $orderServices->get($orderId);
        if (!$order) {
            return null;
        }
        return is_array($order) ? $order : $order->toArray();
    }

    /**
     * 是否因小程序交易未结算导致分账失败（确认收货后方可分账）
     */
    protected function isSettlementFrozenError(string $msg): bool
    {
        return strpos($msg, '交易被冻结') !== false
            || strpos($msg, '确认收货后') !== false
            || strpos($msg, '还未结算') !== false;
    }

    /**
     * 最近一次分账失败原因（供队列终态解冻判断）
     */
    public function getLatestFailMsg(int $orderId, string $bizType = self::BIZ_PRODUCT): string
    {
        if ($orderId <= 0) {
            return '';
        }
        $bizType = $this->normalizeBizType($bizType);
        $list = $this->dao->selectList(['oid' => $orderId, 'biz_type' => $bizType], 'fail_msg', 0, 1, 'id desc');
        $row = $list ? $list->toArray() : [];
        return (string)(($row[0]['fail_msg'] ?? '') ?: '');
    }

    /**
     * 分账最终失败后解冻剩余资金（按订单）
     */
    public function unfreezeAfterShareFail(int $orderId, string $failMsg = '', string $bizType = self::BIZ_PRODUCT): bool
    {
        if ($orderId <= 0) {
            return false;
        }
        $bizType = $this->normalizeBizType($bizType);
        if ($this->isSettlementFrozenError($failMsg)) {
            Log::warning('微信分账最终仍未结算，跳过解冻 orderId=' . $orderId . ' biz=' . $bizType);
            return false;
        }
        $existSuccess = $this->dao->getOne(['oid' => $orderId, 'biz_type' => $bizType, 'status' => self::STATUS_SUCCESS]);
        $existSkip = $this->dao->getOne(['oid' => $orderId, 'biz_type' => $bizType, 'status' => self::STATUS_SKIP]);
        if ($existSuccess || $existSkip) {
            return true;
        }

        $order = $this->loadOrderForSharing($orderId, $bizType);
        if (!$order) {
            return false;
        }
        if (empty($order['trade_no'])) {
            return false;
        }

        $exist = $this->dao->getOne(['oid' => $orderId, 'biz_type' => $bizType, 'status' => self::STATUS_WAIT])
            ?: $this->dao->getOne(['oid' => $orderId, 'biz_type' => $bizType, 'status' => self::STATUS_FAIL]);
        if ($exist) {
            $record = is_array($exist) ? $exist : $exist->toArray();
            $id = (int)$record['id'];
            if ((int)$record['status'] === self::STATUS_WAIT && $this->isProcessingResult($record['result'] ?? '')) {
                try {
                    $pay = $this->getV3Pay();
                    $res = $pay->profitSharingQueryOrder(
                        (string)$record['out_order_no'],
                        (string)($record['sub_mchid'] ?: sys_config('pay_sub_merchant_id')),
                        (string)$order['trade_no']
                    );
                    $this->applyProfitSharingResult($id, $res);
                    return true;
                } catch (\Throwable $e) {
                    Log::error('微信分账最终仍处理中，暂不解冻:' . $e->getMessage() . ' order_id=' . ($order['order_id'] ?? ''));
                    return false;
                }
            }
        } else {
            $record = [
                'sub_mchid' => trim((string)sys_config('pay_sub_merchant_id')),
                'trade_no' => $order['trade_no'],
            ];
            $id = (int)$this->dao->save([
                'oid' => $orderId,
                'biz_type' => $bizType,
                'order_id' => $order['order_id'],
                'trade_no' => $order['trade_no'],
                'sub_mchid' => $record['sub_mchid'],
                'receiver_type' => $this->getReceiverType(),
                'receiver_mchid' => $this->getReceiverAccount(),
                'ratio' => $this->getRatio(),
                'amount' => '0.00',
                'amount_fen' => 0,
                'out_order_no' => '',
                'status' => self::STATUS_FAIL,
                'fail_msg' => mb_substr($failMsg ?: '分账失败', 0, 500),
                'add_time' => time(),
            ])->id;
        }

        return $this->unfreezeRemainingFunds($order, $record, $id, $failMsg ?: ((string)($record['fail_msg'] ?? '分账失败')));
    }

    /**
     * 解冻待分账剩余资金，保证特约商户可结算
     */
    protected function unfreezeRemainingFunds(array $order, array $record, int $id, string $failMsg): bool
    {
        $outOrderNo = 'PU' . ($order['order_id'] ?? $id) . substr((string)time(), -4);
        try {
            $pay = $this->getV3Pay();
            $res = $pay->profitSharingUnfreeze([
                'sub_mchid' => $record['sub_mchid'] ?: trim((string)sys_config('pay_sub_merchant_id')),
                'transaction_id' => $order['trade_no'],
                'out_order_no' => $outOrderNo,
                'description' => '分账失败，解冻剩余资金',
            ]);
            $this->dao->update($id, [
                'status' => self::STATUS_FAIL,
                'fail_msg' => mb_substr($failMsg . '；已解冻剩余资金', 0, 500),
                'out_order_no' => $outOrderNo,
                'result' => json_encode(['msg' => 'share_fail_unfreeze', 'unfreeze' => $res], JSON_UNESCAPED_UNICODE),
                'finish_time' => time(),
            ]);
            Log::warning('微信分账失败已解冻剩余资金 order_id=' . ($order['order_id'] ?? ''));
            return true;
        } catch (\Throwable $e) {
            $this->dao->update($id, [
                'status' => self::STATUS_FAIL,
                'fail_msg' => mb_substr($failMsg . '；解冻失败:' . $e->getMessage(), 0, 500),
                'finish_time' => time(),
            ]);
            Log::error('微信分账失败且解冻失败:' . $e->getMessage() . ' order_id=' . ($order['order_id'] ?? ''));
            return false;
        }
    }

    /**
     * 执行解冻或请求分账
     */
    protected function runProfitSharing($pay, array $order, array $record, int $id, string $receiverType, string $receiverAccount, int $amountFen, string $outOrderNo): void
    {
        $this->ensureReceiver($pay, $receiverType, $receiverAccount);

        if ($amountFen <= 0) {
            $pay->profitSharingUnfreeze([
                'sub_mchid' => $record['sub_mchid'],
                'transaction_id' => $order['trade_no'],
                'out_order_no' => $outOrderNo,
                'description' => '无需抽成，解冻剩余资金',
            ]);
            $this->dao->update($id, [
                'status' => self::STATUS_SKIP,
                'finish_time' => time(),
                'result' => json_encode(['msg' => 'amount_zero_unfreeze'], JSON_UNESCAPED_UNICODE),
            ]);
            return;
        }

        $receiver = [
            'type' => $receiverType,
            'account' => $receiverAccount,
            'amount' => $amountFen,
            'description' => '平台服务费',
        ];
        $receiverName = $this->getReceiverDisplayName($receiverType);
        if ($receiverName !== '') {
            $receiver['name'] = $receiverName;
        }

        $orderData = [
            'sub_mchid' => $record['sub_mchid'],
            'appid' => $this->getSpAppid(),
            'transaction_id' => $order['trade_no'],
            'out_order_no' => $outOrderNo,
            'receivers' => [$receiver],
            'unfreeze_unsplit' => true,
        ];
        // PERSONAL_SUB_OPENID：openid 属特约 sub_appid；PERSONAL_OPENID：属服务商 appid
        if ($receiverType === self::RECEIVER_PERSONAL_SUB) {
            $orderData['sub_appid'] = $this->getSubAppid();
        }

        $res = $pay->profitSharingOrder($orderData);
        $this->applyProfitSharingResult($id, $res);
    }

    /**
     * 上次分账是否仍在处理中
     */
    protected function isProcessingResult($result): bool
    {
        if (!$result) {
            return false;
        }
        if (is_string($result)) {
            $decoded = json_decode($result, true);
        } else {
            $decoded = $result;
        }
        return is_array($decoded) && (($decoded['state'] ?? '') === 'PROCESSING');
    }

    /**
     * 按官方终态落库：FINISHED + 接收方 SUCCESS 才算成功
     */
    protected function applyProfitSharingResult(int $id, array $res): void
    {
        $state = (string)($res['state'] ?? '');
        if ($state === 'PROCESSING') {
            $this->dao->update($id, [
                'status' => self::STATUS_WAIT,
                'wx_order_id' => $res['order_id'] ?? '',
                'result' => json_encode($res, JSON_UNESCAPED_UNICODE),
            ]);
            throw new PayException('分账处理中，请稍后查询');
        }
        if ($state !== 'FINISHED') {
            throw new PayException('分账状态异常:' . ($state !== '' ? $state : 'unknown'));
        }
        foreach ($res['receivers'] ?? [] as $receiver) {
            $result = (string)($receiver['result'] ?? '');
            if ($result !== 'SUCCESS') {
                $reason = (string)($receiver['fail_reason'] ?? $result);
                throw new PayException('分账接收方失败:' . $reason);
            }
        }
        $this->dao->update($id, [
            'status' => self::STATUS_SUCCESS,
            'wx_order_id' => $res['order_id'] ?? '',
            'finish_time' => time(),
            'result' => json_encode($res, JSON_UNESCAPED_UNICODE),
            'fail_msg' => '',
        ]);
    }

    /**
     * 退款前按比例回退已分账金额（仅商户号接收方；个人零钱微信不支持回退）
     * @param array $order 原支付订单
     * @param string $refundPrice 本次退款金额（元）
     */
    public function returnBeforeRefund(array $order, string $refundPrice): bool
    {
        if (!$this->isEnabled() || empty($order['id'])) {
            return true;
        }
        $record = $this->dao->getOne(['oid' => (int)$order['id'], 'biz_type' => self::BIZ_PRODUCT, 'status' => self::STATUS_SUCCESS]);
        if (!$record) {
            return true;
        }
        $record = is_array($record) ? $record : $record->toArray();
        if ((int)$record['amount_fen'] <= 0) {
            return true;
        }

        $receiverType = (string)($record['receiver_type'] ?? self::RECEIVER_MERCHANT);
        if ($this->isPersonalReceiver($receiverType)) {
            // 微信分账回退不支持个人接收方，已分到零钱的金额无法通过接口退回
            Log::warning('微信分账回退跳过:个人接收方不支持回退 order_id=' . ($order['order_id'] ?? ''));
            return true;
        }

        $payPrice = (string)($order['pay_price'] ?? '0');
        if (bccomp($payPrice, '0', 2) <= 0) {
            return true;
        }
        $alreadyReturned = (int)($record['return_amount_fen'] ?? 0);
        $remainFen = (int)$record['amount_fen'] - $alreadyReturned;
        if ($remainFen <= 0) {
            return true;
        }
        $returnFen = (int)bcmul((string)$record['amount_fen'], bcdiv($refundPrice, $payPrice, 6), 0);
        if ($returnFen <= 0) {
            return true;
        }
        if ($returnFen > $remainFen) {
            $returnFen = $remainFen;
        }

        $outReturnNo = 'PR' . ($order['order_id'] ?? $order['id']) . substr((string)time(), -5);
        try {
            $pay = $this->getV3Pay();
            $data = [
                'sub_mchid' => $record['sub_mchid'],
                'out_return_no' => $outReturnNo,
                'return_mchid' => $record['receiver_mchid'],
                'amount' => $returnFen,
                'description' => '订单退款分账回退',
            ];
            if (!empty($record['wx_order_id'])) {
                $data['order_id'] = $record['wx_order_id'];
            } else {
                $data['out_order_no'] = $record['out_order_no'];
            }
            $res = $pay->profitSharingReturn($data);
            $returnedFen = (int)($record['return_amount_fen'] ?? 0) + $returnFen;
            $status = $returnedFen >= (int)$record['amount_fen'] ? self::STATUS_RETURNED : self::STATUS_SUCCESS;
            $this->dao->update($record['id'], [
                'status' => $status,
                'return_amount_fen' => $returnedFen,
                'out_return_no' => $outReturnNo,
                'return_result' => json_encode($res, JSON_UNESCAPED_UNICODE),
                'return_time' => time(),
            ]);
            return true;
        } catch (\Throwable $e) {
            Log::error('微信分账回退失败:' . $e->getMessage() . ' order_id=' . ($order['order_id'] ?? ''));
            throw new PayException('分账回退失败:' . $e->getMessage());
        }
    }

    /**
     * 确保接收方已添加（按子商户+账号缓存，分账任务用）
     * @param bool $force 跳过缓存强制调用微信添加
     */
    protected function ensureReceiver($pay, string $receiverType, string $receiverAccount, bool $force = false): void
    {
        $cacheKey = $this->receiverCacheKey($receiverType, $receiverAccount);
        if (!$force && Cache::get($cacheKey)) {
            return;
        }
        $this->addReceiver($pay, $receiverType, $receiverAccount);
        Cache::set($cacheKey, 1, 86400 * 30);
    }

    /**
     * 调用微信添加分账接收方
     * @return string exists|added
     */
    protected function addReceiver($pay, string $receiverType, string $receiverAccount): string
    {
        $data = [
            'sub_mchid' => trim((string)sys_config('pay_sub_merchant_id')),
            'appid' => $this->getSpAppid(),
            'type' => $receiverType,
            'account' => $receiverAccount,
            'relation_type' => 'SERVICE_PROVIDER',
        ];
        if ($receiverType === self::RECEIVER_PERSONAL_SUB) {
            $data['sub_appid'] = $this->getSubAppid();
        }
        $name = $this->getReceiverDisplayName($receiverType);
        if ($name !== '') {
            $data['name'] = $name;
        }
        try {
            $pay->profitSharingAddReceiver($data);
            return 'added';
        } catch (\Throwable $e) {
            if (strpos($e->getMessage(), '已存在') !== false
                || stripos($e->getMessage(), 'RESOURCE_ALREADY_EXISTS') !== false
                || stripos($e->getMessage(), 'ALREADY_EXISTS') !== false) {
                return 'exists';
            }
            throw $e;
        }
    }

    protected function receiverCacheKey(string $receiverType, string $receiverAccount): string
    {
        $subMchid = trim((string)sys_config('pay_sub_merchant_id'));
        return 'wx_profit_sharing_receiver_' . $subMchid . '_' . $receiverType . '_' . md5($receiverAccount);
    }

    protected function clearReceiverCache(string $receiverType, string $receiverAccount): void
    {
        Cache::delete($this->receiverCacheKey($receiverType, $receiverAccount));
    }

    /**
     * 接收方名称（商户全称或个人实名）
     */
    protected function getReceiverDisplayName(string $receiverType): string
    {
        if ($this->isPersonalReceiver($receiverType)) {
            return trim((string)sys_config('profit_sharing_receiver_user_name', ''));
        }
        return trim((string)sys_config('profit_sharing_receiver_name', ''));
    }

    /**
     * @return Pay
     */
    protected function getV3Pay(): Pay
    {
        return app()->make(Pay::class, ['v3_wechat_pay']);
    }
}

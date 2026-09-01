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

    public function __construct(StoreOrderProfitSharingDao $dao)
    {
        $this->dao = $dao;
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
            && trim((string)sys_config('profit_sharing_receiver_mchid', '')) !== '';
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
     * 支付成功后发起分账（建议走队列）
     */
    public function handleOrderPaid(int $orderId): bool
    {
        if (!$this->isEnabled() || $orderId <= 0) {
            return true;
        }

        /** @var StoreOrderServices $orderServices */
        $orderServices = app()->make(StoreOrderServices::class);
        $order = $orderServices->get($orderId);
        if (!$order) {
            return true;
        }
        $order = is_array($order) ? $order : $order->toArray();
        if ((int)$order['paid'] !== 1) {
            return true;
        }
        if (($order['pay_type'] ?? '') !== PayServices::WEIXIN_PAY) {
            return true;
        }
        if (empty($order['trade_no'])) {
            Log::error('微信分账失败:订单缺少trade_no order_id=' . ($order['order_id'] ?? ''));
            return false;
        }

        $existSuccess = $this->dao->getOne(['oid' => $orderId, 'status' => self::STATUS_SUCCESS]);
        $existSkip = $this->dao->getOne(['oid' => $orderId, 'status' => self::STATUS_SKIP]);
        if ($existSuccess || $existSkip) {
            return true;
        }

        $amountFen = $this->calcAmountFen((string)$order['pay_price']);
        $outOrderNo = 'PS' . $order['order_id'] . substr((string)time(), -4);
        $record = [
            'oid' => $orderId,
            'order_id' => $order['order_id'],
            'trade_no' => $order['trade_no'],
            'sub_mchid' => trim((string)sys_config('pay_sub_merchant_id')),
            'receiver_mchid' => trim((string)sys_config('profit_sharing_receiver_mchid')),
            'ratio' => $this->getRatio(),
            'amount' => bcdiv((string)$amountFen, '100', 2),
            'amount_fen' => $amountFen,
            'out_order_no' => $outOrderNo,
            'status' => self::STATUS_WAIT,
            'add_time' => time(),
        ];
        $id = $this->dao->save($record)->id;

        try {
            $pay = $this->getV3Pay();
            $this->ensureReceiver($pay);

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
                return true;
            }

            $res = $pay->profitSharingOrder([
                'sub_mchid' => $record['sub_mchid'],
                'appid' => trim((string)sys_config('sp_appid')),
                'transaction_id' => $order['trade_no'],
                'out_order_no' => $outOrderNo,
                'receivers' => [[
                    'type' => 'MERCHANT_ID',
                    'account' => $record['receiver_mchid'],
                    'amount' => $amountFen,
                    'description' => '平台服务费',
                ]],
                'unfreeze_unsplit' => true,
            ]);

            $this->dao->update($id, [
                'status' => self::STATUS_SUCCESS,
                'wx_order_id' => $res['order_id'] ?? '',
                'finish_time' => time(),
                'result' => json_encode($res, JSON_UNESCAPED_UNICODE),
            ]);
            return true;
        } catch (\Throwable $e) {
            $this->dao->update($id, [
                'status' => self::STATUS_FAIL,
                'fail_msg' => mb_substr($e->getMessage(), 0, 500),
                'finish_time' => time(),
            ]);
            Log::error('微信分账失败:' . $e->getMessage() . ' order_id=' . $order['order_id']);
            return false;
        }
    }

    /**
     * 退款前按比例回退已分账金额
     * @param array $order 原支付订单
     * @param string $refundPrice 本次退款金额（元）
     */
    public function returnBeforeRefund(array $order, string $refundPrice): bool
    {
        if (!$this->isEnabled() || empty($order['id'])) {
            return true;
        }
        $record = $this->dao->getOne(['oid' => (int)$order['id'], 'status' => self::STATUS_SUCCESS]);
        if (!$record) {
            return true;
        }
        $record = is_array($record) ? $record : $record->toArray();
        if ((int)$record['amount_fen'] <= 0) {
            return true;
        }

        $payPrice = (string)($order['pay_price'] ?? '0');
        if (bccomp($payPrice, '0', 2) <= 0) {
            return true;
        }
        // 按退款金额占实付比例回退抽成；全额退则全额回退
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
     * 确保接收方已添加（按子商户缓存）
     */
    protected function ensureReceiver($pay): void
    {
        $subMchid = trim((string)sys_config('pay_sub_merchant_id'));
        $receiver = trim((string)sys_config('profit_sharing_receiver_mchid'));
        $cacheKey = 'wx_profit_sharing_receiver_' . $subMchid . '_' . $receiver;
        if (Cache::get($cacheKey)) {
            return;
        }
        $data = [
            'sub_mchid' => $subMchid,
            'appid' => trim((string)sys_config('sp_appid')),
            'type' => 'MERCHANT_ID',
            'account' => $receiver,
            'relation_type' => 'SERVICE_PROVIDER',
        ];
        $name = trim((string)sys_config('profit_sharing_receiver_name', ''));
        if ($name !== '') {
            $data['name'] = $name;
        }
        try {
            $pay->profitSharingAddReceiver($data);
        } catch (\Throwable $e) {
            // 已存在时微信可能报错，允许继续分账
            if (strpos($e->getMessage(), '已存在') === false
                && stripos($e->getMessage(), 'RESOURCE_ALREADY_EXISTS') === false
                && stripos($e->getMessage(), 'ALREADY_EXISTS') === false) {
                throw $e;
            }
        }
        Cache::set($cacheKey, 1, 86400 * 30);
    }

    /**
     * @return Pay
     */
    protected function getV3Pay(): Pay
    {
        return app()->make(Pay::class, ['v3_wechat_pay']);
    }
}

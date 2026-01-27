<?php
// +----------------------------------------------------------------------
// | CRMEB [ CRMEB赋能开发者，助力企业发展 ]
// +----------------------------------------------------------------------
// | Copyright (c) 2016~2023 https://www.crmeb.com All rights reserved.
// +----------------------------------------------------------------------
// | Licensed CRMEB并不是自由软件，未经许可不能去掉CRMEB相关版权
// +----------------------------------------------------------------------
// | Author: CRMEB Team <admin@crmeb.com>
// +----------------------------------------------------------------------

namespace crmeb\utils;

use think\Response;

/**
 * Json输出类
 * Class Json
 * @package crmeb\utils
 */
class Json
{
    /**
     * @var int
     */
    private $code = 200;
    /**
     * @var array
     */
    private $header = [];

    /**
     * 设置响应状态码
     * @param int $code
     * @return self
     */
    public function code(int $code): self
    {
        $this->code = $code;
        return $this;
    }

    /**
     * 设置响应头
     * @param array $header
     * @return self
     */ 
    public function header(array $header): self
    {
        $this->header = $header;
        return $this;
    }
    /**
     * 生成响应
     * @param int $status
     * @param string $msg
     * @param array|null $data
     * @param array|null $replace
     * @return Response
     */
    public function make(int $status, string $msg, ?array $data = null, ?array $replace = []): Response
    {
        $res = compact('status', 'msg');

        if (!is_null($data))
            $res['data'] = $data;

        if (is_numeric($res['msg'])) {
            $res['code'] = $res['msg'];
            try {
                // 尝试根据状态码获取语言包消息
                $res['msg'] = function_exists('getLang') ? getLang($res['msg'], $replace) : $res['msg'];
            } catch (\Exception $e) {
                // 保持原消息，避免语言包错误影响响应
            }
        }
        return Response::create($res, 'json', $this->code)->header($this->header);
    }
    /**
     * 成功响应
     * @param string|array $msg 响应消息
     * @param array|null $data 响应数据
     * @param array|null $replace 消息替换数组
     * @return \think\Response
     * @see \crmeb\utils\Json::make()
     */
    public function success($msg = 'success', ?array $data = null, ?array $replace = []): Response
    {
        // 如果消息是数组，将其作为数据处理
        if (is_array($msg)) {
            $data = $msg;
            $msg = 'success';
        }

        return $this->make(200, $msg, $data, $replace);
    }

    /**
     * 失败响应
     * @param string|array $msg 响应消息
     * @param array|null $data 响应数据
     * @param array|null $replace 消息替换数组
     * @return \think\Response
     * @see \crmeb\utils\Json::make()
     */
    public function fail($msg = 'fail', ?array $data = null, ?array $replace = []): Response
    {
        // 如果消息是数组，将其作为数据处理
        if (is_array($msg)) {
            $data = $msg;
            $msg = 'fail';
        }
        return $this->make(400, $msg, $data, $replace);
    }
    /**
     * 自定义状态响应
     * @param string $status 状态码
     * @param string|array $msg 响应消息
     * @param array|null $result 响应数据
     * @return \think\Response
     * @see \crmeb\utils\Json::success()
     */
    public function status($status, $msg, $result = [])
    {
        // 转换状态码为大写
        $status = strtoupper($status);
        if (is_array($msg)) {
            $result = $msg;
            $msg = 'success';
        }
        return $this->success($msg, compact('status', 'result'));
    }
}

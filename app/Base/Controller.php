<?php

namespace App\Base;

use LarkFrame\Response;

/**
 * 控制器基类
 *
 * 构造函数必须为 public：路由回调经容器反射实例化控制器，
 * protected 构造函数会触发 ReflectionException 直接 500
 */
class Controller
{
    /**
     * 统一 JSON 响应格式
     *
     * @param mixed $data 业务数据
     * @param int $code 业务状态码（写入响应体，非 HTTP 状态码；HTTP 状态码固定 200）
     */
    protected function api(mixed $data, int $code = 200, string $message = ''): Response
    {
        return json([
            'code' => $code,
            'message' => $message,
            'data' => $data,
        ]);
    }
}

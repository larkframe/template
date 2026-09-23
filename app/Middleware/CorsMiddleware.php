<?php

namespace App\Middleware;

use LarkFrame\MiddlewareInterface;
use LarkFrame\Request;
use LarkFrame\Response;

/**
 * 跨域中间件（白名单驱动）
 *
 * 安全约束：
 *   - 仅当请求 Origin 命中 config('cors.allow_origins') 白名单时才回显该 Origin；
 *     无条件反射任意 Origin + Allow-Credentials:true 等于向所有恶意站点开放带 Cookie 的跨域请求
 *   - credentials 模式下浏览器禁止 Access-Control-Allow-Origin: *，必须回显具体来源
 */
class CorsMiddleware implements MiddlewareInterface
{
    public function process(Request $request, callable $handler): Response
    {
        // 预检请求直接返回空响应，不进入业务处理
        $response = $request->method() === 'OPTIONS' ? response('') : $handler($request);

        $origin = (string)$request->header('origin', '');

        // 响应内容随 Origin 变化，必须声明 Vary: Origin：否则共享缓存（CDN/反向代理）
        // 会把某一来源的 CORS 响应（或无 CORS 头的响应）复用给其他来源
        if ($origin !== '') {
            $vary = $response->getHeader('Vary');
            $vary = is_array($vary) ? implode(', ', $vary) : (string)($vary ?? '');
            if (!str_contains(strtolower($vary), 'origin')) {
                $response->withHeader('Vary', $vary === '' ? 'Origin' : "{$vary}, Origin");
            }
        }

        if ($origin !== '' && in_array($origin, (array)config('cors.allow_origins', []), true)) {
            $response->withHeaders([
                'Access-Control-Allow-Credentials' => 'true',
                'Access-Control-Allow-Origin' => $origin,
                'Access-Control-Allow-Methods' => $request->header('access-control-request-method', 'GET, POST, OPTIONS'),
                'Access-Control-Allow-Headers' => $request->header('access-control-request-headers', 'Content-Type, Authorization'),
            ]);
        }

        return $response;
    }
}

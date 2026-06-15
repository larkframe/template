<?php

namespace App\Middleware;

use LarkFrame\MiddlewareInterface;
use LarkFrame\Request;
use LarkFrame\Response;

class CorsMiddleware implements MiddlewareInterface
{
    public function process(Request $request, callable $handler) : Response
    {
        // 如果是options请求则返回一个空响应
        $response = $request->method() == 'OPTIONS' ? response('') : $handler($request);

        // 给响应添加跨域相关的http头
        $response->withHeaders([
            'Access-Control-Allow-Credentials' => 'true',
            'Access-Control-Allow-Origin' => $request->header('origin', '*'),
            'Access-Control-Allow-Methods' => $request->header('access-control-request-method', '*'),
            'Access-Control-Allow-Headers' => $request->header('access-control-request-headers', '*'),
        ]);

        return $response;
    }
}
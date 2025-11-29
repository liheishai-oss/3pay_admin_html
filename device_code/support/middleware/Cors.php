<?php

namespace support\middleware;

use Webman\MiddlewareInterface;
use Webman\Http\Response;
use Webman\Http\Request;

class Cors implements MiddlewareInterface
{
    public function process(Request $request, callable $next): Response
    {
        // 处理预检请求
        if ($request->method() == 'OPTIONS') {
            $response = response('', 204);
        } else {
            $response = $next($request);
        }
        
        // 添加CORS头
        $response->header('Access-Control-Allow-Origin', '*');
        $response->header('Access-Control-Allow-Methods', 'GET, POST, PUT, DELETE, OPTIONS');
        $response->header('Access-Control-Allow-Headers', 'Content-Type, Authorization, X-Requested-With, X-Device-Code, X-Merchant-Key, X-Signature, X-Timestamp, X-Nonce');
        $response->header('Access-Control-Max-Age', '86400');
        
        return $response;
    }
}

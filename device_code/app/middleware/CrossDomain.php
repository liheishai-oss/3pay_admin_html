<?php
namespace app\middleware;

use Webman\MiddlewareInterface;
use Webman\Http\Response;
use Webman\Http\Request;

class CrossDomain implements MiddlewareInterface
{
    public function process(Request $request, callable $next): Response
    {
        // 设置允许跨域的 Origin
        $origin = $request->header('origin', '*');
        
        // 如果是预检请求，直接返回 204 无内容
        if ($request->method() === 'OPTIONS') {
            return response('', 204, [
                'Access-Control-Allow-Origin'      => $origin,
                'Access-Control-Allow-Credentials' => 'true',
                'Access-Control-Allow-Methods'     => 'GET,POST,PUT,DELETE,OPTIONS,PATCH',
                'Access-Control-Allow-Headers'     => 'Content-Type,Authorization,X-Requested-With,Accept,Origin,Access-Control-Request-Method,Access-Control-Request-Headers,X-Merchant-Key,X-Signature,X-Timestamp,X-Nonce,X-Device-Code',
                'Access-Control-Expose-Headers'    => 'Authorization,Content-Disposition,X-Custom-Header',
                'Access-Control-Max-Age'           => '86400',
            ]);
        }

        /** @var Response $response */
        $response = $next($request);

        $response->withHeaders([
            'Access-Control-Allow-Origin'      => $origin,
            'Access-Control-Allow-Credentials' => 'true',
            'Access-Control-Allow-Methods'     => 'GET,POST,PUT,DELETE,OPTIONS,PATCH',
            'Access-Control-Allow-Headers'     => 'Content-Type,Authorization,X-Requested-With,Accept,Origin,Access-Control-Request-Method,Access-Control-Request-Headers,X-Merchant-Key,X-Signature,X-Timestamp,X-Nonce,X-Device-Code',
            'Access-Control-Expose-Headers'    => 'Authorization,Content-Disposition,X-Custom-Header',
            'Access-Control-Max-Age'           => '86400',
        ]);

        return $response;
    }
}

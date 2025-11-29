<?php

namespace app\middleware;

use Webman\MiddlewareInterface;
use Webman\Http\Response;
use Webman\Http\Request;

class AuthMiddleware implements MiddlewareInterface
{
    public function process(Request $request, callable $handler): Response
    {
        // 跳过 OPTIONS 预检请求（CORS）
        if ($request->method() === 'OPTIONS') {
            return $handler($request);
        }
        
        // 跳过登录接口
        if ($request->path() === '/auth/login' || $request->path() === '/auth/logout') {
            return $handler($request);
        }

        $token = $request->header('Authorization');
        if (!$token) {
            return json(['code' => 401, 'message' => '未提供认证令牌']);
        }

        // 移除 Bearer 前缀
        $token = str_replace('Bearer ', '', $token);

        try {
            $payload = $this->verifyToken($token);
            $request->admin_id = $payload['admin_id'];
            $request->admin_info = $payload;
        } catch (\Exception $e) {
            return json(['code' => 401, 'message' => '认证令牌无效: ' . $e->getMessage()]);
        }

        return $handler($request);
    }

    private function verifyToken($token)
    {
        $config = config('env');
        $secret = $config['JWT_SECRET'] ?? 'your_jwt_secret_key_here_change_in_production';
        
        // 简单的JWT验证（生产环境建议使用专业的JWT库）
        $parts = explode('.', $token);
        if (count($parts) !== 3) {
            throw new \Exception('令牌格式错误');
        }

        $header = json_decode(base64_decode($parts[0]), true);
        $payload = json_decode(base64_decode($parts[1]), true);
        $signature = $parts[2];

        // 验证签名
        $expectedSignature = base64_encode(hash_hmac('sha256', $parts[0] . '.' . $parts[1], $secret, true));
        if ($signature !== $expectedSignature) {
            throw new \Exception('签名验证失败');
        }

        // 检查过期时间
        if (isset($payload['exp']) && $payload['exp'] < time()) {
            throw new \Exception('令牌已过期');
        }

        return $payload;
    }
}

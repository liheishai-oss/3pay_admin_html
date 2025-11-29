<?php

namespace app\controller;

use support\Request;
use app\model\Admin;

class AuthController
{
    /**
     * 管理员登录
     */
    public function login(Request $request)
    {
        $username = $request->input('username');
        $password = $request->input('password');

        if (!$username || !$password) {
            return json(['code' => 400, 'message' => '用户名和密码不能为空']);
        }

        $admin = Admin::where('username', $username)
            ->where('status', 1)
            ->first();

        if (!$admin || !$admin->verifyPassword($password)) {
            return json(['code' => 401, 'message' => '用户名或密码错误']);
        }

        // 生成JWT令牌
        $token = $this->generateToken($admin);

        // 更新最后登录信息
        $admin->updateLastLogin($request->getRealIp());

        return json([
            'code' => 200,
            'message' => '登录成功',
            'data' => [
                'token' => $token,
                'admin' => [
                    'id' => $admin->id,
                    'username' => $admin->username,
                    'email' => $admin->email,
                    'real_name' => $admin->real_name,
                    'avatar' => $admin->avatar,
                ]
            ]
        ]);
    }

    /**
     * 获取当前用户信息
     */
    public function me(Request $request)
    {
        $admin = Admin::find($request->admin_id);
        
        if (!$admin) {
            return json(['code' => 404, 'message' => '用户不存在']);
        }

        return json([
            'code' => 200,
            'data' => [
                'id' => $admin->id,
                'username' => $admin->username,
                'email' => $admin->email,
                'real_name' => $admin->real_name,
                'avatar' => $admin->avatar,
                'last_login_at' => $admin->last_login_at,
                'last_login_ip' => $admin->last_login_ip,
            ]
        ]);
    }

    /**
     * 退出登录
     */
    public function logout(Request $request)
    {
        return json(['code' => 200, 'message' => '退出成功']);
    }

    /**
     * 生成JWT令牌
     */
    private function generateToken($admin)
    {
        $config = config('env');
        $secret = $config['JWT_SECRET'] ?? 'your_jwt_secret_key_here_change_in_production';
        $expire = $config['JWT_EXPIRE'] ?? 7200;

        $header = json_encode(['typ' => 'JWT', 'alg' => 'HS256']);
        $payload = json_encode([
            'admin_id' => $admin->id,
            'username' => $admin->username,
            'exp' => time() + $expire,
            'iat' => time()
        ]);

        $headerEncoded = base64_encode($header);
        $payloadEncoded = base64_encode($payload);
        $signature = base64_encode(hash_hmac('sha256', $headerEncoded . '.' . $payloadEncoded, $secret, true));

        return $headerEncoded . '.' . $payloadEncoded . '.' . $signature;
    }
}






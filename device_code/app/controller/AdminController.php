<?php

namespace app\controller;

use support\Request;
use app\model\Admin;
use app\model\Role;

class AdminController
{
    /**
     * 管理员列表
     */
    public function index(Request $request)
    {
        $page = $request->input('page', 1);
        $limit = $request->input('limit', 15);
        $search = $request->input('search', '');

        $query = Admin::query();

        if ($search) {
            $query->where(function($q) use ($search) {
                $q->where('username', 'like', "%{$search}%")
                  ->orWhere('email', 'like', "%{$search}%")
                  ->orWhere('real_name', 'like', "%{$search}%");
            });
        }

        $total = $query->count();
        $admins = $query->with('roles')
            ->orderBy('id', 'desc')
            ->offset(($page - 1) * $limit)
            ->limit($limit)
            ->get();

        return json([
            'code' => 200,
            'data' => [
                'list' => $admins,
                'total' => $total,
                'page' => $page,
                'limit' => $limit
            ]
        ]);
    }

    /**
     * 创建管理员
     */
    public function store(Request $request)
    {
        $data = $request->all();
        
        // 验证必填字段
        if (empty($data['username']) || empty($data['email']) || empty($data['password'])) {
            return json(['code' => 400, 'message' => '用户名、邮箱和密码不能为空']);
        }

        // 检查用户名是否已存在
        if (Admin::where('username', $data['username'])->exists()) {
            return json(['code' => 400, 'message' => '用户名已存在']);
        }

        // 检查邮箱是否已存在
        if (Admin::where('email', $data['email'])->exists()) {
            return json(['code' => 400, 'message' => '邮箱已存在']);
        }

        $admin = new Admin();
        $admin->username = $data['username'];
        $admin->email = $data['email'];
        $admin->real_name = $data['real_name'] ?? '';
        $admin->avatar = $data['avatar'] ?? '';
        $admin->setPassword($data['password']);
        $admin->status = $data['status'] ?? 1;
        $admin->save();

        // 分配角色
        if (!empty($data['role_ids'])) {
            $admin->roles()->attach($data['role_ids']);
        }

        return json(['code' => 200, 'message' => '创建成功']);
    }

    /**
     * 更新管理员
     */
    public function update(Request $request)
    {
        $id = $request->input('id');
        $admin = Admin::find($id);

        if (!$admin) {
            return json(['code' => 404, 'message' => '管理员不存在']);
        }

        $data = $request->all();

        // 检查用户名是否已存在（排除自己）
        if (isset($data['username']) && Admin::where('username', $data['username'])->where('id', '!=', $id)->exists()) {
            return json(['code' => 400, 'message' => '用户名已存在']);
        }

        // 检查邮箱是否已存在（排除自己）
        if (isset($data['email']) && Admin::where('email', $data['email'])->where('id', '!=', $id)->exists()) {
            return json(['code' => 400, 'message' => '邮箱已存在']);
        }

        // 更新密码
        if (!empty($data['password'])) {
            $admin->setPassword($data['password']);
        }

        $admin->fill($data);
        $admin->save();

        // 更新角色
        if (isset($data['role_ids'])) {
            $admin->roles()->sync($data['role_ids']);
        }

        return json(['code' => 200, 'message' => '更新成功']);
    }

    /**
     * 删除管理员
     */
    public function delete(Request $request)
    {
        $id = $request->input('id');
        $admin = Admin::find($id);

        if (!$admin) {
            return json(['code' => 404, 'message' => '管理员不存在']);
        }

        // 不能删除自己
        if ($admin->id == $request->admin_id) {
            return json(['code' => 400, 'message' => '不能删除自己']);
        }

        $admin->delete();

        return json(['code' => 200, 'message' => '删除成功']);
    }
}






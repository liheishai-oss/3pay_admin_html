<?php

namespace app\controller;

use support\Request;
use app\model\Merchant;

class MerchantController
{
    /**
     * 商户列表
     */
    public function index(Request $request)
    {
        $page = $request->input('page', 1);
        $limit = $request->input('limit', 15);
        $search = $request->input('search', '');

        $query = Merchant::query();

        if ($search) {
            $query->where(function($q) use ($search) {
                $q->where('merchant_name', 'like', "%{$search}%")
                  ->orWhere('merchant_key', 'like', "%{$search}%")
                  ->orWhere('contact_person', 'like', "%{$search}%");
            });
        }

        $total = $query->count();
        $merchants = $query->orderBy('id', 'desc')
            ->offset(($page - 1) * $limit)
            ->limit($limit)
            ->get();

        return json([
            'code' => 200,
            'data' => [
                'list' => $merchants,
                'total' => $total,
                'page' => $page,
                'limit' => $limit
            ]
        ]);
    }

    /**
     * 创建商户
     */
    public function store(Request $request)
    {
        $data = $request->all();
        
        // 验证必填字段
        if (empty($data['merchant_name']) || empty($data['merchant_key'])) {
            return json(['code' => 400, 'message' => '商户名称和商户Key不能为空']);
        }

        // 检查商户Key是否已存在
        if (Merchant::where('merchant_key', $data['merchant_key'])->exists()) {
            return json(['code' => 400, 'message' => '商户Key已存在']);
        }

        // 生成API密钥
        $data['api_secret'] = $this->generateApiSecret();
        $data['status'] = $data['status'] ?? 1;

        $merchant = Merchant::create($data);

        return json(['code' => 200, 'message' => '创建成功', 'data' => $merchant]);
    }

    /**
     * 更新商户
     */
    public function update(Request $request)
    {
        $id = $request->input('id');
        $merchant = Merchant::find($id);

        if (!$merchant) {
            return json(['code' => 404, 'message' => '商户不存在']);
        }

        $data = $request->all();

        // 检查商户Key是否已存在（排除自己）
        if (isset($data['merchant_key']) && Merchant::where('merchant_key', $data['merchant_key'])->where('id', '!=', $id)->exists()) {
            return json(['code' => 400, 'message' => '商户Key已存在']);
        }

        // 重新生成API密钥
        if (isset($data['regenerate_api_secret']) && $data['regenerate_api_secret']) {
            $data['api_secret'] = $this->generateApiSecret();
        }

        $merchant->fill($data);
        $merchant->save();

        return json(['code' => 200, 'message' => '更新成功']);
    }

    /**
     * 删除商户
     */
    public function delete(Request $request)
    {
        $id = $request->input('id');
        $merchant = Merchant::find($id);

        if (!$merchant) {
            return json(['code' => 404, 'message' => '商户不存在']);
        }

        $merchant->delete();

        return json(['code' => 200, 'message' => '删除成功']);
    }

    /**
     * 生成API密钥
     */
    private function generateApiSecret()
    {
        return 'api_' . strtoupper(substr(md5(uniqid()), 0, 16)) . '_' . time();
    }
}






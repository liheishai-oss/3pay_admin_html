<?php

namespace app\controller;

use support\Request;
use app\model\DeviceFingerprint;
use app\model\Merchant;

class DeviceController
{
    /**
     * 获取设备列表
     */
    public function index(Request $request)
    {
        $page = $request->input('page', 1);
        $limit = $request->input('limit', 15);
        $search = $request->input('search', '');
        $deviceType = $request->input('deviceType', '');
        $status = $request->input('status', '');
        $merchantId = $request->input('merchantId', '');
        $dateRange = $request->input('dateRange', []);

        $query = DeviceFingerprint::with(['merchant']);

        // 搜索条件
        if ($search) {
            $query->where(function ($q) use ($search) {
                $q->where('device_code', 'like', "%{$search}%")
                  ->orWhere('fingerprint_key', 'like', "%{$search}%")
                  ->orWhere('ip_address', 'like', "%{$search}%");
            });
        }

        // 设备类型筛选
        if ($deviceType) {
            if ($deviceType === 'mobile') {
                $query->where(function ($q) {
                    $q->where('user_agent', 'like', '%Mobile%')
                      ->orWhere('user_agent', 'like', '%Android%')
                      ->orWhere('user_agent', 'like', '%iPhone%');
                });
            } else if ($deviceType === 'desktop') {
                $query->where(function ($q) {
                    $q->where('user_agent', 'not like', '%Mobile%')
                      ->where('user_agent', 'not like', '%Android%')
                      ->where('user_agent', 'not like', '%iPhone%');
                });
            }
        }

        // 状态筛选
        if ($status !== '') {
            $query->where('status', $status);
        }

        // 商户筛选
        if ($merchantId) {
            $query->where('merchant_id', $merchantId);
        }

        // 日期范围筛选
        if (!empty($dateRange) && count($dateRange) === 2) {
            $query->whereBetween('created_at', [$dateRange[0], $dateRange[1]]);
        }

        $total = $query->count();
        $devices = $query->orderBy('created_at', 'desc')
            ->offset(($page - 1) * $limit)
            ->limit($limit)
            ->get()
            ->map(function ($device) {
                return [
                    'id' => $device->id,
                    'device_code' => $device->device_code,
                    'fingerprint_key' => $device->fingerprint_key,
                    'merchant_name' => $device->merchant->merchant_name ?? 'N/A',
                    'device_type' => $this->getDeviceType($device->user_agent),
                    'browser' => $this->getBrowser($device->user_agent),
                    'ip_address' => $device->ip_address,
                    'status' => $device->status,
                    'status_text' => $this->getStatusText($device->status),
                    'created_at' => $device->created_at->format('Y-m-d H:i:s'),
                    'last_access_at' => $device->updated_at->format('Y-m-d H:i:s')
                ];
            });

        return json([
            'code' => 200,
            'data' => [
                'list' => $devices,
                'total' => $total,
                'page' => $page,
                'limit' => $limit
            ]
        ]);
    }

    /**
     * 获取设备详情
     */
    public function show(Request $request)
    {
        $id = $request->input('id');
        $device = DeviceFingerprint::with(['merchant', 'accessLogs'])->find($id);

        if (!$device) {
            return json(['code' => 404, 'message' => '设备不存在']);
        }

        $deviceData = [
            'id' => $device->id,
            'device_code' => $device->device_code,
            'fingerprint_key' => $device->fingerprint_key,
            'merchant_name' => $device->merchant->merchant_name ?? 'N/A',
            'device_type' => $this->getDeviceType($device->user_agent),
            'browser' => $this->getBrowser($device->user_agent),
            'user_agent' => $device->user_agent,
            'ip_address' => $device->ip_address,
            'components' => json_decode($device->components, true),
            'status' => $device->status,
            'status_text' => $this->getStatusText($device->status),
            'created_at' => $device->created_at->format('Y-m-d H:i:s'),
            'updated_at' => $device->updated_at->format('Y-m-d H:i:s'),
            'access_count' => $device->accessLogs->count(),
            'last_access' => $device->accessLogs->max('created_at')
        ];

        return json([
            'code' => 200,
            'data' => $deviceData
        ]);
    }

    /**
     * 更新设备状态
     */
    public function updateStatus(Request $request)
    {
        $id = $request->input('id');
        $status = $request->input('status');

        $device = DeviceFingerprint::find($id);
        if (!$device) {
            return json(['code' => 404, 'message' => '设备不存在']);
        }

        $device->status = $status;
        $device->save();

        return json([
            'code' => 200,
            'message' => '状态更新成功'
        ]);
    }

    /**
     * 删除设备
     */
    public function delete(Request $request)
    {
        $id = $request->input('id');

        $device = DeviceFingerprint::find($id);
        if (!$device) {
            return json(['code' => 404, 'message' => '设备不存在']);
        }

        $device->delete();

        return json([
            'code' => 200,
            'message' => '删除成功'
        ]);
    }

    /**
     * 批量操作
     */
    public function batchAction(Request $request)
    {
        $ids = $request->input('ids', []);
        $action = $request->input('action');

        if (empty($ids)) {
            return json(['code' => 400, 'message' => '请选择要操作的设备']);
        }

        switch ($action) {
            case 'enable':
                DeviceFingerprint::whereIn('id', $ids)->update(['status' => 1]);
                break;
            case 'disable':
                DeviceFingerprint::whereIn('id', $ids)->update(['status' => 0]);
                break;
            case 'suspend':
                DeviceFingerprint::whereIn('id', $ids)->update(['status' => 2]);
                break;
            case 'delete':
                DeviceFingerprint::whereIn('id', $ids)->delete();
                break;
            default:
                return json(['code' => 400, 'message' => '无效的操作']);
        }

        return json([
            'code' => 200,
            'message' => '批量操作成功'
        ]);
    }

    /**
     * 获取设备类型
     */
    private function getDeviceType($userAgent)
    {
        if (strpos($userAgent, 'Mobile') !== false || 
            strpos($userAgent, 'Android') !== false || 
            strpos($userAgent, 'iPhone') !== false) {
            return 'mobile';
        }
        return 'desktop';
    }

    /**
     * 获取浏览器信息
     */
    private function getBrowser($userAgent)
    {
        if (strpos($userAgent, 'Chrome') !== false) {
            return 'Chrome';
        } elseif (strpos($userAgent, 'Firefox') !== false) {
            return 'Firefox';
        } elseif (strpos($userAgent, 'Safari') !== false && strpos($userAgent, 'Chrome') === false) {
            return 'Safari';
        } elseif (strpos($userAgent, 'Edge') !== false) {
            return 'Edge';
        } elseif (strpos($userAgent, 'Opera') !== false) {
            return 'Opera';
        }
        return '其他';
    }

    /**
     * 获取状态文本
     */
    private function getStatusText($status)
    {
        $statusMap = [
            0 => '禁用',
            1 => '正常',
            2 => '可疑'
        ];
        return $statusMap[$status] ?? '未知';
    }
}






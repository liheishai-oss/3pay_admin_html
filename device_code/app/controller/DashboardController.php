<?php

namespace app\controller;

use support\Request;
use app\model\DeviceFingerprint;
use app\model\DeviceAccessLog;
use app\model\Merchant;
use app\model\Admin;

class DashboardController
{
    /**
     * 获取仪表板统计数据
     */
    public function getStats(Request $request)
    {
        // 总设备数
        $totalDevices = DeviceFingerprint::count();
        
        // 移动设备数（根据user_agent判断）
        $mobileDevices = DeviceFingerprint::where('user_agent', 'like', '%Mobile%')
            ->orWhere('user_agent', 'like', '%Android%')
            ->orWhere('user_agent', 'like', '%iPhone%')
            ->count();
        
        // 桌面设备数
        $desktopDevices = $totalDevices - $mobileDevices;
        
        // 可疑设备数（状态为2的设备）
        $suspiciousDevices = DeviceFingerprint::where('status', 2)->count();
        
        // 今日新增设备
        $todayNewDevices = DeviceFingerprint::whereDate('created_at', date('Y-m-d'))->count();
        
        // 今日访问次数
        $todayAccess = DeviceAccessLog::whereDate('created_at', date('Y-m-d'))->count();
        
        // 活跃商户数
        $activeMerchants = Merchant::where('status', 1)->count();
        
        // 在线管理员数（最近1小时有活动的）
        $onlineAdmins = Admin::where('last_login_at', '>=', date('Y-m-d H:i:s', strtotime('-1 hour')))->count();

        return json([
            'code' => 200,
            'data' => [
                'totalDevices' => $totalDevices,
                'mobileDevices' => $mobileDevices,
                'desktopDevices' => $desktopDevices,
                'suspiciousDevices' => $suspiciousDevices,
                'todayNewDevices' => $todayNewDevices,
                'todayAccess' => $todayAccess,
                'activeMerchants' => $activeMerchants,
                'onlineAdmins' => $onlineAdmins
            ]
        ]);
    }

    /**
     * 获取设备类型分布数据
     */
    public function getDeviceTypeDistribution(Request $request)
    {
        $mobile = DeviceFingerprint::where('user_agent', 'like', '%Mobile%')
            ->orWhere('user_agent', 'like', '%Android%')
            ->orWhere('user_agent', 'like', '%iPhone%')
            ->count();
        
        $desktop = DeviceFingerprint::count() - $mobile;
        
        return json([
            'code' => 200,
            'data' => [
                ['name' => '移动设备', 'value' => $mobile],
                ['name' => '桌面设备', 'value' => $desktop]
            ]
        ]);
    }

    /**
     * 获取访问趋势数据
     */
    public function getAccessTrend(Request $request)
    {
        $days = $request->input('days', 7); // 默认7天
        
        $trendData = [];
        for ($i = $days - 1; $i >= 0; $i--) {
            $date = now()->subDays($i)->format('Y-m-d');
            $count = DeviceAccessLog::whereDate('created_at', $date)->count();
            $trendData[] = [
                'date' => $date,
                'count' => $count
            ];
        }
        
        return json([
            'code' => 200,
            'data' => $trendData
        ]);
    }

    /**
     * 获取最近活动
     */
    public function getRecentActivity(Request $request)
    {
        $limit = $request->input('limit', 10);
        
        $activities = DeviceAccessLog::with(['deviceFingerprint', 'merchant'])
            ->orderBy('created_at', 'desc')
            ->limit($limit)
            ->get()
            ->map(function ($log) {
                return [
                    'id' => $log->id,
                    'action' => $this->getActionText($log->action),
                    'device_code' => $log->deviceFingerprint->device_code ?? 'N/A',
                    'merchant_name' => $log->merchant->merchant_name ?? 'N/A',
                    'ip_address' => $log->ip_address,
                    'created_at' => $log->created_at->format('Y-m-d H:i:s')
                ];
            });
        
        return json([
            'code' => 200,
            'data' => $activities
        ]);
    }

    /**
     * 获取浏览器分布数据
     */
    public function getBrowserDistribution(Request $request)
    {
        $browsers = DeviceFingerprint::selectRaw('
            CASE 
                WHEN user_agent LIKE "%Chrome%" THEN "Chrome"
                WHEN user_agent LIKE "%Firefox%" THEN "Firefox"
                WHEN user_agent LIKE "%Safari%" AND user_agent NOT LIKE "%Chrome%" THEN "Safari"
                WHEN user_agent LIKE "%Edge%" THEN "Edge"
                WHEN user_agent LIKE "%Opera%" THEN "Opera"
                ELSE "其他"
            END as browser,
            COUNT(*) as count
        ')
        ->groupBy('browser')
        ->get()
        ->map(function ($item) {
            return [
                'name' => $item->browser,
                'value' => $item->count
            ];
        });
        
        return json([
            'code' => 200,
            'data' => $browsers
        ]);
    }

    private function getActionText($action)
    {
        $actions = [
            'get_device_code' => '获取设备码',
            'verify_device_code' => '验证设备码',
            'login' => '登录',
            'logout' => '退出'
        ];
        
        return $actions[$action] ?? $action;
    }
}

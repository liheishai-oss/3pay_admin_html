<?php

namespace app\controller;

use support\Request;
use app\model\DeviceFingerprint;

class FingerprintController
{
    /**
     * 获取指纹库列表
     */
    public function index(Request $request)
    {
        $page = $request->input('page', 1);
        $limit = $request->input('limit', 15);
        $search = $request->input('search', '');
        $merchantId = $request->input('merchantId', '');
        $status = $request->input('status', '');

        $query = DeviceFingerprint::with(['merchant']);

        // 搜索条件
        if ($search) {
            $query->where(function ($q) use ($search) {
                $q->where('fingerprint_key', 'like', "%{$search}%")
                  ->orWhere('device_code', 'like', "%{$search}%")
                  ->orWhere('ip_address', 'like', "%{$search}%");
            });
        }

        // 商户筛选
        if ($merchantId) {
            $query->where('merchant_id', $merchantId);
        }

        // 状态筛选
        if ($status !== '') {
            $query->where('status', $status);
        }

        $total = $query->count();
        $fingerprints = $query->orderBy('created_at', 'desc')
            ->offset(($page - 1) * $limit)
            ->limit($limit)
            ->get()
            ->map(function ($fingerprint) {
                return [
                    'id' => $fingerprint->id,
                    'device_code' => $fingerprint->device_code,
                    'merchant_name' => $fingerprint->merchant->merchant_name ?? 'N/A',
                    'device_type' => $this->getDeviceType($fingerprint->user_agent),
                    'browser' => $this->getBrowser($fingerprint->user_agent),
                    'ip_address' => $fingerprint->ip_address,
                    'status' => $fingerprint->status,
                    'status_text' => $this->getStatusText($fingerprint->status),
                    'created_at' => $fingerprint->created_at->format('Y-m-d H:i:s'),
                    'updated_at' => $fingerprint->updated_at->format('Y-m-d H:i:s'),
                    // 添加详细的指纹组件信息
                    'fingerprint_details' => $this->getFingerprintDetails($fingerprint)
                ];
            });

        return json([
            'code' => 200,
            'data' => [
                'list' => $fingerprints,
                'total' => $total,
                'page' => $page,
                'limit' => $limit
            ]
        ]);
    }

    /**
     * 获取指纹详情
     */
    public function show(Request $request)
    {
        $id = $request->input('id');
        $fingerprint = DeviceFingerprint::with(['merchant'])->find($id);

        if (!$fingerprint) {
            return json(['code' => 404, 'message' => '指纹不存在']);
        }

        $fingerprintData = [
            'id' => $fingerprint->id,
            'device_code' => $fingerprint->device_code,
            'merchant_name' => $fingerprint->merchant->merchant_name ?? 'N/A',
            'device_type' => $this->getDeviceType($fingerprint->user_agent),
            'browser' => $this->getBrowser($fingerprint->user_agent),
            'user_agent' => $fingerprint->user_agent,
            'ip_address' => $fingerprint->ip_address,
            'components' => $fingerprint->components,
            'status' => $fingerprint->status,
            'status_text' => $this->getStatusText($fingerprint->status),
            'created_at' => $fingerprint->created_at->format('Y-m-d H:i:s'),
            'updated_at' => $fingerprint->updated_at->format('Y-m-d H:i:s'),
            // 添加详细的指纹组件信息
            'fingerprint_details' => $this->getFingerprintDetails($fingerprint)
        ];

        return json([
            'code' => 200,
            'data' => $fingerprintData
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
     * 分析指纹相似度
     */
    public function analyzeSimilarity(Request $request)
    {
        $id = $request->input('id');
        $fingerprint = DeviceFingerprint::find($id);

        if (!$fingerprint) {
            return json(['code' => 404, 'message' => '指纹不存在']);
        }

        $currentComponents = $fingerprint->components;
        $similarFingerprints = [];

        // 查找相似的指纹
        $allFingerprints = DeviceFingerprint::where('id', '!=', $id)->get();
        
        foreach ($allFingerprints as $fp) {
            $fpComponents = $fp->components;
            $similarity = $this->calculateSimilarity($currentComponents, $fpComponents);
            
            if ($similarity > 0.8) { // 相似度超过80%
                $similarFingerprints[] = [
                    'id' => $fp->id,
                    'device_code' => $fp->device_code,
                    'similarity' => round($similarity * 100, 2),
                    'created_at' => $fp->created_at->format('Y-m-d H:i:s')
                ];
            }
        }

        // 按相似度排序
        usort($similarFingerprints, function($a, $b) {
            return $b['similarity'] <=> $a['similarity'];
        });

        return json([
            'code' => 200,
            'data' => [
                'current_fingerprint' => [
                    'id' => $fingerprint->id,
                    'device_code' => $fingerprint->device_code
                ],
                'similar_fingerprints' => array_slice($similarFingerprints, 0, 10) // 只返回前10个
            ]
        ]);
    }

    /**
     * 获取指纹统计信息
     */
    public function getStatistics(Request $request)
    {
        $total = DeviceFingerprint::count();
        $mobile = DeviceFingerprint::where('user_agent', 'like', '%Mobile%')
            ->orWhere('user_agent', 'like', '%Android%')
            ->orWhere('user_agent', 'like', '%iPhone%')
            ->count();
        $desktop = $total - $mobile;
        $suspicious = DeviceFingerprint::where('status', 2)->count();
        $normal = DeviceFingerprint::where('status', 1)->count();
        $disabled = DeviceFingerprint::where('status', 0)->count();

        // 按浏览器统计
        $browserStats = DeviceFingerprint::selectRaw('
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
        ->get();

        // 按日期统计（最近30天）
        $dateStats = [];
        for ($i = 29; $i >= 0; $i--) {
            $date = date('Y-m-d', strtotime("-{$i} days"));
            $count = DeviceFingerprint::whereDate('created_at', $date)->count();
            $dateStats[] = [
                'date' => $date,
                'count' => $count
            ];
        }

        return json([
            'code' => 200,
            'data' => [
                'total' => $total,
                'mobile' => $mobile,
                'desktop' => $desktop,
                'suspicious' => $suspicious,
                'normal' => $normal,
                'disabled' => $disabled,
                'browser_stats' => $browserStats,
                'date_stats' => $dateStats
            ]
        ]);
    }


    /**
     * 更新指纹状态
     */
    public function updateStatus(Request $request)
    {
        $id = $request->input('id');
        $status = $request->input('status');

        if (!$id || !isset($status)) {
            return json(['code' => 400, 'message' => '缺少必要参数']);
        }

        $fingerprint = DeviceFingerprint::find($id);
        if (!$fingerprint) {
            return json(['code' => 404, 'message' => '指纹不存在']);
        }

        $fingerprint->status = $status;
        $fingerprint->save();

        return json([
            'code' => 200,
            'message' => '状态更新成功'
        ]);
    }

    /**
     * 删除指纹
     */
    public function delete(Request $request)
    {
        $id = $request->input('id');

        $fingerprint = DeviceFingerprint::find($id);
        if (!$fingerprint) {
            return json(['code' => 404, 'message' => '指纹不存在']);
        }

        $fingerprint->delete();

        return json([
            'code' => 200,
            'message' => '删除成功'
        ]);
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

    /**
     * 获取指纹详细信息
     */
    private function getFingerprintDetails($fingerprint)
    {
        return [
            // 基础浏览器信息
            'browser_info' => [
                'user_agent_normalized' => $fingerprint->user_agent_normalized,
                'language' => $fingerprint->language,
                'languages' => $fingerprint->languages,
                'platform' => $fingerprint->platform,
                'cookie_enabled' => $fingerprint->cookie_enabled,
                'do_not_track' => $fingerprint->do_not_track,
                'hardware_concurrency' => $fingerprint->hardware_concurrency,
                'max_touch_points' => $fingerprint->max_touch_points
            ],
            // 屏幕信息
            'screen_info' => [
                'screen_avail_width' => $fingerprint->screen_avail_width,
                'screen_avail_height' => $fingerprint->screen_avail_height,
                'screen_orientation' => $fingerprint->screen_orientation,
                'color_gamut' => $fingerprint->color_gamut,
                'contrast' => $fingerprint->contrast,
                'forced_colors' => $fingerprint->forced_colors,
                'screen_resolution' => $fingerprint->screen_resolution,
                'screen_color_depth' => $fingerprint->screen_color_depth,
                'screen_pixel_depth' => $fingerprint->screen_pixel_depth,
                'device_pixel_ratio' => $fingerprint->device_pixel_ratio
            ],
            // 时区信息
            'timezone_info' => [
                'timezone' => $fingerprint->timezone,
                'timezone_offset' => $fingerprint->timezone_offset
            ],
            // 指纹特征
            'fingerprint_features' => [
                'canvas_fingerprint' => $fingerprint->canvas_fingerprint,
                'webgl_fingerprint' => $fingerprint->webgl_fingerprint,
                'audio_fingerprint' => $fingerprint->audio_fingerprint,
                'fonts' => $fingerprint->fonts,
                'plugins' => $fingerprint->plugins
            ],
            // 系统信息
            'system_info' => [
                'storage_info' => $fingerprint->storage_info,
                'memory_info' => $fingerprint->memory_info,
                'connection_info' => $fingerprint->connection_info,
                'media_devices' => $fingerprint->media_devices,
                'permissions' => $fingerprint->permissions,
                'battery_info' => $fingerprint->battery_info
            ],
            // 高级特征
            'advanced_features' => [
                'gpu_info' => $fingerprint->gpu_info,
                'sensor_info' => $fingerprint->sensor_info,
                'performance_info' => $fingerprint->performance_info,
                'storage_detailed' => $fingerprint->storage_detailed,
                'timezone_detailed' => $fingerprint->timezone_detailed,
                'encoding_info' => $fingerprint->encoding_info,
                'vendor_info' => $fingerprint->vendor_info,
                'capabilities_info' => $fingerprint->capabilities_info,
                'time_stability' => $fingerprint->time_stability
            ]
        ];
    }

    /**
     * 计算指纹相似度
     */
    private function calculateSimilarity($components1, $components2)
    {
        if (empty($components1) || empty($components2)) {
            return 0;
        }

        $totalWeight = 0;
        $matchWeight = 0;

        $weights = [
            'userAgent' => 0.3,
            'screen' => 0.2,
            'canvas' => 0.2,
            'webgl' => 0.15,
            'audio' => 0.1,
            'fonts' => 0.05
        ];

        foreach ($weights as $key => $weight) {
            $totalWeight += $weight;
            
            if (isset($components1[$key]) && isset($components2[$key])) {
                if ($components1[$key] === $components2[$key]) {
                    $matchWeight += $weight;
                }
            }
        }

        return $totalWeight > 0 ? $matchWeight / $totalWeight : 0;
    }
}

<?php

namespace app\controller;

use support\Request;
use app\model\Merchant;
use app\model\DeviceFingerprint;
use app\model\DeviceAccessLog;

class DeviceCodeController
{
    /**
     * 上报设备信息（自动生成设备码）
     */
    public function reportDeviceCode(Request $request)
    {
        $merchantKey = $request->input('merchantKey');
        $fingerprintKey = $request->input('fingerprint_key');
        
        // 调试：记录接收到的原始数据
        $receivedComponents = $request->input('components');
        error_log('接收到的 components 类型: ' . gettype($receivedComponents));
        error_log('接收到的 components 内容: ' . json_encode($receivedComponents));

        if (!$merchantKey || !$fingerprintKey) {
            return json(['code' => 400, 'message' => '缺少必要参数']);
        }

        // 验证商户
        $merchant = Merchant::where('merchant_key', $merchantKey)
            ->where('status', 1)
            ->first();

        if (!$merchant) {
            return json(['code' => 403, 'message' => '商户不存在或已禁用']);
        }

        // 检查商户是否过期
        if ($merchant->isExpired()) {
            return json(['code' => 403, 'message' => '商户已过期']);
        }

        // 查找是否已存在相同指纹的设备
        $existingDevice = DeviceFingerprint::where('merchant_id', $merchant->id)
            ->where('canvas_fingerprint', $fingerprintKey)
            ->first();

        if ($existingDevice) {
            // 如果已存在，更新设备信息
            $components = $request->input('components', []);
            
            $existingDevice->update([
                'user_agent' => $request->header('User-Agent'),
                'ip_address' => $request->getRealIp(),
                'components' => json_encode($components),
                'updated_at' => date('Y-m-d H:i:s'),
                // 更新详细字段
                'user_agent_normalized' => $components['userAgent'] ?? $existingDevice->user_agent_normalized,
                'language' => $components['language'] ?? $existingDevice->language,
                'languages' => isset($components['languages']) ? json_encode($components['languages']) : $existingDevice->languages,
                'platform' => $components['platform'] ?? $existingDevice->platform,
                'cookie_enabled' => $components['cookieEnabled'] ?? $existingDevice->cookie_enabled,
                'hardware_concurrency' => $components['hardwareConcurrency'] ?? $existingDevice->hardware_concurrency,
                'screen_resolution' => $components['screenResolution'] ?? $existingDevice->screen_resolution,
                'timezone' => $components['timezone'] ?? $existingDevice->timezone,
            ]);

            $deviceFingerprint = $existingDevice;
            $isNew = false;
        } else {
            // 生成新的设备码
            $deviceCode = $this->generateUniqueDeviceCode($merchant->id);
            
            // 获取 components 数据
            $components = $request->input('components', []);

            // 创建新的设备指纹记录（解析 components 到各个字段）
            $deviceFingerprint = DeviceFingerprint::create([
                'merchant_id' => $merchant->id,
                'device_code' => $deviceCode,
                'canvas_fingerprint' => $fingerprintKey,
                'user_agent' => $request->header('User-Agent'),
                'ip_address' => $request->getRealIp(),
                'components' => json_encode($components),
                'status' => 1,
                // 基础浏览器信息
                'user_agent_normalized' => $components['userAgent'] ?? null,
                'language' => $components['language'] ?? null,
                'languages' => isset($components['languages']) ? json_encode($components['languages']) : null,
                'platform' => $components['platform'] ?? null,
                'cookie_enabled' => $components['cookieEnabled'] ?? true,
                'do_not_track' => $components['doNotTrack'] ?? null,
                'hardware_concurrency' => $components['hardwareConcurrency'] ?? 0,
                'max_touch_points' => $components['maxTouchPoints'] ?? 0,
                // 屏幕信息
                'screen_avail_width' => $components['screenAvailWidth'] ?? 0,
                'screen_avail_height' => $components['screenAvailHeight'] ?? 0,
                'screen_orientation' => $components['screenOrientation'] ?? null,
                'color_gamut' => $components['colorGamut'] ?? null,
                'contrast' => $components['contrast'] ?? null,
                'forced_colors' => $components['forcedColors'] ?? null,
                'screen_resolution' => $components['screenResolution'] ?? null,
                'screen_color_depth' => $components['screenColorDepth'] ?? null,
                'screen_pixel_depth' => $components['screenPixelDepth'] ?? null,
                'device_pixel_ratio' => $components['devicePixelRatio'] ?? 1.0,
                // 时区信息
                'timezone' => $components['timezone'] ?? null,
                'timezone_offset' => $components['timezoneOffset'] ?? null,
                // 指纹特征（canvas_fingerprint 已经在上面设置）
                'webgl_fingerprint' => $components['webglFingerprint'] ?? null,
                'audio_fingerprint' => $components['audioFingerprint'] ?? null,
                'fonts' => isset($components['fonts']) ? json_encode($components['fonts']) : null,
                'plugins' => isset($components['plugins']) ? json_encode($components['plugins']) : null,
                // 存储和系统信息
                'storage_info' => isset($components['storage']) ? json_encode($components['storage']) : null,
                'memory_info' => isset($components['memoryInfo']) ? json_encode($components['memoryInfo']) : null,
                'connection_info' => isset($components['connectionInfo']) ? json_encode($components['connectionInfo']) : null,
                'media_devices' => isset($components['mediaDevices']) ? json_encode($components['mediaDevices']) : null,
                'permissions' => isset($components['permissions']) ? json_encode($components['permissions']) : null,
                'battery_info' => isset($components['batteryInfo']) ? json_encode($components['batteryInfo']) : null,
                // 高级特征
                'gpu_info' => isset($components['gpuInfo']) ? json_encode($components['gpuInfo']) : null,
                'sensor_info' => isset($components['sensorInfo']) ? json_encode($components['sensorInfo']) : null,
                'performance_info' => isset($components['performanceInfo']) ? json_encode($components['performanceInfo']) : null,
                'storage_detailed' => isset($components['storageInfo']) ? json_encode($components['storageInfo']) : null,
                'timezone_detailed' => isset($components['timezoneInfo']) ? json_encode($components['timezoneInfo']) : null,
                'encoding_info' => isset($components['encodingInfo']) ? json_encode($components['encodingInfo']) : null,
                'vendor_info' => isset($components['vendorInfo']) ? json_encode($components['vendorInfo']) : null,
                'capabilities_info' => isset($components['capabilitiesInfo']) ? json_encode($components['capabilitiesInfo']) : null,
                'time_stability' => isset($components['timeStability']) ? json_encode($components['timeStability']) : null,
            ]);

            $isNew = true;
        }

        // 记录访问日志
        DeviceAccessLog::create([
            'device_fingerprint_id' => $deviceFingerprint->id,
            'merchant_id' => $merchant->id,
            'action' => $isNew ? 'create_device' : 'update_device',
            'ip_address' => $request->getRealIp(),
            'user_agent' => $request->header('User-Agent'),
            'request_data' => $request->all(),
            'response_data' => ['device_code' => $deviceFingerprint->device_code]
        ]);

        return json([
            'code' => 200,
            'message' => $isNew ? '设备创建成功' : '设备信息更新成功',
            'data' => [
                'fingerprint_key' => $deviceFingerprint->canvas_fingerprint,  // 返回前端指纹
                'device_code' => $deviceFingerprint->device_code,              // 返回后端生成的设备码
                'is_new' => $isNew,
                'updated_at' => $deviceFingerprint->updated_at
            ]
        ]);
    }

    /**
     * 生成唯一设备码
     */
    private function generateUniqueDeviceCode($merchantId)
    {
        do {
            $deviceCode = 'DEV_' . $merchantId . '_' . time() . '_' . mt_rand(1000, 9999);
        } while (DeviceFingerprint::where('device_code', $deviceCode)->exists());

        return $deviceCode;
    }

    /**
     * 验证指纹
     */
    public function verifyFingerprint(Request $request)
    {
        $fingerprintKey = $request->input('fingerprint_key');
        $merchantKey = $request->input('merchantKey');

        if (!$fingerprintKey || !$merchantKey) {
            return json(['code' => 400, 'message' => '缺少必要参数']);
        }

        // 验证商户
        $merchant = Merchant::where('merchant_key', $merchantKey)
            ->where('status', 1)
            ->first();

        if (!$merchant) {
            return json(['code' => 403, 'message' => '商户不存在或已禁用']);
        }

        // 查找设备指纹记录
        $deviceFingerprint = DeviceFingerprint::where('merchant_id', $merchant->id)
            ->where('canvas_fingerprint', $fingerprintKey)
            ->where('status', 1)
            ->first();

        if (!$deviceFingerprint) {
            return json(['code' => 404, 'message' => '指纹不存在或已禁用']);
        }

        // 记录访问日志
        DeviceAccessLog::create([
            'device_fingerprint_id' => $deviceFingerprint->id,
            'merchant_id' => $merchant->id,
            'action' => 'verify_fingerprint',
            'ip_address' => $request->getRealIp(),
            'user_agent' => $request->header('User-Agent'),
            'request_data' => $request->all(),
            'response_data' => ['valid' => true]
        ]);

        return json([
            'code' => 200,
            'message' => '验证成功',
            'data' => [
                'valid' => true,
                'fingerprint_key' => $deviceFingerprint->canvas_fingerprint,  // 返回前端指纹
                'device_code' => $deviceFingerprint->device_code,              // 返回设备码
                'created_at' => $deviceFingerprint->created_at
            ]
        ]);
    }
}

<?php

namespace app\model;

use support\Model;

class DeviceFingerprint extends Model
{
    protected $connection = 'mysql';
    protected $table = 'device_fingerprints';
    
    protected $fillable = [
        'merchant_id', 'device_code', 'user_agent', 
        'ip_address', 'components', 'status',
        // 基础浏览器信息
        'user_agent_normalized', 'language', 'languages', 'platform',
        'cookie_enabled', 'do_not_track', 'hardware_concurrency', 'max_touch_points',
        // 屏幕信息
        'screen_avail_width', 'screen_avail_height', 'screen_orientation',
        'color_gamut', 'contrast', 'forced_colors', 'screen_resolution',
        'screen_color_depth', 'screen_pixel_depth', 'device_pixel_ratio',
        // 时区信息
        'timezone', 'timezone_offset',
        // 指纹特征
        'canvas_fingerprint', 'webgl_fingerprint', 'audio_fingerprint',
        'fonts', 'plugins',
        // 存储和系统信息
        'storage_info', 'memory_info', 'connection_info', 'media_devices',
        'permissions', 'battery_info',
        // 高级特征
        'gpu_info', 'sensor_info', 'performance_info', 'storage_detailed',
        'timezone_detailed', 'encoding_info', 'vendor_info', 'capabilities_info',
        'time_stability'
    ];
    
    protected $casts = [
        'components' => 'array',
        'status' => 'boolean',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    /**
     * 获取商户
     */
    public function merchant()
    {
        return $this->belongsTo(Merchant::class, 'merchant_id');
    }

    /**
     * 获取访问日志
     */
    public function accessLogs()
    {
        return $this->hasMany(DeviceAccessLog::class, 'device_fingerprint_id');
    }

}

<?php

namespace app\model;

use support\Model;

class DeviceAccessLog extends Model
{
    protected $table = 'device_access_logs';
    
    protected $fillable = [
        'device_fingerprint_id', 'merchant_id', 'action', 'ip_address', 
        'user_agent', 'request_data', 'response_data'
    ];
    
    protected $casts = [
        'request_data' => 'array',
        'response_data' => 'array',
        'created_at' => 'datetime',
    ];

    /**
     * 获取设备指纹
     */
    public function deviceFingerprint()
    {
        return $this->belongsTo(DeviceFingerprint::class, 'device_fingerprint_id');
    }

    /**
     * 获取商户
     */
    public function merchant()
    {
        return $this->belongsTo(Merchant::class, 'merchant_id');
    }
}






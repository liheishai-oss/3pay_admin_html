<?php

namespace app\model;

use support\Model;

class Merchant extends Model
{
    protected $connection = 'mysql';
    protected $table = 'merchants';
    
    protected $fillable = [
        'merchant_key', 'merchant_name', 'contact_person', 'contact_phone', 
        'contact_email', 'api_secret', 'status', 'expires_at'
    ];
    
    protected $casts = [
        'status' => 'boolean',
        'expires_at' => 'datetime',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    /**
     * 获取设备指纹
     */
    public function deviceFingerprints()
    {
        return $this->hasMany(DeviceFingerprint::class, 'merchant_id');
    }

    /**
     * 验证API密钥
     */
    public function verifyApiSecret($secret)
    {
        return $this->api_secret === $secret;
    }

    /**
     * 检查是否过期
     */
    public function isExpired()
    {
        return $this->expires_at && $this->expires_at->isPast();
    }
}

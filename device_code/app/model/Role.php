<?php

namespace app\model;

use support\Model;

class Role extends Model
{
    protected $table = 'roles';
    
    protected $fillable = [
        'name', 'description', 'permissions', 'status'
    ];
    
    protected $casts = [
        'permissions' => 'array',
        'status' => 'boolean',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    /**
     * 获取管理员
     */
    public function admins()
    {
        return $this->belongsToMany(Admin::class, 'admin_roles', 'role_id', 'admin_id');
    }
}






<?php

namespace app\model;

use support\Model;

class Admin extends Model
{
    protected $connection = 'mysql';
    protected $table = 'admins';
    
    protected $fillable = [
        'username', 'email', 'password', 'real_name', 'avatar', 'status'
    ];
    
    protected $hidden = [
        'password'
    ];
    
    protected $casts = [
        'status' => 'boolean',
        'last_login_at' => 'datetime',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    /**
     * 验证密码
     */
    public function verifyPassword($password)
    {
        return password_verify($password, $this->password);
    }

    /**
     * 设置密码
     */
    public function setPassword($password)
    {
        $this->password = password_hash($password, PASSWORD_DEFAULT);
    }

    /**
     * 获取角色
     */
    public function roles()
    {
        return $this->belongsToMany(Role::class, 'admin_roles', 'admin_id', 'role_id');
    }

    /**
     * 检查权限
     */
    public function hasPermission($permission)
    {
        $roles = $this->roles()->where('status', 1)->get();
        
        foreach ($roles as $role) {
            $permissions = json_decode($role->permissions, true);
            if (in_array('*', $permissions) || in_array($permission, $permissions)) {
                return true;
            }
        }
        
        return false;
    }

    /**
     * 更新最后登录信息
     */
    public function updateLastLogin($ip)
    {
        $this->update([
            'last_login_at' => date('Y-m-d H:i:s'),
            'last_login_ip' => $ip
        ]);
    }
}

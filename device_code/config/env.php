<?php

return [
    // 数据库配置
    'DB_HOST' => 'mysql',
    'DB_PORT' => 3306,
    'DB_DATABASE' => 'device_fingerprint',
    'DB_USERNAME' => 'device_user',
    'DB_PASSWORD' => 'device_password',
    
    // JWT配置
    'JWT_SECRET' => 'your_jwt_secret_key_here_change_in_production',
    'JWT_ALGORITHM' => 'HS256',
    'JWT_EXPIRE' => 7200,
    
    // 应用配置
    'APP_DEBUG' => true,
    'APP_NAME' => '设备指纹管理系统',
    'APP_URL' => 'http://localhost:8787',
    
    // 管理员配置
    'ADMIN_EMAIL' => 'admin@example.com',
    'ADMIN_PASSWORD' => 'admin123456',
];

-- 创建数据库和用户
CREATE DATABASE IF NOT EXISTS `device_fingerprint` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;

-- 创建用户并授权
CREATE USER IF NOT EXISTS 'device_user'@'%' IDENTIFIED BY 'device_password';
GRANT ALL PRIVILEGES ON `device_fingerprint`.* TO 'device_user'@'%';
FLUSH PRIVILEGES;

-- 使用数据库
USE `device_fingerprint`;

-- 管理员表
CREATE TABLE IF NOT EXISTS `admins` (
    `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
    `username` varchar(50) NOT NULL COMMENT '用户名',
    `email` varchar(100) NOT NULL COMMENT '邮箱',
    `password` varchar(255) NOT NULL COMMENT '密码',
    `real_name` varchar(50) DEFAULT NULL COMMENT '真实姓名',
    `avatar` varchar(255) DEFAULT NULL COMMENT '头像',
    `status` tinyint(1) NOT NULL DEFAULT '1' COMMENT '状态：1=正常，0=禁用',
    `last_login_at` timestamp NULL DEFAULT NULL COMMENT '最后登录时间',
    `last_login_ip` varchar(45) DEFAULT NULL COMMENT '最后登录IP',
    `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
    `updated_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    UNIQUE KEY `username` (`username`),
    UNIQUE KEY `email` (`email`),
    KEY `status` (`status`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='管理员表';

-- 角色表
CREATE TABLE IF NOT EXISTS `roles` (
    `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
    `name` varchar(50) NOT NULL COMMENT '角色名称',
    `description` varchar(255) DEFAULT NULL COMMENT '角色描述',
    `permissions` text COMMENT '权限列表（JSON格式）',
    `status` tinyint(1) NOT NULL DEFAULT '1' COMMENT '状态：1=正常，0=禁用',
    `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
    `updated_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    UNIQUE KEY `name` (`name`),
    KEY `status` (`status`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='角色表';

-- 管理员角色关联表
CREATE TABLE IF NOT EXISTS `admin_roles` (
    `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
    `admin_id` bigint(20) unsigned NOT NULL COMMENT '管理员ID',
    `role_id` bigint(20) unsigned NOT NULL COMMENT '角色ID',
    `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    UNIQUE KEY `admin_role` (`admin_id`, `role_id`),
    KEY `admin_id` (`admin_id`),
    KEY `role_id` (`role_id`),
    CONSTRAINT `admin_roles_admin_id_foreign` FOREIGN KEY (`admin_id`) REFERENCES `admins` (`id`) ON DELETE CASCADE,
    CONSTRAINT `admin_roles_role_id_foreign` FOREIGN KEY (`role_id`) REFERENCES `roles` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='管理员角色关联表';

-- 商户表
CREATE TABLE IF NOT EXISTS `merchants` (
    `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
    `merchant_key` varchar(100) NOT NULL COMMENT '商户Key',
    `merchant_name` varchar(100) NOT NULL COMMENT '商户名称',
    `contact_person` varchar(50) DEFAULT NULL COMMENT '联系人',
    `contact_phone` varchar(20) DEFAULT NULL COMMENT '联系电话',
    `contact_email` varchar(100) DEFAULT NULL COMMENT '联系邮箱',
    `api_secret` varchar(255) NOT NULL COMMENT 'API密钥',
    `status` tinyint(1) NOT NULL DEFAULT '1' COMMENT '状态：1=正常，0=禁用',
    `expires_at` timestamp NULL DEFAULT NULL COMMENT '过期时间',
    `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
    `updated_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    UNIQUE KEY `merchant_key` (`merchant_key`),
    KEY `status` (`status`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='商户表';

-- 设备指纹表
CREATE TABLE IF NOT EXISTS `device_fingerprints` (
    `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
    `merchant_id` bigint(20) unsigned NOT NULL COMMENT '商户ID',
    `fingerprint_key` varchar(32) NOT NULL COMMENT '指纹Key（MD5）',
    `device_code` varchar(100) NOT NULL COMMENT '设备码',
    `user_agent` text COMMENT '用户代理',
    `ip_address` varchar(45) DEFAULT NULL COMMENT 'IP地址',
    `components` longtext COMMENT '设备特征组件（JSON格式）',
    `status` tinyint(1) NOT NULL DEFAULT '1' COMMENT '状态：1=正常，0=禁用',
    `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
    `updated_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    UNIQUE KEY `merchant_fingerprint` (`merchant_id`, `fingerprint_key`),
    UNIQUE KEY `device_code` (`device_code`),
    KEY `merchant_id` (`merchant_id`),
    KEY `fingerprint_key` (`fingerprint_key`),
    KEY `status` (`status`),
    CONSTRAINT `device_fingerprints_merchant_id_foreign` FOREIGN KEY (`merchant_id`) REFERENCES `merchants` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='设备指纹表';

-- 设备访问日志表
CREATE TABLE IF NOT EXISTS `device_access_logs` (
    `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
    `device_fingerprint_id` bigint(20) unsigned NOT NULL COMMENT '设备指纹ID',
    `merchant_id` bigint(20) unsigned NOT NULL COMMENT '商户ID',
    `action` varchar(50) NOT NULL COMMENT '操作类型',
    `ip_address` varchar(45) DEFAULT NULL COMMENT 'IP地址',
    `user_agent` text COMMENT '用户代理',
    `request_data` longtext COMMENT '请求数据（JSON格式）',
    `response_data` longtext COMMENT '响应数据（JSON格式）',
    `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    KEY `device_fingerprint_id` (`device_fingerprint_id`),
    KEY `merchant_id` (`merchant_id`),
    KEY `action` (`action`),
    KEY `created_at` (`created_at`),
    CONSTRAINT `device_access_logs_device_fingerprint_id_foreign` FOREIGN KEY (`device_fingerprint_id`) REFERENCES `device_fingerprints` (`id`) ON DELETE CASCADE,
    CONSTRAINT `device_access_logs_merchant_id_foreign` FOREIGN KEY (`merchant_id`) REFERENCES `merchants` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='设备访问日志表';

-- 系统配置表
CREATE TABLE IF NOT EXISTS `system_configs` (
    `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
    `config_key` varchar(100) NOT NULL COMMENT '配置键',
    `config_value` text COMMENT '配置值',
    `config_type` varchar(20) NOT NULL DEFAULT 'string' COMMENT '配置类型',
    `description` varchar(255) DEFAULT NULL COMMENT '配置描述',
    `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
    `updated_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    UNIQUE KEY `config_key` (`config_key`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='系统配置表';

-- 插入默认数据
INSERT INTO `admins` (`username`, `email`, `password`, `real_name`, `status`) VALUES
('admin', 'admin@example.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', '超级管理员', 1);

INSERT INTO `roles` (`name`, `description`, `permissions`, `status`) VALUES
('super_admin', '超级管理员', '["*"]', 1),
('admin', '管理员', '["device.view", "device.create", "device.update", "merchant.view", "merchant.create", "merchant.update"]', 1),
('operator', '操作员', '["device.view", "device.create"]', 1);

INSERT INTO `admin_roles` (`admin_id`, `role_id`) VALUES
(1, 1);

INSERT INTO `merchants` (`merchant_key`, `merchant_name`, `contact_person`, `contact_phone`, `contact_email`, `api_secret`, `status`) VALUES
('test_merchant_123', '测试商户', '测试联系人', '13800138000', 'test@example.com', 'test_api_secret_key_123', 1);

INSERT INTO `system_configs` (`config_key`, `config_value`, `config_type`, `description`) VALUES
('system_name', '设备指纹管理系统', 'string', '系统名称'),
('system_version', '1.0.0', 'string', '系统版本'),
('jwt_secret', 'your_jwt_secret_key_here_change_in_production', 'string', 'JWT密钥'),
('jwt_expire', '7200', 'integer', 'JWT过期时间（秒）'),
('max_device_count', '10000', 'integer', '每个商户最大设备数量');



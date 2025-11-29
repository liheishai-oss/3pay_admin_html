-- 移除 device_code 字段
ALTER TABLE `device_fingerprints` DROP COLUMN `device_code`;

-- 更新索引，移除 device_code 相关索引
-- 注意：MySQL 会自动删除相关的索引



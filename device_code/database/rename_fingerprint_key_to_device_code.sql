-- 将 fingerprint_key 字段重命名为 device_code
ALTER TABLE `device_fingerprints` CHANGE COLUMN `fingerprint_key` `device_code` VARCHAR(255) NOT NULL COMMENT '设备码（前端生成）';

-- 更新索引
DROP INDEX `idx_fingerprint_key` ON `device_fingerprints`;
CREATE INDEX `idx_device_code` ON `device_fingerprints` (`device_code`);



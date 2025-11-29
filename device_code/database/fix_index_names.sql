-- 修复索引名称
DROP INDEX `fingerprint_key` ON `device_fingerprints`;
CREATE INDEX `idx_device_code` ON `device_fingerprints` (`device_code`);



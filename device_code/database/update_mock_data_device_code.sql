-- 更新模拟数据，将 fingerprint_key 改为 device_code
UPDATE `device_fingerprints` SET `device_code` = 'fp_mobile_001_abc123' WHERE `device_code` = 'DC20251024M001A1B2';
UPDATE `device_fingerprints` SET `device_code` = 'fp_mobile_002_def456' WHERE `device_code` = 'DC20251024M002C3D4';
UPDATE `device_fingerprints` SET `device_code` = 'fp_desktop_001_ghi789' WHERE `device_code` = 'DC20251024D001E5F6';
UPDATE `device_fingerprints` SET `device_code` = 'fp_desktop_002_jkl012' WHERE `device_code` = 'DC20251024D002G7H8';
UPDATE `device_fingerprints` SET `device_code` = 'fp_suspicious_001_mno345' WHERE `device_code` = 'DC20251024S001I9J0';
UPDATE `device_fingerprints` SET `device_code` = 'fp_disabled_001_pqr678' WHERE `device_code` = 'DC20251024X001K1L2';



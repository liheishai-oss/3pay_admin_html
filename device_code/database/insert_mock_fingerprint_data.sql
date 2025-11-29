-- 插入模拟指纹数据
-- 基于 device-fingerprint.js 的组件结构

-- 清理现有数据
DELETE FROM device_fingerprints WHERE id > 0;

-- 插入模拟指纹数据
INSERT INTO device_fingerprints (
    merchant_id, fingerprint_key, device_code, user_agent, ip_address, components, status,
    -- 基础浏览器信息
    user_agent_normalized, language, languages, platform, cookie_enabled, do_not_track, 
    hardware_concurrency, max_touch_points,
    -- 屏幕信息
    screen_avail_width, screen_avail_height, screen_orientation, color_gamut, contrast, 
    forced_colors, screen_resolution, screen_color_depth, screen_pixel_depth, device_pixel_ratio,
    -- 时区信息
    timezone, timezone_offset,
    -- 指纹特征
    canvas_fingerprint, webgl_fingerprint, audio_fingerprint, fonts, plugins,
    -- 存储和系统信息
    storage_info, memory_info, connection_info, media_devices, permissions, battery_info,
    -- 高级特征
    gpu_info, sensor_info, performance_info, storage_detailed, timezone_detailed, 
    encoding_info, vendor_info, capabilities_info, time_stability,
    created_at, updated_at
) VALUES 
-- 移动设备指纹 1
(1, 'fp_mobile_001_abc123', 'DC20251024M001A1B2', 
 'Mozilla/5.0 (iPhone; CPU iPhone OS 17_2 like Mac OS X) AppleWebKit/605.1.15 (KHTML, like Gecko) Version/17.2 Mobile/15E148 Safari/604.1',
 '192.168.1.101', '{"userAgent":"iPhone Safari","screen":"1179x2556","canvas":"canvas_mobile_001"}', 1,
 -- 基础浏览器信息
 'Mozilla/5.0 (iPhone; CPU iPhone OS 17_2 like Mac OS X) AppleWebKit/605.1.15 (KHTML, like Gecko) Version/17.2 Mobile/15E148 Safari/604.1',
 'zh-CN', 'zh-CN,en-US,en', 'iPhone', 1, '1', 6, 5,
 -- 屏幕信息
 1179, 2556, 'portrait-primary', 'p3', 'high', 'none', '1179x2556', 24, 24, 3.0,
 -- 时区信息
 'Asia/Shanghai', -480,
 -- 指纹特征
 'canvas_mobile_001_hash', 'webgl_mobile_001_hash', 'audio_mobile_001_hash', 
 'Arial,Helvetica,sans-serif,Georgia,Times New Roman', 'Safari PDF Plugin,WebKit built-in PDF',
 -- 存储和系统信息
 '{"localStorage":5000000,"sessionStorage":10000000}', '{"deviceMemory":8}', '{"hasConnectionAPI":true}', 
 '{"audioInput":1,"videoInput":1,"audioOutput":1}', '{"camera":"granted","microphone":"granted"}', '{"charging":true,"level":0.85}',
 -- 高级特征
 '{"vendor":"Apple","renderer":"Apple A17 Pro","version":"WebGL 2.0"}', '{"accelerometer":true,"gyroscope":true}', 
 '{"jsHeapSizeLimit":4294705152}', '{"quota":50000000000,"usage":1200000000}', '{"timezone":"Asia/Shanghai","locale":"zh-CN"}',
 '{"textEncoding":"utf-8","contentEncoding":"gzip"}', '{"vendor":"Apple","product":"iPhone"}', 
 '{"webgl":true,"canvas":true,"audio":true}', '{"timezone":"Asia/Shanghai"}',
 NOW(), NOW()),

-- 移动设备指纹 2 (Android)
(1, 'fp_mobile_002_def456', 'DC20251024M002C3D4', 
 'Mozilla/5.0 (Linux; Android 14; SM-G998B) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/120.0.0.0 Mobile Safari/537.36',
 '192.168.1.102', '{"userAgent":"Android Chrome","screen":"1080x2400","canvas":"canvas_mobile_002"}', 1,
 -- 基础浏览器信息
 'Mozilla/5.0 (Linux; Android 14; SM-G998B) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/120.0.0.0 Mobile Safari/537.36',
 'zh-CN', 'zh-CN,en-US,en', 'Linux armv8l', 1, '0', 8, 10,
 -- 屏幕信息
 1080, 2400, 'portrait-primary', 'srgb', 'high', 'none', '1080x2400', 24, 24, 2.75,
 -- 时区信息
 'Asia/Shanghai', -480,
 -- 指纹特征
 'canvas_mobile_002_hash', 'webgl_mobile_002_hash', 'audio_mobile_002_hash', 
 'Roboto,Arial,Helvetica,sans-serif', 'Chrome PDF Plugin,Chrome PDF Viewer',
 -- 存储和系统信息
 '{"localStorage":10000000,"sessionStorage":10000000}', '{"deviceMemory":8}', '{"hasConnectionAPI":true}', 
 '{"audioInput":1,"videoInput":2,"audioOutput":1}', '{"camera":"granted","microphone":"granted"}', '{"charging":false,"level":0.65}',
 -- 高级特征
 '{"vendor":"Google Inc.","renderer":"Mali-G78 MP14","version":"WebGL 2.0"}', '{"accelerometer":true,"gyroscope":true,"magnetometer":true}', 
 '{"jsHeapSizeLimit":4294705152}', '{"quota":50000000000,"usage":2500000000}', '{"timezone":"Asia/Shanghai","locale":"zh-CN"}',
 '{"textEncoding":"utf-8","contentEncoding":"gzip"}', '{"vendor":"Samsung","product":"SM-G998B"}', 
 '{"webgl":true,"canvas":true,"audio":true,"webgl2":true}', '{"timezone":"Asia/Shanghai"}',
 NOW(), NOW()),

-- 桌面设备指纹 1 (Windows Chrome)
(1, 'fp_desktop_001_ghi789', 'DC20251024D001E5F6', 
 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/120.0.0.0 Safari/537.36',
 '192.168.1.201', '{"userAgent":"Windows Chrome","screen":"1920x1080","canvas":"canvas_desktop_001"}', 1,
 -- 基础浏览器信息
 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/120.0.0.0 Safari/537.36',
 'en-US', 'en-US,en', 'Win32', 1, '0', 16, 0,
 -- 屏幕信息
 1920, 1080, 'landscape-primary', 'srgb', 'high', 'none', '1920x1080', 24, 24, 1.0,
 -- 时区信息
 'America/New_York', 300,
 -- 指纹特征
 'canvas_desktop_001_hash', 'webgl_desktop_001_hash', 'audio_desktop_001_hash', 
 'Arial,Helvetica,sans-serif,Georgia,Times New Roman,Consolas,Monaco', 'Chrome PDF Plugin,Chrome PDF Viewer,Microsoft Edge PDF Viewer',
 -- 存储和系统信息
 '{"localStorage":10000000,"sessionStorage":10000000}', '{"deviceMemory":16}', '{"hasConnectionAPI":true}', 
 '{"audioInput":1,"videoInput":1,"audioOutput":2}', '{"camera":"granted","microphone":"granted"}', '{"charging":true,"level":1.0}',
 -- 高级特征
 '{"vendor":"Google Inc.","renderer":"ANGLE (Intel(R) UHD Graphics 630 Direct3D11 vs_5_0 ps_5_0)","version":"WebGL 2.0"}', '{"accelerometer":false,"gyroscope":false}', 
 '{"jsHeapSizeLimit":4294705152}', '{"quota":50000000000,"usage":5000000000}', '{"timezone":"America/New_York","locale":"en-US"}',
 '{"textEncoding":"utf-8","contentEncoding":"gzip"}', '{"vendor":"Microsoft","product":"Windows"}', 
 '{"webgl":true,"canvas":true,"audio":true,"webgl2":true,"webxr":false}', '{"timezone":"America/New_York"}',
 NOW(), NOW()),

-- 桌面设备指纹 2 (Mac Safari)
(1, 'fp_desktop_002_jkl012', 'DC20251024D002G7H8', 
 'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit/605.1.15 (KHTML, like Gecko) Version/17.2 Safari/605.1.15',
 '192.168.1.202', '{"userAgent":"Mac Safari","screen":"2560x1440","canvas":"canvas_desktop_002"}', 1,
 -- 基础浏览器信息
 'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit/605.1.15 (KHTML, like Gecko) Version/17.2 Safari/605.1.15',
 'en-US', 'en-US,en', 'MacIntel', 1, '0', 8, 0,
 -- 屏幕信息
 2560, 1440, 'landscape-primary', 'p3', 'high', 'none', '2560x1440', 24, 24, 2.0,
 -- 时区信息
 'America/Los_Angeles', 480,
 -- 指纹特征
 'canvas_desktop_002_hash', 'webgl_desktop_002_hash', 'audio_desktop_002_hash', 
 'Arial,Helvetica,sans-serif,Georgia,Times New Roman,Menlo,Monaco', 'Safari PDF Plugin,WebKit built-in PDF',
 -- 存储和系统信息
 '{"localStorage":10000000,"sessionStorage":10000000}', '{"deviceMemory":16}', '{"hasConnectionAPI":true}', 
 '{"audioInput":1,"videoInput":1,"audioOutput":2}', '{"camera":"granted","microphone":"granted"}', '{"charging":true,"level":0.95}',
 -- 高级特征
 '{"vendor":"Apple Inc.","renderer":"Intel(R) Iris(TM) Plus Graphics 655","version":"WebGL 2.0"}', '{"accelerometer":false,"gyroscope":false}', 
 '{"jsHeapSizeLimit":4294705152}', '{"quota":50000000000,"usage":3000000000}', '{"timezone":"America/Los_Angeles","locale":"en-US"}',
 '{"textEncoding":"utf-8","contentEncoding":"gzip"}', '{"vendor":"Apple","product":"MacBook Pro"}', 
 '{"webgl":true,"canvas":true,"audio":true,"webgl2":true,"webxr":false}', '{"timezone":"America/Los_Angeles"}',
 NOW(), NOW()),

-- 可疑设备指纹
(1, 'fp_suspicious_001_mno345', 'DC20251024S001I9J0', 
 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/120.0.0.0 Safari/537.36',
 '192.168.1.999', '{"userAgent":"Suspicious Chrome","screen":"1920x1080","canvas":"canvas_suspicious_001"}', 2,
 -- 基础浏览器信息
 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/120.0.0.0 Safari/537.36',
 'en-US', 'en-US,en', 'Win32', 1, '0', 16, 0,
 -- 屏幕信息
 1920, 1080, 'landscape-primary', 'srgb', 'high', 'none', '1920x1080', 24, 24, 1.0,
 -- 时区信息
 'UTC', 0,
 -- 指纹特征
 'canvas_suspicious_001_hash', 'webgl_suspicious_001_hash', 'audio_suspicious_001_hash', 
 'Arial,Helvetica,sans-serif', 'Chrome PDF Plugin',
 -- 存储和系统信息
 '{"localStorage":10000000,"sessionStorage":10000000}', '{"deviceMemory":16}', '{"hasConnectionAPI":true}', 
 '{"audioInput":1,"videoInput":1,"audioOutput":1}', '{"camera":"denied","microphone":"denied"}', '{"charging":true,"level":1.0}',
 -- 高级特征
 '{"vendor":"Google Inc.","renderer":"ANGLE (NVIDIA GeForce RTX 3080 Direct3D11 vs_5_0 ps_5_0)","version":"WebGL 2.0"}', '{"accelerometer":false,"gyroscope":false}', 
 '{"jsHeapSizeLimit":4294705152}', '{"quota":50000000000,"usage":8000000000}', '{"timezone":"UTC","locale":"en-US"}',
 '{"textEncoding":"utf-8","contentEncoding":"gzip"}', '{"vendor":"Unknown","product":"Unknown"}', 
 '{"webgl":true,"canvas":true,"audio":true,"webgl2":true,"webxr":false}', '{"timezone":"UTC"}',
 NOW(), NOW()),

-- 禁用设备指纹
(1, 'fp_disabled_001_pqr678', 'DC20251024X001K1L2', 
 'Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/120.0.0.0 Safari/537.36',
 '192.168.1.888', '{"userAgent":"Linux Chrome","screen":"1366x768","canvas":"canvas_disabled_001"}', 0,
 -- 基础浏览器信息
 'Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/120.0.0.0 Safari/537.36',
 'en-US', 'en-US,en', 'Linux x86_64', 1, '0', 4, 0,
 -- 屏幕信息
 1366, 768, 'landscape-primary', 'srgb', 'high', 'none', '1366x768', 24, 24, 1.0,
 -- 时区信息
 'Europe/London', -60,
 -- 指纹特征
 'canvas_disabled_001_hash', 'webgl_disabled_001_hash', 'audio_disabled_001_hash', 
 'Arial,Helvetica,sans-serif,DejaVu Sans', 'Chrome PDF Plugin',
 -- 存储和系统信息
 '{"localStorage":10000000,"sessionStorage":10000000}', '{"deviceMemory":8}', '{"hasConnectionAPI":true}', 
 '{"audioInput":1,"videoInput":1,"audioOutput":1}', '{"camera":"prompt","microphone":"prompt"}', '{"charging":true,"level":0.8}',
 -- 高级特征
 '{"vendor":"Google Inc.","renderer":"ANGLE (Intel(R) HD Graphics 620 Direct3D11 vs_5_0 ps_5_0)","version":"WebGL 2.0"}', '{"accelerometer":false,"gyroscope":false}', 
 '{"jsHeapSizeLimit":4294705152}', '{"quota":50000000000,"usage":2000000000}', '{"timezone":"Europe/London","locale":"en-GB"}',
 '{"textEncoding":"utf-8","contentEncoding":"gzip"}', '{"vendor":"Unknown","product":"Linux"}', 
 '{"webgl":true,"canvas":true,"audio":true,"webgl2":true,"webxr":false}', '{"timezone":"Europe/London"}',
 NOW(), NOW());



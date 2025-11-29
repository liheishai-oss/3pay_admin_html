-- 更新设备指纹表，添加详细的指纹组件字段
-- 基于 device-fingerprint.js 中的 collectComponents 方法

-- 添加基础浏览器信息字段
ALTER TABLE device_fingerprints 
ADD COLUMN user_agent_normalized VARCHAR(500) COMMENT '标准化的UserAgent',
ADD COLUMN language VARCHAR(50) COMMENT '浏览器语言',
ADD COLUMN languages TEXT COMMENT '支持的语言列表',
ADD COLUMN platform VARCHAR(100) COMMENT '操作系统平台',
ADD COLUMN cookie_enabled BOOLEAN DEFAULT TRUE COMMENT 'Cookie是否启用',
ADD COLUMN do_not_track VARCHAR(10) COMMENT 'DoNotTrack设置',
ADD COLUMN hardware_concurrency INT DEFAULT 0 COMMENT 'CPU核心数',
ADD COLUMN max_touch_points INT DEFAULT 0 COMMENT '最大触摸点数';

-- 添加屏幕信息字段
ALTER TABLE device_fingerprints 
ADD COLUMN screen_avail_width INT DEFAULT 0 COMMENT '可用屏幕宽度',
ADD COLUMN screen_avail_height INT DEFAULT 0 COMMENT '可用屏幕高度',
ADD COLUMN screen_orientation VARCHAR(20) COMMENT '屏幕方向',
ADD COLUMN color_gamut VARCHAR(20) COMMENT '色域',
ADD COLUMN contrast VARCHAR(20) COMMENT '对比度',
ADD COLUMN forced_colors VARCHAR(20) COMMENT '强制颜色',
ADD COLUMN screen_resolution VARCHAR(20) COMMENT '屏幕分辨率',
ADD COLUMN screen_color_depth INT COMMENT '颜色深度',
ADD COLUMN screen_pixel_depth INT COMMENT '像素深度',
ADD COLUMN device_pixel_ratio DECIMAL(3,2) DEFAULT 1.0 COMMENT '设备像素比';

-- 添加时区信息字段
ALTER TABLE device_fingerprints 
ADD COLUMN timezone VARCHAR(50) COMMENT '时区',
ADD COLUMN timezone_offset INT COMMENT '时区偏移量';

-- 添加指纹特征字段
ALTER TABLE device_fingerprints 
ADD COLUMN canvas_fingerprint VARCHAR(100) COMMENT 'Canvas指纹',
ADD COLUMN webgl_fingerprint VARCHAR(100) COMMENT 'WebGL指纹',
ADD COLUMN audio_fingerprint VARCHAR(100) COMMENT '音频指纹',
ADD COLUMN fonts TEXT COMMENT '已安装字体',
ADD COLUMN plugins TEXT COMMENT '浏览器插件信息';

-- 添加存储信息字段
ALTER TABLE device_fingerprints 
ADD COLUMN storage_info TEXT COMMENT '存储信息',
ADD COLUMN memory_info TEXT COMMENT '内存信息',
ADD COLUMN connection_info TEXT COMMENT '网络连接信息',
ADD COLUMN media_devices TEXT COMMENT '媒体设备信息',
ADD COLUMN permissions TEXT COMMENT '权限信息',
ADD COLUMN battery_info TEXT COMMENT '电池信息';

-- 添加高级特征字段
ALTER TABLE device_fingerprints 
ADD COLUMN gpu_info TEXT COMMENT 'GPU信息',
ADD COLUMN sensor_info TEXT COMMENT '传感器信息',
ADD COLUMN performance_info TEXT COMMENT '性能信息',
ADD COLUMN storage_detailed TEXT COMMENT '详细存储信息',
ADD COLUMN timezone_detailed TEXT COMMENT '详细时区信息',
ADD COLUMN encoding_info TEXT COMMENT '编码信息',
ADD COLUMN vendor_info TEXT COMMENT '厂商信息',
ADD COLUMN capabilities_info TEXT COMMENT '能力信息',
ADD COLUMN time_stability TEXT COMMENT '时间稳定性信息';

-- 添加索引以提高查询性能
CREATE INDEX idx_device_fingerprints_user_agent ON device_fingerprints(user_agent_normalized);
CREATE INDEX idx_device_fingerprints_platform ON device_fingerprints(platform);
CREATE INDEX idx_device_fingerprints_screen_resolution ON device_fingerprints(screen_resolution);
CREATE INDEX idx_device_fingerprints_timezone ON device_fingerprints(timezone);
CREATE INDEX idx_device_fingerprints_canvas ON device_fingerprints(canvas_fingerprint);
CREATE INDEX idx_device_fingerprints_webgl ON device_fingerprints(webgl_fingerprint);
CREATE INDEX idx_device_fingerprints_audio ON device_fingerprints(audio_fingerprint);

-- 更新现有记录的components字段，将JSON数据解析到各个字段
-- 注意：这个更新需要根据实际的components JSON结构来调整
UPDATE device_fingerprints 
SET 
    user_agent_normalized = JSON_UNQUOTE(JSON_EXTRACT(components, '$.userAgent')),
    language = JSON_UNQUOTE(JSON_EXTRACT(components, '$.language')),
    languages = JSON_UNQUOTE(JSON_EXTRACT(components, '$.languages')),
    platform = JSON_UNQUOTE(JSON_EXTRACT(components, '$.platform')),
    cookie_enabled = JSON_EXTRACT(components, '$.cookieEnabled'),
    do_not_track = JSON_UNQUOTE(JSON_EXTRACT(components, '$.doNotTrack')),
    hardware_concurrency = JSON_EXTRACT(components, '$.hardwareConcurrency'),
    max_touch_points = JSON_EXTRACT(components, '$.maxTouchPoints'),
    screen_avail_width = JSON_EXTRACT(components, '$.screenAvailWidth'),
    screen_avail_height = JSON_EXTRACT(components, '$.screenAvailHeight'),
    screen_orientation = JSON_UNQUOTE(JSON_EXTRACT(components, '$.screenOrientation')),
    color_gamut = JSON_UNQUOTE(JSON_EXTRACT(components, '$.colorGamut')),
    contrast = JSON_UNQUOTE(JSON_EXTRACT(components, '$.contrast')),
    forced_colors = JSON_UNQUOTE(JSON_EXTRACT(components, '$.forcedColors')),
    screen_resolution = JSON_UNQUOTE(JSON_EXTRACT(components, '$.screenResolution')),
    screen_color_depth = JSON_EXTRACT(components, '$.screenColorDepth'),
    screen_pixel_depth = JSON_EXTRACT(components, '$.screenPixelDepth'),
    device_pixel_ratio = JSON_EXTRACT(components, '$.devicePixelRatio'),
    timezone = JSON_UNQUOTE(JSON_EXTRACT(components, '$.timezone')),
    timezone_offset = JSON_EXTRACT(components, '$.timezoneOffset'),
    canvas_fingerprint = JSON_UNQUOTE(JSON_EXTRACT(components, '$.canvasFingerprint')),
    webgl_fingerprint = JSON_UNQUOTE(JSON_EXTRACT(components, '$.webglFingerprint')),
    audio_fingerprint = JSON_UNQUOTE(JSON_EXTRACT(components, '$.audioFingerprint')),
    fonts = JSON_UNQUOTE(JSON_EXTRACT(components, '$.fonts')),
    plugins = JSON_UNQUOTE(JSON_EXTRACT(components, '$.plugins')),
    storage_info = JSON_UNQUOTE(JSON_EXTRACT(components, '$.storage')),
    memory_info = JSON_UNQUOTE(JSON_EXTRACT(components, '$.memoryInfo')),
    connection_info = JSON_UNQUOTE(JSON_EXTRACT(components, '$.connectionInfo')),
    media_devices = JSON_UNQUOTE(JSON_EXTRACT(components, '$.mediaDevices')),
    permissions = JSON_UNQUOTE(JSON_EXTRACT(components, '$.permissions')),
    battery_info = JSON_UNQUOTE(JSON_EXTRACT(components, '$.batteryInfo')),
    gpu_info = JSON_UNQUOTE(JSON_EXTRACT(components, '$.gpuInfo')),
    sensor_info = JSON_UNQUOTE(JSON_EXTRACT(components, '$.sensorInfo')),
    performance_info = JSON_UNQUOTE(JSON_EXTRACT(components, '$.performanceInfo')),
    storage_detailed = JSON_UNQUOTE(JSON_EXTRACT(components, '$.storageInfo')),
    timezone_detailed = JSON_UNQUOTE(JSON_EXTRACT(components, '$.timezoneInfo')),
    encoding_info = JSON_UNQUOTE(JSON_EXTRACT(components, '$.encodingInfo')),
    vendor_info = JSON_UNQUOTE(JSON_EXTRACT(components, '$.vendorInfo')),
    capabilities_info = JSON_UNQUOTE(JSON_EXTRACT(components, '$.capabilitiesInfo')),
    time_stability = JSON_UNQUOTE(JSON_EXTRACT(components, '$.timeStability'))
WHERE components IS NOT NULL AND components != 'null';



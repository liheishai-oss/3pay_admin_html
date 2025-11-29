/**
 * 设备指纹识别库
 * 基于丁香园constid-js实现
 * 用于生成唯一的设备标识符
 */

class DeviceFingerprint {
    constructor(merchantKey = '') {
        this.fingerprint = null;
        this.components = {};
        this.merchantKey = merchantKey;
    }

    /**
     * 生成设备指纹key
     * @returns {Promise<string>} 设备指纹key字符串
     */
    async generate() {
        if (this.fingerprint) {
            return this.fingerprint;
        }

        try {
            // 收集各种设备特征
            const components = await this.collectComponents();
            
            // 调试：输出组件信息
            console.log('设备指纹组件:', components);
            
            // 生成指纹key
            this.fingerprint = this.hashComponents(components);
            this.components = components;
            
            console.log('生成的指纹key:', this.fingerprint);
            
            return this.fingerprint;
        } catch (error) {
            console.error('生成设备指纹key失败:', error);
            return this.generateFallbackFingerprint();
        }
    }

    /**
     * 获取设备码（需要鉴权）
     * @param {string} apiUrl - API接口地址
     * @param {string} authToken - 鉴权令牌
     * @returns {Promise<string>} 设备码
     */
    async getDeviceCode(apiUrl, authToken) {
        try {
            if (!authToken) {
                throw new Error('需要提供鉴权令牌');
            }
            
            const fingerprintKey = await this.generate();
            
            // 构建请求参数
            const params = new URLSearchParams({
                key: fingerprintKey,
                merchantKey: this.merchantKey
            });
            
            // 发送请求获取设备码
            const response = await fetch(`${apiUrl}?${params}`, {
                method: 'GET',
                headers: {
                    'Content-Type': 'application/json',
                    'Authorization': `Bearer ${authToken}`,
                    'X-Merchant-Key': this.merchantKey
                }
            });
            
            if (!response.ok) {
                if (response.status === 401) {
                    throw new Error('鉴权失败，请检查令牌');
                } else if (response.status === 403) {
                    throw new Error('权限不足，请检查商户key');
                }
                throw new Error(`HTTP error! status: ${response.status}`);
            }
            
            const data = await response.json();
            
            if (data.success) {
                return data.deviceCode;
            } else {
                throw new Error(data.message || '获取设备码失败');
            }
        } catch (error) {
            console.error('获取设备码失败:', error);
            throw error;
        }
    }

    /**
     * 获取设备码（POST方式，更安全）
     * @param {string} apiUrl - API接口地址
     * @param {string} authToken - 鉴权令牌
     * @returns {Promise<string>} 设备码
     */
    async getDeviceCodePost(apiUrl, authToken) {
        try {
            if (!authToken) {
                throw new Error('需要提供鉴权令牌');
            }
            
            const fingerprintKey = await this.generate();
            
            // 构建请求体
            const requestBody = {
                fingerprintKey: fingerprintKey,
                merchantKey: this.merchantKey,
                timestamp: Date.now()
            };
            
            // 发送POST请求获取设备码
            const response = await fetch(apiUrl, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'Authorization': `Bearer ${authToken}`,
                    'X-Merchant-Key': this.merchantKey
                },
                body: JSON.stringify(requestBody)
            });
            
            if (!response.ok) {
                if (response.status === 401) {
                    throw new Error('鉴权失败，请检查令牌');
                } else if (response.status === 403) {
                    throw new Error('权限不足，请检查商户key');
                }
                throw new Error(`HTTP error! status: ${response.status}`);
            }
            
            const data = await response.json();
            
            if (data.success) {
                return data.deviceCode;
            } else {
                throw new Error(data.message || '获取设备码失败');
            }
        } catch (error) {
            console.error('获取设备码失败:', error);
            throw error;
        }
    }

    /**
     * 标准化UserAgent，移除动态变化的部分
     */
    normalizeUserAgent(userAgent) {
        try {
            let normalizedUA = userAgent;
            
            // 移除支付宝客户端中的动态ChannelId
            normalizedUA = normalizedUA.replace(/ChannelId\(\d+\)/g, 'ChannelId(XX)');
            
            // 移除其他可能的动态标识符
            normalizedUA = normalizedUA.replace(/UWS\/[\d\.]+/g, 'UWS/X.X.X');
            normalizedUA = normalizedUA.replace(/UCBS\/[\d\._]+/g, 'UCBS/X.X.X');
            normalizedUA = normalizedUA.replace(/NebulaSDK\/[\d\.]+/g, 'NebulaSDK/X.X.X');
            normalizedUA = normalizedUA.replace(/AliApp\(AP\/[\d\.]+\)/g, 'AliApp(AP/X.X.X)');
            normalizedUA = normalizedUA.replace(/AlipayClient\/[\d\.]+/g, 'AlipayClient/X.X.X');
            
            // 移除时间戳相关的动态内容
            normalizedUA = normalizedUA.replace(/\d{13}/g, 'XXXXXXXXXXXXX'); // 13位时间戳
            normalizedUA = normalizedUA.replace(/\d{10}/g, 'XXXXXXXXXX'); // 10位时间戳
            
            console.log('原始UserAgent:', userAgent);
            console.log('标准化UserAgent:', normalizedUA);
            
            return normalizedUA;
        } catch (error) {
            console.log('UserAgent标准化失败:', error.message);
            return userAgent;
        }
    }

    /**
     * 快速稳定性检查
     */
    async quickStabilityCheck() {
        try {
            // 检查关键组件是否稳定，避免创建新的DeviceFingerprint实例
            const keyComponents = [
                'userAgent',
                'platform', 
                'screenResolution',
                'devicePixelRatio',
                'timezone',
                'language'
            ];
            
            // 直接检查这些组件的当前值
            for (const key of keyComponents) {
                if (!this.components[key]) {
                    return false;
                }
            }
            return true;
        } catch (error) {
            return false;
        }
    }

    /**
     * 收集设备特征组件
     */
    async collectComponents() {
        const components = {};

        // 基础浏览器信息
        if (typeof navigator !== 'undefined') {
            components.userAgent = this.normalizeUserAgent(navigator.userAgent);
            components.language = navigator.language;
            components.languages = navigator.languages ? navigator.languages.join(',') : '';
            components.platform = navigator.platform;
            components.cookieEnabled = navigator.cookieEnabled;
            components.doNotTrack = navigator.doNotTrack;
            components.hardwareConcurrency = navigator.hardwareConcurrency || 0;
            components.maxTouchPoints = navigator.maxTouchPoints || 0;
        } else {
            // Node.js环境下的模拟值
            components.userAgent = 'Node.js/' + process.version;
            components.language = 'en-US';
            components.languages = 'en-US';
            components.platform = process.platform;
            components.cookieEnabled = false;
            components.doNotTrack = null;
            components.hardwareConcurrency = require('os').cpus().length;
            components.maxTouchPoints = 0;
        }
        
        // 增加更多差异化特征
        if (typeof screen !== 'undefined') {
            components.screenAvailWidth = screen.availWidth || 0;
            components.screenAvailHeight = screen.availHeight || 0;
            components.screenOrientation = screen.orientation ? screen.orientation.type : 'unknown';
            components.colorGamut = screen.colorGamut || 'unknown';
            components.contrast = screen.contrast || 'unknown';
            components.forcedColors = screen.forcedColors || 'none';

            // 屏幕信息
            components.screenResolution = `${screen.width}x${screen.height}`;
            components.screenColorDepth = screen.colorDepth;
            components.screenPixelDepth = screen.pixelDepth;
        } else {
            // Node.js环境下的模拟值
            components.screenAvailWidth = 1920;
            components.screenAvailHeight = 1080;
            components.screenOrientation = 'landscape-primary';
            components.colorGamut = 'srgb';
            components.contrast = 'no-preference';
            components.forcedColors = 'none';
            components.screenResolution = '1920x1080';
            components.screenColorDepth = 24;
            components.screenPixelDepth = 24;
        }
        
        components.devicePixelRatio = (typeof window !== 'undefined' && window.devicePixelRatio) || 1;

        // 时区信息（固定值，不依赖当前时间）
        try {
            components.timezone = Intl.DateTimeFormat().resolvedOptions().timeZone;
            components.timezoneOffset = new Date().getTimezoneOffset();
        } catch (error) {
            // Node.js环境下的备用方案
            components.timezone = 'UTC';
            components.timezoneOffset = 0;
        }
        
        console.log('时区信息:', {
            timezone: components.timezone,
            timezoneOffset: components.timezoneOffset
        });

        // Canvas指纹
        components.canvasFingerprint = this.getCanvasFingerprint();

        // WebGL指纹
        components.webglFingerprint = this.getWebGLFingerprint();

        // 音频指纹
        components.audioFingerprint = await this.getAudioFingerprint();

        // 字体检测
        components.fonts = this.getInstalledFonts();

        // 插件信息
        components.plugins = this.getPluginInfo();

        // 存储信息
        components.storage = this.getStorageInfo();
        
        // 添加更多硬件特征（只包含稳定的特征，不受网络/系统负载影响）
        components.memoryInfo = this.getMemoryInfo();
        components.connectionInfo = this.getConnectionInfo();
        components.mediaDevices = this.getMediaDevicesInfo();
        components.permissions = this.getPermissionsInfo();
        components.batteryInfo = this.getBatteryInfo();
        
        // 添加高级特征
        components.gpuInfo = this.getGPUInfo();
        components.sensorInfo = this.getSensorInfo();
        components.performanceInfo = this.getPerformanceInfo();
        components.storageInfo = this.getStorageInfo();
        components.timezoneInfo = this.getTimezoneInfo();
        components.encodingInfo = this.getEncodingInfo();
        components.vendorInfo = this.getVendorInfo();
        components.capabilitiesInfo = this.getCapabilitiesInfo();
        
        // 添加时间稳定性特征（避免因时间变化影响指纹）
        components.timeStability = this.getTimeStability();
        
        // 代理检测已禁用（不需要）
        // components.proxyDetection = await this.detectProxy();
        
        // 添加设备状态检测
        components.deviceStatus = this.getDeviceStatus();

        return components;
    }

    /**
     * Canvas指纹识别 - 使用更稳定的方法
     */
    getCanvasFingerprint() {
        try {
            // 在Node.js环境中返回固定指纹
            if (typeof document === 'undefined') {
                return 'canvas_nodejs_simulated_fixed';
            }

            const canvas = document.createElement('canvas');
            const ctx = canvas.getContext('2d');
            
            if (!ctx) {
                return 'canvas_no_context';
            }
            
            // 使用更稳定的Canvas指纹方法
            // 不依赖具体的绘制内容，而是基于Canvas的渲染特性
            const fingerprint = this.generateStableCanvasFingerprint(canvas, ctx);
            console.log('Canvas指纹:', fingerprint);
            return fingerprint;
        } catch (error) {
            console.log('Canvas指纹错误:', error.message);
            return 'canvas_error_' + error.message.substring(0, 20);
        }
    }

    /**
     * 生成稳定的Canvas指纹
     */
    generateStableCanvasFingerprint(canvas, ctx) {
        try {
            // 设置固定尺寸
            canvas.width = 200;
            canvas.height = 50;
            
            // 基于Canvas的基本属性生成指纹，而不是绘制内容
            const canvasInfo = {
                width: canvas.width,
                height: canvas.height,
                // 测试基本绘制能力
                fillStyle: ctx.fillStyle,
                strokeStyle: ctx.strokeStyle,
                lineWidth: ctx.lineWidth,
                lineCap: ctx.lineCap,
                lineJoin: ctx.lineJoin,
                miterLimit: ctx.miterLimit,
                // 测试文本渲染
                font: ctx.font,
                textAlign: ctx.textAlign,
                textBaseline: ctx.textBaseline,
                // 测试变换
                globalAlpha: ctx.globalAlpha,
                globalCompositeOperation: ctx.globalCompositeOperation
            };
            
            // 基于这些属性生成稳定的指纹
            const fingerprintData = Object.keys(canvasInfo)
                .sort()
                .map(key => `${key}:${canvasInfo[key]}`)
                .join('|');
            
            return this.simpleHash(fingerprintData);
        } catch (error) {
            return 'canvas_stable_error';
        }
    }

    /**
     * WebGL指纹识别
     */
    getWebGLFingerprint() {
        try {
            // 在Node.js环境中返回模拟指纹
            if (typeof document === 'undefined') {
                return JSON.stringify({
                    vendor: 'Node.js Simulated',
                    renderer: 'Node.js WebGL Simulator',
                    version: '1.0.0',
                    shadingLanguageVersion: '1.0.0',
                    extensions: ['simulated_extension']
                });
            }

            const canvas = document.createElement('canvas');
            const gl = canvas.getContext('webgl') || canvas.getContext('experimental-webgl');
            
            if (!gl) {
                return 'webgl_not_supported';
            }

            const info = {
                vendor: gl.getParameter(gl.VENDOR) || 'unknown',
                renderer: gl.getParameter(gl.RENDERER) || 'unknown',
                version: gl.getParameter(gl.VERSION) || 'unknown',
                shadingLanguageVersion: gl.getParameter(gl.SHADING_LANGUAGE_VERSION) || 'unknown',
                extensions: gl.getSupportedExtensions() || []
            };

            return JSON.stringify(info);
        } catch (error) {
            return 'webgl_error_' + error.message.substring(0, 20);
        }
    }

    /**
     * 音频指纹识别 - 使用更稳定的方法
     */
    async getAudioFingerprint() {
        try {
            // 在Node.js环境中返回固定指纹
            if (typeof window === 'undefined' || (!window.AudioContext && !window.webkitAudioContext)) {
                return 'audio_nodejs_simulated_fixed';
            }

            // 使用更稳定的音频指纹方法
            const fingerprint = this.generateStableAudioFingerprint();
            console.log('音频指纹:', fingerprint);
            return fingerprint;
        } catch (error) {
            console.log('音频指纹错误:', error.message);
            return 'audio_error_' + error.message.substring(0, 20);
        }
    }

    /**
     * 生成稳定的音频指纹
     */
    generateStableAudioFingerprint() {
        try {
            // 基于浏览器音频能力的固定特征
            const audioFeatures = {
                hasAudioContext: !!(window.AudioContext || window.webkitAudioContext),
                hasWebAudio: !!(window.AudioContext || window.webkitAudioContext),
                // 检测音频相关API
                hasMediaDevices: !!navigator.mediaDevices,
                hasGetUserMedia: !!(navigator.mediaDevices && navigator.mediaDevices.getUserMedia),
                // 检测音频格式支持
                canPlayMP3: this.canPlayAudio('audio/mpeg'),
                canPlayWAV: this.canPlayAudio('audio/wav'),
                canPlayOGG: this.canPlayAudio('audio/ogg'),
                canPlayAAC: this.canPlayAudio('audio/aac')
            };
            
            // 基于这些固定特征生成指纹
            const fingerprintData = Object.keys(audioFeatures)
                .sort()
                .map(key => `${key}:${audioFeatures[key]}`)
                .join('|');
            
            return this.simpleHash(fingerprintData);
        } catch (error) {
            return 'audio_stable_error';
        }
    }

    /**
     * 检测音频格式支持
     */
    canPlayAudio(type) {
        try {
            const audio = document.createElement('audio');
            return audio.canPlayType(type) !== '';
        } catch (error) {
            return false;
        }
    }

    /**
     * 生成固定的音频指纹
     */
    generateFixedAudioFingerprint(audioContext) {
        try {
            // 基于音频上下文的固定属性生成指纹
            const sampleRate = audioContext.sampleRate || 44100;
            const maxChannelCount = audioContext.destination.maxChannelCount || 2;
            const state = audioContext.state || 'running';
            const numberOfInputs = audioContext.destination.numberOfInputs || 0;
            const numberOfOutputs = audioContext.destination.numberOfOutputs || 0;
            
            // 创建固定的指纹数据，确保每次都相同
            const fingerprintData = [
                sampleRate,
                maxChannelCount,
                state,
                numberOfInputs,
                numberOfOutputs
            ];
            
            const result = fingerprintData.join(',');
            console.log('音频指纹数据:', result);
            return result;
        } catch (error) {
            console.log('音频指纹错误:', error.message);
            return 'audio_fixed_error';
        }
    }

    /**
     * 检测已安装字体 - 使用更稳定的方法
     */
    getInstalledFonts() {
        try {
            // 在Node.js环境中返回默认字体
            if (typeof document === 'undefined') {
                return 'Arial,Helvetica,Times New Roman,Georgia,Courier New,Monaco,serif,sans-serif,monospace';
            }

            // 使用更稳定的字体检测方法
            const fontInfo = this.generateStableFontFingerprint();
            console.log('字体检测结果:', fontInfo);
            return fontInfo;
        } catch (error) {
            console.log('字体检测错误:', error.message);
            return 'fonts_error';
        }
    }

    /**
     * 生成稳定的字体指纹
     */
    generateStableFontFingerprint() {
        try {
            // 基于浏览器字体渲染能力的固定特征
            const fontFeatures = {
                // 检测基本字体支持
                hasArial: this.testFontSupport('Arial'),
                hasHelvetica: this.testFontSupport('Helvetica'),
                hasTimes: this.testFontSupport('Times New Roman'),
                hasGeorgia: this.testFontSupport('Georgia'),
                hasCourier: this.testFontSupport('Courier New'),
                hasVerdana: this.testFontSupport('Verdana'),
                hasTahoma: this.testFontSupport('Tahoma'),
                hasImpact: this.testFontSupport('Impact'),
                // 检测字体渲染能力
                supportsTextMetrics: this.supportsTextMetrics(),
                supportsFontLoading: this.supportsFontLoading(),
                // 检测字体变体支持
                supportsFontVariants: this.supportsFontVariants()
            };
            
            // 基于这些固定特征生成指纹
            const fingerprintData = Object.keys(fontFeatures)
                .sort()
                .map(key => `${key}:${fontFeatures[key]}`)
                .join('|');
            
            return this.simpleHash(fingerprintData);
        } catch (error) {
            return 'fonts_stable_error';
        }
    }

    /**
     * 测试字体支持
     */
    testFontSupport(fontName) {
        try {
            const canvas = document.createElement('canvas');
            const ctx = canvas.getContext('2d');
            
            // 设置基准字体
            ctx.font = '12px monospace';
            const baselineWidth = ctx.measureText('Test').width;
            
            // 测试目标字体
            ctx.font = `12px ${fontName}, monospace`;
            const testWidth = ctx.measureText('Test').width;
            
            // 如果宽度不同，说明字体被应用了
            return Math.abs(baselineWidth - testWidth) > 0.1;
        } catch (error) {
            return false;
        }
    }

    /**
     * 检测文本测量支持
     */
    supportsTextMetrics() {
        try {
            const canvas = document.createElement('canvas');
            const ctx = canvas.getContext('2d');
            return typeof ctx.measureText('test').actualBoundingBoxAscent === 'number';
        } catch (error) {
            return false;
        }
    }

    /**
     * 检测字体加载API支持
     */
    supportsFontLoading() {
        return typeof document.fonts !== 'undefined' && typeof document.fonts.load === 'function';
    }

    /**
     * 检测字体变体支持
     */
    supportsFontVariants() {
        try {
            const canvas = document.createElement('canvas');
            const ctx = canvas.getContext('2d');
            ctx.font = '12px Arial';
            return typeof ctx.fontKerning !== 'undefined';
        } catch (error) {
            return false;
        }
    }

    /**
     * 比较字体数据
     */
    compareFontData(data1, data2) {
        let different = 0;
        for (let i = 0; i < data1.length; i++) {
            if (data1[i] !== data2[i]) {
                different++;
            }
        }
        return different > 0;
    }

    /**
     * 获取插件信息
     */
    getPluginInfo() {
        try {
            const plugins = [];
            if (navigator.plugins && navigator.plugins.length) {
                for (let i = 0; i < navigator.plugins.length; i++) {
                    plugins.push(navigator.plugins[i].name);
                }
            }
            return plugins.join(',') || 'no_plugins';
        } catch (error) {
            return 'plugins_error';
        }
    }

    /**
     * 获取存储信息
     */
    getStorageInfo() {
        const storage = {};
        
        try {
            storage.localStorage = typeof localStorage !== 'undefined' ? 'available' : 'unavailable';
        } catch (e) {
            storage.localStorage = 'unavailable';
        }

        try {
            storage.sessionStorage = typeof sessionStorage !== 'undefined' ? 'available' : 'unavailable';
        } catch (e) {
            storage.sessionStorage = 'unavailable';
        }

        try {
            storage.indexedDB = typeof indexedDB !== 'undefined' ? 'available' : 'unavailable';
        } catch (e) {
            storage.indexedDB = 'unavailable';
        }

        return JSON.stringify(storage);
    }

    /**
     * 哈希组件生成指纹key
     */
    hashComponents(components) {
        const str = Object.keys(components)
            .sort()
            .map(key => `${key}:${components[key]}`)
            .join('|');
        
        // 如果传入了商户key，将其加入到哈希中
        const finalStr = this.merchantKey ? `${str}|merchantKey:${this.merchantKey}` : str;
        
        return this.md5Hash(finalStr);
    }

    /**
     * MD5哈希函数
     */
    md5Hash(str) {
        // 简单的MD5实现（生产环境建议使用crypto-js等库）
        return this.simpleMD5(str);
    }

    /**
     * 简单MD5实现
     */
    simpleMD5(str) {
        // 这里使用一个简化的哈希算法，生产环境建议使用真正的MD5
        let hash = 0;
        if (str.length === 0) return hash.toString(16);
        
        for (let i = 0; i < str.length; i++) {
            const char = str.charCodeAt(i);
            hash = ((hash << 5) - hash) + char;
            hash = hash & hash; // 转换为32位整数
        }
        
        // 转换为16进制并补齐到32位
        const hex = Math.abs(hash).toString(16);
        return hex.padStart(8, '0').repeat(4).substring(0, 32);
    }

    /**
     * 简单哈希函数（备用）
     */
    simpleHash(str) {
        let hash = 0;
        if (str.length === 0) return hash.toString();
        
        for (let i = 0; i < str.length; i++) {
            const char = str.charCodeAt(i);
            hash = ((hash << 5) - hash) + char;
            hash = hash & hash; // 转换为32位整数
        }
        
        return Math.abs(hash).toString(36);
    }

    /**
     * 生成备用指纹
     */
    generateFallbackFingerprint() {
        const fallback = {
            userAgent: navigator.userAgent || 'unknown',
            platform: navigator.platform || 'unknown',
            language: navigator.language || 'unknown',
            timestamp: Date.now()
        };
        
        return this.hashComponents(fallback);
    }

    /**
     * 获取指纹组件详情
     */
    getComponents() {
        return this.components;
    }

    /**
     * 获取内存信息 - 只保留稳定的特征
     */
    getMemoryInfo() {
        try {
            if (navigator.deviceMemory) {
                return {
                    // 只保留设备内存大小，这是硬件固定的
                    deviceMemory: navigator.deviceMemory,
                    // 移除 jsHeapSizeLimit，这个值可能因系统负载变化
                };
            }
            return 'not_supported';
        } catch (error) {
            return 'error';
        }
    }

    /**
     * 获取网络连接信息 - 只保留稳定的特征
     */
    getConnectionInfo() {
        try {
            if (navigator.connection) {
                // 只保留稳定的网络特征，移除动态变化的部分
                return {
                    // 保留网络类型支持检测，但不包含具体数值
                    hasConnectionAPI: true,
                    // 移除 effectiveType, downlink, rtt, saveData 等动态值
                };
            }
            return 'not_supported';
        } catch (error) {
            return 'error';
        }
    }

    /**
     * 获取媒体设备信息
     */
    getMediaDevicesInfo() {
        try {
            if (navigator.mediaDevices && navigator.mediaDevices.enumerateDevices) {
                return 'available';
            }
            return 'not_supported';
        } catch (error) {
            return 'error';
        }
    }

    /**
     * 获取权限信息
     */
    getPermissionsInfo() {
        try {
            if (navigator.permissions) {
                return 'available';
            }
            return 'not_supported';
        } catch (error) {
            return 'error';
        }
    }

    /**
     * 获取电池信息
     */
    getBatteryInfo() {
        try {
            if (navigator.getBattery) {
                return 'available';
            }
            return 'not_supported';
        } catch (error) {
            return 'error';
        }
    }

    /**
     * 获取GPU信息
     */
    getGPUInfo() {
        try {
            const canvas = document.createElement('canvas');
            const gl = canvas.getContext('webgl') || canvas.getContext('experimental-webgl');
            
            if (gl) {
                const debugInfo = gl.getExtension('WEBGL_debug_renderer_info');
                if (debugInfo) {
                    return {
                        vendor: gl.getParameter(debugInfo.UNMASKED_VENDOR_WEBGL),
                        renderer: gl.getParameter(debugInfo.UNMASKED_RENDERER_WEBGL),
                        version: gl.getParameter(gl.VERSION),
                        shadingLanguageVersion: gl.getParameter(gl.SHADING_LANGUAGE_VERSION)
                    };
                }
                return {
                    version: gl.getParameter(gl.VERSION),
                    shadingLanguageVersion: gl.getParameter(gl.SHADING_LANGUAGE_VERSION)
                };
            }
            return 'not_supported';
        } catch (error) {
            return 'error';
        }
    }

    /**
     * 获取传感器信息
     */
    getSensorInfo() {
        try {
            const sensors = {
                accelerometer: 'Accelerometer' in window,
                gyroscope: 'Gyroscope' in window,
                magnetometer: 'Magnetometer' in window,
                linearAccelerationSensor: 'LinearAccelerationSensor' in window,
                gravitySensor: 'GravitySensor' in window,
                orientationSensor: 'OrientationSensor' in window,
                ambientLightSensor: 'AmbientLightSensor' in window,
                proximitySensor: 'ProximitySensor' in window
            };
            return sensors;
        } catch (error) {
            return 'error';
        }
    }

    /**
     * 获取性能信息 - 只保留稳定的特征
     */
    getPerformanceInfo() {
        try {
            if (performance && performance.memory) {
                return {
                    // 只保留堆大小限制，这是硬件固定的
                    jsHeapSizeLimit: performance.memory.jsHeapSizeLimit,
                    // 移除动态变化的内存使用情况
                    timing: performance.timing ? 'available' : 'not_available'
                };
            }
            return {
                timing: performance && performance.timing ? 'available' : 'not_available'
            };
        } catch (error) {
            return 'error';
        }
    }

    /**
     * 获取存储信息
     */
    getStorageInfo() {
        try {
            const storage = {
                localStorage: typeof Storage !== 'undefined' ? 'available' : 'not_supported',
                sessionStorage: typeof Storage !== 'undefined' ? 'available' : 'not_supported',
                indexedDB: 'indexedDB' in window ? 'available' : 'not_supported',
                webSQL: 'openDatabase' in window ? 'available' : 'not_supported',
                cacheAPI: 'caches' in window ? 'available' : 'not_supported'
            };
            return storage;
        } catch (error) {
            return 'error';
        }
    }

    /**
     * 获取时区详细信息 - 只保留稳定的特征
     */
    getTimezoneInfo() {
        try {
            return {
                // 只保留时区名称，这是系统设置，不受网络影响
                timezone: Intl.DateTimeFormat().resolvedOptions().timeZone,
                // 移除时区偏移，因为它可能因夏令时变化
                // timezoneOffset: now.getTimezoneOffset(),
                // 移除可能因网络位置变化的区域设置
                // locale: Intl.DateTimeFormat().resolvedOptions().locale,
                // calendar: Intl.DateTimeFormat().resolvedOptions().calendar,
                // numberingSystem: Intl.DateTimeFormat().resolvedOptions().numberingSystem
            };
        } catch (error) {
            return 'error';
        }
    }

    /**
     * 获取编码信息
     */
    getEncodingInfo() {
        try {
            return {
                charset: document.characterSet || document.charset,
                inputEncoding: document.inputEncoding,
                defaultView: window ? 'available' : 'not_available',
                textContent: document.documentElement ? 'available' : 'not_available'
            };
        } catch (error) {
            return 'error';
        }
    }

    /**
     * 获取厂商信息
     */
    getVendorInfo() {
        try {
            return {
                vendor: navigator.vendor,
                vendorSub: navigator.vendorSub,
                product: navigator.product,
                productSub: navigator.productSub,
                appName: navigator.appName,
                appVersion: navigator.appVersion,
                appCodeName: navigator.appCodeName
            };
        } catch (error) {
            return 'error';
        }
    }

    /**
     * 获取浏览器能力信息
     */
    getCapabilitiesInfo() {
        try {
            return {
                geolocation: 'geolocation' in navigator,
                serviceWorker: 'serviceWorker' in navigator,
                pushManager: 'PushManager' in window,
                webRTC: 'RTCPeerConnection' in window,
                webGL: 'WebGLRenderingContext' in window,
                webGL2: 'WebGL2RenderingContext' in window,
                webAudio: 'AudioContext' in window || 'webkitAudioContext' in window,
                webSpeech: 'speechSynthesis' in window,
                webVR: 'getVRDisplays' in navigator,
                webXR: 'xr' in navigator,
                webNFC: 'NDEFReader' in window,
                webUSB: 'USB' in navigator,
                webSerial: 'Serial' in navigator,
                webHID: 'HID' in navigator,
                webBluetooth: 'bluetooth' in navigator,
                webShare: 'share' in navigator,
                webClipboard: 'clipboard' in navigator,
                webPayment: 'PaymentRequest' in window,
                webCredential: 'CredentialsContainer' in navigator,
                webLocks: 'locks' in navigator,
                webScheduling: 'scheduler' in window,
                webFileSystem: 'showOpenFilePicker' in window,
                webFileSystemAccess: 'showSaveFilePicker' in window
            };
        } catch (error) {
            return 'error';
        }
    }

    /**
     * 获取时间稳定性特征
     */
    getTimeStability() {
        try {
            return {
                // 只保留完全稳定的时间特征，避免因任何时间变化影响指纹
                timezone: Intl.DateTimeFormat().resolvedOptions().timeZone,
                // 移除时区偏移，因为它可能因夏令时变化
                // timezoneOffset: now.getTimezoneOffset(),
                // 移除所有可能变化的时间信息
                // 不包含日期、时间、小时等任何会变化的时间特征
            };
        } catch (error) {
            return 'error';
        }
    }

    /**
     * 验证指纹稳定性
     */
    async validateStability() {
        const fingerprint1 = await this.generate();
        // 等待一小段时间
        await new Promise(resolve => setTimeout(resolve, 100));
        const fingerprint2 = await this.generate();
        
        return fingerprint1 === fingerprint2;
    }

    /**
     * 检测代理状态
     */
    async detectProxy() {
        // 代理检测已禁用，避免外部网站访问问题
        return {
            isProxy: false,
            proxyType: 'disabled',
            confidence: 0,
            details: {
                reason: '代理检测功能已禁用，避免外部网站访问'
            }
        };
    }

    /**
     * 检测HTTP代理头
     */
    detectHttpProxyHeaders() {
        const headers = {
            'HTTP_VIA': 'Via',
            'HTTP_X_FORWARDED_FOR': 'X-Forwarded-For',
            'HTTP_X_FORWARDED': 'X-Forwarded',
            'HTTP_X_CLUSTER_CLIENT_IP': 'X-Cluster-Client-IP',
            'HTTP_FORWARDED_FOR': 'Forwarded-For',
            'HTTP_FORWARDED': 'Forwarded',
            'HTTP_CLIENT_IP': 'Client-IP',
            'HTTP_X_REAL_IP': 'X-Real-IP'
        };

        const detectedHeaders = {};
        let detected = false;

        // 在浏览器环境中，这些头信息通常不可直接访问
        // 但我们可以通过其他方式检测
        try {
            if (typeof window !== 'undefined') {
                // 检测是否在iframe中（可能的代理特征）
                if (window !== window.top) {
                    detectedHeaders.iframe = true;
                    detected = true;
                }

                // 检测referrer异常
                if (typeof document !== 'undefined' && document.referrer && document.referrer !== window.location.origin) {
                    detectedHeaders.referrer = document.referrer;
                    detected = true;
                }

                // 检测window.name异常（某些代理会设置）
                if (window.name && window.name.length > 0) {
                    detectedHeaders.windowName = window.name;
                    detected = true;
                }
            } else {
                // Node.js环境，无法检测这些特征
                detectedHeaders.nodejs = true;
            }

        } catch (error) {
            console.log('HTTP代理头检测异常:', error.message);
        }

        return {
            detected,
            headers: detectedHeaders
        };
    }

    /**
     * 检测WebRTC泄露
     */
    async detectWebRTCLeak() {
        try {
            if (typeof window === 'undefined' || !window.RTCPeerConnection) {
                return { detected: false, reason: 'WebRTC not supported or not in browser' };
            }

            const pc = new RTCPeerConnection({
                iceServers: [{ urls: 'stun:stun.l.google.com:19302' }]
            });

            return new Promise((resolve) => {
                const localIPs = new Set();
                const startTime = Date.now();

                pc.onicecandidate = (event) => {
                    if (event.candidate) {
                        const candidate = event.candidate.candidate;
                        const ipMatch = candidate.match(/([0-9]{1,3}(\.[0-9]{1,3}){3}|[a-f0-9]{1,4}(:[a-f0-9]{1,4}){7})/);
                        if (ipMatch) {
                            const ip = ipMatch[1];
                            // 排除本地IP
                            if (!ip.startsWith('192.168.') && 
                                !ip.startsWith('10.') && 
                                !ip.startsWith('172.') &&
                                ip !== '127.0.0.1' &&
                                ip !== '::1') {
                                localIPs.add(ip);
                            }
                        }
                    }
                };

                pc.createDataChannel('test');
                pc.createOffer().then(offer => pc.setLocalDescription(offer));

                // 5秒后检查结果
                setTimeout(() => {
                    pc.close();
                    const detected = localIPs.size > 0;
                    resolve({
                        detected,
                        localIPs: Array.from(localIPs),
                        reason: detected ? 'WebRTC泄露检测到真实IP' : '未检测到WebRTC泄露'
                    });
                }, 5000);
            });

        } catch (error) {
            return { detected: false, reason: 'WebRTC检测异常: ' + error.message };
        }
    }

    /**
     * 检测时区与IP不匹配
     */
    async detectTimezoneMismatch() {
        try {
            // 检查是否在浏览器环境中
            if (typeof window === 'undefined') {
                return { detected: false, reason: '需要在浏览器环境中检测时区' };
            }

            // 获取浏览器时区
            const browserTimezone = Intl.DateTimeFormat().resolvedOptions().timeZone;
            const browserOffset = new Date().getTimezoneOffset();

            // 尝试获取IP地理位置（这里使用免费API，实际项目中可能需要更可靠的方案）
            const ipInfo = await this.getIPInfo();
            
            if (ipInfo && ipInfo.timezone) {
                const timezoneMismatch = browserTimezone !== ipInfo.timezone;
                return {
                    detected: timezoneMismatch,
                    browserTimezone,
                    ipTimezone: ipInfo.timezone,
                    browserOffset,
                    reason: timezoneMismatch ? '时区与IP地理位置不匹配' : '时区与IP地理位置匹配'
                };
            }

            return { detected: false, reason: '无法获取IP信息' };

        } catch (error) {
            return { detected: false, reason: '时区检测异常: ' + error.message };
        }
    }

    /**
     * 获取IP信息
     */
    async getIPInfo() {
        try {
            const response = await fetch('https://ipapi.co/json/', {
                method: 'GET',
                timeout: 5000
            });
            
            if (response.ok) {
                return await response.json();
            }
            return null;
        } catch (error) {
            console.log('获取IP信息失败:', error.message);
            return null;
        }
    }

    /**
     * 检测DNS泄露
     */
    async detectDNSLeak() {
        try {
            // 检查是否在浏览器环境中
            if (typeof window === 'undefined' || typeof fetch === 'undefined') {
                return { detected: false, reason: '需要在浏览器环境中检测DNS' };
            }

            // 检测DNS解析异常
            const testDomains = [
                'google.com',
                'facebook.com',
                'amazon.com'
            ];

            const dnsResults = [];
            for (const domain of testDomains) {
                try {
                    const startTime = performance.now();
                    await fetch(`https://${domain}`, { 
                        method: 'HEAD',
                        mode: 'no-cors',
                        cache: 'no-cache'
                    });
                    const endTime = performance.now();
                    dnsResults.push({
                        domain,
                        responseTime: endTime - startTime
                    });
                } catch (error) {
                    dnsResults.push({
                        domain,
                        error: error.message
                    });
                }
            }

            // 分析DNS响应时间异常
            const avgResponseTime = dnsResults
                .filter(r => r.responseTime)
                .reduce((sum, r) => sum + r.responseTime, 0) / dnsResults.length;

            const suspiciousResponseTime = avgResponseTime > 2000; // 超过2秒认为可疑

            return {
                detected: suspiciousResponseTime,
                dnsResults,
                avgResponseTime,
                reason: suspiciousResponseTime ? 'DNS响应时间异常' : 'DNS响应正常'
            };

        } catch (error) {
            return { detected: false, reason: 'DNS检测异常: ' + error.message };
        }
    }

    /**
     * 检测浏览器指纹异常
     */
    detectFingerprintAnomaly() {
        const anomalies = [];

        try {
            // 检查是否在浏览器环境中
            if (typeof window === 'undefined' || typeof document === 'undefined' || typeof navigator === 'undefined') {
                return { detected: false, reason: '需要在浏览器环境中检测指纹异常' };
            }

            // 检测UserAgent异常
            const userAgent = navigator.userAgent;
            if (userAgent.includes('HeadlessChrome') || 
                userAgent.includes('PhantomJS') ||
                userAgent.includes('Selenium')) {
                anomalies.push('自动化工具特征');
            }

            // 检测WebGL异常
            const canvas = document.createElement('canvas');
            const gl = canvas.getContext('webgl');
            if (gl) {
                const debugInfo = gl.getExtension('WEBGL_debug_renderer_info');
                if (debugInfo) {
                    const renderer = gl.getParameter(debugInfo.UNMASKED_RENDERER_WEBGL);
                    if (renderer.includes('Software') || renderer.includes('Virtual')) {
                        anomalies.push('虚拟化环境特征');
                    }
                }
            }

            // 检测屏幕分辨率异常
            if (typeof screen !== 'undefined' && (screen.width === 0 || screen.height === 0)) {
                anomalies.push('屏幕分辨率异常');
            }

            // 检测插件数量异常
            if (navigator.plugins.length === 0) {
                anomalies.push('无插件（可能为无头浏览器）');
            }

            return {
                detected: anomalies.length > 0,
                anomalies,
                reason: anomalies.length > 0 ? '检测到指纹异常' : '指纹正常'
            };

        } catch (error) {
            return { detected: false, reason: '指纹检测异常: ' + error.message };
        }
    }

    /**
     * 检测VPN
     */
    async detectVPN() {
        try {
            // 检查是否在浏览器环境中
            if (typeof window === 'undefined' || typeof navigator === 'undefined') {
                return { detected: false, reason: '需要在浏览器环境中检测VPN' };
            }

            // 检测常见的VPN特征
            const vpnIndicators = [];

            // 检测时区与语言不匹配
            const timezone = Intl.DateTimeFormat().resolvedOptions().timeZone;
            const language = navigator.language;
            
            // 简单的时区语言匹配检测
            if (timezone.includes('America') && !language.startsWith('en')) {
                vpnIndicators.push('时区语言不匹配');
            }
            if (timezone.includes('Europe') && !language.startsWith('en') && !language.startsWith('de') && !language.startsWith('fr')) {
                vpnIndicators.push('时区语言不匹配');
            }

            // 检测屏幕分辨率异常（某些VPN客户端会修改）
            if (typeof screen !== 'undefined' && (screen.width < 1024 || screen.height < 768)) {
                vpnIndicators.push('屏幕分辨率异常');
            }

            // 检测硬件并发数异常
            if (navigator.hardwareConcurrency < 2) {
                vpnIndicators.push('硬件并发数异常');
            }

            return {
                detected: vpnIndicators.length > 0,
                indicators: vpnIndicators,
                reason: vpnIndicators.length > 0 ? '检测到VPN特征' : '未检测到VPN特征'
            };

        } catch (error) {
            return { detected: false, reason: 'VPN检测异常: ' + error.message };
        }
    }

    /**
     * 确定代理类型
     */
    determineProxyType(details) {
        const types = [];
        
        if (details.httpHeaders && details.httpHeaders.detected) types.push('HTTP代理');
        if (details.webrtcLeak && details.webrtcLeak.detected) types.push('WebRTC泄露');
        if (details.timezoneMismatch && details.timezoneMismatch.detected) types.push('时区不匹配');
        if (details.dnsLeak && details.dnsLeak.detected) types.push('DNS泄露');
        if (details.fingerprintAnomaly && details.fingerprintAnomaly.detected) types.push('指纹异常');
        if (details.vpnDetection && details.vpnDetection.detected) types.push('VPN');

        return types.length > 0 ? types.join(', ') : '未知代理';
    }

    /**
     * 获取设备状态
     */
    getDeviceStatus() {
        const status = {
            isOnline: true,
            connectionType: 'unknown',
            batteryLevel: 'unknown',
            memoryUsage: 'unknown',
            cpuCores: 0,
            devicePixelRatio: 1,
            screenOrientation: 'unknown',
            isSecureContext: false,
            hasTouch: false,
            hasPointer: false
        };

        try {
            // 检查是否在浏览器环境中
            if (typeof window !== 'undefined' && typeof navigator !== 'undefined') {
                status.isOnline = navigator.onLine;
                status.cpuCores = navigator.hardwareConcurrency || 0;
                status.devicePixelRatio = window.devicePixelRatio || 1;
                status.isSecureContext = window.isSecureContext;
                status.hasTouch = 'ontouchstart' in window;
                status.hasPointer = 'onpointerdown' in window;

                // 检测网络连接类型
                if (navigator.connection) {
                    status.connectionType = navigator.connection.effectiveType || 'unknown';
                }

                // 检测电池状态
                if (navigator.getBattery) {
                    navigator.getBattery().then(battery => {
                        status.batteryLevel = Math.round(battery.level * 100);
                    });
                }

                // 检测屏幕方向
                if (typeof screen !== 'undefined' && screen.orientation) {
                    status.screenOrientation = screen.orientation.type;
                }

                // 检测内存使用
                if (typeof performance !== 'undefined' && performance.memory) {
                    status.memoryUsage = {
                        used: Math.round(performance.memory.usedJSHeapSize / 1024 / 1024),
                        total: Math.round(performance.memory.totalJSHeapSize / 1024 / 1024),
                        limit: Math.round(performance.memory.jsHeapSizeLimit / 1024 / 1024)
                    };
                }
            } else {
                // Node.js环境下的模拟值
                status.cpuCores = require('os').cpus().length;
                status.memoryUsage = {
                    used: Math.round(process.memoryUsage().heapUsed / 1024 / 1024),
                    total: Math.round(process.memoryUsage().heapTotal / 1024 / 1024),
                    limit: Math.round(process.memoryUsage().heapTotal / 1024 / 1024)
                };
            }

        } catch (error) {
            console.log('设备状态检测异常:', error.message);
        }

        return status;
    }

    /**
     * 上报设备信息到服务器（设备码由后台生成）
     * @param {string} reportUrl - 上报接口地址
     * @param {string} merchantKey - 商户密钥
     * @param {string} secretKey - 加密密钥（可选）
     * @param {Object} additionalData - 额外数据
     * @returns {Promise<Object>} 服务器返回的指纹信息
     */
    async reportDeviceInfo(reportUrl, merchantKey, secretKey = null, additionalData = {}) {
        try {
            if (!merchantKey) {
                throw new Error('商户密钥不能为空');
            }

            // 生成设备指纹
            const fingerprint = await this.generate();
            
            // 代理检测已禁用（不需要，节省时间）
            // const proxyDetection = await this.detectProxy();
            
            // 获取设备状态
            const deviceStatus = this.getDeviceStatus();

            // 构建原始数据（符合PHP API格式）
            const rawData = {
                merchantKey: merchantKey,
                fingerprint_key: fingerprint,
                components: this.components,
                // proxy_detection: proxyDetection,  // 已禁用
                device_status: deviceStatus,
                timestamp: Math.floor(Date.now() / 1000), // Unix时间戳
                user_agent: typeof navigator !== 'undefined' ? navigator.userAgent : 'Node.js/' + process.version,
                url: typeof window !== 'undefined' ? window.location.href : 'nodejs://localhost',
                referrer: typeof document !== 'undefined' ? document.referrer : '',
                ...additionalData
            };

            // 生成签名
            const signature = this.generateSignature(rawData, secretKey || merchantKey);
            
            // 构建安全的上报数据
            const reportData = {
                ...rawData,
                signature: signature,
                nonce: this.generateNonce()
            };

            console.log('准备安全上报设备信息:', {
                merchantKey: reportData.merchantKey,
                fingerprint_key: reportData.fingerprint_key.substring(0, 16) + '...',
                // proxy_detected: reportData.proxy_detection?.isProxy,  // 代理检测已禁用
                timestamp: reportData.timestamp,
                signature: reportData.signature.substring(0, 16) + '...',
                nonce: reportData.nonce
            });

            // 发送安全上报请求
            const response = await fetch(reportUrl, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-Merchant-Key': merchantKey,
                    'X-Signature': signature,
                    'X-Timestamp': reportData.timestamp.toString(),
                    'X-Nonce': reportData.nonce
                },
                body: JSON.stringify(reportData)
            });

            if (!response.ok) {
                const errorText = await response.text();
                throw new Error(`HTTP error! status: ${response.status}, message: ${errorText}`);
            }

            const result = await response.json();
            
            if (result.success === true || result.code === 200) {
                console.log('设备信息安全上报成功');
                return {
                    success: true,
                    fingerprint: result.fingerprint_key || result.data?.fingerprint_key || fingerprint,
                    is_new: result.is_new || result.data?.is_new || false,
                    message: result.message || '上报成功'
                };
            } else {
                throw new Error(result.message || '上报失败');
            }

        } catch (error) {
            console.error('设备信息安全上报失败:', error);
            throw error;
        }
    }

    /**
     * 生成数据签名
     * @param {Object} data - 要签名的数据
     * @param {string} secretKey - 密钥
     * @returns {string} 签名
     */
    generateSignature(data, secretKey) {
        try {
            // 创建签名字符串
            const sortedKeys = Object.keys(data).sort();
            const signString = sortedKeys
                .map(key => `${key}=${data[key]}`)
                .join('&') + `&key=${secretKey}`;
            
            // 使用简单哈希算法（生产环境建议使用HMAC-SHA256）
            return this.simpleHash(signString);
        } catch (error) {
            console.error('生成签名失败:', error);
            return '';
        }
    }

    /**
     * 生成随机数
     * @returns {string} 随机数
     */
    generateNonce() {
        return Math.random().toString(36).substring(2) + Date.now().toString(36);
    }

    /**
     * 使用指纹验证设备
     * @param {string} verifyUrl - 验证接口地址
     * @param {string} fingerprint - 要验证的指纹
     * @returns {Promise<Object>} 验证结果
     */
    async verifyFingerprint(verifyUrl, fingerprint) {
        try {
            if (!fingerprint) {
                throw new Error('指纹不能为空');
            }

            const response = await fetch(verifyUrl, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json'
                },
                body: JSON.stringify({ fingerprint })
            });

            if (!response.ok) {
                throw new Error(`HTTP error! status: ${response.status}`);
            }

            const result = await response.json();
            
            return {
                success: result.success || false,
                isValid: result.isValid || false,
                deviceCode: result.deviceCode || null,
                message: result.message || '验证完成'
            };

        } catch (error) {
            console.error('指纹验证失败:', error);
            throw error;
        }
    }
}

// 导出类
if (typeof module !== 'undefined' && module.exports) {
    module.exports = DeviceFingerprint;
} else if (typeof window !== 'undefined') {
    window.DeviceFingerprint = DeviceFingerprint;
}

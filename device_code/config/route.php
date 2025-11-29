<?php
/**
 * This file is part of webman.
 *
 * Licensed under The MIT License
 * For full copyright and license information, please see the MIT-LICENSE.txt
 * Redistributions of files must retain the above copyright notice.
 *
 * @author    walkor<walkor@workerman.net>
 * @copyright walkor<walkor@workerman.net>
 * @link      http://www.workerman.net/
 * @license   http://www.opensource.org/licenses/mit-license.php MIT License
 */

use Webman\Route;

// 首页
Route::get('/', [app\controller\IndexController::class, 'index']);

// 认证相关 - 登录接口不需要认证
Route::post('/auth/login', [app\controller\AuthController::class, 'login']);
Route::options('/auth/login', function() { return response('', 204); });

// 认证相关 - 需要认证的接口
Route::get('/auth/me', [app\controller\AuthController::class, 'me'])->middleware([app\middleware\AuthMiddleware::class]);
Route::options('/auth/me', function() { return response('', 204); });
Route::post('/auth/logout', [app\controller\AuthController::class, 'logout'])->middleware([app\middleware\AuthMiddleware::class]);
Route::options('/auth/logout', function() { return response('', 204); });

// 设备码相关（无需认证）
Route::post('/device-code/report', [app\controller\DeviceCodeController::class, 'reportDeviceCode']);
Route::options('/device-code/report', function() { return response('', 204); });
Route::post('/device-code/verify', [app\controller\DeviceCodeController::class, 'verifyFingerprint']);
Route::options('/device-code/verify', function() { return response('', 204); });

// 需要认证的管理接口
// 仪表板统计
Route::get('/admin/dashboard/stats', [app\controller\DashboardController::class, 'getStats'])->middleware([app\middleware\AuthMiddleware::class]);
Route::options('/admin/dashboard/stats', function() { return response('', 204); });
Route::get('/admin/dashboard/device-type-distribution', [app\controller\DashboardController::class, 'getDeviceTypeDistribution'])->middleware([app\middleware\AuthMiddleware::class]);
Route::options('/admin/dashboard/device-type-distribution', function() { return response('', 204); });
Route::get('/admin/dashboard/access-trend', [app\controller\DashboardController::class, 'getAccessTrend'])->middleware([app\middleware\AuthMiddleware::class]);
Route::options('/admin/dashboard/access-trend', function() { return response('', 204); });
Route::get('/admin/dashboard/recent-activity', [app\controller\DashboardController::class, 'getRecentActivity'])->middleware([app\middleware\AuthMiddleware::class]);
Route::options('/admin/dashboard/recent-activity', function() { return response('', 204); });
Route::get('/admin/dashboard/browser-distribution', [app\controller\DashboardController::class, 'getBrowserDistribution'])->middleware([app\middleware\AuthMiddleware::class]);
Route::options('/admin/dashboard/browser-distribution', function() { return response('', 204); });

// 管理员管理
Route::get('/admin/admins', [app\controller\AdminController::class, 'index'])->middleware([app\middleware\AuthMiddleware::class]);
Route::post('/admin/admins', [app\controller\AdminController::class, 'store'])->middleware([app\middleware\AuthMiddleware::class]);
Route::put('/admin/admins', [app\controller\AdminController::class, 'update'])->middleware([app\middleware\AuthMiddleware::class]);
Route::delete('/admin/admins', [app\controller\AdminController::class, 'delete'])->middleware([app\middleware\AuthMiddleware::class]);
Route::options('/admin/admins', function() { return response('', 204); });

// 商户管理
Route::get('/admin/merchants', [app\controller\MerchantController::class, 'index'])->middleware([app\middleware\AuthMiddleware::class]);
Route::post('/admin/merchants', [app\controller\MerchantController::class, 'store'])->middleware([app\middleware\AuthMiddleware::class]);
Route::put('/admin/merchants', [app\controller\MerchantController::class, 'update'])->middleware([app\middleware\AuthMiddleware::class]);
Route::delete('/admin/merchants', [app\controller\MerchantController::class, 'delete'])->middleware([app\middleware\AuthMiddleware::class]);
Route::options('/admin/merchants', function() { return response('', 204); });

// 设备管理
Route::get('/admin/devices', [app\controller\DeviceController::class, 'index'])->middleware([app\middleware\AuthMiddleware::class]);
Route::delete('/admin/devices', [app\controller\DeviceController::class, 'delete'])->middleware([app\middleware\AuthMiddleware::class]);
Route::options('/admin/devices', function() { return response('', 204); });
Route::get('/admin/devices/show', [app\controller\DeviceController::class, 'show'])->middleware([app\middleware\AuthMiddleware::class]);
Route::options('/admin/devices/show', function() { return response('', 204); });
Route::post('/admin/devices/batch', [app\controller\DeviceController::class, 'batchAction'])->middleware([app\middleware\AuthMiddleware::class]);
Route::options('/admin/devices/batch', function() { return response('', 204); });

// 指纹库管理
Route::get('/admin/fingerprints', [app\controller\FingerprintController::class, 'index'])->middleware([app\middleware\AuthMiddleware::class]);
Route::delete('/admin/fingerprints', [app\controller\FingerprintController::class, 'delete'])->middleware([app\middleware\AuthMiddleware::class]);
Route::options('/admin/fingerprints', function() { return response('', 204); });
Route::get('/admin/fingerprints/show', [app\controller\FingerprintController::class, 'show'])->middleware([app\middleware\AuthMiddleware::class]);
Route::options('/admin/fingerprints/show', function() { return response('', 204); });
Route::get('/admin/fingerprints/similarity', [app\controller\FingerprintController::class, 'analyzeSimilarity'])->middleware([app\middleware\AuthMiddleware::class]);
Route::options('/admin/fingerprints/similarity', function() { return response('', 204); });
Route::get('/admin/fingerprints/statistics', [app\controller\FingerprintController::class, 'getStatistics'])->middleware([app\middleware\AuthMiddleware::class]);
Route::options('/admin/fingerprints/statistics', function() { return response('', 204); });
Route::put('/admin/fingerprints/status', [app\controller\FingerprintController::class, 'updateStatus'])->middleware([app\middleware\AuthMiddleware::class]);
Route::options('/admin/fingerprints/status', function() { return response('', 204); });



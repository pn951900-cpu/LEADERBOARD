<?php

use App\Http\Controllers\Api\LeaderboardController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| API Routes - Leaderboard System
|--------------------------------------------------------------------------
*/

Route::prefix('v1')->middleware(['throttle:60,1'])->group(function () {
    // API Bảng xếp hạng chính
    Route::get('/leaderboard', [LeaderboardController::class, 'index']);

    // API danh sách các mùa giải / kỳ
    Route::get('/seasons', [LeaderboardController::class, 'seasons']);

    // API cập nhật / ghi nhận điểm (chỉ hợp lệ trong thời gian kỳ diễn ra)
    Route::post('/scores/update', [LeaderboardController::class, 'updateScore']);

    // API lấy danh sách demo users (tiện cho reviewer test đổi tài khoản trên UI)
    Route::get('/demo-users', [LeaderboardController::class, 'demoUsers']);
});


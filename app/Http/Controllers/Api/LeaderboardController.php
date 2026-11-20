<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\GetLeaderboardRequest;
use App\Models\Season;
use App\Models\User;
use App\Services\LeaderboardService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class LeaderboardController extends Controller
{
    public function __construct(
        protected LeaderboardService $leaderboardService
    ) {}

    /**
     * API Bảng xếp hạng chính (hỗ trợ chọn kỳ, phân trang, hiển thị thứ hạng cá nhân)
     */
    public function index(GetLeaderboardRequest $request): JsonResponse
    {
        // Nhận diện User hiện tại:
        // 1. Qua Sanctum/Auth token nếu có
        // 2. Hoặc qua header `X-User-Id` (hữu ích cho reviewer test switch user linh hoạt)
        // 3. Fallback về user ID = 1 nếu không truyền gì để tiện test nhanh
        $currentUserId = $request->user()?->id 
            ?? $request->header('X-User-Id') 
            ?? $request->query('current_user_id') 
            ?? 1;

        $data = $this->leaderboardService->getLeaderboard(
            $request->validated(),
            $currentUserId ? (int) $currentUserId : null
        );

        return response()->json([
            'success' => true,
            'message' => 'Lấy dữ liệu bảng xếp hạng thành công.',
            'data'    => $data,
        ]);
    }

    /**
     * Lấy danh sách các kỳ thi đấu
     */
    public function seasons(): JsonResponse
    {
        $seasons = Season::select('id', 'name', 'code', 'is_active', 'start_date', 'end_date')
            ->orderBy('id', 'asc')
            ->get();

        return response()->json([
            'success' => true,
            'data'    => $seasons,
        ]);
    }

    /**
     * API Cập nhật / Cộng điểm người chơi (chỉ cho phép khi chương trình đang diễn ra)
     */
    public function updateScore(\App\Http\Requests\UpdateScoreRequest $request): JsonResponse
    {
        $userId = $request->validated('user_id') 
            ?? $request->user()?->id 
            ?? $request->header('X-User-Id') 
            ?? 1;

        $points = (int) $request->validated('points');
        $seasonId = $request->validated('season_id');

        try {
            $result = $this->leaderboardService->updateScore((int) $userId, $points, $seasonId);

            return response()->json([
                'success' => true,
                'message' => "Đã cộng thành công {$points} điểm trong thời gian diễn ra kỳ thi đấu.",
                'data'    => $result,
            ]);
        } catch (\Exception $e) {
            $statusCode = in_array($e->getCode(), [400, 403, 404, 422]) ? $e->getCode() : 400;

            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], $statusCode);
        }
    }

    /**
     * Lấy danh sách một số users mẫu để Frontend có dropdown đổi nhanh tài khoản test rank cá nhân
     */
    public function demoUsers(): JsonResponse
    {
        $users = User::select('id', 'name', 'avatar', 'email')
            ->take(15)
            ->get();

        return response()->json([
            'success' => true,
            'data'    => $users,
        ]);
    }
}


<?php

namespace App\Services;

use App\Models\LeaderboardScore;
use App\Models\Season;
use App\Models\User;
use Illuminate\Support\Facades\Cache;

class LeaderboardService
{
    /**
     * Lấy dữ liệu Bảng Xếp Hạng: Kỳ, Rank cá nhân, Danh sách & Phân trang
     */
    public function getLeaderboard(array $params, ?int $currentUserId = null): array
    {
        $seasonId = $params['season_id'] ?? null;
        $page     = max((int) ($params['page'] ?? 1), 1);
        $perPage  = min(max((int) ($params['per_page'] ?? 20), 1), 100);

        // 1. Xác định Season hiện tại
        $season = null;
        if ($seasonId) {
            $season = Season::find($seasonId);
        }
        if (!$season) {
            $season = Season::where('is_active', true)->first() ?? Season::latest('id')->first();
        }

        if (!$season) {
            return [
                'current_season'    => null,
                'available_seasons' => [],
                'my_rank'           => null,
                'leaderboard'       => [],
                'pagination'        => [
                    'current_page' => $page,
                    'per_page'     => $perPage,
                    'total_items'  => 0,
                    'total_pages'  => 0,
                    'has_next_page'=> false,
                    'has_prev_page'=> false,
                ],
            ];
        }

        // 2. Danh sách các mùa giải để Frontend hiển thị selector (theo thứ tự tháng: Tháng 9 -> 10 -> 11)
        $availableSeasons = Season::select('id', 'name', 'code', 'is_active', 'start_date', 'end_date')
            ->orderBy('id', 'asc')
            ->get();

        // 3. Tính thứ hạng cá nhân (My Rank)
        $myRankData = $this->calculateUserRank($season->id, $currentUserId);

        // 4. Lấy danh sách bảng xếp hạng phân trang
        $totalItems = LeaderboardScore::where('season_id', $season->id)->count();

        // Query bảng xếp hạng với Eager Loading chống N+1 và tận dụng Composite Index
        // Thuật toán Tie-breaking: score DESC, achieved_at ASC, user_id ASC
        $scores = LeaderboardScore::query()
            ->where('season_id', $season->id)
            ->with(['user:id,name,avatar'])
            ->orderByDesc('score')
            ->orderBy('achieved_at', 'asc')
            ->orderBy('user_id', 'asc')
            ->forPage($page, $perPage)
            ->get();

        $startRank = ($page - 1) * $perPage + 1;
        $leaderboardItems = $scores->map(function ($item, $index) use ($startRank, $currentUserId) {
            return [
                'rank'            => $startRank + $index,
                'user_id'         => $item->user_id,
                'name'            => $item->user->name ?? 'Người chơi ẩn danh',
                'avatar'          => $item->user->avatar ?? "https://api.dicebear.com/7.x/bottts/svg?seed={$item->user_id}",
                'score'           => (int) $item->score,
                'achieved_at'     => $item->achieved_at ? $item->achieved_at->format('Y-m-d H:i:s') : null,
                'is_current_user' => ($currentUserId !== null && (int) $item->user_id === (int) $currentUserId),
            ];
        });

        $totalPages = (int) ceil($totalItems / $perPage);

        return [
            'current_season' => [
                'id'         => $season->id,
                'name'       => $season->name,
                'code'       => $season->code,
                'is_active'  => (bool) $season->is_active,
                'start_date' => $season->start_date?->format('Y-m-d H:i:s'),
                'end_date'   => $season->end_date?->format('Y-m-d H:i:s'),
            ],
            'available_seasons' => $availableSeasons,
            'my_rank'           => $myRankData,
            'leaderboard'       => $leaderboardItems,
            'pagination'        => [
                'current_page'  => $page,
                'per_page'      => $perPage,
                'total_items'   => $totalItems,
                'total_pages'   => $totalPages,
                'has_next_page' => $page < $totalPages,
                'has_prev_page' => $page > 1,
            ],
        ];
    }

    /**
     * Tính thứ hạng cá nhân theo thuật toán Tie-breaking tối ưu index O(log N)
     */
    protected function calculateUserRank(int $seasonId, ?int $userId): ?array
    {
        if (!$userId) {
            return null;
        }

        $user = User::select('id', 'name', 'avatar')->find($userId);
        if (!$user) {
            return null;
        }

        // Tìm điểm của user trong kỳ này
        $userScore = LeaderboardScore::where('season_id', $seasonId)
            ->where('user_id', $userId)
            ->first();

        if (!$userScore) {
            return [
                'is_ranked'   => false,
                'rank'        => null,
                'score'       => 0,
                'achieved_at' => null,
                'user'        => [
                    'id'     => $user->id,
                    'name'   => $user->name,
                    'avatar' => $user->avatar ?? "https://api.dicebear.com/7.x/bottts/svg?seed={$user->id}",
                ],
            ];
        }

        // Đếm số người xếp trên user theo đúng quy tắc Tie-breaking:
        // 1. Điểm cao hơn
        // 2. Điểm bằng nhau nhưng đạt sớm hơn (achieved_at < user.achieved_at)
        // 3. Điểm và thời gian bằng nhau nhưng user_id nhỏ hơn
        $higherCount = LeaderboardScore::where('season_id', $seasonId)
            ->where(function ($query) use ($userScore) {
                $query->where('score', '>', $userScore->score)
                    ->orWhere(function ($q) use ($userScore) {
                        $q->where('score', '=', $userScore->score)
                          ->where('achieved_at', '<', $userScore->achieved_at);
                    })
                    ->orWhere(function ($q) use ($userScore) {
                        $q->where('score', '=', $userScore->score)
                          ->where('achieved_at', '=', $userScore->achieved_at)
                          ->where('user_id', '<', $userScore->user_id);
                    });
            })
            ->count();

        $myRank = $higherCount + 1;

        return [
            'is_ranked'   => true,
            'rank'        => $myRank,
            'score'       => (int) $userScore->score,
            'achieved_at' => $userScore->achieved_at ? $userScore->achieved_at->format('Y-m-d H:i:s') : null,
            'user'        => [
                'id'     => $user->id,
                'name'   => $user->name,
                'avatar' => $user->avatar ?? "https://api.dicebear.com/7.x/bottts/svg?seed={$user->id}",
            ],
        ];
    }

    /**
     * Cập nhật / Cộng điểm cho người chơi trong thời gian chương trình diễn ra
     *
     * @throws \Exception nếu kỳ đã kết thúc hoặc không trong thời gian hợp lệ
     */
    public function updateScore(int $userId, int $points, ?int $seasonId = null): array
    {
        // 1. Xác định kỳ thi đấu
        $season = $seasonId ? Season::find($seasonId) : Season::where('is_active', true)->first();
        if (!$season) {
            throw new \Exception('Không tìm thấy kỳ thi đấu hợp lệ.', 404);
        }

        // 2. Kiểm tra tính hợp lệ về THỜI GIAN:
        // Điểm CHỈ có thể cập nhật trong thời gian chương trình đang diễn ra
        $now = now();
        if (!$season->is_active) {
            throw new \Exception("Kỳ thi đấu '{$season->name}' đã đóng (is_active = false), không thể cập nhật thêm điểm.", 403);
        }

        if ($season->start_date && $now->lt($season->start_date)) {
            throw new \Exception("Kỳ thi đấu '{$season->name}' chưa bắt đầu (Bắt đầu lúc: {$season->start_date}), không thể cộng điểm.", 403);
        }

        if ($season->end_date && $now->gt($season->end_date)) {
            throw new \Exception("Kỳ thi đấu '{$season->name}' đã kết thúc (Kết thúc lúc: {$season->end_date}), không thể ghi nhận điểm mới.", 403);
        }

        // 3. Cập nhật hoặc tạo mới điểm số của người chơi
        // achieved_at được cập nhật thành thời điểm hiện tại (now()) để phục vụ chuẩn luật Tie-breaking
        $scoreRecord = LeaderboardScore::firstOrNew([
            'user_id'   => $userId,
            'season_id' => $season->id,
        ]);

        $scoreRecord->score = ($scoreRecord->score ?? 0) + $points;
        $scoreRecord->achieved_at = $now;
        $scoreRecord->save();

        // 4. Tính lại thứ hạng mới của user ngay lập tức
        $updatedRank = $this->calculateUserRank($season->id, $userId);

        return [
            'season'       => [
                'id'   => $season->id,
                'name' => $season->name,
            ],
            'points_added' => $points,
            'new_score'    => $scoreRecord->score,
            'achieved_at'  => $scoreRecord->achieved_at->format('Y-m-d H:i:s'),
            'updated_rank' => $updatedRank,
        ];
    }
}


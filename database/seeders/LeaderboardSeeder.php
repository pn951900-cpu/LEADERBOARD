<?php

namespace Database\Seeders;

use App\Models\Season;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;

class LeaderboardSeeder extends Seeder
{
    public function run(): void
    {
        // 1. Tạo 3 Mùa Giải / Tháng
        $season1 = Season::updateOrCreate(
            ['code' => 'season_2026_09'],
            [
                'name' => 'Tháng 09/2026',
                'start_date' => Carbon::parse('2026-09-01 00:00:00'),
                'end_date' => Carbon::parse('2026-09-30 23:59:59'),
                'is_active' => false,
            ]
        );

        $season2 = Season::updateOrCreate(
            ['code' => 'season_2026_10'],
            [
                'name' => 'Tháng 10/2026',
                'start_date' => Carbon::parse('2026-10-01 00:00:00'),
                'end_date' => Carbon::parse('2026-10-31 23:59:59'),
                'is_active' => false,
            ]
        );

        $season3 = Season::updateOrCreate(
            ['code' => 'season_2026_11'],
            [
                'name' => 'Tháng 11/2026',
                'start_date' => Carbon::parse('2026-11-01 00:00:00'),
                'end_date' => Carbon::parse('2026-11-30 23:59:59'),
                'is_active' => true, // Tháng hiện tại
            ]
        );

        // 2. Tạo User bạn (Current User - ID = 1)
        $currentUser = User::updateOrCreate(
            ['email' => 'myaccount@example.com'],
            [
                'name' => 'Nguyễn Văn Đạt (Bạn)',
                'avatar' => 'https://api.dicebear.com/7.x/avataaars/svg?seed=dat',
                'password' => Hash::make('password123'),
            ]
        );

        // 3. Tạo 49 Users khác (Tổng cộng 50 Users)
        $now = now();
        $userData = [];
        for ($i = 2; $i <= 50; $i++) {
            $userData[] = [
                'name' => "Người chơi #{$i}",
                'email' => "player_{$i}@example.com",
                'avatar' => "https://api.dicebear.com/7.x/bottts/svg?seed=player_{$i}",
                'password' => '$2y$12$e6m2kF0NlU9W06bM3bQJveFv88vT6M7bC8o4i5b6e7n8p9q0r1s2t',
                'created_at' => $now,
                'updated_at' => $now,
            ];
        }

        foreach (array_chunk($userData, 25) as $chunk) {
            DB::table('users')->insertOrIgnore($chunk);
        }

        // Lấy danh sách ID của 50 users
        $allUserIds = DB::table('users')->orderBy('id')->pluck('id')->toArray();

        // Xóa sạch bảng điểm cũ
        DB::table('leaderboard_scores')->truncate();

        $seasonsConfig = [
            // Tháng 9: Bạn 2,500 điểm
            [
                'season' => $season1,
                'baseDate' => Carbon::parse('2026-09-01 08:00:00'),
                'myScore' => 2500,
            ],
            // Tháng 10: Bạn 1,800 điểm
            [
                'season' => $season2,
                'baseDate' => Carbon::parse('2026-10-01 08:00:00'),
                'myScore' => 1800,
            ],
            // Tháng 11: Bạn 2,150 điểm
            [
                'season' => $season3,
                'baseDate' => Carbon::parse('2026-11-01 08:00:00'),
                'myScore' => 2150,
            ],
        ];

        $scoreData = [];

        foreach ($seasonsConfig as $cfg) {
            $sId = $cfg['season']->id;
            $base = $cfg['baseDate'];

            // 1. Điểm của Bạn
            $scoreData[] = [
                'user_id' => $currentUser->id,
                'season_id' => $sId,
                'score' => $cfg['myScore'],
                'achieved_at' => $base->copy()->addDays(5)->addHours(12),
                'created_at' => $now,
                'updated_at' => $now,
            ];

            // 2. Điểm của 49 người chơi khác (Đủ đúng 50 dòng / tháng)
            foreach ($allUserIds as $uId) {
                if ($uId == $currentUser->id) continue;

                $scoreData[] = [
                    'user_id' => $uId,
                    'season_id' => $sId,
                    'score' => rand(10, 60) * 50, // 500 đến 3,000 điểm
                    'achieved_at' => $base->copy()->addMinutes(rand(100, 30000)),
                    'created_at' => $now,
                    'updated_at' => $now,
                ];
            }
        }

        // Nạp vào bảng điểm
        foreach (array_chunk($scoreData, 50) as $chunk) {
            DB::table('leaderboard_scores')->insertOrIgnore($chunk);
        }
    }
}

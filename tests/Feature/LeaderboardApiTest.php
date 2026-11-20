<?php

namespace Tests\Feature;

use App\Models\LeaderboardScore;
use App\Models\Season;
use App\Models\User;
use Database\Seeders\LeaderboardSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class LeaderboardApiTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        // Seed dữ liệu mẫu cho SQLite in-memory test DB
        $this->seed(LeaderboardSeeder::class);
    }

    public function test_can_fetch_seasons_list(): void
    {
        $response = $this->getJson('/api/v1/seasons');

        $response->assertStatus(200)
            ->assertJsonStructure([
                'success',
                'data' => [
                    '*' => ['id', 'name', 'code', 'is_active'],
                ],
            ]);
    }

    public function test_can_fetch_leaderboard_with_pagination_and_personal_rank(): void
    {
        $user = User::first();

        $response = $this->getJson('/api/v1/leaderboard?page=1&per_page=5', [
            'X-User-Id' => $user->id,
        ]);

        $response->assertStatus(200)
            ->assertJsonStructure([
                'success',
                'message',
                'data' => [
                    'current_season' => ['id', 'name', 'code', 'is_active'],
                    'available_seasons',
                    'my_rank' => [
                        'is_ranked',
                        'rank',
                        'score',
                        'user',
                    ],
                    'leaderboard' => [
                        '*' => ['rank', 'user_id', 'name', 'avatar', 'score', 'achieved_at', 'is_current_user'],
                    ],
                    'pagination' => [
                        'current_page',
                        'per_page',
                        'total_items',
                        'total_pages',
                        'has_next_page',
                        'has_prev_page',
                    ],
                ],
            ]);

        $responseData = $response->json('data');
        $this->assertCount(5, $responseData['leaderboard']);
        $this->assertEquals(1, $responseData['pagination']['current_page']);
        $this->assertNotNull($responseData['my_rank']['rank']);
    }

    public function test_validation_fails_for_invalid_params(): void
    {
        // per_page vượt quá 100
        $response = $this->getJson('/api/v1/leaderboard?per_page=999');
        $response->assertStatus(422)
            ->assertJsonValidationErrors(['per_page']);

        // season_id không tồn tại
        $response = $this->getJson('/api/v1/leaderboard?season_id=999999');
        $response->assertStatus(422)
            ->assertJsonValidationErrors(['season_id']);
    }
}

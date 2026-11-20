<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('leaderboard_scores', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
            $table->foreignId('season_id')->constrained('seasons')->cascadeOnDelete();
            $table->unsignedInteger('score')->default(0);
            $table->timestamp('achieved_at')->useCurrent();
            $table->timestamps();

            // Ràng buộc 1 user chỉ có 1 bản ghi điểm cho mỗi kì
            $table->unique(['season_id', 'user_id'], 'uniq_season_user');

            // Composite Index tối ưu hiệu năng sắp xếp và tính thứ hạng (Tie-breaking: điểm cao trước, đạt trước xếp trước)
            $table->index(['season_id', 'score', 'achieved_at'], 'idx_season_score_achieved');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('leaderboard_scores');
    }
};


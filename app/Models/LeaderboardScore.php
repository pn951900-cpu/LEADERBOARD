<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class LeaderboardScore extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'season_id',
        'score',
        'achieved_at',
    ];

    protected function casts(): array
    {
        return [
            'score'       => 'integer',
            'achieved_at' => 'datetime',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function season(): BelongsTo
    {
        return $this->belongsTo(Season::class);
    }
}


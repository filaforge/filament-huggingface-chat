<?php

namespace Filaforge\HuggingfaceChat\Models;

use Illuminate\Database\Eloquent\Model;

class ModelProfileUsage extends Model
{
    protected $table = 'hf_model_profile_usages';

    protected $fillable = [
        'user_id','model_profile_id','minute_at','count'
    ];

    protected $casts = [
        'minute_at' => 'datetime',
        'count' => 'integer',
    ];

    public static function incr(int $userId, int $profileId): void
    {
        $minute = now()->copy()->second(0)->micro(0);
        $row = static::query()->firstOrCreate([
            'user_id' => $userId,
            'model_profile_id' => $profileId,
            'minute_at' => $minute,
        ], ['count' => 0]);
        $row->increment('count');
    }

    public static function countForDay(int $userId, int $profileId): int
    {
        $start = now()->startOfDay();
        return (int) static::query()
            ->where('user_id',$userId)
            ->where('model_profile_id',$profileId)
            ->where('minute_at','>=',$start)
            ->sum('count');
    }

    public static function countForMinute(int $userId, int $profileId): int
    {
        $minute = now()->copy()->second(0)->micro(0);
        return (int) static::query()
            ->where('user_id',$userId)
            ->where('model_profile_id',$profileId)
            ->where('minute_at',$minute)
            ->value('count');
    }
}

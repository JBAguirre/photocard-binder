<?php

namespace App\Models;

use App\Enums\CardCondition;
use App\Enums\CardType;
use App\Enums\PhotocardStatus;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Builder;

class Photocard extends Model
{
    use HasFactory;

    protected $fillable = [
        'group_name',
        'member_name',
        'album',
        'version',
        'card_type',
        'status',
        'condition',
        'notes',
        'image_path',
    ];

    protected function casts(): array
    {
        return [
            'card_type' => CardType::class,
            'status' => PhotocardStatus::class,
            'condition' => CardCondition::class,
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function scopeFilter(Builder $query, array $filters): Builder
{
    return $query
        ->when($filters['status'] ?? null, function (Builder $q, string $status) {
            $q->where('status', $status);
        })
        ->when($filters['card_type'] ?? null, function (Builder $q, string $type) {
            $q->where('card_type', $type);
        })
        ->when($filters['group'] ?? null, function (Builder $q, string $group) {
            $q->where('group_name', 'like', "%{$group}%");
        })
        ->when($filters['member'] ?? null, function (Builder $q, string $member) {
            $q->where('member_name', 'like', "%{$member}%");
        })
        ->when($filters['q'] ?? null, function (Builder $q, string $term) {
            $q->where(function (Builder $q) use ($term) {
                $q->where('member_name', 'like', "%{$term}%")
                    ->orWhere('album', 'like', "%{$term}%")
                    ->orWhere('version', 'like', "%{$term}%");
            });
        });
}
}
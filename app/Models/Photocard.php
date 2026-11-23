<?php

namespace App\Models;

use App\Enums\CardCondition;
use App\Enums\CardType;
use App\Enums\PhotocardStatus;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

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
}
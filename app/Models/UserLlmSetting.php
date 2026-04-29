<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class UserLlmSetting extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'provider',
        'api_key_encrypted',
        'base_url',
        'default_model',
        'timeout',
    ];

    protected function casts(): array
    {
        return [
            'timeout' => 'integer',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}

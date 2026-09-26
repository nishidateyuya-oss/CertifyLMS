<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class GoogleCredential extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'calendar_id',
        'access_token',
        'refresh_token',
        'token_expires_at',
        'connected_at',
    ];

    protected $casts = [
        'token_expires_at' => 'datetime',
        'connected_at' => 'datetime',
    ];

    /**
     * @return BelongsTo<User, $this>
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}

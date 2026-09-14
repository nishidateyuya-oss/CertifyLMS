<?php

declare(strict_types=1);

namespace App\Models;

use App\Enums\AnnouncementTargetType;
use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Announcement extends Model
{
    use HasFactory, HasUlids;

    protected $fillable = [
        'title',
        'target_type',
        'sender_id',
        'target_certification_id',
        'target_user_id',
        'body',
        'dispatched_count',
        'dispatched_at',
    ];

    protected $casts = [
        'target_type' => AnnouncementTargetType::class,
        'dispatched_at' => 'date',
    ];

    public function targetCertification(): BelongsTo
    {
        return $this->belongsTo(Certification::class, 'target_certification_id');
    }

    public function targetUser(): BelongsTo
    {
        return $this->belongsTo(User::class, 'target_user_id');
    }

    public function createdBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'sender_id');
    }
}

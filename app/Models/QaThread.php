<?php

declare(strict_types=1);

namespace App\Models;

use App\Enums\QaThreadStatus;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class QaThread extends Model
{
    use HasFactory;

    protected $casts = [
        'status' => QaThreadStatus::class,
    ];

    protected $fillable = [
        'certification_id',
        'title',
        'body',
        'status',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function certification()
    {
        return $this->belongsTo(Certification::class);
    }

    public function replies()
    {
        return $this->hasMany(QaReply::class);
    }
}

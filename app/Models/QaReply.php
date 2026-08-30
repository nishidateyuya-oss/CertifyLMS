<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class QaReply extends Model
{
    use HasFactory;

    protected $fillable = [
        'body',
        'qa_thread_id',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function qaThread()
    {
        return $this->belongsTo(QaThread::class);
    }
}

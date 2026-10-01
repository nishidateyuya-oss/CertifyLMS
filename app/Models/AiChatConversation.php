<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOneThrough;

class AiChatConversation extends Model
{
    use HasFactory;

    /**
     * テーブル名の明示的指定
     */
    protected $table = 'ai_chat_conversations';

    /**
     * 一括割り当て可能な属性
     */
    protected $fillable = [
        'user_id',
        'title',
        'auto_title_enabled',
        'section_id',
        'target_certification',
        'last_message_at', // ★ 追記：並び替え用日時の更新に必要です
    ];

    /**
     * 属性のキャスト
     */
    protected $casts = [
        'auto_title_enabled' => 'boolean',
        'last_message_at' => 'datetime', // ★ 追記：日時オブジェクトとして扱うため
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function messages(): HasMany
    {
        return $this->hasMany(AiChatMessage::class, 'ai_chat_conversation_id');
    }

    public function section(): BelongsTo
    {
        return $this->belongsTo(Section::class);
    }

    public function enrollment(): HasOneThrough
    {
        return $this->hasOneThrough(
            Enrollment::class,
            User::class,
            'id',       // User モデルのローカルキー
            'user_id',  // Enrollment モデルの外部キー
            'user_id',  // AiChatConversation モデルの外部キー
            'id'        // User モデルのローカルキー
        );
    }
}

<?php

declare(strict_types=1);

namespace App\Enums;

enum PaymentStatus: string
{
    case Succeeded = 'succeeded';
    case Pending = 'pending';
    case Failed = 'failed';
    case Refunded = 'refunded';

    /**
     * 画面表示用の日本語ラベルを取得するメソッド（オプション）
     */
    public function label(): string
    {
        return match ($this) {
            self::Succeeded => '決済完了',
            self::Pending => '処理中',
            self::Failed => '決済失敗',
            self::Refunded => '返金済み',
        };
    }
}

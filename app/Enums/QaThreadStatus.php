<?php

declare(strict_types=1);

namespace App\Enums;

enum QaThreadStatus: string
{
    case Unresolved = 'unresolved';         // 受付中（未解決）
    case Resolved = 'resolved'; // 解決済み

    /**
     * 日本語ラベルを返す（必要に応じて）
     */
    public function label(): string
    {
        return match ($this) {
            self::Unresolved => '未解決',
            self::Resolved => '解決済',
        };
    }
}

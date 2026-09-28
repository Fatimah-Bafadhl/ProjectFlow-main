<?php

namespace App\Enums;

enum TaskStatus: string
{
    case Pending = 'pending';
    case InProgress = 'in_progress';
    case InReview = 'in_review';
    case Completed = 'completed';
    case Paused = 'paused';

    // Arabic text for now. In L4 this moves to lang/ar/statuses.php.
    public function label(): string
    {
        return match ($this) {
            self::Pending => 'قيد الانتظار',
            self::InProgress => 'قيد التنفيذ',
            self::InReview => 'قيد المراجعة',
            self::Completed => 'مكتملة',
            self::Paused => 'متوقف مؤقتاً',
        };
    }

    public function badgeClass(): string
    {
        return match ($this) {
            self::Pending => 'badge-status-waiting',
            self::InProgress => 'badge-status-progress',
            self::InReview => 'badge-status-review',
            self::Completed => 'badge-status-done',
            self::Paused => 'badge-status-paused',
        };
    }

    // Used by ProjectStage::taskProgressPercent()
    public function progressWeight(): int
    {
        return match ($this) {
            self::Completed => 100,
            self::InReview => 75,
            self::InProgress => 50,
            self::Paused => 25,
            self::Pending => 0,
        };
    }
}
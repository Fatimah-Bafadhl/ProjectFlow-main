<?php

namespace App\Enums;

enum TaskPriority: string
{
    case Low = 'low';
    case Medium = 'medium';
    case High = 'high';

    // Text used in dropdowns. In L4 this moves to lang/ar/priorities.php.
    public function label(): string
    {
        return match ($this) {
            self::Low => 'منخفض',
            self::Medium => 'متوسط',
            self::High => 'عالي',
        };
    }

    // Text used in the badge (feminine form, read after the word "أولوية")
    public function badgeLabel(): string
    {
        return match ($this) {
            self::Low => 'أولوية منخفضة',
            self::Medium => 'أولوية متوسطة',
            self::High => 'أولوية عالية',
        };
    }

    public function badgeClass(): string
    {
        return match ($this) {
            self::Low => 'badge-priority-low',
            self::Medium => 'badge-priority-medium',
            self::High => 'badge-priority-high',
        };
    }
}

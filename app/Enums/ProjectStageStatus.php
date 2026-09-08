<?php

namespace App\Enums;

enum ProjectStageStatus: string
{
    case NotStarted = 'not_started';
    case InProgress = 'in_progress';
    case Done = 'done';

    public function label(): string
    {
        return match ($this) {
            self::NotStarted => 'لم تبدأ',
            self::InProgress => 'قيد التنفيذ',
            self::Done => 'مكتملة',
        };
    }
}
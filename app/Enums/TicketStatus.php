<?php

namespace App\Enums;

enum TicketStatus: string
{
    case Open = 'open';
    case Handled = 'handled';

    public function label(): string
    {
        return match ($this) {
            self::Open => 'مفتوح',
            self::Handled => 'تم التعامل معه',
        };
    }
}
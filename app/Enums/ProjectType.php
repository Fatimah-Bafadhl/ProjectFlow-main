<?php

namespace App\Enums;

enum ProjectType: string
{
    case App = 'app';
    case Website = 'website';

    public function label(): string
    {
        return match ($this) {
            self::App => 'تطوير تطبيق',
            self::Website => 'تطوير موقع إلكتروني',
        };
    }
}
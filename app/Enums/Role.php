<?php

namespace App\Enums;

enum Role: string
{
    case Admin = 'admin';
    case Manager = 'manager';
    case Employee = 'employee';
    case Client = 'client';

    public function label(): string
    {
        return match($this) {
            self::Admin => 'مدير النظام',
            self::Manager => 'مدير ',
            self::Employee => 'موظف',
            self::Client => 'عميل',
        };
    }
}
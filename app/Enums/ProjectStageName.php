<?php

namespace App\Enums;

enum ProjectStageName: string
{
    case Planning = 'planning';
    case RequirementsAnalysis = 'requirements_analysis';
    case UxUiDesign = 'ux_ui_design';
    case Development = 'development';
    case Testing = 'testing';
    case PreLaunch = 'pre_launch';
    case Launch = 'launch';

    public function label(): string
    {
        return match ($this) {
            self::Planning => 'مرحلة التخطيط',
            self::RequirementsAnalysis => 'مرحلة تحليل المتطلبات',
            self::UxUiDesign => 'مرحلة تجربة المستخدم والتصميم',
            self::Development => 'مرحلة التطوير',
            self::Testing => 'مرحلة الاختبار',
            self::PreLaunch => 'مرحلة التجهيز للإطلاق',
            self::Launch => 'مرحلة الإطلاق',
        };
    }

    public function order(): int
    {
        return match ($this) {
            self::Planning => 1,
            self::RequirementsAnalysis => 2,
            self::UxUiDesign => 3,
            self::Development => 4,
            self::Testing => 5,
            self::PreLaunch => 6,
            self::Launch => 7,
        };
    }

        public function color(): string
    {
        return match ($this) {
            self::Planning => '#3B82F6',
            self::RequirementsAnalysis => '#6366F1',
            self::UxUiDesign => '#A855F7',
            self::Development => '#F59E0B',
            self::Testing => '#EF4444',
            self::PreLaunch => '#F97316',
            self::Launch => '#22C55E',
        };
    }
}
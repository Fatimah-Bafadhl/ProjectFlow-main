<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    // Old value => new key. Every variant seen in the code or the data is listed.
    private array $taskStatusUp = [
        'قيد الانتظار' => 'pending',
        'Not started' => 'pending',
        'قيد التنفيذ' => 'in_progress',
        'قيد المراجعة' => 'in_review',
        'مكتملة' => 'completed',
        'مكتمل' => 'completed',
        'متوقف مؤقتاً' => 'paused',
        'متوقف مؤقتا' => 'paused',
    ];

    private array $priorityUp = [
        'منخفض' => 'low',
        'متوسط' => 'medium',
        'عالي' => 'high',
    ];

    // projects.status holds "pending", "completed", or the label of the current stage.
    private array $projectStatusUp = [
        'قيد الانتظار' => 'pending',
        'Not started' => 'pending',
        'مكتملة' => 'completed',
        'مكتمل' => 'completed',
        'مرحلة التخطيط' => 'planning',
        'مرحلة تحليل المتطلبات' => 'requirements_analysis',
        'مرحلة تجربة المستخدم والتصميم' => 'ux_ui_design',
        'مرحلة التطوير' => 'development',
        'مرحلة الاختبار' => 'testing',
        'مرحلة التجهيز للإطلاق' => 'pre_launch',
        'مرحلة الإطلاق' => 'launch',
    ];

    private array $taskStatusDown = [
        'pending' => 'قيد الانتظار',
        'in_progress' => 'قيد التنفيذ',
        'in_review' => 'قيد المراجعة',
        'completed' => 'مكتملة',
        'paused' => 'متوقف مؤقتاً',
    ];

    private array $priorityDown = [
        'low' => 'منخفض',
        'medium' => 'متوسط',
        'high' => 'عالي',
    ];

    private array $projectStatusDown = [
        'pending' => 'قيد الانتظار',
        'completed' => 'مكتملة',
        'planning' => 'مرحلة التخطيط',
        'requirements_analysis' => 'مرحلة تحليل المتطلبات',
        'ux_ui_design' => 'مرحلة تجربة المستخدم والتصميم',
        'development' => 'مرحلة التطوير',
        'testing' => 'مرحلة الاختبار',
        'pre_launch' => 'مرحلة التجهيز للإطلاق',
        'launch' => 'مرحلة الإطلاق',
    ];

    public function up(): void
    {
        DB::transaction(function () {
            $this->convert('tasks', 'status', $this->taskStatusUp);
            $this->convert('tasks', 'priority', $this->priorityUp);
            $this->convert('projects', 'status', $this->projectStatusUp);

            // If any value was not mapped, stop and roll everything back.
            $this->assertOnly('tasks', 'status', array_values(array_unique($this->taskStatusUp)));
            $this->assertOnly('tasks', 'priority', array_values($this->priorityUp));
            $this->assertOnly('projects', 'status', array_values(array_unique($this->projectStatusUp)));
        });
    }

    public function down(): void
    {
        DB::transaction(function () {
            $this->convert('tasks', 'status', $this->taskStatusDown);
            $this->convert('tasks', 'priority', $this->priorityDown);
            $this->convert('projects', 'status', $this->projectStatusDown);
        });
    }

    // Uses the query builder on purpose: no model events fire, and soft-deleted rows are included.
    private function convert(string $table, string $column, array $map): void
    {
        foreach ($map as $old => $new) {
            DB::table($table)
                ->whereRaw("trim({$column}) = ?", [$old])
                ->update([$column => $new]);
        }
    }

    private function assertOnly(string $table, string $column, array $allowed): void
    {
        $unmapped = DB::table($table)
            ->whereNotIn($column, $allowed)
            ->distinct()
            ->pluck($column)
            ->all();

        if (! empty($unmapped)) {
            throw new RuntimeException("Unmapped values in {$table}.{$column}: " . implode(' | ', $unmapped));
        }
    }
};
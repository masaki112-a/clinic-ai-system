<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Visit;
use App\Models\ExamSession;

class DevelopmentSeeder extends Seeder
{
    /**
     * 開発環境用のテストデータを生成
     */
    public function run(): void
    {
        // 待機中の患者 3名
        Visit::factory()->count(3)->waiting()->create();

        // 呼出中の患者 1名
        Visit::factory()->calling()->create();

        // 診察中の患者 1名（診察セッション付き）
        $inExamVisit = Visit::factory()->inExam()->create();
        ExamSession::factory()->inExam()->create([
            'visit_id' => $inExamVisit->id,
        ]);

        // 完了済みの患者 5名
        Visit::factory()->count(5)->completed()->create();

        // 診察なし会計の患者 1名
        Visit::factory()->noExam()->create();

        $this->command->info('Development test data created successfully.');
        $this->command->info('- Waiting: 3 patients');
        $this->command->info('- Calling: 1 patient');
        $this->command->info('- In Exam: 1 patient');
        $this->command->info('- Completed: 5 patients');
        $this->command->info('- No Exam: 1 patient');
    }
}

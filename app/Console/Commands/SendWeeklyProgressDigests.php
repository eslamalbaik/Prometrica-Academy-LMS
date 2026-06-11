<?php

namespace App\Console\Commands;

use App\Mail\WeeklyProgressDigest;
use App\Models\StudyPlan;
use App\Models\User;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Mail;

class SendWeeklyProgressDigests extends Command
{
    protected $signature   = 'study:send-digests';
    protected $description = 'Send weekly progress digest emails to all active students (FTR-005)';

    public function handle(): int
    {
        $this->info('Sending weekly progress digest emails…');

        $students = User::query()
            ->where('role', 'student')
            ->where('study_emails_enabled', true)
            ->whereHas('studyPlans', fn ($q) => $q->where('status', 'active'))
            ->with([
                'studyPlans' => fn ($q) => $q
                    ->where('status', 'active')
                    ->with([
                        'course:id,title,thumbnail',
                        'tasks',
                    ]),
            ])
            ->get();

        $sent = 0;
        foreach ($students as $student) {
            $activePlans = $student->studyPlans->filter(fn ($p) => $p->tasks->isNotEmpty());

            if ($activePlans->isEmpty()) {
                continue;
            }

            Mail::to($student->email)->queue(
                new WeeklyProgressDigest($student, $activePlans)
            );

            $sent++;
        }

        $this->info("Queued {$sent} weekly progress digest emails.");
        return self::SUCCESS;
    }
}

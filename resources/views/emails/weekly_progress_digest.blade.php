<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Weekly Study Progress — Prometrica Academy</title>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { font-family: 'Segoe UI', Arial, sans-serif; background: #f0f2f5; color: #333; padding: 24px 12px; }
        .wrapper { max-width: 600px; margin: 0 auto; }
        .card { background: #fff; border-radius: 12px; overflow: hidden; box-shadow: 0 2px 12px rgba(0,0,0,.08); }
        .header { background: linear-gradient(135deg, #6c5ce7, #a29bfe); padding: 36px 40px; color: white; }
        .header h1 { font-size: 22px; font-weight: 700; }
        .header p { margin-top: 8px; opacity: .9; font-size: 15px; }
        .body { padding: 32px 40px; }
        .plan-card { border: 1px solid #e8e8f0; border-radius: 10px; padding: 22px; margin-bottom: 24px; }
        .plan-title { font-size: 17px; font-weight: 700; color: #6c5ce7; margin-bottom: 4px; }
        .plan-meta { font-size: 13px; color: #888; margin-bottom: 14px; }
        .progress-row { display: flex; align-items: center; gap: 12px; margin: 14px 0; }
        .progress-bar { flex: 1; background: #eee; border-radius: 100px; height: 8px; overflow: hidden; }
        .progress-fill { background: linear-gradient(90deg, #6c5ce7, #a29bfe); border-radius: 100px; height: 8px; }
        .progress-pct { font-size: 15px; font-weight: 700; color: #6c5ce7; min-width: 44px; text-align: right; }
        .tasks-title { font-size: 13px; font-weight: 600; color: #555; margin: 16px 0 8px; text-transform: uppercase; letter-spacing: .5px; }
        .task-item { display: flex; align-items: flex-start; gap: 10px; padding: 8px 0; border-bottom: 1px solid #f4f4f8; }
        .task-item:last-child { border-bottom: none; }
        .task-icon { font-size: 16px; flex-shrink: 0; }
        .task-name { font-size: 14px; color: #333; }
        .task-date { font-size: 12px; color: #aaa; margin-top: 2px; }
        .all-done { background: #e8f8f0; border-radius: 8px; padding: 14px 18px; color: #27ae60; font-weight: 600; font-size: 14px; }
        .btn { display: inline-block; background: #6c5ce7; color: #fff !important; text-decoration: none; padding: 14px 28px; border-radius: 8px; font-size: 15px; font-weight: 600; margin-top: 8px; }
        .footer { text-align: center; padding: 24px 40px; font-size: 12px; color: #aaa; }
        .footer a { color: #aaa; }
    </style>
</head>
<body>
<div class="wrapper">
    <div class="card">
        <div class="header">
            <h1>📚 Your Weekly Study Progress</h1>
            <p>Hello {{ $user->name }}! Here's a snapshot of your learning journey this week.</p>
        </div>

        <div class="body">
            @foreach ($plans as $plan)
                @php
                    $progress   = $plan->progressPercentage();
                    $completed  = $plan->completedTasksCount();
                    $total      = $plan->totalTasksCount();
                    $upcoming   = $plan->tasks->whereNull('completed_at')->take(5);
                @endphp

                <div class="plan-card">
                    <div class="plan-title">{{ $plan->course?->title ?? 'Your Course' }}</div>
                    <div class="plan-meta">{{ $completed }} of {{ $total }} tasks completed</div>

                    <div class="progress-row">
                        <div class="progress-bar">
                            <div class="progress-fill" style="width: {{ $progress }}%"></div>
                        </div>
                        <div class="progress-pct">{{ $progress }}%</div>
                    </div>

                    @if ($upcoming->isNotEmpty())
                        <div class="tasks-title">📋 Upcoming Tasks</div>
                        @foreach ($upcoming as $task)
                            <div class="task-item">
                                <span class="task-icon">{{ $task->type === 'quiz' ? '📝' : '🎬' }}</span>
                                <div>
                                    <div class="task-name">{{ $task->title }}</div>
                                    <div class="task-date">Scheduled: {{ \Carbon\Carbon::parse($task->scheduled_date)->format('M d, Y') }}</div>
                                </div>
                            </div>
                        @endforeach
                    @else
                        <div class="all-done">🎉 All tasks completed — exceptional work!</div>
                    @endif
                </div>
            @endforeach

            <a href="{{ env('LANDING_URL', '') }}/student/study-plan" class="btn">
                View Full Study Plan →
            </a>
        </div>

        <div class="footer">
            <p>You received this because you have an active study plan at Prometrica Academy.</p>
            <p style="margin-top:8px">
                <a href="{{ env('LANDING_URL', '') }}/student/study-plan?unsubscribe=1">Unsubscribe from weekly emails</a>
            </p>
        </div>
    </div>
</div>
</body>
</html>

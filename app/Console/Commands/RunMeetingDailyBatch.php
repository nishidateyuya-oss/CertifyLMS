<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Enums\MeetingStatus;
use App\Models\Meeting;
use App\Notifications\MeetingReminderNotification;
use Illuminate\Console\Command;

class RunMeetingDailyBatch extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'app:run-meeting-daily-batch';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = '前日及び、面談時間一時間前の面談リマインダー通知';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $now = now()->second(0); // 秒を切り捨て

        // 1. 前日リマインド (例: 明日の同時刻あたりの面談を取得)
        $tomorrowStart = $now->copy()->addDay()->startOfMinute();
        $tomorrowEnd = $now->copy()->addDay()->endOfMinute();

        $beforeOneDayMeetings = Meeting::where('status', MeetingStatus::Reserved)->whereBetween('scheduled_at', [$tomorrowStart, $tomorrowEnd])->get();

        foreach ($beforeOneDayMeetings as $meeting) {
            if ($meeting->student) {
                $meeting->student->notify(new MeetingReminderNotification($meeting, 'before_one_days'));
            }
        }

        // 2. 1時間前リマインド (例: 1時間後の同時刻あたりの面談を取得)
        $oneHourStart = $now->copy()->addHour()->startOfMinute();
        $oneHourEnd = $now->copy()->addHour()->endOfMinute();

        $beforeOneHourMeetings = Meeting::where('status', MeetingStatus::Reserved)->whereBetween('scheduled_at', [$oneHourStart, $oneHourEnd])->get();

        foreach ($beforeOneHourMeetings as $meeting) {
            if ($meeting->student) {
                $meeting->student->notify(new MeetingReminderNotification($meeting, 'before_one_hours'));
            }
        }
    }
}

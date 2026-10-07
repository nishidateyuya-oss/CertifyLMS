<?php

declare(strict_types=1);

namespace App\UseCases\Meeting;

use App\Models\Certification;
use App\Models\Enrollment;
use App\Models\User;
use App\Services\GoogleCalendarService;
use App\Services\MeetingAvailabilityService;
use Carbon\Carbon;

class FetchMeetingAvailabilityAction
{
    public function __construct(
        private readonly MeetingAvailabilityService $availabilityService,
        private readonly GoogleCalendarService $googleCalendarService,
    ) {}

    /**
     * @return array{date: string, slots: array<int, array{slot_start: string, slot_end: string, available_coach_count: int}>}
     */
    public function execute(Enrollment $enrollment, Carbon $date): array
    {
        $certification = $enrollment->loadMissing('certification')->certification;

        // 1. DB基準の空き枠を取得
        $slots = $this->availabilityService->slotsForCertification($certification, $date);

        // 2. 該当資格のGoogle連携済コーチを取得
        $coachesWithGoogle = $certification->coaches()
            ->has('googleCredential')
            ->with('googleCredential')
            ->get();

        // コーチ数が0、または枠が0ならそのまま返す（無駄なAPI呼び出しを防止）
        if ($coachesWithGoogle->isEmpty() || $slots->isEmpty()) {
            return [
                'date' => $date->toDateString(),
                'slots' => $slots->map(fn (array $slot) => [
                    'slot_start' => $slot['slot_start']->toIso8601String(),
                    'slot_end' => $slot['slot_end']->toIso8601String(),
                    'available_coach_count' => $slot['available_coach_count'],
                ])->all(),
            ];
        }

        // 3. コーチごとに「その日1日分（00:00〜23:59）」の Busy スロットを一括取得
        $dayStart = $date->copy()->startOfDay();
        $dayEnd = $date->copy()->endOfDay();

        $coachBusyMap = [];
        foreach ($coachesWithGoogle as $coach) {
            $coachBusyMap[$coach->id] = $this->googleCalendarService->getBusySlots(
                $coach->googleCredential,
                $dayStart,
                $dayEnd
            );
        }

        // 4. 各スロットについて「実際にシフトが入っていて、かつ Busy でないコーチ」を判定
        $filteredSlots = $slots->map(function (array $slot) use ($certification, $coachesWithGoogle, $coachBusyMap) {
            $slotStart = $slot['slot_start'];
            $slotEnd = $slot['slot_end'];

            $busyCount = 0;
            foreach ($coachesWithGoogle as $coach) {
                if ($this->isCoachAssignedToSlot($certification, $coach, $slotStart)) {
                    $busySlots = $coachBusyMap[$coach->id] ?? [];

                    if ($this->hasOverlap($busySlots, $slotStart, $slotEnd)) {
                        $busyCount++;
                    }
                }
            }

            $slot['available_coach_count'] = max(0, $slot['available_coach_count'] - $busyCount);

            return $slot;
        })->filter(fn (array $slot) => $slot['available_coach_count'] > 0);

        // 5. フィルタリング後の $filteredSlots を整形して返却
        return [
            'date' => $date->toDateString(),
            'slots' => $filteredSlots->map(fn (array $slot) => [
                'slot_start' => $slot['slot_start']->toIso8601String(),
                'slot_end' => $slot['slot_end']->toIso8601String(),
                'available_coach_count' => $slot['available_coach_count'],
            ])->values()->all(),
        ];
    }

    private function isCoachAssignedToSlot(Certification $certification, User $coach, Carbon $scheduledAt): bool
    {
        $time = $scheduledAt->format('H:i:s');

        return $coach->coachAvailabilities()
            ->where('day_of_week', $scheduledAt->dayOfWeek)
            ->where('is_active', true)
            ->where('start_time', '<=', $time)
            ->where('end_time', '>', $time)
            ->exists();
    }

    private function hasOverlap(array $busySlots, Carbon $slotStart, Carbon $slotEnd): bool
    {
        foreach ($busySlots as $busy) {
            $busyStart = Carbon::parse($busy['start']);
            $busyEnd = Carbon::parse($busy['end']);

            if ($slotStart->lessThan($busyEnd) && $slotEnd->greaterThan($busyStart)) {
                return true;
            }
        }

        return false;
    }
}

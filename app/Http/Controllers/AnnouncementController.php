<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Enums\AnnouncementTargetType;
use App\Enums\CertificationStatus;
use App\Enums\UserRole;
use App\Enums\UserStatus;
use App\Http\Requests\StoreAnnouncementRequest;
use App\Models\Announcement;
use App\Models\Certification;
use App\Models\User;
use App\Notifications\NewMessageNotification;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Notification;

class AnnouncementController extends Controller
{
    public function index()
    {
        $announcements = Announcement::paginate(7);

        return view('announcement.management.index', compact('announcements'));
    }

    public function create()
    {

        $students = User::where('role', UserRole::Student)->where('status', UserStatus::InProgress)->get();
        $certifications = Certification::where('status', CertificationStatus::Published);

        return view('announcement.management.create', compact(['students', 'certifications']));
    }

    public function store(StoreAnnouncementRequest $request)
    {
        $validated = $request->validated();
        $validated['sender_id'] = $request->user()->id;

        // 1. target_type に応じて対象受講生（User コレクション）を取得
        $targetType = $validated['target_type'];

        $targetStudents = match ($targetType) {
            // 全受講生 (受講中の受講生)
            AnnouncementTargetType::AllStudents, AnnouncementTargetType::AllStudents->value => User::where('role', UserRole::Student->value)->where('status', UserStatus::InProgress->value)->get(),

            // 資格指定 (該当資格を受講中の受講生)
            AnnouncementTargetType::Certification, AnnouncementTargetType::Certification->value => User::whereHas('enrollments', function ($query) use ($validated) {
                $query->where('certification_id', $validated['target_certification_id']);
            })->get(),

            // ユーザー指定 (指定された1人)
            AnnouncementTargetType::User, AnnouncementTargetType::User->value => User::where('id', $validated['target_user_id'])->get(),

            default => collect(),
        };

        // 2. 配信メタデータのセットと保存（トランザクション保護）
        $announcement = DB::transaction(function () use ($validated, $targetStudents) {
            $validated['dispatched_count'] = $targetStudents->count();
            $validated['dispatched_at'] = now();

            return Announcement::create($validated);
        });

        // 3. 該当する受講生へ通知を一括送信 (Notification ファサードを使用)
        if ($targetStudents->isNotEmpty()) {
            Notification::send($targetStudents, new NewMessageNotification($announcement));
        }

        return redirect()
            ->route('admin.announcements.index')
            ->with('success', 'お知らせを配信しました。');
    }

    public function show(Announcement $announcement)
    {
        return view('announcement.management.show', compact('announcement'));
    }
}

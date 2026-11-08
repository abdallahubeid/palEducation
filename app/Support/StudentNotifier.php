<?php

namespace App\Support;

use App\Models\Student;
use App\Models\User;
use App\Notifications\StudentNotification;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Notification;

/**
 * من يصله إشعار الطالب، ومتى يُكبَح.
 *
 * 🔴 مكان واحد — كل موضع إرسال يستدعي هذا بدل أن يحلّ المستلمين بنفسه.
 */
final class StudentNotifier
{
    /**
     * يُخطر طالباً واحداً، إلا إن كان هو مسبّب الحدث.
     */
    public static function notify(Student $student, StudentNotification $notification, ?User $except = null): void
    {
        $account = $student->user;

        if ($account === null || ! $account->is_active) {
            return;
        }

        if ($except !== null && $account->is($except)) {
            return;
        }

        $account->notify($notification);
    }

    /**
     * يُرسل لكلّ حسابات الطلبة الفاعلة.
     */
    public static function broadcast(StudentNotification $notification, ?User $except = null): void
    {
        $recipients = self::recipients($except);

        if ($recipients->isEmpty()) {
            return;
        }

        Notification::send($recipients, $notification);
    }

    /**
     * كلّ حساب طالب فاعل.
     *
     * @return Collection<int, User>
     */
    public static function recipients(?User $except = null): Collection
    {
        return User::query()
            ->where('userable_type', Student::class)
            // ⚠️ المعطَّلون يُتخطَّون: لا يستطيعون الدخول ليقرؤوا.
            ->where('is_active', true)
            ->when($except !== null, fn ($query) => $query->whereKeyNot($except->id))
            ->get();
    }
}

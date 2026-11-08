<?php

namespace App\Notifications;

/**
 * The admin removed a subject assignment from this teacher.
 *
 * ⚠️ The teacher keeps no further access to that subject's content — the
 * notification says so plainly rather than letting them discover it by a
 * screen that quietly went empty.
 */
class SubjectUnassignedFromTeacherNotification extends TeacherNotification
{
    public function __construct(public string $subjectName) {}

    public function title(): string
    {
        return 'أُلغي إسناد مادة';
    }

    public function message(): string
    {
        return 'لم تعد تُدرّس مادة «'.$this->subjectName.'» — محتواها لم يعد يظهر لك.';
    }

    public function icon(): string
    {
        return 'book';
    }

    public function url(): string
    {
        return route('teacher.subjects');
    }

    public function type(): string
    {
        return 'warning';
    }
}

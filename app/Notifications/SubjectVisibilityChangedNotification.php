<?php

namespace App\Notifications;

/**
 * The admin published or hid one of this teacher's subjects.
 *
 * 🔴 A snapshot of the state at the moment it changed. `published` here is not
 * re-read from the row, so a later toggle does not rewrite the wording of a
 * notification about the earlier one.
 */
class SubjectVisibilityChangedNotification extends TeacherNotification
{
    public function __construct(public string $subjectName, public bool $published) {}

    public function title(): string
    {
        return $this->published ? 'نُشرت مادتك' : 'أُخفيت مادتك';
    }

    public function message(): string
    {
        return $this->published
            ? 'مادة «'.$this->subjectName.'» صارت ظاهرة لطلبة فرعها.'
            : 'مادة «'.$this->subjectName.'» لم تعد ظاهرة للطلبة.';
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
        return $this->published ? 'success' : 'warning';
    }
}

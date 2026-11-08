<?php

namespace App\Notifications;

/**
 * The admin assigned a subject to this teacher.
 */
class SubjectAssignedToTeacherNotification extends TeacherNotification
{
    public function __construct(public string $subjectName) {}

    public function title(): string
    {
        return 'مادة جديدة أُسندت إليك';
    }

    public function message(): string
    {
        return 'صرت تُدرّس مادة «'.$this->subjectName.'» — محتواها الآن تحت إدارتك.';
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
        return 'success';
    }
}

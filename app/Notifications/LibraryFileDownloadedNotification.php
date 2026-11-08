<?php

namespace App\Notifications;

/**
 * Someone downloaded one of this teacher's library files.
 *
 * ⚠️ Fired by TeacherNotifier with the downloader excluded, so a teacher
 * fetching their own file gets nothing. Today the download route is
 * teacher-only, so this stays silent — it is wired now so that the day the
 * student delivery route ships, the teacher starts hearing about it with no
 * further change.
 */
class LibraryFileDownloadedNotification extends TeacherNotification
{
    public function __construct(public string $fileTitle) {}

    public function title(): string
    {
        return 'تنزيل ملف من مكتبتك';
    }

    public function message(): string
    {
        return 'نُزّل ملف «'.$this->fileTitle.'».';
    }

    public function icon(): string
    {
        return 'folder';
    }

    public function url(): string
    {
        return route('teacher.library');
    }

    public function type(): string
    {
        return 'info';
    }
}

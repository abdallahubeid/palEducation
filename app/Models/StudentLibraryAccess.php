<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * وصول طالب واحد إلى ملف مكتبة واحد — صفّ فريد لكل زوج.
 *
 * ⚠️ منفصل عن library_files.download_count: ذاك مجموع كلّي، وهذا «مَن ومتى»
 * لعدّاد «موارد اطُّلع عليها» على شاشة تقدّم الطالب.
 */
class StudentLibraryAccess extends Model
{
    protected $table = 'student_library_access';

    protected $fillable = [
        'student_id',
        'library_file_id',
        'download_count',
        'first_accessed_at',
        'last_accessed_at',
    ];

    protected function casts(): array
    {
        return [
            'download_count' => 'integer',
            'first_accessed_at' => 'datetime',
            'last_accessed_at' => 'datetime',
        ];
    }

    public function student(): BelongsTo
    {
        return $this->belongsTo(Student::class);
    }

    public function libraryFile(): BelongsTo
    {
        return $this->belongsTo(LibraryFile::class);
    }
}

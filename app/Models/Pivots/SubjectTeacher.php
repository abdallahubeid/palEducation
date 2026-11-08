<?php

namespace App\Models\Pivots;

use Illuminate\Database\Eloquent\Relations\Pivot;

/**
 * The subject ⇄ teacher assignment row.
 *
 * 🔴 Exists because the casts declared with withCasts() on the relation were
 * INERT for pivot attributes: `is_primary` came back as int 1 and
 * `assigned_at` as a raw string, so `$pivot->assigned_at->format(...)` would
 * fatal and every boolean check was comparing an integer. A declared cast
 * that silently does nothing is worse than no cast — the code reads as if
 * the types are handled.
 *
 * Pivot casts only apply through a pivot model reached by using().
 */
class SubjectTeacher extends Pivot
{
    protected $table = 'subject_teacher';

    /** ⚠️ The pivot HAS an id column, so it is a real incrementing row. */
    public $incrementing = true;

    protected function casts(): array
    {
        return [
            'is_primary' => 'boolean',
            'assigned_at' => 'datetime',
        ];
    }
}

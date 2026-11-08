<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasManyThrough;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

/**
 * الفرع — الفلتر الجذري للمنصة.
 */
class Branch extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'name',
        'slug',
        'description',
        'icon',
        'accent_token',
        'btn_text',
        'btn_link',
        'display_order',
        'is_active',
    ];

    protected function casts(): array
    {
        return ['is_active' => 'boolean'];
    }

    /** نصّ بلا رابط يُنتج زرّاً ميّتاً — يُفحص الاثنان معاً. */
    public function hasAction(): bool
    {
        return filled($this->btn_text) && filled($this->btn_link);
    }

    public function getRouteKeyName(): string
    {
        return 'slug';
    }

    public function students(): HasMany
    {
        return $this->hasMany(Student::class);
    }

    public function subjects(): HasMany
    {
        return $this->hasMany(Subject::class);
    }

    /**
     * كل محاضرات الفرع عبر مواده.
     *
     * موجودة لأن السؤال «ماذا في هذا الفرع؟» يُطرح في شاشة الفرع العامة
     * وفي إحصاءات الأدمن؛ بدونها يُجمع الجواب بحلقة على المواد = N+1.
     */
    public function lectures(): HasManyThrough
    {
        return $this->hasManyThrough(Lecture::class, Subject::class);
    }

    /*
    |--------------------------------------------------------------------------
    | Scopes
    |--------------------------------------------------------------------------
    */

    /**
     * Adds subjects_count and teachers_count for the public branch card.
     *
     * 🔴 Derived, never stored. Both figures used to be columns on this
     * table, which meant the card advertised whatever was typed there long
     * after the content changed.
     *
     * ⚠️ teachers_count needs DISTINCT and therefore cannot be a plain
     * withCount('teachers'): a teacher reachable through two subjects in the
     * same branch is one teacher, not two. Counting the pivot rows instead
     * of the people is the classic many-to-many miscount, so the select is
     * overridden with count(distinct …).
     *
     * Both counts are restricted to PUBLISHED subjects — the card is public,
     * and a draft subject is not something to advertise.
     */
    public function scopeWithCardCounts(Builder $query): Builder
    {
        return $query->withCount([
            'subjects as subjects_count' => fn (Builder $subjects) => $subjects->published(),

            'subjects as teachers_count' => fn (Builder $subjects) => $subjects
                ->published()
                ->join('subject_teacher', 'subject_teacher.subject_id', '=', 'subjects.id')
                ->join('teachers', 'teachers.id', '=', 'subject_teacher.teacher_id')
                ->whereNull('teachers.deleted_at')
                ->select(DB::raw('count(distinct subject_teacher.teacher_id)')),
        ]);
    }

    /**
     * Eager-loads everything a branch overview needs, in one place.
     *
     * 🔴 The nested load is the whole point: `subjects.teachers` and
     * `subjects.teachers.image` are what stop the teacher grid becoming one
     * query per subject and then one per portrait. Naming it here means a
     * screen cannot forget half of it — the failure mode is invisible in
     * development with three rows and quadratic in production.
     */
    public function scopeWithPublicContent(Builder $query): Builder
    {
        return $query->with(self::publicContentRelations());
    }

    /**
     * The one definition of what a branch overview needs loaded.
     *
     * ⚠️ Shared by the scope and by load() on an already-bound model: a route
     * that resolved the branch by slug would otherwise have to re-fetch it
     * just to apply the scope, paying an extra query for nothing.
     *
     * @return array<string, mixed>
     */
    public static function publicContentRelations(): array
    {
        return [
            'subjects' => fn ($subjects) => $subjects->published()
                ->withCount(['lectures', 'libraryFiles'])
                ->orderBy('display_order')
                ->orderBy('id'),

            'subjects.teachers' => fn ($teachers) => $teachers
                ->where('is_publicly_listed', true)
                ->withCount('lectures'),

            'subjects.teachers.image',
        ];
    }

    /**
     * The branch's teachers, each once.
     *
     * 🔴 One teacher can carry two subjects in the same branch. Flattening
     * the pivot without keying by id lists that person TWICE — the classic
     * many-to-many double count, visible here as a duplicate face in the
     * grid and a wrong number in the stats.
     *
     * ⚠️ Reads the LOADED relation and never queries: call withPublicContent()
     * first, or this returns an empty collection rather than silently firing
     * a query per subject.
     *
     * @return Collection<int, Teacher>
     */
    public function uniqueTeachers(): Collection
    {
        if (! $this->relationLoaded('subjects')) {
            return collect();
        }

        return $this->subjects
            ->flatMap(fn (Subject $subject) => $subject->relationLoaded('teachers')
                ? $subject->teachers->map(function (Teacher $teacher) use ($subject): Teacher {
                    // Which subject in THIS branch put them on the page.
                    $teacher->branch_subject_name ??= $subject->name;

                    return $teacher;
                })
                : collect())
            ->unique('id')
            ->values();
    }

    /** Display order used by every screen that lists branches. */
    public function scopeOrdered(Builder $query): Builder
    {
        return $query->orderBy('display_order')->orderBy('id');
    }
}

<?php

namespace App\Models;

use App\Support\CoveredIntervals;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * تقدّم مشاهدة طالب لمحاضرة — م-5.
 *
 * 🔴 completed_at يكتبه recordSegment() وحده، حين تبلغ الثواني المُغطّاة
 * عتبة المحاضرة (90% من المدّة). لا يوجد setter عام لـ«اكتمل».
 */
class LectureProgress extends Model
{
    /** جمع «progress» هو مفرده — يُعلَن صراحةً. */
    protected $table = 'lecture_progress';

    protected $fillable = [
        'student_id',
        'lecture_id',
        'covered_intervals',
        'watched_seconds',
        'duration_seconds',
        'completed_at',
        'last_heartbeat_at',
    ];

    protected function casts(): array
    {
        return [
            'covered_intervals' => 'array',
            'watched_seconds' => 'integer',
            'duration_seconds' => 'integer',
            'completed_at' => 'datetime',
            'last_heartbeat_at' => 'datetime',
        ];
    }

    public function student(): BelongsTo
    {
        return $this->belongsTo(Student::class);
    }

    public function lecture(): BelongsTo
    {
        return $this->belongsTo(Lecture::class);
    }

    public function isComplete(): bool
    {
        return $this->completed_at !== null;
    }

    /** نسبة المشاهدة 0–100 مقابل المدّة المخزَّنة. */
    public function percent(): int
    {
        if (! $this->duration_seconds) {
            return 0;
        }

        return (int) min(100, round($this->watched_seconds / $this->duration_seconds * 100));
    }

    /**
     * يدمج مقطعاً مُشاهَداً ويحفظ — نقطة الكتابة الوحيدة للتقدّم.
     *
     * 🔴 الاكتمال يُقاس بعد الدمج على الثواني الفريدة لا على الموضع الحالي:
     * القفز للنهاية لا يكفي. وبمجرّد ضبط completed_at لا يُنزَع — إعادة
     * المشاهدة لا تُلغي إكمالاً سابقاً.
     */
    public function recordSegment(int $from, int $to, Lecture $lecture): void
    {
        $duration = $lecture->video_duration_seconds ?: null;

        $covered = CoveredIntervals::fromStored($this->covered_intervals)
            ->add($from, $to, $duration);

        $this->covered_intervals = $covered->toArray();
        $this->watched_seconds = $covered->seconds();
        $this->duration_seconds = $duration;
        $this->last_heartbeat_at = now();

        $threshold = $lecture->completionThresholdSeconds();

        if ($this->completed_at === null && $threshold !== null && $this->watched_seconds >= $threshold) {
            $this->completed_at = now();
        }

        $this->save();
    }
}

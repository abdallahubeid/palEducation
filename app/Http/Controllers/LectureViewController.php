<?php

namespace App\Http\Controllers;

use App\Enums\LectureStatus;
use App\Models\Lecture;
use App\Models\User;
use Illuminate\Contracts\View\View;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Http\Request;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

/**
 * The in-platform lecture watch page.
 *
 * 🔴 The video is played INSIDE our layout — the teacher's preview and the
 * student's lesson are the same screen, so there is one place where playback,
 * progress and the quiz will eventually meet. Sending either audience out to
 * youtube.com would put the lesson on a page we do not control.
 *
 * ⚠️ Access is checked HERE, in the controller, because this project still
 * has no Policies and no global branch scope — the documented red flag in
 * CLAUDE.md. When those land, this method is one of the first places that
 * should defer to them rather than keep its own rules.
 */
class LectureViewController extends Controller
{
    public function show(Request $request, Lecture $lecture): View
    {
        $lecture->load(['subject.branch', 'teacher']);

        $this->assertVisibleTo($request->user(), $lecture);

        $siblings = $this->siblingsFor($request, $lecture);

        return view('pages.student.lecture', [
            'lecture' => $lecture,

            'siblings' => $siblings,

            // Whether to show the "you are previewing" framing.
            'isPreview' => $this->canManage($request->user(), $lecture),

            /*
             * The outline, grouped into units.
             *
             * ⚠️ Grouped from the SAME collection the flat list uses — not a
             * second query. Two queries over the same rows would let the
             * sidebar and the prev/next controls disagree about what exists.
             *
             * 🔴 A lecture with no unit is not dropped: `unit_name` is
             * nullable and every existing lecture has none, so an empty value
             * falls into a named default group rather than vanishing from the
             * navigation of its own subject.
             */
            'lecturesByUnit' => $siblings
                ->groupBy(fn (Lecture $item): string => filled($item->unit_name)
                    ? $item->unit_name
                    : __('student.lecture_default_unit')),

            /*
             * 🔴 The unit holding the lecture being watched — the accordion
             * opens on it and leaves the rest collapsed. Without this the
             * student lands on a wall of closed panels with no clue which one
             * they are inside.
             */
            'activeUnit' => filled($lecture->unit_name)
                ? $lecture->unit_name
                : __('student.lecture_default_unit'),
        ]);
    }

    /**
     * The real siblings of this lecture, in display order.
     *
     * ⚠️ Fetched once and reused for the outline, the unit grouping and the
     * previous/next controls. The page used to render six invented lectures
     * beside whichever one you opened — a navigation rail that led nowhere.
     *
     * @return Collection<int, Lecture>
     */
    private function siblingsFor(Request $request, Lecture $lecture)
    {
        return Lecture::query()
            ->where('subject_id', $lecture->subject_id)
            ->when(
                ! $this->canManage($request->user(), $lecture),
                // A student sees published lectures only; whoever manages the
                // subject sees drafts too, which is the point of a preview.
                fn ($query) => $query->where('status', LectureStatus::Published)
            )
            ->orderBy('display_order')
            ->orderBy('id')
            ->get(['id', 'title', 'slug', 'display_order', 'status', 'unit_name', 'video_duration_seconds']);
    }

    /**
     * 🔴 404, never 403. A lecture the viewer may not see is
     * indistinguishable from one that does not exist — "forbidden" confirms
     * the id is real and that content sits behind it.
     */
    private function assertVisibleTo(?User $user, Lecture $lecture): void
    {
        if ($this->canManage($user, $lecture)) {
            return;
        }

        /*
         * ⚠️ A draft is invisible to everyone except whoever manages the
         * subject — that is the whole reason the preview link exists.
         */
        if ($lecture->status !== LectureStatus::Published) {
            throw new NotFoundHttpException;
        }

        /*
         * 🔴 The platform's first rule: a student sees only their own branch.
         * Enforced by hand here for want of a global scope — and deliberately
         * a hard check, not a soft filter, because this is the screen that
         * actually serves the content.
         */
        if ($user?->isStudent()) {
            if ($user->userable?->branch_id !== $lecture->subject?->branch_id) {
                throw new NotFoundHttpException;
            }

            return;
        }

        /*
         * ⏳ A guest currently reaches published lectures. Subscriptions do
         * not exist yet (no `subscriptions` table), so there is nothing to
         * check against — this is the paywall's future home, and it is the
         * reason the YouTube-hosting decision matters.
         */
    }

    /** Whoever may manage the subject: its teachers, and any admin. */
    private function canManage(?User $user, Lecture $lecture): bool
    {
        if ($user === null) {
            return false;
        }

        if ($user->isAdmin()) {
            return true;
        }

        return $user->isTeacher()
            && $user->userable?->subjects()->whereKey($lecture->subject_id)->exists();
    }
}

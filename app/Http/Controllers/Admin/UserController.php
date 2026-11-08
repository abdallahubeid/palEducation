<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Admin;
use App\Models\Branch;
use App\Models\Student;
use App\Models\Subject;
use App\Models\Teacher;
use App\Models\User;
use App\Notifications\NewAdminRegisteredNotification;
use App\Notifications\SubjectAssignedToTeacherNotification;
use App\Notifications\SubjectUnassignedFromTeacherNotification;
use App\Support\AdminNotifier;
use App\Support\TeacherNotifier;
use Illuminate\Contracts\View\View;
use Illuminate\Database\Eloquent\Collection as EloquentCollection;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Password;

/**
 * حسابات المنصّة — طلبة ومعلّمون وأدمن.
 *
 * 🔴 الهوية هنا **متعدّدة الأشكال**: كل حساب يحلّ إلى سجلّ مجال واحد
 * (Student · Teacher · Admin) يحمل الاسم، و userable_id إجباري. لذلك
 * إنشاء مستخدم = إنشاء صفّين في معاملة واحدة، والنوع يُختار عند الإنشاء
 * **ولا يُغيَّر بعده**: تغييره يعني نقل الاسم والفرع والمحتوى بين جداول
 * مختلفة، وهو ترحيل بيانات لا تعديل حقل.
 */
class UserController extends Controller
{
    /** النوع ⇐ [التسمية، صنف سجلّ المجال]. */
    private const TYPES = [
        'student' => ['label' => 'طالب', 'model' => Student::class],
        'teacher' => ['label' => 'معلّم', 'model' => Teacher::class],
        'admin' => ['label' => 'أدمن', 'model' => Admin::class],
    ];

    public function index(Request $request): View
    {
        $type = array_key_exists($request->query('type'), self::TYPES)
            ? $request->query('type')
            : null;

        $users = User::query()
            // with('userable') لا حلقة على العلاقة: بلا التحميل المسبق يصير
            // كل صفّ استعلاماً منفصلاً على جدول مجاله.
            //
            // ⚠️ The morphWith block is what keeps the teacher columns from
            // becoming an N+1: a teacher row shows its branch and a COUNT of
            // its published lectures, and both would otherwise be one query
            // per row. Loaded only for Teacher — students and admins have
            // neither.
            ->with(['userable' => fn ($morph) => $morph->morphWith([
                Teacher::class => [
                    'branch',
                    'subjects.branch',
                ],
            ]), 'roles'])
            ->when($type, fn ($q) => $q->where('userable_type', self::TYPES[$type]['model']))
            ->latest()
            ->paginate(config('app.paginate_page'))
            ->withQueryString();

        /*
         * 🔴 The lecture figure is COUNTED, never stored — the same rule the
         * public roster follows, so the admin table and the card can never
         * disagree.
         *
         * ⚠️ loadCount on the collected teachers is ONE query for the whole
         * page. morphWith cannot express a count, and reaching for it from
         * the Blade template would be a query per row.
         *
         * Published only: a draft is not a lecture anyone can watch, and the
         * card advertises the same number.
         */
        $users->getCollection()
            ->pluck('userable')
            ->filter(fn ($domain): bool => $domain instanceof Teacher)
            ->pipe(fn ($teachers) => new EloquentCollection($teachers))
            ->loadCount(['lectures as published_lectures_count' => fn ($query) => $query->published()]);

        return view('admin.users.index', [
            'users' => $users,
            'types' => self::TYPES,
            'activeType' => $type,
            'counts' => collect(self::TYPES)->map(
                fn (array $t): int => User::query()->where('userable_type', $t['model'])->count()
            )->all(),
        ]);
    }

    public function create(): View
    {
        return view('admin.users.create', [
            'types' => self::TYPES,
            'branches' => Branch::query()->orderBy('display_order')->pluck('name', 'id'),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'type' => ['required', Rule::in(array_keys(self::TYPES))],
            'full_name' => ['required', 'string', 'max:160'],
            'email' => ['required', 'email', 'max:190', Rule::unique('users', 'email')],
            'password' => ['required', 'confirmed', Password::defaults()],
            /*
             * 🔴 Mandatory for a student, OPTIONAL for a teacher.
             * students.branch_id is a NOT NULL column with a foreign key —
             * a student without a branch cannot be shown any content at all.
             * teachers.branch_id is nullable: it labels the public card, and
             * a teacher with none falls back to the branch of the subject
             * they teach.
             */
            'branch_id' => ['required_if:type,student', 'nullable', 'exists:branches,id'],
            // Teacher-only profile fields, ignored for the other two types.
            'title' => ['nullable', 'string', 'max:120'],
            'is_publicly_listed' => ['sometimes', 'boolean'],
            'is_active' => ['sometimes', 'boolean'],
        ]);

        $user = DB::transaction(function () use ($validated, $request): User {
            $model = self::TYPES[$validated['type']]['model'];

            // سجلّ المجال أولاً ثم الحساب — لأن userable_id إجباري.
            $domain = $model::create(
                self::domainAttributes($validated['type'], $validated, $request),
            );

            return $domain->user()->create([
                'email' => $validated['email'],
                'password' => Hash::make($validated['password']),
                'email_verified_at' => now(),   // ينشئه أدمن، فلا توثيق مطلوب
                'is_active' => $request->boolean('is_active', true),
                'locale' => app()->getLocale(),
                // الدور يُسنَد داخل نفس المعاملة: حساب بلا دور لا يشتكي —
                // يعمل ثم يسقط عند أول فحص صلاحية. (الثابت 4)
            ])->syncRoleToShape();
        });

        /*
         * 🔴 الحسابات الموظَّفة فقط. تسجيل طالب حدث روتيني، وإشعار لكل
         * تسجيل يدفن كل ما عداه في الجرس خلال يوم من الإطلاق.
         */
        if ($user->isAdmin() || $user->isTeacher()) {
            AdminNotifier::send(
                new NewAdminRegisteredNotification($user, $user->isAdmin() ? 'أدمن' : 'معلّم'),
                except: $request->user(),
            );
        }

        flash()->success('تم إنشاء الحساب / الإضافة بنجاح.');

        return redirect()->route('admin.users.index');
    }

    public function edit(User $user): View
    {
        return view('admin.users.edit', [
            'user' => $user->load('userable'),
            'types' => self::TYPES,
            'branches' => Branch::query()->orderBy('display_order')->pluck('name', 'id'),

            /*
             * ⚠️ Loaded only for a teacher. Building the subject picker for a
             * student or an admin would be two wasted queries on every edit
             * of an account that can never use it.
             */
            'subjectsByBranch' => $user->isTeacher()
                ? Subject::query()->published()->with('branch')->ordered()->get()->groupBy('branch.name')
                : collect(),

            'assignedSubjectIds' => $user->isTeacher()
                ? $user->userable->subjects()->pluck('subjects.id')->all()
                : [],
        ]);
    }

    public function update(Request $request, User $user): RedirectResponse
    {
        $validated = $request->validate([
            'full_name' => ['required', 'string', 'max:160'],
            'email' => ['required', 'email', 'max:190', Rule::unique('users', 'email')->ignore($user->id)],
            // كلمة المرور اختيارية: تركها فارغة يعني «أبقِ الحالية».
            'password' => ['nullable', 'confirmed', Password::defaults()],
            /*
             * ⚠️ NOT required_if:type,student here. `type` is not submitted
             * on edit — the shape is immutable after creation — so that rule
             * would never fire and a student could be saved with no branch,
             * which their content access depends on. The record itself is
             * the condition.
             */
            'branch_id' => [$user->isStudent() ? 'required' : 'nullable', 'nullable', 'exists:branches,id'],

            /*
             * 🔴 المواد المسندة — لمعلّم فقط. كل مُعرِّف يُتحقَّق منه: النموذج
             * يرسل قائمة خانات، أي قائمة أعداد يمكن تحريرها، وإسناد مُعرِّف
             * عشوائي كان سيربط معلّماً بمادة فرع آخر — أي كسر مسار الوصول
             * الوحيد (subjects.branch_id) من حيث لا يظهر.
             */
            'subject_ids' => ['nullable', 'array'],
            'subject_ids.*' => [Rule::exists('subjects', 'id')->whereNull('deleted_at')],
            'title' => ['nullable', 'string', 'max:120'],
            'is_publicly_listed' => ['sometimes', 'boolean'],
            'is_active' => ['sometimes', 'boolean'],
        ]);

        DB::transaction(function () use ($validated, $request, $user) {
            $user->userable->update(
                self::domainAttributes($user->roleName(), $validated, $request),
            );

            $payload = [
                'email' => $validated['email'],
                'is_active' => $request->boolean('is_active'),
            ];

            if (filled($validated['password'] ?? null)) {
                $payload['password'] = Hash::make($validated['password']);
            }

            $user->update($payload);

            if ($user->isTeacher()) {
                $this->syncAssignedSubjects($user->userable, $validated['subject_ids'] ?? [], $request);
            }
        });

        flash()->success('تم تحديث البيانات بنجاح.');

        return redirect()->route('admin.users.index');
    }

    /** تبديل التفعيل — الحظر ليس حذفاً، وهما عمودان منفصلان. */
    public function toggle(User $user): RedirectResponse
    {
        if ($this->isSelf($user)) {
            flash()->error('لا يمكنك تعطيل حسابك الحالي.');

            return back();
        }

        $user->update(['is_active' => ! $user->is_active]);

        flash()->info('تم تغيير الحالة بنجاح.');

        return back();
    }

    public function destroy(User $user): RedirectResponse
    {
        if ($this->isSelf($user)) {
            flash()->error('لا يمكنك حذف حسابك الحالي.');

            return back();
        }

        if ($user->isAdmin() && $this->activeAdminCount() <= 1) {
            flash()->error('لا يمكن حذف آخر حساب أدمن.');

            return back();
        }

        // 🔴 حذف ناعم للاثنين معاً: حساب حيّ يشير إلى سجلّ مجال محذوف يعني
        // حساباً يستطيع الدخول إلى فراغ. المعاملة تُبقي الزوج متّسقاً.
        DB::transaction(function () use ($user) {
            $user->userable?->delete();
            $user->delete();
        });

        flash()->warning('تم حذف العنصر بنجاح.');

        return redirect()->route('admin.users.index');
    }

    /**
     * The domain-record columns for one account type.
     *
     * 🔴 One place decides which fields belong to which shape, so create and
     * edit cannot drift apart. The previous version used array_filter to
     * drop nulls, which silently made "clear this field" impossible: an
     * admin unticking public listing sent 0, and a null branch could never
     * be written back.
     *
     * @param  array<string, mixed>  $validated
     * @return array<string, mixed>
     */
    private static function domainAttributes(string $type, array $validated, Request $request): array
    {
        $attributes = ['full_name' => $validated['full_name']];

        if ($type === 'student') {
            $attributes['branch_id'] = $validated['branch_id'];

            return $attributes;
        }

        if ($type === 'teacher') {
            // Nullable on purpose: "no branch set" is a real state, and the
            // card falls back to the branch of the subject they teach.
            $attributes['branch_id'] = $validated['branch_id'] ?? null;
            $attributes['title'] = $validated['title'] ?? null;
            // boolean() reads an unchecked box as false rather than absent.
            $attributes['is_publicly_listed'] = $request->boolean('is_publicly_listed');
        }

        // Admins carry a name and nothing else.
        return $attributes;
    }

    /**
     * Syncs a teacher's assigned subjects.
     *
     * ⚠️ Existing rows keep their `assigned_at` and `assigned_by`. sync() with
     * a flat attribute array would rewrite both on every save, so a teacher
     * assigned last term would look newly assigned after an unrelated edit to
     * their phone number — and the pivot is the only record of when the
     * assignment actually happened.
     *
     * 🔴 An EMPTY array unassigns everything, deliberately. Reading it as
     * "leave as is" would make the checkboxes one-way: assignable, never
     * removable.
     *
     * @param  array<int, int|string>  $subjectIds
     */
    private function syncAssignedSubjects(Teacher $teacher, array $subjectIds, Request $request): void
    {
        $ids = array_map('intval', $subjectIds);
        $existing = $teacher->subjects()->pluck('subjects.id')->all();

        $payload = [];

        foreach ($ids as $id) {
            $payload[$id] = in_array($id, $existing, true)
                ? []
                : ['assigned_at' => now(), 'assigned_by' => $request->user()?->id];
        }

        $changes = $teacher->subjects()->sync($payload);

        $this->notifyAssignmentChanges($teacher, $changes['attached'], $changes['detached']);
    }

    /**
     * Tells the teacher which subjects came and went.
     *
     * 🔴 One notification per subject, not one "your assignments changed": the
     * teacher needs to know WHICH subject to go look at, and a lumped message
     * makes that a guess.
     *
     * ⚠️ Names are read here, before the notification is queued, so a subject
     * renamed or deleted later does not rewrite or blank the row.
     *
     * @param  array<int, int|string>  $attached
     * @param  array<int, int|string>  $detached
     */
    private function notifyAssignmentChanges(Teacher $teacher, array $attached, array $detached): void
    {
        $touched = [...$attached, ...$detached];

        if ($touched === []) {
            return;
        }

        $names = Subject::whereIn('id', $touched)->pluck('name', 'id');

        foreach ($attached as $id) {
            TeacherNotifier::notify($teacher, new SubjectAssignedToTeacherNotification($names[$id] ?? 'مادة'));
        }

        foreach ($detached as $id) {
            TeacherNotifier::notify($teacher, new SubjectUnassignedFromTeacherNotification($names[$id] ?? 'مادة'));
        }
    }

    private function isSelf(User $user): bool
    {
        return $user->is(auth()->user());
    }

    private function activeAdminCount(): int
    {
        return User::query()->where('userable_type', Admin::class)->count();
    }
}

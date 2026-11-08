@php
    /*
     * نموذج المستخدم — عمودي بعرض كامل، حاوية واحدة.
     *
     * المتغيّرات المتوقّعة: $user (قد يكون جديداً) · $types · $branches
     *                      · $action · $method
     *
     * 🔴 النوع يُختار عند الإنشاء **ولا يُغيَّر بعده**: تغييره يعني نقل الاسم
     * والفرع والمحتوى بين جداول مجال مختلفة — ترحيل بيانات لا تعديل حقل.
     */
    $inputClass = 'w-full rounded-xl border border-hairline bg-canvas px-3 py-2.5 text-ui text-ink
                   transition placeholder:text-muted focus:border-accent focus:outline-none
                   focus:ring-2 focus:ring-accent/25';
    $labelClass = 'mb-1.5 block text-caption font-medium text-steel';
    $hintClass = 'mt-1.5 text-micro text-stone';
    $isNew = ! $user->exists;
    $currentType = $isNew ? old('type', 'student') : $user->roleName();
@endphp

<form method="POST" action="{{ $action }}" class="mt-6">
    @csrf
    @if ($method !== 'POST')
        @method($method)
    @endif

    <div class="space-y-6 rounded-xl border border-hairline bg-canvas p-5 sm:p-6 lg:p-8"
         x-data="{ type: '{{ $currentType }}' }">

        {{-- ═══ نوع الحساب ═══ --}}
        <div class="space-y-4">
            <h3 class="text-ui font-bold text-ink">نوع الحساب</h3>

            @if ($isNew)
                <div class="grid grid-cols-1 gap-3 sm:grid-cols-3">
                    @foreach ($types as $key => $type)
                        <label class="flex cursor-pointer items-center gap-2.5 rounded-xl border px-4 py-3
                                      text-ui transition"
                               :class="type === '{{ $key }}'
                                   ? 'border-accent bg-accent/8 text-accent-deep'
                                   : 'border-hairline text-steel hover:border-hairline-strong'">
                            <input type="radio" name="type" value="{{ $key }}" x-model="type"
                                   class="size-4 shrink-0 border-hairline-strong text-accent focus:ring-accent/40">
                            {{ $type['label'] }}
                        </label>
                    @endforeach
                </div>
            @else
                <div class="flex items-center gap-2.5">
                    <x-admin.status-badge status="neutral" :label="$types[$currentType]['label'] ?? '—'" />
                    <p class="text-micro text-stone">
                        النوع ثابت بعد الإنشاء — تغييره ينقل البيانات بين جداول مختلفة.
                    </p>
                </div>
            @endif
        </div>

        <hr class="border-hairline">

        {{-- ═══ البيانات ═══ --}}
        <div class="space-y-5">
            <h3 class="text-ui font-bold text-ink">البيانات</h3>

            <div>
                <label for="full_name" class="{{ $labelClass }}">
                    الاسم الكامل <span class="text-error-deep">*</span>
                </label>
                {{-- الاسم يعيش على سجلّ المجال لا على users — لا عمود name هناك. --}}
                <input type="text" id="full_name" name="full_name" required
                       value="{{ old('full_name', $user->userable?->full_name) }}"
                       class="{{ $inputClass }}">
            </div>

            <div>
                <label for="email" class="{{ $labelClass }}">
                    البريد الإلكتروني <span class="text-error-deep">*</span>
                </label>
                <input type="email" id="email" name="email" dir="ltr" required
                       value="{{ old('email', $user->email) }}" class="{{ $inputClass }}">
                <p class="{{ $hintClass }}">هو معرّف الدخول.</p>
            </div>

            {{-- 🔴 Branch for students AND teachers, but they mean different
                 things. students.branch_id is NOT NULL and gates every piece
                 of content they can see. teachers.branch_id is nullable and
                 only labels the public card — a teacher left blank falls
                 back to the branch of the subject they teach. --}}
            <div x-show="type === 'student' || type === 'teacher'" x-cloak>
                <label for="branch_id" class="{{ $labelClass }}">
                    الفرع
                    <span class="text-error-deep" x-show="type === 'student'" x-cloak>*</span>
                </label>
                <select id="branch_id" name="branch_id" class="{{ $inputClass }}">
                    <option value="">— اختر الفرع —</option>
                    @foreach ($branches as $id => $name)
                        <option value="{{ $id }}"
                            @selected(old('branch_id', $user->userable?->branch_id) == $id)>{{ $name }}</option>
                    @endforeach
                </select>
                <p class="{{ $hintClass }}" x-show="type === 'teacher'" x-cloak>
                    اختياري — يُعرَض على بطاقة المعلّم. تركه فارغاً يجعلها تتبع فرع المادة التي يدرّسها.
                </p>
            </div>
        </div>

        {{-- ═══ Public profile — teachers only ═══ --}}
        <div x-show="type === 'teacher'" x-cloak>
            <hr class="mb-6 border-hairline">

            <div class="space-y-5">
                <div>
                    <h3 class="text-ui font-bold text-ink">الملف العام</h3>
                    <p class="{{ $hintClass }}">ما يظهر عن المعلّم في قسم المعلّمين على الصفحة الرئيسية.</p>
                </div>

                <div>
                    <label for="title" class="{{ $labelClass }}">المسمّى المعروض</label>
                    <input type="text" id="title" name="title" maxlength="120"
                           placeholder="الرياضيات"
                           value="{{ old('title', $user->userable?->title) }}"
                           class="{{ $inputClass }}">
                    <p class="{{ $hintClass }}">السطر تحت الاسم. تركه فارغاً يعرض اسم المادة المسندة.</p>
                </div>

                @if ($user->exists && $user->userable instanceof \App\Models\Teacher)
                    {{-- 🔴 Read-only, and deliberately so: the lecture figure
                         is COUNTED from published lectures, never typed. A
                         field here would be a second number that disagrees
                         with the page the moment anything is published. --}}
                    <div class="flex items-center gap-2.5 rounded-xl border border-hairline bg-surface/40 px-3 py-2.5">
                        <x-icon name="play" class="size-4 shrink-0 text-stone" />
                        <p class="text-caption text-steel">
                            <span class="num font-semibold text-ink">{{ $user->userable->lectures()->published()->count() }}</span>
                            محاضرة منشورة — محسوبة تلقائياً، لا تُحرَّر.
                        </p>
                    </div>
                @endif

                <label class="flex cursor-pointer items-center gap-2.5 rounded-xl border border-hairline
                              bg-surface/40 px-3 py-2.5 text-ui text-ink transition hover:bg-surface">
                    {{-- 🔴 Hidden 0 before the switch: an unchecked box is not
                         submitted at all, so without it a teacher could never
                         be taken OFF the public roster. --}}
                    <input type="hidden" name="is_publicly_listed" value="0">
                    <input type="checkbox" name="is_publicly_listed" value="1"
                           @checked(old('is_publicly_listed', $user->userable?->is_publicly_listed ?? false))
                           class="size-4 shrink-0 rounded border-hairline-strong text-accent focus:ring-accent/40">
                    يظهر في قسم المعلّمين على الصفحة الرئيسية
                </label>
            </div>

            {{--
                ═══ المواد المسندة ═══

                🔴 يظهر عند التعديل فقط. الإسناد صفوف في subject_teacher تحتاج
                مُعرِّف معلّم، وهو غير موجود قبل الإنشاء — فنموذج الإنشاء كان
                سيعرض خانات لا تُحفظ.

                ⚠️ مجموعة بالفرع عمداً: نفس اسم المادة يتكرّر بين الفروع
                («اللغة العربية» في كل فرع مادة مستقلّة)، وقائمة مسطّحة كانت
                ستجعل الأدمن يسند مادة فرع آخر بلا أن يلاحظ.
            --}}
            @if (! $isNew && isset($subjectsByBranch))
                <div>
                    <h3 class="text-ui font-bold text-ink">المواد المسندة</h3>
                    <p class="{{ $hintClass }}">
                        المواد المؤشَّرة هي ما يستطيع هذا المعلّم إدارة محتواه.
                    </p>

                    @if ($subjectsByBranch->isEmpty())
                        <p class="mt-3 rounded-xl border border-dashed border-hairline-strong px-4 py-6
                                  text-center text-micro text-stone">
                            لا مواد منشورة بعد — أنشئ مادة أولاً من شاشة المواد الدراسية.
                        </p>
                    @else
                        {{-- 🔴 قيمة 0 مخفية قبل المجموعة: إلغاء كل التأشيرات
                             لا يرسل الحقل إطلاقاً، وبدونها يصير الإسناد
                             باتجاه واحد — يُضاف ولا يُنزع أبداً. --}}
                        <input type="hidden" name="subject_ids" value="">

                        <div class="mt-3 max-h-72 space-y-4 overflow-y-auto rounded-xl border border-hairline p-4">
                            @foreach ($subjectsByBranch as $branchName => $branchSubjects)
                                <div>
                                    <p class="text-micro font-semibold text-stone">{{ $branchName ?: 'بلا فرع' }}</p>

                                    <div class="mt-2 grid gap-1.5 sm:grid-cols-2">
                                        @foreach ($branchSubjects as $subject)
                                            <label class="flex cursor-pointer items-center gap-2.5 rounded-lg px-2 py-1.5
                                                          transition hover:bg-surface has-checked:bg-accent/8">
                                                <input type="checkbox" name="subject_ids[]" value="{{ $subject->id }}"
                                                       @checked(in_array($subject->id, array_map('intval', (array) old('subject_ids', $assignedSubjectIds ?? [])), true))
                                                       class="size-4 shrink-0 rounded border-hairline-strong
                                                              text-accent focus:ring-accent/40">
                                                <span class="min-w-0 truncate text-caption text-ink">{{ $subject->name }}</span>
                                            </label>
                                        @endforeach
                                    </div>
                                </div>
                            @endforeach
                        </div>
                    @endif

                    @error('subject_ids')<p class="mt-1.5 text-micro text-error-deep">{{ $message }}</p>@enderror
                    @error('subject_ids.*')<p class="mt-1.5 text-micro text-error-deep">{{ $message }}</p>@enderror
                </div>
            @endif
        </div>

        <hr class="border-hairline">

        {{-- ═══ الدخول ═══ --}}
        <div class="space-y-5">
            <div>
                <h3 class="text-ui font-bold text-ink">كلمة المرور</h3>
                @unless ($isNew)
                    <p class="{{ $hintClass }}">اتركها فارغة للإبقاء على الحالية.</p>
                @endunless
            </div>

            <div class="grid grid-cols-1 gap-5 sm:grid-cols-2">
                <div>
                    <label for="password" class="{{ $labelClass }}">
                        كلمة المرور @if ($isNew)<span class="text-error-deep">*</span>@endif
                    </label>
                    <input type="password" id="password" name="password" autocomplete="new-password"
                           @required($isNew) class="{{ $inputClass }}">
                </div>
                <div>
                    <label for="password_confirmation" class="{{ $labelClass }}">تأكيد كلمة المرور</label>
                    <input type="password" id="password_confirmation" name="password_confirmation"
                           autocomplete="new-password" @required($isNew) class="{{ $inputClass }}">
                </div>
            </div>

            <label class="flex cursor-pointer items-center gap-2.5 rounded-xl border border-hairline
                          bg-surface/40 px-3 py-2.5 text-ui text-ink transition hover:bg-surface">
                {{-- 🔴 خانة مخفية بقيمة 0 قبل المفتاح: المتصفّح لا يرسل خانة
                     اختيار غير مؤشّرة إطلاقاً، فبدونها يستحيل تعطيل الحساب. --}}
                <input type="hidden" name="is_active" value="0">
                <input type="checkbox" name="is_active" value="1"
                       @checked(old('is_active', $user->is_active ?? true))
                       class="size-4 shrink-0 rounded border-hairline-strong text-accent focus:ring-accent/40">
                الحساب مفعّل
            </label>
        </div>
    </div>

    <div class="mt-6 flex items-center justify-end gap-3">
        <x-admin.btn :href="route('admin.users.index')" variant="ghost">إلغاء</x-admin.btn>
        <x-admin.btn variant="primary">حفظ</x-admin.btn>
    </div>
</form>

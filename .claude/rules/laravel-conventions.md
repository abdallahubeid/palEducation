# قواعد Laravel — palEducation

---

## 1. نموذج المجال

```
Branch (فرع)
 ├── Subject (مادة) ──── Teacher (معلم)   [إسناد]
 │    ├── Lecture (محاضرة)
 │    │    └── Quiz (1:1)
 │    │         └── Question
 │    │              └── Option
 │    └── LibraryFile (ملف مكتبة)
 └── Student (طالب)  [الطالب ينتمي لفرع واحد]

Subscription (اشتراك) ── Student
QuizAttempt (محاولة)  ── Student × Quiz
News (خبر)
```

**علاقات حاكمة:**
- `Student → Branch` : belongsTo (واحد فقط، شبه دائم)
- `Subject → Branch` : belongsTo
- `Subject ↔ Teacher` : معلم واحد لكل مادة مبدئياً — صمّم الجدول ليحتمل many-to-many لاحقاً (قرار م-10)
- `Lecture → Quiz` : hasOne **إجباري** (قرار م-3)

---

## 2. الأدوار والصلاحيات

### التمثيل
عمود `role` في `users` بقيم enum: `student` · `teacher` · `admin`.
لا تستخدم حزمة صلاحيات ثقيلة — ثلاثة أدوار ثابتة لا تبرّرها.

```php
enum UserRole: string {
    case Student = 'student';
    case Teacher = 'teacher';
    case Admin   = 'admin';
}
```

### قواعد النطاق — الأهم في المشروع

```php
// الطالب: فرعه ∩ اشتراك ساري
// المعلم: المواد المسندة له فقط
// الأدمن: غير مقيّد
```

**التنفيذ عبر Policies — لا عبر إخفاء الروابط:**

```php
class LecturePolicy
{
    public function view(User $user, Lecture $lecture): bool
    {
        return match ($user->role) {
            UserRole::Admin   => true,
            UserRole::Teacher => $lecture->subject->teacher_id === $user->id,
            UserRole::Student => $lecture->subject->branch_id === $user->branch_id
                                 && $user->hasActiveSubscription(),
        };
    }
}
```

### Global Scopes للطالب
طبّق فلتر الفرع على مستوى الاستعلام لا الواجهة:

```php
// app/Models/Scopes/BranchScope.php
public function apply(Builder $builder, Model $model): void
{
    if (auth()->check() && auth()->user()->role === UserRole::Student) {
        $builder->where('branch_id', auth()->user()->branch_id);
    }
}
```

> ⚠️ **قاعدة أمنية:** أي استعلام يعرض محتوى للطالب دون تطبيق فلتر الفرع والاشتراك = ثغرة. الفلترة في الـ Query، والتحقق في الـ Policy، والإخفاء في الـ View — **الثلاثة معاً**.

---

## 3. Soft Deletes — سلة المحذوفات

سلة المحذوفات ركن أساسي في المشروع (شاشتان مخصّصتان).

```php
use Illuminate\Database\Eloquent\SoftDeletes;

class Lecture extends Model {
    use SoftDeletes;
}
```

**تُطبَّق على:** `Lecture` · `LibraryFile` · `News` · `User` · `Subject` · `Branch` · `Quiz`

**قواعد:**
- المعلم يرى ويسترجع **محتواه فقط**
- المعلم **لا يملك حذفاً نهائياً** (قرار م-9) — الأدمن حصراً
- حذف تلقائي نهائي بعد **30 يوماً** عبر مهمة مجدولة
- كل حذف يمرّ عبر `ConfirmDialog` في الواجهة
- سجّل `deleted_by` لتتبّع من حذف

---

## 4. حماية الملفات والفيديو

> 🔴 **الملفات لا تُخدَّم من `public/` أبداً.** المحتوى مدفوع؛ رابط مباشر يعني تسريباً مجانياً.

```php
// التخزين
Storage::disk('private')->put("lectures/{$id}/video.mp4", $file);

// الخدمة عبر controller يفحص الصلاحية
public function stream(Lecture $lecture)
{
    $this->authorize('view', $lecture);   // فرع + اشتراك
    return response()->file(
        Storage::disk('private')->path($lecture->video_path)
    );
}
```

**لملفات المكتبة:** روابط موقّتة موقّعة (`temporarySignedRoute`) بصلاحية قصيرة.
**للفيديو:** القرار م-5 معلّق — لو اعتُمد مزوّد خارجي (Bunny/Vimeo)، استخدم توقيع روابط المزوّد بدل خدمتها من السيرفر.

---

## 5. الاصطلاحات

| العنصر | الاصطلاح | مثال |
|---|---|---|
| Model | مفرد PascalCase | `Lecture` |
| الجدول | جمع snake_case | `lectures` |
| Controller | مفرد + Controller | `LectureController` |
| Livewire | PascalCase وصفي | `Student\QuizRunner` |
| Migration | وصفي | `create_lectures_table` |
| Route name | نقطي بالنطاق | `student.subjects.show` |
| Policy | Model + Policy | `LecturePolicy` |
| Enum | مفرد PascalCase | `UserRole` |
| مفاتيح الترجمة | snake_case إنجليزي | `student.my_subjects` |

**أسماء الأعمدة إنجليزية دائماً** حتى لو كان المحتوى عربياً.

---

## 6. المسارات

```php
// routes/web.php — مجموعات واضحة بالنطاق
Route::middleware(['auth', 'role:student'])
    ->prefix('student')->name('student.')
    ->group(function () {
        Route::get('/dashboard', Dashboard::class)->name('dashboard');
        Route::get('/subjects', MySubjects::class)->name('subjects.index');
    });
```

- كل منطقة لها `prefix` و`name` و`middleware` خاص
- Middleware للأدوار: `role:student` · `role:teacher` · `role:admin`
- Middleware للاشتراك: `subscription.active` → يعيد التوجيه لشاشة 402

---

## 7. الاختبار (Pest 5)

```php
it('منع طالب من مشاهدة محاضرة خارج فرعه', function () {
    $student = User::factory()->student()->create(['branch_id' => 1]);
    $lecture = Lecture::factory()->forBranch(2)->create();

    actingAs($student)->get(route('student.lectures.show', $lecture))
        ->assertForbidden();
});
```

**اختبارات إلزامية لكل ميزة تمسّ الصلاحيات:**
- طالب لا يصل لمحتوى خارج فرعه → 403
- طالب باشتراك منتهٍ لا يصل للمحتوى → 402
- معلم لا يصل لمواد زميله → 403
- معلم لا يحذف محتوى غيره → 403

> هذه الأربعة هي **الحد الأدنى**. أي PR يمسّ المحتوى بلا هذه الاختبارات ناقص.

---

## 8. الجودة

```bash
php artisan pint
```
```bash
php artisan test
```

- `pint` قبل اعتبار أي شغل منتهياً
- لا `dd()` أو `dump()` متروكة في الكود
- لا أسرار في الكود — كلها في `.env`
- `.env` **لا يُرفع** لأي مستودع

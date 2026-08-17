# قواعد Blade و Livewire 3 — palEducation

---

## 1. متى Livewire ومتى Blade عادي

القاعدة: **Blade هو الافتراضي. Livewire استثناء يُبرَّر.**

| استخدم | الحالة | أمثلة من المشروع |
|---|---|---|
| **Blade ساكن** | عرض بلا تفاعل | الصفحة الرئيسية · صفحة الفرع · تفاصيل الخبر · 403/404 |
| **Blade + Alpine** | تفاعل بصري بحت، بلا سيرفر | قائمة منسدلة · مودال · تبويبات · درج الجوال |
| **Livewire** | حالة تحتاج السيرفر | الكويز · بناء الكويز · رفع الفيديو · الجداول بفلترة · البحث |

> **لا تجعل كل شيء Livewire.** كل مكوّن Livewire = طلب شبكة عند كل تفاعل. الصفحة الساكنة تبقى ساكنة.

---

## 2. أين يعيش كل شيء

```
resources/views/
├── components/              ← Blade Components (بلا حالة)
│   ├── ui/                  ← الأساسيات: button, input, badge, card...
│   ├── layout/              ← sidebar, topbar, page-header
│   └── domain/              ← branch-card, subject-card, lecture-item, file-row
├── layouts/
│   ├── public.blade.php     ← PublicShell
│   ├── student.blade.php    ← StudentShell
│   ├── teacher.blade.php    ← TeacherShell
│   └── admin.blade.php      ← AdminShell
├── livewire/                ← مكوّنات Livewire (بحالة)
│   ├── student/
│   ├── teacher/
│   └── admin/
└── pages/                   ← الصفحات
```

---

## 3. Blade Components

### الصيغة القياسية
```blade
{{-- resources/views/components/ui/button.blade.php --}}
@props([
    'variant' => 'primary',   // primary | secondary | ghost | danger
    'size'    => 'md',        // sm | md | lg
    'icon'    => null,
])

@php
$variants = [
    'primary'   => 'bg-primary text-on-primary hover:bg-primary-hover',
    'secondary' => 'bg-surface-1 text-ink border border-hairline-strong hover:bg-surface-2',
    'ghost'     => 'text-ink-muted hover:bg-surface-2',
    'danger'    => 'bg-danger text-white hover:brightness-95',
];
$sizes = ['sm' => 'h-8 px-3 text-sm', 'md' => 'h-10 px-4', 'lg' => 'h-12 px-6'];
@endphp

<button {{ $attributes->merge([
    'class' => "inline-flex items-center justify-center gap-2 rounded-md font-semibold
                transition focus-visible:outline-none focus-visible:ring-3
                focus-visible:ring-primary/20 disabled:opacity-50
                {$variants[$variant]} {$sizes[$size]}"
]) }}>
    @if($icon)<x-icon :name="$icon" class="size-4" />@endif
    {{ $slot }}
</button>
```

### قواعد
- **دائماً `$attributes->merge()`** — يسمح بتمرير أصناف إضافية من الاستدعاء
- **`@props` مع قيم افتراضية** لكل خيار
- **لا قيم hex** — استخدم أسماء الـ tokens من `DESIGN.md`
- **لا أصناف فيزيائية** — راجع `rtl-bilingual.md`
- اسم المكوّن يطابق اسمه في جرد الـ38 مكوّناً

---

## 4. Livewire 3

### البنية
```php
namespace App\Livewire\Student;

use Livewire\Component;
use Livewire\Attributes\{Layout, Title, Computed};

#[Layout('layouts.student')]
#[Title('الكويز')]
class QuizRunner extends Component
{
    public Lecture $lecture;
    public array $answers = [];
    public int $currentIndex = 0;

    #[Computed]
    public function questions()
    {
        return $this->lecture->quiz->questions;
    }

    public function render()
    {
        return view('livewire.student.quiz-runner');
    }
}
```

### قواعد إلزامية
1. **التحقق من الصلاحية داخل المكوّن** — لا تعتمد على إخفاء الرابط:
   ```php
   public function mount(Lecture $lecture) {
       $this->authorize('view', $lecture);   // Policy تفحص الفرع + الاشتراك
   }
   ```
2. **`wire:key` إلزامي** في كل حلقة `@foreach` داخل Livewire — بدونه يخرب الـ DOM diffing
3. **`wire:model.live` بحذر** — يرسل طلباً عند كل ضغطة مفتاح. استخدم `.blur` أو `.debounce.400ms`
4. **`wire:loading`** لكل إجراء يستغرق وقتاً — الطالب يجب أن يرى أن شيئاً يحدث
5. **لا تمرّر Eloquent Models ضخمة في `public`** — تُسلسَل مع كل طلب. مرّر الـ id واستعلم
6. **`#[Computed]`** للبيانات المشتقّة بدل حسابها في `render()`

### رفع الملفات (رفع المحاضرات)
```php
use Livewire\WithFileUploads;

class UploadLecture extends Component
{
    use WithFileUploads;
    public $video;

    protected function rules() {
        return ['video' => 'required|file|mimes:mp4,webm|max:2097152'];
    }
}
```
- شريط تقدّم عبر `wire:model` + `x-on:livewire-upload-progress`
- **احفظ مسودة تلقائياً** — رفع فيديو ثقيل + انقطاع نت = فقدان عمل المعلم
- الفيديو الكبير: فكّر برفع مباشر لمزوّد الاستضافة بدل المرور بالسيرفر (قرار م-5 معلّق)

---

## 5. Alpine.js

يأتي مضمّناً مع Livewire 3 — **لا تثبّته منفصلاً**.

```blade
<div x-data="{ open: false }">
    <button @click="open = !open" :aria-expanded="open">القائمة</button>
    <div x-show="open" x-collapse x-cloak>...</div>
</div>
```

- `x-cloak` إلزامي على أي عنصر مخفي ابتدائياً (يمنع الوميض)
- لا تستخدم Alpine لحالة يجب أن تصل للسيرفر
- احترم `prefers-reduced-motion` في أي `x-transition`

---

## 6. الأداء

- `@once` للسكربتات المتكرّرة داخل مكوّن
- Lazy loading لمكوّنات Livewire الثقيلة: `<livewire:teacher.student-performance lazy />`
- `wire:navigate` على روابط التنقّل الداخلي — انتقال شبه فوري بلا SPA
- انتبه لـ N+1: استخدم `with()` في كل استعلام قوائم (المحاضرات، الملفات، الطلاب)
- الصور: `loading="lazy"` عدا الصورة الأولى في الشاشة

---

## 7. قائمة فحص لأي مكوّن Livewire

- [ ] `authorize()` في `mount()`
- [ ] `wire:key` في كل `@foreach`
- [ ] `wire:loading` لكل إجراء بطيء
- [ ] لا `wire:model.live` بلا debounce على حقول نصية
- [ ] لا Models ضخمة في خصائص `public`
- [ ] `EmptyState` عند غياب البيانات
- [ ] اختُبر في RTL و LTR
- [ ] لا استعلام N+1

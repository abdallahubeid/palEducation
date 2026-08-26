@props([
    'status' => 'neutral',   // active | hidden | draft | danger | neutral
    'label' => null,
])

@php
    /*
     * شارة حالة بنقطة ملوّنة — منقولة عن admin/status-badge في لوحة veyra.
     *
     * 🔴 كل صنف مكتوب حرفياً كاملاً في هذه المصفوفة، لا مُجمَّعاً نصّياً من
     * متغيّر: فاحص Tailwind وقت البناء لا ينفّذ PHP، فـ"bg-{$tone}/10" لا
     * يولّد أي CSS. فخّ وقع مرّتين في هذا المشروع.
     *
     * النصوص بدرجة -deep: warn و error الأساسيان أسطح لا نصوص (3.14:1 و
     * 3.73:1 — يفشلان AA).
     */
    $map = [
        'active' => ['label' => 'ظاهرة', 'text' => 'text-accent-deep', 'bg' => 'bg-accent/10', 'dot' => 'bg-accent'],
        'hidden' => ['label' => 'مخفيّة', 'text' => 'text-stone', 'bg' => 'bg-surface', 'dot' => 'bg-stone'],
        'draft' => ['label' => 'مسودّة', 'text' => 'text-warn-deep', 'bg' => 'bg-warn/12', 'dot' => 'bg-warn'],
        'danger' => ['label' => 'محذوفة', 'text' => 'text-error-deep', 'bg' => 'bg-error/10', 'dot' => 'bg-error'],
        'neutral' => ['label' => '—', 'text' => 'text-stone', 'bg' => 'bg-surface', 'dot' => 'bg-stone'],
    ];

    $cfg = $map[$status] ?? $map['neutral'];
@endphp

<span {{ $attributes->class(['inline-flex items-center gap-1.5 rounded-md px-2.5 py-1 text-micro font-semibold', $cfg['text'], $cfg['bg']]) }}>
    <span class="size-1.5 shrink-0 rounded-full {{ $cfg['dot'] }}"></span>
    {{ $label ?? $cfg['label'] }}
</span>

@props([
    'title'  => null,
    'errors' => [],   // قائمة رسائل — تُعرض كقائمة تحت العنوان
])

{{--
    ملخّص أخطاء على مستوى النموذج — مبني على ui/alert لا مكوّن جديد.
    سببه: خطأ مثل «البريد أو كلمة المرور غير صحيحة» لا يُنسب لحقل واحد،
    وأمنياً لا يجوز الكشف عن أيّهما الخطأ.

    role="alert" لا status — قارئ الشاشة يجب أن يعلنه فور ظهوره.
--}}
<x-ui.alert variant="error" role="alert" {{ $attributes }}>
    @if ($title)
        <p class="font-semibold">{{ $title }}</p>
    @endif

    @if (! empty($errors))
        <ul @class(['space-y-0.5 text-caption', 'mt-1' => $title])>
            @foreach ($errors as $message)
                <li class="flex gap-2">
                    <span aria-hidden="true">•</span>
                    <span>{{ $message }}</span>
                </li>
            @endforeach
        </ul>
    @endif

    {{ $slot }}
</x-ui.alert>

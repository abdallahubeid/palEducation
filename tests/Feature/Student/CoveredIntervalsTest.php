<?php

use App\Support\CoveredIntervals;

/*
| منطق دمج فترات المشاهدة — م-5.
|
| 🔴 الطالب الذي يعيد أول دقيقة عشر مرات لا يتقدّم؛ الذي يقفز للنهاية لا
| «يُكمل». هذا الملف يثبّت ذلك.
*/

it('يدمج الفترات المتداخلة إلى مجموعة غير متداخلة', function () {
    $covered = (new CoveredIntervals([[0, 30], [20, 50], [45, 60]]));

    expect($covered->toArray())->toBe([[0, 60]])
        ->and($covered->seconds())->toBe(60);
});

it('يبقي الفجوات كفجوات', function () {
    $covered = (new CoveredIntervals([[0, 20], [40, 60]]));

    expect($covered->toArray())->toBe([[0, 20], [40, 60]])
        ->and($covered->seconds())->toBe(40);
});

it('يدمج الفترات المتلاصقة', function () {
    expect((new CoveredIntervals([[0, 20], [20, 40]]))->toArray())->toBe([[0, 40]]);
});

it('إعادة مشاهدة نفس المقطع لا تزيد الثواني', function () {
    $covered = (new CoveredIntervals)
        ->add(0, 60)
        ->add(0, 60)
        ->add(10, 30);

    expect($covered->seconds())->toBe(60);
});

it('القفز للأمام يترك فجوة غير محتسَبة', function () {
    // شاهد أول دقيقة، ثم قفز إلى 5:00 وشاهد دقيقة.
    $covered = (new CoveredIntervals)->add(0, 60)->add(300, 360);

    expect($covered->seconds())->toBe(120)
        ->and($covered->toArray())->toBe([[0, 60], [300, 360]]);
});

it('يتجاهل المقاطع المقلوبة والسالبة والصفرية', function () {
    $covered = (new CoveredIntervals)
        ->add(50, 30)   // نهاية قبل بداية
        ->add(-10, 20)  // بداية سالبة → تُقصّ إلى 0
        ->add(40, 40);  // صفري

    expect($covered->toArray())->toBe([[0, 20]]);
});

it('يقصّ المقطع عند سقف المدّة', function () {
    $covered = (new CoveredIntervals)->add(0, 9999, ceiling: 120);

    expect($covered->seconds())->toBe(120);
});

it('يقرأ القيمة المخزَّنة أو null بأمان', function () {
    expect(CoveredIntervals::fromStored(null)->seconds())->toBe(0)
        ->and(CoveredIntervals::fromStored([[0, 15]])->seconds())->toBe(15)
        ->and(CoveredIntervals::fromStored('garbage')->seconds())->toBe(0);
});

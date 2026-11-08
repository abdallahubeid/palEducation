<?php

/*
|--------------------------------------------------------------------------
| Palestinian governorates
|--------------------------------------------------------------------------
| 🔴 Extracted from a @php block inside the registration view, where it was
| the only copy. A second surface now needs it — the testimonial card shows
| the reviewer's city — and duplicating fifteen names into a template is how
| two lists start disagreeing.
|
| Keys are the values stored in `students.governorate`. Never rename one
| without a data migration: the key IS the stored value.
*/
return [
    'jenin' => 'جنين',
    'tulkarm' => 'طولكرم',
    'nablus' => 'نابلس',
    'qalqilya' => 'قلقيلية',
    'salfit' => 'سلفيت',
    'ramallah' => 'رام الله والبيرة',
    'jericho' => 'أريحا',
    'jerusalem' => 'القدس',
    'bethlehem' => 'بيت لحم',
    'hebron' => 'الخليل',
    'gaza' => 'غزة',
    'north-gaza' => 'شمال غزة',
    'deir-albalah' => 'دير البلح',
    'khan-younis' => 'خان يونس',
    'rafah' => 'رفح',
];

<?php

namespace App\Enums;

/**
 * نوع أصل الوسائط في جدول media.
 *
 * 🔴 هذه الأنواع للأصول **التسويقية العامة** فقط. فيديو المحاضرة ليس
 * Video هنا — هو في lectures على القرص الخاص خلف فحص صلاحية، وملف
 * المكتبة ليس Document هنا — هو في library_files. الاسم المشترك لا يعني
 * مساراً مشتركاً.
 */
enum MediaType: string
{
    case Image = 'image';
    case Video = 'video';
    case Document = 'document';
}

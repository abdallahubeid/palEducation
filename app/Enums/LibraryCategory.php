<?php

namespace App\Enums;

/**
 * فئة ملف المكتبة.
 *
 * 🔴 غير mime_type تماماً. الطالب لا يبحث عن «PDF» — يبحث عن «امتحان سابق»
 * أو «ملخّص»، وكلاهما PDF. الأولى نيّة تعليمية والثاني صيغة تخزين.
 *
 * ⚠️ أربع قيم ثابتة عمداً: enum لا جدول تصنيفات. جدول كان سيفرض شاشة إدارة
 * كاملة لقائمة لا تتغيّر.
 */
enum LibraryCategory: string
{
    case Summary = 'summary';
    case Exam = 'exam';
    case Worksheet = 'worksheet';
    case Revision = 'revision';

    public function label(): string
    {
        return match ($this) {
            self::Summary => 'ملخّصات',
            self::Exam => 'امتحانات',
            self::Worksheet => 'أوراق عمل',
            self::Revision => 'مراجعات',
        };
    }

    /**
     * نغمة الشارة.
     *
     * ⚠️ كل صنف مكتوب حرفياً كاملاً — فاحص Tailwind لا ينفّذ PHP، فتجميع
     * "bg-{$tone}/12" لا يولّد أي CSS.
     */
    public function badge(): string
    {
        return match ($this) {
            self::Summary => 'bg-accent/12 text-accent-deep',
            self::Exam => 'bg-error/10 text-error-deep',
            self::Worksheet => 'bg-tag/12 text-tag',
            self::Revision => 'bg-amber/12 text-amber-deep',
        };
    }

    /** @return array<string, string> */
    public static function options(): array
    {
        return collect(self::cases())
            ->mapWithKeys(fn (self $case): array => [$case->value => $case->label()])
            ->all();
    }
}

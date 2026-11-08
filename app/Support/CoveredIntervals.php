<?php

namespace App\Support;

/**
 * دمج فترات المشاهدة — منطق م-5 الخالص.
 *
 * 🔴 المشغّل لا يُقال له «اكتمل». يرسل مقاطع [from,to] فقط، وهذا الصنف يدمجها
 * في مجموعة فترات غير متداخلة ويحسب مجموع الثواني الفريدة. الطالب الذي يعيد
 * مشاهدة أول دقيقة عشر مرات لا يتقدّم؛ الذي يقفز للنهاية لا «يُكمل».
 *
 * غير قابل للتغيير، بلا حالة، بلا قاعدة بيانات — قابل للاختبار وحده.
 */
final class CoveredIntervals
{
    /** @var array<int, array{0: int, 1: int}> فترات مرتّبة غير متداخلة */
    private array $intervals;

    /**
     * @param  array<int, array{0: int, 1: int}>  $intervals
     */
    public function __construct(array $intervals = [])
    {
        $this->intervals = self::normalize($intervals);
    }

    /**
     * @param  mixed  $stored  القيمة كما تُقرأ من عمود json (مصفوفة أو null)
     */
    public static function fromStored($stored): self
    {
        return new self(is_array($stored) ? $stored : []);
    }

    /**
     * يضيف مقطعاً مُشاهَداً ويُرجع نسخة جديدة مدموجة.
     *
     * ⚠️ يتجاهل بصمت: النهاية قبل البداية، القيم السالبة، وأي مقطع خارج
     * [0, $ceiling] — النبضة تأتي من عميل يمكن تحريره.
     */
    public function add(int $from, int $to, ?int $ceiling = null): self
    {
        $from = max(0, $from);
        $to = $ceiling !== null ? min($to, $ceiling) : $to;

        if ($to <= $from) {
            return $this;
        }

        return new self([...$this->intervals, [$from, $to]]);
    }

    /** مجموع الثواني الفريدة المُغطّاة. */
    public function seconds(): int
    {
        return array_reduce(
            $this->intervals,
            fn (int $sum, array $pair): int => $sum + ($pair[1] - $pair[0]),
            0,
        );
    }

    /** @return array<int, array{0: int, 1: int}> */
    public function toArray(): array
    {
        return $this->intervals;
    }

    /**
     * يدمج فترات متداخلة أو متلاصقة، ويرتّبها، ويُسقط ما لا معنى له.
     *
     * @param  array<int, mixed>  $raw
     * @return array<int, array{0: int, 1: int}>
     */
    private static function normalize(array $raw): array
    {
        $clean = [];

        foreach ($raw as $pair) {
            if (! is_array($pair) || count($pair) < 2) {
                continue;
            }

            $start = (int) $pair[0];
            $end = (int) $pair[1];

            if ($start < 0 || $end <= $start) {
                continue;
            }

            $clean[] = [$start, $end];
        }

        usort($clean, fn (array $a, array $b): int => $a[0] <=> $b[0]);

        $merged = [];

        foreach ($clean as [$start, $end]) {
            if ($merged === [] || $start > $merged[count($merged) - 1][1]) {
                $merged[] = [$start, $end];

                continue;
            }

            // متداخل أو متلاصق — مُدّ نهاية الأخير.
            $merged[count($merged) - 1][1] = max($merged[count($merged) - 1][1], $end);
        }

        return $merged;
    }
}

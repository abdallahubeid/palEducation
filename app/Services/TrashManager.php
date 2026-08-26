<?php

namespace App\Services;

use App\Support\TrashableResources;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Storage;

/**
 * سلّة المحذوفات المركزية — جمع · استعادة · حذف نهائي · تفريغ.
 *
 * الكنترولر ينسّق فقط؛ كل ما يعرف كيف يُقرأ الصفّ المحذوف ويُمحى يعيش هنا،
 * فتُضاف كيانات جديدة للسلّة بلا لمس أي كنترولر.
 */
class TrashManager
{
    /**
     * صفوف السلّة، الأحدث حذفاً أولاً.
     *
     * @return Collection<int, array{type:string,type_label:string,id:int,title:string,subtitle:?string,deleted_at:?Carbon,token:string}>
     */
    public function items(?string $type = null): Collection
    {
        $resources = TrashableResources::all();

        if (TrashableResources::has($type)) {
            $resources = [$type => $resources[$type]];
        }

        $rows = collect();

        foreach ($resources as $key => $config) {
            $model = $config['model'];

            foreach ($model::onlyTrashed()->latest('deleted_at')->get() as $record) {
                $rows->push([
                    'type' => $key,
                    'type_label' => $config['label'],
                    'id' => $record->getKey(),
                    'title' => ($config['title'])($record),
                    'subtitle' => $config['subtitle'] ? ($config['subtitle'])($record) : null,
                    'deleted_at' => $record->deleted_at,
                    // الرمز يحمل النوع والمعرّف معاً: التحديد الجماعي يعبر
                    // أنواعاً مختلفة، فمعرّف وحده لا يكفي لتحديد الصفّ.
                    'token' => $key.':'.$record->getKey(),
                ]);
            }
        }

        return $rows->sortByDesc(fn (array $row) => $row['deleted_at']?->timestamp ?? 0)->values();
    }

    public function count(?string $type = null): int
    {
        return $this->items($type)->count();
    }

    /** عدد المحذوفات لكل نوع — لشارات التبويبات. */
    public function countsByType(): array
    {
        $counts = [];

        foreach (TrashableResources::all() as $key => $config) {
            $counts[$key] = ($config['model'])::onlyTrashed()->count();
        }

        return $counts;
    }

    public function restore(string $type, int|string $id): bool
    {
        // 🔴 الجلب بـonlyTrashed إجباري: restore() على نسخة مجلوبة قبل الحذف
        // لا يفعل شيئاً — deleted_at في الذاكرة null، فلا يتّسخ النموذج ولا
        // يُصدر save() أي UPDATE. لا-عملية صامتة.
        $record = TrashableResources::modelFor($type)::onlyTrashed()->find($id);

        return $record ? (bool) $record->restore() : false;
    }

    public function forceDestroy(string $type, int|string $id): bool
    {
        $record = TrashableResources::modelFor($type)::onlyTrashed()->find($id);

        if (! $record) {
            return false;
        }

        $this->purgeMedia($record);

        return (bool) $record->forceDelete();
    }

    /**
     * @param  array<int, string>  $tokens  رموز بصيغة "type:id"
     */
    public function restoreMany(array $tokens): int
    {
        return $this->eachToken($tokens, fn (string $type, string $id) => $this->restore($type, $id));
    }

    /** @param array<int, string> $tokens */
    public function forceDestroyMany(array $tokens): int
    {
        return $this->eachToken($tokens, fn (string $type, string $id) => $this->forceDestroy($type, $id));
    }

    /** استعادة كل المعروض في الفلتر الحالي. */
    public function restoreAll(?string $type = null): int
    {
        return $this->restoreMany($this->items($type)->pluck('token')->all());
    }

    /** تفريغ السلّة — حذف نهائي لكل المعروض في الفلتر الحالي. */
    public function empty(?string $type = null): int
    {
        return $this->forceDestroyMany($this->items($type)->pluck('token')->all());
    }

    /**
     * @param  array<int, string>  $tokens
     */
    private function eachToken(array $tokens, callable $action): int
    {
        $done = 0;

        foreach ($tokens as $token) {
            [$type, $id] = array_pad(explode(':', (string) $token, 2), 2, null);

            // نوع غير معروف أو معرّف غير رقمي يُتجاهَل بصمت: الرموز تأتي من
            // نموذج يستطيع المستخدم تحريره، فلا تُثق بها.
            if (! TrashableResources::has($type) || ! ctype_digit((string) $id)) {
                continue;
            }

            if ($action($type, $id)) {
                $done++;
            }
        }

        return $done;
    }

    /**
     * ينظّف وسائط الصفّ وملفاتها قبل محوه نهائياً.
     *
     * 🔴 mediable_id عمود متعدّد الأشكال بلا مفتاح أجنبي، فلا تتالي يمسح
     * صفوف media مع أبيها. تركها يُنتج صفوفاً يتيمة وملفات فائضة.
     */
    private function purgeMedia(Model $record): void
    {
        if (! method_exists($record, 'media')) {
            return;
        }

        foreach ($record->media()->withTrashed()->get() as $media) {
            $this->deleteFile($media->path);
            $media->forceDelete();
        }
    }

    private function deleteFile(?string $path): void
    {
        // البادئة شرط: الأصول المبذورة تحت public/images ملفات مستودع،
        // حذفها يكسر البذرة في كل بيئة ولا تُستعاد من السلّة.
        if ($path && str_starts_with($path, 'storage/')) {
            Storage::disk('public')->delete(str_replace('storage/', '', $path));
        }
    }
}

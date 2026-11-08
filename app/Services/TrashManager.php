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
    public function items(?string $type = null, ?array $onlyTypes = null): Collection
    {
        $resources = TrashableResources::all();

        // 🔴 Scoped to what the caller may act on. Permission is per type
        // now, so an admin who may delete branches but not users must not
        // even see a trashed user listed.
        if ($onlyTypes !== null) {
            $resources = array_intersect_key($resources, array_flip($onlyTypes));
        }

        if (TrashableResources::has($type) && array_key_exists($type, $resources)) {
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

    public function count(?string $type = null, ?array $onlyTypes = null): int
    {
        return $this->items($type, $onlyTypes)->count();
    }

    /**
     * عدد المحذوفات لكل نوع — لشارات التبويبات.
     *
     * @param  array<int, string>|null  $onlyTypes
     */
    public function countsByType(?array $onlyTypes = null): array
    {
        $counts = [];

        $resources = $onlyTypes === null
            ? TrashableResources::all()
            : array_intersect_key(TrashableResources::all(), array_flip($onlyTypes));

        foreach ($resources as $key => $config) {
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

        if (! $record) {
            return false;
        }

        // A type may declare its own restore when the row is only half the
        // record — a user account is paired with a domain row, and bringing
        // one back without the other is a broken account, not a restored one.
        $hook = TrashableResources::all()[$type]['restore'] ?? null;

        return $hook ? (bool) $hook($record) : (bool) $record->restore();
    }

    public function forceDestroy(string $type, int|string $id): bool
    {
        $record = TrashableResources::modelFor($type)::onlyTrashed()->find($id);

        if (! $record) {
            return false;
        }

        $this->purgeMedia($record);

        $hook = TrashableResources::all()[$type]['forceDelete'] ?? null;

        return $hook ? (bool) $hook($record) : (bool) $record->forceDelete();
    }

    /**
     * @param  array<int, string>  $tokens  رموز بصيغة "type:id"
     * @param  array<int, string>|null  $onlyTypes  الأنواع المسموح بها للفاعل
     */
    public function restoreMany(array $tokens, ?array $onlyTypes = null): int
    {
        return $this->eachToken($tokens, fn (string $type, string $id) => $this->restore($type, $id), $onlyTypes);
    }

    /**
     * @param  array<int, string>  $tokens
     * @param  array<int, string>|null  $onlyTypes
     */
    public function forceDestroyMany(array $tokens, ?array $onlyTypes = null): int
    {
        return $this->eachToken($tokens, fn (string $type, string $id) => $this->forceDestroy($type, $id), $onlyTypes);
    }

    /** استعادة كل المعروض في الفلتر الحالي. */
    public function restoreAll(?string $type = null, ?array $onlyTypes = null): int
    {
        return $this->restoreMany($this->items($type, $onlyTypes)->pluck('token')->all(), $onlyTypes);
    }

    /** تفريغ السلّة — حذف نهائي لكل المعروض في الفلتر الحالي. */
    public function empty(?string $type = null, ?array $onlyTypes = null): int
    {
        return $this->forceDestroyMany($this->items($type, $onlyTypes)->pluck('token')->all(), $onlyTypes);
    }

    /**
     * @param  array<int, string>  $tokens
     * @param  array<int, string>|null  $onlyTypes
     */
    private function eachToken(array $tokens, callable $action, ?array $onlyTypes = null): int
    {
        $done = 0;

        foreach ($tokens as $token) {
            [$type, $id] = array_pad(explode(':', (string) $token, 2), 2, null);

            // نوع غير معروف أو معرّف غير رقمي يُتجاهَل بصمت: الرموز تأتي من
            // نموذج يستطيع المستخدم تحريره، فلا تُثق بها.
            if (! TrashableResources::has($type) || ! ctype_digit((string) $id)) {
                continue;
            }

            // 🔴 وكذلك النوع الذي لا يملك الفاعل صلاحيته. الرمز مركّب يصل من
            // النموذج، فتحديد صفٍّ من نوع محظور لا يُرفَض الطلب كلّه — يُتجاهَل
            // الصفّ وحده، تماماً كالرمز المزوَّر.
            if ($onlyTypes !== null && ! in_array($type, $onlyTypes, true)) {
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

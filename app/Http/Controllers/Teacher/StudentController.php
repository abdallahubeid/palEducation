<?php

namespace App\Http\Controllers\Teacher;

use App\Http\Controllers\Controller;
use App\Models\Branch;
use App\Models\Student;
use Illuminate\Contracts\View\View;
use Illuminate\Http\Request;

/**
 * طلبة فروع المعلّم — قائمة، لا لوحة أداء.
 *
 * 🔴 لا بيانات أداء في قاعدة البيانات بعد: لا امتحانات ولا واجبات ولا حضور،
 * ولا جدول يربط الطالب بمادة معلّم بعينه. الرابط الوحيد الموجود:
 * students.branch_id = subjects.branch_id. فالنطاق هنا = طلبة **فروع** مواد
 * المعلّم المسندة. المقاييس (تسليم/علامات/نتائج) تُضاف حين تُبنى أنظمتها.
 *
 * ⚠️ النطاق في الاستعلام لا في العرض: الطالب خارج فروع المعلّم لا يظهر أبداً،
 * وسلسلة الاستعلام المحرَّرة (?branch_id=) تُسقَط إن كانت خارج فروعه.
 */
class StudentController extends Controller
{
    public function index(Request $request): View
    {
        $teacher = $request->user()->userable;

        // فروع مواد المعلّم المسندة — مصدر النطاق كلّه.
        $branchIds = $teacher->subjects()
            ->pluck('subjects.branch_id')
            ->unique()
            ->values();

        $branches = Branch::query()
            ->whereIn('id', $branchIds)
            ->orderBy('name')
            ->get(['id', 'name']);

        // فرع لا يملكه المعلّم يُسقَط بدل أن يُطاع.
        $branchId = $request->integer('branch_id') ?: null;
        if ($branchId !== null && ! $branches->contains('id', $branchId)) {
            $branchId = null;
        }

        $search = $request->query('search');

        $students = Student::query()
            ->whereIn('branch_id', $branchIds)
            ->with('branch:id,name')
            ->when($branchId, fn ($query) => $query->where('branch_id', $branchId))
            ->when(filled($search), fn ($query) => $query->where('full_name', 'like', '%'.trim($search).'%'))
            ->orderBy('full_name')
            ->paginate(config('app.paginate_page'))
            ->withQueryString();

        return view('teacher.students.index', [
            'students' => $students,
            'branches' => $branches,
            'filters' => ['branch_id' => $branchId, 'search' => $search],
            'hasSubjects' => $branchIds->isNotEmpty(),
            // العدّ الكلّي غير المُصفّى — للترويسة.
            'totalStudents' => Student::whereIn('branch_id', $branchIds)->count(),
        ]);
    }
}

<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Branch;
use App\Models\Lecture;
use App\Models\Slider;
use App\Models\Student;
use App\Models\Subject;
use App\Models\Teacher;
use Illuminate\Contracts\View\View;

class DashboardController extends Controller
{
    public function index(): View
    {
        return view('admin.dashboard', [
            'metrics' => [
                'students' => Student::query()->count(),
                'teachers' => Teacher::query()->count(),
                'subjects' => Subject::query()->count(),
                'lectures' => Lecture::query()->count(),
            ],
            'sliders' => [
                'active' => Slider::query()->where('is_active', true)->count(),
                'total' => Slider::query()->count(),
                'trashed' => Slider::onlyTrashed()->count(),
            ],
            // withCount لا حلقة على العلاقة: العدّ في استعلام واحد بدل
            // استعلام لكل فرع.
            'branches' => Branch::query()
                ->withCount(['students', 'subjects'])
                ->orderBy('display_order')
                ->get(),
            'latestSliders' => Slider::query()->with('image')->latest()->take(5)->get(),
        ]);
    }
}

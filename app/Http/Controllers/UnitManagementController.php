<?php

namespace App\Http\Controllers;

use App\Models\Course;
use App\Models\Department;
use App\Models\Unit;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class UnitManagementController extends Controller
{
    public function index(Request $request): Response
    {
        abort_unless($request->user()->hasAnyPermission('units.view|classes.manage'), 403);

        $filters = $request->only(['search', 'course_id', 'department_id']);

        return Inertia::render('Academics/Units', [
            'units' => Unit::query()
                ->with(['course:id,code,name,has_units', 'department:id,code,name'])
                ->withCount(['lecturerAssignments', 'enrollments'])
                ->when($filters['search'] ?? null, fn ($query, $search) => $query->where(fn ($query) => $query
                    ->where('code', 'like', "%{$search}%")
                    ->orWhere('name', 'like', "%{$search}%")))
                ->when($filters['course_id'] ?? null, fn ($query, $course) => $query->where('course_id', $course))
                ->when($filters['department_id'] ?? null, fn ($query, $department) => $query->where('department_id', $department))
                ->orderBy('code')
                ->paginate(20)
                ->withQueryString(),
            'courses' => Course::query()
                ->where('has_units', true)
                ->where('is_active', true)
                ->orderBy('name')
                ->get(['id', 'code', 'name', 'department_id', 'has_units']),
            'departments' => Department::orderBy('name')->get(['id', 'code', 'name']),
            'filters' => $filters,
            'permissions' => [
                'canAdd' => $request->user()->hasAnyPermission('units.add|classes.manage'),
                'canEdit' => $request->user()->hasAnyPermission('units.edit|classes.manage'),
                'canDelete' => $request->user()->hasAnyPermission('units.delete|classes.manage'),
            ],
        ]);
    }

    public function destroy(Request $request, Unit $unit): RedirectResponse
    {
        abort_unless($request->user()->hasAnyPermission('units.delete|classes.manage'), 403);

        $unit->forceFill(['deleted_by' => $request->user()->id])->save();
        $unit->delete();

        return back()->with('flash.banner', 'Unit deleted.');
    }
}

<?php

namespace App\Http\Controllers;

use App\Models\Department;
use App\Models\Lecturer;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Inertia\Inertia;
use Inertia\Response;

class DepartmentManagementController extends Controller
{
    public function index(Request $request): Response
    {
        abort_unless($request->user()->hasAnyPermission('departments.view|classes.manage'), 403);

        $search = $request->string('search')->toString();

        return Inertia::render('Academics/Departments', [
            'departments' => Department::query()
                ->with(['parent:id,code,name'])
                ->withCount(['courses', 'units', 'lecturers'])
                ->when($search, fn ($query) => $query->where(fn ($query) => $query
                    ->where('code', 'like', "%{$search}%")
                    ->orWhere('name', 'like', "%{$search}%")))
                ->orderBy('name')
                ->paginate(20)
                ->withQueryString(),
            'departmentOptions' => Department::orderBy('name')->get(['id', 'code', 'name']),
            'lecturerOptions' => Lecturer::orderBy('last_name')->get(['id', 'title', 'first_name', 'last_name']),
            'filters' => ['search' => $search],
            'permissions' => [
                'canAdd' => $request->user()->hasAnyPermission('departments.add|classes.manage'),
                'canEdit' => $request->user()->hasAnyPermission('departments.edit|classes.manage'),
                'canDelete' => $request->user()->hasAnyPermission('departments.delete|classes.manage'),
            ],
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        abort_unless($request->user()->hasAnyPermission('departments.add|classes.manage'), 403);

        Department::create($this->departmentData($request) + [
            'created_by' => $request->user()->id,
            'updated_by' => $request->user()->id,
        ]);

        return back()->with('flash.banner', 'Department created.');
    }

    public function update(Request $request, Department $department): RedirectResponse
    {
        abort_unless($request->user()->hasAnyPermission('departments.edit|classes.manage'), 403);

        $department->update($this->departmentData($request, $department) + ['updated_by' => $request->user()->id]);

        return back()->with('flash.banner', 'Department updated.');
    }

    public function destroy(Request $request, Department $department): RedirectResponse
    {
        abort_unless($request->user()->hasAnyPermission('departments.delete|classes.manage'), 403);

        $department->forceFill(['deleted_by' => $request->user()->id])->save();
        $department->delete();

        return back()->with('flash.banner', 'Department deleted.');
    }

    private function departmentData(Request $request, ?Department $department = null): array
    {
        return $request->validate([
            'parent_department_id' => ['nullable', 'exists:departments,id'],
            'head_lecturer_id' => ['nullable', 'exists:lecturers,id'],
            'code' => ['required', 'max:30', Rule::unique('departments')->ignore($department)->whereNull('deleted_at')],
            'name' => ['required', 'max:255'],
            'description' => ['nullable', 'string'],
            'is_active' => ['boolean'],
        ]);
    }
}

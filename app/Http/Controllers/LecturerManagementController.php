<?php

namespace App\Http\Controllers;

use App\Models\Department;
use App\Models\Lecturer;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Inertia\Inertia;
use Inertia\Response;

class LecturerManagementController extends Controller
{
    public function index(Request $request): Response
    {
        abort_unless($request->user()->hasPermission('classes.manage'), 403);

        $filters = $request->only(['search', 'department_id', 'status']);

        return Inertia::render('Academics/Lecturers', [
            'lecturers' => Lecturer::query()
                ->with('department:id,code,name')
                ->withCount('unitAssignments')
                ->when($filters['search'] ?? null, fn ($query, $search) => $query->where(fn ($query) => $query
                    ->where('staff_number', 'like', "%{$search}%")
                    ->orWhere('first_name', 'like', "%{$search}%")
                    ->orWhere('last_name', 'like', "%{$search}%")
                    ->orWhere('email', 'like', "%{$search}%")))
                ->when($filters['department_id'] ?? null, fn ($query, $department) => $query->where('department_id', $department))
                ->when($filters['status'] ?? null, fn ($query, $status) => $query->where('employment_status', $status))
                ->orderBy('last_name')
                ->paginate(10)
                ->withQueryString(),
            'departments' => Department::orderBy('name')->get(['id', 'code', 'name']),
            'filters' => $filters,
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        abort_unless($request->user()->hasPermission('classes.manage'), 403);

        Lecturer::create($this->lecturerData($request) + [
            'created_by' => $request->user()->id,
            'updated_by' => $request->user()->id,
        ]);

        return back()->with('flash.banner', 'Lecturer created.');
    }

    public function update(Request $request, Lecturer $lecturer): RedirectResponse
    {
        abort_unless($request->user()->hasPermission('classes.manage'), 403);

        $lecturer->update($this->lecturerData($request, $lecturer) + ['updated_by' => $request->user()->id]);

        return back()->with('flash.banner', 'Lecturer updated.');
    }

    public function destroy(Request $request, Lecturer $lecturer): RedirectResponse
    {
        abort_unless($request->user()->hasPermission('classes.manage'), 403);

        $lecturer->forceFill(['deleted_by' => $request->user()->id])->save();
        $lecturer->delete();

        return back()->with('flash.banner', 'Lecturer deleted.');
    }

    private function lecturerData(Request $request, ?Lecturer $lecturer = null): array
    {
        return $request->validate([
            'department_id' => ['required', 'exists:departments,id'],
            'staff_number' => ['required', 'max:50', Rule::unique('lecturers')->ignore($lecturer)->whereNull('deleted_at')],
            'title' => ['nullable', 'max:30'],
            'first_name' => ['required', 'max:100'],
            'middle_name' => ['nullable', 'max:100'],
            'last_name' => ['required', 'max:100'],
            'email' => ['nullable', 'email', 'max:255', Rule::unique('lecturers')->ignore($lecturer)->whereNull('deleted_at')],
            'phone' => ['nullable', 'max:30'],
            'hired_on' => ['nullable', 'date'],
            'employment_status' => ['required', Rule::in(['active', 'inactive', 'suspended', 'terminated'])],
        ]);
    }
}

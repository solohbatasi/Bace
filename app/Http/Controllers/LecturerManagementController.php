<?php

namespace App\Http\Controllers;

use App\Models\Department;
use App\Models\Lecturer;
use App\Models\Role;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rule;
use Inertia\Inertia;
use Inertia\Response;

class LecturerManagementController extends Controller
{
    public function index(Request $request): Response
    {
        abort_unless($request->user()->hasAnyPermission('lecturers.view|classes.manage'), 403);

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
                ->paginate(20)
                ->withQueryString(),
            'departments' => Department::orderBy('name')->get(['id', 'code', 'name']),
            'filters' => $filters,
            'permissions' => [
                'canAdd' => $request->user()->hasAnyPermission('lecturers.add|classes.manage'),
                'canEdit' => $request->user()->hasAnyPermission('lecturers.edit|classes.manage'),
                'canDelete' => $request->user()->hasAnyPermission('lecturers.delete|classes.manage'),
            ],
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        abort_unless($request->user()->hasAnyPermission('lecturers.add|classes.manage'), 403);

        DB::transaction(function () use ($request): void {
            $data = $this->lecturerData($request);
            $user = $this->createOrUpdateLecturerUser($data);

            Lecturer::create($data + [
                'user_id' => $user?->id,
                'created_by' => $request->user()->id,
                'updated_by' => $request->user()->id,
            ]);
        });

        return back()->with('flash.banner', 'Lecturer created. Email login created when an email was provided.');
    }

    public function update(Request $request, Lecturer $lecturer): RedirectResponse
    {
        abort_unless($request->user()->hasAnyPermission('lecturers.edit|classes.manage'), 403);

        DB::transaction(function () use ($request, $lecturer): void {
            $data = $this->lecturerData($request, $lecturer);
            $user = $this->createOrUpdateLecturerUser($data, $lecturer);

            $lecturer->update($data + [
                'user_id' => $user?->id ?? $lecturer->user_id,
                'updated_by' => $request->user()->id,
            ]);
        });

        return back()->with('flash.banner', 'Lecturer updated.');
    }

    public function destroy(Request $request, Lecturer $lecturer): RedirectResponse
    {
        abort_unless($request->user()->hasAnyPermission('lecturers.delete|classes.manage'), 403);

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
            'email' => [
                'nullable',
                'email',
                'max:255',
                Rule::unique('lecturers')->ignore($lecturer)->whereNull('deleted_at'),
                Rule::unique('users')->ignore($lecturer?->user_id)->whereNull('deleted_at'),
            ],
            'phone' => ['nullable', 'max:30'],
            'hired_on' => ['nullable', 'date'],
            'employment_status' => ['required', Rule::in(['active', 'inactive', 'suspended', 'terminated'])],
        ]);
    }

    private function createOrUpdateLecturerUser(array $data, ?Lecturer $lecturer = null): ?User
    {
        if (blank($data['email'] ?? null)) {
            return null;
        }

        $user = $lecturer?->user ?: new User();
        $user->fill([
            'name' => trim(($data['title'] ? "{$data['title']} " : '') . "{$data['first_name']} {$data['last_name']}"),
            'email' => $data['email'],
            'status' => $data['employment_status'] === 'active' ? 'active' : 'suspended',
            'status_reason' => $data['employment_status'] === 'active' ? null : 'Synced from lecturer employment status.',
            'password' => $user->exists ? $user->password : Hash::make('password'),
        ]);
        $user->save();

        if ($role = Role::where('name', 'Lecturer')->first()) {
            $user->roles()->syncWithoutDetaching([$role->id]);
        }

        return $user;
    }
}

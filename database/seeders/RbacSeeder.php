<?php

namespace Database\Seeders;

use App\Models\Permission;
use App\Models\Role;
use App\Support\AppPermissions;
use App\Models\User;
use Illuminate\Database\Seeder;

class RbacSeeder extends Seeder
{
    public function run(): void
    {
        $legacyPermissions = [
            ['name' => 'users.view', 'group' => 'users', 'description' => 'View user accounts and filters.'],
            ['name' => 'users.create', 'group' => 'users', 'description' => 'Create user accounts.'],
            ['name' => 'users.update', 'group' => 'users', 'description' => 'Update users, roles, and permissions.'],
            ['name' => 'users.suspend', 'group' => 'users', 'description' => 'Suspend, reactivate, or terminate account access.'],
            ['name' => 'roles.manage', 'group' => 'access', 'description' => 'Manage roles and role permissions.'],
            ['name' => 'permissions.manage', 'group' => 'access', 'description' => 'Manage permission definitions.'],
            ['name' => 'health.view', 'group' => 'security', 'description' => 'View system health, sessions, and tokens.'],
            ['name' => 'tokens.revoke', 'group' => 'security', 'description' => 'Revoke API tokens and browser sessions.'],
            ['name' => 'students.view', 'group' => 'students', 'description' => 'View student records and profiles.'],
            ['name' => 'students.create', 'group' => 'students', 'description' => 'Admit new students.'],
            ['name' => 'students.update', 'group' => 'students', 'description' => 'Update student profile, guardian, academic, course, class, and status records.'],
            ['name' => 'students.delete', 'group' => 'students', 'description' => 'Delete student records.'],
            ['name' => 'finance.view', 'group' => 'finance', 'description' => 'View billing, invoices, payments, and receipts.'],
            ['name' => 'finance.manage', 'group' => 'finance', 'description' => 'Manage fees, invoices, payments, and receipts.'],
            ['name' => 'classes.manage', 'group' => 'academics', 'description' => 'Manage classes, courses, units, and enrollments.'],
            ['name' => 'assignments.manage', 'group' => 'academics', 'description' => 'Manage assignments, submissions, and grading.'],
            ['name' => 'attendance.manage', 'group' => 'academics', 'description' => 'Manage attendance records.'],
        ];

        $permissions = collect([...AppPermissions::all(), ...$legacyPermissions])->map(fn ($permission) => Permission::updateOrCreate(
            ['name' => $permission['name']],
            ['group' => $permission['group'], 'description' => $permission['description']]
        ));

        $superAdmin = Role::firstOrCreate(
            ['name' => 'Super Admin'],
            ['guard_name' => 'web', 'description' => 'Full operational access across the college management system.']
        );

        $registrar = Role::firstOrCreate(
            ['name' => 'Registrar'],
            ['guard_name' => 'web', 'description' => 'Student admissions, academic records, courses, and class assignments.']
        );

        $financeOfficer = Role::firstOrCreate(
            ['name' => 'Finance Officer'],
            ['guard_name' => 'web', 'description' => 'Fees, invoices, payments, and receipts.']
        );

        $lecturer = Role::firstOrCreate(
            ['name' => 'Lecturer'],
            ['guard_name' => 'web', 'description' => 'Teaching, assignments, grading, and attendance.']
        );

        $student = Role::firstOrCreate(
            ['name' => 'Student'],
            ['guard_name' => 'web', 'description' => 'Student self-service access.']
        );

        $administrator = Role::firstOrCreate(
            ['name' => 'Administrator'],
            ['guard_name' => 'web', 'description' => 'Legacy administrator role retained for compatibility.']
        );

        $support = Role::firstOrCreate(
            ['name' => 'Support'],
            ['guard_name' => 'web', 'description' => 'Frontline account review and support access.']
        );

        $superAdmin->permissions()->syncWithoutDetaching($permissions->pluck('id'));
        $administrator->permissions()->syncWithoutDetaching($permissions->pluck('id'));
        $registrar->permissions()->syncWithoutDetaching(
            $permissions->whereIn('name', [
                'students.view',
                'students.add',
                'students.edit',
                'academic-settings.view',
                'academic-years.view',
                'academic-years.add',
                'academic-years.edit',
                'academic-years.delete',
                'semesters.view',
                'semesters.add',
                'semesters.edit',
                'semesters.delete',
                'classes.view',
                'classes.add',
                'classes.edit',
                'classes.delete',
                'departments.view',
                'departments.add',
                'departments.edit',
                'departments.delete',
                'courses.view',
                'courses.add',
                'courses.edit',
                'courses.delete',
                'courses.manage',
                'units.view',
                'units.add',
                'units.edit',
                'units.delete',
                'units.manage',
                'lecturers.view',
                'lecturers.add',
                'lecturers.edit',
                'lecturers.delete',
                'enrollments.view',
                'enrollments.add',
                'enrollments.edit',
                'enrollments.delete',
                'examinations.view',
                'examinations.add',
                'examinations.edit',
                'examinations.delete',
                'examinations.manage',
                'results.view',
                'students.create',
                'students.update',
                'classes.manage',
            ])->pluck('id')
        );
        $financeOfficer->permissions()->syncWithoutDetaching(
            $permissions->whereIn('name', [
                'students.view',
                'payments.view',
                'payments.add',
                'payments.edit',
                'payments.delete',
                'tickets.view',
                'tickets.add',
                'tickets.edit',
                'tickets.delete',
                'tickets.manage',
                'finance.view',
                'finance.manage',
            ])->pluck('id')
        );
        $lecturer->permissions()->syncWithoutDetaching(
            $permissions->whereIn('name', [
                'students.view',
                'courses.view',
                'units.view',
                'enrollments.view',
                'examinations.view',
                'results.view',
                'assignments.view',
                'assignments.add',
                'assignments.edit',
                'assignments.delete',
                'assignments.manage',
                'tickets.view',
                'attendance.manage',
            ])->pluck('id')
        );
        $student->permissions()->syncWithoutDetaching(
            $permissions->whereIn('name', ['payments.view', 'courses.view', 'units.view', 'results.view', 'tickets.view', 'tickets.add'])->pluck('id')
        );
        $student->permissions()->detach($permissions->whereIn('name', [
            'students.view',
            'students.add',
            'students.edit',
            'students.delete',
            'students.manage',
        ])->pluck('id'));
        $support->permissions()->syncWithoutDetaching(
            $permissions->whereIn('name', ['users.view', 'health.view'])->pluck('id')
        );

        $firstUser = User::oldest('id')->first();

        if ($firstUser) {
            $firstUser->roles()->syncWithoutDetaching([$superAdmin->id]);
        }
    }
}

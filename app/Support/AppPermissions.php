<?php

namespace App\Support;

class AppPermissions
{
    public const RESOURCES = [
        'users' => 'Users',
        'students' => 'Students',
        'payments' => 'Payments',
        'tickets' => 'Lesson Tickets',
        'academic-settings' => 'Academic Settings',
        'academic-years' => 'Academic Years',
        'semesters' => 'Semesters',
        'classes' => 'Classes',
        'departments' => 'Departments',
        'courses' => 'Courses',
        'units' => 'Units',
        'lecturers' => 'Lecturers',
        'enrollments' => 'Enrollments',
        'examinations' => 'Examinations',
        'results' => 'Results',
        'assignments' => 'Assignments',
        'roles' => 'Roles',
        'permissions' => 'Permissions',
        'organisation-settings' => 'Organisation Settings',
        'system-health' => 'System Health',
        'api-tokens' => 'API Tokens',
    ];

    public const ACTIONS = ['view', 'add', 'edit', 'delete', 'manage'];

    public static function all(): array
    {
        $permissions = [];

        foreach (self::RESOURCES as $resource => $label) {
            foreach (self::ACTIONS as $action) {
                $permissions[] = [
                    'name' => "{$resource}.{$action}",
                    'group' => $resource,
                    'description' => ucfirst($action).' '.$label.'.',
                ];
            }
        }

        return $permissions;
    }
}

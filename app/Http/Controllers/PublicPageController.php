<?php

namespace App\Http\Controllers;

use App\Models\Course;
use App\Models\Department;
use App\Models\Lecturer;
use App\Models\OrganisationSetting;
use App\Models\Student;
use Inertia\Inertia;
use Inertia\Response;

class PublicPageController extends Controller
{
    public function home(): Response
    {
        return Inertia::render('Public/Home', [
            ...$this->basePayload(),
            'featuredCourses' => $this->coursesQuery()->limit(4)->get()->map(fn (Course $course) => $this->coursePayload($course)),
            'stats' => $this->stats(),
        ]);
    }

    public function about(): Response
    {
        return Inertia::render('Public/About', [
            ...$this->basePayload(),
            'stats' => $this->stats(),
        ]);
    }

    public function courses(): Response
    {
        return Inertia::render('Public/Courses', [
            ...$this->basePayload(),
            'courses' => $this->coursesQuery()->get()->map(fn (Course $course) => $this->coursePayload($course)),
        ]);
    }

    public function contact(): Response
    {
        return Inertia::render('Public/Contact', [
            ...$this->basePayload(),
        ]);
    }

    private function basePayload(): array
    {
        $organisation = OrganisationSetting::current();

        return [
            'organisation' => [
                'name' => $organisation->name ?: config('app.name'),
                'short_name' => $organisation->short_name ?: config('app.name'),
                'logo_url' => $organisation->logo_url,
                'official_email' => $organisation->official_email,
                'contact_email' => $organisation->contact_email,
                'marketing_email' => $organisation->marketing_email,
                'primary_contact' => $organisation->primary_contact,
                'secondary_contact' => $organisation->secondary_contact,
                'location' => $organisation->location,
                'mission' => $organisation->mission,
                'vision' => $organisation->vision,
                'about' => $organisation->about,
                'description' => $organisation->description,
                'operation_hours' => $organisation->operation_hours ?: OrganisationSetting::defaultOperationHours(),
            ],
        ];
    }

    private function coursesQuery()
    {
        return Course::query()
            ->with([
                'department:id,name',
                'subcourses' => fn ($query) => $query
                    ->where('is_active', true)
                    ->orderBy('name')
                    ->select(['id', 'department_id', 'parent_course_id', 'code', 'name', 'duration_semesters', 'fees', 'description', 'is_active']),
                'units' => fn ($query) => $query
                    ->where('is_active', true)
                    ->orderBy('name')
                    ->select(['id', 'course_id', 'code', 'name', 'credit_hours', 'is_active']),
            ])
            ->whereNull('parent_course_id')
            ->where('is_active', true)
            ->orderBy('name')
            ->select(['id', 'department_id', 'parent_course_id', 'code', 'name', 'duration_semesters', 'fees', 'has_units', 'description', 'is_active']);
    }

    private function coursePayload(Course $course): array
    {
        return [
            'id' => $course->id,
            'code' => $course->code,
            'name' => $course->name,
            'department' => $course->department?->name,
            'duration_semesters' => $course->duration_semesters,
            'fees' => (float) $course->fees,
            'has_units' => (bool) $course->has_units,
            'description' => $course->description,
            'subcourses' => $course->subcourses->map(fn (Course $subcourse) => [
                'id' => $subcourse->id,
                'code' => $subcourse->code,
                'name' => $subcourse->name,
                'duration_semesters' => $subcourse->duration_semesters,
                'fees' => (float) $subcourse->fees,
                'description' => $subcourse->description,
            ])->values(),
            'units' => $course->units->map(fn ($unit) => [
                'id' => $unit->id,
                'code' => $unit->code,
                'name' => $unit->name,
                'credit_hours' => $unit->credit_hours,
            ])->values(),
        ];
    }

    private function stats(): array
    {
        return [
            ['label' => 'Active Courses', 'value' => Course::whereNull('parent_course_id')->where('is_active', true)->count()],
            ['label' => 'Departments', 'value' => Department::where('is_active', true)->count()],
            ['label' => 'Learners', 'value' => Student::where('status', 'active')->count()],
            ['label' => 'Lecturers', 'value' => Lecturer::where('employment_status', 'active')->count()],
        ];
    }
}

<?php

namespace App\Http\Controllers;

use App\Http\Requests\Student\StoreStudentRequest;
use App\Http\Requests\Student\UpdateStudentRequest;
use App\Models\AcademicYear;
use App\Models\CollegeClass;
use App\Models\Course;
use App\Models\Department;
use App\Models\Enrollment;
use App\Models\Role;
use App\Models\Semester;
use App\Models\SemesterRegistration;
use App\Models\Student;
use App\Models\Unit;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;
use Inertia\Inertia;
use Inertia\Response;

class StudentController extends Controller
{
    public function index(Request $request): Response
    {
        $filters = $request->only(['search', 'status', 'course_id', 'class_id']);

        return Inertia::render('Students/Index', [
            'students' => Student::query()
                ->with(['department:id,name,code', 'course:id,name,code,fees', 'class:id,name,code'])
                ->when($filters['search'] ?? null, fn ($query, $search) => $query->where(fn ($query) => $query
                    ->where('admission_number', 'like', "%{$search}%")
                    ->orWhere('first_name', 'like', "%{$search}%")
                    ->orWhere('last_name', 'like', "%{$search}%")
                    ->orWhere('email', 'like', "%{$search}%")))
                ->when($filters['status'] ?? null, fn ($query, $status) => $query->where('status', $status))
                ->when($filters['course_id'] ?? null, fn ($query, $courseId) => $query->where('course_id', $courseId))
                ->when($filters['class_id'] ?? null, fn ($query, $classId) => $query->where('class_id', $classId))
                ->latest()
                ->paginate(12)
                ->withQueryString(),
            'filters' => $filters,
            'statuses' => Student::STATUSES,
            'courses' => Course::orderBy('name')->get(['id', 'name', 'code', 'fees']),
            'departments' => Department::orderBy('name')->get(['id', 'name', 'code']),
            'classes' => CollegeClass::orderBy('name')->get(['id', 'name', 'code', 'course_id']),
            'academicYears' => AcademicYear::orderByDesc('starts_on')->get(['id', 'name', 'is_current']),
            'semesters' => Semester::orderByDesc('starts_on')->get(['id', 'academic_year_id', 'name', 'sequence', 'is_current']),
        ]);
    }

    public function create(): Response
    {
        $this->authorize('create', Student::class);

        return Inertia::render('Students/Form', $this->formData());
    }

    public function store(StoreStudentRequest $request): RedirectResponse
    {
        DB::transaction(function () use ($request): void {
            $data = $request->safe()->except(['photo', 'academic_histories']);
            $data['admission_number'] = $data['admission_number'] ?: Student::nextAdmissionNumber();
            $data['created_by'] = $request->user()->id;
            $data['updated_by'] = $request->user()->id;

            if ($request->hasFile('photo')) {
                $data['photo_path'] = $request->file('photo')->store('students/photos', 'public');
            }

            $user = $this->createOrUpdateStudentUser($data);
            $student = Student::create($data + ['user_id' => $user?->id]);

            foreach ($request->validated('academic_histories', []) as $history) {
                $student->academicHistories()->create(array_merge($history, [
                    'created_by' => $request->user()->id,
                    'updated_by' => $request->user()->id,
                ]));
            }
        });

        return redirect()->route('students.index')->with('flash.banner', 'Student admitted.');
    }

     /**
     * Enroll a new student with course units
     */
    public function enroll(Request $request): RedirectResponse
    {
        try {
            // Get all request data
            $data = $request->all();

            // Filter out Vue form helper properties
            $allowedKeys = [
                'department_id', 'course_id', 'course_fee', 'class_id', 'admission_number',
                'first_name', 'middle_name', 'last_name', 'gender', 'date_of_birth',
                'email', 'phone', 'address', 'guardian_name',
                'guardian_relationship', 'guardian_phone', 'guardian_email',
                'guardian_address', 'admitted_on', 'status', 'academic_year_id',
                'semester_id', 'units', 'academic_histories'
            ];

            $data = array_intersect_key($data, array_flip($allowedKeys));

            Log::info('ENROLLMENT - Filtered request data:', $data);

            // Decode academic_histories if it's a JSON string
            if (isset($data['academic_histories']) && is_string($data['academic_histories'])) {
                $decoded = json_decode($data['academic_histories'], true);
                $data['academic_histories'] = is_array($decoded) ? $decoded : [];
                Log::info('ENROLLMENT - Decoded academic_histories:', $data['academic_histories']);
            }

            // Decode units if it's a JSON string
            if (isset($data['units']) && is_string($data['units'])) {
                $decoded = json_decode($data['units'], true);
                $data['units'] = is_array($decoded) ? $decoded : [];
                Log::info('ENROLLMENT - Decoded units:', $data['units']);
            }

            // Merge the decoded data back into the request
            $request->merge($data);

            // Now validate
            $validated = $request->validate([
                'department_id' => ['required', 'exists:departments,id'],
                'course_id' => ['required', 'exists:courses,id'],
                'course_fee' => ['nullable', 'numeric', 'min:0'],
                'class_id' => [
                    'required',
                    Rule::exists('classes', 'id')
                        ->where('course_id', $request->input('course_id'))
                        ->where('academic_year_id', $request->input('academic_year_id')),
                ],
                'academic_year_id' => ['required', 'exists:academic_years,id'],
                'semester_id' => [
                    'required',
                    Rule::exists('semesters', 'id')->where('academic_year_id', $request->input('academic_year_id')),
                ],
                'admission_number' => ['nullable', 'string', 'max:50'],
                'first_name' => ['required', 'string', 'max:100'],
                'middle_name' => ['nullable', 'string', 'max:100'],
                'last_name' => ['required', 'string', 'max:100'],
                'gender' => ['nullable', 'string', 'max:20'],
                'date_of_birth' => ['nullable', 'date'],
                'email' => ['nullable', 'email', 'max:255'],
                'phone' => ['nullable', 'string', 'max:30'],
                'address' => ['nullable', 'string', 'max:2000'],
                'guardian_name' => ['nullable', 'string', 'max:255'],
                'guardian_relationship' => ['nullable', 'string', 'max:80'],
                'guardian_phone' => ['nullable', 'string', 'max:30'],
                'guardian_email' => ['nullable', 'email', 'max:255'],
                'guardian_address' => ['nullable', 'string', 'max:2000'],
                'admitted_on' => ['required', 'date'],
                'status' => ['required', Rule::in(Student::STATUSES)],
                'units' => ['nullable', 'array'],
                'units.*' => ['exists:units,id'],
                'academic_histories' => ['nullable', 'array'],
                'academic_histories.*.institution_name' => ['nullable', 'string', 'max:255'],
                'academic_histories.*.qualification' => ['nullable', 'string', 'max:255'],
                'academic_histories.*.grade' => ['nullable', 'string', 'max:255'],
                'academic_histories.*.started_on' => ['nullable', 'date'],
                'academic_histories.*.completed_on' => ['nullable', 'date'],
                'academic_histories.*.notes' => ['nullable', 'string', 'max:2000'],
            ]);

            Log::info('ENROLLMENT - Validation passed:', $validated);

            DB::transaction(function () use ($request, $validated): void {
                // Prepare data for student creation
                $data = $validated;
                $data['admission_number'] = $data['admission_number'] ?: Student::nextAdmissionNumber();
                $data['created_by'] = $request->user()->id;
                $data['updated_by'] = $request->user()->id;

                // Handle photo upload - store in photo_path column
                if ($request->hasFile('photo')) {
                    $data['photo_path'] = $request->file('photo')->store('students/photos', 'public');
                    Log::info('ENROLLMENT - Photo uploaded:', ['path' => $data['photo_path']]);
                }

                // Remove non-column fields
                unset($data['academic_histories']);
                unset($data['units']);
                unset($data['academic_year_id']);
                unset($data['semester_id']);

                Log::info('ENROLLMENT - Data before student create:', $data);

                // Create the student and a linked login account when an email is available.
                $user = $this->createOrUpdateStudentUser($data);
                $student = Student::create($data + ['user_id' => $user?->id]);
                Log::info('ENROLLMENT - Student created:', $student->toArray());

                // Create enrollments for selected units
                if (!empty($validated['units'])) {
                    $units = Unit::whereIn('id', $validated['units'])
                        ->where('course_id', $validated['course_id'])
                        ->where('is_active', true)
                        ->get();

                    $registration = SemesterRegistration::create([
                        'student_id' => $student->id,
                        'class_id' => $validated['class_id'],
                        'semester_id' => $validated['semester_id'],
                        'academic_year_id' => $validated['academic_year_id'],
                        'registered_at' => now(),
                        'approved_at' => now(),
                        'approved_by' => $request->user()->id,
                        'status' => 'approved',
                        'created_by' => $request->user()->id,
                        'updated_by' => $request->user()->id,
                    ]);

                    Log::info('ENROLLMENT - Units to enroll:', ['unit_ids' => $validated['units'], 'count' => $units->count()]);

                    foreach ($units as $unit) {
                        $enrollment = Enrollment::create([
                            'semester_registration_id' => $registration->id,
                            'student_id' => $student->id,
                            'unit_id' => $unit->id,
                            'class_id' => $validated['class_id'],
                            'semester_id' => $validated['semester_id'],
                            'academic_year_id' => $validated['academic_year_id'],
                            'enrolled_on' => now()->toDateString(),
                            'status' => 'approved',
                            'created_by' => $request->user()->id,
                            'updated_by' => $request->user()->id,
                        ]);
                        Log::info('ENROLLMENT - Enrollment created:', $enrollment->toArray());
                    }
                }

                // Add academic history
                foreach ($validated['academic_histories'] ?? [] as $history) {
                    // Only create if there's an institution name
                    if (!empty($history['institution_name'])) {
                        $academicHistory = $student->academicHistories()->create(array_merge($history, [
                            'created_by' => $request->user()->id,
                            'updated_by' => $request->user()->id,
                        ]));
                        Log::info('ENROLLMENT - Academic history created:', $academicHistory->toArray());
                    }
                }
            });

            Log::info('ENROLLMENT - Completed successfully');
            return redirect()->route('students.index')->with('flash.banner', 'Student enrolled successfully.');

        } catch (\Illuminate\Validation\ValidationException $e) {
            Log::error('ENROLLMENT - Validation error:', [
                'errors' => $e->errors(),
                'request_data' => $request->all()
            ]);
            return back()->withErrors($e->errors())->withInput();
        } catch (\Exception $e) {
            Log::error('ENROLLMENT - General error:', [
                'message' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
                'request_data' => $request->all()
            ]);
            return back()->with('flash.banner', 'Error: ' . $e->getMessage())->with('flash.bannerStyle', 'danger');
        }
    }

    public function show(Student $student): Response
    {
        $this->authorize('view', $student);

        return Inertia::render('Students/Show', [
            'student' => $student->load(['department', 'course', 'class', 'academicHistories', 'user:id,name,email']),
        ]);
    }

    public function edit(Student $student): Response
    {
        $this->authorize('update', $student);

        return Inertia::render('Students/Form', array_merge($this->formData(), [
            'student' => $student->load('academicHistories'),
        ]));
    }

    public function update(UpdateStudentRequest $request, Student $student): RedirectResponse
    {
        DB::transaction(function () use ($request, $student): void {
            $data = $request->safe()->except(['photo', 'academic_histories']);
            $data['updated_by'] = $request->user()->id;

            if ($request->hasFile('photo')) {
                if ($student->photo_path) {
                    Storage::disk('public')->delete($student->photo_path);
                }

                $data['photo_path'] = $request->file('photo')->store('students/photos', 'public');
            }

            $user = $this->createOrUpdateStudentUser($data, $student);
            $student->fill($data + ['user_id' => $user?->id ?? $student->user_id])->save();
            $student->academicHistories()->delete();

            foreach ($request->validated('academic_histories', []) as $history) {
                $student->academicHistories()->create(array_merge($history, [
                    'created_by' => $request->user()->id,
                    'updated_by' => $request->user()->id,
                ]));
            }
        });

        return redirect()->route('students.show', $student)->with('flash.banner', 'Student updated.');
    }

    public function destroy(Request $request, Student $student): RedirectResponse
    {
        $this->authorize('delete', $student);

        $student->forceFill(['deleted_by' => $request->user()->id])->save();
        $student->delete();

        return redirect()->route('students.index')->with('flash.banner', 'Student deleted.');
    }

    private function formData(): array
    {
        return [
            'statuses' => Student::STATUSES,
            'departments' => Department::orderBy('name')->get(['id', 'name', 'code']),
            'courses' => Course::orderBy('name')->get(['id', 'name', 'code', 'department_id', 'fees']),
            'classes' => CollegeClass::orderBy('name')->get(['id', 'name', 'code', 'course_id']),
        ];
    }

    private function getCurrentSemester()
    {
        return Semester::where('is_current', true)->first();
    }

    private function getCurrentAcademicYear()
    {
        return AcademicYear::where('is_current', true)->first();
    }

    private function createOrUpdateStudentUser(array $data, ?Student $student = null): ?User
    {
        if (blank($data['email'] ?? null)) {
            return null;
        }

        $user = $student?->user ?: new User();
        $user->fill([
            'name' => trim("{$data['first_name']} {$data['last_name']}"),
            'email' => $data['email'],
            'status' => ($data['status'] ?? 'active') === 'active' ? 'active' : 'suspended',
            'status_reason' => ($data['status'] ?? 'active') === 'active' ? null : 'Synced from student status.',
            'password' => $user->exists ? $user->password : Hash::make('password'),

        ]);
        $user->save();

        if ($role = Role::where('name', 'Student')->first()) {
            $user->roles()->syncWithoutDetaching([$role->id]);
        }

        return $user;
    }
}

<?php

namespace App\Http\Controllers;

use App\Models\Assignment;
use App\Models\AssignmentSubmission;
use App\Models\Course;
use App\Models\Department;
use App\Models\Enrollment;
use App\Models\Examination;
use App\Models\ExaminationResult;
use App\Models\Lecturer;
use App\Models\LecturerUnitAssignment;
use App\Models\LessonTicket;
use App\Models\LessonTicketCheckin;
use App\Models\Payment;
use App\Models\SemesterRegistration;
use App\Models\Student;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;
use Inertia\Response;

class DashboardController extends Controller
{
    public function __invoke(Request $request): Response
    {
        $user = $request->user()->loadMissing('lecturer', 'student');

        if ($user->lecturer && ! $user->hasAnyPermission('classes.manage|finance.manage|users.view')) {
            return Inertia::render('Dashboard', [
                'mode' => 'lecturer',
                'dashboard' => $this->lecturerDashboard($user->lecturer, $request),
            ]);
        }

        if ($user->student && ! $user->hasAnyPermission('classes.manage|finance.manage|users.view')) {
            return Inertia::render('Dashboard', [
                'mode' => 'learner',
                'dashboard' => $this->learnerDashboard($user->student),
            ]);
        }

        return Inertia::render('Dashboard', [
            'mode' => 'admin',
            'dashboard' => $this->adminDashboard($request),
        ]);
    }

    private function adminDashboard(Request $request): array
    {
        $period = $this->dashboardPeriod($request);
        $start = $period['start'];
        $end = $period['end'];
        $previousStart = $period['previous_start'];
        $previousEnd = $period['previous_end'];
        $paymentQuery = Payment::where('status', 'confirmed')->whereBetween('payment_date', [$start, $end]);
        $previousPaymentQuery = Payment::where('status', 'confirmed')->whereBetween('payment_date', [$previousStart, $previousEnd]);
        $registrationQuery = SemesterRegistration::whereBetween('registered_at', [$start, $end]);
        $previousRegistrationQuery = SemesterRegistration::whereBetween('registered_at', [$previousStart, $previousEnd]);
        $ticketQuery = LessonTicket::whereBetween('issued_on', [$start->toDateString(), $end->toDateString()]);
        $previousTicketQuery = LessonTicket::whereBetween('issued_on', [$previousStart->toDateString(), $previousEnd->toDateString()]);
        $attendanceRows = DB::table('attendance')
            ->whereBetween('attendance_date', [$start->toDateString(), $end->toDateString()])
            ->whereNull('deleted_at')
            ->get(['status']);
        $attendanceTotal = $attendanceRows->count();
        $attendancePresent = $attendanceRows->where('status', 'present')->count();
        $expectedFees = (float) SemesterRegistration::whereBetween('registered_at', [$start, $end])->sum('course_fee');
        $currentRevenue = (float) (clone $paymentQuery)->sum('amount');
        $previousRevenue = (float) (clone $previousPaymentQuery)->sum('amount');
        $resultsRecorded = ExaminationResult::whereBetween('recorded_at', [$start, $end])->count();
        $previousResultsRecorded = ExaminationResult::whereBetween('recorded_at', [$previousStart, $previousEnd])->count();
        $ticketCheckins = LessonTicketCheckin::whereBetween('checked_in_at', [$start, $end])->count();
        $previousTicketCheckins = LessonTicketCheckin::whereBetween('checked_in_at', [$previousStart, $previousEnd])->count();
        $registrations = (clone $registrationQuery)->count();
        $previousRegistrations = (clone $previousRegistrationQuery)->count();
        $approvedRegistrations = (clone $registrationQuery)->where('status', 'approved')->count();
        $pendingRegistrations = (clone $registrationQuery)->where('status', 'pending')->count();
        $approvedEnrollments = Enrollment::whereBetween('enrolled_on', [$start->toDateString(), $end->toDateString()])->where('status', 'approved')->count();
        $activeCourses = Course::where('is_active', true)->count();
        $activeDepartments = Department::where('is_active', true)->count();
        $paymentCount = (clone $paymentQuery)->count();
        $cashPayments = (clone $paymentQuery)->where('method', 'cash')->count();
        $pendingPayments = Payment::whereBetween('payment_date', [$start, $end])->where('status', 'pending')->count();
        $ticketsIssued = (clone $ticketQuery)->count();
        $previousTicketsIssued = (clone $previousTicketQuery)->count();
        $downloadedTickets = (clone $ticketQuery)->whereNotNull('downloaded_at')->count();
        $openTickets = (clone $ticketQuery)->where('status', 'issued')->count();
        $activeLecturers = Lecturer::where('employment_status', 'active')->count();
        $lecturerAssignments = LecturerUnitAssignment::count();
        $attendanceAverage = $attendanceTotal ? round(($attendancePresent / $attendanceTotal) * 100) : 0;
        $activeExams = Examination::where('is_active', true)->whereBetween('starts_on', [$start->toDateString(), $end->toDateString()])->count();
        $assignmentSubmissions = AssignmentSubmission::whereBetween('submitted_at', [$start, $end])->count();
        $assignmentsDue = Assignment::whereBetween('due_at', [$start, $end])->count();

        return [
            'stats' => [
                ['label' => 'Active Learners', 'value' => Student::where('status', 'active')->count(), 'change' => (clone $registrationQuery)->count().' registrations in period', 'tone' => 'violet'],
                ['label' => 'Confirmed Payments', 'value' => $currentRevenue, 'change' => $this->percentChange($currentRevenue, $previousRevenue), 'tone' => 'emerald', 'money' => true],
                ['label' => 'Ticket Check-ins', 'value' => $ticketCheckins, 'change' => $this->percentChange($ticketCheckins, $previousTicketCheckins), 'tone' => 'blue'],
                ['label' => 'Results Recorded', 'value' => $resultsRecorded, 'change' => $this->percentChange($resultsRecorded, $previousResultsRecorded), 'tone' => 'amber'],
            ],
            'charts' => [
                [
                    'title' => 'Registration Trend',
                    'subtitle' => '',
                    'type' => 'line',
                    'tone' => 'violet',
                    'items' => $this->dateSeries(SemesterRegistration::whereBetween('registered_at', [$start, $end]), 'registered_at', $start, $end),
                ],
                [
                    'title' => 'Students by Department',
                    'subtitle' => '',
                    'type' => 'pie',
                    'tone' => 'blue',
                    'items' => $this->studentsByDepartment(),
                ],
                [
                    'title' => 'Finance Collection',
                    'subtitle' => '',
                    'type' => 'doughnut',
                    'tone' => 'emerald',
                    'items' => collect([
                        ['label' => 'Collected', 'value' => $currentRevenue],
                        ['label' => 'Outstanding', 'value' => max($expectedFees - $currentRevenue, 0)],
                        ['label' => 'Pending', 'value' => (float) $pendingPayments],
                    ]),
                ],
                [
                    'title' => 'Tickets by Session',
                    'subtitle' => '',
                    'type' => 'bar',
                    'tone' => 'amber',
                    'items' => $this->ticketsBySession($start, $end),
                ],
            ],
            'overview' => [
                [
                    'title' => 'Academic Activity',
                    'tone' => 'violet',
                    'chart' => ['type' => 'bars'],
                    'items' => [
                        ['label' => 'Registrations', 'value' => $registrations, 'raw' => $registrations, 'detail' => $this->percentChange($registrations, $previousRegistrations)],
                        ['label' => 'Approved registrations', 'value' => $approvedRegistrations, 'raw' => $approvedRegistrations, 'detail' => $pendingRegistrations.' pending'],
                        ['label' => 'Approved enrollments', 'value' => $approvedEnrollments, 'raw' => $approvedEnrollments, 'detail' => 'Units/course selections'],
                        ['label' => 'Active courses', 'value' => $activeCourses, 'raw' => $activeCourses, 'detail' => $activeDepartments.' active departments'],
                    ],
                ],
                [
                    'title' => 'Finance Health',
                    'tone' => 'emerald',
                    'chart' => ['type' => 'doughnut', 'primary' => $currentRevenue, 'total' => max($expectedFees, $currentRevenue)],
                    'items' => [
                        ['label' => 'Expected fees', 'value' => $this->moneyValue($expectedFees), 'raw' => $expectedFees, 'detail' => 'From period registrations'],
                        ['label' => 'Collected', 'value' => $this->moneyValue($currentRevenue), 'raw' => $currentRevenue, 'detail' => $expectedFees > 0 ? round(($currentRevenue / $expectedFees) * 100).'% of expected' : 'No expected fees'],
                        ['label' => 'Payments count', 'value' => $paymentCount, 'raw' => $paymentCount, 'detail' => $cashPayments.' cash payments'],
                        ['label' => 'Pending payments', 'value' => $pendingPayments, 'raw' => $pendingPayments, 'detail' => 'Needs confirmation'],
                    ],
                ],
                [
                    'title' => 'Lesson Access',
                    'tone' => 'blue',
                    'chart' => ['type' => 'doughnut', 'primary' => $ticketCheckins, 'total' => max($ticketsIssued, $ticketCheckins)],
                    'items' => [
                        ['label' => 'Tickets issued', 'value' => $ticketsIssued, 'raw' => $ticketsIssued, 'detail' => $this->percentChange($ticketsIssued, $previousTicketsIssued)],
                        ['label' => 'Downloaded tickets', 'value' => $downloadedTickets, 'raw' => $downloadedTickets, 'detail' => 'Ready for lesson entry'],
                        ['label' => 'Checked in', 'value' => $ticketCheckins, 'raw' => $ticketCheckins, 'detail' => 'Scanned at lesson entry'],
                        ['label' => 'Open tickets', 'value' => $openTickets, 'raw' => $openTickets, 'detail' => 'Not yet used/cancelled'],
                    ],
                ],
                [
                    'title' => 'Teaching & Results',
                    'tone' => 'amber',
                    'chart' => ['type' => 'bars'],
                    'items' => [
                        ['label' => 'Lecturers', 'value' => $activeLecturers, 'raw' => $activeLecturers, 'detail' => $lecturerAssignments.' unit assignments'],
                        ['label' => 'Attendance avg.', 'value' => $attendanceAverage.'%', 'raw' => $attendanceAverage, 'detail' => $attendanceTotal.' attendance records'],
                        ['label' => 'Active exams', 'value' => $activeExams, 'raw' => $activeExams, 'detail' => $resultsRecorded.' results recorded'],
                        ['label' => 'Assignment submissions', 'value' => $assignmentSubmissions, 'raw' => $assignmentSubmissions, 'detail' => $assignmentsDue.' assignments due'],
                    ],
                ],
            ],
            'rankings' => [
                'title' => 'Course Load',
                'items' => $this->courseLoad($start, $end),
            ],
            'attention' => [
                'title' => 'Needs Attention',
                'items' => $this->needsAttention($start, $end),
            ],
            'recentActivities' => $this->recentActivities($start, $end),
            'quickAccess' => [
                ['label' => 'Academic Years', 'route' => 'academics.settings.index', 'tone' => 'violet'],
                ['label' => 'Semesters', 'route' => 'academics.settings.index', 'tone' => 'blue'],
                ['label' => 'Courses', 'route' => 'academics.courses.index', 'tone' => 'red'],
                ['label' => 'Examinations', 'route' => 'academics.examinations.index', 'tone' => 'amber'],
                ['label' => 'Payments', 'route' => 'finance.payments.index', 'tone' => 'emerald'],
                ['label' => 'Reports', 'route' => 'academics.results.index', 'tone' => 'sky'],
            ],
            'filters' => [
                'period' => $period['key'],
                'start_date' => $start->toDateString(),
                'end_date' => $end->toDateString(),
            ],
        ];
    }

    private function dashboardPeriod(Request $request): array
    {
        $key = $request->input('period', 'this_year');
        $now = now();

        [$start, $end] = match ($key) {
            'last_year' => [$now->copy()->subYear()->startOfYear(), $now->copy()->subYear()->endOfYear()],
            'this_month' => [$now->copy()->startOfMonth(), $now->copy()->endOfMonth()],
            'last_30_days' => [$now->copy()->subDays(29)->startOfDay(), $now->copy()->endOfDay()],
            'custom' => [
                Carbon::parse($request->input('start_date', $now->copy()->startOfYear()->toDateString()))->startOfDay(),
                Carbon::parse($request->input('end_date', $now->copy()->endOfDay()->toDateString()))->endOfDay(),
            ],
            default => [$now->copy()->startOfYear(), $now->copy()->endOfYear()],
        };

        if ($start->gt($end)) {
            [$start, $end] = [$end->copy()->startOfDay(), $start->copy()->endOfDay()];
        }

        $days = max(1, $start->diffInDays($end) + 1);

        return [
            'key' => in_array($key, ['this_year', 'last_year', 'this_month', 'last_30_days', 'custom'], true) ? $key : 'this_year',
            'start' => $start,
            'end' => $end,
            'previous_start' => $start->copy()->subDays($days),
            'previous_end' => $start->copy()->subDay()->endOfDay(),
        ];
    }

    private function lecturerDashboard(Lecturer $lecturer, Request $request): array
    {
        $period = $this->dashboardPeriod($request);
        $start = $period['start'];
        $end = $period['end'];
        $assignments = LecturerUnitAssignment::query()
            ->with(['unit:id,course_id,code,name', 'unit.course:id,code,name', 'class:id,code,name'])
            ->where('lecturer_id', $lecturer->id)
            ->latest()
            ->get();
        $unitIds = $assignments->pluck('unit_id')->unique()->values();
        $courseIds = $assignments->pluck('unit.course_id')->filter()->unique()->values();
        $classIds = $assignments->pluck('class_id')->unique()->values();
        $studentCount = Enrollment::whereIn('unit_id', $unitIds)->whereIn('class_id', $classIds)->distinct('student_id')->count('student_id');
        $attendanceRows = DB::table('attendance')
            ->where('lecturer_id', $lecturer->id)
            ->whereBetween('attendance_date', [$start->toDateString(), $end->toDateString()])
            ->whereNull('deleted_at')
            ->get(['status']);
        $attendanceTotal = $attendanceRows->count();
        $attendanceAverage = $attendanceTotal
            ? round(($attendanceRows->where('status', 'present')->count() / $attendanceTotal) * 100)
            : 0;

        return [
            'lecturerName' => trim(($lecturer->title ? $lecturer->title.' ' : '').$lecturer->first_name.' '.$lecturer->last_name),
            'stats' => [
                ['label' => 'Courses', 'value' => $courseIds->count(), 'tone' => 'blue'],
                ['label' => 'Students', 'value' => $studentCount, 'tone' => 'amber'],
                ['label' => 'Attendance Avg.', 'value' => $attendanceAverage.'%', 'tone' => 'red'],
                ['label' => 'Assignments', 'value' => Assignment::whereIn('lecturer_unit_assignment_id', $assignments->pluck('id'))->whereBetween('due_at', [$start, $end])->count(), 'tone' => 'violet'],
            ],
            'courses' => $this->lecturerCourses($assignments),
            'attendance' => $this->lecturerAttendance($assignments, $start, $end),
            'pendingAssignments' => $this->lecturerAssignments($assignments->pluck('id'), $start, $end),
            'upcomingExams' => $this->lecturerExams($unitIds, $courseIds, $start, $end),
            'filters' => [
                'period' => $period['key'],
                'start_date' => $start->toDateString(),
                'end_date' => $end->toDateString(),
            ],
        ];
    }

    private function learnerDashboard(Student $student): array
    {
        return [
            'studentName' => trim($student->first_name.' '.$student->last_name),
            'quickAccess' => [
                ['label' => 'My Courses', 'route' => 'academics.courses.index', 'tone' => 'blue'],
                ['label' => 'My Payments', 'route' => 'finance.payments.index', 'tone' => 'emerald'],
                ['label' => 'My Results', 'route' => 'academics.results.index', 'tone' => 'violet'],
                ['label' => 'My Tickets', 'route' => 'finance.tickets.index', 'tone' => 'amber'],
            ],
        ];
    }

    private function lecturerCourses(Collection $assignments): Collection
    {
        return $assignments
            ->groupBy('unit.course_id')
            ->filter(fn (Collection $items, $courseId) => $courseId)
            ->take(5)
            ->map(function (Collection $items) {
                $first = $items->first();
                $studentCount = Enrollment::whereIn('unit_id', $items->pluck('unit_id'))->whereIn('class_id', $items->pluck('class_id'))->distinct('student_id')->count('student_id');

                return [
                    'code' => $first->unit?->course?->code,
                    'name' => $first->unit?->course?->name,
                    'students' => $studentCount,
                    'units' => $items->map(fn (LecturerUnitAssignment $assignment) => [
                        'code' => $assignment->unit?->code,
                        'name' => $assignment->unit?->name,
                        'class' => trim(($assignment->class?->code ?: '').' - '.($assignment->class?->name ?: ''), ' -'),
                    ])->values(),
                ];
            })
            ->values();
    }

    private function lecturerAttendance(Collection $assignments, Carbon $start, Carbon $end): Collection
    {
        return $assignments
            ->take(6)
            ->map(function (LecturerUnitAssignment $assignment) use ($start, $end) {
                $rows = DB::table('attendance')
                    ->where('unit_id', $assignment->unit_id)
                    ->where('class_id', $assignment->class_id)
                    ->whereBetween('attendance_date', [$start->toDateString(), $end->toDateString()])
                    ->whereNull('deleted_at')
                    ->get(['status']);
                $total = $rows->count();

                return [
                    'label' => $assignment->unit?->code ?: 'Unit',
                    'value' => $total ? round(($rows->where('status', 'present')->count() / $total) * 100) : 0,
                ];
            })
            ->values();
    }

    private function lecturerAssignments(Collection $assignmentIds, Carbon $start, Carbon $end): Collection
    {
        return Assignment::query()
            ->withCount('submissions')
            ->with('lecturerUnitAssignment.unit:id,code,name')
            ->whereIn('lecturer_unit_assignment_id', $assignmentIds)
            ->whereIn('status', ['draft', 'published'])
            ->whereBetween('due_at', [$start, $end])
            ->orderBy('due_at')
            ->limit(4)
            ->get()
            ->map(fn (Assignment $assignment) => [
                'title' => $assignment->title,
                'unit' => $assignment->lecturerUnitAssignment?->unit?->code,
                'due' => $assignment->due_at?->format('M j, Y'),
                'submissions' => $assignment->submissions_count,
            ]);
    }

    private function lecturerExams(Collection $unitIds, Collection $courseIds, Carbon $start, Carbon $end): Collection
    {
        return Examination::query()
            ->with(['unit:id,code,name', 'course:id,code,name'])
            ->where(fn ($query) => $query
                ->whereIn('unit_id', $unitIds)
                ->orWhereIn('course_id', $courseIds))
            ->whereBetween('starts_on', [$start->toDateString(), $end->toDateString()])
            ->where('is_active', true)
            ->orderBy('starts_on')
            ->limit(4)
            ->get()
            ->map(fn (Examination $exam) => [
                'title' => $exam->name,
                'target' => $exam->unit?->code ?: $exam->course?->code,
                'date' => $exam->starts_on?->format('M j, Y'),
                'max_score' => $exam->max_score,
            ]);
    }

    private function courseLoad(Carbon $start, Carbon $end): Collection
    {
        return Course::query()
            ->withCount(['semesterRegistrations as registrations_count' => fn ($query) => $query
                ->whereBetween('registered_at', [$start, $end])])
            ->withCount(['enrollments as enrollments_count' => fn ($query) => $query
                ->whereBetween('enrolled_on', [$start->toDateString(), $end->toDateString()])])
            ->orderByDesc('registrations_count')
            ->orderByDesc('enrollments_count')
            ->limit(5)
            ->get(['id', 'code', 'name'])
            ->map(fn (Course $course) => [
                'label' => trim(($course->code ?: '').' - '.$course->name, ' -'),
                'value' => $course->registrations_count,
                'detail' => $course->enrollments_count.' approved/unit enrollments',
            ]);
    }

    private function dateSeries($query, string $column, Carbon $start, Carbon $end): Collection
    {
        $useDaily = $start->diffInDays($end) <= 45;
        $format = $useDaily ? 'M j' : 'M';
        $keyFormat = $useDaily ? 'Y-m-d' : 'Y-m';
        $cursor = $start->copy()->startOfDay();
        $periods = collect();

        while ($cursor->lte($end)) {
            $periods->push([
                'key' => $cursor->format($keyFormat),
                'label' => $cursor->format($format),
                'value' => 0,
            ]);

            $useDaily ? $cursor->addDay() : $cursor->addMonthNoOverflow();
        }

        $counts = (clone $query)
            ->get([$column])
            ->groupBy(fn ($row) => optional($row->{$column})->format($keyFormat))
            ->map->count();

        return $periods
            ->map(fn (array $period) => [
                'label' => $period['label'],
                'value' => $counts[$period['key']] ?? 0,
            ])
            ->values();
    }

    private function studentsByDepartment(): Collection
    {
        return DB::table('departments')
            ->leftJoin('students', function ($join) {
                $join->on('students.department_id', '=', 'departments.id')
                    ->whereNull('students.deleted_at');
            })
            ->whereNull('departments.deleted_at')
            ->groupBy('departments.id', 'departments.name')
            ->orderByDesc(DB::raw('count(students.id)'))
            ->limit(6)
            ->get([
                'departments.name as label',
                DB::raw('count(students.id) as value'),
            ])
            ->map(fn ($row) => [
                'label' => $row->label,
                'value' => (int) $row->value,
            ]);
    }

    private function ticketsBySession(Carbon $start, Carbon $end): Collection
    {
        $rows = LessonTicket::query()
            ->whereBetween('issued_on', [$start->toDateString(), $end->toDateString()])
            ->selectRaw('session_type, count(*) as total')
            ->groupBy('session_type')
            ->orderByDesc('total')
            ->get();

        if ($rows->isEmpty()) {
            return collect([
                ['label' => 'Theoretical', 'value' => 0],
                ['label' => 'Practical', 'value' => 0],
            ]);
        }

        return $rows->map(fn (LessonTicket $row) => [
            'label' => str($row->session_type ?: 'General')->headline()->toString(),
            'value' => (int) $row->total,
        ]);
    }

    private function needsAttention(Carbon $start, Carbon $end): Collection
    {
        return collect([
            [
                'label' => 'Pending registrations',
                'value' => SemesterRegistration::whereBetween('registered_at', [$start, $end])->where('status', 'pending')->count(),
                'detail' => 'Approve, drop, or transfer',
                'tone' => 'amber',
            ],
            [
                'label' => 'Unconfirmed payments',
                'value' => Payment::whereBetween('payment_date', [$start, $end])->where('status', 'pending')->count(),
                'detail' => 'Finance follow-up',
                'tone' => 'emerald',
            ],
            [
                'label' => 'Undownloaded tickets',
                'value' => LessonTicket::whereBetween('issued_on', [$start->toDateString(), $end->toDateString()])->whereNull('downloaded_at')->count(),
                'detail' => 'Learners may miss access',
                'tone' => 'blue',
            ],
            [
                'label' => 'Ungraded submissions',
                'value' => AssignmentSubmission::whereBetween('submitted_at', [$start, $end])->whereNull('graded_at')->count(),
                'detail' => 'Lecturer action needed',
                'tone' => 'violet',
            ],
        ]);
    }

    private function recentActivities(Carbon $start, Carbon $end): Collection
    {
        return collect()
            ->merge(Student::with(['course:id,code,name', 'department:id,name'])
                ->whereBetween('created_at', [$start, $end])
                ->latest()
                ->limit(4)
                ->get()
                ->map(fn (Student $student) => [
                'type' => 'Student registered',
                'title' => trim($student->first_name.' '.$student->last_name),
                'actor' => $this->userName($student->created_by) ?: 'Admissions',
                'target' => trim($student->first_name.' '.$student->last_name),
                'meta' => collect([
                    $student->admission_number ? 'Adm '.$student->admission_number : null,
                    $student->course?->code ?: $student->course?->name,
                    $student->department?->name,
                ])->filter()->join(' / '),
                'reference' => 'Learner profile',
                'time' => $student->created_at,
                'tone' => 'blue',
            ]))
            ->merge(Payment::with(['student:id,first_name,last_name,admission_number', 'course:id,code,name'])
                ->whereBetween('payment_date', [$start, $end])
                ->latest()
                ->limit(4)
                ->get()
                ->map(fn (Payment $payment) => [
                'type' => 'Payment received',
                'title' => $payment->payment_reference ?: 'Payment #'.$payment->id,
                'actor' => $this->userName($payment->created_by) ?: 'Finance desk',
                'target' => $this->studentName($payment->student),
                'meta' => collect([
                    $this->moneyValue((float) $payment->amount),
                    str($payment->method ?: 'payment')->headline()->toString(),
                    $payment->course?->code ?: $payment->course?->name,
                ])->filter()->join(' / '),
                'reference' => $payment->payment_reference ?: 'Payment #'.$payment->id,
                'time' => $payment->created_at,
                'tone' => 'emerald',
            ]))
            ->merge(Examination::with(['course:id,code,name', 'subcourse:id,code,name', 'unit:id,code,name'])
                ->whereBetween('created_at', [$start, $end])
                ->latest()
                ->limit(4)
                ->get()
                ->map(fn (Examination $exam) => [
                'type' => 'Exam scheduled',
                'title' => $exam->name,
                'actor' => $this->userName($exam->created_by) ?: 'Exams office',
                'target' => $exam->unit?->name ?: ($exam->subcourse?->name ?: $exam->course?->name),
                'meta' => collect([
                    $exam->starts_on?->format('M d, Y'),
                    $exam->max_score ? 'Max '.$exam->max_score : null,
                    $exam->scope_type ? str($exam->scope_type)->headline()->toString() : null,
                ])->filter()->join(' / '),
                'reference' => $exam->course?->code ?: ($exam->unit?->code ?: 'Examination'),
                'time' => $exam->created_at,
                'tone' => 'violet',
            ]))
            ->merge(LessonTicketCheckin::with([
                    'checkedInBy:id,name',
                    'ticket.student:id,first_name,last_name,admission_number',
                    'ticket.course:id,code,name',
                    'ticket.unit:id,code,name',
                ])
                ->whereBetween('checked_in_at', [$start, $end])
                ->latest('checked_in_at')
                ->limit(4)
                ->get()
                ->map(fn (LessonTicketCheckin $checkin) => [
                    'type' => 'Ticket checked in',
                    'title' => $this->studentName($checkin->ticket?->student) ?: 'Lesson ticket',
                    'actor' => $checkin->checkedInBy?->name ?: 'Scanner',
                    'target' => $this->studentName($checkin->ticket?->student),
                    'meta' => collect([
                        $checkin->ticket?->student?->admission_number,
                        $checkin->ticket?->unit?->code ?: $checkin->ticket?->course?->code,
                        $checkin->ticket?->session_type ? str($checkin->ticket->session_type)->headline()->toString() : null,
                    ])->filter()->join(' / '),
                    'reference' => $checkin->ticket?->ticket_number ?: 'Ticket #'.$checkin->lesson_ticket_id,
                    'time' => $checkin->checked_in_at,
                    'tone' => 'amber',
                ]))
            ->merge(ExaminationResult::with(['student:id,first_name,last_name,admission_number', 'examination:id,name,course_id,unit_id'])
                ->whereBetween('recorded_at', [$start, $end])
                ->latest('recorded_at')
                ->limit(4)
                ->get()
                ->map(fn (ExaminationResult $result) => [
                    'type' => 'Result recorded',
                    'title' => $result->examination?->name ?: 'Learner result',
                    'actor' => $this->userName($result->recorded_by ?: $result->created_by) ?: 'Exams office',
                    'target' => $this->studentName($result->student),
                    'meta' => collect([
                        $result->student?->admission_number,
                        'Score '.$result->score,
                        $result->grade ? 'Grade '.$result->grade : null,
                    ])->filter()->join(' / '),
                    'reference' => $result->examination?->name ?: 'Result #'.$result->id,
                    'time' => $result->recorded_at,
                    'tone' => 'red',
            ]))
            ->sortByDesc('time')
            ->take(8)
            ->values()
            ->map(fn (array $activity) => $activity + ['time_label' => $activity['time']?->diffForHumans()]);
    }

    private function userName(null|int|string $userId): ?string
    {
        if (! $userId) {
            return null;
        }

        return DB::table('users')->where('id', $userId)->value('name');
    }

    private function studentName(?Student $student): string
    {
        return trim(($student?->first_name ?: '').' '.($student?->last_name ?: '')) ?: 'Learner';
    }

    private function moneyValue(float|int $value): string
    {
        return 'KES '.number_format((float) $value);
    }

    private function percentChange(float|int $current, float|int $previous): string
    {
        if ((float) $previous === 0.0) {
            return $current > 0 ? '+100% from previous period' : '0% from previous period';
        }

        $change = (($current - $previous) / $previous) * 100;

        return ($change >= 0 ? '+' : '').number_format($change, 1).'% from previous period';
    }
}

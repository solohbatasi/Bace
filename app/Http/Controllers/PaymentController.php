<?php

namespace App\Http\Controllers;

use App\Models\Payment;
use App\Models\Student;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\Validation\ValidationException;
use Illuminate\Validation\Rule;
use Inertia\Inertia;
use Inertia\Response;

class PaymentController extends Controller
{
    public function index(Request $request): Response
    {
        abort_unless($request->user()->hasPermission('finance.view'), 403);

        $filters = $request->only(['search', 'student_id', 'course_id']);

        return Inertia::render('Finance/Payments', [
            'canManage' => $request->user()->hasPermission('finance.manage'),
            'filters' => $filters,
            'paymentDate' => now()->toDateString(),
            'students' => Student::query()
                ->with([
                    'course:id,code,name,fees',
                    'enrollments.unit.course:id,code,name,fees',
                    'semesterRegistrations:id,student_id,class_id,course_fee',
                    'semesterRegistrations.class:id,course_id',
                    'semesterRegistrations.class.course:id,code,name,fees',
                    'payments:id,student_id,course_id,amount,status',
                ])
                ->orderBy('admission_number')
                ->get(['id', 'admission_number', 'first_name', 'last_name', 'course_id', 'course_fee'])
                ->map(fn (Student $student) => $this->studentOption($student)),
            'payments' => Payment::query()
                ->with(['student:id,admission_number,first_name,last_name', 'course:id,code,name,fees'])
                ->when($filters['search'] ?? null, fn ($query, $search) => $query->where(fn ($query) => $query
                    ->where('payment_reference', 'like', "%{$search}%")
                    ->orWhere('external_transaction_id', 'like', "%{$search}%")
                    ->orWhereHas('student', fn ($query) => $query
                        ->where('admission_number', 'like', "%{$search}%")
                        ->orWhere('first_name', 'like', "%{$search}%")
                        ->orWhere('last_name', 'like', "%{$search}%"))))
                ->when($filters['student_id'] ?? null, fn ($query, $studentId) => $query->where('student_id', $studentId))
                ->when($filters['course_id'] ?? null, fn ($query, $courseId) => $query->where('course_id', $courseId))
                ->latest('payment_date')
                ->latest()
                ->paginate(20)
                ->withQueryString(),
            'summary' => [
                'total_paid' => Payment::where('status', 'confirmed')->sum('amount'),
                'payments_count' => Payment::count(),
                'today_paid' => Payment::whereDate('payment_date', now()->toDateString())->where('status', 'confirmed')->sum('amount'),
            ],
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        abort_unless($request->user()->hasPermission('finance.manage'), 403);

        $data = $this->paymentData($request);
        $data['payment_reference'] = $data['payment_reference'] ?: $this->nextReference();

        Payment::create($data + [
            'currency' => 'KES',
            'created_by' => $request->user()->id,
            'updated_by' => $request->user()->id,
        ]);

        return back()->with('flash.banner', 'Payment recorded.');
    }

    public function update(Request $request, Payment $payment): RedirectResponse
    {
        abort_unless($request->user()->hasPermission('finance.manage'), 403);

        $data = $this->paymentData($request, $payment);
        $data['payment_reference'] = $data['payment_reference'] ?: $payment->payment_reference;

        $payment->update($data + [
            'updated_by' => $request->user()->id,
        ]);

        return back()->with('flash.banner', 'Payment updated.');
    }

    public function destroy(Request $request, Payment $payment): RedirectResponse
    {
        abort_unless($request->user()->hasPermission('finance.manage'), 403);

        $payment->forceFill([
            'deleted_by' => $request->user()->id,
        ])->save();
        $payment->delete();

        return back()->with('flash.banner', 'Payment deleted.');
    }

    private function studentOption(Student $student): array
    {
        $paidByCourse = $student->payments
            ->where('status', 'confirmed')
            ->groupBy('course_id')
            ->map(fn (Collection $payments) => (float) $payments->sum('amount'));

        $courses = collect([$student->course])
            ->merge($student->enrollments->pluck('unit.course'))
            ->merge($student->semesterRegistrations->pluck('class.course'))
            ->filter()
            ->unique('id')
            ->values()
            ->map(function ($course) use ($paidByCourse, $student) {
                $registration = $student->semesterRegistrations
                    ->first(fn ($registration) => (int) $registration->class?->course_id === (int) $course->id);
                $fees = match (true) {
                    (int) $course->id === (int) $student->course_id && $student->course_fee !== null => (float) $student->course_fee,
                    $registration?->course_fee !== null => (float) $registration->course_fee,
                    default => (float) $course->fees,
                };

                return [
                    'id' => $course->id,
                    'code' => $course->code,
                    'name' => $course->name,
                    'fees' => $fees,
                    'paid' => (float) ($paidByCourse[$course->id] ?? 0),
                    'balance' => max($fees - (float) ($paidByCourse[$course->id] ?? 0), 0),
                ];
            });

        return [
            'id' => $student->id,
            'admission_number' => $student->admission_number,
            'name' => trim($student->first_name.' '.$student->last_name),
            'courses' => $courses,
        ];
    }

    private function studentCourseIds(Student $student): Collection
    {
        return collect([$student->course_id])
            ->merge($student->enrollments->pluck('unit.course_id'))
            ->merge($student->semesterRegistrations->pluck('class.course_id'))
            ->filter()
            ->unique()
            ->values();
    }

    private function paymentData(Request $request, ?Payment $payment = null): array
    {
        $data = $request->validate([
            'student_id' => ['required', 'exists:students,id'],
            'course_id' => ['required', 'exists:courses,id'],
            'payment_reference' => ['nullable', 'max:80', Rule::unique('payments')->ignore($payment)->whereNull('deleted_at')],
            'payment_date' => ['required', 'date'],
            'method' => ['required', 'max:40'],
            'amount' => ['required', 'numeric', 'min:1'],
            'status' => ['required', Rule::in(['pending', 'confirmed', 'failed', 'refunded'])],
            'external_transaction_id' => ['nullable', 'max:255'],
            'notes' => ['nullable', 'string'],
        ]);

        $student = Student::with(['course:id', 'enrollments.unit:id,course_id', 'semesterRegistrations.class:id,course_id'])->findOrFail($data['student_id']);
        if (! $this->studentCourseIds($student)->contains((int) $data['course_id'])) {
            throw ValidationException::withMessages([
                'course_id' => 'The selected learner is not enrolled in that course.',
            ]);
        }

        return $data;
    }

    private function nextReference(): string
    {
        do {
            $reference = 'PAY-'.now()->format('YmdHis').'-'.random_int(100, 999);
        } while (Payment::where('payment_reference', $reference)->exists());

        return $reference;
    }
}

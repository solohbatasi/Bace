<?php

namespace App\Http\Controllers;

use App\Models\OrganisationSetting;
use App\Models\Course;
use App\Models\LessonTicket;
use App\Models\LessonTicketRule;
use App\Models\Student;
use App\Models\Unit;
use BaconQrCode\Renderer\Image\SvgImageBackEnd;
use BaconQrCode\Renderer\ImageRenderer;
use BaconQrCode\Renderer\RendererStyle\RendererStyle;
use BaconQrCode\Writer;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response as HttpResponse;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\URL;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Inertia\Inertia;
use Inertia\Response;

class LessonTicketController extends Controller
{
    public function index(Request $request): Response
    {
        abort_unless($request->user()->hasAnyPermission('tickets.view|finance.view'), 403);

        $filters = $request->only(['search', 'course_id', 'student_id', 'status']);

        $rules = LessonTicketRule::query()
            ->with(['course:id,parent_course_id,code,name,fees', 'course.parentCourse:id,code,name', 'unit:id,course_id,code,name'])
            ->withCount('tickets')
            ->when($filters['search'] ?? null, fn ($query, $search) => $query->where(fn ($query) => $query
                ->where('name', 'like', "%{$search}%")
                ->orWhere('session_type', 'like', "%{$search}%")
                ->orWhereHas('course', fn ($query) => $query
                    ->where('code', 'like', "%{$search}%")
                    ->orWhere('name', 'like', "%{$search}%"))
                ->orWhereHas('unit', fn ($query) => $query
                    ->where('code', 'like', "%{$search}%")
                    ->orWhere('name', 'like', "%{$search}%"))))
            ->when($filters['course_id'] ?? null, fn ($query, $courseId) => $query->where('course_id', $courseId))
            ->latest()
            ->paginate(10, ['*'], 'rules_page')
            ->withQueryString()
            ->through(fn (LessonTicketRule $rule) => $this->rulePayload($rule));

        $tickets = LessonTicket::query()
            ->with([
                'rule:id,name,pricing_type,pricing_value',
                'student:id,admission_number,first_name,last_name',
                'course:id,code,name',
                'unit:id,course_id,code,name',
            ])
            ->when($filters['search'] ?? null, fn ($query, $search) => $query->where(fn ($query) => $query
                ->where('ticket_number', 'like', "%{$search}%")
                ->orWhereHas('student', fn ($query) => $query
                    ->where('admission_number', 'like', "%{$search}%")
                    ->orWhere('first_name', 'like', "%{$search}%")
                    ->orWhere('last_name', 'like', "%{$search}%"))
                ->orWhereHas('course', fn ($query) => $query
                    ->where('code', 'like', "%{$search}%")
                    ->orWhere('name', 'like', "%{$search}%"))))
            ->when($filters['course_id'] ?? null, fn ($query, $courseId) => $query->where('course_id', $courseId))
            ->when($filters['student_id'] ?? null, fn ($query, $studentId) => $query->where('student_id', $studentId))
            ->when($filters['status'] ?? null, fn ($query, $status) => $query->where('status', $status))
            ->latest('issued_on')
            ->latest()
            ->paginate(10, ['*'], 'tickets_page')
            ->withQueryString()
            ->through(fn (LessonTicket $ticket) => $this->ticketPayload($ticket));

        return Inertia::render('Finance/Tickets', [
            'canAdd' => $request->user()->hasAnyPermission('tickets.add|finance.manage'),
            'canEdit' => $request->user()->hasAnyPermission('tickets.edit|finance.manage'),
            'canDelete' => $request->user()->hasAnyPermission('tickets.delete|finance.manage'),
            'filters' => $filters,
            'ticketDate' => now()->toDateString(),
            'courses' => Course::query()
                ->with('parentCourse:id,code,name')
                ->where('is_active', true)
                ->orderBy('name')
                ->get(['id', 'parent_course_id', 'department_id', 'code', 'name', 'fees', 'has_units']),
            'units' => Unit::query()
                ->with(['course:id,parent_course_id,code,name'])
                ->where('is_active', true)
                ->orderBy('name')
                ->get(['id', 'course_id', 'code', 'name']),
            'students' => Student::query()
                ->with([
                    'course:id,parent_course_id,code,name,fees',
                    'subcourse:id,parent_course_id,code,name,fees',
                    'enrollments.unit:id,course_id,code,name',
                    'enrollments.unit.course:id,parent_course_id,code,name,fees',
                    'semesterRegistrations:id,student_id,course_id,subcourse_id,course_fee',
                    'semesterRegistrations.course:id,parent_course_id,code,name,fees',
                    'semesterRegistrations.subcourse:id,parent_course_id,code,name,fees',
                    'payments:id,student_id,course_id,amount,status',
                ])
                ->orderBy('admission_number')
                ->get(['id', 'admission_number', 'first_name', 'last_name', 'course_id', 'subcourse_id', 'course_fee'])
                ->map(fn (Student $student) => $this->studentOption($student, $this->activeRules())),
            'rules' => $rules,
            'ticketRules' => LessonTicketRule::query()
                ->with(['course:id,parent_course_id,code,name,fees', 'course.parentCourse:id,code,name', 'unit:id,course_id,code,name'])
                ->where('is_active', true)
                ->orderBy('name')
                ->get()
                ->map(fn (LessonTicketRule $rule) => $this->rulePayload($rule)),
            'tickets' => $tickets,
            'summary' => [
                'active_rules' => LessonTicketRule::where('is_active', true)->count(),
                'issued_tickets' => LessonTicket::count(),
                'downloaded_tickets' => LessonTicket::whereNotNull('downloaded_at')->count(),
            ],
        ]);
    }

    public function storeRule(Request $request): RedirectResponse
    {
        abort_unless($request->user()->hasAnyPermission('tickets.add|finance.manage'), 403);

        LessonTicketRule::create($this->ruleData($request) + [
            'created_by' => $request->user()->id,
            'updated_by' => $request->user()->id,
        ]);

        return back()->with('flash.banner', 'Ticket rule created.');
    }

    public function updateRule(Request $request, LessonTicketRule $rule): RedirectResponse
    {
        abort_unless($request->user()->hasAnyPermission('tickets.edit|finance.manage'), 403);

        $rule->update($this->ruleData($request) + ['updated_by' => $request->user()->id]);

        return back()->with('flash.banner', 'Ticket rule updated.');
    }

    public function destroyRule(Request $request, LessonTicketRule $rule): RedirectResponse
    {
        abort_unless($request->user()->hasAnyPermission('tickets.delete|finance.manage'), 403);

        $rule->forceFill(['deleted_by' => $request->user()->id])->save();
        $rule->delete();

        return back()->with('flash.banner', 'Ticket rule deleted.');
    }

    public function issue(Request $request): RedirectResponse
    {
        abort_unless($request->user()->hasAnyPermission('tickets.add|finance.manage'), 403);

        $data = $request->validate([
            'student_id' => ['required', 'exists:students,id'],
            'target_key' => ['nullable', 'string', 'max:80'],
            'issued_on' => ['required', 'date'],
            'notes' => ['nullable', 'string'],
        ]);

        $student = Student::with([
            'course:id,parent_course_id,code,name,fees',
            'subcourse:id,parent_course_id,code,name,fees',
            'enrollments.unit:id,course_id,code,name',
            'enrollments.unit.course:id,parent_course_id,code,name,fees',
            'semesterRegistrations:id,student_id,course_id,subcourse_id',
            'semesterRegistrations.course:id,parent_course_id,code,name,fees',
            'semesterRegistrations.subcourse:id,parent_course_id,code,name,fees',
            'payments:id,student_id,course_id,amount,status',
        ])->findOrFail($data['student_id']);

        $targets = collect($this->studentOption($student, $this->activeRules())['ticket_targets']);
        $target = $this->issueTarget($targets, $data['target_key'] ?? null);

        if (! $target) {
            throw ValidationException::withMessages([
                'target_key' => 'Select the course, subcourse, or unit this learner needs a ticket for.',
            ]);
        }

        $created = 0;
        $insufficient = [];

        foreach ($target['rules'] as $ruleData) {
            $rule = LessonTicketRule::with(['course:id,fees', 'unit:id,course_id,code,name'])->findOrFail($ruleData['id']);
            $amountRequired = $this->ticketPrice($rule);
            $amountPaid = (float) $target['paid'];

            if ($amountPaid < $amountRequired) {
                $insufficient[] = $rule->name;
                continue;
            }

            LessonTicket::create([
                'lesson_ticket_rule_id' => $rule->id,
                'student_id' => $student->id,
                'course_id' => $rule->course_id,
                'unit_id' => $rule->unit_id,
                'target_type' => $rule->target_type,
                'ticket_number' => $this->nextTicketNumber(),
                'verification_token' => $this->nextVerificationToken(),
                'session_type' => $rule->session_type,
                'lesson_count' => $rule->lessons_per_ticket,
                'amount_required' => $amountRequired,
                'amount_paid' => $amountPaid,
                'issued_on' => $data['issued_on'],
                'status' => 'issued',
                'notes' => $data['notes'] ?? null,
                'created_by' => $request->user()->id,
                'updated_by' => $request->user()->id,
            ]);

            $created++;
        }

        if ($created === 0) {
            throw ValidationException::withMessages([
                'target_key' => 'This learner has not paid enough for the active ticket rules on this selection.',
            ]);
        }

        return back()->with('flash.banner', $created > 1 ? "{$created} lesson tickets issued." : 'Lesson ticket issued.');
    }

    public function download(Request $request, LessonTicket $ticket): HttpResponse
    {
        abort_unless($request->user()->hasAnyPermission('tickets.view|finance.view'), 403);

        $ticket = $this->loadedTicket($ticket);

        if (! $ticket->downloaded_at) {
            $ticket->forceFill([
                'downloaded_at' => now(),
                'status' => 'downloaded',
                'updated_by' => $request->user()->id,
            ])->save();
        }

        return response($this->ticketHtml($ticket))
            ->header('Content-Type', 'text/html; charset=UTF-8')
            ->header('Content-Disposition', 'inline; filename="'.$ticket->ticket_number.'.html"');
    }

    public function verify(Request $request, LessonTicket $ticket): HttpResponse
    {
        abort_unless($request->user()->hasAnyPermission('tickets.view|finance.view'), 403);

        if (! hash_equals((string) $ticket->verification_token, (string) $request->query('token'))) {
            abort(404);
        }

        return response($this->verificationHtml($this->loadedTicket($ticket)))
            ->header('Content-Type', 'text/html; charset=UTF-8');
    }

    private function ruleData(Request $request): array
    {
        $data = $request->validate([
            'course_id' => [Rule::requiredIf($request->input('target_type') !== 'unit'), 'nullable', 'exists:courses,id'],
            'unit_id' => ['nullable', 'exists:units,id'],
            'target_type' => ['required', Rule::in(['course', 'subcourse', 'unit'])],
            'name' => ['required', 'max:255'],
            'session_type' => ['required', Rule::in(['theoretical', 'practical', 'combined'])],
            'lesson_count' => ['required', 'integer', 'min:1', 'max:1000'],
            'lessons_per_ticket' => ['required', 'integer', 'min:1', 'max:1000', 'lte:lesson_count'],
            'pricing_type' => ['required', Rule::in(['fixed_amount', 'percentage'])],
            'pricing_value' => ['required', 'numeric', 'min:0'],
            'description' => ['nullable', 'string'],
            'is_active' => ['boolean'],
        ]);

        if ($data['target_type'] === 'unit') {
            if (! ($data['unit_id'] ?? null)) {
                throw ValidationException::withMessages([
                    'unit_id' => 'Select the unit this ticket rule applies to.',
                ]);
            }

            $unit = Unit::findOrFail($data['unit_id']);
            $data['course_id'] = $unit->course_id;
        } else {
            $data['unit_id'] = null;
            $course = Course::findOrFail($data['course_id']);
            $data['target_type'] = $course->parent_course_id ? 'subcourse' : 'course';
        }

        return $data;
    }

    private function rulePayload(LessonTicketRule $rule): array
    {
        return [
            'id' => $rule->id,
            'course_id' => $rule->course_id,
            'course' => $rule->course,
            'unit_id' => $rule->unit_id,
            'unit' => $rule->unit,
            'target_type' => $rule->target_type,
            'target_key' => $this->ruleTargetKey($rule),
            'target_label' => $this->ruleTargetLabel($rule),
            'name' => $rule->name,
            'session_type' => $rule->session_type,
            'lesson_count' => $rule->lesson_count,
            'lessons_per_ticket' => $rule->lessons_per_ticket,
            'pricing_type' => $rule->pricing_type,
            'pricing_value' => $rule->pricing_value,
            'ticket_price' => $this->ticketPrice($rule),
            'description' => $rule->description,
            'is_active' => $rule->is_active,
            'tickets_count' => $rule->tickets_count,
        ];
    }

    private function ticketPayload(LessonTicket $ticket): array
    {
        return [
            'id' => $ticket->id,
            'lesson_ticket_rule_id' => $ticket->lesson_ticket_rule_id,
            'rule' => $ticket->rule,
            'student_id' => $ticket->student_id,
            'student' => $ticket->student,
            'course_id' => $ticket->course_id,
            'course' => $ticket->course,
            'unit_id' => $ticket->unit_id,
            'unit' => $ticket->unit,
            'target_type' => $ticket->target_type,
            'target_label' => $this->ticketTargetLabel($ticket),
            'ticket_number' => $ticket->ticket_number,
            'session_type' => $ticket->session_type,
            'lesson_count' => $ticket->lesson_count,
            'amount_required' => $ticket->amount_required,
            'amount_paid' => $ticket->amount_paid,
            'issued_on' => $ticket->issued_on,
            'downloaded_at' => $ticket->downloaded_at,
            'status' => $ticket->status,
            'verification_url' => $this->verificationUrl($ticket),
        ];
    }

    private function studentOption(Student $student, Collection $activeRules): array
    {
        $paidByCourse = $student->payments
            ->where('status', 'confirmed')
            ->groupBy('course_id')
            ->map(fn (Collection $payments) => (float) $payments->sum('amount'));

        $courses = collect([$student->course, $student->subcourse])
            ->merge($student->enrollments->pluck('unit.course'))
            ->merge($student->semesterRegistrations->pluck('course'))
            ->merge($student->semesterRegistrations->pluck('subcourse'))
            ->filter()
            ->unique('id')
            ->values()
            ->map(fn ($course) => [
                'id' => $course->id,
                'parent_course_id' => $course->parent_course_id,
                'code' => $course->code,
                'name' => $course->name,
                'fees' => (float) ($course->fees ?? 0),
                'paid' => (float) ($paidByCourse[$course->id] ?? 0),
            ]);

        $units = $student->enrollments
            ->pluck('unit')
            ->filter()
            ->unique('id')
            ->values()
            ->map(fn (Unit $unit) => [
                'id' => $unit->id,
                'course_id' => $unit->course_id,
                'code' => $unit->code,
                'name' => $unit->name,
                'course' => $unit->course,
                'paid' => (float) ($paidByCourse[$unit->course_id] ?? 0),
            ]);

        $courseTargets = $courses->map(fn (array $course) => [
            'key' => ($course['parent_course_id'] ? 'subcourse' : 'course').':'.$course['id'],
            'type' => $course['parent_course_id'] ? 'subcourse' : 'course',
            'course_id' => $course['id'],
            'unit_id' => null,
            'label' => $course['code'].' - '.$course['name'],
            'paid' => $course['paid'],
        ]);

        $unitTargets = $units->map(fn (array $unit) => [
            'key' => 'unit:'.$unit['id'],
            'type' => 'unit',
            'course_id' => $unit['course_id'],
            'unit_id' => $unit['id'],
            'label' => $unit['code'].' - '.$unit['name'],
            'paid' => $unit['paid'],
        ]);

        $ticketTargets = $courseTargets
            ->merge($unitTargets)
            ->map(function (array $target) use ($activeRules) {
                $rules = $activeRules
                    ->where('target_key', $target['key'])
                    ->values();

                return $target + [
                    'rules' => $rules,
                    'rules_count' => $rules->count(),
                    'required_total' => (float) $rules->sum('ticket_price'),
                ];
            })
            ->filter(fn (array $target) => $target['rules_count'] > 0)
            ->values();

        return [
            'id' => $student->id,
            'admission_number' => $student->admission_number,
            'name' => trim($student->first_name.' '.$student->last_name),
            'courses' => $courses,
            'units' => $units,
            'ticket_targets' => $ticketTargets,
        ];
    }

    private function studentCourseIds(Student $student): Collection
    {
        return collect([$student->course_id, $student->subcourse_id])
            ->merge($student->enrollments->pluck('unit.course_id'))
            ->merge($student->semesterRegistrations->pluck('course_id'))
            ->merge($student->semesterRegistrations->pluck('subcourse_id'))
            ->filter()
            ->unique()
            ->values();
    }

    private function ticketPrice(LessonTicketRule $rule): float
    {
        if ($rule->pricing_type === 'percentage') {
            return round(((float) ($rule->course?->fees ?? 0) * (float) $rule->pricing_value) / 100, 2);
        }

        return round((float) $rule->pricing_value, 2);
    }

    private function activeRules(): Collection
    {
        return LessonTicketRule::query()
            ->with(['course:id,parent_course_id,code,name,fees', 'course.parentCourse:id,code,name', 'unit:id,course_id,code,name'])
            ->where('is_active', true)
            ->get()
            ->map(fn (LessonTicketRule $rule) => $this->rulePayload($rule));
    }

    private function issueTarget(Collection $targets, ?string $targetKey): ?array
    {
        if ($targetKey) {
            return $targets->first(fn (array $target) => $target['key'] === $targetKey);
        }

        return $targets->count() === 1 ? $targets->first() : null;
    }

    private function ruleTargetKey(LessonTicketRule $rule): string
    {
        return $rule->target_type === 'unit'
            ? 'unit:'.$rule->unit_id
            : $rule->target_type.':'.$rule->course_id;
    }

    private function ruleTargetLabel(LessonTicketRule $rule): string
    {
        if ($rule->target_type === 'unit') {
            return trim(($rule->unit?->code ?: '').' - '.($rule->unit?->name ?: ''), ' -');
        }

        return trim(($rule->course?->code ?: '').' - '.($rule->course?->name ?: ''), ' -');
    }

    private function ticketTargetLabel(LessonTicket $ticket): string
    {
        if ($ticket->target_type === 'unit') {
            return trim(($ticket->unit?->code ?: '').' - '.($ticket->unit?->name ?: ''), ' -');
        }

        return trim(($ticket->course?->code ?: '').' - '.($ticket->course?->name ?: ''), ' -');
    }

    private function nextTicketNumber(): string
    {
        do {
            $number = 'TKT-'.now()->format('YmdHis').'-'.Str::upper(Str::random(4));
        } while (LessonTicket::where('ticket_number', $number)->exists());

        return $number;
    }

    private function nextVerificationToken(): string
    {
        do {
            $token = Str::upper(Str::random(32));
        } while (LessonTicket::where('verification_token', $token)->exists());

        return $token;
    }

    private function loadedTicket(LessonTicket $ticket): LessonTicket
    {
        return $ticket->load([
            'rule:id,name,pricing_type,pricing_value',
            'student:id,admission_number,first_name,last_name',
            'course:id,code,name',
            'unit:id,course_id,code,name',
        ]);
    }

    private function verificationUrl(LessonTicket $ticket): string
    {
        return URL::route('finance.tickets.verify', [
            'ticket' => $ticket->id,
            'token' => $ticket->verification_token,
        ]);
    }

    private function qrDataUri(string $payload): string
    {
        $renderer = new ImageRenderer(
            new RendererStyle(164, 1),
            new SvgImageBackEnd()
        );
        $writer = new Writer($renderer);

        return 'data:image/svg+xml;base64,'.base64_encode($writer->writeString($payload));
    }

    private function organisationPayload(): array
    {
        $organisation = OrganisationSetting::query()->first();

        return [
            'name' => $organisation?->name ?: 'Organisation',
            'location' => $organisation?->location ?: '',
            'primary_contact' => $organisation?->primary_contact ?: '',
            'official_email' => $organisation?->official_email ?: '',
        ];
    }

    private function ticketHtml(LessonTicket $ticket): string
    {
        $org = $this->organisationPayload();
        $studentName = e(trim($ticket->student->first_name.' '.$ticket->student->last_name));
        $courseName = e($this->ticketTargetLabel($ticket));
        $admissionNumber = e($ticket->student->admission_number);
        $issuedOn = e($ticket->issued_on->toFormattedDateString());
        $ticketNumber = e($ticket->ticket_number);
        $sessionType = e(Str::headline($ticket->session_type));
        $amountRequired = e('KES '.number_format((float) $ticket->amount_required, 2));
        $amountPaid = e('KES '.number_format((float) $ticket->amount_paid, 2));
        $verificationUrl = e($this->verificationUrl($ticket));
        $qrDataUri = $this->qrDataUri($this->verificationUrl($ticket));
        $orgName = e($org['name']);
        $orgLocation = e($org['location']);
        $orgContact = e($org['primary_contact']);
        $orgEmail = e($org['official_email']);

        return <<<HTML
<!doctype html>
<html>
<head>
    <meta charset="utf-8">
    <title>{$ticketNumber}</title>
    <style>
        * { box-sizing: border-box; }
        @page { size: 80mm auto; margin: 3mm; }
        body { margin: 0; background: #ffffff; color: #000000; font-family: "Courier New", Courier, monospace; font-size: 11px; }
        .toolbar { padding: 10px; text-align: center; }
        .toolbar button { border: 1px solid #111827; background: #111827; color: #ffffff; border-radius: 4px; padding: 8px 12px; font-weight: 700; }
        .receipt { width: 72mm; margin: 0 auto; background: #ffffff; padding: 0; }
        .header { text-align: center; }
        h1, p { margin: 0; }
        .org-name { font-size: 14px; font-weight: 700; text-transform: uppercase; }
        .org-meta { margin-top: 3px; font-size: 10px; line-height: 1.35; }
        .line { border-top: 1px dashed #000000; margin: 7px 0; }
        .title { font-size: 13px; font-weight: 700; text-align: center; text-transform: uppercase; }
        .row { display: flex; justify-content: space-between; gap: 8px; line-height: 1.45; }
        .row span:first-child { white-space: nowrap; }
        .row span:last-child { text-align: right; overflow-wrap: anywhere; }
        .block { line-height: 1.45; }
        .total-row { display: flex; justify-content: space-between; gap: 8px; font-size: 13px; font-weight: 700; }
        .qr { display: flex; justify-content: center; margin: 8px 0 4px; }
        .qr img { width: 34mm; height: 34mm; }
        .verify { overflow-wrap: anywhere; text-align: center; font-size: 9px; line-height: 1.25; }
        .thanks { margin-top: 10px; text-align: center; font-weight: 700; }
        @media print {
            .toolbar { display: none; }
            .receipt { width: 72mm; margin: 0; }
        }
    </style>
</head>
<body>
    <div class="toolbar"><button onclick="window.print()">Print / Save PDF</button></div>
    <main class="receipt">
        <section class="header">
            <h1 class="org-name">{$orgName}</h1>
            <p class="org-meta">{$orgLocation}<br>Tel: {$orgContact}<br>Email: {$orgEmail}</p>
        </section>
        <div class="line"></div>
        <div class="title">Lesson Access Ticket</div>
        <div class="line"></div>
        <section>
            <div class="row"><span>Ticket No:</span><span>{$ticketNumber}</span></div>
            <div class="row"><span>Issued:</span><span>{$issuedOn}</span></div>
            <div class="row"><span>Status:</span><span>{$ticket->status}</span></div>
        </section>
        <div class="line"></div>
        <section class="block">
            <p><strong>Student:</strong> {$studentName}</p>
            <p><strong>Admission:</strong> {$admissionNumber}</p>
            <p><strong>Course:</strong> {$courseName}</p>
        </section>
        <div class="line"></div>
        <section>
            <div class="row"><span>Session:</span><span>{$sessionType}</span></div>
            <div class="row"><span>Lessons:</span><span>{$ticket->lesson_count}</span></div>
            <div class="row"><span>Required:</span><span>{$amountRequired}</span></div>
            <div class="total-row"><span>Paid</span><span>{$amountPaid}</span></div>
        </section>
        <div class="line"></div>
        <section>
            <div class="title">Verify Ticket</div>
            <div class="qr"><img src="{$qrDataUri}" alt="Verification QR code"></div>
            <p class="verify">{$verificationUrl}</p>
        </section>
        <div class="line"></div>
        <p class="thanks">Present this ticket at lesson entry.</p>
    </main>
</body>
</html>
HTML;
    }

    private function verificationHtml(LessonTicket $ticket): string
    {
        $studentName = e(trim($ticket->student->first_name.' '.$ticket->student->last_name));
        $courseName = e($this->ticketTargetLabel($ticket));
        $ticketNumber = e($ticket->ticket_number);
        $status = e(Str::headline($ticket->status));
        $issuedOn = e($ticket->issued_on->toFormattedDateString());

        return <<<HTML
<!doctype html>
<html>
<head>
    <meta charset="utf-8">
    <title>Verify {$ticketNumber}</title>
    <style>
        body { margin: 0; min-height: 100vh; display: grid; place-items: center; background: #f8fafc; color: #111827; font-family: Arial, sans-serif; }
        main { width: min(92vw, 520px); border: 1px solid #d1d5db; border-radius: 8px; background: #ffffff; padding: 24px; }
        .badge { display: inline-block; border-radius: 999px; background: #dcfce7; color: #166534; padding: 6px 10px; font-size: 12px; font-weight: 700; text-transform: uppercase; }
        h1 { margin: 14px 0 18px; font-size: 24px; }
        .row { display: flex; justify-content: space-between; gap: 18px; border-top: 1px solid #e5e7eb; padding: 12px 0; }
        .row span:first-child { color: #6b7280; }
        .row span:last-child { font-weight: 700; text-align: right; }
    </style>
</head>
<body>
    <main>
        <span class="badge">Valid Ticket</span>
        <h1>{$ticketNumber}</h1>
        <div class="row"><span>Status</span><span>{$status}</span></div>
        <div class="row"><span>Student</span><span>{$studentName}</span></div>
        <div class="row"><span>Course</span><span>{$courseName}</span></div>
        <div class="row"><span>Session</span><span>{$ticket->session_type}</span></div>
        <div class="row"><span>Lessons</span><span>{$ticket->lesson_count}</span></div>
        <div class="row"><span>Issued</span><span>{$issuedOn}</span></div>
    </main>
</body>
</html>
HTML;
    }
}

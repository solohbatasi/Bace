<?php

namespace App\Http\Controllers;

use App\Models\Assignment;
use App\Models\AssignmentSubmission;
use App\Models\LecturerUnitAssignment;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Inertia\Inertia;
use Inertia\Response;

class AssignmentManagementController extends Controller
{
    private const FILE_RULE = 'mimes:pdf,doc,docx,ppt,pptx,xls,xlsx,txt,zip,jpg,jpeg,png|max:10240';

    public function index(Request $request): Response
    {
        $lecturer = $request->user()->lecturer;
        $student = $request->user()->student;
        $canManage = $request->user()->hasPermission('assignments.manage');

        abort_unless($canManage || $student, 403);

        return Inertia::render('Academics/Assignments', [
            'canManage' => $canManage,
            'assignments' => Assignment::query()
                ->with([
                    'attachments',
                    'lecturerUnitAssignment.unit:id,code,name',
                    'lecturerUnitAssignment.class:id,code,name',
                    'lecturerUnitAssignment.semester:id,name',
                    'submissions.student:id,admission_number,first_name,last_name',
                    'submissions.versions',
                ])
                ->when($canManage && $lecturer, fn ($query) => $query->whereHas('lecturerUnitAssignment', fn ($query) => $query->where('lecturer_id', $lecturer->id)))
                ->when(! $canManage && $student, fn ($query) => $query
                    ->where('status', 'published')
                    ->whereHas('lecturerUnitAssignment.unit.enrollments', fn ($query) => $query
                        ->where('student_id', $student->id)
                        ->whereIn('status', ['approved', 'pending'])))
                ->latest()
                ->paginate(10),
            'lecturerAssignments' => LecturerUnitAssignment::query()
                ->with(['unit:id,code,name', 'class:id,code,name', 'semester:id,name'])
                ->when($lecturer, fn ($query) => $query->where('lecturer_id', $lecturer->id))
                ->latest()
                ->get(),
            'student' => $student,
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        abort_unless($request->user()->hasPermission('assignments.manage'), 403);

        $data = $this->assignmentData($request);

        DB::transaction(function () use ($request, $data): void {
            $files = $request->file('attachments', []);
            unset($data['attachments']);

            $assignment = Assignment::create($data + [
                'status' => $request->boolean('publish') ? 'published' : 'draft',
                'created_by' => $request->user()->id,
                'updated_by' => $request->user()->id,
            ]);

            $this->storeAttachments($assignment, $files, $request->user()->id);
        });

        return back()->with('flash.banner', 'Assignment created.');
    }

    public function update(Request $request, Assignment $assignment): RedirectResponse
    {
        abort_unless($request->user()->hasPermission('assignments.manage'), 403);

        $data = $this->assignmentData($request);

        DB::transaction(function () use ($request, $assignment, $data): void {
            $files = $request->file('attachments', []);
            unset($data['attachments']);

            $assignment->update($data + ['updated_by' => $request->user()->id]);
            $this->storeAttachments($assignment, $files, $request->user()->id);
        });

        return back()->with('flash.banner', 'Assignment updated.');
    }

    public function publish(Request $request, Assignment $assignment): RedirectResponse
    {
        abort_unless($request->user()->hasPermission('assignments.manage'), 403);

        $assignment->update(['status' => 'published', 'updated_by' => $request->user()->id]);

        return back()->with('flash.banner', 'Assignment published.');
    }

    public function submit(Request $request, Assignment $assignment): RedirectResponse
    {
        $student = $request->user()->student;
        abort_unless($student && $assignment->status === 'published', 403);

        $data = $request->validate([
            'submission_text' => ['nullable', 'string'],
            'file' => ['nullable', 'file', self::FILE_RULE],
        ]);

        DB::transaction(function () use ($request, $assignment, $student, $data): void {
            $submission = AssignmentSubmission::firstOrCreate(
                ['assignment_id' => $assignment->id, 'student_id' => $student->id],
                ['created_by' => $request->user()->id]
            );

            $file = $request->file('file');
            $path = $file?->store('assignments/submissions', 'public');
            $submittedAt = now();
            $isLate = $submittedAt->greaterThan($assignment->due_at);
            $version = ((int) $submission->versions()->max('version_number')) + 1;

            $submission->versions()->create([
                'version_number' => $version,
                'submitted_at' => $submittedAt,
                'is_late' => $isLate,
                'submission_text' => $data['submission_text'] ?? null,
                'disk' => 'public',
                'file_path' => $path,
                'original_name' => $file?->getClientOriginalName(),
                'mime_type' => $file?->getClientMimeType(),
                'size' => $file?->getSize() ?? 0,
                'created_by' => $request->user()->id,
                'updated_by' => $request->user()->id,
            ]);

            $submission->update([
                'submitted_at' => $submittedAt,
                'submission_text' => $data['submission_text'] ?? null,
                'file_path' => $path,
                'status' => $isLate ? 'late' : 'submitted',
                'updated_by' => $request->user()->id,
            ]);
        });

        return back()->with('flash.banner', 'Submission uploaded.');
    }

    public function downloadAttachment(Assignment $assignment, int $attachment): mixed
    {
        $file = $assignment->attachments()->findOrFail($attachment);

        return Storage::disk($file->disk)->download($file->path, $file->original_name);
    }

    private function assignmentData(Request $request): array
    {
        return $request->validate([
            'lecturer_unit_assignment_id' => ['required', 'exists:lecturer_unit_assignments,id'],
            'title' => ['required', 'max:255'],
            'description' => ['nullable', 'string'],
            'opens_at' => ['nullable', 'date'],
            'due_at' => ['required', 'date'],
            'max_score' => ['required', 'numeric', 'min:1'],
            'submission_type' => ['required', 'in:file,text,both'],
            'attachments' => ['array'],
            'attachments.*' => ['file', self::FILE_RULE],
        ]);
    }

    private function storeAttachments(Assignment $assignment, array $files, int $userId): void
    {
        foreach ($files as $file) {
            $assignment->attachments()->create([
                'disk' => 'public',
                'path' => $file->store('assignments/attachments', 'public'),
                'original_name' => $file->getClientOriginalName(),
                'mime_type' => $file->getClientMimeType(),
                'size' => $file->getSize(),
                'created_by' => $userId,
                'updated_by' => $userId,
            ]);
        }
    }
}

<?php

namespace App\Http\Controllers;

use App\Models\Course;
use App\Models\Unit;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class ScoreLevelController extends Controller
{
    public function updateCourse(Request $request, Course $course): RedirectResponse
    {
        abort_unless($request->user()->hasAnyPermission('courses.manage|classes.manage'), 403);

        [$mode, $levels] = $this->scoreLevelData($request);

        DB::transaction(function () use ($course, $levels, $mode, $request): void {
            $course->update([
                'grading_mode' => $mode,
                'updated_by' => $request->user()->id,
            ]);
            $course->scoreLevels()->delete();

            foreach ($levels as $index => $level) {
                $course->scoreLevels()->create($level + [
                    'sort_order' => $index + 1,
                    'created_by' => $request->user()->id,
                    'updated_by' => $request->user()->id,
                ]);
            }
        });

        return back()->with('flash.banner', 'Course score levels updated.');
    }

    public function updateUnit(Request $request, Unit $unit): RedirectResponse
    {
        abort_unless($request->user()->hasAnyPermission('units.manage|classes.manage'), 403);

        [$mode, $levels] = $this->scoreLevelData($request);

        DB::transaction(function () use ($unit, $levels, $mode, $request): void {
            $unit->update([
                'grading_mode' => $mode,
                'updated_by' => $request->user()->id,
            ]);
            $unit->scoreLevels()->delete();

            foreach ($levels as $index => $level) {
                $unit->scoreLevels()->create($level + [
                    'sort_order' => $index + 1,
                    'created_by' => $request->user()->id,
                    'updated_by' => $request->user()->id,
                ]);
            }
        });

        return back()->with('flash.banner', 'Unit score levels updated.');
    }

    private function scoreLevelData(Request $request): array
    {
        $data = $request->validate([
            'grading_mode' => ['required', 'in:grade_only,score_levels,score_levels_with_grades'],
            'levels' => ['required', 'array', 'min:1'],
            'levels.*.min_score' => ['nullable', 'numeric', 'min:0', 'max:100'],
            'levels.*.max_score' => ['nullable', 'numeric', 'min:0', 'max:100'],
            'levels.*.grade' => ['nullable', 'string', 'max:20'],
            'levels.*.comment' => ['nullable', 'string', 'max:255'],
        ]);

        $usesRange = in_array($data['grading_mode'], ['score_levels', 'score_levels_with_grades'], true);

        $levels = collect($data['levels'] ?? [])
            ->map(fn (array $level) => [
                'min_score' => $usesRange ? ($level['min_score'] ?? null) : null,
                'max_score' => $usesRange ? ($level['max_score'] ?? null) : null,
                'grade' => $level['grade'] ?? null,
                'comment' => $level['comment'] ?? null,
            ])
            ->filter(fn (array $level) => $usesRange
                || filled($level['grade'])
                || filled($level['comment']))
            ->map(fn (array $level) => [
                ...$level,
                'min_score' => $level['min_score'] === null ? null : (float) $level['min_score'],
                'max_score' => $level['max_score'] === null ? null : (float) $level['max_score'],
            ])
            ->sortBy(fn (array $level) => $level['min_score'] ?? 0)
            ->values();

        if ($levels->isEmpty()) {
            throw ValidationException::withMessages([
                'levels' => 'Add at least one score range for this grading mode.',
            ]);
        }

        foreach ($levels as $index => $level) {
            if (! $usesRange) {
                continue;
            }

            if ($level['min_score'] === null || $level['max_score'] === null) {
                throw ValidationException::withMessages([
                    'levels' => 'Each range row must have both From and To values.',
                ]);
            }

            if ($level['min_score'] > $level['max_score']) {
                throw ValidationException::withMessages([
                    'levels' => 'Each score range must start below or equal to its end score.',
                ]);
            }

            $previous = $levels[$index - 1] ?? null;
            if ($previous && $level['min_score'] < $previous['max_score']) {
                throw ValidationException::withMessages([
                    'levels' => 'Score ranges cannot overlap.',
                ]);
            }
        }

        return [$data['grading_mode'], $levels->all()];
    }
}

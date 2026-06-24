<?php

namespace App\Http\Requests\Student;

use App\Models\Student;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateStudentRequest extends StoreStudentRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('update', $this->route('student')) ?? false;
    }

    public function rules(): array
    {
        return array_merge(parent::rules(), [
            'department_id' => ['sometimes', 'required', 'integer', Rule::exists('departments', 'id')],
            'course_id' => ['sometimes', 'required', 'integer', Rule::exists('courses', 'id')],
            'first_name' => ['sometimes', 'required', 'string', 'max:100'],
            'last_name' => ['sometimes', 'required', 'string', 'max:100'],
            'admitted_on' => ['sometimes', 'required', 'date'],
            'status' => ['sometimes', 'required', Rule::in(Student::STATUSES)],
            'academic_year_id' => ['required_with:additional_courses', 'nullable', 'integer', Rule::exists('academic_years', 'id')],
            'semester_id' => ['required_with:additional_courses', 'nullable', 'integer', Rule::exists('semesters', 'id')],
        ]);
    }
}

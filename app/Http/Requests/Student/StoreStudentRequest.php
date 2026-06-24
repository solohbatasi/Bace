<?php

namespace App\Http\Requests\Student;

use App\Models\Student;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreStudentRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('create', Student::class) ?? false;
    }

    public function rules(): array
    {
        return [
            'user_id' => ['nullable', 'integer', Rule::exists('users', 'id')],
            'department_id' => ['required', 'integer', Rule::exists('departments', 'id')],
            'course_id' => ['required', 'integer', Rule::exists('courses', 'id')],
            'course_fee' => ['nullable', 'numeric', 'min:0'],
            'class_id' => ['nullable', 'integer', Rule::exists('classes', 'id')],
            'admission_number' => ['nullable', 'string', 'max:50'],
            'first_name' => ['required', 'string', 'max:100'],
            'middle_name' => ['nullable', 'string', 'max:100'],
            'last_name' => ['required', 'string', 'max:100'],
            'gender' => ['nullable', 'string', 'max:20'],
            'date_of_birth' => ['nullable', 'date', 'before:today'],
            'email' => ['nullable', 'email', 'max:255'],
            'phone' => ['nullable', 'string', 'max:30'],
            'photo' => ['nullable', 'image', 'max:2048'],
            'address' => ['nullable', 'string', 'max:2000'],
            'guardian_name' => ['nullable', 'string', 'max:255'],
            'guardian_relationship' => ['nullable', 'string', 'max:80'],
            'guardian_phone' => ['nullable', 'string', 'max:30'],
            'guardian_email' => ['nullable', 'email', 'max:255'],
            'guardian_address' => ['nullable', 'string', 'max:2000'],
            'admitted_on' => ['required', 'date'],
            'status' => ['required', Rule::in(Student::STATUSES)],
            'academic_histories' => ['array'],
            'academic_histories.*.institution_name' => ['required_with:academic_histories', 'string', 'max:255'],
            'academic_histories.*.qualification' => ['nullable', 'string', 'max:255'],
            'academic_histories.*.grade' => ['nullable', 'string', 'max:255'],
            'academic_histories.*.started_on' => ['nullable', 'date'],
            'academic_histories.*.completed_on' => ['nullable', 'date', 'after_or_equal:academic_histories.*.started_on'],
            'academic_histories.*.notes' => ['nullable', 'string', 'max:2000'],
        ];
    }
}

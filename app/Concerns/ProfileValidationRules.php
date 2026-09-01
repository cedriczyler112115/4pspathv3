<?php

namespace App\Concerns;

use App\Models\User;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Validation\Rule;

trait ProfileValidationRules
{
    /**
     * Get the validation rules used to validate user profiles.
     *
     * @return array<string, array<int, ValidationRule|array<mixed>|string>>
     */
    protected function profileRules(?int $userId = null): array
    {
        return [
            'name' => ['nullable', 'string', 'max:255'],
            'first_name' => ['required', 'string', 'max:100'],
            'middle_name' => ['required', 'string', 'max:100'],
            'last_name' => ['required', 'string', 'max:100'],
            'extension_name' => $this->nullableTextRule(50),
            'position' => ['required', 'string', 'max:100'],
            'designation' => ['required', 'string', 'max:100'],
            'division_id' => ['required', 'integer', Rule::exists('lib_division', 'id')],
            'section_id' => ['required', 'integer', Rule::exists('lib_section', 'id')],
            'contact_number' => ['required', 'string', 'max:100'],
            'supervisor_id' => ['required', 'integer', Rule::exists(User::class, 'id')],
            'is_supervisor' => ['nullable', 'boolean'],
        ];
    }

    /**
     * Get the validation rules used to validate user names.
     *
     * @return array<int, ValidationRule|array<mixed>|string>
     */
    protected function nameRules(): array
    {
        return ['required', 'string', 'max:255'];
    }

    /**
     * Get a nullable text rule with a max length.
     *
     * @return array<int, ValidationRule|array<mixed>|string>
     */
    protected function nullableTextRule(int $maxLength): array
    {
        return ['nullable', 'string', 'max:'.$maxLength];
    }
}

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
            'name' => $this->nameRules(),
            'email' => $this->emailRules($userId),
            'last_name' => $this->nullableTextRule(100),
            'first_name' => $this->nullableTextRule(100),
            'middle_name' => $this->nullableTextRule(100),
            'extension_name' => $this->nullableTextRule(50),
            'position' => $this->nullableTextRule(100),
            'designation' => $this->nullableTextRule(100),
            'division' => $this->nullableTextRule(255),
            'section' => $this->nullableTextRule(255),
            'contact_number' => $this->nullableTextRule(100),
            'supervisor_id' => ['nullable', 'integer', Rule::exists(User::class, 'id')],
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
     * Get the validation rules used to validate user emails.
     *
     * @return array<int, ValidationRule|array<mixed>|string>
     */
    protected function emailRules(?int $userId = null): array
    {
        return [
            'required',
            'string',
            'email',
            'max:255',
            $userId === null
                ? Rule::unique(User::class)
                : Rule::unique(User::class)->ignore($userId),
        ];
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

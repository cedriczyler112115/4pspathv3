<?php

namespace App\Livewire\Pages\Settings;

use App\Concerns\ProfileValidationRules;
use App\Models\User;
use Flux\Flux;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Facades\Auth;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Title;
use Livewire\Component;

#[Title('Profile settings')]
class ProfilePage extends Component
{
    use ProfileValidationRules;

    public string $name = '';

    public string $email = '';
    public string $last_name = '';
    public string $first_name = '';
    public string $middle_name = '';
    public string $extension_name = '';
    public string $position = '';
    public string $designation = '';
    public string $division = '';
    public string $section = '';
    public string $contact_number = '';
    public ?int $supervisor_id = null;

    public function mount(): void
    {
        $user = Auth::user();

        $this->name = $user->name ?? trim(collect([
            $user->first_name ?? '',
            $user->middle_name ?? '',
            $user->last_name ?? '',
            $user->extension_name ?? '',
        ])->filter()->join(' '));
        $this->email = $user->email;
        $this->last_name = $user->last_name ?? '';
        $this->first_name = $user->first_name ?? '';
        $this->middle_name = $user->middle_name ?? '';
        $this->extension_name = $user->extension_name ?? '';
        $this->position = $user->position ?? '';
        $this->designation = $user->designation ?? '';
        $this->division = $user->division ?? '';
        $this->section = $user->section ?? '';
        $this->contact_number = $user->contact_number ?? '';
        $this->supervisor_id = $user->supervisor_id;
    }

    public function updateProfileInformation(): void
    {
        $user = Auth::user();

        $validated = $this->validate($this->profileRules($user->id));
        $validated['supervisor_id'] = $validated['supervisor_id'] ?: null;

        $user->fill($validated);
        $user->name = trim(collect([
            $validated['first_name'] ?? '',
            $validated['middle_name'] ?? '',
            $validated['last_name'] ?? '',
            $validated['extension_name'] ?? '',
        ])->filter()->join(' '));

        if ($user->isDirty('email')) {
            $user->email_verified_at = null;
        }

        $user->save();

        Flux::toast(variant: 'success', text: __('Profile updated.'));
    }

    #[Computed]
    public function showDeleteUser(): bool
    {
        return Auth::user()->hasVerifiedEmail();
    }

    #[Computed]
    public function supervisors()
    {
        return User::query()
            ->whereKeyNot(Auth::id())
            ->orderBy('last_name')
            ->orderBy('first_name')
            ->get(['id', 'name', 'last_name', 'first_name', 'middle_name', 'extension_name']);
    }

    public function render(): View
    {
        return view('livewire.pages.settings.profile-page');
    }
}

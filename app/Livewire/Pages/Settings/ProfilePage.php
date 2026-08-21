<?php

namespace App\Livewire\Pages\Settings;

use App\Actions\Users\UpdateProfile;
use App\Concerns\ProfileValidationRules;
use App\Models\User;
use Flux\Flux;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Title;
use Livewire\Component;
use stdClass;

#[Title('My Account')]
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

    public string $division_id = '';

    public string $section_id = '';

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
        $this->division_id = (string) ($user->division_id ?? '');
        $this->section_id = (string) ($user->section_id ?? '');
        $this->contact_number = (string) ($user->contact_number ?? '');
        $this->supervisor_id = $user->supervisor_id;
    }

    public function updateProfileInformation(UpdateProfile $updateProfile): void
    {
        $user = Auth::user();

        $validated = $this->validate($this->profileRules($user->id));
        $validated['supervisor_id'] = $validated['supervisor_id'] ?: null;

        $updateProfile->execute($user, $validated);

        Flux::toast(variant: 'success', text: __('Profile updated.'));
    }

    /** @return Collection<int, User> */
    #[Computed]
    public function supervisors()
    {
        return User::query()
            ->whereKeyNot(Auth::id())
            ->orderBy('last_name')
            ->orderBy('first_name')
            ->get(['id', 'name', 'last_name', 'first_name', 'middle_name', 'extension_name']);
    }

    /** @return Collection<int, stdClass> */
    #[Computed]
    public function divisions()
    {
        return DB::table('lib_division')
            ->orderBy('division_name')
            ->get(['id', 'division_name']);
    }

    /** @return Collection<int, stdClass> */
    #[Computed]
    public function sections()
    {
        return DB::table('lib_section')
            ->when($this->division_id !== '', function ($query): void {
                $query->where('division_id', $this->division_id);
            })
            ->orderBy('section_name')
            ->get(['id', 'section_name', 'division_id']);
    }

    public function updatedDivisionId(): void
    {
        $this->section_id = '';
    }

    public function render(): View
    {
        return view('livewire.pages.settings.profile-page');
    }
}

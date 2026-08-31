<?php

namespace App\Http\Controllers\Inertia\Settings;

use App\Actions\Users\UpdateProfile;
use App\Concerns\ProfileValidationRules;
use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Auth;
use Inertia\Inertia;
use Inertia\Response;

class ProfileController extends Controller
{
    use ProfileValidationRules;

    public function edit(Request $request): Response
    {
        $user = $request->user();

        return Inertia::render('Settings/Profile', [
            'user' => $user?->only([
                'id',
                'name',
                'email',
                'last_name',
                'first_name',
                'middle_name',
                'extension_name',
                'position',
                'designation',
                'division_id',
                'section_id',
                'contact_number',
                'supervisor_id',
                'is_supervisor',
            ]),
            'divisions' => DB::table('lib_division')
                ->orderBy('division_name')
                ->get(['id', 'division_name']),
            'sections' => DB::table('lib_section')
                ->orderBy('section_name')
                ->get(['id', 'section_name', 'division_id']),
            'supervisors' => User::query()
                ->whereKeyNot(Auth::id())
                ->orderBy('last_name')
                ->orderBy('first_name')
                ->get(['id', 'name', 'last_name', 'first_name', 'middle_name', 'extension_name']),
        ]);
    }

    public function update(Request $request, UpdateProfile $updateProfile): RedirectResponse
    {
        $user = $request->user();
        abort_if($user === null, 403);

        $validated = $request->validate($this->profileRules($user->id));
        $validated['supervisor_id'] = $validated['supervisor_id'] ?: null;
        $validated['is_supervisor'] = (int) ($request->boolean('is_supervisor'));

        $updateProfile->execute($user, $validated);

        return back()->with('success', __('Profile updated.'));
    }
}

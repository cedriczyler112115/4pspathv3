<section class="w-full">
    @include('partials.settings-heading')

    <flux:heading class="sr-only">{{ __('Profile settings') }}</flux:heading>

    <x-pages::settings.layout :heading="__('Profile')" :subheading="__('Update your personal and work details')"
        content-class="mt-3 w-full max-w-3xl">
        <form wire:submit="updateProfileInformation" class="my-6 w-full space-y-6">
            <div class="grid gap-4 md:grid-cols-2">
                <flux:input wire:model="last_name" :label="__('Lastname')" type="text" autocomplete="family-name" />
                <flux:input wire:model="first_name" :label="__('Firstname')" type="text" required autofocus
                    autocomplete="given-name" />
                <flux:input wire:model="middle_name" :label="__('Middlename')" type="text"
                    autocomplete="additional-name" />
                <flux:input wire:model="extension_name" :label="__('Extension Name')" type="text"
                    autocomplete="honorific-suffix" />
            </div>

            <div class="grid gap-4 md:grid-cols-2">
                <flux:input wire:model="position" :label="__('Position')" type="text" />
                <flux:input wire:model="designation" :label="__('Designation')" type="text" />
                <flux:input wire:model="division" :label="__('Division')" type="text" />
                <flux:input wire:model="section" :label="__('Section')" type="text" />
            </div>

            <div class="grid gap-4 md:grid-cols-2">
                <flux:input wire:model="contact_number" :label="__('Mobile Number')" type="text" />

                <flux:select wire:model="supervisor_id" :label="__('Your Supervisor')">
                    <option value="">{{ __('Select supervisor') }}</option>
                    @foreach ($this->supervisors as $supervisor)
                        <option value="{{ $supervisor->id }}">
                            {{ trim(collect([$supervisor->first_name, $supervisor->middle_name, $supervisor->last_name, $supervisor->extension_name])->filter()->join(' ')) ?: $supervisor->name }}
                        </option>
                    @endforeach
                </flux:select>
            </div>

            <div>
                <flux:input wire:model="email" :label="__('Email')" type="email" required autocomplete="email" />
            </div>

            <div class="flex items-center gap-4">
                <div class="flex items-center justify-end">
                    <flux:button variant="primary" type="submit" class="w-full" data-test="update-profile-button">
                        {{ __('Save') }}
                    </flux:button>
                </div>
            </div>
        </form>

        @if ($this->showDeleteUser)
            @livewire(\App\Livewire\Pages\Settings\DeleteUserForm::class)
        @endif
    </x-pages::settings.layout>
</section>

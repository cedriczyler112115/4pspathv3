<section class="w-full">
    <x-pages::settings.layout :heading="__('My Account')" :subheading="__('Manage your personal, work, and contact information from this page.')" content-class="mt-3 w-full max-w-3xl">
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
                <flux:select wire:model.live="division_id" :label="__('Division')">
                    <option value="">{{ __('Select division') }}</option>
                    @foreach ($this->divisions as $division)
                        <option value="{{ $division->id }}">{{ $division->division_name }}</option>
                    @endforeach
                </flux:select>
                <flux:field>
                    <flux:label>{{ __('Section') }}</flux:label>
                    <div class="relative" style="position: relative;">
                        <flux:select wire:model="section_id" style="padding-left: 2.25rem;" wire:loading.attr="disabled"
                            wire:target="division_id">
                            <option value="">{{ __('Select section') }}</option>
                            @foreach ($this->sections as $section)
                                <option value="{{ $section->id }}">{{ $section->section_name }}</option>
                            @endforeach
                        </flux:select>
                        <div wire:loading.flex wire:target="division_id"
                            class="pointer-events-none items-center justify-center text-muted-foreground"
                            style="position: absolute; left: 0.75rem; top: 50%; z-index: 10; transform: translateY(-50%);">
                            <svg class="h-4 w-4 animate-spin" viewBox="0 0 24 24" fill="none" aria-hidden="true">
                                <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor"
                                    stroke-width="4"></circle>
                                <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 0 1 8-8v4a4 4 0 0 0-4 4H4z">
                                </path>
                            </svg>
                        </div>
                    </div>
                </flux:field>
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

            <div class="rounded-xl border border-border bg-muted/20 p-4">
                <div class="text-sm font-medium text-foreground">{{ __('Email') }}</div>
                <div class="mt-1 text-sm text-muted-foreground">{{ $email }}</div>
            </div>

            <div class="flex items-center gap-4">
                <div class="flex items-center justify-end">
                    <flux:button variant="primary" type="submit" class="w-full" data-test="update-profile-button">
                        {{ __('Save') }}
                    </flux:button>
                </div>
            </div>
        </form>

    </x-pages::settings.layout>
</section>
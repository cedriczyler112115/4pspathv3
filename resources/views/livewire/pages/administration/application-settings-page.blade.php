<section class="w-full space-y-6">
    <div>
        <flux:heading size="lg" level="1">{{ __('Application Settings') }}</flux:heading>
        <flux:subheading size="sm">{{ __('Manage the application identity and Annual Target categories.') }}</flux:subheading>
    </div>

    <form wire:submit="save" class="max-w-3xl rounded-2xl border border-border bg-card shadow-sm">
        <div class="border-b border-border px-6 py-5">
            <flux:heading size="base">{{ __('General Settings') }}</flux:heading>
            <flux:subheading size="sm">{{ __('These settings apply to all users of the application.') }}</flux:subheading>
        </div>

        <div class="space-y-6 p-6">
            <flux:input
                wire:model="appName"
                :label="__('App Name')"
                :description="__('Displayed in the application header and browser title.')"
                maxlength="255"
                required
            />

            <div class="rounded-xl border border-border bg-muted/25 p-4">
                <flux:checkbox
                    wire:model="includeStrategicFunction"
                    :label="__('Include Strategic Function')"
                    :description="__('Show Strategic Function in the Annual Target table and category dropdowns.')"
                />
            </div>
        </div>

        <div class="flex justify-end border-t border-border px-6 py-4">
            <flux:button variant="primary" type="submit" icon="check" wire:loading.attr="disabled" wire:target="save">
                {{ __('Save Settings') }}
            </flux:button>
        </div>
    </form>
</section>

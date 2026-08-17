<section class="w-full">
    @include('partials.settings-heading')

    <flux:heading class="sr-only">{{ __('Appearance settings') }}</flux:heading>

    <x-pages::settings.layout :heading="__('Appearance')" :subheading="__('Update the appearance settings for your account')">
        <div class="my-6 space-y-8">
            <div class="space-y-4 rounded-2xl border border-border bg-card p-5">
                <div class="space-y-1">
                    <flux:heading size="sm">{{ __('Interface mode') }}</flux:heading>
                    <flux:subheading>{{ __('Switch between light, dark, or follow your system preference.') }}</flux:subheading>
                </div>

                <flux:radio.group x-data variant="segmented" x-model="$flux.appearance">
                    <flux:radio value="light" icon="sun">{{ __('Light') }}</flux:radio>
                    <flux:radio value="dark" icon="moon">{{ __('Dark') }}</flux:radio>
                    <flux:radio value="system" icon="computer-desktop">{{ __('System') }}</flux:radio>
                </flux:radio.group>
            </div>

            <div class="space-y-4 rounded-2xl border border-border bg-card p-5">
                <div class="space-y-1">
                    <flux:heading size="sm">{{ __('Shadcn theme palette') }}</flux:heading>
                    <flux:subheading>{{ __('Theme palettes now live in the floating dropdown at the bottom left for faster switching across the app.') }}</flux:subheading>
                </div>

                <div class="rounded-xl border border-dashed border-border bg-muted/30 p-4 text-sm text-muted-foreground">
                    {{ __('Use the fixed bottom-left theme dropdown to change the active shadcn color palette.') }}
                </div>
            </div>
        </div>
    </x-pages::settings.layout>
</section>

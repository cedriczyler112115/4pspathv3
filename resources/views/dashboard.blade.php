<x-layouts::app :title="__('Dashboard')">
    @php($user = auth()->user())
    @php($isVerified = filled($user->email_verified_at))

    <div class="flex h-full w-full flex-1 flex-col gap-4">
        <section class="grid gap-4 xl:grid-cols-[minmax(0,2fr)_minmax(300px,1fr)]">
            <div class="rounded-2xl border border-border bg-card p-4 sm:p-5 shadow-sm">
                <div class="flex flex-col gap-4 lg:flex-row lg:items-start lg:justify-between">
                    <div class="space-y-2.5">
                        <div class="space-y-0.5">
                            <p class="text-xs font-medium text-muted-foreground">{{ __('Welcome back') }}</p>
                            <h1 class="text-2xl font-semibold tracking-tight text-foreground">{{ $user->name }}</h1>
                            <p class="text-xs text-muted-foreground">{{ $user->email }}</p>
                        </div>

                        <div class="flex flex-wrap gap-1.5 text-xs font-medium">
                            <span class="rounded-full bg-secondary px-2.5 py-0.5 text-secondary-foreground">
                                {{ $isVerified ? __('Verified account') : __('Email verification pending') }}
                            </span>
                            <span class="rounded-full border border-border px-2.5 py-0.5 text-muted-foreground">
                                {{ __('Member since :date', ['date' => $user->created_at?->format('M d, Y')]) }}
                            </span>
                        </div>
                    </div>

                    <div x-data="themePreferences()" x-init="init()" class="grid gap-2.5 rounded-xl border border-border bg-background p-3 sm:min-w-64">
                        <div>
                            <p class="text-xs font-medium text-foreground">{{ __('Appearance snapshot') }}</p>
                            <p class="text-xs text-muted-foreground">{{ __('Quick view of your active shadcn theme in this browser.') }}</p>
                        </div>

                        <div class="flex items-center justify-between rounded-xl bg-muted/50 px-3 py-2">
                            <div>
                                <p class="text-[10px] uppercase tracking-[0.15em] text-muted-foreground">{{ __('Theme') }}</p>
                                <p class="text-xs font-medium text-foreground" x-text="currentThemeLabel"></p>
                            </div>

                            <div class="flex items-center gap-1.5">
                                <template x-for="swatch in themes.find((theme) => theme.id === selectedTheme)?.swatches ?? []" :key="swatch">
                                    <span class="size-3 rounded-full border border-border" :style="`background:${swatch}`"></span>
                                </template>
                            </div>
                        </div>

                        <a
                            href="{{ route('appearance.edit') }}"
                            wire:navigate
                            class="inline-flex items-center justify-center rounded-lg bg-primary px-3 py-2 text-xs font-medium text-primary-foreground transition hover:opacity-90"
                        >
                            {{ __('Manage appearance') }}
                        </a>
                    </div>
                </div>
            </div>

            <div class="rounded-2xl border border-border bg-card p-4 sm:p-5 shadow-sm">
                <div class="space-y-3">
                    <div>
                        <p class="text-xs font-medium text-foreground">{{ __('Account details') }}</p>
                        <p class="text-xs text-muted-foreground">{{ __('Your authenticated user details and quick account actions.') }}</p>
                    </div>

                    <dl class="space-y-2.5">
                        <div class="rounded-xl bg-muted/40 p-3">
                            <dt class="text-[10px] uppercase tracking-[0.15em] text-muted-foreground">{{ __('Full name') }}</dt>
                            <dd class="mt-0.5 text-xs font-medium text-foreground">{{ $user->name }}</dd>
                        </div>
                        <div class="rounded-xl bg-muted/40 p-3">
                            <dt class="text-[10px] uppercase tracking-[0.15em] text-muted-foreground">{{ __('Email address') }}</dt>
                            <dd class="mt-0.5 text-xs font-medium text-foreground">{{ $user->email }}</dd>
                        </div>
                        <div class="rounded-xl bg-muted/40 p-3">
                            <dt class="text-[10px] uppercase tracking-[0.15em] text-muted-foreground">{{ __('Profile status') }}</dt>
                            <dd class="mt-0.5 text-xs font-medium text-foreground">
                                {{ $isVerified ? __('Verified and ready') : __('Needs email verification') }}
                            </dd>
                        </div>
                    </dl>
                </div>
            </div>
        </section>

        <section class="grid gap-3 sm:grid-cols-2 lg:grid-cols-4">
            <a href="{{ route('profile.edit') }}" wire:navigate class="rounded-2xl border border-border bg-card p-4 shadow-sm transition hover:border-primary/40 hover:shadow-md">
                <p class="text-xs font-medium text-foreground">{{ __('Profile') }}</p>
                <p class="mt-1 text-xs text-muted-foreground">{{ __('Update your name, email address, and verification details.') }}</p>
            </a>

            <a href="{{ route('appearance.edit') }}" wire:navigate class="rounded-2xl border border-border bg-card p-4 shadow-sm transition hover:border-primary/40 hover:shadow-md">
                <p class="text-xs font-medium text-foreground">{{ __('Light and dark mode') }}</p>
                <p class="mt-1 text-xs text-muted-foreground">{{ __('Switch interface mode and use the floating bottom-left shadcn theme picker.') }}</p>
            </a>

            <a href="{{ route('security.edit') }}" wire:navigate class="rounded-2xl border border-border bg-card p-4 shadow-sm transition hover:border-primary/40 hover:shadow-md">
                <p class="text-xs font-medium text-foreground">{{ __('Security') }}</p>
                <p class="mt-1 text-xs text-muted-foreground">{{ __('Manage password, sessions, and other security settings.') }}</p>
            </a>

            <form method="POST" action="{{ route('logout') }}" class="h-full">
                @csrf

                <button
                    type="submit"
                    class="flex h-full w-full flex-col rounded-2xl border border-border bg-card p-4 text-left shadow-sm transition hover:border-destructive/40 hover:shadow-md"
                >
                    <span class="text-xs font-medium text-foreground">{{ __('Log out') }}</span>
                    <span class="mt-1 text-xs text-muted-foreground">{{ __('Sign out of this session and return to the home page.') }}</span>
                </button>
            </form>
        </section>
    </div>
</x-layouts::app>

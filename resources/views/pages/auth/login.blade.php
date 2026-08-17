<x-layouts::auth :title="__('Sign in to 4Ps PATH v3')" max-width="max-w-lg" :show-header="false">
    <div class="w-full px-1 sm:px-3">
        <div class="mb-6 flex justify-center">
            <a href="{{ route('home') }}" class="flex items-center gap-3" wire:navigate>
                <span
                    class="flex size-10 items-center justify-center rounded-lg border border-border bg-card shadow-sm">
                    <x-app-logo-icon class="size-6 fill-current text-foreground" />
                </span>
                <div class="text-left">
                    <p class="text-sm font-semibold leading-none text-foreground">4Ps PATH v3</p>
                    <p class="mt-1 text-xs text-muted-foreground">Performance Appraisal Tracking Hub</p>
                </div>
            </a>
        </div>

        <div class="rounded-xl border border-border bg-card p-6 text-card-foreground shadow-sm sm:p-8 space-y-6">
            <div class="flex flex-col space-y-2 text-center">
                <h1 class="text-2xl font-semibold tracking-tight">{{ __('Sign in to 4Ps PATH Version 3') }}</h1>
                <p class="mx-auto max-w-sm text-sm text-muted-foreground">
                    {{ __('Use your official Google account to access performance appraisals and tracking records.') }}
                </p>
            </div>

            <div class="space-y-6">
                <x-auth-session-status class="text-left" :status="session('status')" />

                @if ($errors->any())
                    <div class="rounded-md border border-destructive/30 bg-destructive/10 p-3 text-sm text-destructive"
                        role="alert">
                        {{ $errors->first() }}
                    </div>
                @endif

                <a href="{{ route('google.redirect') }}"
                    class="inline-flex h-10 w-full items-center justify-center gap-2 rounded-md border border-input bg-background px-4 text-sm font-medium shadow-xs transition-colors hover:bg-accent hover:text-accent-foreground focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-ring focus-visible:ring-offset-2">
                    <svg viewBox="0 0 24 24" aria-hidden="true" class="size-4">
                        <path fill="#4285F4"
                            d="M21.35 11.1h-9.17v2.98h5.27c-.23 1.4-1.63 4.1-5.27 4.1-3.17 0-5.74-2.63-5.74-5.88s2.57-5.88 5.74-5.88c1.8 0 3 .77 3.7 1.44l2.52-2.43C16.61 3.89 14.71 3 12.18 3 6.83 3 2.5 7.33 2.5 12.68s4.33 9.68 9.68 9.68c5.58 0 9.28-3.92 9.28-9.45 0-.64-.07-1.14-.11-1.81Z" />
                    </svg>
                    {{ __('Continue with official Google account') }}
                </a>

                <div class="relative">
                    <div class="absolute inset-0 flex items-center">
                        <span class="w-full border-t border-border"></span>
                    </div>
                    <div class="relative flex justify-center text-xs uppercase">
                        <span class="bg-card px-2 text-muted-foreground">{{ __('Or sign in with credentials') }}</span>
                    </div>
                </div>

                <form method="POST" action="{{ route('login.store') }}" class="space-y-6">
                    @csrf

                    <flux:input name="email" :label="__('Email Address')" :value="old('email')" type="email" required
                        autofocus autocomplete="email" placeholder="Enter your email address" />

                    <div class="space-y-2">
                        <div class="flex items-center justify-between">
                            <flux:label>{{ __('Account password') }}</flux:label>
                            @if (Route::has('password.request'))
                                <flux:link :href="route('password.request')" wire:navigate
                                    class="text-xs font-medium underline-offset-4 hover:underline">
                                    {{ __('Recover access') }}
                                </flux:link>
                            @endif
                        </div>
                        <flux:input name="password" type="password" required autocomplete="current-password"
                            :placeholder="__('Enter your account password')" viewable />
                    </div>

                    <flux:checkbox name="remember" :label="__('Keep me signed in on this device')"
                        :checked="old('remember')" />

                    <flux:button variant="primary" type="submit" class="w-full">
                        {{ __('Access 4Ps PATH') }}
                    </flux:button>
                </form>
            </div>
        </div>

        <p class="mt-6 px-4 text-center text-xs leading-5 text-muted-foreground">
            {{ __('For authorized 4Ps personnel only. Contact your system administrator if you need account assistance.') }}
        </p>
    </div>
</x-layouts::auth>
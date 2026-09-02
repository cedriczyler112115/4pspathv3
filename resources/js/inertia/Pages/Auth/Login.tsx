import { Head, useForm } from '@inertiajs/react';
import { useEffect, useState, type FormEvent } from 'react';
import { Mail, Lock, Eye, EyeOff, Loader2, ArrowRight } from 'lucide-react';
import AuthLayout from '../../Layouts/AuthLayout';
import { clearPersistedFilters } from '../../lib/filterPersistence';

type LoginProps = {
  appName?: string;
  canResetPassword?: boolean;
  status?: string | null;
};

export default function Login({ appName, canResetPassword, status }: LoginProps) {
  const [showPassword, setShowPassword] = useState(false);

  useEffect(() => {
    try {
      if (typeof window !== 'undefined') {
        clearPersistedFilters();
      }
    } catch (e) {}
  }, []);

  const form = useForm({
    email: '',
    password: '',
    remember: false,
  });

  function submit(e: FormEvent) {
    e.preventDefault();
    form.post('/login', {
      preserveScroll: true,
    });
  }

  return (
    <AuthLayout
      appName={appName}
      title="Welcome back"
      subtitle="Sign in with your organizational account to continue."
    >
      <Head title="Sign in" />

      {status && (
        <div className="mb-4 rounded-xl border border-emerald-500/30 bg-emerald-500/10 p-3 text-xs font-semibold text-emerald-700 dark:text-emerald-300">
          {status}
        </div>
      )}

      {form.errors.email && (
        <div className="mb-4 rounded-xl border border-destructive/30 bg-destructive/10 p-3 text-xs font-semibold text-destructive">
          {form.errors.email}
        </div>
      )}

      <div className="space-y-4">
        {/* GOOGLE SIGN IN BUTTON */}
        <a
          href="/auth/google/redirect"
          className="inline-flex h-10 w-full items-center justify-center gap-2.5 rounded-xl border border-border bg-background px-4 text-xs font-semibold text-foreground shadow-2xs hover:bg-muted/70 transition cursor-pointer"
        >
          <svg className="size-4 shrink-0" viewBox="0 0 24 24">
            <path
              fill="#4285F4"
              d="M22.56 12.25c0-.78-.07-1.53-.2-2.25H12v4.26h5.92c-.26 1.37-1.04 2.53-2.21 3.31v2.77h3.57c2.08-1.92 3.28-4.74 3.28-8.09z"
            />
            <path
              fill="#34A853"
              d="M12 23c2.97 0 5.46-.98 7.28-2.66l-3.57-2.77c-.98.66-2.23 1.06-3.71 1.06-2.86 0-5.29-1.93-6.16-4.53H2.18v2.84C3.99 20.53 7.7 23 12 23z"
            />
            <path
              fill="#FBBC05"
              d="M5.84 14.09c-.22-.66-.35-1.36-.35-2.09s.13-1.43.35-2.09V7.06H2.18C1.43 8.55 1 10.22 1 12s.43 3.45 1.18 4.94l2.85-2.22.81-.63z"
            />
            <path
              fill="#EA4335"
              d="M12 5.38c1.62 0 3.06.56 4.21 1.64l3.15-3.15C17.45 2.09 14.97 1 12 1 7.7 1 3.99 3.47 2.18 7.06l3.66 2.84c.87-2.6 3.3-4.52 6.16-4.52z"
            />
          </svg>
          <span>Continue with Google</span>
        </a>

        {/* DIVIDER */}
        <div className="relative py-1">
          <div className="absolute inset-0 flex items-center">
            <span className="w-full border-t border-border/70" />
          </div>
          <div className="relative flex justify-center">
            <span className="bg-card px-3 text-[10px] uppercase font-bold tracking-wider text-muted-foreground/80">
              Or sign in with email
            </span>
          </div>
        </div>

        {/* FORM */}
        <form onSubmit={submit} className="space-y-3.5">
          {/* EMAIL FIELD */}
          <div className="space-y-1">
            <label className="block text-xs font-semibold text-foreground">Email Address</label>
            <div className="relative">
              <Mail className="size-4 absolute left-3 top-1/2 -translate-y-1/2 text-muted-foreground/70 pointer-events-none" />
              <input
                type="email"
                value={form.data.email}
                onChange={(e) => form.setData('email', e.target.value)}
                className="h-10 w-full rounded-xl border border-input bg-background pl-9 pr-3 text-xs text-foreground placeholder:text-muted-foreground/50 outline-hidden focus:ring-2 focus:ring-emerald-500/20 focus:border-emerald-500 transition"
                autoComplete="email"
                placeholder="name@dswd.gov.ph"
                required
              />
            </div>
          </div>

          {/* PASSWORD FIELD */}
          <div className="space-y-1">
            <div className="flex items-center justify-between">
              <label className="block text-xs font-semibold text-foreground">Password</label>
            </div>
            <div className="relative">
              <Lock className="size-4 absolute left-3 top-1/2 -translate-y-1/2 text-muted-foreground/70 pointer-events-none" />
              <input
                type={showPassword ? 'text' : 'password'}
                value={form.data.password}
                onChange={(e) => form.setData('password', e.target.value)}
                className="h-10 w-full rounded-xl border border-input bg-background pl-9 pr-9 text-xs text-foreground placeholder:text-muted-foreground/50 outline-hidden focus:ring-2 focus:ring-emerald-500/20 focus:border-emerald-500 transition"
                autoComplete="current-password"
                placeholder="••••••••"
                required
              />
              <button
                type="button"
                onClick={() => setShowPassword(!showPassword)}
                className="absolute right-3 top-1/2 -translate-y-1/2 text-muted-foreground/70 hover:text-foreground transition cursor-pointer"
                title={showPassword ? 'Hide password' : 'Show password'}
              >
                {showPassword ? <EyeOff className="size-4" /> : <Eye className="size-4" />}
              </button>
            </div>
          </div>

          {/* REMEMBER ME */}
          <div className="flex items-center justify-between pt-0.5">
            <label className="flex items-center gap-2 cursor-pointer text-xs font-medium text-muted-foreground hover:text-foreground select-none">
              <input
                type="checkbox"
                checked={form.data.remember}
                onChange={(e) => form.setData('remember', e.target.checked)}
                className="size-4 rounded-md border-input text-emerald-600 focus:ring-emerald-500/30 cursor-pointer"
              />
              <span>Keep me signed in</span>
            </label>
          </div>

          {/* SUBMIT BUTTON */}
          <button
            type="submit"
            disabled={form.processing}
            className="inline-flex h-10 w-full items-center justify-center gap-2 rounded-xl bg-emerald-600 hover:bg-emerald-700 text-white text-xs font-bold transition shadow-md shadow-emerald-600/20 disabled:opacity-60 cursor-pointer"
          >
            {form.processing ? (
              <>
                <Loader2 className="size-4 animate-spin" />
                <span>Authenticating...</span>
              </>
            ) : (
              <>
                <span>Sign In</span>
                <ArrowRight className="size-3.5" />
              </>
            )}
          </button>
        </form>
      </div>
    </AuthLayout>
  );
}

import { Head, Link, useForm } from '@inertiajs/react';
import { useEffect, type FormEvent } from 'react';
import AuthLayout from '../../Layouts/AuthLayout';

type LoginProps = {
  canResetPassword?: boolean;
  status?: string | null;
};

export default function Login({ canResetPassword, status }: LoginProps) {
  useEffect(() => {
    try {
      if (typeof window !== 'undefined') {
        localStorage.clear();
        sessionStorage.clear();
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
      title="Sign in to your account"
      subtitle="Enter your email and password to access the platform."
    >
      <Head title="Sign in" />

      {status && (
        <div className="mb-3 rounded-md border border-emerald-500/30 bg-emerald-500/10 px-3 py-2 text-xs text-emerald-700 dark:text-emerald-300">
          {status}
        </div>
      )}

      {form.errors.email && (
        <div className="mb-3 rounded-md border border-destructive/30 bg-destructive/10 px-3 py-2 text-xs text-destructive">
          {form.errors.email}
        </div>
      )}

      <div className="space-y-3.5">
        <a
          href="/auth/google/redirect"
          className="inline-flex h-8.5 w-full items-center justify-center gap-2 rounded-md border border-input bg-background px-3 text-xs font-medium text-foreground shadow-2xs hover:bg-muted transition"
        >
          <svg className="size-3.5" viewBox="0 0 24 24">
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
          Continue with Google
        </a>

        <div className="relative py-1">
          <div className="absolute inset-0 flex items-center">
            <span className="w-full border-t border-border" />
          </div>
          <div className="relative flex justify-center">
            <span className="bg-card px-2 text-[10px] uppercase font-semibold text-muted-foreground">
              Or credentials
            </span>
          </div>
        </div>

        <form onSubmit={submit} className="space-y-3">
          <div className="space-y-1">
            <label className="block text-xs font-medium text-foreground">Email</label>
            <input
              type="email"
              value={form.data.email}
              onChange={(e) => form.setData('email', e.target.value)}
              className="h-8.5 w-full rounded-md border border-input bg-background px-2.5 text-xs text-foreground outline-hidden focus:ring-2 focus:ring-ring"
              autoComplete="email"
              placeholder="name@example.com"
              required
            />
          </div>

          <div className="space-y-1">
            <label className="block text-xs font-medium text-foreground">Password</label>
            <input
              type="password"
              value={form.data.password}
              onChange={(e) => form.setData('password', e.target.value)}
              className="h-8.5 w-full rounded-md border border-input bg-background px-2.5 text-xs text-foreground outline-hidden focus:ring-2 focus:ring-ring"
              autoComplete="current-password"
              placeholder="••••••••"
              required
            />
          </div>

          <div className="flex items-center justify-between text-xs">
            <label className="flex items-center gap-1.5 cursor-pointer text-muted-foreground hover:text-foreground">
              <input
                type="checkbox"
                checked={form.data.remember}
                onChange={(e) => form.setData('remember', e.target.checked)}
                className="size-3.5 rounded border-input text-primary focus:ring-ring"
              />
              <span>Remember me</span>
            </label>

            {canResetPassword && (
              <Link href="/forgot-password" className="text-xs text-primary hover:underline font-medium">
                Forgot password?
              </Link>
            )}
          </div>

          <button
            type="submit"
            disabled={form.processing}
            className="inline-flex h-8.5 w-full items-center justify-center rounded-md bg-primary text-primary-foreground text-xs font-medium transition hover:bg-primary/90 disabled:opacity-50"
          >
            {form.processing ? 'Signing in...' : 'Sign In'}
          </button>
        </form>
      </div>
    </AuthLayout>
  );
}

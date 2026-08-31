import { Head, Link, useForm } from '@inertiajs/react';
import type { FormEvent } from 'react';
import AuthLayout from '../../Layouts/AuthLayout';

type LoginProps = {
  canResetPassword: boolean;
  status?: string | null;
};

export default function Login({ canResetPassword, status }: LoginProps) {
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
      title="Sign in to 4Ps PATH"
      subtitle="Use your official Google account or authorized credentials to access the platform."
    >
      <Head title="Sign in" />

      <div className="mb-6 flex items-center justify-between">
        <div>
          <p className="text-xs font-semibold uppercase tracking-[0.3em] text-cyan-700">Secure access</p>
          <h2 className="mt-2 text-2xl font-semibold tracking-tight text-slate-900">Welcome back</h2>
        </div>
      </div>

      {status ? (
        <div className="mb-4 rounded-2xl border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm text-emerald-800">
          {status}
        </div>
      ) : null}

      {form.errors.email ? (
        <div className="mb-4 rounded-2xl border border-rose-200 bg-rose-50 px-4 py-3 text-sm text-rose-700">
          {form.errors.email}
        </div>
      ) : null}

      <div className="space-y-4">
        <a
          href="/auth/google/redirect"
          className="inline-flex h-11 w-full items-center justify-center gap-2 rounded-2xl border border-slate-300 bg-white px-4 text-sm font-medium text-slate-900 shadow-sm transition hover:bg-slate-50"
        >
          Continue with Google
        </a>

        <div className="relative py-2">
          <div className="absolute inset-0 flex items-center">
            <span className="w-full border-t border-slate-200" />
          </div>
          <div className="relative flex justify-center">
            <span className="bg-white px-3 text-[11px] font-semibold uppercase tracking-[0.3em] text-slate-500">
              Or use credentials
            </span>
          </div>
        </div>

        <form onSubmit={submit} className="space-y-4">
          <label className="block">
            <span className="mb-1 block text-sm font-medium text-slate-700">Email address</span>
            <input
              type="email"
              value={form.data.email}
              onChange={(e) => form.setData('email', e.target.value)}
              className="h-11 w-full rounded-2xl border border-slate-300 bg-white px-4 text-sm outline-none transition focus:border-cyan-500 focus:ring-2 focus:ring-cyan-200"
              autoComplete="email"
              required
            />
          </label>

          <label className="block">
            <span className="mb-1 block text-sm font-medium text-slate-700">Password</span>
            <input
              type="password"
              value={form.data.password}
              onChange={(e) => form.setData('password', e.target.value)}
              className="h-11 w-full rounded-2xl border border-slate-300 bg-white px-4 text-sm outline-none transition focus:border-cyan-500 focus:ring-2 focus:ring-cyan-200"
              autoComplete="current-password"
              required
            />
          </label>

          <div className="flex items-center justify-between gap-3">
            <label className="flex items-center gap-2 text-sm text-slate-600">
              <input
                type="checkbox"
                checked={form.data.remember}
                onChange={(e) => form.setData('remember', e.target.checked)}
                className="rounded border-slate-300 text-cyan-600 focus:ring-cyan-500"
              />
              Remember me
            </label>

            {canResetPassword ? (
              <Link href="/forgot-password" className="text-sm font-medium text-cyan-700 hover:text-cyan-800">
                Forgot password?
              </Link>
            ) : null}
          </div>

          <button
            type="submit"
            disabled={form.processing}
            className="inline-flex h-11 w-full items-center justify-center rounded-2xl bg-slate-950 px-4 text-sm font-medium text-white transition hover:bg-slate-800 disabled:opacity-60"
          >
            {form.processing ? 'Signing in...' : 'Sign in'}
          </button>
        </form>
      </div>
    </AuthLayout>
  );
}

import { Head, useForm } from '@inertiajs/react';
import AppLayout from '../../Layouts/AppLayout';

type Props = {
  appName: string;
  user: { name: string; email: string } | null;
  status?: string | null;
};

export default function Security({ appName, user, status }: Props) {
  const form = useForm({
    current_password: '',
    password: '',
    password_confirmation: '',
  });

  return (
    <AppLayout appName={appName} user={user}>
      <Head title="Security" />
      <div className="rounded-[2rem] border border-slate-200 bg-white p-6 shadow-[0_20px_60px_rgba(15,23,42,0.08)] sm:p-8">
        <div className="flex flex-col gap-2">
          <p className="text-xs font-semibold uppercase tracking-[0.3em] text-cyan-700">My Account</p>
          <h2 className="text-2xl font-semibold tracking-tight text-slate-900">Security settings</h2>
          <p className="max-w-2xl text-sm leading-6 text-slate-600">
            Manage your password and keep your account protected.
          </p>
        </div>

        {status ? <div className="mt-4 rounded-2xl border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm text-emerald-700">{status}</div> : null}

        <form
          className="mt-6 space-y-4"
          onSubmit={(e) => {
            e.preventDefault();
            form.patch('/inertia/settings/security', {
              preserveScroll: true,
            });
          }}
        >
          <label className="block">
            <span className="mb-1 block text-sm font-medium text-slate-700">Current password</span>
            <input
              type="password"
              value={form.data.current_password}
              onChange={(e) => form.setData('current_password', e.target.value)}
              className="h-11 w-full rounded-2xl border border-slate-300 bg-white px-4 text-sm outline-none transition focus:border-cyan-500 focus:ring-2 focus:ring-cyan-200"
              autoComplete="current-password"
              required
            />
          </label>

          <label className="block">
            <span className="mb-1 block text-sm font-medium text-slate-700">New password</span>
            <input
              type="password"
              value={form.data.password}
              onChange={(e) => form.setData('password', e.target.value)}
              className="h-11 w-full rounded-2xl border border-slate-300 bg-white px-4 text-sm outline-none transition focus:border-cyan-500 focus:ring-2 focus:ring-cyan-200"
              autoComplete="new-password"
              required
            />
          </label>

          <label className="block">
            <span className="mb-1 block text-sm font-medium text-slate-700">Confirm password</span>
            <input
              type="password"
              value={form.data.password_confirmation}
              onChange={(e) => form.setData('password_confirmation', e.target.value)}
              className="h-11 w-full rounded-2xl border border-slate-300 bg-white px-4 text-sm outline-none transition focus:border-cyan-500 focus:ring-2 focus:ring-cyan-200"
              autoComplete="new-password"
              required
            />
          </label>

          {form.errors.current_password || form.errors.password ? (
            <div className="rounded-2xl border border-rose-200 bg-rose-50 px-4 py-3 text-sm text-rose-700">
              {form.errors.current_password ?? form.errors.password}
            </div>
          ) : null}

          <div className="flex items-center gap-3">
            <button
              type="submit"
              disabled={form.processing}
              className="inline-flex h-11 items-center justify-center rounded-2xl bg-slate-950 px-5 text-sm font-medium text-white transition hover:bg-slate-800 disabled:opacity-60"
            >
              {form.processing ? 'Saving...' : 'Save'}
            </button>
          </div>
        </form>
      </div>
    </AppLayout>
  );
}

import { Head, useForm } from '@inertiajs/react';
import AppLayout from '../../Layouts/AppLayout';
import { ShieldCheck, Save } from 'lucide-react';

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
      <Head title="Security Settings - 4Ps PATH" />

      <div className="space-y-3 max-w-xl">
        <div className="rounded-xl border border-border bg-card p-3 sm:p-4 shadow-2xs space-y-3">
          {/* HEADER */}
          <div className="flex items-center gap-2.5 border-b border-border/80 pb-3">
            <div className="size-8 rounded-lg bg-emerald-500/10 text-emerald-700 dark:text-emerald-400 flex items-center justify-center font-bold">
              <ShieldCheck className="size-4.5" />
            </div>
            <div>
              <h1 className="text-sm font-bold tracking-tight text-foreground flex items-center gap-2">
                <span>Account Security</span>
              </h1>
              <p className="text-[11px] text-muted-foreground">
                Manage your account credentials and update access password.
              </p>
            </div>
          </div>

          {status && (
            <div className="rounded-lg border border-emerald-500/30 bg-emerald-500/10 px-3 py-2 text-xs text-emerald-700 dark:text-emerald-300 font-medium">
              {status}
            </div>
          )}

          <form
            className="space-y-3 pt-1"
            onSubmit={(e) => {
              e.preventDefault();
              form.patch('/settings/security', {
                preserveScroll: true,
              });
            }}
          >
            <div className="space-y-1">
              <label className="text-[11px] font-semibold text-muted-foreground">Current Password</label>
              <input
                type="password"
                value={form.data.current_password}
                onChange={(e) => form.setData('current_password', e.target.value)}
                className="h-8 w-full rounded-lg border border-input bg-background px-2.5 text-xs text-foreground outline-hidden focus:ring-2 focus:ring-ring"
                autoComplete="current-password"
                required
              />
            </div>

            <div className="space-y-1">
              <label className="text-[11px] font-semibold text-muted-foreground">New Password</label>
              <input
                type="password"
                value={form.data.password}
                onChange={(e) => form.setData('password', e.target.value)}
                className="h-8 w-full rounded-lg border border-input bg-background px-2.5 text-xs text-foreground outline-hidden focus:ring-2 focus:ring-ring"
                autoComplete="new-password"
                required
              />
            </div>

            <div className="space-y-1">
              <label className="text-[11px] font-semibold text-muted-foreground">Confirm New Password</label>
              <input
                type="password"
                value={form.data.password_confirmation}
                onChange={(e) => form.setData('password_confirmation', e.target.value)}
                className="h-8 w-full rounded-lg border border-input bg-background px-2.5 text-xs text-foreground outline-hidden focus:ring-2 focus:ring-ring"
                autoComplete="new-password"
                required
              />
            </div>

            {(form.errors.current_password || form.errors.password) && (
              <div className="rounded-lg border border-destructive/30 bg-destructive/10 px-3 py-2 text-xs text-destructive font-medium">
                {form.errors.current_password ?? form.errors.password}
              </div>
            )}

            <div className="flex justify-end pt-2 border-t border-border">
              <button
                type="submit"
                disabled={form.processing}
                className="inline-flex items-center gap-1.5 h-8 rounded-lg bg-emerald-600 px-3.5 text-xs font-semibold text-white hover:bg-emerald-700 transition shadow-xs disabled:opacity-50 cursor-pointer"
              >
                <Save className="size-3.5" />
                <span>{form.processing ? 'Updating...' : 'Update Password'}</span>
              </button>
            </div>
          </form>
        </div>
      </div>
    </AppLayout>
  );
}

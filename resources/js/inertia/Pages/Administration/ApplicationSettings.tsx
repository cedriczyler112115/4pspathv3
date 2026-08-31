import { Head, useForm } from '@inertiajs/react';
import AppLayout from '../../Layouts/AppLayout';
import { Settings as SettingsIcon, Save } from 'lucide-react';

type Props = {
  appName: string;
  user: { name: string; email: string } | null;
  settings: {
    appName: string;
    includeStrategicFunction: boolean;
    defaultYear: string;
    defaultSemester: string;
  };
  years?: string[];
  semesters?: Array<{ value: string; label: string }>;
  navigation?: { sidebar?: any[] };
};

export default function ApplicationSettings({ appName, user, settings, years = [], semesters = [], navigation }: Props) {
  const form = useForm({
    appName: settings.appName,
    includeStrategicFunction: settings.includeStrategicFunction,
    defaultYear: settings.defaultYear || '2026',
    defaultSemester: settings.defaultSemester || '1',
  });

  const submit = (e: React.FormEvent) => {
    e.preventDefault();
    form.patch('/inertia/administration/settings');
  };

  return (
    <AppLayout appName={appName} user={user} sidebar={navigation?.sidebar ?? []}>
      <Head title="Application Settings - Administration" />

      <div className="space-y-3 max-w-2xl">
        <div className="rounded-xl border border-border bg-card p-3 sm:p-4 shadow-2xs space-y-3">
          {/* HEADER */}
          <div className="flex items-center gap-2.5 border-b border-border/80 pb-3">
            <div className="size-8 rounded-lg bg-emerald-500/10 text-emerald-700 dark:text-emerald-400 flex items-center justify-center font-bold">
              <SettingsIcon className="size-4.5" />
            </div>
            <div>
              <h1 className="text-sm font-bold tracking-tight text-foreground flex items-center gap-2">
                <span>Application Settings</span>
              </h1>
              <p className="text-[11px] text-muted-foreground">
                Manage system title, branding, default period filters, and target configurations.
              </p>
            </div>
          </div>

          <form onSubmit={submit} className="space-y-4 pt-1">
            <div className="space-y-1">
              <label className="text-[11px] font-semibold text-muted-foreground" htmlFor="appName">
                Application Title
              </label>
              <input
                id="appName"
                value={form.data.appName}
                onChange={(e) => form.setData('appName', e.target.value)}
                className="h-8 w-full rounded-lg border border-input bg-background px-2.5 text-xs text-foreground outline-hidden focus:ring-2 focus:ring-ring"
                maxLength={255}
                required
              />
              <p className="text-[10px] text-muted-foreground">Displayed in the header, navbar branding, and browser title bar.</p>
            </div>

            {/* DEFAULT YEAR & SEMESTER */}
            <div className="grid gap-3 sm:grid-cols-2 rounded-lg border border-border bg-muted/10 p-3">
              <div className="space-y-1">
                <label className="text-[11px] font-semibold text-muted-foreground" htmlFor="defaultYear">
                  Default Target Year
                </label>
                <select
                  id="defaultYear"
                  value={form.data.defaultYear}
                  onChange={(e) => form.setData('defaultYear', e.target.value)}
                  className="h-8 w-full rounded-lg border border-input bg-background px-2.5 text-xs text-foreground outline-hidden focus:ring-2 focus:ring-ring cursor-pointer"
                  required
                >
                  {years.map((yr) => (
                    <option key={yr} value={yr}>
                      {yr}
                    </option>
                  ))}
                </select>
                <p className="text-[10px] text-muted-foreground">Default year pre-selected across all filters and target forms.</p>
              </div>

              <div className="space-y-1">
                <label className="text-[11px] font-semibold text-muted-foreground" htmlFor="defaultSemester">
                  Default Semester
                </label>
                <select
                  id="defaultSemester"
                  value={form.data.defaultSemester}
                  onChange={(e) => form.setData('defaultSemester', e.target.value)}
                  className="h-8 w-full rounded-lg border border-input bg-background px-2.5 text-xs text-foreground outline-hidden focus:ring-2 focus:ring-ring cursor-pointer"
                  required
                >
                  {semesters.map((sem) => (
                    <option key={sem.value} value={sem.value}>
                      {sem.label}
                    </option>
                  ))}
                </select>
                <p className="text-[10px] text-muted-foreground">Default semester pre-selected across all filters and semestral forms.</p>
              </div>
            </div>

            <label className="flex items-start gap-2.5 rounded-lg border border-border bg-muted/20 p-2.5 cursor-pointer hover:bg-muted/40 transition">
              <input
                type="checkbox"
                checked={form.data.includeStrategicFunction}
                onChange={(e) => form.setData('includeStrategicFunction', e.target.checked)}
                className="mt-0.5 size-3.5 rounded border-input text-emerald-600 focus:ring-emerald-500"
              />
              <div className="text-xs">
                <span className="font-semibold text-foreground block">Include Strategic Function</span>
                <span className="text-[11px] text-muted-foreground block">
                  Show Strategic Function category in the Annual Target matrices, rating tables, and dropdowns.
                </span>
              </div>
            </label>

            <div className="flex justify-end pt-2 border-t border-border">
              <button
                type="submit"
                disabled={form.processing}
                className="inline-flex items-center gap-1.5 h-8 rounded-lg bg-emerald-600 px-3.5 text-xs font-semibold text-white hover:bg-emerald-700 transition shadow-xs disabled:opacity-50 cursor-pointer"
              >
                <Save className="size-3.5" />
                <span>{form.processing ? 'Saving...' : 'Save Settings'}</span>
              </button>
            </div>
          </form>
        </div>
      </div>
    </AppLayout>
  );
}

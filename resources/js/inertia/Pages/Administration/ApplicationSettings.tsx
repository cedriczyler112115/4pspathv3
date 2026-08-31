import { Head, useForm } from '@inertiajs/react';
import AppLayout from '../../Layouts/AppLayout';

type Props = {
  appName: string;
  user: { name: string; email: string } | null;
  settings: {
    appName: string;
    includeStrategicFunction: boolean;
  };
  navigation?: { sidebar?: any[] };
};

export default function ApplicationSettings({ appName, user, settings, navigation }: Props) {
  const form = useForm({
    appName: settings.appName,
    includeStrategicFunction: settings.includeStrategicFunction,
  });

  const submit = (e: React.FormEvent) => {
    e.preventDefault();
    form.patch('/inertia/administration/settings');
  };

  return (
    <AppLayout appName={appName} user={user} sidebar={navigation?.sidebar ?? []}>
      <Head title="Application Settings" />
      <div className="space-y-6 rounded-[2rem] border border-slate-200 bg-white p-6 shadow-[0_20px_60px_rgba(15,23,42,0.08)] sm:p-8">
        <div className="flex flex-col gap-2">
          <p className="text-xs font-semibold uppercase tracking-[0.3em] text-cyan-700">Administration</p>
          <h2 className="text-2xl font-semibold tracking-tight text-slate-900">Application Settings</h2>
          <p className="max-w-2xl text-sm leading-6 text-slate-600">Manage the application identity and Annual Target categories.</p>
        </div>

        <form onSubmit={submit} className="max-w-3xl rounded-2xl border border-slate-200 bg-slate-50 p-6">
          <div className="space-y-6">
            <div>
              <label className="mb-2 block text-sm font-medium text-slate-700" htmlFor="appName">App Name</label>
              <input
                id="appName"
                value={form.data.appName}
                onChange={(e) => form.setData('appName', e.target.value)}
                className="h-11 w-full rounded-2xl border border-slate-300 px-4 text-sm"
                maxLength={255}
                required
              />
              <p className="mt-1 text-xs text-slate-500">Displayed in the application header and browser title.</p>
            </div>

            <label className="flex items-start gap-3 rounded-2xl border border-slate-200 bg-white p-4">
              <input
                type="checkbox"
                checked={form.data.includeStrategicFunction}
                onChange={(e) => form.setData('includeStrategicFunction', e.target.checked)}
                className="mt-1 h-4 w-4 rounded border-slate-300"
              />
              <span>
                <span className="block text-sm font-medium text-slate-800">Include Strategic Function</span>
                <span className="block text-xs text-slate-500">Show Strategic Function in the Annual Target table and category dropdowns.</span>
              </span>
            </label>
          </div>

          <div className="mt-6 flex justify-end">
            <button type="submit" className="h-11 rounded-2xl bg-slate-950 px-5 text-sm font-medium text-white">
              Save Settings
            </button>
          </div>
        </form>
      </div>
    </AppLayout>
  );
}

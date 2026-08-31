import { Head } from '@inertiajs/react';
import AppLayout from '../../Layouts/AppLayout';

type Props = {
  appName: string;
  user: { name: string; email: string } | null;
  theme: string;
  appearance: string;
};

export default function Appearance({ appName, user, theme, appearance }: Props) {
  return (
    <AppLayout appName={appName} user={user}>
      <Head title="Appearance" />
      <div className="rounded-[2rem] border border-slate-200 bg-white p-6 shadow-[0_20px_60px_rgba(15,23,42,0.08)] sm:p-8">
        <div className="flex flex-col gap-2">
          <p className="text-xs font-semibold uppercase tracking-[0.3em] text-cyan-700">Appearance</p>
          <h2 className="text-2xl font-semibold tracking-tight text-slate-900">Appearance settings</h2>
          <p className="max-w-2xl text-sm leading-6 text-slate-600">
            Update the interface mode and theme palette for this browser.
          </p>
        </div>

        <div className="mt-6 grid gap-4">
          <div className="rounded-2xl border border-slate-200 bg-slate-50 p-4">
            <div className="text-sm font-medium text-slate-900">Interface mode</div>
            <div className="mt-1 text-sm text-slate-600">Switch between light, dark, or system appearance.</div>
            <div className="mt-4 flex flex-wrap gap-2">
              {(['light', 'dark', 'system'] as const).map((mode) => (
                <button
                  key={mode}
                  type="button"
                  onClick={() => {
                    window.localStorage.setItem('flux.appearance', mode);
                    window.dispatchEvent(new StorageEvent('storage', { key: 'flux.appearance', newValue: mode }));
                  }}
                  className={`rounded-full px-4 py-2 text-sm font-medium transition ${
                    appearance === mode ? 'bg-slate-950 text-white' : 'border border-slate-300 bg-white text-slate-700 hover:bg-slate-50'
                  }`}
                >
                  {mode[0].toUpperCase() + mode.slice(1)}
                </button>
              ))}
            </div>
          </div>

          <div className="rounded-2xl border border-slate-200 bg-slate-50 p-4">
            <div className="text-sm font-medium text-slate-900">Theme palette</div>
            <div className="mt-1 text-sm text-slate-600">
              Current theme: <span className="font-medium text-slate-900">{theme}</span>
            </div>
            <p className="mt-3 text-sm text-slate-600">
              The full theme picker still lives in the fixed app sidebar, so this page can focus on the account-level appearance choice.
            </p>
          </div>
        </div>
      </div>
    </AppLayout>
  );
}

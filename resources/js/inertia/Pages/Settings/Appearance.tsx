import { Head } from '@inertiajs/react';
import AppLayout from '../../Layouts/AppLayout';
import { Sliders, Sun, Moon, Monitor } from 'lucide-react';

type Props = {
  appName: string;
  user: { name: string; email: string } | null;
  theme: string;
  appearance: string;
};

export default function Appearance({ appName, user, theme, appearance }: Props) {
  const setMode = (mode: 'light' | 'dark' | 'system') => {
    if (mode === 'dark') {
      document.documentElement.classList.add('dark');
      window.localStorage.setItem('flux.appearance', 'dark');
      window.localStorage.setItem('lgu_appearance', 'dark');
    } else if (mode === 'light') {
      document.documentElement.classList.remove('dark');
      window.localStorage.setItem('flux.appearance', 'light');
      window.localStorage.setItem('lgu_appearance', 'light');
    } else {
      const systemDark = window.matchMedia('(prefers-color-scheme: dark)').matches;
      document.documentElement.classList.toggle('dark', systemDark);
      window.localStorage.removeItem('flux.appearance');
      window.localStorage.removeItem('lgu_appearance');
    }
  };

  return (
    <AppLayout appName={appName} user={user}>
      <Head title="Appearance Settings - 4Ps PATH" />

      <div className="space-y-3 max-w-2xl">
        <div className="rounded-xl border border-border bg-card p-3 sm:p-4 shadow-2xs space-y-3">
          {/* HEADER */}
          <div className="flex items-center gap-2.5 border-b border-border/80 pb-3">
            <div className="size-8 rounded-lg bg-emerald-500/10 text-emerald-700 dark:text-emerald-400 flex items-center justify-center font-bold">
              <Sliders className="size-4.5" />
            </div>
            <div>
              <h1 className="text-sm font-bold tracking-tight text-foreground flex items-center gap-2">
                <span>Appearance &amp; Theme</span>
              </h1>
              <p className="text-[11px] text-muted-foreground">
                Customize interface theme, color schemes, and light/dark display mode.
              </p>
            </div>
          </div>

          <div className="space-y-3 pt-1">
            <div className="rounded-lg border border-border bg-muted/20 p-3 space-y-2">
              <div>
                <div className="text-xs font-bold text-foreground">Interface Display Mode</div>
                <div className="text-[11px] text-muted-foreground">Select your preferred viewing theme.</div>
              </div>
              <div className="flex flex-wrap gap-2 pt-1">
                <button
                  type="button"
                  onClick={() => setMode('light')}
                  className="inline-flex items-center gap-1.5 h-8 rounded-lg border border-input bg-background px-3 text-xs font-semibold text-foreground hover:bg-muted transition cursor-pointer shadow-2xs"
                >
                  <Sun className="size-3.5 text-amber-500" />
                  <span>Light Mode</span>
                </button>
                <button
                  type="button"
                  onClick={() => setMode('dark')}
                  className="inline-flex items-center gap-1.5 h-8 rounded-lg border border-input bg-background px-3 text-xs font-semibold text-foreground hover:bg-muted transition cursor-pointer shadow-2xs"
                >
                  <Moon className="size-3.5 text-indigo-400" />
                  <span>Dark Mode</span>
                </button>
                <button
                  type="button"
                  onClick={() => setMode('system')}
                  className="inline-flex items-center gap-1.5 h-8 rounded-lg border border-input bg-background px-3 text-xs font-semibold text-foreground hover:bg-muted transition cursor-pointer shadow-2xs"
                >
                  <Monitor className="size-3.5 text-muted-foreground" />
                  <span>System Auto</span>
                </button>
              </div>
            </div>
          </div>
        </div>
      </div>
    </AppLayout>
  );
}

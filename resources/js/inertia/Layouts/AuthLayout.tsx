import type { PropsWithChildren } from 'react';
import { usePage } from '@inertiajs/react';
import ToastContainer from '../Components/ToastContainer';

type AuthLayoutProps = PropsWithChildren<{
  title?: string;
  subtitle?: string;
  appName?: string;
}>;

export default function AuthLayout({ title, subtitle, appName: propAppName, children }: AuthLayoutProps) {
  const { appName: sharedAppName } = usePage<{ appName?: string }>().props;
  const displayAppName = propAppName || sharedAppName || '4Ps PATH v3';

  // Compute initials for logo badge (e.g., "4P" from "4Ps PATH v3")
  const badgeText = displayAppName.startsWith('4P')
    ? '4P'
    : displayAppName
        .split(' ')
        .map((w) => w.charAt(0))
        .join('')
        .substring(0, 3)
        .toUpperCase() || '4P';

  return (
    <div className="relative min-h-screen bg-slate-50 dark:bg-slate-950 text-foreground flex flex-col justify-center items-center p-4 sm:p-6 antialiased selection:bg-emerald-500 selection:text-white">
      {/* BACKGROUND DECORATIVE GLOW */}
      <div className="absolute inset-0 overflow-hidden pointer-events-none">
        <div className="absolute -top-40 -left-40 size-96 rounded-full bg-emerald-500/10 dark:bg-emerald-500/5 blur-3xl" />
        <div className="absolute -bottom-40 -right-40 size-96 rounded-full bg-teal-500/10 dark:bg-teal-500/5 blur-3xl" />
      </div>

      <div className="relative w-full max-w-md space-y-5">
        {/* BRAND HEADER */}
        <div className="flex flex-col items-center text-center space-y-2">
          <div className="h-20 w-28 rounded-2xl bg-white dark:bg-slate-900 border border-slate-200/80 dark:border-slate-800 p-1 shadow-xl shadow-slate-200/50 dark:shadow-none flex items-center justify-center ring-4 ring-emerald-500/10 transition-transform hover:scale-105 overflow-hidden">
            <img src="/logos/dswd.png" alt="DSWD Logo" className="w-full h-full object-contain" />
          </div>
          <div>
            <h1 className="text-xl font-bold tracking-tight text-foreground sm:text-2xl">
              {displayAppName}
            </h1>
            <p className="text-xs text-muted-foreground mt-0.5 font-medium">
              Performance Assessment &amp; Tracking Harmonizer
            </p>
          </div>
        </div>

        {/* AUTH CARD CONTAINER */}
        <div className="rounded-2xl border border-border/80 bg-card p-6 sm:p-8 shadow-xl shadow-slate-200/50 dark:shadow-none backdrop-blur-xl">
          {title && (
            <div className="mb-5 text-center sm:text-left">
              <h2 className="text-base font-semibold text-card-foreground tracking-tight">{title}</h2>
              {subtitle && <p className="text-xs text-muted-foreground mt-1">{subtitle}</p>}
            </div>
          )}
          {children}
        </div>

        {/* FOOTER */}
        <div className="text-center text-[11px] text-muted-foreground font-medium">
          &copy; {new Date().getFullYear()} DSWD Pantawid Pamilyang Pilipino Program. All rights reserved.
        </div>
      </div>

      <ToastContainer />
    </div>
  );
}

import type { PropsWithChildren } from 'react';
import { Link } from '@inertiajs/react';
import ToastContainer from '../Components/ToastContainer';

type AuthLayoutProps = PropsWithChildren<{
  title?: string;
  subtitle?: string;
}>;

export default function AuthLayout({ title, subtitle, children }: AuthLayoutProps) {
  return (
    <div className="min-h-screen bg-muted/40 text-foreground flex flex-col justify-center items-center p-4 sm:p-6 antialiased">
      <div className="w-full max-w-md">
        {/* LOGO BRAND HEADER */}
        <div className="flex flex-col items-center mb-6 text-center">
          <div className="size-10 rounded-lg bg-emerald-700 text-white flex items-center justify-center font-black text-sm shadow-md mb-2">
            4P
          </div>
          <h1 className="text-lg font-bold tracking-tight text-foreground">4Ps PATH v3</h1>
          <p className="text-xs text-muted-foreground mt-0.5">IPCRF &amp; Performance Tracking System</p>
        </div>

        {/* AUTH CARD */}
        <div className="rounded-xl border border-border bg-card p-5 sm:p-6 shadow-sm">
          {title && (
            <div className="mb-4">
              <h2 className="text-base font-semibold text-card-foreground">{title}</h2>
              {subtitle && <p className="text-xs text-muted-foreground mt-0.5">{subtitle}</p>}
            </div>
          )}
          {children}
        </div>

        {/* FOOTER LINKS */}
        <div className="mt-4 flex items-center justify-center gap-4 text-xs text-muted-foreground">
          <Link href="/inertia/dashboard" className="hover:text-foreground transition">
            Inertia App
          </Link>
          <span>•</span>
          <Link href="/dashboard" className="hover:text-foreground transition">
            Livewire App
          </Link>
        </div>
      </div>
      <ToastContainer />
    </div>
  );
}

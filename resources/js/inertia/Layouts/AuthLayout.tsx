import type { PropsWithChildren } from 'react';
import { Link } from '@inertiajs/react';
import ToastContainer from '../Components/ToastContainer';

type AuthLayoutProps = PropsWithChildren<{
  title: string;
  subtitle: string;
}>;

export default function AuthLayout({ title, subtitle, children }: AuthLayoutProps) {
  return (
    <div className="min-h-screen bg-[radial-gradient(circle_at_top,_rgba(15,23,42,0.92),_rgba(15,23,42,0.98)_40%,_rgba(241,245,249,1)_40%,_rgba(248,250,252,1)_100%)] px-4 py-10 text-slate-900 sm:px-6 lg:px-8">
      <div className="mx-auto flex min-h-[calc(100vh-5rem)] w-full max-w-6xl items-center">
        <div className="grid w-full gap-6 lg:grid-cols-[0.95fr_1.05fr]">
          <aside className="rounded-[2rem] border border-white/10 bg-slate-950 p-6 text-white shadow-[0_30px_80px_rgba(15,23,42,0.3)] sm:p-8">
            <div className="flex h-full flex-col justify-between gap-8">
              <div>
                <p className="text-[11px] font-semibold uppercase tracking-[0.35em] text-cyan-300">4Ps PATH v3</p>
                <h1 className="mt-4 max-w-md text-4xl font-semibold tracking-tight sm:text-5xl">{title}</h1>
                <p className="mt-4 max-w-md text-sm leading-6 text-slate-300 sm:text-base">{subtitle}</p>
              </div>

              <div className="grid gap-3 rounded-3xl border border-white/10 bg-white/5 p-4">
                <p className="text-xs font-semibold uppercase tracking-[0.3em] text-cyan-200">Paths</p>
                <Link href="/inertia/dashboard" className="rounded-2xl border border-white/10 px-4 py-3 text-sm text-white/90 transition hover:bg-white/10">
                  Inertia dashboard
                </Link>
                <Link href="/dashboard" className="rounded-2xl border border-white/10 px-4 py-3 text-sm text-white/90 transition hover:bg-white/10">
                  Livewire dashboard
                </Link>
              </div>
            </div>
          </aside>

          <section className="rounded-[2rem] border border-slate-200 bg-white p-6 shadow-[0_30px_80px_rgba(15,23,42,0.08)] sm:p-8">
            {children}
          </section>
        </div>
      </div>
      <ToastContainer />
    </div>
  );
}

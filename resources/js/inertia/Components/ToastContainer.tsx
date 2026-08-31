import { useEffect, useState, useRef } from 'react';
import { usePage } from '@inertiajs/react';
import { CheckCircle2, AlertCircle, AlertTriangle, Info, X } from 'lucide-react';

export type ToastVariant = 'success' | 'danger' | 'error' | 'warning' | 'info';

export interface ToastMessage {
  id: string;
  variant: ToastVariant;
  text: string;
  title?: string;
}

export function toast(options: { text: string; variant?: ToastVariant; title?: string }) {
  if (typeof window !== 'undefined') {
    window.dispatchEvent(
      new CustomEvent('inertia-toast', {
        detail: {
          text: options.text,
          variant: options.variant || 'success',
          title: options.title,
        },
      })
    );
  }
}

export default function ToastContainer() {
  const [activeToast, setActiveToast] = useState<ToastMessage | null>(null);
  const timerRef = useRef<NodeJS.Timeout | null>(null);
  const { props } = usePage<{
    flash?: {
      success?: string | null;
      error?: string | null;
      danger?: string | null;
      warning?: string | null;
      info?: string | null;
      message?: string | null;
    };
    'flash.success'?: string | null;
    'flash.error'?: string | null;
  }>();

  const dismissToast = () => {
    if (timerRef.current) {
      clearTimeout(timerRef.current);
      timerRef.current = null;
    }
    setActiveToast(null);
  };

  const showToast = (variant: ToastVariant, text: string, title?: string) => {
    if (!text || !text.trim()) return;

    if (timerRef.current) {
      clearTimeout(timerRef.current);
      timerRef.current = null;
    }

    const id = Math.random().toString(36).substring(2, 9) + Date.now().toString(36);
    setActiveToast({ id, variant, text, title });

    timerRef.current = setTimeout(() => {
      setActiveToast(null);
      timerRef.current = null;
    }, 3500);
  };

  // Listen to Inertia flash page props
  useEffect(() => {
    const flash = props.flash || {};
    const successMsg = flash.success || props['flash.success'];
    const errorMsg = flash.error || flash.danger || props['flash.error'];
    const warningMsg = flash.warning;
    const infoMsg = flash.info || flash.message;

    if (successMsg) showToast('success', successMsg);
    else if (errorMsg) showToast('danger', errorMsg);
    else if (warningMsg) showToast('warning', warningMsg);
    else if (infoMsg) showToast('info', infoMsg);
  }, [props.flash, props['flash.success'], props['flash.error']]);

  // Listen to custom window events for manual toast trigger
  useEffect(() => {
    const handleCustomToast = (event: Event) => {
      const customEvent = event as CustomEvent<{ text: string; variant?: ToastVariant; title?: string }>;
      if (customEvent.detail) {
        const { text, variant = 'success', title } = customEvent.detail;
        showToast(variant, text, title);
      }
    };

    window.addEventListener('inertia-toast', handleCustomToast);
    return () => {
      window.removeEventListener('inertia-toast', handleCustomToast);
      if (timerRef.current) {
        clearTimeout(timerRef.current);
      }
    };
  }, []);

  if (!activeToast) {
    return null;
  }

  const isSuccess = activeToast.variant === 'success';
  const isDanger = activeToast.variant === 'danger' || activeToast.variant === 'error';
  const isWarning = activeToast.variant === 'warning';
  const isInfo = activeToast.variant === 'info';

  return (
    <div
      aria-live="polite"
      className="fixed top-3 right-3 z-[9999] max-w-sm w-full pointer-events-none px-3 sm:px-0"
    >
      <div
        key={activeToast.id}
        className={`pointer-events-auto flex items-start gap-2.5 p-3 rounded-xl border shadow-lg backdrop-blur-md transition-all duration-200 animate-in fade-in slide-in-from-top-2 ${
          isSuccess
            ? 'bg-emerald-50/95 dark:bg-emerald-950/90 border-emerald-300 dark:border-emerald-700/60 text-emerald-950 dark:text-emerald-50 shadow-emerald-500/10'
            : isDanger
            ? 'bg-rose-50/95 dark:bg-rose-950/90 border-rose-300 dark:border-rose-700/60 text-rose-950 dark:text-rose-50 shadow-rose-500/10'
            : isWarning
            ? 'bg-amber-50/95 dark:bg-amber-950/90 border-amber-300 dark:border-amber-700/60 text-amber-950 dark:text-amber-50 shadow-amber-500/10'
            : 'bg-sky-50/95 dark:bg-sky-950/90 border-sky-300 dark:border-sky-700/60 text-sky-950 dark:text-sky-50 shadow-sky-500/10'
        }`}
      >
        {/* Icon */}
        <div className="shrink-0 mt-0.5">
          {isSuccess && <CheckCircle2 className="w-4 h-4 text-emerald-600 dark:text-emerald-400" />}
          {isDanger && <AlertCircle className="w-4 h-4 text-rose-600 dark:text-rose-400" />}
          {isWarning && <AlertTriangle className="w-4 h-4 text-amber-600 dark:text-amber-400" />}
          {isInfo && <Info className="w-4 h-4 text-sky-600 dark:text-sky-400" />}
        </div>

        {/* Content */}
        <div className="flex-1 min-w-0">
          {activeToast.title && (
            <h4 className="text-[11px] font-bold uppercase tracking-wider mb-0.5 opacity-90">{activeToast.title}</h4>
          )}
          <p className="text-xs font-semibold leading-snug break-words">{activeToast.text}</p>
        </div>

        {/* Dismiss Button */}
        <button
          type="button"
          onClick={dismissToast}
          className="shrink-0 text-slate-400 hover:text-slate-700 dark:hover:text-slate-200 p-0.5 rounded-lg transition"
          aria-label="Close notification"
        >
          <X className="w-3.5 h-3.5" />
        </button>
      </div>
    </div>
  );
}

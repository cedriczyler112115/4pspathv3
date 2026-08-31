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
  const [toasts, setToasts] = useState<ToastMessage[]>([]);
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

  const lastFlashKeyRef = useRef<string>('');

  const removeToast = (id: string) => {
    setToasts((prev) => prev.filter((t) => t.id !== id));
  };

  const addToast = (variant: ToastVariant, text: string, title?: string) => {
    if (!text || !text.trim()) return;
    const id = Math.random().toString(36).substring(2, 9) + Date.now().toString(36);
    const newToast: ToastMessage = { id, variant, text, title };

    setToasts((prev) => [...prev, newToast]);

    setTimeout(() => {
      removeToast(id);
    }, 4500);
  };

  // Listen to Inertia flash page props
  useEffect(() => {
    const flash = props.flash || {};
    const successMsg = flash.success || props['flash.success'];
    const errorMsg = flash.error || flash.danger || props['flash.error'];
    const warningMsg = flash.warning;
    const infoMsg = flash.info || flash.message;

    const flashKey = `${successMsg || ''}|${errorMsg || ''}|${warningMsg || ''}|${infoMsg || ''}`;

    if (flashKey !== '|||' && flashKey !== lastFlashKeyRef.current) {
      lastFlashKeyRef.current = flashKey;

      if (successMsg) addToast('success', successMsg);
      if (errorMsg) addToast('danger', errorMsg);
      if (warningMsg) addToast('warning', warningMsg);
      if (infoMsg) addToast('info', infoMsg);
    }
  }, [props.flash, props['flash.success'], props['flash.error']]);

  // Listen to custom window events for manual toast trigger
  useEffect(() => {
    const handleCustomToast = (event: Event) => {
      const customEvent = event as CustomEvent<{ text: string; variant?: ToastVariant; title?: string }>;
      if (customEvent.detail) {
        const { text, variant = 'success', title } = customEvent.detail;
        addToast(variant, text, title);
      }
    };

    window.addEventListener('inertia-toast', handleCustomToast);
    return () => {
      window.removeEventListener('inertia-toast', handleCustomToast);
    };
  }, []);

  if (toasts.length === 0) {
    return null;
  }

  return (
    <div
      aria-live="polite"
      className="fixed top-4 right-4 z-[9999] flex flex-col gap-2.5 max-w-sm w-full pointer-events-none px-4 sm:px-0"
    >
      {toasts.map((toastItem) => {
        const isSuccess = toastItem.variant === 'success';
        const isDanger = toastItem.variant === 'danger' || toastItem.variant === 'error';
        const isWarning = toastItem.variant === 'warning';
        const isInfo = toastItem.variant === 'info';

        return (
          <div
            key={toastItem.id}
            className={`pointer-events-auto flex items-start gap-3 p-4 rounded-xl border shadow-lg backdrop-blur-md transition-all duration-300 animate-in fade-in slide-in-from-top-4 ${
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
              {isSuccess && <CheckCircle2 className="w-5 h-5 text-emerald-600 dark:text-emerald-400" />}
              {isDanger && <AlertCircle className="w-5 h-5 text-rose-600 dark:text-rose-400" />}
              {isWarning && <AlertTriangle className="w-5 h-5 text-amber-600 dark:text-amber-400" />}
              {isInfo && <Info className="w-5 h-5 text-sky-600 dark:text-sky-400" />}
            </div>

            {/* Content */}
            <div className="flex-1 min-w-0">
              {toastItem.title && (
                <h4 className="text-xs font-bold uppercase tracking-wider mb-0.5 opacity-90">{toastItem.title}</h4>
              )}
              <p className="text-xs font-semibold leading-snug break-words">{toastItem.text}</p>
            </div>

            {/* Dismiss Button */}
            <button
              type="button"
              onClick={() => removeToast(toastItem.id)}
              className="shrink-0 text-slate-400 hover:text-slate-700 dark:hover:text-slate-200 p-0.5 rounded-lg transition"
              aria-label="Close notification"
            >
              <X className="w-4 h-4" />
            </button>
          </div>
        );
      })}
    </div>
  );
}

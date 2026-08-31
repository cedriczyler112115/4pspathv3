import React, { useEffect, useRef, useImperativeHandle, forwardRef } from 'react';
import { formatTextValue } from './FormattedText';

export const adjustTextareaHeight = (el: HTMLTextAreaElement | null) => {
  if (!el) return;
  el.style.resize = 'none';
  el.style.overflow = 'hidden';
  el.style.height = 'auto';

  const computed = window.getComputedStyle(el);
  const borderTop = parseFloat(computed.borderTopWidth) || 0;
  const borderBottom = parseFloat(computed.borderBottomWidth) || 0;
  const borderHeight = borderTop + borderBottom;

  const targetHeight = el.scrollHeight + borderHeight;
  el.style.height = `${targetHeight}px`;
};

export interface AutoResizingTextareaProps
  extends React.TextareaHTMLAttributes<HTMLTextAreaElement> {
  value?: string | number | readonly string[];
}

const AutoResizingTextarea = forwardRef<HTMLTextAreaElement, AutoResizingTextareaProps>(
  ({ value, onChange, onInput, className = '', style = {}, rows = 2, ...props }, ref) => {
    const textareaRef = useRef<HTMLTextAreaElement | null>(null);

    useImperativeHandle(ref, () => textareaRef.current!);

    const adjust = () => {
      adjustTextareaHeight(textareaRef.current);
    };

    const displayValue = React.useMemo(() => {
      if (typeof value === 'string' && /<br\s*\/?>/i.test(value)) {
        return formatTextValue(value, '');
      }
      return value ?? '';
    }, [value]);

    useEffect(() => {
      adjust();
      const rafId = requestAnimationFrame(() => {
        adjust();
      });
      return () => cancelAnimationFrame(rafId);
    }, [displayValue]);

    useEffect(() => {
      const el = textareaRef.current;
      if (!el || typeof ResizeObserver === 'undefined') return;

      const observer = new ResizeObserver(() => {
        adjust();
      });
      observer.observe(el);
      return () => observer.disconnect();
    }, []);

    return (
      <textarea
        ref={textareaRef}
        rows={rows}
        value={displayValue}
        onChange={(e) => {
          onChange?.(e);
          adjust();
        }}
        onInput={(e) => {
          onInput?.(e);
          adjust();
        }}
        className={`resize-none overflow-hidden ${className}`}
        style={{
          resize: 'none',
          overflow: 'hidden',
          ...style,
        }}
        {...props}
      />
    );
  }
);

AutoResizingTextarea.displayName = 'AutoResizingTextarea';

export default AutoResizingTextarea;

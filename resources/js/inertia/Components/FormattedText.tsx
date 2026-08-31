import React from 'react';

type FormattedTextProps = {
  value: string | null | undefined;
  className?: string;
  fallback?: string;
};

export function formatTextValue(value: string | null | undefined, fallback = '-'): string {
  if (value === null || value === undefined) return fallback;

  let text = String(value);

  // Convert <br> or <br/> tags into newlines & decode common HTML entities
  text = text
    .replace(/<br\s*\/?>/gi, '\n')
    .replace(/&nbsp;/gi, ' ')
    .replace(/&amp;/gi, '&')
    .replace(/&lt;/gi, '<')
    .replace(/&gt;/gi, '>')
    .replace(/&quot;/gi, '"')
    .replace(/&#039;/gi, "'");

  // Normalize \r\n and \r to \n
  text = text.replace(/\r\n/g, '\n').replace(/\r/g, '\n').trim();

  return text || fallback;
}

export default function FormattedText({
  value,
  className = '',
  fallback = '-',
}: FormattedTextProps) {
  const formatted = formatTextValue(value, fallback);

  if (!formatted || formatted === fallback) {
    return <span className={`text-slate-400 dark:text-slate-500 ${className}`}>{fallback}</span>;
  }

  // Check if string contains HTML tags like <b>, <i>, <ul>, etc.
  const containsHtml = /<[a-z][\s\S]*>/i.test(formatted);

  if (containsHtml) {
    return (
      <span
        className={`whitespace-pre-line leading-relaxed ${className}`}
        dangerouslySetInnerHTML={{ __html: formatted.replace(/\n/g, '<br />') }}
      />
    );
  }

  const lines = formatted.split('\n');

  return (
    <span className={`whitespace-pre-line leading-relaxed ${className}`}>
      {lines.map((line, idx) => (
        <React.Fragment key={idx}>
          {line}
          {idx < lines.length - 1 && <br />}
        </React.Fragment>
      ))}
    </span>
  );
}

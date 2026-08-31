import React, { useEffect, useRef, useState } from 'react';
import {
  Bold,
  Italic,
  Underline,
  Strikethrough,
  List,
  ListOrdered,
  Heading3,
  AlignLeft,
  AlignCenter,
  AlignRight,
  RotateCcw,
  RotateCw,
  RemoveFormatting,
  Code,
} from 'lucide-react';

interface RichTextEditorProps {
  value: string;
  onChange: (html: string) => void;
  placeholder?: string;
  className?: string;
  minHeight?: string;
  readOnly?: boolean;
}

export default function RichTextEditor({
  value,
  onChange,
  placeholder = 'Write comments here...',
  className = '',
  minHeight = '140px',
  readOnly = false,
}: RichTextEditorProps) {
  const editorRef = useRef<HTMLDivElement>(null);
  const [showHtml, setShowHtml] = useState(false);
  const [htmlContent, setHtmlContent] = useState(value || '');

  // Synchronize incoming value if external change happens and editor is not focused
  useEffect(() => {
    if (editorRef.current && editorRef.current !== document.activeElement) {
      if (editorRef.current.innerHTML !== (value || '')) {
        editorRef.current.innerHTML = value || '';
      }
      setHtmlContent(value || '');
    }
  }, [value]);

  const executeCommand = (command: string, arg: string | undefined = undefined) => {
    if (readOnly) return;
    document.execCommand(command, false, arg);
    if (editorRef.current) {
      const newHtml = editorRef.current.innerHTML;
      setHtmlContent(newHtml);
      onChange(newHtml);
    }
  };

  const handleInput = () => {
    if (editorRef.current) {
      const newHtml = editorRef.current.innerHTML;
      setHtmlContent(newHtml);
      onChange(newHtml);
    }
  };

  const handleHtmlChange = (e: React.ChangeEvent<HTMLTextAreaElement>) => {
    const val = e.target.value;
    setHtmlContent(val);
    onChange(val);
    if (editorRef.current) {
      editorRef.current.innerHTML = val;
    }
  };

  return (
    <div className={`overflow-hidden rounded-xl border border-input bg-background shadow-2xs transition-all focus-within:border-ring focus-within:ring-2 focus-within:ring-ring/20 ${className}`}>
      {!readOnly && (
        <div className="flex flex-wrap items-center gap-0.5 border-b border-border bg-muted/40 p-1.5 text-muted-foreground select-none">
          <button
            type="button"
            onClick={() => executeCommand('bold')}
            className="rounded-lg p-1.5 hover:bg-muted hover:text-foreground transition cursor-pointer"
            title="Bold (Ctrl+B)"
          >
            <Bold className="size-3.5" />
          </button>
          <button
            type="button"
            onClick={() => executeCommand('italic')}
            className="rounded-lg p-1.5 hover:bg-muted hover:text-foreground transition cursor-pointer"
            title="Italic (Ctrl+I)"
          >
            <Italic className="size-3.5" />
          </button>
          <button
            type="button"
            onClick={() => executeCommand('underline')}
            className="rounded-lg p-1.5 hover:bg-muted hover:text-foreground transition cursor-pointer"
            title="Underline (Ctrl+U)"
          >
            <Underline className="size-3.5" />
          </button>
          <button
            type="button"
            onClick={() => executeCommand('strikeThrough')}
            className="rounded-lg p-1.5 hover:bg-muted hover:text-foreground transition cursor-pointer"
            title="Strikethrough"
          >
            <Strikethrough className="size-3.5" />
          </button>

          <div className="mx-1 h-4 w-px bg-border" />

          <button
            type="button"
            onClick={() => executeCommand('formatBlock', '<h3>')}
            className="rounded-lg p-1.5 hover:bg-muted hover:text-foreground transition cursor-pointer"
            title="Heading"
          >
            <Heading3 className="size-3.5" />
          </button>
          <button
            type="button"
            onClick={() => executeCommand('insertUnorderedList')}
            className="rounded-lg p-1.5 hover:bg-muted hover:text-foreground transition cursor-pointer"
            title="Bullet List"
          >
            <List className="size-3.5" />
          </button>
          <button
            type="button"
            onClick={() => executeCommand('insertOrderedList')}
            className="rounded-lg p-1.5 hover:bg-muted hover:text-foreground transition cursor-pointer"
            title="Numbered List"
          >
            <ListOrdered className="size-3.5" />
          </button>

          <div className="mx-1 h-4 w-px bg-border" />

          <button
            type="button"
            onClick={() => executeCommand('justifyLeft')}
            className="rounded-lg p-1.5 hover:bg-muted hover:text-foreground transition cursor-pointer"
            title="Align Left"
          >
            <AlignLeft className="size-3.5" />
          </button>
          <button
            type="button"
            onClick={() => executeCommand('justifyCenter')}
            className="rounded-lg p-1.5 hover:bg-muted hover:text-foreground transition cursor-pointer"
            title="Align Center"
          >
            <AlignCenter className="size-3.5" />
          </button>
          <button
            type="button"
            onClick={() => executeCommand('justifyRight')}
            className="rounded-lg p-1.5 hover:bg-muted hover:text-foreground transition cursor-pointer"
            title="Align Right"
          >
            <AlignRight className="size-3.5" />
          </button>

          <div className="mx-1 h-4 w-px bg-border" />

          <button
            type="button"
            onClick={() => executeCommand('removeFormat')}
            className="rounded-lg p-1.5 hover:bg-muted hover:text-foreground transition cursor-pointer"
            title="Clear Formatting"
          >
            <RemoveFormatting className="size-3.5" />
          </button>
          <button
            type="button"
            onClick={() => executeCommand('undo')}
            className="rounded-lg p-1.5 hover:bg-muted hover:text-foreground transition cursor-pointer"
            title="Undo"
          >
            <RotateCcw className="size-3.5" />
          </button>
          <button
            type="button"
            onClick={() => executeCommand('redo')}
            className="rounded-lg p-1.5 hover:bg-muted hover:text-foreground transition cursor-pointer"
            title="Redo"
          >
            <RotateCw className="size-3.5" />
          </button>

          <div className="ml-auto flex items-center">
            <button
              type="button"
              onClick={() => setShowHtml(!showHtml)}
              className={`rounded-lg px-2 py-1 text-[11px] font-medium transition cursor-pointer flex items-center gap-1 ${
                showHtml ? 'bg-primary text-primary-foreground font-semibold' : 'hover:bg-muted hover:text-foreground'
              }`}
              title="Toggle HTML Source"
            >
              <Code className="size-3" />
              <span>HTML</span>
            </button>
          </div>
        </div>
      )}

      {showHtml ? (
        <textarea
          value={htmlContent}
          onChange={handleHtmlChange}
          disabled={readOnly}
          rows={5}
          className="w-full bg-background p-3 font-mono text-xs text-foreground focus:outline-none resize-y"
          style={{ minHeight }}
        />
      ) : (
        <div
          ref={editorRef}
          contentEditable={!readOnly}
          onInput={handleInput}
          onBlur={handleInput}
          dangerouslySetInnerHTML={{ __html: value || '' }}
          style={{ minHeight }}
          data-placeholder={placeholder}
          className="rich-editor-body p-3 text-xs text-foreground leading-relaxed focus:outline-none overflow-y-auto [&_p]:mb-2 [&_ul]:list-disc [&_ul]:pl-5 [&_ul]:mb-2 [&_ol]:list-decimal [&_ol]:pl-5 [&_ol]:mb-2 [&_h3]:font-bold [&_h3]:text-sm [&_h3]:mb-1.5 [&_blockquote]:border-l-2 [&_blockquote]:border-primary/50 [&_blockquote]:pl-3 [&_blockquote]:italic empty:before:content-[attr(data-placeholder)] empty:before:text-muted-foreground empty:before:pointer-events-none"
        />
      )}
    </div>
  );
}

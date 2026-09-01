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

const BULLET_CHAR_REGEX = /^[\s\u00A0]*[\u2022\u00B7\u006F\u00A7\uF0B7\uF0A7\uF0D8\u25CF\u25CB\u25A0\*\-\–\—][\s\u00A0]*/i;
const NUMBER_PREFIX_REGEX = /^[\s\u00A0]*\(?(\d+|[a-zA-Z]|[ivxmldIVXMLD]+)[\.\)][\s\u00A0]*/;

function cleanNodeAttributes(node: HTMLElement) {
  const attributes = Array.from(node.attributes);
  for (const attr of attributes) {
    if (
      attr.name.startsWith('mso-') ||
      (attr.name === 'class' && attr.value.startsWith('Mso')) ||
      (attr.name === 'style' && /mso-|font-family:\s*(Symbol|Wingdings)/i.test(attr.value))
    ) {
      node.removeAttribute(attr.name);
    }
  }

  if (node.hasAttribute('style')) {
    let styleVal = node.getAttribute('style') || '';
    styleVal = styleVal
      .replace(/mso-[^;]+;?/gi, '')
      .replace(/font-family:\s*['"]?(Symbol|Wingdings)['"]?;?/gi, '')
      .trim();
    if (styleVal) {
      node.setAttribute('style', styleVal);
    } else {
      node.removeAttribute('style');
    }
  }

  Array.from(node.children).forEach((child) => cleanNodeAttributes(child as HTMLElement));
}

function escapeHtml(str: string): string {
  return str
    .replace(/&/g, '&amp;')
    .replace(/</g, '&lt;')
    .replace(/>/g, '&gt;')
    .replace(/"/g, '&quot;')
    .replace(/'/g, '&#039;');
}

export function convertPlainTextToHtml(text: string): string {
  if (!text) return '';
  const lines = text.split(/\r?\n/);
  let html = '';
  let currentListType: 'ul' | 'ol' | null = null;

  for (const line of lines) {
    const trimmed = line.trim();
    if (!trimmed) {
      if (currentListType) {
        html += `</${currentListType}>`;
        currentListType = null;
      }
      continue;
    }

    const isBullet = BULLET_CHAR_REGEX.test(line);
    const isNumber = !isBullet && NUMBER_PREFIX_REGEX.test(line);

    if (isBullet) {
      const cleanContent = line.replace(BULLET_CHAR_REGEX, '');
      if (currentListType !== 'ul') {
        if (currentListType) html += `</${currentListType}>`;
        html += '<ul>';
        currentListType = 'ul';
      }
      html += `<li>${escapeHtml(cleanContent)}</li>`;
    } else if (isNumber) {
      const cleanContent = line.replace(NUMBER_PREFIX_REGEX, '');
      if (currentListType !== 'ol') {
        if (currentListType) html += `</${currentListType}>`;
        html += '<ol>';
        currentListType = 'ol';
      }
      html += `<li>${escapeHtml(cleanContent)}</li>`;
    } else {
      if (currentListType) {
        html += `</${currentListType}>`;
        currentListType = null;
      }
      html += `<p>${escapeHtml(line)}</p>`;
    }
  }

  if (currentListType) {
    html += `</${currentListType}>`;
  }

  return html;
}

export function cleanAndTransformWordHtml(rawHtml: string): string {
  if (!rawHtml) return '';

  if (!/<[a-z][\s\S]*>/i.test(rawHtml)) {
    return convertPlainTextToHtml(rawHtml);
  }

  try {
    const parser = new DOMParser();
    const doc = parser.parseFromString(rawHtml, 'text/html');
    const body = doc.body;

    if (!body) return rawHtml;

    const unwantedSelectors = ['style', 'meta', 'link', 'title', 'xml', 'script'];
    unwantedSelectors.forEach((sel) => {
      body.querySelectorAll(sel).forEach((el) => el.remove());
    });

    const removeComments = (parent: Node) => {
      const children = Array.from(parent.childNodes);
      for (const child of children) {
        if (child.nodeType === Node.COMMENT_NODE) {
          child.remove();
        } else if (child.nodeType === Node.ELEMENT_NODE) {
          removeComments(child);
        }
      }
    };
    removeComments(body);

    const container = doc.createElement('div');
    let currentList: { type: 'ul' | 'ol'; element: HTMLElement } | null = null;

    const processElement = (el: HTMLElement) => {
      const styleAttr = el.getAttribute('style') || '';
      const classAttr = el.className || '';
      const isMsoList = /mso-list\s*:/i.test(styleAttr) || /MsoList/i.test(classAttr);

      const ignoreSpan = el.querySelector('[style*="mso-list:Ignore"]') || el.querySelector('.mso-list-ignore');
      let textPrefix = el.textContent || '';
      if (ignoreSpan) {
        textPrefix = ignoreSpan.textContent || textPrefix;
      }

      const isBullet =
        BULLET_CHAR_REGEX.test(textPrefix) ||
        (isMsoList && /font-family:\s*(Symbol|Wingdings)/i.test(styleAttr));
      const isNumber = !isBullet && (NUMBER_PREFIX_REGEX.test(textPrefix) || isMsoList);

      const isListItem = isMsoList || isBullet || isNumber || el.tagName.toLowerCase() === 'li';

      if (isListItem) {
        const listType: 'ul' | 'ol' = isBullet || (!isNumber && !NUMBER_PREFIX_REGEX.test(textPrefix)) ? 'ul' : 'ol';

        if (ignoreSpan) {
          ignoreSpan.remove();
        }

        const removeLeadingPrefix = (node: Node) => {
          if (node.nodeType === Node.TEXT_NODE) {
            let txt = node.nodeValue || '';
            if (listType === 'ul') {
              txt = txt.replace(BULLET_CHAR_REGEX, '');
            } else {
              txt = txt.replace(NUMBER_PREFIX_REGEX, '');
            }
            node.nodeValue = txt;
          } else if (node.nodeType === Node.ELEMENT_NODE) {
            const elem = node as HTMLElement;
            if (!elem.getAttribute('style')?.includes('mso-list:Ignore')) {
              if (elem.firstChild) {
                removeLeadingPrefix(elem.firstChild);
              }
            }
          }
        };

        if (el.firstChild) {
          removeLeadingPrefix(el.firstChild);
        }

        const li = doc.createElement('li');
        while (el.firstChild) {
          li.appendChild(el.firstChild);
        }

        cleanNodeAttributes(li);

        if (!currentList || currentList.type !== listType) {
          const newList = doc.createElement(listType);
          container.appendChild(newList);
          currentList = { type: listType, element: newList };
        }
        currentList.element.appendChild(li);
      } else {
        currentList = null;

        const tagName = el.tagName.toLowerCase();
        if (tagName === 'ul' || tagName === 'ol') {
          cleanNodeAttributes(el);
          el.querySelectorAll('li').forEach((li) => {
            if (li.firstChild) {
              let txt = li.firstChild.nodeValue || '';
              txt = txt.replace(BULLET_CHAR_REGEX, '').replace(NUMBER_PREFIX_REGEX, '');
              if (li.firstChild.nodeType === Node.TEXT_NODE) {
                li.firstChild.nodeValue = txt;
              }
            }
          });
          container.appendChild(el.cloneNode(true));
        } else if (
          tagName === 'p' ||
          tagName === 'h1' ||
          tagName === 'h2' ||
          tagName === 'h3' ||
          tagName === 'h4' ||
          tagName === 'blockquote'
        ) {
          cleanNodeAttributes(el);
          if (el.textContent?.trim() || el.querySelector('img, br')) {
            container.appendChild(el.cloneNode(true));
          }
        } else if (el.childNodes.length > 0) {
          Array.from(el.children).forEach((child) => {
            processElement(child as HTMLElement);
          });
        }
      }
    };

    const topElements = Array.from(body.children);
    if (topElements.length === 0 && body.textContent?.trim()) {
      return convertPlainTextToHtml(body.textContent);
    }

    topElements.forEach((el) => processElement(el as HTMLElement));

    let result = container.innerHTML;
    result = result
      .replace(/<font[^>]*>(.*?)<\/font>/gis, '$1')
      .replace(/<o:p>.*?<\/o:p>/gis, '')
      .replace(/class="Mso[^"]*"/gis, '')
      .replace(/style="[^"]*mso-[^"]*"/gis, '');

    return result || body.innerHTML;
  } catch (err) {
    console.error('Error cleaning Word HTML:', err);
    return rawHtml;
  }
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
  const [htmlContent, setHtmlContent] = useState(cleanAndTransformWordHtml(value || ''));

  // Initialize innerHTML on mount
  useEffect(() => {
    if (editorRef.current) {
      editorRef.current.innerHTML = cleanAndTransformWordHtml(value || '');
    }
  }, []);

  // Synchronize incoming value only when external update happens and user is NOT typing inside
  useEffect(() => {
    const cleaned = cleanAndTransformWordHtml(value || '');
    if (editorRef.current && document.activeElement !== editorRef.current) {
      if (editorRef.current.innerHTML !== cleaned) {
        editorRef.current.innerHTML = cleaned;
      }
    }
    setHtmlContent(cleaned);
  }, [value]);

  const executeCommand = (command: string, arg: string | undefined = undefined) => {
    if (readOnly || !editorRef.current) return;
    editorRef.current.focus();
    document.execCommand(command, false, arg);
    const newHtml = editorRef.current.innerHTML;
    setHtmlContent(newHtml);
    onChange(newHtml);
  };

  const handleInput = () => {
    if (editorRef.current) {
      const newHtml = editorRef.current.innerHTML;
      setHtmlContent(newHtml);
      onChange(newHtml);
    }
  };

  const handlePaste = (e: React.ClipboardEvent<HTMLDivElement>) => {
    if (readOnly) return;
    e.preventDefault();

    const text = e.clipboardData.getData('text/plain');
    const html = e.clipboardData.getData('text/html');

    let cleaned = '';
    if (html) {
      cleaned = cleanAndTransformWordHtml(html);
    } else if (text) {
      cleaned = convertPlainTextToHtml(text);
    }

    if (cleaned) {
      document.execCommand('insertHTML', false, cleaned);
    }

    if (editorRef.current) {
      const newHtml = editorRef.current.innerHTML;
      setHtmlContent(newHtml);
      onChange(newHtml);
    }
  };

  const handleHtmlChange = (e: React.ChangeEvent<HTMLTextAreaElement>) => {
    const val = e.target.value;
    const cleaned = cleanAndTransformWordHtml(val);
    setHtmlContent(cleaned);
    onChange(cleaned);
    if (editorRef.current) {
      editorRef.current.innerHTML = cleaned;
    }
  };

  return (
    <div
      dir="ltr"
      lang="en"
      className={`overflow-hidden rounded-xl border border-input bg-background shadow-2xs transition-all focus-within:border-ring focus-within:ring-2 focus-within:ring-ring/20 ${className}`}
    >
      {!readOnly && (
        <div
          className="flex flex-wrap items-center gap-0.5 border-b border-border bg-muted/40 p-1.5 text-muted-foreground select-none"
          dir="ltr"
          lang="en"
        >
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
          dir="ltr"
          lang="en"
          spellCheck={true}
          autoCapitalize="sentences"
          autoCorrect="on"
          inputMode="text"
          rows={5}
          className="w-full bg-background p-3 font-mono text-xs text-foreground focus:outline-none resize-y text-left [direction:ltr]"
          style={{ minHeight }}
        />
      ) : (
        <div
          ref={editorRef}
          contentEditable={!readOnly}
          dir="ltr"
          lang="en"
          spellCheck={true}
          autoCapitalize="sentences"
          autoCorrect="on"
          inputMode="text"
          onInput={handleInput}
          onBlur={handleInput}
          onPaste={handlePaste}
          style={{ minHeight }}
          data-placeholder={placeholder}
          className="rich-editor-body p-3 text-xs text-foreground leading-relaxed focus:outline-none overflow-y-auto text-left [direction:ltr] [text-align:left] font-sans [&_*]:!font-sans [&_font]:!font-sans [&_span]:!font-sans [&_*]:!text-left [&_*]:[direction:ltr!important] [&_p]:mb-2 [&_p]:text-left [&_p]:[direction:ltr] [&_ul]:list-disc [&_ul]:pl-5 [&_ul]:mb-2 [&_ol]:list-decimal [&_ol]:pl-5 [&_ol]:mb-2 [&_h3]:font-bold [&_h3]:text-sm [&_h3]:mb-1.5 [&_blockquote]:border-l-2 [&_blockquote]:border-primary/50 [&_blockquote]:pl-3 [&_blockquote]:italic empty:before:content-[attr(data-placeholder)] empty:before:text-muted-foreground empty:before:pointer-events-none empty:before:text-left"
        />
      )}
    </div>
  );
}

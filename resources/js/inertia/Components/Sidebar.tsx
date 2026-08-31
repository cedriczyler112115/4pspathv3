import { Link } from '@inertiajs/react';
import {
  BarChart3,
  ShieldCheck,
  ListOrdered,
  User,
  LogOut,
  Building2,
  Users,
  UserCheck,
  BookOpen,
  Search,
  Target,
  FileText,
  Calendar,
  Settings,
  Edit3,
  RefreshCw,
  Sliders,
  CalendarDays,
  CheckSquare,
  ChevronRight,
  Folder,
  FolderKanban,
  LayoutDashboard,
  Sparkles,
  Layers,
  Circle,
} from 'lucide-react';

export type SidebarItem = {
  item: {
    id: number;
    parent_id?: number | null;
    label: string;
    key?: string | null;
    href?: string | null;
    icon?: string | null;
    badge_text?: string | null;
    badge_cls?: string | null;
  };
  children: SidebarItem[];
};

const routeMap: Record<string, string> = {
  '/dashboard': '/inertia/dashboard',
  '/search': '/inertia/search',
  '/ipcrf/myratings': '/inertia/ipcrf/myratings',
  '/ipcrf/annualtarget': '/inertia/ipcrf/annualtarget',
  '/annualtarget': '/inertia/ipcrf/annualtarget',
  '/rpmo-management/harmonized-ipc': '/inertia/rpmo-management/harmonized-ipc',
  '/libraries/harmonized-staff': '/inertia/libraries/harmonized-staff',
  '/libraries/users/users-list': '/inertia/administration/users',
  '/libraries/users/user-level': '/inertia/administration/user-level',
  '/administration/settings': '/inertia/administration/settings',
  '/administration/sidebar-menu': '/inertia/settings/sidebar-menu',
  '/myaccount/profile': '/inertia/settings/profile',
  '/myaccount/security': '/inertia/settings/security',
  '/logout': '/logout',
};

export function mapHref(href?: string | null): string {
  if (!href) return '#';
  if (routeMap[href]) return routeMap[href];
  if (href.startsWith('http://') || href.startsWith('https://')) return href;
  return href.startsWith('/inertia/') ? href : `/inertia${href.startsWith('/') ? '' : '/'}${href}`;
}

export function getIconComponent(iconName?: string | null) {
  switch (iconName) {
    case 'dashboard':
    case 'layout-dashboard':
      return LayoutDashboard;
    case 'chart-bar':
    case 'chart-bar-square':
      return BarChart3;
    case 'shield-check':
      return ShieldCheck;
    case 'list-bullet':
      return ListOrdered;
    case 'user':
      return User;
    case 'arrow-left-end-on-rectangle':
      return LogOut;
    case 'building-library':
      return Building2;
    case 'user-group':
      return Users;
    case 'users':
      return UserCheck;
    case 'book-open-text':
      return BookOpen;
    case 'magnifying-glass':
      return Search;
    case 'viewfinder-circle':
      return Target;
    case 'document-text':
      return FileText;
    case 'calendar-date-range':
      return Calendar;
    case 'cog':
    case 'cog-6-tooth':
      return Settings;
    case 'pencil-square':
      return Edit3;
    case 'arrow-path-rounded-square':
      return RefreshCw;
    case 'calendar-days':
      return CalendarDays;
    case 'clipboard-document-check':
      return CheckSquare;
    case 'folder-kanban':
      return FolderKanban;
    case 'sparkles':
      return Sparkles;
    case 'layers':
      return Layers;
    default:
      return Folder;
  }
}

export function isBranchActive(node: SidebarItem, currentPath: string): boolean {
  const mappedHref = mapHref(node.item.href);
  if (
    mappedHref !== '#' &&
    (currentPath === mappedHref ||
      (mappedHref !== '/inertia/dashboard' && currentPath.startsWith(mappedHref + '/')))
  ) {
    return true;
  }
  return (node.children || []).some((child) => isBranchActive(child, currentPath));
}

export function findBreadcrumbTrail(
  nodes: SidebarItem[],
  currentPath: string,
  trail: Array<{ label: string; href?: string | null }> = []
): Array<{ label: string; href?: string | null }> | null {
  for (const node of nodes) {
    const mapped = mapHref(node.item.href);
    const isDirectMatch =
      mapped !== '#' &&
      (currentPath === mapped ||
        (mapped !== '/inertia/dashboard' && currentPath.startsWith(mapped + '/')));

    const currentTrail = [...trail, { label: node.item.label, href: mapped !== '#' ? mapped : null }];

    if (isDirectMatch) {
      return currentTrail;
    }

    if (node.children && node.children.length > 0) {
      const childTrail = findBreadcrumbTrail(node.children, currentPath, currentTrail);
      if (childTrail) {
        return childTrail;
      }
    }
  }
  return null;
}

type SidebarProps = {
  nodes: SidebarItem[];
  depth?: number;
  isCollapsed?: boolean;
};

export default function Sidebar({ nodes, depth = 0, isCollapsed = false }: SidebarProps) {
  const currentPath =
    typeof window !== 'undefined'
      ? window.location.pathname.replace(/\/+$/, '') || '/'
      : '/inertia/dashboard';

  return (
    <ul className="space-y-0.5">
      {nodes.map((node) => {
        const item = node.item;
        const children = node.children || [];
        const hasChildren = children.length > 0;
        const mappedHref = mapHref(item.href);
        const isExternal = Boolean(item.href && /^https?:\/\//.test(item.href));
        const isCurrent =
          mappedHref !== '#' &&
          (currentPath === mappedHref ||
            (mappedHref !== '/inertia/dashboard' && currentPath.startsWith(mappedHref + '/')));
        const isActiveBranch = isBranchActive(node, currentPath);

        const IconComponent = getIconComponent(item.icon);

        if (isCollapsed) {
          return (
            <li key={item.id} className="relative group/tooltip flex justify-center py-0.5">
              <Link
                href={mappedHref !== '#' ? mappedHref : children[0]?.item ? mapHref(children[0].item.href) : '#'}
                className={`relative flex size-8 items-center justify-center rounded-lg transition-all duration-150 ${
                  isCurrent
                    ? 'bg-emerald-600 text-white shadow-xs font-bold'
                    : isActiveBranch
                    ? 'bg-sidebar-accent text-sidebar-accent-foreground font-semibold border border-emerald-500/40'
                    : 'text-sidebar-foreground/75 hover:bg-sidebar-accent hover:text-sidebar-foreground'
                }`}
              >
                <IconComponent
                  className={`size-3.5 transition-colors ${
                    isCurrent
                      ? 'text-white'
                      : isActiveBranch
                      ? 'text-emerald-600 dark:text-emerald-400'
                      : 'text-sidebar-foreground/70 group-hover/tooltip:text-sidebar-foreground'
                  }`}
                />

                {isActiveBranch && !isCurrent && (
                  <span className="absolute -top-0.5 -right-0.5 size-1.5 rounded-full bg-emerald-500 ring-1.5 ring-sidebar" />
                )}
              </Link>

              {/* Floating Tooltip for collapsed mode */}
              <div className="absolute left-full ml-2.5 z-50 hidden group-hover/tooltip:flex items-center gap-1.5 rounded-md bg-slate-900 dark:bg-slate-800 text-white px-2.5 py-1 text-[11px] font-semibold shadow-lg whitespace-nowrap animate-in fade-in-50 zoom-in-95 pointer-events-none">
                <span>{item.label}</span>
                {item.badge_text && (
                  <span className="rounded bg-emerald-500/20 text-emerald-300 font-mono text-[8px] px-1 py-0.5 border border-emerald-500/30">
                    {item.badge_text}
                  </span>
                )}
              </div>
            </li>
          );
        }

        return (
          <li key={item.id} className="space-y-0.5">
            {hasChildren ? (
              <details open={isActiveBranch} className="group/branch">
                <summary
                  className={`flex list-none cursor-pointer items-center justify-between gap-2 rounded-lg px-2.5 py-1.25 text-xs font-medium transition-colors select-none ${
                    isActiveBranch
                      ? 'bg-sidebar-accent text-sidebar-foreground font-semibold shadow-2xs'
                      : 'text-sidebar-foreground/80 hover:bg-sidebar-accent/60 hover:text-sidebar-foreground'
                  }`}
                  style={{ paddingLeft: depth === 0 ? 10 : 10 + depth * 8 }}
                >
                  <div className="flex items-center gap-2 min-w-0">
                    <div
                      className={`size-6 rounded-md flex items-center justify-center shrink-0 transition-colors ${
                        isActiveBranch
                          ? 'bg-emerald-500/15 text-emerald-600 dark:text-emerald-400 font-bold'
                          : 'text-muted-foreground/80 group-hover/branch:text-sidebar-foreground'
                      }`}
                    >
                      <IconComponent className="size-3.5" />
                    </div>
                    <span className="truncate tracking-tight">{item.label}</span>
                  </div>

                  <div className="flex items-center gap-1 shrink-0">
                    {item.badge_text && (
                      <span className="rounded bg-emerald-500/15 text-emerald-700 dark:text-emerald-300 font-mono text-[9px] font-bold px-1.5 py-0.2 border border-emerald-500/20">
                        {item.badge_text}
                      </span>
                    )}
                    <ChevronRight
                      className={`size-3 transition-transform duration-150 group-open/branch:rotate-90 ${
                        isActiveBranch ? 'text-emerald-600 dark:text-emerald-400 font-bold' : 'text-muted-foreground/60'
                      }`}
                    />
                  </div>
                </summary>

                {mappedHref !== '#' ? (
                  <Link
                    href={mappedHref}
                    target={isExternal ? '_blank' : undefined}
                    rel={isExternal ? 'noreferrer noopener' : undefined}
                    className={`mt-0.5 flex items-center gap-1.5 rounded-md px-2 py-1 text-[11px] font-medium transition-colors ${
                      isCurrent
                        ? 'bg-emerald-600 text-white font-semibold shadow-2xs'
                        : 'text-muted-foreground hover:bg-sidebar-accent/50 hover:text-sidebar-foreground'
                    }`}
                    style={{ paddingLeft: depth === 0 ? 26 : 26 + depth * 8 }}
                  >
                    <Circle className={`size-1.5 ${isCurrent ? 'fill-white text-white' : 'text-muted-foreground/50'}`} />
                    <span>Overview ({item.label})</span>
                  </Link>
                ) : null}

                {/* Subtree container with compact hierarchy border */}
                <div className={`mt-0.5 border-l ml-3 pl-1.5 space-y-0.5 ${
                  isActiveBranch ? 'border-emerald-500/40 dark:border-emerald-400/40' : 'border-sidebar-border/70'
                }`}>
                  <Sidebar nodes={children} depth={depth + 1} />
                </div>
              </details>
            ) : (
              <Link
                href={mappedHref}
                target={isExternal ? '_blank' : undefined}
                rel={isExternal ? 'noreferrer noopener' : undefined}
                className={`group flex items-center justify-between gap-2 rounded-lg px-2.5 py-1.25 text-xs font-medium transition-colors select-none ${
                  isCurrent
                    ? 'bg-emerald-600 text-white font-semibold shadow-xs'
                    : 'text-sidebar-foreground/80 hover:bg-sidebar-accent/60 hover:text-sidebar-foreground'
                }`}
                style={{ paddingLeft: depth === 0 ? 10 : 10 + depth * 8 }}
              >
                <div className="flex items-center gap-2 min-w-0">
                  <div
                    className={`size-6 rounded-md flex items-center justify-center shrink-0 transition-colors ${
                      isCurrent
                        ? 'bg-white/20 text-white font-bold'
                        : 'text-muted-foreground/80 group-hover:text-sidebar-foreground'
                    }`}
                  >
                    <IconComponent className="size-3.5" />
                  </div>
                  <span className="truncate tracking-tight">{item.label}</span>
                </div>

                {item.badge_text && (
                  <span
                    className={`rounded font-mono text-[9px] font-bold px-1.5 py-0.2 border ${
                      isCurrent
                        ? 'bg-white/20 text-white border-white/30'
                        : 'bg-emerald-500/15 text-emerald-700 dark:text-emerald-300 border-emerald-500/20'
                    }`}
                  >
                    {item.badge_text}
                  </span>
                )}
              </Link>
            )}
          </li>
        );
      })}
    </ul>
  );
}


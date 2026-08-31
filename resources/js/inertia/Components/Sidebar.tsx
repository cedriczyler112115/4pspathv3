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

function mapHref(href?: string | null): string {
  if (!href) return '#';
  if (routeMap[href]) return routeMap[href];
  if (href.startsWith('http://') || href.startsWith('https://')) return href;
  return href.startsWith('/inertia/') ? href : `/inertia${href.startsWith('/') ? '' : '/'}${href}`;
}

function getIconComponent(iconName?: string | null) {
  switch (iconName) {
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
    default:
      return Folder;
  }
}

function isBranchActive(node: SidebarItem, currentPath: string): boolean {
  const mappedHref = mapHref(node.item.href);
  if (mappedHref !== '#' && (currentPath === mappedHref || (mappedHref !== '/inertia/dashboard' && currentPath.startsWith(mappedHref + '/')))) {
    return true;
  }
  return node.children.some((child) => isBranchActive(child, currentPath));
}

type SidebarProps = {
  nodes: SidebarItem[];
  depth?: number;
};

export default function Sidebar({ nodes, depth = 0 }: SidebarProps) {
  const currentPath = typeof window !== 'undefined' ? window.location.pathname.replace(/\/+$/, '') || '/' : '/inertia/dashboard';

  return (
    <ul className="space-y-1">
      {nodes.map((node) => {
        const item = node.item;
        const children = node.children;
        const hasChildren = children.length > 0;
        const mappedHref = mapHref(item.href);
        const isExternal = Boolean(item.href && /^https?:\/\//.test(item.href));
        const isCurrent =
          mappedHref !== '#' &&
          (currentPath === mappedHref ||
            (mappedHref !== '/inertia/dashboard' && currentPath.startsWith(mappedHref + '/')));
        const isActiveBranch = isBranchActive(node, currentPath);

        const IconComponent = getIconComponent(item.icon);

        return (
          <li key={item.id} className="space-y-1">
            {hasChildren ? (
              <details open={isActiveBranch} className="group">
                <summary
                  className={`flex list-none cursor-pointer items-center justify-between gap-2.5 rounded-xl px-3 py-2 text-xs font-semibold transition-all ${
                    isActiveBranch
                      ? 'bg-[#2b3915] text-[#dcfce7] border border-[#4d6325]'
                      : 'text-slate-300 hover:bg-[#16271e] hover:text-white'
                  }`}
                  style={{ paddingLeft: 12 + depth * 10 }}
                >
                  <div className="flex items-center gap-2.5 min-w-0">
                    <IconComponent className={`w-4 h-4 shrink-0 ${isActiveBranch ? 'text-[#a3e635]' : 'text-slate-400'}`} />
                    <span className="truncate">{item.label}</span>
                  </div>
                  <ChevronRight className="w-3.5 h-3.5 shrink-0 transition-transform duration-200 group-open:rotate-90 text-slate-400" />
                </summary>

                {mappedHref !== '#' ? (
                  <Link
                    href={mappedHref}
                    target={isExternal ? '_blank' : undefined}
                    rel={isExternal ? 'noreferrer noopener' : undefined}
                    className={`mt-1 flex items-center gap-2 rounded-lg px-3 py-1.5 text-[11px] font-medium transition ${
                      isCurrent
                        ? 'bg-emerald-800 text-white font-bold'
                        : 'text-slate-400 hover:bg-[#16271e] hover:text-slate-200'
                    }`}
                    style={{ paddingLeft: 24 + depth * 10 }}
                  >
                    <span>Open {item.label}</span>
                  </Link>
                ) : null}

                <div className="mt-1 border-l border-[#1b3528] ml-3 pl-1">
                  <Sidebar nodes={children} depth={depth + 1} />
                </div>
              </details>
            ) : (
              <Link
                href={mappedHref}
                target={isExternal ? '_blank' : undefined}
                rel={isExternal ? 'noreferrer noopener' : undefined}
                className={`flex items-center gap-2.5 rounded-xl px-3 py-2 text-xs font-semibold transition-all ${
                  isCurrent
                    ? 'bg-[#2b3915] text-[#dcfce7] border border-[#4d6325] shadow-md'
                    : 'text-slate-300 hover:bg-[#16271e] hover:text-white'
                }`}
                style={{ paddingLeft: 12 + depth * 10 }}
              >
                <IconComponent className={`w-4 h-4 shrink-0 ${isCurrent ? 'text-[#a3e635]' : 'text-slate-400'}`} />
                <span className="truncate flex-1">{item.label}</span>
                {item.badge_text ? (
                  <span className="rounded-full bg-emerald-950 px-2 py-0.5 text-[10px] font-extrabold text-emerald-300 border border-emerald-800">
                    {item.badge_text}
                  </span>
                ) : null}
              </Link>
            )}
          </li>
        );
      })}
    </ul>
  );
}

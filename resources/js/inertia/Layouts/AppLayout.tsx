import type { PropsWithChildren } from 'react';
import { useState, useEffect, useRef } from 'react';
import { Link, usePage } from '@inertiajs/react';
import {
  LayoutDashboard,
  FolderKanban,
  FileSpreadsheet,
  HardHat,
  ShieldCheck,
  BarChart3,
  Bell,
  Settings,
  UserCircle,
  Menu,
  X,
  Search,
  HelpCircle,
  Sun,
  Moon,
  ChevronDown,
  ChevronRight,
  LogOut,
  Sliders,
  Sparkles,
  Home,
  PanelLeftClose,
  PanelLeftOpen,
} from 'lucide-react';

import Sidebar, { findBreadcrumbTrail } from '../Components/Sidebar';
import ToastContainer from '../Components/ToastContainer';

type AppLayoutProps = PropsWithChildren<{
  appName?: string;
  user?: {
    name: string;
    email: string;
  } | null;
  sidebar?: any[];
}>;

export default function AppLayout({ children }: AppLayoutProps) {
  const { auth, appName = '4Ps PATH v3', navigation } = usePage<{
    auth?: { user?: { name: string; email: string } };
    appName?: string;
    navigation?: { sidebar?: any[] };
  }>().props;

  const user = auth?.user;
  const sidebarTree = navigation?.sidebar ?? [];
  const [sidebarOpen, setSidebarOpen] = useState(true);
  const [mobileMenuOpen, setMobileMenuOpen] = useState(false);
  const [userMenuOpen, setUserMenuOpen] = useState(false);
  const [isDarkMode, setIsDarkMode] = useState(false);
  const userMenuRef = useRef<HTMLDivElement>(null);

  const currentPath = typeof window !== 'undefined' ? window.location.pathname.replace(/\/+$/, '') || '/' : '/dashboard';

  // Synchronize dark mode state with document
  useEffect(() => {
    const isDark =
      document.documentElement.classList.contains('dark') ||
      window.localStorage.getItem('flux.appearance') === 'dark' ||
      window.localStorage.getItem('lgu_appearance') === 'dark';
    setIsDarkMode(isDark);
    if (isDark) {
      document.documentElement.classList.add('dark');
    }
  }, []);

  const toggleDarkMode = () => {
    const nextMode = !isDarkMode;
    setIsDarkMode(nextMode);
    if (nextMode) {
      document.documentElement.classList.add('dark');
      window.localStorage.setItem('flux.appearance', 'dark');
    } else {
      document.documentElement.classList.remove('dark');
      window.localStorage.setItem('flux.appearance', 'light');
    }
  };

  // Close user dropdown on outside click
  useEffect(() => {
    const handleClickOutside = (e: MouseEvent) => {
      if (userMenuRef.current && !userMenuRef.current.contains(e.target as Node)) {
        setUserMenuOpen(false);
      }
    };
    document.addEventListener('mousedown', handleClickOutside);
    return () => document.removeEventListener('mousedown', handleClickOutside);
  }, []);

  const defaultNavItems = [
    { label: 'Dashboard', icon: LayoutDashboard, href: '/dashboard' },
    { label: 'Annual Target', icon: FolderKanban, href: '/ipcrf/annualtarget' },
    { label: 'My Ratings', icon: BarChart3, href: '/ipcrf/myratings' },
    { label: 'Harmonized IPC', icon: FileSpreadsheet, href: '/rpmo-management/harmonized-ipc' },
    { label: 'Harmonized Staff', icon: HardHat, href: '/libraries/harmonized-staff' },
    { label: 'Users Management', icon: ShieldCheck, href: '/administration/users' },
    { label: 'Settings', icon: Settings, href: '/administration/settings' },
    { label: 'My Account', icon: UserCircle, href: '/settings/profile' },
  ];

  // Dynamic hierarchical breadcrumb generator matching sidebar nodes
  const getBreadcrumbTrail = (): Array<{ label: string; href?: string | null }> => {
    if (sidebarTree && sidebarTree.length > 0) {
      const treeTrail = findBreadcrumbTrail(sidebarTree, currentPath);
      if (treeTrail && treeTrail.length > 0) {
        return treeTrail;
      }
    }

    const path = currentPath || '/dashboard';
    const parts = path.split('/').filter(Boolean);
    if (parts.length === 0) return [{ label: 'Dashboard', href: '/dashboard' }];

    let accHref = '';
    return parts.map((p, idx) => {
      accHref += `/${p}`;
      return {
        label: p
          .split('-')
          .map((w) => w.charAt(0).toUpperCase() + w.slice(1))
          .join(' '),
        href: idx < parts.length - 1 ? accHref : null,
      };
    });
  };

  const breadcrumbs = getBreadcrumbTrail();
  const userInitials = (user?.name || 'Admin')
    .split(' ')
    .map((w) => w[0])
    .slice(0, 2)
    .join('')
    .toUpperCase();

  return (
    <div className="min-h-screen bg-background text-foreground flex text-xs sm:text-sm antialiased selection:bg-emerald-500 selection:text-white">
      {/* MOBILE BACKDROP OVERLAY */}
      {mobileMenuOpen && (
        <div
          className="fixed inset-0 z-40 bg-black/60 backdrop-blur-xs lg:hidden animate-in fade-in"
          onClick={() => setMobileMenuOpen(false)}
        />
      )}

      {/* COMPACT EXPANDABLE SIDEBAR */}
      <aside
        className={`fixed lg:sticky top-0 z-50 lg:z-30 h-screen shrink-0 flex flex-col justify-between border-r border-sidebar-border bg-sidebar text-sidebar-foreground transition-all duration-200 ease-in-out select-none shadow-xs ${
          sidebarOpen ? 'w-56' : 'w-14'
        } ${
          mobileMenuOpen
            ? 'translate-x-0 !w-56 shadow-2xl'
            : '-translate-x-full lg:translate-x-0'
        }`}
      >
        <div className="flex flex-col h-full overflow-hidden">
          {/* COMPACT SIDEBAR BRAND HEADER */}
          <div className="h-11 shrink-0 border-b border-sidebar-border px-2.5 flex items-center justify-between gap-1.5">
            <Link
              href="/dashboard"
              className="flex items-center gap-2 min-w-0 group cursor-pointer"
            >
              <div className="size-6.5 shrink-0 rounded-lg bg-gradient-to-br from-emerald-600 to-teal-700 text-white flex items-center justify-center font-black text-[10px] shadow-xs group-hover:scale-105 transition-transform">
                4P
              </div>
              {sidebarOpen && (
                <div className="flex flex-col min-w-0 truncate">
                  <span className="font-extrabold text-xs tracking-tight text-sidebar-foreground truncate leading-tight">
                    {appName}
                  </span>
                  <span className="text-[9px] font-medium text-muted-foreground truncate">
                    Performance Assessment
                  </span>
                </div>
              )}
            </Link>

            <button
              type="button"
              onClick={() => {
                if (window.innerWidth < 1024) {
                  setMobileMenuOpen(false);
                } else {
                  setSidebarOpen(!sidebarOpen);
                }
              }}
              title={sidebarOpen ? 'Collapse sidebar' : 'Expand sidebar'}
              className="size-7 rounded-lg hover:bg-sidebar-accent hover:text-sidebar-accent-foreground text-muted-foreground flex items-center justify-center transition-colors cursor-pointer"
            >
              {mobileMenuOpen ? (
                <X className="size-3.5 lg:hidden" />
              ) : sidebarOpen ? (
                <PanelLeftClose className="size-3.5" />
              ) : (
                <PanelLeftOpen className="size-3.5" />
              )}
            </button>
          </div>

          {/* SIDEBAR NAVIGATION ITEMS CONTAINER */}
          <nav className="flex-1 overflow-y-auto px-2 py-1.5 space-y-0.5 custom-scrollbar">
            {sidebarTree && sidebarTree.length > 0 ? (
              <Sidebar nodes={sidebarTree} isCollapsed={!sidebarOpen} />
            ) : (
              defaultNavItems.map((item) => {
                const Icon = item.icon;
                const isActive =
                  currentPath === item.href ||
                  (item.href !== '/dashboard' && currentPath.startsWith(item.href + '/'));

                return (
                  <Link
                    key={item.label}
                    href={item.href}
                    title={!sidebarOpen ? item.label : undefined}
                    className={`flex items-center gap-2 rounded-lg px-2 py-1.25 text-xs font-medium transition-all duration-150 ${
                      isActive
                        ? 'bg-emerald-600 text-white font-semibold shadow-xs'
                        : 'text-sidebar-foreground/80 hover:bg-sidebar-accent/60 hover:text-sidebar-foreground'
                    }`}
                  >
                    <Icon className={`size-3.5 shrink-0 ${isActive ? 'text-white' : 'text-muted-foreground'}`} />
                    {sidebarOpen && <span className="truncate flex-1">{item.label}</span>}
                  </Link>
                );
              })
            )}
          </nav>

          {/* SIDEBAR FOOTER PROFILE CARD */}
          <div className="shrink-0 border-t border-sidebar-border p-2 bg-sidebar/70">
            {sidebarOpen ? (
              <div className="rounded-lg border border-sidebar-border/80 bg-sidebar-accent/40 p-1.5 flex items-center justify-between gap-1.5 shadow-2xs">
                <Link
                  href="/settings/profile"
                  className="flex items-center gap-2 min-w-0 flex-1 hover:opacity-80 transition cursor-pointer"
                >
                  <div className="relative size-7 shrink-0 rounded-full bg-emerald-800 text-white flex items-center justify-center font-bold text-[10px] shadow-xs">
                    {userInitials}
                    <span className="absolute bottom-0 right-0 size-1.5 rounded-full bg-emerald-400 ring-1 ring-sidebar" />
                  </div>
                  <div className="flex flex-col min-w-0">
                    <span className="truncate text-[11px] font-bold text-sidebar-foreground leading-tight">
                      {user?.name || 'Administrator'}
                    </span>
                    <span className="truncate text-[9px] text-muted-foreground">
                      {user?.email || 'User Account'}
                    </span>
                  </div>
                </Link>

                <div className="flex items-center gap-0.5 shrink-0">
                  <Link
                    href="/settings/profile"
                    title="Profile Settings"
                    className="size-6 rounded-md hover:bg-sidebar-accent text-muted-foreground hover:text-sidebar-foreground flex items-center justify-center transition"
                  >
                    <Settings className="size-3" />
                  </Link>
                  <Link
                    href="/logout"
                    title="Log Out"
                    className="size-6 rounded-md hover:bg-rose-500/10 text-muted-foreground hover:text-rose-600 dark:hover:text-rose-400 flex items-center justify-center transition"
                  >
                    <LogOut className="size-3" />
                  </Link>
                </div>
              </div>
            ) : (
              <div className="flex justify-center">
                <Link
                  href="/settings/profile"
                  title={user?.name || 'User Profile'}
                  className="relative size-8 rounded-full bg-emerald-800 text-white flex items-center justify-center font-bold text-[10px] shadow-xs hover:scale-105 transition-transform cursor-pointer"
                >
                  {userInitials}
                  <span className="absolute bottom-0 right-0 size-2 rounded-full bg-emerald-400 ring-1.5 ring-sidebar" />
                </Link>
              </div>
            )}
          </div>
        </div>
      </aside>

      {/* RIGHT MAIN CONTENT WRAPPER */}
      <div className="flex-1 flex flex-col min-w-0 min-h-screen">
        {/* COMPACT TOP HEADER NAVBAR */}
        <header className="sticky top-0 z-20 h-11 shrink-0 border-b border-border bg-background/90 backdrop-blur-md px-2.5 sm:px-4 flex items-center justify-between gap-2 shadow-2xs">
          {/* LEFT: Mobile Toggle + Rich Breadcrumbs */}
          <div className="flex items-center gap-2 min-w-0">
            <button
              type="button"
              onClick={() => setMobileMenuOpen(!mobileMenuOpen)}
              className="lg:hidden size-7 rounded-md hover:bg-muted text-muted-foreground flex items-center justify-center transition cursor-pointer"
            >
              <Menu className="size-4" />
            </button>

            <nav aria-label="Breadcrumb" className="flex items-center gap-1 text-[11px] sm:text-xs font-medium text-muted-foreground truncate">
              <Link
                href="/dashboard"
                className="flex items-center gap-1 text-muted-foreground hover:text-foreground transition p-0.5 rounded hover:bg-muted"
                title="Dashboard Home"
              >
                <Home className="size-3.5 shrink-0" />
                <span className="hidden sm:inline">Home</span>
              </Link>

              {breadcrumbs.map((crumb, idx) => {
                const isLast = idx === breadcrumbs.length - 1;
                return (
                  <div key={idx} className="flex items-center gap-1 min-w-0">
                    <ChevronRight className="size-3 text-muted-foreground/50 shrink-0" />
                    {crumb.href && !isLast ? (
                      <Link
                        href={crumb.href}
                        className="hover:text-foreground transition truncate px-1 py-0.5 rounded hover:bg-muted"
                      >
                        {crumb.label}
                      </Link>
                    ) : (
                      <span
                        className={
                          isLast
                            ? 'font-bold text-foreground truncate px-1.5 py-0.2 rounded bg-emerald-500/10 text-emerald-700 dark:text-emerald-300'
                            : 'truncate'
                        }
                      >
                        {crumb.label}
                      </span>
                    )}
                  </div>
                );
              })}
            </nav>
          </div>

          {/* RIGHT: Quick Search, Dark Mode Toggle, User Menu */}
          <div className="flex items-center gap-1.5 shrink-0">
            {/* Quick Search */}
            <Link
              href="/search"
              className="hidden md:flex items-center gap-1.5 h-7 px-2.5 rounded-lg border border-input bg-muted/30 text-muted-foreground text-xs hover:bg-muted/70 hover:text-foreground transition"
            >
              <Search className="size-3" />
              <span className="text-[10px]">Search users...</span>
              <kbd className="ml-1.5 rounded border border-border bg-background px-1 text-[8px] font-mono text-muted-foreground">
                /
              </kbd>
            </Link>

            {/* Dark/Light Mode Toggle */}
            <button
              type="button"
              onClick={toggleDarkMode}
              title={isDarkMode ? 'Switch to Light Mode' : 'Switch to Dark Mode'}
              className="size-7 rounded-lg hover:bg-muted text-muted-foreground hover:text-foreground flex items-center justify-center transition cursor-pointer"
            >
              {isDarkMode ? <Sun className="size-3.5 text-amber-400" /> : <Moon className="size-3.5 text-slate-600 dark:text-slate-300" />}
            </button>

            {/* User Dropdown Pill */}
            <div className="relative" ref={userMenuRef}>
              <button
                type="button"
                onClick={() => setUserMenuOpen(!userMenuOpen)}
                className="flex items-center gap-1.5 h-7 pl-1 pr-2 rounded-lg hover:bg-muted text-foreground transition text-xs font-semibold border border-transparent hover:border-border cursor-pointer"
              >
                <div className="size-5 rounded-full bg-emerald-800 text-white flex items-center justify-center text-[9px] font-bold shadow-xs">
                  {userInitials}
                </div>
                <span className="hidden sm:inline max-w-[120px] truncate text-xs">
                  {user?.name || 'User'}
                </span>
                <ChevronDown className="size-3 text-muted-foreground" />
              </button>

              {/* DROPDOWN MENU */}
              {userMenuOpen && (
                <div className="absolute right-0 mt-1 w-48 rounded-lg border border-border bg-popover p-1 shadow-xl text-popover-foreground z-50 text-xs animate-in fade-in-50 zoom-in-95">
                  <div className="px-2 py-1.5 border-b border-border/60 mb-1">
                    <p className="font-bold truncate text-foreground">{user?.name || 'Administrator'}</p>
                    <p className="text-[10px] text-muted-foreground truncate">{user?.email || ''}</p>
                  </div>
                  <Link
                    href="/settings/profile"
                    onClick={() => setUserMenuOpen(false)}
                    className="flex items-center gap-2 rounded-md px-2 py-1.25 hover:bg-muted transition font-medium"
                  >
                    <UserCircle className="size-3.5 text-muted-foreground" />
                    <span>My Profile</span>
                  </Link>
                  <Link
                    href="/settings/security"
                    onClick={() => setUserMenuOpen(false)}
                    className="flex items-center gap-2 rounded-md px-2 py-1.25 hover:bg-muted transition font-medium"
                  >
                    <ShieldCheck className="size-3.5 text-muted-foreground" />
                    <span>Security</span>
                  </Link>
                  <Link
                    href="/settings/appearance"
                    onClick={() => setUserMenuOpen(false)}
                    className="flex items-center gap-2 rounded-md px-2 py-1.25 hover:bg-muted transition font-medium"
                  >
                    <Sliders className="size-3.5 text-muted-foreground" />
                    <span>Appearance</span>
                  </Link>
                  <div className="my-1 border-t border-border/60" />
                  <Link
                    href="/logout"
                    className="flex items-center gap-2 rounded-md px-2 py-1.25 text-destructive hover:bg-destructive/10 transition font-semibold"
                  >
                    <LogOut className="size-3.5" />
                    <span>Log Out</span>
                  </Link>
                </div>
              )}
            </div>
          </div>
        </header>

        {/* MAIN COMPACT CONTENT AREA */}
        <main className="w-full max-w-full flex-1 min-w-0 p-2.5 sm:p-3 space-y-2.5 sm:space-y-3">
          {children}
        </main>
      </div>

      <ToastContainer />
    </div>
  );
}


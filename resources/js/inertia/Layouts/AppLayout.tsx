import type { PropsWithChildren } from 'react';
import { useState } from 'react';
import { Link, usePage } from '@inertiajs/react';
import {
  LayoutDashboard,
  MapPin,
  FolderKanban,
  Wrench,
  FileText,
  ShoppingCart,
  FileSpreadsheet,
  HardHat,
  ShieldCheck,
  BarChart3,
  Bell,
  Settings,
  UserCircle,
  Menu,
  Search,
  HelpCircle,
  Sparkles,
  Sun,
  Moon,
  ChevronDown,
} from 'lucide-react';

import Sidebar from '../Components/Sidebar';
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
  const { auth, appName = 'TALACOGON E-BUILD SYSTEM', navigation } = usePage<{
    auth?: { user?: { name: string; email: string } };
    appName?: string;
    navigation?: { sidebar?: any[] };
  }>().props;

  const user = auth?.user;
  const sidebarTree = navigation?.sidebar ?? [];
  const [sidebarOpen, setSidebarOpen] = useState(true);
  const [isDarkMode, setIsDarkMode] = useState(false);
  const [activePath, setActivePath] = useState(typeof window !== 'undefined' ? window.location.pathname : '/inertia/dashboard');

  const navItems = [
    { label: 'Dashboard', icon: LayoutDashboard, href: '/inertia/dashboard', badge: null },
    { label: 'Annual Target', icon: FolderKanban, href: '/inertia/ipcrf/annualtarget', badge: null },
    { label: 'My Ratings', icon: BarChart3, href: '/inertia/ipcrf/myratings', badge: null },
    { label: 'Harmonized IPC', icon: FileSpreadsheet, href: '/inertia/rpmo-management/harmonized-ipc', badge: null },
    { label: 'Harmonized Staff', icon: HardHat, href: '/inertia/libraries/harmonized-staff', badge: null },
    { label: 'Users Management', icon: ShieldCheck, href: '/inertia/administration/users', badge: null },
    { label: 'Settings', icon: Settings, href: '/inertia/administration/settings', badge: null },
    { label: 'My Account', icon: UserCircle, href: '/inertia/settings/profile', badge: null },
  ];

  return (
    <div className={`min-h-screen ${isDarkMode ? 'dark bg-slate-950 text-slate-100' : 'bg-[#fcfbf7] text-slate-900'} flex`}>
      {/* LEFT SIDEBAR NAVIGATION */}
      <aside
        className={`${
          sidebarOpen ? 'w-64' : 'w-20'
        } transition-all duration-300 ease-in-out flex flex-col justify-between bg-[#0b1812] text-slate-200 border-r border-[#162a20] shrink-0 sticky top-0 h-screen z-30`}
      >
        <div className="flex flex-col h-full overflow-y-auto custom-scrollbar">
          {/* LOGO BRANDING */}
          <div className="p-4 border-b border-[#182c22] flex items-center justify-between">
            <div className="flex items-center gap-3">
              <div className="h-10 w-10 rounded-full bg-[#1b3528] border border-[#2f5742] flex items-center justify-center text-emerald-400 shadow-inner shrink-0">
                <div className="h-7 w-7 rounded-full bg-emerald-950 border border-emerald-500/30 flex items-center justify-center font-black text-xs text-emerald-300">
                  TE
                </div>
              </div>
              {sidebarOpen && (
                <div>
                  <div className="flex items-center gap-1.5">
                    <span className="text-[11px] font-black tracking-wider uppercase text-slate-100">TALACOGON</span>
                  </div>
                  <div className="flex items-center gap-1.5 mt-0.5">
                    <span className="text-xs font-extrabold text-emerald-400 tracking-tight">E-BUILD</span>
                    <span className="bg-emerald-800/80 text-[9px] font-extrabold px-1.5 py-0.2 rounded text-emerald-200 tracking-wider">SYSTEM</span>
                  </div>
                </div>
              )}
            </div>
            <button
              type="button"
              onClick={() => setSidebarOpen(!sidebarOpen)}
              className="text-slate-400 hover:text-slate-100 p-1.5 rounded-lg hover:bg-[#162a20] transition"
            >
              <Menu className="w-5 h-5" />
            </button>
          </div>

          {/* SIDEBAR NAVIGATION ITEMS */}
          <nav className="p-3 space-y-1.5 flex-1">
            {sidebarTree && sidebarTree.length > 0 ? (
              <Sidebar nodes={sidebarTree} />
            ) : (
              navItems.map((item) => {
                const Icon = item.icon;
                const isActive = activePath === item.href || (item.href !== '/inertia/dashboard' && activePath.startsWith(item.href + '/'));

                return (
                  <Link
                    key={item.label}
                    href={item.href}
                    onClick={() => setActivePath(item.href)}
                    className={`flex items-center gap-3 px-3 py-2.5 rounded-xl text-xs font-semibold transition-all duration-150 ${
                      isActive
                        ? 'bg-[#2b3915] text-[#dcfce7] border border-[#4d6325] shadow-md'
                        : 'text-slate-300 hover:bg-[#16271e] hover:text-white'
                    }`}
                  >
                    <Icon className={`w-4 h-4 shrink-0 ${isActive ? 'text-[#a3e635]' : 'text-slate-400'}`} />
                    {sidebarOpen && <span className="truncate flex-1">{item.label}</span>}
                    {sidebarOpen && item.badge && (
                      <span className="bg-emerald-900/60 text-emerald-300 text-[10px] px-2 py-0.5 rounded-full border border-emerald-700/50">
                        {item.badge}
                      </span>
                    )}
                  </Link>
                );
              })
            )}
          </nav>

          {/* SIDEBAR FOOTER BRANDING */}
          {sidebarOpen && (
            <div className="p-4 border-t border-[#162a20] bg-[#07110c]/80 text-[9px] text-slate-400 leading-snug">
              <p className="font-extrabold text-slate-300 uppercase tracking-wider">
                ELECTRONIC BUILDING UNIFIED INFORMATION & LIFECYCLE DATABASE
              </p>
              <p className="text-[#a3e635] font-medium mt-1">One Project. One Record. One Digital Journey</p>
            </div>
          )}
        </div>
      </aside>

      {/* RIGHT MAIN CONTENT AREA */}
      <div className="flex-1 flex flex-col min-w-0">
        {/* TOP HEADER NAVBAR */}
        <header className="sticky top-0 z-20 bg-[#fbfbfa]/90 dark:bg-slate-900/90 backdrop-blur border-b border-slate-200/70 dark:border-slate-800 px-6 py-3.5 flex items-center justify-between">
          <div>
            <div className="text-[10px] font-extrabold uppercase tracking-widest text-slate-400">HOME / DASHBOARD</div>
            <h1 className="text-xl font-bold tracking-tight text-slate-900 dark:text-slate-50">Dashboard</h1>
          </div>

          <div className="flex items-center gap-3">
            {/* Global Search Bar */}
            <div className="relative hidden md:block">
              <Search className="w-4 h-4 absolute left-3 top-1/2 -translate-y-1/2 text-slate-400" />
              <input
                type="text"
                placeholder="Global search"
                className="h-9 w-64 rounded-full border border-slate-200 dark:border-slate-700 bg-white dark:bg-slate-800 pl-9 pr-4 text-xs focus:outline-none focus:ring-2 focus:ring-emerald-500/40 text-slate-800 dark:text-slate-100"
              />
            </div>

            {/* Quick Action Icons */}
            <button type="button" className="p-2 rounded-full border border-slate-200 dark:border-slate-800 bg-white dark:bg-slate-800 text-slate-600 dark:text-slate-300 hover:bg-slate-50 relative">
              <Bell className="w-4 h-4" />
              <span className="absolute top-1 right-1 w-2 h-2 rounded-full bg-amber-500" />
            </button>

            <button type="button" className="p-2 rounded-full border border-slate-200 dark:border-slate-800 bg-white dark:bg-slate-800 text-slate-600 dark:text-slate-300 hover:bg-slate-50">
              <Sparkles className="w-4 h-4" />
            </button>

            <button type="button" className="p-2 rounded-full border border-slate-200 dark:border-slate-800 bg-white dark:bg-slate-800 text-slate-600 dark:text-slate-300 hover:bg-slate-50">
              <HelpCircle className="w-4 h-4" />
            </button>

            {/* User Profile Pill Dropdown */}
            <div className="flex items-center gap-2 rounded-full border border-slate-200 dark:border-slate-700 bg-white dark:bg-slate-800 px-3 py-1 text-xs font-semibold text-slate-800 dark:text-slate-200 shadow-sm">
              <div className="h-6 w-6 rounded-full bg-[#1b3528] text-emerald-300 font-bold flex items-center justify-center text-[10px]">
                AD
              </div>
              <span>{user?.name || 'Administrator'}</span>
              <ChevronDown className="w-3.5 h-3.5 text-slate-400" />
            </div>

            {/* Dark Mode Toggle Pill */}
            <button
              type="button"
              onClick={() => setIsDarkMode(!isDarkMode)}
              className="flex items-center gap-1.5 rounded-full border border-slate-200 dark:border-slate-700 bg-white dark:bg-slate-800 px-3 py-1.5 text-xs font-semibold text-slate-700 dark:text-slate-200 hover:bg-slate-50"
            >
              {isDarkMode ? <Sun className="w-3.5 h-3.5 text-amber-400" /> : <Moon className="w-3.5 h-3.5 text-slate-600" />}
              <span>{isDarkMode ? 'Light Mode' : 'Dark Mode'}</span>
            </button>
          </div>
        </header>

        {/* MAIN BODY CONTENT */}
        <main className="p-6 space-y-6 flex-1 bg-[#fbfbfa] dark:bg-slate-950">
          {children}
        </main>
      </div>
      <ToastContainer />
    </div>
  );
}

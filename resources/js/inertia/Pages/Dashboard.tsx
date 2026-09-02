import { Head, Link, router } from '@inertiajs/react';
import { useEffect, useState, useTransition } from 'react';
import AppLayout from '../Layouts/AppLayout';
import {
  Clock,
  Users,
  ShieldCheck,
  CheckCircle2,
  AlertCircle,
  UserX,
  Target,
  FileSpreadsheet,
  Layers,
  ChevronDown,
  ArrowRight,
  Sparkles,
  BarChart3,
  Building2,
  Table as TableIcon,
  Info,
} from 'lucide-react';
import { readPersistedFilters, savePersistedFilters } from '../lib/filterPersistence';

type DivisionStat = {
  id: number;
  name: string;
  totalUsers: number;
  forVerification: number;
  ready: number;
  verified: number;
  notReady: number;
  completionRate: number;
};

type Props = {
  appName?: string;
  user?: { name: string; email: string } | null;
  filters: {
    year: string;
    semester: string;
  };
  years: string[];
  semesters: Array<{ value: string; label: string }>;
  stats: {
    totalActiveUsers: number;
    readyForVerification: number;
    notReadyForVerification: number;
    verifiedStaff: number;
    forVerification: number;
    staffWithoutSupervisor: number;
  };
  divisionStats?: DivisionStat[];
  lastUpdated?: string;
  entryPoints?: Array<{
    label: string;
    href: string;
    description: string;
  }>;
};

export default function Dashboard({
  appName,
  user,
  filters,
  years = [],
  semesters = [],
  stats,
  divisionStats = [],
  lastUpdated,
  entryPoints = [],
}: Props) {
  const pageKey = 'dashboard';
  const persisted = readPersistedFilters(pageKey, user, {
    year: filters.year || '',
    semester: filters.semester || '',
  });
  const [selectedYear, setSelectedYear] = useState(persisted.year);
  const [selectedSemester, setSelectedSemester] = useState(persisted.semester);
  const [isPending, startTransition] = useTransition();
  const [activeChartTab, setActiveChartTab] = useState<'chart' | 'table'>('chart');
  const [hoveredDivision, setHoveredDivision] = useState<DivisionStat | null>(null);

  const handleFilterChange = (newYear: string, newSemester: string) => {
    setSelectedYear(newYear);
    setSelectedSemester(newSemester);
    savePersistedFilters(pageKey, user, { year: newYear, semester: newSemester });
    startTransition(() => {
      router.get(
        '/dashboard',
        { year: newYear, semester: newSemester },
        {
          preserveState: true,
          replace: true,
          preserveScroll: true,
        }
      );
    });
  };

  useEffect(() => {
    savePersistedFilters(pageKey, user, { year: selectedYear, semester: selectedSemester });
  }, [pageKey, user, selectedYear, selectedSemester]);

  const kpiCards = [
    {
      title: 'Total Active Users',
      value: Number(stats?.totalActiveUsers ?? 0).toLocaleString(),
      subtext: 'Active staff in system',
      badge: 'Active Users',
      borderAccent: 'border-l-amber-500',
      badgeBg: 'bg-amber-500/10 text-amber-600 dark:text-amber-400 border-amber-500/20',
      icon: Users,
    },
    {
      title: 'Ready for Verification',
      value: Number(stats?.readyForVerification ?? 0).toLocaleString(),
      subtext: 'Submitted semestral ratings',
      badge: 'Ready',
      borderAccent: 'border-l-emerald-500',
      badgeBg: 'bg-emerald-500/10 text-emerald-600 dark:text-emerald-400 border-emerald-500/20',
      icon: CheckCircle2,
    },
    {
      title: 'Not Ready for Verification',
      value: Number(stats?.notReadyForVerification ?? 0).toLocaleString(),
      subtext: 'Self-rating / In progress',
      badge: 'In Progress',
      borderAccent: 'border-l-rose-500',
      badgeBg: 'bg-rose-500/10 text-rose-600 dark:text-rose-400 border-rose-500/20',
      icon: AlertCircle,
    },
    {
      title: 'Verified Staff',
      value: Number(stats?.verifiedStaff ?? 0).toLocaleString(),
      subtext: 'Approved by supervisor',
      badge: 'Verified',
      borderAccent: 'border-l-blue-500',
      badgeBg: 'bg-blue-500/10 text-blue-600 dark:text-blue-400 border-blue-500/20',
      icon: ShieldCheck,
    },
    {
      title: 'For Verification',
      value: Number(stats?.forVerification ?? 0).toLocaleString(),
      subtext: 'Total semestral targets',
      badge: 'Commitments',
      borderAccent: 'border-l-orange-500',
      badgeBg: 'bg-orange-500/10 text-orange-600 dark:text-orange-400 border-orange-500/20',
      icon: FileSpreadsheet,
    },
    {
      title: 'Staff Without Supervisor',
      value: Number(stats?.staffWithoutSupervisor ?? 0).toLocaleString(),
      subtext: 'Unassigned rater / Unlinked',
      badge: 'Unassigned',
      borderAccent: 'border-l-sky-500',
      badgeBg: 'bg-sky-500/10 text-sky-600 dark:text-sky-400 border-sky-500/20',
      icon: UserX,
    },
  ];

  // Chart configuration & maximum calculation
  const maxVal = Math.max(
    ...divisionStats.map((d) => Math.max(d.totalUsers, d.forVerification, d.verified, d.notReady)),
    100
  );
  // Round maxVal up to a round number
  const chartMax = Math.ceil(maxVal / 200) * 200 || 1000;
  const yTicks = [chartMax, chartMax * 0.75, chartMax * 0.5, chartMax * 0.25, 0];

  const series = [
    { key: 'totalUsers', label: 'Total Users', color: '#3b82f6', bgClass: 'bg-blue-500' },
    { key: 'verified', label: 'Verified', color: '#ef4444', bgClass: 'bg-red-500' },
    { key: 'forVerification', label: 'For Verification', color: '#10b981', bgClass: 'bg-emerald-500' },
    { key: 'notReady', label: 'Not Ready', color: '#a855f7', bgClass: 'bg-purple-500' },
  ];

  return (
    <AppLayout appName={appName} user={user}>
      <Head title="Dashboard - 4Ps PATH" />

      <div className="space-y-6">
        {/* TOP SECTION: TITLE & FILTERS */}
        <div className="flex flex-col sm:flex-row sm:items-center justify-between gap-4 border-b border-border pb-4">
          <div>
            <h1 className="text-base sm:text-lg font-bold tracking-tight text-foreground uppercase">
              Dashboard
            </h1>
            <p className="text-xs text-muted-foreground mt-0.5">
              Performance Assessment &amp; Tracking Harmonizer Executive Overview.
            </p>
          </div>

          {/* FILTERS CONTROLS */}
          <div className="flex flex-wrap items-center gap-2.5">
            <span className="text-xs font-semibold text-muted-foreground">Filters</span>

            {/* YEAR SELECT */}
            <div className="relative">
              <select
                value={selectedYear}
                onChange={(e) => handleFilterChange(e.target.value, selectedSemester)}
                className="h-8 appearance-none rounded-lg border border-input bg-background px-2.5 pr-7 text-xs font-medium text-foreground outline-hidden focus:ring-2 focus:ring-ring cursor-pointer"
              >
                {years.map((y) => (
                  <option key={y} value={y}>
                    Year {y}
                  </option>
                ))}
              </select>
              <ChevronDown className="pointer-events-none absolute right-2 top-1/2 -translate-y-1/2 size-3 text-muted-foreground" />
            </div>

            {/* SEMESTER SELECT */}
            <div className="relative">
              <select
                value={selectedSemester}
                onChange={(e) => handleFilterChange(selectedYear, e.target.value)}
                className="h-8 appearance-none rounded-lg border border-input bg-background px-2.5 pr-7 text-xs font-medium text-foreground outline-hidden focus:ring-2 focus:ring-ring cursor-pointer"
              >
                {semesters.map((s) => (
                  <option key={s.value} value={s.value}>
                    {s.label}
                  </option>
                ))}
              </select>
              <ChevronDown className="pointer-events-none absolute right-2 top-1/2 -translate-y-1/2 size-3 text-muted-foreground" />
            </div>
          </div>
        </div>

        {/* 1. ASSESSMENT STATUS TILE CARDS */}
        <div className="space-y-2.5">
          <div className="flex items-center justify-between px-0.5">
            <div className="flex items-center gap-1.5">
              <Layers className="size-3.5 text-primary" />
              <h2 className="text-xs font-bold text-foreground uppercase tracking-wider">
                Assessment Status
              </h2>
            </div>
            <div className="flex items-center gap-1.5 text-[10px] text-muted-foreground">
              <Clock className="size-3 text-muted-foreground/70" />
              <span>
                Last updated:{' '}
                {lastUpdated ||
                  new Date().toLocaleString('en-US', {
                    month: 'numeric',
                    day: 'numeric',
                    year: 'numeric',
                    hour: 'numeric',
                    minute: '2-digit',
                    second: '2-digit',
                    hour12: true,
                  })}
              </span>
            </div>
          </div>

          {/* 6 TILE CARDS GRID WITH BORDER-L ACCENTS */}
          <div className="grid gap-2.5 sm:grid-cols-2 md:grid-cols-3 xl:grid-cols-6">
            {kpiCards.map((card, idx) => {
              const Icon = card.icon;
              return (
                <div
                  key={idx}
                  className={`rounded-lg border border-border bg-card p-3 shadow-2xs hover:shadow-xs transition border-l-3 ${card.borderAccent}`}
                >
                  <div className="flex items-center justify-between">
                    <span className="text-[10px] font-semibold text-muted-foreground truncate">
                      {card.title}
                    </span>
                    <Icon className="size-3.5 text-muted-foreground/70 shrink-0" />
                  </div>
                  <div className="mt-1.5 flex items-baseline justify-between">
                    <span className="text-lg font-bold tracking-tight text-card-foreground">
                      {card.value}
                    </span>
                    <span className={`text-[9px] font-bold px-1.5 py-0.2 rounded-full border ${card.badgeBg}`}>
                      {card.badge}
                    </span>
                  </div>
                  <p className="text-[10px] text-muted-foreground truncate mt-1">
                    {card.subtext}
                  </p>
                </div>
              );
            })}
          </div>
        </div>

        {/* 2. SYSTEM QUICK ACCESS & NAVIGATION SHORTCUTS */}
        <div className="rounded-lg border border-border bg-card p-3 sm:p-4 shadow-2xs space-y-3">
          <div className="flex items-center justify-between border-b border-border pb-2.5">
            <div className="flex items-center gap-2">
              <div className="size-6 rounded-md bg-emerald-500/10 text-emerald-600 dark:text-emerald-400 flex items-center justify-center font-bold">
                <Sparkles className="size-3.5" />
              </div>
              <h3 className="text-xs font-bold text-card-foreground">Quick Access &amp; Modules</h3>
            </div>
          </div>

          <div className="grid gap-2.5 sm:grid-cols-3">
            <Link
              href="/ipcrf/annualtarget"
              className="group rounded-lg border border-border bg-background p-3 shadow-2xs hover:border-emerald-500/50 hover:bg-muted/40 transition"
            >
              <div className="flex items-center justify-between">
                <div className="size-7 rounded-lg bg-indigo-500/10 text-indigo-600 dark:text-indigo-400 flex items-center justify-center font-bold">
                  <Target className="size-3.5" />
                </div>
                <ArrowRight className="size-3.5 text-muted-foreground group-hover:text-foreground group-hover:translate-x-0.5 transition" />
              </div>
              <h4 className="mt-2 text-xs font-bold text-foreground">Annual Targets</h4>
              <p className="mt-0.5 text-[10px] text-muted-foreground leading-relaxed">
                Formulate, review, and adjust your annual Individual Performance Commitments.
              </p>
            </Link>

            <Link
              href="/ipcrf/myratings"
              className="group rounded-lg border border-border bg-background p-3 shadow-2xs hover:border-emerald-500/50 hover:bg-muted/40 transition"
            >
              <div className="flex items-center justify-between">
                <div className="size-7 rounded-lg bg-emerald-500/10 text-emerald-600 dark:text-emerald-400 flex items-center justify-center font-bold">
                  <FileSpreadsheet className="size-3.5" />
                </div>
                <ArrowRight className="size-3.5 text-muted-foreground group-hover:text-foreground group-hover:translate-x-0.5 transition" />
              </div>
              <h4 className="mt-2 text-xs font-bold text-foreground">Semestral Ratings</h4>
              <p className="mt-0.5 text-[10px] text-muted-foreground leading-relaxed">
                Encode semestral accomplishments, upload MOV attachments, and track scorecards.
              </p>
            </Link>

            <Link
              href="/verification"
              className="group rounded-lg border border-border bg-background p-3 shadow-2xs hover:border-emerald-500/50 hover:bg-muted/40 transition"
            >
              <div className="flex items-center justify-between">
                <div className="size-7 rounded-lg bg-purple-500/10 text-purple-600 dark:text-purple-400 flex items-center justify-center font-bold">
                  <ShieldCheck className="size-3.5" />
                </div>
                <ArrowRight className="size-3.5 text-muted-foreground group-hover:text-foreground group-hover:translate-x-0.5 transition" />
              </div>
              <h4 className="mt-2 text-xs font-bold text-foreground">Staff Verification</h4>
              <p className="mt-0.5 text-[10px] text-muted-foreground leading-relaxed">
                Verify subordinates' self-ratings, submit remarks, and record final scores.
              </p>
            </Link>
          </div>
        </div>

        {/* 3. DIVISION STATUS (GRAPH & BREAKDOWN) */}
        <div className="rounded-xl border border-border bg-card p-4 sm:p-5 shadow-2xs space-y-4">
          {/* DIVISION STATUS HEADER */}
          <div className="flex flex-col sm:flex-row sm:items-center justify-between gap-3 border-b border-border pb-3">
            <div className="space-y-0.5">
              <div className="flex items-center gap-2">
                <div className="size-7 rounded-lg bg-blue-500/10 text-blue-600 dark:text-blue-400 flex items-center justify-center font-bold">
                  <BarChart3 className="size-4" />
                </div>
                <h3 className="text-sm font-bold text-foreground uppercase tracking-wide">
                  Division Status
                </h3>
              </div>
              <p className="text-xs text-muted-foreground pl-9">
                Staff distribution, verification commitments, and accomplishment status by division.
              </p>
            </div>

            {/* VIEW TOGGLES & LEGEND */}
            <div className="flex flex-wrap items-center gap-2 sm:gap-3">
              {/* LEGENDS */}
              <div className="flex items-center gap-2.5 text-[11px] text-muted-foreground font-medium mr-1">
                {series.map((s) => (
                  <span key={s.key} className="flex items-center gap-1.5">
                    <span
                      className="size-2.5 rounded-xs shrink-0 shadow-2xs"
                      style={{ backgroundColor: s.color }}
                    />
                    <span className="text-foreground">{s.label}</span>
                  </span>
                ))}
              </div>

              {/* VIEW SWITCHER */}
              <div className="flex items-center gap-1 bg-muted p-0.5 rounded-lg text-xs font-medium">
                <button
                  type="button"
                  onClick={() => setActiveChartTab('chart')}
                  className={`inline-flex items-center gap-1.5 px-2.5 py-1 rounded-md transition cursor-pointer ${
                    activeChartTab === 'chart'
                      ? 'bg-background text-foreground font-semibold shadow-2xs'
                      : 'text-muted-foreground hover:text-foreground'
                  }`}
                >
                  <BarChart3 className="size-3.5" />
                  <span>Chart</span>
                </button>
                <button
                  type="button"
                  onClick={() => setActiveChartTab('table')}
                  className={`inline-flex items-center gap-1.5 px-2.5 py-1 rounded-md transition cursor-pointer ${
                    activeChartTab === 'table'
                      ? 'bg-background text-foreground font-semibold shadow-2xs'
                      : 'text-muted-foreground hover:text-foreground'
                  }`}
                >
                  <TableIcon className="size-3.5" />
                  <span>Table</span>
                </button>
              </div>
            </div>
          </div>

          {/* FIXED-HEIGHT HOVER INFO BAR (PREVENTS ANY SHIFTING OR SHAKING) */}
          <div className="min-h-[38px] rounded-lg border border-border/70 bg-muted/30 px-3 py-1.5 flex items-center justify-between gap-2 text-xs select-none">
            {hoveredDivision ? (
              <>
                <div className="flex items-center gap-2 min-w-0">
                  <Building2 className="size-3.5 text-primary shrink-0" />
                  <span className="font-bold text-foreground truncate">{hoveredDivision.name}</span>
                </div>
                <div className="flex items-center gap-3 sm:gap-4 text-[11px] shrink-0">
                  <span>
                    Users: <strong className="text-blue-500 font-mono">{hoveredDivision.totalUsers}</strong>
                  </span>
                  <span>
                    For Verification: <strong className="text-emerald-500 font-mono">{hoveredDivision.forVerification}</strong>
                  </span>
                  <span>
                    Verified: <strong className="text-red-500 font-mono">{hoveredDivision.verified}</strong>
                  </span>
                  <span>
                    Not Ready: <strong className="text-purple-500 font-mono">{hoveredDivision.notReady}</strong>
                  </span>
                </div>
              </>
            ) : (
              <div className="flex items-center gap-2 text-muted-foreground text-[11px]">
                <Info className="size-3.5 text-muted-foreground/70" />
                <span>Hover over any division column below to inspect detailed statistics.</span>
              </div>
            )}
          </div>

          {/* TAB 1: MODERN GROUPED BAR GRAPH */}
          {activeChartTab === 'chart' && (
            <div className="space-y-2">
              <div className="overflow-x-auto custom-scrollbar pb-2">
                <div className="min-w-[920px] pt-2">
                  {/* CHART AREA */}
                  <div className="grid grid-cols-[50px_1fr] gap-2 items-end">
                    {/* Y-AXIS TICKS */}
                    <div className="flex flex-col justify-between h-56 text-[10px] font-mono text-muted-foreground text-right pr-2 select-none">
                      {yTicks.map((val, idx) => (
                        <span key={idx}>{val.toLocaleString()}</span>
                      ))}
                    </div>

                    {/* CHART BARS CONTAINER */}
                    <div className="relative h-56 w-full border-b border-l border-border flex items-end">
                      {/* HORIZONTAL GRID LINES */}
                      <div className="absolute inset-0 flex flex-col justify-between pointer-events-none">
                        {yTicks.map((_, idx) => (
                          <div
                            key={idx}
                            className="w-full border-b border-border/50 border-dashed"
                          />
                        ))}
                      </div>

                      {/* GROUPED BARS FOR EACH DIVISION */}
                      <div className="relative z-10 w-full h-full flex items-end justify-around px-2">
                        {divisionStats.map((div) => {
                          const isHovered = hoveredDivision?.id === div.id;
                          const calcHeight = (val: number) => {
                            if (val <= 0) return 0;
                            const h = (val / chartMax) * 100;
                            return Math.max(Math.min(h, 100), 2); // At least 2% height if > 0 for visibility
                          };

                          const totalH = calcHeight(div.totalUsers);
                          const verifiedH = calcHeight(div.verified);
                          const forVerifH = calcHeight(div.forVerification);
                          const notReadyH = calcHeight(div.notReady);

                          return (
                            <div
                              key={div.id}
                              onMouseEnter={() => setHoveredDivision(div)}
                              onMouseLeave={() => setHoveredDivision(null)}
                              className={`flex-1 flex flex-col items-center justify-end h-full px-1 rounded-t-lg transition-colors cursor-pointer ${
                                isHovered ? 'bg-muted/40' : 'hover:bg-muted/20'
                              }`}
                            >
                              {/* BAR CLUSTER */}
                              <div className="flex items-end justify-center gap-1 w-full max-w-[50px] h-full pb-0.5">
                                {/* Total Users Bar (Blue) */}
                                <div
                                  style={{ height: `${totalH}%` }}
                                  className={`w-2.5 sm:w-3 rounded-t-xs bg-blue-500 shadow-2xs transition-opacity ${
                                    isHovered ? 'opacity-100 ring-1 ring-blue-400' : 'opacity-90'
                                  }`}
                                  title={`Total Users: ${div.totalUsers}`}
                                />

                                {/* Verified Bar (Red) */}
                                <div
                                  style={{ height: `${verifiedH}%` }}
                                  className={`w-2.5 sm:w-3 rounded-t-xs bg-red-500 shadow-2xs transition-opacity ${
                                    isHovered ? 'opacity-100 ring-1 ring-red-400' : 'opacity-90'
                                  }`}
                                  title={`Verified: ${div.verified}`}
                                />

                                {/* For Verification Bar (Green) */}
                                <div
                                  style={{ height: `${forVerifH}%` }}
                                  className={`w-2.5 sm:w-3 rounded-t-xs bg-emerald-500 shadow-2xs transition-opacity ${
                                    isHovered ? 'opacity-100 ring-1 ring-emerald-400' : 'opacity-90'
                                  }`}
                                  title={`For Verification: ${div.forVerification}`}
                                />

                                {/* Not Ready Bar (Purple) */}
                                <div
                                  style={{ height: `${notReadyH}%` }}
                                  className={`w-2.5 sm:w-3 rounded-t-xs bg-purple-500 shadow-2xs transition-opacity ${
                                    isHovered ? 'opacity-100 ring-1 ring-purple-400' : 'opacity-90'
                                  }`}
                                  title={`Not Ready: ${div.notReady}`}
                                />
                              </div>
                            </div>
                          );
                        })}
                      </div>
                    </div>
                  </div>

                  {/* X-AXIS LABELS */}
                  <div className="grid grid-cols-[50px_1fr] gap-2 pt-2">
                    <div />
                    <div className="flex justify-around items-start gap-2 px-2 text-muted-foreground text-[10px]">
                      {divisionStats.map((div) => {
                        const isHovered = hoveredDivision?.id === div.id;
                        return (
                          <div
                            key={div.id}
                            className="flex-1 min-w-0 px-0.5 text-center"
                            title={div.name}
                          >
                            <div
                              className={`mx-auto max-w-[120px] whitespace-normal break-words leading-tight transition-colors ${
                                isHovered ? 'font-bold text-foreground' : 'font-medium hover:text-foreground'
                              }`}
                            >
                              {div.name}
                            </div>
                          </div>
                        );
                      })}
                    </div>
                  </div>
                </div>
              </div>

              <div className="pt-6 sm:pt-8 text-center">
                <span className="text-[11px] font-semibold text-muted-foreground uppercase tracking-wider">
                  Division Name
                </span>
              </div>
            </div>
          )}

          {/* TAB 2: DETAILED DIVISION BREAKDOWN TABLE */}
          {activeChartTab === 'table' && (
            <div className="overflow-x-auto rounded-lg border border-border">
              <table className="w-full min-w-[760px] border-collapse text-xs text-left">
                <thead>
                  <tr className="bg-muted/60 text-[11px] font-bold uppercase tracking-wider text-muted-foreground border-b border-border">
                    <th className="px-3 py-2.5 border-r border-border">Division</th>
                    <th className="px-3 py-2.5 text-center border-r border-border w-28 text-blue-600 dark:text-blue-400">
                      Total Users
                    </th>
                    <th className="px-3 py-2.5 text-center border-r border-border w-32 text-emerald-600 dark:text-emerald-400">
                      For Verification
                    </th>
                    <th className="px-3 py-2.5 text-center border-r border-border w-28 text-red-600 dark:text-red-400">
                      Verified
                    </th>
                    <th className="px-3 py-2.5 text-center border-r border-border w-28 text-purple-600 dark:text-purple-400">
                      Not Ready
                    </th>
                    <th className="px-3 py-2.5 text-center w-36">Progress</th>
                  </tr>
                </thead>
                <tbody className="divide-y divide-border">
                  {divisionStats.map((div) => (
                    <tr
                      key={div.id}
                      className="hover:bg-muted/30 transition-colors"
                    >
                      <td className="px-3 py-2.5 font-semibold text-foreground border-r border-border">
                        {div.name}
                      </td>
                      <td className="px-3 py-2.5 text-center font-mono font-bold text-foreground border-r border-border">
                        {div.totalUsers.toLocaleString()}
                      </td>
                      <td className="px-3 py-2.5 text-center font-mono font-bold text-emerald-600 dark:text-emerald-400 border-r border-border">
                        {div.forVerification.toLocaleString()}
                      </td>
                      <td className="px-3 py-2.5 text-center font-mono font-bold text-red-600 dark:text-red-400 border-r border-border">
                        {div.verified.toLocaleString()}
                      </td>
                      <td className="px-3 py-2.5 text-center font-mono font-bold text-purple-600 dark:text-purple-400 border-r border-border">
                        {div.notReady.toLocaleString()}
                      </td>
                      <td className="px-3 py-2.5 text-center">
                        <div className="flex items-center justify-center gap-2">
                          <div className="h-2 w-16 rounded-full bg-muted overflow-hidden">
                            <div
                              className="h-full bg-emerald-500 rounded-full transition-all"
                              style={{ width: `${div.completionRate}%` }}
                            />
                          </div>
                          <span className="font-mono text-[10px] text-muted-foreground w-10 text-right">
                            {div.completionRate}%
                          </span>
                        </div>
                      </td>
                    </tr>
                  ))}
                </tbody>
              </table>
            </div>
          )}
        </div>
      </div>
    </AppLayout>
  );
}

import { Head } from '@inertiajs/react';
import { useState } from 'react';
import AppLayout from '../Layouts/AppLayout';
import {
  Map,
  Building2,
  HardHat,
  CheckCircle2,
  DollarSign,
  MapPin,
  FileCheck,
  Truck,
  ClipboardCheck,
  FileSpreadsheet,
  TrendingUp,
  Activity,
  Layers,
  ArrowUpRight,
  ExternalLink,
} from 'lucide-react';

export default function Dashboard() {
  const [activeTab, setActiveTab] = useState('permits');

  const kpiCards = [
    {
      title: 'Total Infrastructure Projects',
      value: '148',
      subtext: '₱140.1M Capital Allocated',
      badge: '↗ +12.4% YTD',
      borderAccent: 'border-l-amber-500',
      badgeBg: 'bg-emerald-50 text-emerald-700 border-emerald-200',
      icon: Building2,
    },
    {
      title: 'Projects Under Construction',
      value: '38',
      subtext: 'Ongoing field operations',
      badge: '↗ Active On Site',
      borderAccent: 'border-l-orange-500',
      badgeBg: 'bg-emerald-50 text-emerald-700 border-emerald-200',
      icon: HardHat,
    },
    {
      title: 'Completed Infrastructure',
      value: '82',
      subtext: 'Turned over & operational',
      badge: '↗ 100% Inspected',
      borderAccent: 'border-l-emerald-500',
      badgeBg: 'bg-emerald-50 text-emerald-700 border-emerald-200',
      icon: CheckCircle2,
    },
    {
      title: 'Total CAPEX Expended',
      value: '₱125.8M',
      subtext: '₱14.3M Balance remaining',
      badge: '↗ 89.9% Disbursed',
      borderAccent: 'border-l-cyan-500',
      badgeBg: 'bg-emerald-50 text-emerald-700 border-emerald-200',
      icon: DollarSign,
    },
    {
      title: 'Barangays Covered',
      value: '16',
      subtext: 'Talacogon Municipal District',
      badge: '↗ 100% Coverage',
      borderAccent: 'border-l-blue-500',
      badgeBg: 'bg-emerald-50 text-emerald-700 border-emerald-200',
      icon: MapPin,
    },
    {
      title: 'Active Work Orders',
      value: '24',
      subtext: 'SLA processing on-track',
      badge: '↗ Avg 3.1 Days',
      borderAccent: 'border-l-purple-500',
      badgeBg: 'bg-emerald-50 text-emerald-700 border-emerald-200',
      icon: FileCheck,
    },
    {
      title: 'Equipment Motorpool Availability',
      value: '88.9%',
      subtext: 'Optimal fleet readiness',
      badge: '↗ 24/27 Machinery',
      borderAccent: 'border-l-sky-500',
      badgeBg: 'bg-emerald-50 text-emerald-700 border-emerald-200',
      icon: Truck,
    },
    {
      title: 'Field Inspections Conducted',
      value: '1,449',
      subtext: 'Quality assurance reports',
      badge: '↗ +18.4% YoY',
      borderAccent: 'border-l-teal-500',
      badgeBg: 'bg-emerald-50 text-emerald-700 border-emerald-200',
      icon: ClipboardCheck,
    },
    {
      title: 'Total YTD Applications',
      value: '3,280',
      subtext: '16 Barangays served',
      badge: '↗ +10.4% YoY',
      borderAccent: 'border-l-amber-400',
      badgeBg: 'bg-emerald-50 text-emerald-700 border-emerald-200',
      icon: FileSpreadsheet,
    },
    {
      title: 'Approved Permits Issued',
      value: '3,115',
      subtext: 'Fully processed & compliant',
      badge: '↗ 95.0% Approval',
      borderAccent: 'border-l-lime-500',
      badgeBg: 'bg-emerald-50 text-emerald-700 border-emerald-200',
      icon: CheckCircle2,
    },
    {
      title: 'Avg SLA Processing Time',
      value: '3.1 Days',
      subtext: 'SLA target: <= 5.0 days',
      badge: '↗ -24% turnaround',
      borderAccent: 'border-l-yellow-500',
      badgeBg: 'bg-emerald-50 text-emerald-700 border-emerald-200',
      icon: Activity,
    },
    {
      title: 'Total Fees & Revenue',
      value: '₱8.64M',
      subtext: 'Collected YTD 2026',
      badge: '↗ +21.5% target',
      borderAccent: 'border-l-emerald-600',
      badgeBg: 'bg-emerald-50 text-emerald-700 border-emerald-200',
      icon: TrendingUp,
    },
  ];

  return (
    <AppLayout>
      <Head title="Dashboard - Talacogon E-Build System" />

      {/* TOP ANNOUNCEMENT BANNER CARD */}
      <div className="rounded-2xl bg-[#081e18] p-5 text-white border border-[#163c30] shadow-lg flex flex-col md:flex-row items-start md:items-center justify-between gap-4">
        <div className="flex items-start gap-4">
          <div className="h-12 w-12 rounded-2xl bg-[#143d30] border border-[#235847] flex items-center justify-center text-amber-400 shrink-0 shadow-inner">
            <Map className="w-6 h-6" />
          </div>
          <div>
            <div className="flex items-center gap-2">
              <span className="bg-emerald-900/80 text-emerald-300 text-[10px] font-black tracking-widest px-2 py-0.5 rounded uppercase border border-emerald-700/50">
                PROJECT MAPS SIDEBAR MENU
              </span>
            </div>
            <h2 className="text-lg font-bold tracking-tight text-white mt-1">Interactive GIS Project Maps</h2>
            <p className="text-xs text-slate-300 max-w-3xl leading-relaxed mt-0.5">
              Access 16 Barangay GIS polygon boundaries, interactive location pins, layer catalog, and boundary shape editor on the dedicated Project Maps page.
            </p>
          </div>
        </div>

        <a
          href="/maps"
          className="inline-flex items-center gap-2 rounded-full bg-amber-400 hover:bg-amber-300 px-5 py-2.5 text-xs font-bold text-slate-950 shadow-md transition shrink-0"
        >
          <span>Open Project Maps (/maps)</span>
          <ExternalLink className="w-3.5 h-3.5" />
        </a>
      </div>

      {/* EXECUTIVE KPIs SECTION */}
      <div className="space-y-4">
        <div className="flex items-center justify-between">
          <div className="flex items-center gap-2">
            <Layers className="w-4 h-4 text-amber-600" />
            <h3 className="text-sm font-bold text-slate-900 dark:text-slate-100">
              Executive Key Performance Indicators & Infrastructure Overview
            </h3>
          </div>
          <span className="text-[11px] text-slate-500 font-medium">0 Executive Signals • YTD 2026</span>
        </div>

        {/* 12 KPI CARDS GRID */}
        <div className="grid gap-3 sm:grid-cols-2 lg:grid-cols-4">
          {kpiCards.map((card, idx) => {
            const Icon = card.icon;
            return (
              <div
                key={idx}
                className={`rounded-2xl border border-slate-200/80 dark:border-slate-800 bg-white dark:bg-slate-900 p-4 shadow-sm hover:shadow-md transition border-l-4 ${card.borderAccent}`}
              >
                <div className="flex items-center justify-between text-slate-400">
                  <span className="text-[11px] font-semibold text-slate-500 dark:text-slate-400">{card.title}</span>
                  <Icon className="w-4 h-4 text-slate-400" />
                </div>
                <div className="mt-2 flex items-baseline justify-between">
                  <span className="text-2xl font-extrabold tracking-tight text-slate-900 dark:text-slate-50">{card.value}</span>
                  <span className={`text-[10px] font-bold px-2 py-0.5 rounded-full border ${card.badgeBg}`}>
                    {card.badge}
                  </span>
                </div>
                <p className="mt-1 text-[11px] text-slate-500 dark:text-slate-400">{card.subtext}</p>
              </div>
            );
          })}
        </div>
      </div>

      {/* ENGINEERING ANALYTICS & OPERATIONAL INTELLIGENCE SECTION */}
      <div className="rounded-2xl border border-slate-200/80 dark:border-slate-800 bg-white dark:bg-slate-900 p-6 shadow-sm space-y-6">
        <div className="flex flex-col md:flex-row md:items-center justify-between gap-4">
          <div>
            <div className="flex items-center gap-2">
              <TrendingUp className="w-4 h-4 text-emerald-600" />
              <h3 className="text-base font-bold text-slate-900 dark:text-slate-100">
                Engineering Analytics & Operational Intelligence
              </h3>
              <span className="bg-emerald-100 dark:bg-emerald-950 text-emerald-800 dark:text-emerald-300 text-[10px] font-extrabold px-2 py-0.5 rounded-full border border-emerald-200 dark:border-emerald-800">
                4 Live Graphs
              </span>
            </div>
            <p className="text-xs text-slate-500 dark:text-slate-400 mt-1">
              Comprehensive real-time analytics for LGU permits, CAPEX fund utilization, barangay project distribution, and equipment motorpool.
            </p>
          </div>

          {/* TAB SWITCHER BUTTONS */}
          <div className="flex items-center gap-1 bg-slate-100 dark:bg-slate-800 p-1 rounded-xl text-xs font-semibold">
            <button
              type="button"
              onClick={() => setActiveTab('permits')}
              className={`px-3 py-1.5 rounded-lg transition ${
                activeTab === 'permits'
                  ? 'bg-[#3b4819] text-white shadow-sm'
                  : 'text-slate-600 dark:text-slate-300 hover:text-slate-900'
              }`}
            >
              Permits & SLA
            </button>
            <button
              type="button"
              onClick={() => setActiveTab('capex')}
              className={`px-3 py-1.5 rounded-lg transition ${
                activeTab === 'capex'
                  ? 'bg-[#3b4819] text-white shadow-sm'
                  : 'text-slate-600 dark:text-slate-300 hover:text-slate-900'
              }`}
            >
              $ CAPEX Budget
            </button>
            <button
              type="button"
              onClick={() => setActiveTab('barangay')}
              className={`px-3 py-1.5 rounded-lg transition ${
                activeTab === 'barangay'
                  ? 'bg-[#3b4819] text-white shadow-sm'
                  : 'text-slate-600 dark:text-slate-300 hover:text-slate-900'
              }`}
            >
              Barangay Projects
            </button>
            <button
              type="button"
              onClick={() => setActiveTab('motorpool')}
              className={`px-3 py-1.5 rounded-lg transition ${
                activeTab === 'motorpool'
                  ? 'bg-[#3b4819] text-white shadow-sm'
                  : 'text-slate-600 dark:text-slate-300 hover:text-slate-900'
              }`}
            >
              Motorpool SLA
            </button>
          </div>
        </div>

        {/* GRAPH TITLE & LEGEND */}
        <div className="flex flex-col sm:flex-row sm:items-center justify-between gap-2 border-t border-slate-100 dark:border-slate-800 pt-4">
          <h4 className="text-xs font-bold text-slate-700 dark:text-slate-300">
            Graph 1: Monthly Permit Applications vs Approved Permits vs Inspections
          </h4>
          <div className="flex items-center gap-4 text-xs font-medium text-slate-600 dark:text-slate-400">
            <span className="flex items-center gap-1.5">
              <span className="w-2.5 h-2.5 rounded-full bg-amber-400" />
              Applications
            </span>
            <span className="flex items-center gap-1.5">
              <span className="w-2.5 h-2.5 rounded-full bg-emerald-500" />
              Approved
            </span>
            <span className="flex items-center gap-1.5">
              <span className="w-2.5 h-2.5 rounded-full bg-cyan-500" />
              Inspections
            </span>
          </div>
        </div>

        {/* SVG AREA & LINE CHART MATCHING SCREENSHOT */}
        <div className="relative pt-4">
          <div className="grid grid-cols-[auto_1fr] gap-4 items-end">
            <div className="flex flex-col justify-between h-48 text-[10px] font-bold text-slate-400 pr-2">
              <span>400</span>
              <span>300</span>
              <span>200</span>
              <span>100</span>
              <span>0</span>
            </div>

            <div className="relative h-48 w-full border-b border-l border-slate-200 dark:border-slate-800">
              <svg className="w-full h-full overflow-visible" viewBox="0 0 600 200" preserveAspectRatio="none">
                <defs>
                  <linearGradient id="chartGradient" x1="0" y1="0" x2="0" y2="1">
                    <stop offset="0%" stopColor="#10b981" stopOpacity="0.35" />
                    <stop offset="100%" stopColor="#10b981" stopOpacity="0.0" />
                  </linearGradient>
                </defs>

                {/* Grid Lines */}
                <line x1="0" y1="40" x2="600" y2="40" stroke="#f1f5f9" strokeDasharray="4 4" />
                <line x1="0" y1="80" x2="600" y2="80" stroke="#f1f5f9" strokeDasharray="4 4" />
                <line x1="0" y1="120" x2="600" y2="120" stroke="#f1f5f9" strokeDasharray="4 4" />
                <line x1="0" y1="160" x2="600" y2="160" stroke="#f1f5f9" strokeDasharray="4 4" />

                {/* Area Gradient Fill */}
                <polygon
                  points="0,170 100,160 200,140 300,100 400,90 500,70 600,60 600,200 0,200"
                  fill="url(#chartGradient)"
                />

                {/* Line 1: Applications (Amber) */}
                <path
                  d="M 0,175 Q 100,165 200,145 T 400,95 T 600,65"
                  fill="none"
                  stroke="#fbbf24"
                  strokeWidth="3.5"
                  strokeLinecap="round"
                />

                {/* Line 2: Approved (Green) */}
                <path
                  d="M 0,180 Q 100,170 200,150 T 400,100 T 600,75"
                  fill="none"
                  stroke="#10b981"
                  strokeWidth="3.5"
                  strokeLinecap="round"
                />

                {/* Line 3: Inspections (Cyan) */}
                <path
                  d="M 0,185 Q 100,175 200,155 T 400,110 T 600,85"
                  fill="none"
                  stroke="#06b6d4"
                  strokeWidth="3"
                  strokeDasharray="6 4"
                />

                {/* Highlight Point for Selected Month */}
                <line x1="280" y1="0" x2="280" y2="200" stroke="#a855f7" strokeDasharray="3 3" strokeWidth="1.5" />
                <circle cx="280" cy="105" r="5" fill="#10b981" stroke="#ffffff" strokeWidth="2" />
                <circle cx="280" cy="105" r="9" fill="none" stroke="#10b981" strokeOpacity="0.4" strokeWidth="2" />
              </svg>

              {/* Selected Month Analytics Card Badge */}
              <div className="absolute right-4 top-2 bg-white dark:bg-slate-800 border border-slate-200 dark:border-slate-700 shadow-lg rounded-xl p-3 text-xs">
                <span className="text-[10px] font-bold text-slate-400 uppercase tracking-wider">SELECTED MONTH</span>
                <p className="font-bold text-slate-900 dark:text-slate-100 mt-0.5">Aug 2026 Analytics</p>
                <p className="text-amber-600 font-extrabold text-sm mt-1">₱825,000</p>
              </div>
            </div>
          </div>
        </div>
      </div>
    </AppLayout>
  );
}

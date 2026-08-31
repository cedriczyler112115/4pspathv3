import { Head } from '@inertiajs/react';
import { useState } from 'react';
import AppLayout from '../Layouts/AppLayout';
import {
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
} from 'lucide-react';

export default function Dashboard() {
  const [activeTab, setActiveTab] = useState('permits');

  const kpiCards = [
    {
      title: 'Total Infrastructure Projects',
      value: '148',
      subtext: '₱140.1M Capital Allocated',
      badge: '+12.4%',
      borderAccent: 'border-l-amber-500',
      badgeBg: 'bg-emerald-500/10 text-emerald-600 dark:text-emerald-400 border-emerald-500/20',
      icon: Building2,
    },
    {
      title: 'Projects Under Construction',
      value: '38',
      subtext: 'Ongoing field operations',
      badge: 'Active',
      borderAccent: 'border-l-orange-500',
      badgeBg: 'bg-emerald-500/10 text-emerald-600 dark:text-emerald-400 border-emerald-500/20',
      icon: HardHat,
    },
    {
      title: 'Completed Infrastructure',
      value: '82',
      subtext: 'Turned over & operational',
      badge: '100% Inspected',
      borderAccent: 'border-l-emerald-500',
      badgeBg: 'bg-emerald-500/10 text-emerald-600 dark:text-emerald-400 border-emerald-500/20',
      icon: CheckCircle2,
    },
    {
      title: 'Total CAPEX Expended',
      value: '₱125.8M',
      subtext: '₱14.3M Balance remaining',
      badge: '89.9% Disbursed',
      borderAccent: 'border-l-cyan-500',
      badgeBg: 'bg-emerald-500/10 text-emerald-600 dark:text-emerald-400 border-emerald-500/20',
      icon: DollarSign,
    },
    {
      title: 'Barangays Covered',
      value: '16',
      subtext: 'Municipal District',
      badge: '100% Coverage',
      borderAccent: 'border-l-blue-500',
      badgeBg: 'bg-emerald-500/10 text-emerald-600 dark:text-emerald-400 border-emerald-500/20',
      icon: MapPin,
    },
    {
      title: 'Active Work Orders',
      value: '24',
      subtext: 'SLA processing on-track',
      badge: '3.1 Days Avg',
      borderAccent: 'border-l-purple-500',
      badgeBg: 'bg-emerald-500/10 text-emerald-600 dark:text-emerald-400 border-emerald-500/20',
      icon: FileCheck,
    },
    {
      title: 'Motorpool Availability',
      value: '88.9%',
      subtext: 'Fleet readiness',
      badge: '24/27 Units',
      borderAccent: 'border-l-sky-500',
      badgeBg: 'bg-emerald-500/10 text-emerald-600 dark:text-emerald-400 border-emerald-500/20',
      icon: Truck,
    },
    {
      title: 'Field Inspections',
      value: '1,449',
      subtext: 'Quality reports',
      badge: '+18.4% YoY',
      borderAccent: 'border-l-teal-500',
      badgeBg: 'bg-emerald-500/10 text-emerald-600 dark:text-emerald-400 border-emerald-500/20',
      icon: ClipboardCheck,
    },
  ];

  return (
    <AppLayout>
      <Head title="Dashboard - 4Ps PATH v3" />

      {/* EXECUTIVE KPIs GRID */}
      <div className="space-y-2">
        <div className="flex items-center justify-between px-0.5">
          <div className="flex items-center gap-1.5">
            <Layers className="size-3.5 text-primary" />
            <h3 className="text-xs font-bold text-foreground uppercase tracking-wider">
              Executive Indicators &amp; Overview
            </h3>
          </div>
          <span className="text-[10px] text-muted-foreground font-mono">YTD 2026</span>
        </div>

        {/* 8 COMPACT KPI CARDS */}
        <div className="grid gap-2 sm:grid-cols-2 lg:grid-cols-4">
          {kpiCards.map((card, idx) => {
            const Icon = card.icon;
            return (
              <div
                key={idx}
                className={`rounded-lg border border-border bg-card p-2.5 shadow-2xs hover:shadow-xs transition border-l-3 ${card.borderAccent}`}
              >
                <div className="flex items-center justify-between">
                  <span className="text-[10px] font-semibold text-muted-foreground truncate">{card.title}</span>
                  <Icon className="size-3.5 text-muted-foreground/70 shrink-0" />
                </div>
                <div className="mt-1 flex items-baseline justify-between">
                  <span className="text-lg font-bold tracking-tight text-card-foreground">{card.value}</span>
                  <span className={`text-[9px] font-bold px-1.5 py-0.2 rounded-full border ${card.badgeBg}`}>
                    {card.badge}
                  </span>
                </div>
                <p className="text-[10px] text-muted-foreground truncate mt-0.5">{card.subtext}</p>
              </div>
            );
          })}
        </div>
      </div>

      {/* COMPACT ANALYTICS & GRAPH SECTION */}
      <div className="rounded-lg border border-border bg-card p-3 sm:p-4 shadow-2xs space-y-3">
        <div className="flex flex-col sm:flex-row sm:items-center justify-between gap-2 border-b border-border pb-2.5">
          <div className="flex items-center gap-2">
            <TrendingUp className="size-4 text-emerald-600 dark:text-emerald-400" />
            <h3 className="text-xs font-bold text-card-foreground">
              Performance Analytics &amp; Accomplishment Metrics
            </h3>
          </div>

          {/* TAB SWITCHER */}
          <div className="flex items-center gap-1 bg-muted p-0.5 rounded-md text-[11px] font-medium">
            <button
              type="button"
              onClick={() => setActiveTab('permits')}
              className={`px-2.5 py-1 rounded transition ${
                activeTab === 'permits'
                  ? 'bg-background text-foreground font-semibold shadow-2xs'
                  : 'text-muted-foreground hover:text-foreground'
              }`}
            >
              Targets vs Output
            </button>
            <button
              type="button"
              onClick={() => setActiveTab('capex')}
              className={`px-2.5 py-1 rounded transition ${
                activeTab === 'capex'
                  ? 'bg-background text-foreground font-semibold shadow-2xs'
                  : 'text-muted-foreground hover:text-foreground'
              }`}
            >
              Efficiency Rating
            </button>
            <button
              type="button"
              onClick={() => setActiveTab('barangay')}
              className={`px-2.5 py-1 rounded transition ${
                activeTab === 'barangay'
                  ? 'bg-background text-foreground font-semibold shadow-2xs'
                  : 'text-muted-foreground hover:text-foreground'
              }`}
            >
              Semestral Trend
            </button>
          </div>
        </div>

        {/* GRAPH HEADER & LEGEND */}
        <div className="flex flex-col sm:flex-row sm:items-center justify-between gap-2">
          <span className="text-[11px] font-medium text-muted-foreground">
            Monthly Target Accomplishment vs Verified Deliverables
          </span>
          <div className="flex items-center gap-3 text-[11px] text-muted-foreground">
            <span className="flex items-center gap-1">
              <span className="size-2 rounded-full bg-amber-400" />
              Planned Targets
            </span>
            <span className="flex items-center gap-1">
              <span className="size-2 rounded-full bg-emerald-500" />
              Accomplished
            </span>
            <span className="flex items-center gap-1">
              <span className="size-2 rounded-full bg-cyan-500" />
              Verified MOVs
            </span>
          </div>
        </div>

        {/* COMPACT SVG CHART */}
        <div className="relative pt-2">
          <div className="grid grid-cols-[auto_1fr] gap-2 items-end">
            <div className="flex flex-col justify-between h-36 text-[9px] font-mono text-muted-foreground pr-1">
              <span>100%</span>
              <span>75%</span>
              <span>50%</span>
              <span>25%</span>
              <span>0%</span>
            </div>

            <div className="relative h-36 w-full border-b border-l border-border">
              <svg className="w-full h-full overflow-visible" viewBox="0 0 600 150" preserveAspectRatio="none">
                <defs>
                  <linearGradient id="chartGradient" x1="0" y1="0" x2="0" y2="1">
                    <stop offset="0%" stopColor="#10b981" stopOpacity="0.25" />
                    <stop offset="100%" stopColor="#10b981" stopOpacity="0.0" />
                  </linearGradient>
                </defs>

                {/* Grid Lines */}
                <line x1="0" y1="30" x2="600" y2="30" stroke="currentColor" className="text-border" strokeDasharray="3 3" />
                <line x1="0" y1="60" x2="600" y2="60" stroke="currentColor" className="text-border" strokeDasharray="3 3" />
                <line x1="0" y1="90" x2="600" y2="90" stroke="currentColor" className="text-border" strokeDasharray="3 3" />
                <line x1="0" y1="120" x2="600" y2="120" stroke="currentColor" className="text-border" strokeDasharray="3 3" />

                {/* Area Gradient Fill */}
                <polygon
                  points="0,130 100,120 200,100 300,75 400,65 500,50 600,40 600,150 0,150"
                  fill="url(#chartGradient)"
                />

                {/* Planned Targets (Amber) */}
                <path
                  d="M 0,135 Q 100,125 200,105 T 400,70 T 600,45"
                  fill="none"
                  stroke="#fbbf24"
                  strokeWidth="2.5"
                  strokeLinecap="round"
                />

                {/* Accomplished (Green) */}
                <path
                  d="M 0,140 Q 100,130 200,110 T 400,75 T 600,55"
                  fill="none"
                  stroke="#10b981"
                  strokeWidth="2.5"
                  strokeLinecap="round"
                />

                {/* Verified (Cyan) */}
                <path
                  d="M 0,145 Q 100,135 200,115 T 400,85 T 600,65"
                  fill="none"
                  stroke="#06b6d4"
                  strokeWidth="2"
                  strokeDasharray="4 3"
                />

                <circle cx="300" cy="75" r="4" fill="#10b981" stroke="#ffffff" strokeWidth="1.5" />
              </svg>
            </div>
          </div>
        </div>
      </div>
    </AppLayout>
  );
}

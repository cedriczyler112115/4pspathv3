import { useRef, useState } from 'react';
import { Head, Link, router, useForm } from '@inertiajs/react';
import AppLayout from '../Layouts/AppLayout';
import UserAvatar from '../Components/UserAvatar';
import {
  ShieldCheck,
  Search,
  X,
  RotateCcw,
  Mail,
  Phone,
  CheckCircle2,
  Clock,
  Lock,
  Flag,
  Edit3,
  FileSpreadsheet,
  AlertCircle,
  ExternalLink,
  Users,
} from 'lucide-react';

type VerificationRecord = {
  userId: number;
  lastName: string;
  firstName: string;
  middleName: string;
  extensionName: string;
  fullName: string;
  email: string;
  contactNumber?: string | null;
  position?: string | null;
  designation?: string | null;
  divisionName?: string | null;
  sectionName?: string | null;
  userLevelName?: string | null;
  userStatus: number;
  semesterId: number | null;
  year: string | null;
  semester: string | null;
  lock: number | null;
  isReady?: number | null;
  dateReady?: string | null;
  dateVerified?: string | null;
  finalRating: number | null;
  adjectivalRating?: string | null;
  overallRemarks?: string | null;
  dateCreated?: string | null;
};

type Props = {
  appName: string;
  user: { name: string; email: string } | null;
  filters: {
    search: string;
    year: string;
    semester: string;
    perPage: number;
  };
  records: {
    data: VerificationRecord[];
    from: number | null;
    to: number | null;
    total: number;
    links: Array<{ url: string | null; label: string; active: boolean }>;
  };
  years: string[];
  semesters: Array<{ value: string; label: string }>;
  perPageOptions: Array<{ value: number; label: string }>;
  navigation?: { sidebar?: any[] };
};

export default function Verification({
  appName,
  user,
  filters,
  records,
  years,
  semesters,
  perPageOptions,
  navigation,
}: Props) {
  const filterForm = useForm({
    search: filters.search || '',
    year: filters.year || '',
    semester: filters.semester || '',
    perPage: String(filters.perPage || 10),
  });

  const searchTimerRef = useRef<NodeJS.Timeout | null>(null);

  const handleSearchChange = (val: string) => {
    filterForm.setData('search', val);
    if (searchTimerRef.current) clearTimeout(searchTimerRef.current);
    searchTimerRef.current = setTimeout(() => {
      submitFilters({ search: val, page: 1 });
    }, 350);
  };

  const submitFilters = (overrides?: Partial<typeof filterForm.data> & { page?: number }) => {
    if (searchTimerRef.current) clearTimeout(searchTimerRef.current);
    const data = { ...filterForm.data, ...overrides };
    router.post('/verification', data, {
      preserveState: true,
      replace: true,
      preserveScroll: true,
    });
  };

  const resetFilters = () => {
    if (searchTimerRef.current) clearTimeout(searchTimerRef.current);
    filterForm.setData({
      search: '',
      year: '',
      semester: '',
      perPage: '10',
    });
    router.post('/verification', { search: '', year: '', semester: '', perPage: '10', page: 1 }, { replace: true, preserveState: true });
  };

  const getInitials = (name?: string) => {
    if (!name) return 'U';
    const parts = name.trim().split(' ');
    if (parts.length === 1) return parts[0].substring(0, 2).toUpperCase();
    return (parts[0][0] + parts[parts.length - 1][0]).toUpperCase();
  };

  const formatDateTime = (dateStr?: string | null) => {
    if (!dateStr) return '-';
    try {
      const d = new Date(dateStr);
      if (isNaN(d.getTime())) return dateStr;
      return d.toLocaleDateString('en-US', {
        month: 'short',
        day: '2-digit',
        year: 'numeric',
        hour: '2-digit',
        minute: '2-digit',
        hour12: true,
      });
    } catch {
      return dateStr;
    }
  };

  const renderStatusBadge = (row: VerificationRecord) => {
    if (!row.semesterId) {
      return (
        <span className="inline-flex items-center gap-1 rounded-full bg-zinc-500/10 text-zinc-600 dark:text-zinc-400 px-2 py-0.5 text-[10px] font-semibold border border-zinc-500/20">
          No Rating Record
        </span>
      );
    }

    if (row.dateVerified) {
      return (
        <span className="inline-flex items-center gap-1 rounded-full bg-emerald-500/10 text-emerald-700 dark:text-emerald-300 px-2.5 py-0.5 text-[10px] font-bold border border-emerald-500/20">
          <ShieldCheck className="size-3 text-emerald-600 dark:text-emerald-400 shrink-0" />
          <span>Verified on {formatDateTime(row.dateVerified)}</span>
        </span>
      );
    }

    if (row.isReady === 1) {
      return (
        <span className="inline-flex items-center gap-1 rounded-full bg-purple-500/10 text-purple-700 dark:text-purple-300 px-2.5 py-0.5 text-[10px] font-bold border border-purple-500/20 animate-pulse">
          <Clock className="size-3 text-purple-600 dark:text-purple-400 shrink-0" />
          <span>Ready on {row.dateReady ? formatDateTime(row.dateReady) : '-'}</span>
        </span>
      );
    }

    if (row.lock === 1) {
      return (
        <span className="inline-flex items-center gap-1 rounded-full bg-sky-500/10 text-sky-700 dark:text-sky-300 px-2.5 py-0.5 text-[10px] font-bold border border-sky-500/20">
          <Edit3 className="size-3 text-sky-600 dark:text-sky-400 shrink-0" />
          <span>Self-Rating state</span>
        </span>
      );
    }

    return (
      <span className="inline-flex items-center gap-1 rounded-full bg-amber-500/10 text-amber-700 dark:text-amber-300 px-2.5 py-0.5 text-[10px] font-bold border border-amber-500/20">
        <Flag className="size-3 text-amber-600 dark:text-amber-400 shrink-0" />
        <span>Checkpointing state</span>
      </span>
    );
  };

  return (
    <AppLayout appName={appName} user={user} navigation={navigation}>
      <Head title="Staff Performance Verification - 4Ps PATH" />

      <div className="space-y-4">
        {/* HEADER & FILTERS CARD */}
        <div className="rounded-xl border border-border bg-card p-3 sm:p-4 shadow-2xs space-y-3">
          <div className="flex flex-col sm:flex-row sm:items-center justify-between gap-3 border-b border-border/80 pb-3">
            {/* TITLE */}
            <div className="flex items-center gap-2.5">
              <div className="size-8 rounded-lg bg-emerald-500/10 text-emerald-700 dark:text-emerald-400 flex items-center justify-center font-bold">
                <ShieldCheck className="size-4.5" />
              </div>
              <div>
                <h1 className="text-sm font-bold tracking-tight text-foreground flex items-center gap-2">
                  <span>Staff Performance Verification</span>
                  <span className="rounded-full bg-emerald-500/10 text-emerald-700 dark:text-emerald-400 font-mono text-[10px] font-bold px-2 py-0.2 border border-emerald-500/20">
                    {records.total} Record{records.total !== 1 ? 's' : ''}
                  </span>
                </h1>
                <p className="text-[11px] text-muted-foreground">
                  Monitor, inspect, and verify semestral target ratings submitted by staff members under your supervision.
                </p>
              </div>
            </div>

            {/* ACTIONS */}
            <div className="flex items-center gap-2">
              <button
                type="button"
                onClick={resetFilters}
                className="h-8 inline-flex items-center gap-1.5 rounded-lg border border-input bg-background px-2.5 text-xs font-medium text-muted-foreground hover:text-foreground hover:bg-muted transition cursor-pointer"
                title="Reset all filters"
              >
                <RotateCcw className="size-3" />
                <span>Reset</span>
              </button>
            </div>
          </div>

          {/* FILTERS FORM */}
          <div className="grid gap-2.5 sm:grid-cols-2 lg:grid-cols-4">
            {/* Search Input */}
            <div className="space-y-1 sm:col-span-2">
              <label className="text-[11px] font-semibold text-muted-foreground">
                Search Staff (Last, First, Middle Name)
              </label>
              <div className="relative">
                <Search className="size-3.5 absolute left-2.5 top-1/2 -translate-y-1/2 text-muted-foreground" />
                <input
                  type="text"
                  value={filterForm.data.search}
                  onChange={(e) => handleSearchChange(e.target.value)}
                  placeholder="Search staff by last, first, middle name, email, position..."
                  className="h-8 w-full rounded-lg border border-input bg-background pl-8 pr-7 text-xs text-foreground placeholder:text-muted-foreground/60 outline-hidden focus:ring-2 focus:ring-ring"
                />
                {filterForm.data.search && (
                  <button
                    type="button"
                    onClick={() => {
                      filterForm.setData('search', '');
                      if (searchTimerRef.current) clearTimeout(searchTimerRef.current);
                      submitFilters({ search: '', page: 1 });
                    }}
                    className="absolute right-2 top-1/2 -translate-y-1/2 text-muted-foreground hover:text-foreground cursor-pointer"
                  >
                    <X className="size-3" />
                  </button>
                )}
              </div>
            </div>

            {/* Year Filter */}
            <div className="space-y-1">
              <label className="text-[11px] font-semibold text-muted-foreground">Target Year</label>
              <select
                value={filterForm.data.year}
                onChange={(e) => {
                  filterForm.setData('year', e.target.value);
                  submitFilters({ year: e.target.value });
                }}
                className="h-8 w-full rounded-lg border border-input bg-background px-2.5 text-xs text-foreground outline-hidden focus:ring-2 focus:ring-ring cursor-pointer"
              >
                <option value="">All Years</option>
                {years.map((y) => (
                  <option key={y} value={y}>
                    {y}
                  </option>
                ))}
              </select>
            </div>

            {/* Semester Filter */}
            <div className="space-y-1">
              <label className="text-[11px] font-semibold text-muted-foreground">Semester</label>
              <select
                value={filterForm.data.semester}
                onChange={(e) => {
                  filterForm.setData('semester', e.target.value);
                  submitFilters({ semester: e.target.value });
                }}
                className="h-8 w-full rounded-lg border border-input bg-background px-2.5 text-xs text-foreground outline-hidden focus:ring-2 focus:ring-ring cursor-pointer"
              >
                <option value="">All Semesters</option>
                {semesters.map((s) => (
                  <option key={s.value} value={s.value}>
                    {s.label}
                  </option>
                ))}
              </select>
            </div>
          </div>
        </div>

        {/* RESULTS TABLE CARD */}
        <div className="rounded-xl border border-border bg-card shadow-2xs overflow-hidden">
          <div className="overflow-x-auto">
            <table className="w-full min-w-[980px] border-collapse text-xs text-left">
              <thead>
                <tr className="bg-muted/60 text-[11px] font-bold uppercase tracking-wider text-muted-foreground border-b border-border">
                  <th className="px-3 py-2.5 text-center w-12 border-r border-border">#</th>
                  <th className="px-3 py-2.5 border-r border-border">Staff Member</th>
                  <th className="px-3 py-2.5 border-r border-border">Division / Section</th>
                  <th className="px-3 py-2.5 border-r border-border text-center w-24">Year</th>
                  <th className="px-3 py-2.5 border-r border-border text-center w-32">Semester</th>
                  <th className="px-3 py-2.5 border-r border-border text-center">Verification Status</th>
                  <th className="px-3 py-2.5 border-r border-border text-center">Final Rating</th>
                  <th className="px-3 py-2.5 text-center w-24">Action</th>
                </tr>
              </thead>
              <tbody className="divide-y divide-border">
                {records.data.length === 0 ? (
                  <tr>
                    <td colSpan={8} className="px-4 py-12 text-center">
                      <div className="flex flex-col items-center justify-center space-y-2">
                        <div className="size-10 rounded-full bg-muted flex items-center justify-center text-muted-foreground">
                          <Users className="size-5" />
                        </div>
                        <p className="text-xs font-bold text-foreground">No verification records found</p>
                        <p className="text-[11px] text-muted-foreground max-w-sm">
                          {filterForm.data.search || filterForm.data.year || filterForm.data.semester
                            ? 'No staff performance records match your current filter settings. Try resetting your search filters.'
                            : 'No staff members under your supervision have rating records in ipc_semester.'}
                        </p>
                      </div>
                    </td>
                  </tr>
                ) : (
                  records.data.map((row, idx) => (
                    <tr key={`${row.userId}-${row.semesterId || idx}`} className="hover:bg-muted/30 transition-colors">
                      {/* # INDEX */}
                      <td className="px-3 py-2.5 text-center font-mono text-[11px] text-muted-foreground border-r border-border">
                        {(records.from || 1) + idx}
                      </td>

                      {/* STAFF MEMBER */}
                      <td className="px-3 py-2.5 border-r border-border">
                        <div className="flex items-center gap-2.5">
                          <UserAvatar
                            user={{
                              name: row.fullName,
                              avatar_url: (row as any).avatarUrl,
                              avatar: (row as any).avatar,
                            }}
                            size="sm"
                            fallbackInitials={getInitials(row.fullName)}
                          />
                          <div>
                            <div className="font-semibold text-foreground text-xs leading-tight">
                              {row.fullName || 'Unnamed Staff'}
                            </div>
                            <div className="text-[10px] text-muted-foreground">
                              {row.designation || row.position || row.email}
                            </div>
                          </div>
                        </div>
                      </td>

                      {/* DIVISION / SECTION */}
                      <td className="px-3 py-2.5 border-r border-border">
                        <div className="font-medium text-foreground">{row.divisionName || '—'}</div>
                        {row.sectionName && (
                          <div className="text-[10px] text-muted-foreground">{row.sectionName}</div>
                        )}
                      </td>

                      {/* YEAR */}
                      <td className="px-3 py-2.5 border-r border-border text-center font-mono font-medium text-foreground">
                        {row.year ? row.year : <span className="text-muted-foreground text-[11px]">—</span>}
                      </td>

                      {/* SEMESTER */}
                      <td className="px-3 py-2.5 border-r border-border text-center">
                        {row.semester === '1' ? (
                          <span className="inline-flex items-center rounded-md bg-muted px-2 py-0.5 text-[11px] font-semibold text-foreground border border-border">
                            1st Semester
                          </span>
                        ) : row.semester === '2' ? (
                          <span className="inline-flex items-center rounded-md bg-muted px-2 py-0.5 text-[11px] font-semibold text-foreground border border-border">
                            2nd Semester
                          </span>
                        ) : (
                          <span className="text-muted-foreground text-[11px]">—</span>
                        )}
                      </td>

                      {/* VERIFICATION STATUS */}
                      <td className="px-3 py-2.5 border-r border-border text-center">
                        {renderStatusBadge(row)}
                      </td>

                      {/* FINAL RATING */}
                      <td className="px-3 py-2.5 border-r border-border text-center">
                        {row.finalRating !== null ? (
                          <div>
                            <span className="font-bold text-foreground font-mono text-xs">
                              {Number(row.finalRating).toFixed(2)}
                            </span>
                            {row.adjectivalRating && (
                              <div className="text-[10px] text-emerald-600 dark:text-emerald-400 font-semibold">
                                {row.adjectivalRating}
                              </div>
                            )}
                          </div>
                        ) : (
                          <span className="text-muted-foreground text-[11px]">—</span>
                        )}
                      </td>

                      {/* ACTIONS */}
                      <td className="px-3 py-2.5 text-center">
                        <div className="flex items-center justify-center">
                          {row.semesterId ? (
                            <Link
                              href={`/verification/${row.semesterId}/semestral-verification`}
                              title="Review & Verify Semestral Targets"
                              className="size-7 inline-flex items-center justify-center rounded-lg bg-emerald-600/10 text-emerald-700 dark:text-emerald-400 hover:bg-emerald-600 hover:text-white border border-emerald-500/20 transition cursor-pointer"
                            >
                              <FileSpreadsheet className="size-3.5" />
                            </Link>
                          ) : (
                            <span className="text-muted-foreground text-[11px]">—</span>
                          )}
                        </div>
                      </td>
                    </tr>
                  ))
                )}
              </tbody>
            </table>
          </div>

          {/* PAGINATION FOOTER */}
          <div className="flex flex-col sm:flex-row items-center justify-between gap-3 border-t border-border bg-card px-3 py-2.5 text-xs text-muted-foreground">
            <div className="flex items-center gap-2">
              <span>
                Showing <strong className="text-foreground">{records.from ?? 0}</strong> to{' '}
                <strong className="text-foreground">{records.to ?? 0}</strong> of{' '}
                <strong className="text-foreground">{records.total}</strong> results
              </span>

              <div className="flex items-center gap-1 pl-2 border-l border-border">
                <span className="text-[11px]">Per page:</span>
                <select
                  value={filterForm.data.perPage}
                  onChange={(e) => {
                    filterForm.setData('perPage', e.target.value);
                    submitFilters({ perPage: e.target.value });
                  }}
                  className="h-7 rounded-md border border-input bg-background px-1.5 text-xs text-foreground outline-hidden focus:ring-1 focus:ring-ring cursor-pointer"
                >
                  {perPageOptions.map((opt) => (
                    <option key={opt.value} value={opt.value}>
                      {opt.label}
                    </option>
                  ))}
                </select>
              </div>
            </div>

            {/* Pagination Links */}
            {records.links && records.links.length > 3 && (
              <div className="flex items-center gap-1 flex-wrap">
                {records.links.map((link, i) => {
                  if (!link.url) {
                    return (
                      <span
                        key={i}
                        dangerouslySetInnerHTML={{ __html: link.label }}
                        className="inline-flex min-w-7 h-7 items-center justify-center rounded-md px-2 text-xs text-muted-foreground/50 border border-transparent select-none"
                      />
                    );
                  }

                  let targetPage = records.current_page;
                  try {
                    const urlObj = new URL(link.url, window.location.origin);
                    targetPage = Number(urlObj.searchParams.get('page')) || records.current_page;
                  } catch {
                    targetPage = records.current_page;
                  }

                  return (
                    <button
                      key={i}
                      type="button"
                      onClick={() => submitFilters({ page: targetPage })}
                      dangerouslySetInnerHTML={{ __html: link.label }}
                      className={`inline-flex min-w-7 h-7 items-center justify-center rounded-md px-2 text-xs font-semibold transition cursor-pointer ${
                        link.active
                          ? 'bg-emerald-600 text-white shadow-xs'
                          : 'border border-input bg-background text-foreground hover:bg-muted'
                      }`}
                    />
                  );
                })}
              </div>
            )}
          </div>
        </div>
      </div>
    </AppLayout>
  );
}

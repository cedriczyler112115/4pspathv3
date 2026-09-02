import { useState, useEffect, useRef } from 'react';
import { Head, Link, router, useForm } from '@inertiajs/react';
import { Eye, Trash2, RotateCcw, Search, ShieldCheck, Clock, Edit3, Flag, AlertTriangle, X } from 'lucide-react';
import AppLayout from '../../Layouts/AppLayout';
import UserAvatar from '../../Components/UserAvatar';
import { readPersistedFilters, savePersistedFilters } from '../../lib/filterPersistence';

type Rating = {
  id: number;
  year: string;
  semester: number;
  finalRating: string | null;
  adjectivalRating: string | null;
  lock: number;
  dateVerified: string | null;
  dateCreated: string | null;
  overallRemarks: string | null;
};

type Props = {
  appName: string;
  user: { name: string; email: string } | null;
  profile: {
    fullName: string;
    position: string;
    designation: string;
    divisionName: string;
    sectionName: string;
  } | null;
  filters: {
    search: string;
    year: string;
    semester: string;
    perPage: number;
  };
  years: Array<{ target_year: string }>;
  semesters: Array<{ value: string; label: string }>;
  perPageOptions: Array<{ value: number; label: string }>;
  ratings: {
    data: Rating[];
    from: number | null;
    to: number | null;
    total: number;
    links: Array<{ url: string | null; label: string; active: boolean }>;
  };
  navigation?: { sidebar?: any[] };
};

export default function MyRatings({
  appName,
  user,
  profile,
  filters,
  years,
  semesters,
  perPageOptions,
  ratings,
  navigation,
}: Props) {
  const pageKey = 'ipcrf-myratings';
  const persisted = readPersistedFilters(pageKey, user, {
    search: filters.search || '',
    year: filters.year || '',
    semester: filters.semester || '',
    perPage: String(filters.perPage || 10),
  });
  const form = useForm({
    search: persisted.search,
    year: persisted.year,
    semester: persisted.semester,
    perPage: persisted.perPage,
  });

  const [deletingId, setDeletingId] = useState<number | null>(null);
  const [isDeleting, setIsDeleting] = useState(false);
  const searchTimerRef = useRef<NodeJS.Timeout | null>(null);

  const applyFilters = (overrides?: Partial<typeof form.data> & { page?: number }) => {
    if (searchTimerRef.current) clearTimeout(searchTimerRef.current);
    const data = { ...form.data, ...overrides };
    router.post('/ipcrf/myratings', data, {
      preserveState: true,
      replace: true,
      preserveScroll: true,
    });
    savePersistedFilters(pageKey, user, data);
  };

  const handleSearchChange = (val: string) => {
    form.setData('search', val);
    savePersistedFilters(pageKey, user, { ...form.data, search: val });
    if (searchTimerRef.current) clearTimeout(searchTimerRef.current);
    searchTimerRef.current = setTimeout(() => {
      applyFilters({ search: val, page: 1 });
    }, 350);
  };

  const handleYearChange = (val: string) => {
    form.setData('year', val);
    savePersistedFilters(pageKey, user, { ...form.data, year: val });
    applyFilters({ year: val, page: 1 });
  };

  const handleSemesterChange = (val: string) => {
    form.setData('semester', val);
    savePersistedFilters(pageKey, user, { ...form.data, semester: val });
    applyFilters({ semester: val, page: 1 });
  };

  const handlePerPageChange = (val: string) => {
    form.setData('perPage', val);
    savePersistedFilters(pageKey, user, { ...form.data, perPage: val });
    applyFilters({ perPage: val, page: 1 });
  };

  const resetFilters = () => {
    if (searchTimerRef.current) clearTimeout(searchTimerRef.current);
    form.setData({
      search: '',
      year: '',
      semester: '',
      perPage: '10',
    });
    router.post('/ipcrf/myratings', { search: '', year: '', semester: '', perPage: '10', page: 1 }, { replace: true, preserveState: true });
    savePersistedFilters(pageKey, user, { search: '', year: '', semester: '', perPage: '10' });
  };

  const confirmDelete = (id: number) => {
    setDeletingId(id);
  };

  const cancelDelete = () => {
    setDeletingId(null);
  };

  const handleDelete = () => {
    if (!deletingId) return;
    setIsDeleting(true);
    router.delete(`/ipcrf/myratings/${deletingId}`, {
      preserveScroll: true,
      onFinish: () => {
        setIsDeleting(false);
        setDeletingId(null);
      },
    });
  };

  const formatDate = (dateStr: string | null) => {
    if (!dateStr) return '-';
    try {
      const d = new Date(dateStr);
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

  return (
    <AppLayout appName={appName} user={user} sidebar={navigation?.sidebar ?? []}>
      <Head title="My Ratings - IPCRF" />

      <div className="space-y-3">
        {/* TOP FILTER & USER PROFILE CARD */}
        <div className="rounded-xl border border-border bg-card p-3 sm:p-4 shadow-2xs space-y-3">
          {/* HEADER */}
          <div className="flex flex-col sm:flex-row sm:items-center justify-between gap-2 border-b border-border/80 pb-3">
            <div className="flex items-center gap-2.5">
              <div className="size-8 rounded-lg bg-emerald-500/10 text-emerald-700 dark:text-emerald-400 flex items-center justify-center font-bold">
                <ShieldCheck className="size-4.5" />
              </div>
              <div>
                <h1 className="text-sm font-bold tracking-tight text-foreground flex items-center gap-2">
                  <span>My Performance Ratings (IPCRF)</span>
                  <span className="rounded-full bg-emerald-500/10 text-emerald-700 dark:text-emerald-400 font-mono text-[10px] font-bold px-2 py-0.2 border border-emerald-500/20">
                    {ratings.total} Total Ratings
                  </span>
                </h1>
                <p className="text-[11px] text-muted-foreground">
                  Review IPCRF semester ratings, performance achievements, and verification status.
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

          {/* USER PROFILE STRIP */}
          {profile && (
            <div className="rounded-lg border border-border bg-muted/30 p-2.5 overflow-x-auto">
              <table className="w-full border-0 border-collapse text-xs">
                <tbody>
                  <tr className="align-top">
                    <td className="pr-6 whitespace-nowrap">
                      <div className="text-[10px] font-semibold uppercase text-muted-foreground">Full Name</div>
                      <div className="mt-0.5 font-bold uppercase text-foreground flex items-center gap-2">
                        <UserAvatar
                          user={{
                            name: profile.fullName,
                            avatar_url: (profile as any).avatarUrl,
                            avatar: (profile as any).avatar,
                          }}
                          size="sm"
                        />
                        <span>{profile.fullName || '-'}</span>
                      </div>
                    </td>
                    <td className="pr-6 whitespace-nowrap">
                      <div className="text-[10px] font-semibold uppercase text-muted-foreground">Position</div>
                      <div className="mt-0.5 font-bold uppercase text-foreground">{profile.position || '-'}</div>
                    </td>
                    <td className="pr-6 whitespace-nowrap">
                      <div className="text-[10px] font-semibold uppercase text-muted-foreground">Designation</div>
                      <div className="mt-0.5 font-bold uppercase text-foreground">{profile.designation || '-'}</div>
                    </td>
                    <td className="pr-6 whitespace-nowrap">
                      <div className="text-[10px] font-semibold uppercase text-muted-foreground">Division Name</div>
                      <div className="mt-0.5 font-bold uppercase text-foreground">{profile.divisionName || '-'}</div>
                    </td>
                    <td className="whitespace-nowrap">
                      <div className="text-[10px] font-semibold uppercase text-muted-foreground">Section Name</div>
                      <div className="mt-0.5 font-bold uppercase text-foreground">{profile.sectionName || '-'}</div>
                    </td>
                  </tr>
                </tbody>
              </table>
            </div>
          )}

          {/* FILTERS FORM */}
          <form onSubmit={(e) => { e.preventDefault(); applyFilters({ page: 1 }); }} className="grid gap-2.5 sm:grid-cols-2 lg:grid-cols-4">
            {/* Search Input */}
            <div className="space-y-1">
              <label className="text-[11px] font-semibold text-muted-foreground">Search by Keywords</label>
              <div className="relative">
                <Search className="size-3.5 absolute left-2.5 top-1/2 -translate-y-1/2 text-muted-foreground" />
                <input
                  type="text"
                  value={form.data.search}
                  onChange={(e) => handleSearchChange(e.target.value)}
                  placeholder="Search remarks, ratings..."
                  className="h-8 w-full rounded-lg border border-input bg-background pl-8 pr-7 text-xs text-foreground placeholder:text-muted-foreground/60 outline-hidden focus:ring-2 focus:ring-ring"
                />
                {form.data.search && (
                  <button
                    type="button"
                    onClick={() => {
                      form.setData('search', '');
                      if (searchTimerRef.current) clearTimeout(searchTimerRef.current);
                      applyFilters({ search: '', page: 1 });
                    }}
                    className="absolute right-2 top-1/2 -translate-y-1/2 text-muted-foreground hover:text-foreground cursor-pointer"
                  >
                    <X className="size-3" />
                  </button>
                )}
              </div>
            </div>

            {/* Year Selector */}
            <div className="space-y-1">
              <label className="text-[11px] font-semibold text-muted-foreground">Target Year</label>
              <select
                value={form.data.year}
                onChange={(e) => handleYearChange(e.target.value)}
                className="h-8 w-full rounded-lg border border-input bg-background px-2.5 text-xs text-foreground outline-hidden focus:ring-2 focus:ring-ring cursor-pointer"
              >
                <option value="">All Years ({years.length})</option>
                {years.map((y) => (
                  <option key={y.target_year} value={y.target_year}>
                    {y.target_year}
                  </option>
                ))}
              </select>
            </div>

            {/* Semester Selector */}
            <div className="space-y-1">
              <label className="text-[11px] font-semibold text-muted-foreground">Semester Period</label>
              <select
                value={form.data.semester}
                onChange={(e) => handleSemesterChange(e.target.value)}
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

            {/* Records per page */}
            <div className="space-y-1">
              <label className="text-[11px] font-semibold text-muted-foreground">Per Page</label>
              <select
                value={form.data.perPage}
                onChange={(e) => handlePerPageChange(e.target.value)}
                className="h-8 w-full rounded-lg border border-input bg-background px-2.5 text-xs text-foreground outline-hidden focus:ring-2 focus:ring-ring cursor-pointer"
              >
                {perPageOptions.map((opt) => (
                  <option key={opt.value} value={opt.value}>
                    {opt.label}
                  </option>
                ))}
              </select>
            </div>
          </form>
        </div>

        {/* RESULTS TABLE CARD */}
        <div className="rounded-xl border border-border bg-card shadow-2xs overflow-hidden">
          <div className="overflow-x-auto">
            <table className="w-full min-w-[860px] border-collapse text-xs text-left">
              <thead>
                <tr className="bg-muted/60 text-[11px] font-bold uppercase tracking-wider text-muted-foreground border-b border-border">
                  <th className="px-3 py-2 text-center w-12 border-r border-border">#</th>
                  <th className="px-3 py-2 border-r border-border w-24">Year</th>
                  <th className="px-3 py-2 border-r border-border w-32">Semester</th>
                  <th className="px-3 py-2 border-r border-border w-28">Final Rating</th>
                  <th className="px-3 py-2 border-r border-border w-36">Adjectival</th>
                  <th className="px-3 py-2 border-r border-border text-center">Status</th>
                  <th className="px-3 py-2 border-r border-border">Date Created</th>
                  <th className="px-3 py-2 text-left w-20">Action</th>
                </tr>
              </thead>
              <tbody className="divide-y divide-border">
                {ratings.data.length > 0 ? (
                  ratings.data.map((rating, index) => {
                    const isVerified = Boolean(rating.dateVerified);
                    const isWaitingVerification = rating.lock === 2;
                    const isOngoingSelfRating = rating.lock === 1;

                    return (
                      <tr key={rating.id} className="hover:bg-muted/30 transition-colors">
                        <td className="px-3 py-2 text-center font-mono text-[11px] text-muted-foreground border-r border-border">
                          {(ratings.from ?? 1) + index}
                        </td>
                        <td className="px-3 py-2 font-bold text-foreground border-r border-border">
                          {rating.year}
                        </td>
                        <td className="px-3 py-2 font-medium text-foreground border-r border-border">
                          {rating.semester === 1 ? '1st Semester' : rating.semester === 2 ? '2nd Semester' : rating.semester}
                        </td>
                        <td className="px-3 py-2 font-mono font-bold text-foreground border-r border-border">
                          {rating.finalRating || '0.00000'}
                        </td>
                        <td className="px-3 py-2 font-semibold text-emerald-600 dark:text-emerald-400 border-r border-border">
                          {rating.adjectivalRating || '-'}
                        </td>
                        <td className="px-3 py-2 text-center border-r border-border">
                          {isVerified ? (
                            <span className="inline-flex items-center gap-1 text-[11px] font-semibold text-emerald-700 dark:text-emerald-300 bg-emerald-500/10 px-2 py-0.5 rounded-full border border-emerald-500/20">
                              <ShieldCheck className="size-3" />
                              <span>{formatDate(rating.dateVerified)}</span>
                            </span>
                          ) : isWaitingVerification ? (
                            <span className="inline-flex items-center gap-1 text-[11px] font-semibold text-amber-700 dark:text-amber-300 bg-amber-500/10 px-2 py-0.5 rounded-full border border-amber-500/20">
                              <Clock className="size-3" />
                              <span>Waiting for Verification</span>
                            </span>
                          ) : isOngoingSelfRating ? (
                            <span className="inline-flex items-center gap-1 text-[11px] font-semibold text-sky-700 dark:text-sky-300 bg-sky-500/10 px-2 py-0.5 rounded-full border border-sky-500/20">
                              <Edit3 className="size-3" />
                              <span>On-going Self Rating</span>
                            </span>
                          ) : (
                            <span className="inline-flex items-center gap-1 text-[11px] font-semibold text-emerald-700 dark:text-emerald-300 bg-emerald-500/10 px-2 py-0.5 rounded-full border border-emerald-500/20">
                              <Flag className="size-3" />
                              <span>On-going Checkpoint</span>
                            </span>
                          )}
                        </td>
                        <td className="px-3 py-2 text-muted-foreground font-mono text-[11px] border-r border-border">
                          {formatDate(rating.dateCreated)}
                        </td>
                        <td className="px-3 py-2 text-left">
                          <div className="inline-flex items-center gap-1">
                            <Link
                              href={`/ipcrf/myratings/${rating.id}/sem-target`}
                              title="View Semestral Target"
                              className="p-1 rounded-md bg-emerald-600 text-white hover:bg-emerald-700 transition"
                            >
                              <Eye className="size-3.5" />
                            </Link>

                            {!isWaitingVerification && !isVerified ? (
                              <button
                                type="button"
                                title="Remove"
                                onClick={() => confirmDelete(rating.id)}
                                className="p-1 rounded-md bg-rose-600 text-white hover:bg-rose-700 transition cursor-pointer"
                              >
                                <Trash2 className="size-3.5" />
                              </button>
                            ) : null}
                          </div>
                        </td>
                      </tr>
                    );
                  })
                ) : (
                  <tr>
                    <td colSpan={8} className="px-4 py-12 text-center">
                      <div className="flex flex-col items-center justify-center space-y-2">
                        <div className="size-10 rounded-full bg-muted flex items-center justify-center text-muted-foreground">
                          <ShieldCheck className="size-5" />
                        </div>
                        <p className="text-xs font-bold text-foreground">No ratings found</p>
                        <p className="text-[11px] text-muted-foreground max-w-sm">
                          No performance commitment records matched your query.
                        </p>
                      </div>
                    </td>
                  </tr>
                )}
              </tbody>
            </table>
          </div>

          {/* PAGINATION FOOTER */}
          <div className="border-t border-border px-3.5 py-2.5 flex flex-col sm:flex-row sm:items-center justify-between gap-2.5 bg-muted/20">
            <div className="text-[11px] text-muted-foreground">
              Showing <span className="font-bold text-foreground">{ratings.from ?? 0}</span> to{' '}
              <span className="font-bold text-foreground">{ratings.to ?? 0}</span> of{' '}
              <span className="font-bold text-foreground">{ratings.total}</span> rating periods
            </div>

            {ratings.links && ratings.links.length > 3 && (
              <div className="flex items-center gap-1 flex-wrap">
                {ratings.links.map((link, idx) => {
                  if (!link.url) {
                    return (
                      <span
                        key={idx}
                        className="h-7 min-w-7 px-2 rounded-md flex items-center justify-center text-[11px] text-muted-foreground/50 border border-transparent select-none"
                        dangerouslySetInnerHTML={{ __html: link.label }}
                      />
                    );
                  }

                  let targetPage = ratings.current_page;
                  if (link.url) {
                    try {
                      const urlObj = new URL(link.url, window.location.origin);
                      targetPage = Number(urlObj.searchParams.get('page')) || ratings.current_page;
                    } catch {
                      targetPage = ratings.current_page;
                    }
                  }

                  return (
                    <button
                      key={idx}
                      type="button"
                      onClick={() => applyFilters({ page: targetPage })}
                      className={`h-7 min-w-7 px-2 rounded-md flex items-center justify-center text-[11px] font-medium transition-colors cursor-pointer ${
                        link.active
                          ? 'bg-emerald-600 text-white font-bold shadow-2xs'
                          : 'border border-input bg-background text-foreground hover:bg-muted'
                      }`}
                      dangerouslySetInnerHTML={{ __html: link.label }}
                    />
                  );
                })}
              </div>
            )}
          </div>
        </div>

        {/* Delete Confirmation Modal */}
        {deletingId !== null && (
          <div className="fixed inset-0 z-50 flex items-center justify-center bg-black/60 backdrop-blur-xs p-4 animate-in fade-in duration-150">
            <div className="w-full max-w-md rounded-xl border border-border bg-card p-5 shadow-2xl space-y-4">
              <div className="flex items-start justify-between">
                <div className="flex items-center gap-3">
                  <div className="p-2 rounded-lg bg-rose-500/10 text-rose-600">
                    <AlertTriangle className="size-5" />
                  </div>
                  <div>
                    <h3 className="text-sm font-bold text-foreground">Remove Rating Record</h3>
                    <p className="text-xs text-muted-foreground mt-0.5">
                      Are you sure you want to remove this rating period? This action cannot be undone.
                    </p>
                  </div>
                </div>
                <button
                  type="button"
                  onClick={cancelDelete}
                  className="text-muted-foreground hover:text-foreground p-1 rounded-md transition"
                >
                  <X className="size-4" />
                </button>
              </div>

              <div className="flex items-center justify-end gap-2 pt-2 border-t border-border">
                <button
                  type="button"
                  onClick={cancelDelete}
                  disabled={isDeleting}
                  className="px-3 py-1.5 rounded-lg border border-input bg-background text-xs font-semibold text-foreground hover:bg-muted transition"
                >
                  Cancel
                </button>
                <button
                  type="button"
                  onClick={handleDelete}
                  disabled={isDeleting}
                  className="px-3.5 py-1.5 rounded-lg bg-rose-600 hover:bg-rose-700 text-white text-xs font-semibold shadow-xs transition flex items-center gap-2"
                >
                  {isDeleting ? 'Removing...' : 'Confirm & Remove'}
                </button>
              </div>
            </div>
          </div>
        )}
      </div>
    </AppLayout>
  );
}

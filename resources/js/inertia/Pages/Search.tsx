import React, { useMemo, useRef } from 'react';
import { Head, Link, router, useForm } from '@inertiajs/react';
import AppLayout from '../Layouts/AppLayout';
import UserAvatar from '../Components/UserAvatar';
import {
  Search as SearchIcon,
  RotateCcw,
  Users as UsersIcon,
  Building2,
  Layers,
  ChevronLeft,
  ChevronRight,
  Briefcase,
  SlidersHorizontal,
  X,
  Calendar,
  FileCheck,
} from 'lucide-react';
import { readPersistedFilters, savePersistedFilters } from '../lib/filterPersistence';

type UserRow = {
  id: number;
  userId: number;
  lastName?: string | null;
  firstName?: string | null;
  middleName?: string | null;
  extensionName?: string | null;
  fullName: string;
  email: string | null;
  contactNumber: string | null;
  position: string | null;
  designation: string | null;
  divisionName: string | null;
  sectionName: string | null;
  avatar: string | null;
  avatarUrl: string | null;
  avatar_url?: string | null;
  semesterId: number | null;
  year: string | null;
  semester: number | null;
  finalRating: string | null;
  adjectivalRating: string | null;
  overallRemarks: string | null;
  lock: number | null;
  isReady: number | null;
};

type Props = {
  appName: string;
  user: { name: string; email: string } | null;
  filters: {
    search: string;
    division: string;
    section: string;
    year: string;
    semester: string;
    perPage: number;
  };
  users: {
    data: UserRow[];
    current_page: number;
    last_page: number;
    from: number | null;
    to: number | null;
    total: number;
    per_page: number;
    links: Array<{ url: string | null; label: string; active: boolean }>;
  };
  divisions: Array<{ id: number; division_name: string }>;
  sections: Array<{ id: number; section_name: string; division_id?: number | string }>;
  years: Array<{ value: string; label: string }>;
  semesters: Array<{ value: string; label: string }>;
  navigation?: { sidebar?: any[] };
};

function formatFullName(user: UserRow): string {
  if (user.fullName) return user.fullName.toUpperCase();
  return [
    user.lastName,
    user.firstName,
    user.middleName,
    user.extensionName,
  ]
    .filter(Boolean)
    .join(' ')
    .toUpperCase();
}

function getUserInitials(user: UserRow): string {
  const first = (user.firstName || user.fullName || '').charAt(0);
  const last = (user.lastName || '').charAt(0);
  return (first + last).toUpperCase() || 'U';
}

function maskFinalRating(rating: string | number | null): string {
  if (rating === null || rating === undefined || rating === '') return '-';
  const str = String(rating).trim();
  const parts = str.split('.');
  if (parts.length > 1) {
    const decimalLength = parts[1].length;
    return `${parts[0]}.${'*'.repeat(decimalLength > 0 ? decimalLength : 5)}`;
  }
  return `${str}.*****`;
}

function getAdjectivalBadge(rating: string | null) {
  if (!rating) return <span className="text-muted-foreground/60">—</span>;
  const r = rating.toUpperCase();
  if (r.includes('OUTSTANDING')) {
    return <span className="rounded-full bg-emerald-500/10 text-emerald-700 dark:text-emerald-400 font-bold text-[10px] px-2 py-0.5 border border-emerald-500/20">{rating}</span>;
  }
  if (r.includes('VERY SATISFACTORY')) {
    return <span className="rounded-full bg-blue-500/10 text-blue-700 dark:text-blue-400 font-bold text-[10px] px-2 py-0.5 border border-blue-500/20">{rating}</span>;
  }
  if (r.includes('SATISFACTORY')) {
    return <span className="rounded-full bg-amber-500/10 text-amber-700 dark:text-amber-400 font-bold text-[10px] px-2 py-0.5 border border-amber-500/20">{rating}</span>;
  }
  if (r.includes('UNSATISFACTORY') || r.includes('POOR')) {
    return <span className="rounded-full bg-rose-500/10 text-rose-700 dark:text-rose-400 font-bold text-[10px] px-2 py-0.5 border border-rose-500/20">{rating}</span>;
  }
  return <span className="rounded-full bg-muted text-muted-foreground font-semibold text-[10px] px-2 py-0.5 border border-border">{rating}</span>;
}

export default function Search({
  appName,
  user,
  filters,
  users,
  divisions = [],
  sections = [],
  years = [],
  semesters = [],
  navigation,
}: Props) {
  const pageKey = 'search';
  const persisted = readPersistedFilters(pageKey, user, {
    search: filters?.search || '',
    division: filters?.division || '',
    section: filters?.section || '',
    year: filters?.year || '',
    semester: filters?.semester || '',
    perPage: String(filters?.perPage || 10),
  });
  const form = useForm({
    search: persisted.search,
    division: persisted.division,
    section: persisted.section,
    year: persisted.year,
    semester: persisted.semester,
    perPage: persisted.perPage,
  });

  const searchTimerRef = useRef<NodeJS.Timeout | null>(null);

  // Filter sections based on selected division
  const availableSections = useMemo(() => {
    if (!form.data.division) {
      return sections;
    }
    return sections.filter((s) => String(s.division_id) === String(form.data.division));
  }, [sections, form.data.division]);

  const navigateSearch = (overrides: Record<string, any> = {}) => {
    const payload = {
      search: form.data.search,
      division: form.data.division,
      section: form.data.section,
      year: form.data.year,
      semester: form.data.semester,
      perPage: form.data.perPage,
      page: 1,
      ...overrides,
    };

    router.post('/search', payload, {
      preserveState: true,
      replace: true,
      preserveScroll: true,
    });
    savePersistedFilters(pageKey, user, payload);
  };

  const handleSearchChange = (val: string) => {
    form.setData('search', val);
    savePersistedFilters(pageKey, user, { ...form.data, search: val });
    if (searchTimerRef.current) clearTimeout(searchTimerRef.current);
    searchTimerRef.current = setTimeout(() => {
      navigateSearch({ search: val, page: 1 });
    }, 350);
  };

  const handleDivisionChange = (divisionId: string) => {
    const isSectionValid =
      divisionId === '' ||
      sections.some(
        (s) => String(s.division_id) === String(divisionId) && String(s.id) === String(form.data.section)
      );
    const newSection = isSectionValid ? form.data.section : '';

    form.setData((prev) => ({
      ...prev,
      division: divisionId,
      section: newSection,
    }));
    savePersistedFilters(pageKey, user, { ...form.data, division: divisionId, section: newSection });
    navigateSearch({ division: divisionId, section: newSection, page: 1 });
  };

  const handleSectionChange = (sectionId: string) => {
    form.setData('section', sectionId);
    savePersistedFilters(pageKey, user, { ...form.data, section: sectionId });
    navigateSearch({ section: sectionId, page: 1 });
  };

  const handleYearChange = (year: string) => {
    form.setData('year', year);
    savePersistedFilters(pageKey, user, { ...form.data, year });
    navigateSearch({ year, page: 1 });
  };

  const handleSemesterChange = (semester: string) => {
    form.setData('semester', semester);
    savePersistedFilters(pageKey, user, { ...form.data, semester });
    navigateSearch({ semester, page: 1 });
  };

  const applyFilters = (e?: React.FormEvent) => {
    if (e) e.preventDefault();
    if (searchTimerRef.current) clearTimeout(searchTimerRef.current);
    navigateSearch({ page: 1 });
  };

  const handlePerPageChange = (val: string) => {
    form.setData('perPage', val);
    savePersistedFilters(pageKey, user, { ...form.data, perPage: val });
    navigateSearch({ perPage: val, page: 1 });
  };

  const resetFilters = () => {
    if (searchTimerRef.current) clearTimeout(searchTimerRef.current);
    form.setData({
      search: '',
      division: '',
      section: '',
      year: '',
      semester: '',
      perPage: '10',
    });
    router.post(
      '/search',
      {
        search: '',
        division: '',
        section: '',
        year: '',
        semester: '',
        perPage: 10,
        page: 1,
      },
      { replace: true, preserveState: true }
    );
    savePersistedFilters(pageKey, user, {
      search: '',
      division: '',
      section: '',
      year: '',
      semester: '',
      perPage: '10',
    });
  };

  return (
    <AppLayout appName={appName} user={user} sidebar={navigation?.sidebar ?? []}>
      <Head title="Search Users - Directory" />

      <div className="space-y-3">
        {/* TOP FILTER & SEARCH CARD */}
        <div className="rounded-xl border border-border bg-card p-3 sm:p-4 shadow-2xs">
          {/* HEADER */}
          <div className="flex flex-col sm:flex-row sm:items-center justify-between gap-2 border-b border-border/80 pb-3 mb-3">
            <div className="flex items-center gap-2.5">
              <div className="size-8 rounded-lg bg-emerald-500/10 text-emerald-700 dark:text-emerald-400 flex items-center justify-center font-bold">
                <UsersIcon className="size-4.5" />
              </div>
              <div>
                <h1 className="text-sm font-bold tracking-tight text-foreground flex items-center gap-2">
                  <span>User Directory &amp; Staff Search</span>
                  <span className="rounded-full bg-emerald-500/10 text-emerald-700 dark:text-emerald-400 font-mono text-[10px] font-bold px-2 py-0.2 border border-emerald-500/20">
                    {users?.total || 0} Total Records
                  </span>
                </h1>
                <p className="text-[11px] text-muted-foreground">
                  Find personnel by full name, assigned operational division, or specific section.
                </p>
              </div>
            </div>

            {/* QUICK ACTIONS */}
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

          {/* FILTER CONTROLS FORM */}
          <form onSubmit={applyFilters} className="grid gap-2.5 sm:grid-cols-2 lg:grid-cols-6">
            {/* Search Input */}
            <div className="space-y-1">
              <label className="text-[11px] font-semibold text-muted-foreground">Search by Name / Rating</label>
              <div className="relative">
                <SearchIcon className="size-3.5 absolute left-2.5 top-1/2 -translate-y-1/2 text-muted-foreground" />
                <input
                  type="text"
                  value={form.data.search}
                  onChange={(e) => handleSearchChange(e.target.value)}
                  placeholder="Type name, year, or rating..."
                  className="h-8 w-full rounded-lg border border-input bg-background pl-8 pr-7 text-xs text-foreground placeholder:text-muted-foreground/60 outline-hidden focus:ring-2 focus:ring-ring"
                />
                {form.data.search && (
                  <button
                    type="button"
                    onClick={() => {
                      form.setData('search', '');
                      if (searchTimerRef.current) clearTimeout(searchTimerRef.current);
                      navigateSearch({ search: '', page: 1 });
                    }}
                    className="absolute right-2 top-1/2 -translate-y-1/2 text-muted-foreground hover:text-foreground"
                  >
                    <X className="size-3" />
                  </button>
                )}
              </div>
            </div>

            {/* Division Selector */}
            <div className="space-y-1">
              <label className="text-[11px] font-semibold text-muted-foreground flex items-center gap-1">
                <Building2 className="size-3 text-muted-foreground" />
                <span>Division</span>
              </label>
              <select
                value={form.data.division}
                onChange={(e) => handleDivisionChange(e.target.value)}
                className="h-8 w-full rounded-lg border border-input bg-background px-2.5 text-xs text-foreground outline-hidden focus:ring-2 focus:ring-ring cursor-pointer"
              >
                <option value="">All Divisions ({divisions.length})</option>
                {divisions.map((division) => (
                  <option key={division.id} value={division.id}>
                    {division.division_name}
                  </option>
                ))}
              </select>
            </div>

            {/* Section Selector */}
            <div className="space-y-1">
              <label className="text-[11px] font-semibold text-muted-foreground flex items-center gap-1">
                <Layers className="size-3 text-muted-foreground" />
                <span>Section</span>
              </label>
              <select
                value={form.data.section}
                onChange={(e) => handleSectionChange(e.target.value)}
                className="h-8 w-full rounded-lg border border-input bg-background px-2.5 text-xs text-foreground outline-hidden focus:ring-2 focus:ring-ring cursor-pointer"
              >
                <option value="">
                  {form.data.division
                    ? `All Sections under Division (${availableSections.length})`
                    : `All Sections (${availableSections.length})`}
                </option>
                {availableSections.map((section) => (
                  <option key={section.id} value={section.id}>
                    {section.section_name}
                  </option>
                ))}
              </select>
            </div>

            {/* Year Selector */}
            <div className="space-y-1">
              <label className="text-[11px] font-semibold text-muted-foreground flex items-center gap-1">
                <Calendar className="size-3 text-muted-foreground" />
                <span>Target Year</span>
              </label>
              <select
                value={form.data.year}
                onChange={(e) => handleYearChange(e.target.value)}
                className="h-8 w-full rounded-lg border border-input bg-background px-2.5 text-xs text-foreground outline-hidden focus:ring-2 focus:ring-ring cursor-pointer"
              >
                <option value="">All Years</option>
                {years.map((y) => (
                  <option key={y.value} value={y.value}>
                    {y.label}
                  </option>
                ))}
              </select>
            </div>

            {/* Semester Selector */}
            <div className="space-y-1">
              <label className="text-[11px] font-semibold text-muted-foreground flex items-center gap-1">
                <FileCheck className="size-3 text-muted-foreground" />
                <span>Semester</span>
              </label>
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
              <label className="text-[11px] font-semibold text-muted-foreground flex items-center gap-1">
                <SlidersHorizontal className="size-3 text-muted-foreground" />
                <span>Per Page</span>
              </label>
              <select
                value={form.data.perPage}
                onChange={(e) => handlePerPageChange(e.target.value)}
                className="h-8 w-full rounded-lg border border-input bg-background px-2.5 text-xs text-foreground outline-hidden focus:ring-2 focus:ring-ring cursor-pointer"
              >
                <option value="10">10 records per page</option>
                <option value="25">25 records per page</option>
                <option value="50">50 records per page</option>
                <option value="100">100 records per page</option>
              </select>
            </div>
          </form>
        </div>

        {/* RESULTS TABLE CARD */}
        <div className="rounded-xl border border-border bg-card shadow-2xs overflow-hidden">
          <div className="overflow-x-auto">
            <table className="w-full min-w-[700px] border-collapse text-xs text-left">
              <thead>
                <tr className="bg-muted/60 text-[11px] font-bold uppercase tracking-wider text-muted-foreground border-b border-border">
                  <th className="px-3 py-2 text-center w-12 border-r border-border">#</th>
                  <th className="px-3 py-2 border-r border-border">Employee / Full Name</th>
                  <th className="px-3 py-2 border-r border-border">Division / Position</th>
                  <th className="px-3 py-2 border-r border-border">Section / Designation</th>
                  <th className="px-3 py-2 border-r border-border text-center">Year</th>
                  <th className="px-3 py-2 border-r border-border text-center">Semester</th>
                  <th className="px-3 py-2 border-r border-border text-center">Final Rating</th>
                  <th className="px-3 py-2 text-center">Adjectival Rating</th>
                </tr>
              </thead>
              <tbody className="divide-y divide-border">
                {users?.data && users.data.length > 0 ? (
                  users.data.map((row, index) => (
                    <tr key={`${row.userId}-${row.semesterId || index}`} className="hover:bg-muted/30 transition-colors">
                      {/* Index */}
                      <td className="px-3 py-2 text-center font-mono text-[11px] text-muted-foreground border-r border-border">
                        {(users.from ?? 1) + index}
                      </td>

                      {/* Full Name with Avatar */}
                      <td className="px-3 py-2 border-r border-border">
                        <div className="flex items-center gap-2">
                          <UserAvatar
                            user={{
                              name: formatFullName(row),
                              avatar_url: row.avatarUrl || (row as any).avatar_url,
                              avatar: row.avatar,
                            }}
                            size="sm"
                            fallbackInitials={getUserInitials(row)}
                          />
                          <span className="font-bold text-foreground leading-tight">
                            {formatFullName(row)}
                          </span>
                        </div>
                      </td>

                      {/* Division & Position */}
                      <td className="px-3 py-2 border-r border-border">
                        <div className="space-y-0.5">
                          {row.divisionName ? (
                            <span className="inline-flex items-center px-1.5 py-0.5 rounded bg-muted text-[11px] font-semibold text-foreground">
                              {row.divisionName}
                            </span>
                          ) : (
                            <span className="text-muted-foreground/60 text-xs">-</span>
                          )}
                          {row.position && (
                            <div className="text-[10px] text-muted-foreground font-medium flex items-center gap-1">
                              <Briefcase className="size-2.5 text-muted-foreground/60 shrink-0" />
                              <span className="truncate">{row.position}</span>
                            </div>
                          )}
                        </div>
                      </td>

                      {/* Section & Designation */}
                      <td className="px-3 py-2 border-r border-border">
                        <div className="space-y-0.5">
                          {row.sectionName ? (
                            <span className="text-foreground font-medium text-xs block">
                              {row.sectionName}
                            </span>
                          ) : (
                            <span className="text-muted-foreground/60 text-xs block">-</span>
                          )}
                          {row.designation && (
                            <div className="text-[10px] text-muted-foreground font-medium truncate">
                              {row.designation}
                            </div>
                          )}
                        </div>
                      </td>

                      {/* Year */}
                      <td className="px-3 py-2 border-r border-border text-center font-mono font-semibold text-xs">
                        {row.year ? (
                          <span className="rounded bg-muted px-1.5 py-0.5">{row.year}</span>
                        ) : (
                          <span className="text-muted-foreground/60">-</span>
                        )}
                      </td>

                      {/* Semester */}
                      <td className="px-3 py-2 border-r border-border text-center text-xs">
                        {row.semester ? (
                          <span className="font-medium text-foreground">
                            {row.semester === 1 ? '1st Sem' : row.semester === 2 ? '2nd Sem' : row.semester}
                          </span>
                        ) : (
                          <span className="text-muted-foreground/60">-</span>
                        )}
                      </td>

                      {/* Final Rating */}
                      <td className="px-3 py-2 border-r border-border text-center font-mono font-bold text-xs text-foreground">
                        {row.finalRating ? (
                          row.semesterId ? (
                            <Link
                              href={`/verification/${row.semesterId}/semestral-verification`}
                              className="text-emerald-700 dark:text-emerald-400 hover:underline"
                            >
                              {maskFinalRating(row.finalRating)}
                            </Link>
                          ) : (
                            maskFinalRating(row.finalRating)
                          )
                        ) : (
                          <span className="text-muted-foreground/60">-</span>
                        )}
                      </td>

                      {/* Adjectival Rating */}
                      <td className="px-3 py-2 text-center whitespace-nowrap">
                        {getAdjectivalBadge(row.adjectivalRating)}
                      </td>
                    </tr>
                  ))
                ) : (
                  <tr>
                    <td colSpan={8} className="px-4 py-12 text-center">
                      <div className="flex flex-col items-center justify-center space-y-2">
                        <div className="size-10 rounded-full bg-muted flex items-center justify-center text-muted-foreground">
                          <UsersIcon className="size-5" />
                        </div>
                        <p className="text-xs font-bold text-foreground">No users found</p>
                        <p className="text-[11px] text-muted-foreground max-w-sm">
                          No personnel matched your current search filters. Try adjusting your query or resetting filters.
                        </p>
                        <button
                          type="button"
                          onClick={resetFilters}
                          className="mt-2 inline-flex items-center gap-1 text-xs font-semibold text-emerald-600 dark:text-emerald-400 hover:underline"
                        >
                          <RotateCcw className="size-3" />
                          <span>Reset all filters</span>
                        </button>
                      </div>
                    </td>
                  </tr>
                )}
              </tbody>
            </table>
          </div>

          {/* PAGINATION FOOTER */}
          <div className="border-t border-border px-3.5 py-2.5 flex flex-col sm:flex-row sm:items-center justify-between gap-2.5 bg-muted/20">
            {/* Range info */}
            <div className="text-[11px] text-muted-foreground">
              Showing <span className="font-bold text-foreground">{users.from || 0}</span> to{' '}
              <span className="font-bold text-foreground">{users.to || 0}</span> of{' '}
              <span className="font-bold text-foreground">{users.total || 0}</span> staff members
            </div>

            {/* Pagination Controls */}
            {users.links && users.links.length > 3 && (
              <div className="flex items-center gap-1 flex-wrap">
                {users.links.map((link, idx) => {
                  const isPrevious = idx === 0;
                  const isNext = idx === users.links.length - 1;

                  if (!link.url) {
                    return (
                      <span
                        key={idx}
                        className="h-7 min-w-7 px-2 rounded-md flex items-center justify-center text-[11px] text-muted-foreground/50 border border-transparent select-none"
                      >
                        {isPrevious ? <ChevronLeft className="size-3.5" /> : isNext ? <ChevronRight className="size-3.5" /> : link.label.replace('&laquo;', '').replace('&raquo;', '')}
                      </span>
                    );
                  }

                  // Extract target page number
                  let targetPage = users.current_page;
                  if (link.url) {
                    try {
                      const urlObj = new URL(link.url, window.location.origin);
                      targetPage = Number(urlObj.searchParams.get('page')) || users.current_page;
                    } catch {
                      if (isPrevious) targetPage = Math.max(1, users.current_page - 1);
                      else if (isNext) targetPage = Math.min(users.last_page, users.current_page + 1);
                      else targetPage = Number(link.label) || users.current_page;
                    }
                  }

                  return (
                    <button
                      key={idx}
                      type="button"
                      data-pagination-number={/^\d+$/.test(link.label) ? targetPage : undefined}
                      onClick={() => navigateSearch({ page: targetPage })}
                      className={`h-7 min-w-7 px-2 rounded-md flex items-center justify-center text-[11px] font-medium transition-colors cursor-pointer ${
                        link.active
                          ? 'bg-emerald-600 text-white font-bold shadow-2xs'
                          : 'border border-input bg-background text-foreground hover:bg-muted'
                      }`}
                    >
                      {isPrevious ? (
                        <ChevronLeft className="size-3.5" />
                      ) : isNext ? (
                        <ChevronRight className="size-3.5" />
                      ) : (
                        link.label.replace('&laquo;', '').replace('&raquo;', '')
                      )}
                    </button>
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

import React, { useMemo } from 'react';
import { Head, Link, router, useForm } from '@inertiajs/react';
import AppLayout from '../Layouts/AppLayout';
import {
  Search as SearchIcon,
  RotateCcw,
  Users as UsersIcon,
  Building2,
  Layers,
  ChevronLeft,
  ChevronRight,
  Mail,
  Phone,
  Briefcase,
  SlidersHorizontal,
  X,
} from 'lucide-react';

type UserRow = {
  id: number;
  last_name: string | null;
  first_name: string | null;
  middle_name: string | null;
  extension_name: string | null;
  email: string | null;
  contact_number: string | null;
  position: string | null;
  division_name: string | null;
  section_name: string | null;
};

type Props = {
  appName: string;
  user: { name: string; email: string } | null;
  filters: {
    search: string;
    division: string;
    section: string;
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
  navigation?: { sidebar?: any[] };
};

function formatFullName(user: UserRow): string {
  return [
    user.last_name,
    user.first_name,
    user.middle_name,
    user.extension_name,
  ]
    .filter(Boolean)
    .join(' ')
    .toUpperCase();
}

function getUserInitials(user: UserRow): string {
  const first = (user.first_name || '').charAt(0);
  const last = (user.last_name || '').charAt(0);
  return (first + last).toUpperCase() || 'U';
}

export default function Search({
  appName,
  user,
  filters,
  users,
  divisions,
  sections,
  navigation,
}: Props) {
  const form = useForm({
    search: filters.search || '',
    division: filters.division || '',
    section: filters.section || '',
    perPage: String(filters.perPage || 10),
  });

  // Filter sections based on selected division
  const availableSections = useMemo(() => {
    if (!form.data.division) {
      return sections;
    }
    return sections.filter((s) => String(s.division_id) === String(form.data.division));
  }, [sections, form.data.division]);

  const handleDivisionChange = (divisionId: string) => {
    form.setData((prev) => {
      const isSectionValid =
        divisionId === '' ||
        sections.some(
          (s) => String(s.division_id) === String(divisionId) && String(s.id) === String(prev.section)
        );

      return {
        ...prev,
        division: divisionId,
        section: isSectionValid ? prev.section : '',
      };
    });
  };

  const applyFilters = (e?: React.FormEvent) => {
    if (e) e.preventDefault();
    router.get('/search', { ...form.data, page: 1 }, { preserveState: true, replace: true });
  };

  const handlePerPageChange = (val: string) => {
    form.setData('perPage', val);
    router.get(
      '/search',
      {
        search: form.data.search,
        division: form.data.division,
        section: form.data.section,
        perPage: val,
        page: 1,
      },
      { preserveState: true, replace: true }
    );
  };

  const resetFilters = () => {
    form.setData({
      search: '',
      division: '',
      section: '',
      perPage: '10',
    });
    router.get('/search', { search: '', division: '', section: '', perPage: 10 }, { replace: true });
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
                    {users.total} Total Users
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
              <button
                type="button"
                onClick={() => applyFilters()}
                className="h-8 inline-flex items-center gap-1.5 rounded-lg bg-emerald-600 text-white px-3 text-xs font-semibold hover:bg-emerald-700 transition shadow-xs cursor-pointer"
              >
                <SearchIcon className="size-3.5" />
                <span>Apply Filters</span>
              </button>
            </div>
          </div>

          {/* FILTER CONTROLS FORM */}
          <form onSubmit={applyFilters} className="grid gap-2.5 sm:grid-cols-2 lg:grid-cols-4">
            {/* Search Input */}
            <div className="space-y-1">
              <label className="text-[11px] font-semibold text-muted-foreground">Search by Name</label>
              <div className="relative">
                <SearchIcon className="size-3.5 absolute left-2.5 top-1/2 -translate-y-1/2 text-muted-foreground" />
                <input
                  type="text"
                  value={form.data.search}
                  onChange={(e) => form.setData('search', e.target.value)}
                  placeholder="Type full name or surname..."
                  className="h-8 w-full rounded-lg border border-input bg-background pl-8 pr-7 text-xs text-foreground placeholder:text-muted-foreground/60 outline-hidden focus:ring-2 focus:ring-ring"
                />
                {form.data.search && (
                  <button
                    type="button"
                    onClick={() => form.setData('search', '')}
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
                onChange={(e) => form.setData('section', e.target.value)}
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
            <table className="w-full min-w-[860px] border-collapse text-xs text-left">
              <thead>
                <tr className="bg-muted/60 text-[11px] font-bold uppercase tracking-wider text-muted-foreground border-b border-border">
                  <th className="px-3 py-2 text-center w-12 border-r border-border">#</th>
                  <th className="px-3 py-2 border-r border-border">Employee / Full Name</th>
                  <th className="px-3 py-2 border-r border-border">Division</th>
                  <th className="px-3 py-2 border-r border-border">Section</th>
                  <th className="px-3 py-2 border-r border-border">Position</th>
                  <th className="px-3 py-2 border-r border-border">Contact Number</th>
                  <th className="px-3 py-2">Email Address</th>
                </tr>
              </thead>
              <tbody className="divide-y divide-border">
                {users.data.length > 0 ? (
                  users.data.map((row, index) => (
                    <tr key={row.id} className="hover:bg-muted/30 transition-colors">
                      {/* Index */}
                      <td className="px-3 py-2 text-center font-mono text-[11px] text-muted-foreground border-r border-border">
                        {(users.from ?? 1) + index}
                      </td>

                      {/* Full Name with Avatar */}
                      <td className="px-3 py-2 border-r border-border">
                        <div className="flex items-center gap-2">
                          <div className="size-6.5 shrink-0 rounded-full bg-emerald-700/15 text-emerald-800 dark:text-emerald-300 font-bold text-[10px] flex items-center justify-center">
                            {getUserInitials(row)}
                          </div>
                          <span className="font-bold text-foreground leading-tight">
                            {formatFullName(row)}
                          </span>
                        </div>
                      </td>

                      {/* Division */}
                      <td className="px-3 py-2 border-r border-border">
                        {row.division_name ? (
                          <span className="inline-flex items-center px-1.5 py-0.5 rounded bg-muted text-[11px] font-medium text-foreground">
                            {row.division_name}
                          </span>
                        ) : (
                          <span className="text-muted-foreground/60">-</span>
                        )}
                      </td>

                      {/* Section */}
                      <td className="px-3 py-2 border-r border-border">
                        {row.section_name ? (
                          <span className="text-foreground font-medium text-xs">
                            {row.section_name}
                          </span>
                        ) : (
                          <span className="text-muted-foreground/60">-</span>
                        )}
                      </td>

                      {/* Position */}
                      <td className="px-3 py-2 text-muted-foreground border-r border-border font-medium">
                        {row.position ? (
                          <div className="flex items-center gap-1.5">
                            <Briefcase className="size-3 text-muted-foreground/70 shrink-0" />
                            <span className="truncate">{row.position}</span>
                          </div>
                        ) : (
                          <span className="text-muted-foreground/60">-</span>
                        )}
                      </td>

                      {/* Contact */}
                      <td className="px-3 py-2 text-muted-foreground border-r border-border font-mono text-[11px]">
                        {row.contact_number ? (
                          <a
                            href={`tel:${row.contact_number}`}
                            className="inline-flex items-center gap-1 hover:text-primary transition"
                          >
                            <Phone className="size-3 text-muted-foreground/60" />
                            <span>{row.contact_number}</span>
                          </a>
                        ) : (
                          <span className="text-muted-foreground/60">-</span>
                        )}
                      </td>

                      {/* Email */}
                      <td className="px-3 py-2 text-muted-foreground font-mono text-[11px]">
                        {row.email ? (
                          <a
                            href={`mailto:${row.email}`}
                            className="inline-flex items-center gap-1 hover:text-primary hover:underline transition"
                          >
                            <Mail className="size-3 text-muted-foreground/60" />
                            <span>{row.email}</span>
                          </a>
                        ) : (
                          <span className="text-muted-foreground/60">-</span>
                        )}
                      </td>
                    </tr>
                  ))
                ) : (
                  <tr>
                    <td colSpan={7} className="px-4 py-12 text-center">
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

                  return (
                    <Link
                      key={idx}
                      href={link.url}
                      preserveState
                      className={`h-7 min-w-7 px-2 rounded-md flex items-center justify-center text-[11px] font-medium transition-colors ${
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
                    </Link>
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


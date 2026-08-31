import { useState } from 'react';
import { Head, Link, router, useForm } from '@inertiajs/react';
import AppLayout from '../../Layouts/AppLayout';
import {
  Users,
  UserCheck,
  Search,
  X,
  RotateCcw,
  Mail,
  Phone,
  Shield,
  Eye,
  CheckCircle2,
  XCircle,
} from 'lucide-react';

type StaffRow = {
  id: number;
  lastName: string;
  firstName: string;
  middleName: string;
  extensionName: string;
  fullName: string;
  email: string;
  contactNumber?: string | null;
  position?: string | null;
  designation?: string | null;
  divisionId?: string | null;
  divisionName?: string | null;
  sectionId?: string | null;
  sectionName?: string | null;
  supervisorId?: string | null;
  userLevelId?: string | null;
  userLevelName?: string | null;
  isSupervisor: boolean;
  isStatus: number;
};

type Props = {
  appName: string;
  user: { name: string; email: string } | null;
  filters: {
    search: string;
    status: string;
    perPage: number;
  };
  staff: {
    data: StaffRow[];
    from: number | null;
    to: number | null;
    total: number;
    links: Array<{ url: string | null; label: string; active: boolean }>;
  };
  statusOptions: Array<{ value: string; label: string }>;
  perPageOptions: Array<{ value: number; label: string }>;
  navigation?: { sidebar?: any[] };
};

export default function MyStaff({
  appName,
  user,
  filters,
  staff,
  statusOptions,
  perPageOptions,
  navigation,
}: Props) {
  const filterForm = useForm({
    search: filters.search || '',
    status: filters.status || '',
    perPage: String(filters.perPage || 10),
  });

  const [viewingStaff, setViewingStaff] = useState<StaffRow | null>(null);

  const submitFilters = (overrides?: Partial<typeof filterForm.data>) => {
    const data = { ...filterForm.data, ...overrides };
    router.get('/inertia/settings/mystaff', data, {
      preserveState: true,
      replace: true,
    });
  };

  const resetFilters = () => {
    filterForm.setData({
      search: '',
      status: '',
      perPage: '10',
    });
    router.get('/inertia/settings/mystaff', {}, { replace: true });
  };

  const getInitials = (name?: string) => {
    if (!name) return 'U';
    const parts = name.trim().split(' ');
    if (parts.length === 1) return parts[0].substring(0, 2).toUpperCase();
    return (parts[0][0] + parts[parts.length - 1][0]).toUpperCase();
  };

  return (
    <AppLayout appName={appName} user={user} navigation={navigation}>
      <Head title="My Supervised Staff - 4Ps PATH" />

      <div className="space-y-4">
        {/* HEADER & FILTERS CARD */}
        <div className="rounded-xl border border-border bg-card p-3 sm:p-4 shadow-2xs space-y-3">
          <div className="flex flex-col sm:flex-row sm:items-center justify-between gap-3 border-b border-border/80 pb-3">
            {/* TITLE */}
            <div className="flex items-center gap-2.5">
              <div className="size-8 rounded-lg bg-emerald-500/10 text-emerald-700 dark:text-emerald-400 flex items-center justify-center font-bold">
                <UserCheck className="size-4.5" />
              </div>
              <div>
                <h1 className="text-sm font-bold tracking-tight text-foreground flex items-center gap-2">
                  <span>My Supervised Staff</span>
                  <span className="rounded-full bg-emerald-500/10 text-emerald-700 dark:text-emerald-400 font-mono text-[10px] font-bold px-2 py-0.2 border border-emerald-500/20">
                    {staff.total} Staff Member{staff.total !== 1 ? 's' : ''}
                  </span>
                </h1>
                <p className="text-[11px] text-muted-foreground">
                  View, filter, and monitor staff members assigned under your direct supervision.
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
          <div className="grid gap-2.5 sm:grid-cols-3 lg:grid-cols-4">
            {/* Search Input */}
            <div className="space-y-1 sm:col-span-2 lg:col-span-3">
              <label className="text-[11px] font-semibold text-muted-foreground">Search Staff</label>
              <div className="relative">
                <Search className="size-3.5 absolute left-2.5 top-1/2 -translate-y-1/2 text-muted-foreground" />
                <input
                  type="text"
                  value={filterForm.data.search}
                  onChange={(e) => filterForm.setData('search', e.target.value)}
                  onKeyDown={(e) => e.key === 'Enter' && submitFilters()}
                  placeholder="Search by full name, position, email, designation..."
                  className="h-8 w-full rounded-lg border border-input bg-background pl-8 pr-7 text-xs text-foreground placeholder:text-muted-foreground/60 outline-hidden focus:ring-2 focus:ring-ring"
                />
                {filterForm.data.search && (
                  <button
                    type="button"
                    onClick={() => {
                      filterForm.setData('search', '');
                      submitFilters({ search: '' });
                    }}
                    className="absolute right-2 top-1/2 -translate-y-1/2 text-muted-foreground hover:text-foreground cursor-pointer"
                  >
                    <X className="size-3" />
                  </button>
                )}
              </div>
            </div>

            {/* Status */}
            <div className="space-y-1">
              <label className="text-[11px] font-semibold text-muted-foreground">Status</label>
              <select
                value={filterForm.data.status}
                onChange={(e) => {
                  filterForm.setData('status', e.target.value);
                  submitFilters({ status: e.target.value });
                }}
                className="h-8 w-full rounded-lg border border-input bg-background px-2.5 text-xs text-foreground outline-hidden focus:ring-2 focus:ring-ring cursor-pointer"
              >
                {statusOptions.map((opt) => (
                  <option key={opt.value} value={opt.value}>
                    {opt.label}
                  </option>
                ))}
              </select>
            </div>
          </div>
        </div>

        {/* RESULTS TABLE CARD */}
        <div className="rounded-xl border border-border bg-card shadow-2xs overflow-hidden">
          <div className="overflow-x-auto">
            <table className="w-full min-w-[950px] border-collapse text-xs text-left">
              <thead>
                <tr className="bg-muted/60 text-[11px] font-bold uppercase tracking-wider text-muted-foreground border-b border-border">
                  <th className="px-3 py-2.5 text-center w-12 border-r border-border">#</th>
                  <th className="px-3 py-2.5 border-r border-border">Staff Member</th>
                  <th className="px-3 py-2.5 border-r border-border">Contact Information</th>
                  <th className="px-3 py-2.5 border-r border-border">Position & Designation</th>
                  <th className="px-3 py-2.5 border-r border-border">Division & Section</th>
                  <th className="px-3 py-2.5 border-r border-border text-center">User Level</th>
                  <th className="px-3 py-2.5 border-r border-border text-center w-24">Status</th>
                  <th className="px-3 py-2.5 text-center w-20">Action</th>
                </tr>
              </thead>
              <tbody className="divide-y divide-border">
                {staff.data.length === 0 ? (
                  <tr>
                    <td colSpan={8} className="px-4 py-12 text-center">
                      <div className="flex flex-col items-center justify-center space-y-2">
                        <div className="size-10 rounded-full bg-muted flex items-center justify-center text-muted-foreground">
                          <Users className="size-5" />
                        </div>
                        <p className="text-xs font-bold text-foreground">No supervised staff found</p>
                        <p className="text-[11px] text-muted-foreground max-w-sm">
                          {filterForm.data.search || filterForm.data.division || filterForm.data.section || filterForm.data.status
                            ? 'No staff members match your active filter criteria. Try resetting your filters.'
                            : 'You currently have no staff members assigned under your direct supervision.'}
                        </p>
                      </div>
                    </td>
                  </tr>
                ) : (
                  staff.data.map((row, idx) => (
                    <tr key={row.id} className="hover:bg-muted/30 transition-colors">
                      {/* # INDEX */}
                      <td className="px-3 py-2.5 text-center font-mono text-[11px] text-muted-foreground border-r border-border">
                        {(staff.from || 1) + idx}
                      </td>

                      {/* STAFF MEMBER */}
                      <td className="px-3 py-2.5 border-r border-border">
                        <div className="flex items-center gap-2.5">
                          <div className="size-7 shrink-0 rounded-full bg-emerald-700/15 text-emerald-800 dark:text-emerald-300 font-bold text-[10px] flex items-center justify-center border border-emerald-600/20">
                            {getInitials(row.fullName)}
                          </div>
                          <div>
                            <div className="font-semibold text-foreground text-xs leading-tight">
                              {row.fullName || 'Unnamed Staff'}
                            </div>
                            {row.isSupervisor && (
                              <span className="inline-flex items-center gap-0.5 text-[9px] font-bold text-emerald-700 dark:text-emerald-400 bg-emerald-500/10 px-1.5 py-0.2 rounded border border-emerald-500/20 mt-0.5">
                                <Shield className="size-2.5" />
                                Supervisor Privileges
                              </span>
                            )}
                          </div>
                        </div>
                      </td>

                      {/* CONTACT INFO */}
                      <td className="px-3 py-2.5 border-r border-border space-y-0.5">
                        <div className="flex items-center gap-1.5 text-muted-foreground">
                          <Mail className="size-3 shrink-0 text-muted-foreground/70" />
                          <a
                            href={`mailto:${row.email}`}
                            className="text-foreground hover:text-emerald-600 dark:hover:text-emerald-400 truncate hover:underline"
                          >
                            {row.email || '—'}
                          </a>
                        </div>
                        {row.contactNumber && (
                          <div className="flex items-center gap-1.5 text-[11px] text-muted-foreground">
                            <Phone className="size-3 shrink-0 text-muted-foreground/70" />
                            <span>{row.contactNumber}</span>
                          </div>
                        )}
                      </td>

                      {/* POSITION & DESIGNATION */}
                      <td className="px-3 py-2.5 border-r border-border">
                        <div className="font-medium text-foreground">{row.position || '—'}</div>
                        {row.designation && (
                          <div className="text-[11px] text-muted-foreground">{row.designation}</div>
                        )}
                      </td>

                      {/* DIVISION & SECTION */}
                      <td className="px-3 py-2.5 border-r border-border">
                        <div className="font-medium text-foreground">{row.divisionName || '—'}</div>
                        {row.sectionName && (
                          <div className="text-[11px] text-muted-foreground">{row.sectionName}</div>
                        )}
                      </td>

                      {/* USER LEVEL */}
                      <td className="px-3 py-2.5 border-r border-border text-center">
                        {row.userLevelName ? (
                          <span className="inline-block rounded-md bg-blue-500/10 text-blue-700 dark:text-blue-400 px-2 py-0.5 text-[10px] font-semibold border border-blue-500/20">
                            {row.userLevelName}
                          </span>
                        ) : (
                          <span className="text-muted-foreground text-[11px]">—</span>
                        )}
                      </td>

                      {/* STATUS */}
                      <td className="px-3 py-2.5 border-r border-border text-center">
                        {row.isStatus === 1 ? (
                          <span className="inline-flex items-center gap-1 rounded-full bg-emerald-500/10 text-emerald-700 dark:text-emerald-400 px-2 py-0.5 text-[10px] font-bold border border-emerald-500/20">
                            <span className="size-1.5 rounded-full bg-emerald-500" />
                            Active
                          </span>
                        ) : (
                          <span className="inline-flex items-center gap-1 rounded-full bg-rose-500/10 text-rose-700 dark:text-rose-400 px-2 py-0.5 text-[10px] font-bold border border-rose-500/20">
                            <span className="size-1.5 rounded-full bg-rose-500" />
                            Inactive
                          </span>
                        )}
                      </td>

                      {/* ACTION */}
                      <td className="px-3 py-2.5 text-center">
                        <button
                          type="button"
                          onClick={() => setViewingStaff(row)}
                          title="View Profile Details"
                          className="size-7 inline-flex items-center justify-center rounded-lg border border-input bg-background hover:bg-muted text-muted-foreground hover:text-foreground transition cursor-pointer"
                        >
                          <Eye className="size-3.5" />
                        </button>
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
                Showing <strong className="text-foreground">{staff.from ?? 0}</strong> to{' '}
                <strong className="text-foreground">{staff.to ?? 0}</strong> of{' '}
                <strong className="text-foreground">{staff.total}</strong> results
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
            {staff.links && staff.links.length > 3 && (
              <div className="flex items-center gap-1 flex-wrap">
                {staff.links.map((link, i) =>
                  link.url ? (
                    <Link
                      key={i}
                      href={link.url}
                      preserveScroll
                      preserveState
                      dangerouslySetInnerHTML={{ __html: link.label }}
                      className={`inline-flex min-w-7 h-7 items-center justify-center rounded-md px-2 text-xs font-semibold transition cursor-pointer ${
                        link.active
                          ? 'bg-emerald-600 text-white shadow-xs'
                          : 'border border-input bg-background text-foreground hover:bg-muted'
                      }`}
                    />
                  ) : (
                    <span
                      key={i}
                      dangerouslySetInnerHTML={{ __html: link.label }}
                      className="inline-flex min-w-7 h-7 items-center justify-center rounded-md px-2 text-xs text-muted-foreground/50 border border-transparent select-none"
                    />
                  )
                )}
              </div>
            )}
          </div>
        </div>
      </div>

      {/* STAFF PROFILE DETAILS MODAL */}
      {viewingStaff && (
        <div className="fixed inset-0 z-50 flex items-center justify-center bg-black/50 p-4 backdrop-blur-xs">
          <div className="w-full max-w-lg rounded-xl border border-border bg-card p-4 shadow-xl space-y-4 animate-in fade-in zoom-in-95">
            {/* MODAL HEADER */}
            <div className="flex items-center justify-between border-b border-border pb-3">
              <div className="flex items-center gap-2.5">
                <div className="size-8 rounded-full bg-emerald-500/10 text-emerald-700 dark:text-emerald-400 flex items-center justify-center font-bold text-xs">
                  {getInitials(viewingStaff.fullName)}
                </div>
                <div>
                  <h3 className="text-sm font-bold text-foreground">{viewingStaff.fullName}</h3>
                  <p className="text-[11px] text-muted-foreground">Staff Profile Details</p>
                </div>
              </div>
              <button
                type="button"
                onClick={() => setViewingStaff(null)}
                className="size-7 inline-flex items-center justify-center rounded-md hover:bg-muted text-muted-foreground hover:text-foreground transition cursor-pointer"
              >
                <X className="size-4" />
              </button>
            </div>

            {/* DETAILS GRID */}
            <div className="grid gap-2.5 sm:grid-cols-2 text-xs">
              <div className="space-y-0.5 rounded-lg border border-border bg-muted/20 p-2.5">
                <span className="text-[10px] font-bold uppercase tracking-wider text-muted-foreground">
                  Position
                </span>
                <p className="font-semibold text-foreground">{viewingStaff.position || '—'}</p>
              </div>

              <div className="space-y-0.5 rounded-lg border border-border bg-muted/20 p-2.5">
                <span className="text-[10px] font-bold uppercase tracking-wider text-muted-foreground">
                  Designation
                </span>
                <p className="font-semibold text-foreground">{viewingStaff.designation || '—'}</p>
              </div>

              <div className="space-y-0.5 rounded-lg border border-border bg-muted/20 p-2.5">
                <span className="text-[10px] font-bold uppercase tracking-wider text-muted-foreground">
                  Division
                </span>
                <p className="font-semibold text-foreground">{viewingStaff.divisionName || '—'}</p>
              </div>

              <div className="space-y-0.5 rounded-lg border border-border bg-muted/20 p-2.5">
                <span className="text-[10px] font-bold uppercase tracking-wider text-muted-foreground">
                  Section
                </span>
                <p className="font-semibold text-foreground">{viewingStaff.sectionName || '—'}</p>
              </div>

              <div className="space-y-0.5 rounded-lg border border-border bg-muted/20 p-2.5">
                <span className="text-[10px] font-bold uppercase tracking-wider text-muted-foreground">
                  Email Address
                </span>
                <p className="font-semibold text-foreground break-all">{viewingStaff.email || '—'}</p>
              </div>

              <div className="space-y-0.5 rounded-lg border border-border bg-muted/20 p-2.5">
                <span className="text-[10px] font-bold uppercase tracking-wider text-muted-foreground">
                  Contact Number
                </span>
                <p className="font-semibold text-foreground">{viewingStaff.contactNumber || '—'}</p>
              </div>

              <div className="space-y-0.5 rounded-lg border border-border bg-muted/20 p-2.5">
                <span className="text-[10px] font-bold uppercase tracking-wider text-muted-foreground">
                  User Level
                </span>
                <p className="font-semibold text-foreground">{viewingStaff.userLevelName || '—'}</p>
              </div>

              <div className="space-y-0.5 rounded-lg border border-border bg-muted/20 p-2.5">
                <span className="text-[10px] font-bold uppercase tracking-wider text-muted-foreground">
                  Account Status
                </span>
                <p className="font-semibold">
                  {viewingStaff.isStatus === 1 ? (
                    <span className="text-emerald-600 dark:text-emerald-400 font-bold inline-flex items-center gap-1">
                      <CheckCircle2 className="size-3.5" /> Active
                    </span>
                  ) : (
                    <span className="text-rose-600 dark:text-rose-400 font-bold inline-flex items-center gap-1">
                      <XCircle className="size-3.5" /> Inactive
                    </span>
                  )}
                </p>
              </div>

              <div className="sm:col-span-2 space-y-0.5 rounded-lg border border-border bg-muted/20 p-2.5">
                <span className="text-[10px] font-bold uppercase tracking-wider text-muted-foreground">
                  Supervisor Privileges
                </span>
                <p className="font-semibold text-foreground">
                  {viewingStaff.isSupervisor ? 'Yes (Can Supervise Staff)' : 'No (Standard Staff)'}
                </p>
              </div>
            </div>

            {/* MODAL FOOTER */}
            <div className="flex justify-end pt-2 border-t border-border">
              <button
                type="button"
                onClick={() => setViewingStaff(null)}
                className="px-3.5 py-1.5 rounded-lg bg-emerald-600 hover:bg-emerald-700 text-white text-xs font-semibold shadow-xs transition cursor-pointer"
              >
                Close
              </button>
            </div>
          </div>
        </div>
      )}
    </AppLayout>
  );
}

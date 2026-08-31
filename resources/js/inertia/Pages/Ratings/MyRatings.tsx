import { useState, useEffect } from 'react';
import { Head, Link, router, useForm } from '@inertiajs/react';
import { Eye, Trash2, RotateCcw, Search, ShieldCheck, Clock, Edit3, Flag, AlertTriangle, X } from 'lucide-react';
import AppLayout from '../../Layouts/AppLayout';

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
  const form = useForm({
    search: filters.search || '',
    year: filters.year || '',
    semester: filters.semester || '',
    perPage: String(filters.perPage || 10),
  });

  const [deletingId, setDeletingId] = useState<number | null>(null);
  const [isDeleting, setIsDeleting] = useState(false);

  const applyFilters = (overrides?: Partial<typeof form.data>) => {
    const data = { ...form.data, ...overrides };
    router.get('/inertia/ipcrf/myratings', data, {
      preserveState: true,
      replace: true,
    });
  };

  const handleSearchChange = (val: string) => {
    form.setData('search', val);
  };

  const handleSearchSubmit = (e: React.FormEvent) => {
    e.preventDefault();
    applyFilters();
  };

  const handleYearChange = (val: string) => {
    form.setData('year', val);
    applyFilters({ year: val });
  };

  const handleSemesterChange = (val: string) => {
    form.setData('semester', val);
    applyFilters({ semester: val });
  };

  const handlePerPageChange = (val: string) => {
    form.setData('perPage', val);
    applyFilters({ perPage: val });
  };

  const resetFilters = () => {
    form.setData({
      search: '',
      year: '',
      semester: '',
      perPage: '10',
    });
    router.get('/inertia/ipcrf/myratings', {}, { replace: true });
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
    router.delete(`/inertia/ipcrf/myratings/${deletingId}`, {
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
      <Head title="My Ratings" />

      <section className="w-full space-y-6">
        {/* Page Header */}
        <div className="flex flex-col gap-3 lg:flex-row lg:items-start lg:justify-between">
          <div className="space-y-1">
            <h1 className="text-xl font-bold tracking-tight text-slate-900 dark:text-slate-100">My Ratings</h1>
            <p className="text-sm text-slate-500 dark:text-slate-400">Review IPCRF semester ratings and performance entries.</p>
          </div>
        </div>

        {/* Container for User Profile Info */}
        <div className="rounded-2xl border border-slate-200 dark:border-slate-800 bg-white dark:bg-slate-900 p-4 shadow-sm">
          <div className="overflow-x-auto">
            <table className="w-full border-0 border-collapse">
              <tbody>
                <tr className="align-top">
                  <td className="pr-8 whitespace-nowrap">
                    <div className="text-[11px] leading-none text-slate-500 dark:text-slate-400">Full Name</div>
                    <div className="mt-1 text-sm font-semibold leading-tight text-slate-900 dark:text-slate-100 uppercase">
                      {profile?.fullName || '-'}
                    </div>
                  </td>
                  <td className="pr-8 whitespace-nowrap">
                    <div className="text-[11px] leading-none text-slate-500 dark:text-slate-400">Position</div>
                    <div className="mt-1 text-sm font-semibold leading-tight text-slate-900 dark:text-slate-100 uppercase">
                      {profile?.position || '-'}
                    </div>
                  </td>
                  <td className="pr-8 whitespace-nowrap">
                    <div className="text-[11px] leading-none text-slate-500 dark:text-slate-400">Designation</div>
                    <div className="mt-1 text-sm font-semibold leading-tight text-slate-900 dark:text-slate-100 uppercase">
                      {profile?.designation || '-'}
                    </div>
                  </td>
                  <td className="pr-8 whitespace-nowrap">
                    <div className="text-[11px] leading-none text-slate-500 dark:text-slate-400">Division Name</div>
                    <div className="mt-1 text-sm font-semibold leading-tight text-slate-900 dark:text-slate-100 uppercase">
                      {profile?.divisionName || '-'}
                    </div>
                  </td>
                  <td className="whitespace-nowrap">
                    <div className="text-[11px] leading-none text-slate-500 dark:text-slate-400">Section Name</div>
                    <div className="mt-1 text-sm font-semibold leading-tight text-slate-900 dark:text-slate-100 uppercase">
                      {profile?.sectionName || '-'}
                    </div>
                  </td>
                </tr>
              </tbody>
            </table>
          </div>
        </div>

        {/* Filters and Table Container */}
        <div className="rounded-2xl border border-slate-200 dark:border-slate-800 bg-white dark:bg-slate-900 p-4 shadow-sm space-y-4">
          {/* Filters Bar */}
          <form onSubmit={handleSearchSubmit} className="pb-4 border-b border-slate-200 dark:border-slate-800">
            <div className="flex flex-wrap items-end gap-3">
              {/* Search */}
              <div className="flex-1 min-w-[200px]">
                <label className="block text-xs font-semibold text-slate-600 dark:text-slate-400 mb-1">Search</label>
                <div className="relative">
                  <input
                    type="text"
                    value={form.data.search}
                    onChange={(e) => handleSearchChange(e.target.value)}
                    placeholder="Search ratings..."
                    className="w-full h-9 rounded-xl border border-slate-300 dark:border-slate-700 bg-white dark:bg-slate-800 pl-3 pr-8 text-xs text-slate-900 dark:text-slate-100 focus:outline-none focus:ring-2 focus:ring-emerald-500/30"
                  />
                  <button type="submit" className="absolute right-2.5 top-1/2 -translate-y-1/2 text-slate-400 hover:text-slate-600">
                    <Search className="w-4 h-4" />
                  </button>
                </div>
              </div>

              {/* Year */}
              <div className="w-32">
                <label className="block text-xs font-semibold text-slate-600 dark:text-slate-400 mb-1">Year</label>
                <select
                  value={form.data.year}
                  onChange={(e) => handleYearChange(e.target.value)}
                  className="w-full h-9 rounded-xl border border-slate-300 dark:border-slate-700 bg-white dark:bg-slate-800 px-3 text-xs text-slate-900 dark:text-slate-100 focus:outline-none focus:ring-2 focus:ring-emerald-500/30"
                >
                  <option value="">All years</option>
                  {years.map((y) => (
                    <option key={y.target_year} value={y.target_year}>
                      {y.target_year}
                    </option>
                  ))}
                </select>
              </div>

              {/* Semester */}
              <div className="w-40">
                <label className="block text-xs font-semibold text-slate-600 dark:text-slate-400 mb-1">Semester</label>
                <select
                  value={form.data.semester}
                  onChange={(e) => handleSemesterChange(e.target.value)}
                  className="w-full h-9 rounded-xl border border-slate-300 dark:border-slate-700 bg-white dark:bg-slate-800 px-3 text-xs text-slate-900 dark:text-slate-100 focus:outline-none focus:ring-2 focus:ring-emerald-500/30"
                >
                  <option value="">All semesters</option>
                  {semesters.map((s) => (
                    <option key={s.value} value={s.value}>
                      {s.label}
                    </option>
                  ))}
                </select>
              </div>

              {/* Records Per Page */}
              <div className="w-32">
                <label className="block text-xs font-semibold text-slate-600 dark:text-slate-400 mb-1">Records Per Page</label>
                <select
                  value={form.data.perPage}
                  onChange={(e) => handlePerPageChange(e.target.value)}
                  className="w-full h-9 rounded-xl border border-slate-300 dark:border-slate-700 bg-white dark:bg-slate-800 px-3 text-xs text-slate-900 dark:text-slate-100 focus:outline-none focus:ring-2 focus:ring-emerald-500/30"
                >
                  {perPageOptions.map((opt) => (
                    <option key={opt.value} value={opt.value}>
                      {opt.label}
                    </option>
                  ))}
                </select>
              </div>

              {/* Reset Button */}
              <div>
                <button
                  type="button"
                  onClick={resetFilters}
                  className="h-9 px-4 rounded-xl bg-slate-600 hover:bg-slate-700 dark:bg-slate-700 dark:hover:bg-slate-600 text-white text-xs font-semibold flex items-center gap-1.5 transition"
                >
                  <RotateCcw className="w-3.5 h-3.5" />
                  <span>Reset</span>
                </button>
              </div>
            </div>
          </form>

          {/* Table for ipc_semester Data */}
          <div className="w-full overflow-x-auto rounded-xl border border-slate-200 dark:border-slate-800">
            <table className="w-full min-w-[900px] table-fixed border-separate border-spacing-0 text-sm">
              <thead className="bg-slate-50 dark:bg-slate-800/60 text-left text-xs font-semibold uppercase tracking-wide text-slate-500 dark:text-slate-400">
                <tr>
                  <th className="border-b border-r border-slate-200 dark:border-slate-800 px-3 py-3 text-center whitespace-nowrap" style={{ width: '50px' }}>
                    #
                  </th>
                  <th className="border-b border-r border-slate-200 dark:border-slate-800 px-3 py-3 whitespace-nowrap" style={{ width: '90px' }}>
                    Year
                  </th>
                  <th className="border-b border-r border-slate-200 dark:border-slate-800 px-3 py-3 whitespace-nowrap" style={{ width: '130px' }}>
                    Semester
                  </th>
                  <th className="border-b border-r border-slate-200 dark:border-slate-800 px-3 py-3 whitespace-nowrap" style={{ width: '130px' }}>
                    Final Rating
                  </th>
                  <th className="border-b border-r border-slate-200 dark:border-slate-800 px-3 py-3 whitespace-nowrap" style={{ width: '160px' }}>
                    Adjectival Rating
                  </th>
                  <th className="border-b border-r border-slate-200 dark:border-slate-800 px-4 py-3 text-center whitespace-nowrap" style={{ width: '220px' }}>
                    Status
                  </th>
                  <th className="border-b border-r border-slate-200 dark:border-slate-800 px-3 py-3 whitespace-nowrap" style={{ width: '160px' }}>
                    Date Created
                  </th>
                  <th className="border-b border-slate-200 dark:border-slate-800 px-3 py-3 text-center whitespace-nowrap" style={{ width: '120px' }}>
                    Action
                  </th>
                </tr>
              </thead>
              <tbody>
                {ratings.data.length > 0 ? (
                  ratings.data.map((rating, index) => {
                    const isVerified = Boolean(rating.dateVerified);
                    const isWaitingVerification = rating.lock === 2;
                    const isOngoingSelfRating = rating.lock === 1;

                    return (
                      <tr key={rating.id} className="border-t border-slate-200/60 dark:border-slate-800/60 text-sm hover:bg-slate-50/50 dark:hover:bg-slate-800/30 transition">
                        <td className="border-b border-r border-slate-200 dark:border-slate-800 px-3 py-3 text-center text-slate-500 dark:text-slate-400 align-middle">
                          {(ratings.from ?? 1) + index}
                        </td>
                        <td className="border-b border-r border-slate-200 dark:border-slate-800 px-3 py-3 font-semibold text-slate-900 dark:text-slate-100 align-middle">
                          {rating.year}
                        </td>
                        <td className="border-b border-r border-slate-200 dark:border-slate-800 px-3 py-3 font-medium align-middle text-slate-800 dark:text-slate-200">
                          {rating.semester === 1 ? '1st Semester' : rating.semester === 2 ? '2nd Semester' : rating.semester}
                        </td>
                        <td className="border-b border-r border-slate-200 dark:border-slate-800 px-3 py-3 font-mono align-middle text-slate-800 dark:text-slate-200">
                          {rating.finalRating || '0.00000'}
                        </td>
                        <td className="border-b border-r border-slate-200 dark:border-slate-800 px-3 py-3 align-middle text-slate-800 dark:text-slate-200">
                          {rating.adjectivalRating || '-'}
                        </td>
                        <td className="border-b border-r border-slate-200 dark:border-slate-800 px-4 py-3 align-middle whitespace-nowrap text-center">
                          {isVerified ? (
                            <div className="inline-flex items-center gap-1.5 text-xs font-semibold text-emerald-600 dark:text-emerald-400">
                              <ShieldCheck className="w-4 h-4 shrink-0 text-emerald-600 dark:text-emerald-400" />
                              <span>{formatDate(rating.dateVerified)}</span>
                            </div>
                          ) : isWaitingVerification ? (
                            <div className="inline-flex items-center gap-1.5 text-xs font-semibold text-amber-600 dark:text-amber-400">
                              <Clock className="w-4 h-4 shrink-0 text-amber-600 dark:text-amber-400" />
                              <span>Waiting for Verification</span>
                            </div>
                          ) : isOngoingSelfRating ? (
                            <div className="inline-flex items-center gap-1.5 text-xs font-semibold text-sky-600 dark:text-sky-400">
                              <Edit3 className="w-4 h-4 shrink-0 text-sky-600 dark:text-sky-400" />
                              <span>On-going for Self-Rating</span>
                            </div>
                          ) : (
                            <div className="inline-flex items-center gap-1.5 text-xs font-semibold text-emerald-600 dark:text-emerald-400">
                              <Flag className="w-4 h-4 shrink-0 text-emerald-600 dark:text-emerald-400" />
                              <span>On-going for Checkpoint</span>
                            </div>
                          )}
                        </td>
                        <td className="border-b border-r border-slate-200 dark:border-slate-800 px-3 py-3 text-xs text-slate-500 dark:text-slate-400 align-middle">
                          {formatDate(rating.dateCreated)}
                        </td>
                        <td className="border-b border-slate-200 dark:border-slate-800 px-3 py-3 align-middle whitespace-nowrap text-center">
                          <div className="inline-flex items-center gap-1.5 justify-center">
                            {/* View Button */}
                            <Link
                              href={`/inertia/ipcrf/myratings/${rating.id}/sem-target`}
                              title="View"
                              className="p-1.5 rounded-lg bg-emerald-600 text-white hover:bg-emerald-700 transition"
                            >
                              <Eye className="w-4 h-4" />
                            </Link>

                            {/* Remove Button */}
                            {!isWaitingVerification && !isVerified ? (
                              <button
                                type="button"
                                title="Remove"
                                onClick={() => confirmDelete(rating.id)}
                                className="p-1.5 rounded-lg bg-rose-600 text-white hover:bg-rose-700 transition"
                              >
                                <Trash2 className="w-4 h-4" />
                              </button>
                            ) : null}
                          </div>
                        </td>
                      </tr>
                    );
                  })
                ) : (
                  <tr>
                    <td colSpan={8} className="border-b border-slate-200 dark:border-slate-800 px-3 py-10 text-center text-slate-500 dark:text-slate-400">
                      No rating records found in ipc_semester.
                    </td>
                  </tr>
                )}
              </tbody>
            </table>
          </div>

          {/* Pagination Bar */}
          {ratings.links && ratings.links.length > 3 && (
            <div className="mt-4 flex flex-col sm:flex-row items-center justify-between gap-3 text-xs text-slate-500 dark:text-slate-400">
              <div>
                Showing <span className="font-semibold text-slate-800 dark:text-slate-200">{ratings.from ?? 0}</span> to{' '}
                <span className="font-semibold text-slate-800 dark:text-slate-200">{ratings.to ?? 0}</span> of{' '}
                <span className="font-semibold text-slate-800 dark:text-slate-200">{ratings.total}</span> results
              </div>
              <div className="flex flex-wrap gap-1">
                {ratings.links.map((link, idx) => {
                  if (!link.url) {
                    return (
                      <span
                        key={idx}
                        className="px-3 py-1.5 rounded-lg border border-slate-200 dark:border-slate-800 text-slate-400 dark:text-slate-600 opacity-60 cursor-not-allowed"
                        dangerouslySetInnerHTML={{ __html: link.label }}
                      />
                    );
                  }

                  return (
                    <Link
                      key={idx}
                      href={link.url}
                      preserveState
                      preserveScroll
                      className={`px-3 py-1.5 rounded-lg border transition ${
                        link.active
                          ? 'bg-slate-900 text-white border-slate-900 dark:bg-emerald-600 dark:border-emerald-600 font-semibold'
                          : 'bg-white dark:bg-slate-800 border-slate-200 dark:border-slate-700 text-slate-700 dark:text-slate-200 hover:bg-slate-50 dark:hover:bg-slate-700'
                      }`}
                      dangerouslySetInnerHTML={{ __html: link.label }}
                    />
                  );
                })}
              </div>
            </div>
          )}
        </div>

        {/* Delete Confirmation Modal */}
        {deletingId !== null && (
          <div className="fixed inset-0 z-50 flex items-center justify-center bg-slate-900/50 backdrop-blur-sm p-4 animate-in fade-in duration-150">
            <div className="w-full max-w-md rounded-2xl border border-slate-200 dark:border-slate-800 bg-white dark:bg-slate-900 p-6 shadow-2xl space-y-5">
              <div className="flex items-start justify-between">
                <div className="flex items-center gap-3">
                  <div className="p-2.5 rounded-xl bg-rose-100 dark:bg-rose-950 text-rose-600 dark:text-rose-400">
                    <AlertTriangle className="w-6 h-6" />
                  </div>
                  <div>
                    <h3 className="text-base font-bold text-slate-900 dark:text-slate-100">Remove Semester Rating Record</h3>
                    <p className="text-xs text-slate-500 dark:text-slate-400 mt-0.5">
                      Are you sure you want to remove this semester rating record from ipc_semester? This action cannot be undone.
                    </p>
                  </div>
                </div>
                <button
                  type="button"
                  onClick={cancelDelete}
                  className="text-slate-400 hover:text-slate-600 dark:hover:text-slate-200 p-1 rounded-lg transition"
                >
                  <X className="w-4 h-4" />
                </button>
              </div>

              <div className="flex items-center justify-end gap-2.5 pt-2">
                <button
                  type="button"
                  onClick={cancelDelete}
                  disabled={isDeleting}
                  className="px-4 py-2 rounded-xl border border-slate-300 dark:border-slate-700 bg-white dark:bg-slate-800 text-xs font-semibold text-slate-700 dark:text-slate-300 hover:bg-slate-50 dark:hover:bg-slate-700 transition"
                >
                  Cancel
                </button>
                <button
                  type="button"
                  onClick={handleDelete}
                  disabled={isDeleting}
                  className="px-4 py-2 rounded-xl bg-rose-600 hover:bg-rose-700 text-white text-xs font-semibold shadow-sm transition flex items-center gap-2"
                >
                  {isDeleting ? 'Removing...' : 'Confirm and Remove'}
                </button>
              </div>
            </div>
          </div>
        )}
      </section>
    </AppLayout>
  );
}

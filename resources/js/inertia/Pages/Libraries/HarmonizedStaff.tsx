import { Head, router, useForm } from '@inertiajs/react';
import { useState } from 'react';
import AppLayout from '../../Layouts/AppLayout';
import { Pencil, Trash2, Plus, RotateCcw } from 'lucide-react';

type PositionRow = {
  id: number;
  name: string;
  sortOrder: number;
  isActive: boolean;
};

type Props = {
  appName: string;
  user: { name: string; email: string } | null;
  filters: { search: string; perPage: number };
  positions: {
    data: PositionRow[];
    from: number | null;
    to: number | null;
    total: number;
    currentPage: number;
    lastPage: number;
  };
  maxSortOrder: number;
  perPageOptions: Array<{ value: number; label: string }>;
  navigation?: { sidebar?: any[] };
};

export default function HarmonizedStaff({
  appName,
  user,
  filters,
  positions,
  maxSortOrder,
  perPageOptions,
  navigation,
}: Props) {
  // Filter Form
  const filterForm = useForm({
    search: filters.search,
    perPage: String(filters.perPage),
  });

  // Modal State
  const [showModal, setShowModal] = useState(false);
  const [editingId, setEditingId] = useState<number | null>(null);
  const [deletingId, setDeletingId] = useState<number | null>(null);

  // Add/Edit Position Form
  const positionForm = useForm({
    name: '',
    sortOrder: maxSortOrder,
    isActive: true,
  });

  const submitFilters = (overrides = {}) => {
    const data = { ...filterForm.data, ...overrides };
    router.get('/inertia/libraries/harmonized-staff', data, {
      preserveState: true,
      replace: true,
    });
  };

  const resetFilters = () => {
    filterForm.setData({ search: '', perPage: '10' });
    router.get('/inertia/libraries/harmonized-staff', { search: '', perPage: '10' }, {
      replace: true,
    });
  };

  const openCreateModal = () => {
    setEditingId(null);
    positionForm.setData({
      name: '',
      sortOrder: maxSortOrder,
      isActive: true,
    });
    setShowModal(true);
  };

  const openEditModal = (pos: PositionRow) => {
    setEditingId(pos.id);
    positionForm.setData({
      name: pos.name,
      sortOrder: pos.sortOrder,
      isActive: pos.isActive,
    });
    setShowModal(true);
  };

  const handleFormSubmit = (e: React.FormEvent) => {
    e.preventDefault();

    if (editingId !== null) {
      positionForm.patch(`/inertia/libraries/harmonized-staff/${editingId}`, {
        onSuccess: () => {
          setShowModal(false);
          setEditingId(null);
        },
      });
    } else {
      positionForm.post('/inertia/libraries/harmonized-staff', {
        onSuccess: () => {
          setShowModal(false);
        },
      });
    }
  };

  return (
    <AppLayout appName={appName} user={user} sidebar={navigation?.sidebar ?? []}>
      <Head title="Harmonized Staff" />

      <section className="w-full space-y-6">
        {/* Livewire Header Style */}
        <div className="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
          <div className="space-y-1">
            <h1 className="text-xl font-bold tracking-tight text-slate-900 dark:text-slate-50">
              Harmonized Staff
            </h1>
            <p className="text-sm text-slate-500 dark:text-slate-400">
              Manage positions and roles for harmonized staff.
            </p>
          </div>

          <div className="flex items-center gap-2">
            <button
              type="button"
              onClick={openCreateModal}
              className="inline-flex items-center gap-1.5 rounded-xl bg-emerald-600 hover:bg-emerald-700 px-4 py-2 text-sm font-semibold text-white shadow-sm transition"
            >
              <Plus className="w-4 h-4" />
              <span>Add Position</span>
            </button>
          </div>
        </div>

        {/* Outer Card Container */}
        <div className="rounded-2xl border border-slate-200 dark:border-slate-800 bg-white dark:bg-slate-900 p-4 shadow-sm space-y-4">
          {/* Filter Bar */}
          <div className="mb-4 border-b border-slate-100 dark:border-slate-800 pb-4">
            <div className="grid gap-3 sm:grid-cols-3 lg:grid-cols-4 items-end">
              <div>
                <label className="block text-xs font-semibold text-slate-600 dark:text-slate-400 mb-1">
                  Search
                </label>
                <input
                  type="text"
                  value={filterForm.data.search}
                  onChange={(e) => filterForm.setData('search', e.target.value)}
                  onKeyDown={(e) => e.key === 'Enter' && submitFilters()}
                  placeholder="Search positions..."
                  className="h-10 w-full rounded-xl border border-slate-300 dark:border-slate-700 bg-white dark:bg-slate-800 px-3 text-sm focus:border-emerald-500 focus:outline-none dark:text-slate-100"
                />
              </div>

              <div>
                <label className="block text-xs font-semibold text-slate-600 dark:text-slate-400 mb-1">
                  Records Per Page
                </label>
                <select
                  value={filterForm.data.perPage}
                  onChange={(e) => {
                    filterForm.setData('perPage', e.target.value);
                    submitFilters({ perPage: e.target.value });
                  }}
                  className="h-10 w-full rounded-xl border border-slate-300 dark:border-slate-700 bg-white dark:bg-slate-800 px-3 text-sm focus:border-emerald-500 focus:outline-none dark:text-slate-100"
                >
                  {perPageOptions.map((opt) => (
                    <option key={opt.value} value={opt.value}>
                      {opt.label}
                    </option>
                  ))}
                </select>
              </div>

              <div>
                <button
                  type="button"
                  onClick={resetFilters}
                  className="h-10 inline-flex items-center gap-1.5 px-3 rounded-xl border border-slate-200 dark:border-slate-700 bg-white dark:bg-slate-800 text-xs font-semibold text-slate-700 dark:text-slate-300 hover:bg-slate-50 transition"
                >
                  <RotateCcw className="w-3.5 h-3.5" />
                  <span>Reset Filters</span>
                </button>
              </div>
            </div>
          </div>

          {/* Table Matching Livewire Bordered Grid Exact Layout */}
          <div className="overflow-x-auto rounded-xl border border-slate-200 dark:border-slate-800">
            <table className="w-full border-separate border-spacing-0 text-sm">
              <thead className="bg-slate-50 dark:bg-slate-800/50 text-left text-xs font-semibold uppercase tracking-wide text-slate-500 dark:text-slate-400">
                <tr>
                  <th className="border-b border-r border-slate-200 dark:border-slate-800 px-3 py-3 text-center first:rounded-tl-xl w-[60px]">
                    #
                  </th>
                  <th className="border-b border-r border-slate-200 dark:border-slate-800 px-3 py-3 whitespace-nowrap">
                    Position Name
                  </th>
                  <th className="border-b border-r border-slate-200 dark:border-slate-800 px-3 py-3 text-center whitespace-nowrap w-[120px]">
                    Sort Order
                  </th>
                  <th className="border-b border-r border-slate-200 dark:border-slate-800 px-3 py-3 text-center whitespace-nowrap w-[120px]">
                    Status
                  </th>
                  <th className="border-b border-slate-200 dark:border-slate-800 px-3 py-3 text-center whitespace-nowrap last:rounded-tr-xl w-[120px]">
                    Action
                  </th>
                </tr>
              </thead>
              <tbody className="divide-y divide-slate-100 dark:divide-slate-800">
                {positions.data.length === 0 ? (
                  <tr>
                    <td colSpan={5} className="border-b border-slate-200 dark:border-slate-800 px-3 py-10 text-center text-slate-500 dark:text-slate-400">
                      No positions found.
                    </td>
                  </tr>
                ) : (
                  positions.data.map((pos, index) => (
                    <tr key={pos.id} className="border-t border-slate-200 dark:border-slate-800 text-sm hover:bg-slate-50/50 dark:hover:bg-slate-800/40">
                      <td className="border-b border-r border-slate-200 dark:border-slate-800 px-3 py-3 text-center text-slate-500">
                        {(positions.from ?? 1) + index}
                      </td>

                      <td className="border-b border-r border-slate-200 dark:border-slate-800 px-3 py-3 font-medium text-slate-900 dark:text-slate-100">
                        {pos.name}
                      </td>

                      <td className="border-b border-r border-slate-200 dark:border-slate-800 px-3 py-3 text-center text-slate-700 dark:text-slate-300">
                        {pos.sortOrder}
                      </td>

                      <td className="border-b border-r border-slate-200 dark:border-slate-800 px-3 py-3 text-center">
                        {pos.isActive ? (
                          <span className="inline-flex items-center rounded-full bg-emerald-500/10 px-2.5 py-0.5 text-xs font-medium text-emerald-600 dark:text-emerald-400">
                            Active
                          </span>
                        ) : (
                          <span className="inline-flex items-center rounded-full bg-slate-100 dark:bg-slate-800 px-2.5 py-0.5 text-xs font-medium text-slate-500 dark:text-slate-400">
                            Inactive
                          </span>
                        )}
                      </td>

                      <td className="border-b border-slate-200 dark:border-slate-800 px-3 py-3 text-center">
                        <div className="flex items-center justify-center gap-1">
                          <button
                            type="button"
                            onClick={() => openEditModal(pos)}
                            aria-label="Edit"
                            className="p-1.5 rounded-lg text-slate-600 hover:bg-slate-100 dark:text-slate-300 dark:hover:bg-slate-800 transition"
                          >
                            <Pencil className="w-3.5 h-3.5" />
                          </button>
                          <button
                            type="button"
                            onClick={() => setDeletingId(pos.id)}
                            aria-label="Delete"
                            className="p-1.5 rounded-lg text-red-600 hover:bg-red-50 dark:text-red-400 dark:hover:bg-red-500/10 transition"
                          >
                            <Trash2 className="w-3.5 h-3.5" />
                          </button>
                        </div>
                      </td>
                    </tr>
                  ))
                )}
              </tbody>
            </table>
          </div>

          {/* Livewire Pagination Style Matching vendor.pagination.users-pagination */}
          {positions.lastPage > 1 ? (
            <nav
              role="navigation"
              aria-label="Pagination Navigation"
              className="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between pt-2"
            >
              <div className="text-sm text-slate-500 dark:text-slate-400">
                Showing {positions.from ?? 0} to {positions.to ?? 0} of {positions.total} records
              </div>

              <div className="flex flex-wrap items-center gap-1.5">
                {/* Previous Button */}
                {positions.currentPage === 1 ? (
                  <span className="inline-flex cursor-not-allowed items-center rounded-lg border border-slate-200 dark:border-slate-800 bg-slate-100 dark:bg-slate-800 px-3 py-2 text-sm text-slate-400 select-none">
                    Previous
                  </span>
                ) : (
                  <button
                    type="button"
                    onClick={() =>
                      router.get(
                        '/inertia/libraries/harmonized-staff',
                        { ...filterForm.data, page: positions.currentPage - 1 },
                        { replace: true, preserveState: true }
                      )
                    }
                    className="inline-flex cursor-pointer items-center rounded-lg border border-slate-200 dark:border-slate-800 bg-white dark:bg-slate-900 px-3 py-2 text-sm text-slate-700 dark:text-slate-200 hover:border-emerald-500/50 hover:bg-emerald-50/50 hover:text-emerald-600 dark:hover:bg-emerald-950/30 dark:hover:text-emerald-400 transition-colors"
                  >
                    Previous
                  </button>
                )}

                {/* Page Numbers */}
                {Array.from({ length: positions.lastPage }, (_, i) => i + 1).map((page) => {
                  if (page === positions.currentPage) {
                    return (
                      <span
                        key={page}
                        aria-current="page"
                        className="inline-flex min-w-10 cursor-pointer items-center justify-center rounded-lg border border-emerald-600 bg-emerald-600 px-3 py-2 text-sm font-semibold text-white shadow-xs"
                      >
                        {page}
                      </span>
                    );
                  }
                  return (
                    <button
                      key={page}
                      type="button"
                      onClick={() =>
                        router.get(
                          '/inertia/libraries/harmonized-staff',
                          { ...filterForm.data, page },
                          { replace: true, preserveState: true }
                        )
                      }
                      className="inline-flex min-w-10 cursor-pointer items-center justify-center rounded-lg border border-slate-200 dark:border-slate-800 bg-white dark:bg-slate-900 px-3 py-2 text-sm text-slate-700 dark:text-slate-200 hover:border-emerald-500/50 hover:bg-emerald-50/50 hover:text-emerald-600 dark:hover:bg-emerald-950/30 dark:hover:text-emerald-400 transition-colors"
                    >
                      {page}
                    </button>
                  );
                })}

                {/* Next Button */}
                {positions.currentPage === positions.lastPage ? (
                  <span className="inline-flex cursor-not-allowed items-center rounded-lg border border-slate-200 dark:border-slate-800 bg-slate-100 dark:bg-slate-800 px-3 py-2 text-sm text-slate-400 select-none">
                    Next
                  </span>
                ) : (
                  <button
                    type="button"
                    onClick={() =>
                      router.get(
                        '/inertia/libraries/harmonized-staff',
                        { ...filterForm.data, page: positions.currentPage + 1 },
                        { replace: true, preserveState: true }
                      )
                    }
                    className="inline-flex cursor-pointer items-center rounded-lg border border-slate-200 dark:border-slate-800 bg-white dark:bg-slate-900 px-3 py-2 text-sm text-slate-700 dark:text-slate-200 hover:border-emerald-500/50 hover:bg-emerald-50/50 hover:text-emerald-600 dark:hover:bg-emerald-950/30 dark:hover:text-emerald-400 transition-colors"
                  >
                    Next
                  </button>
                )}
              </div>
            </nav>
          ) : null}
        </div>

        {/* Modal: Add/Edit Position */}
        {showModal ? (
          <div className="fixed inset-0 z-50 flex items-center justify-center bg-slate-900/40 p-4 backdrop-blur-sm">
            <div className="w-full max-w-md rounded-3xl bg-white dark:bg-slate-900 p-6 shadow-xl space-y-5 border border-slate-200 dark:border-slate-800">
              <div className="space-y-1">
                <h3 className="text-lg font-bold text-slate-900 dark:text-slate-100">
                  {editingId ? 'Edit Position' : 'Add Position'}
                </h3>
                <p className="text-xs text-slate-500 dark:text-slate-400">
                  {editingId
                    ? 'Update position details below.'
                    : 'Enter position details to add a new record.'}
                </p>
              </div>

              <form onSubmit={handleFormSubmit} className="space-y-4">
                <div>
                  <label className="block text-xs font-semibold text-slate-600 dark:text-slate-400 mb-1">
                    Position Name
                  </label>
                  <input
                    type="text"
                    value={positionForm.data.name}
                    onChange={(e) => positionForm.setData('name', e.target.value)}
                    placeholder="e.g., Provincial Link"
                    className="h-10 w-full rounded-xl border border-slate-300 dark:border-slate-700 bg-white dark:bg-slate-800 px-3 text-sm text-slate-900 dark:text-slate-100 focus:border-emerald-500 focus:outline-none"
                    required
                  />
                </div>

                <div>
                  <label className="block text-xs font-semibold text-slate-600 dark:text-slate-400 mb-1">
                    Sort Order
                  </label>
                  <input
                    type="number"
                    min="0"
                    value={positionForm.data.sortOrder}
                    onChange={(e) => positionForm.setData('sortOrder', Number(e.target.value))}
                    className="h-10 w-full rounded-xl border border-slate-300 dark:border-slate-700 bg-white dark:bg-slate-800 px-3 text-sm text-slate-900 dark:text-slate-100 focus:border-emerald-500 focus:outline-none"
                    required
                  />
                </div>

                <div>
                  <label className="block text-xs font-semibold text-slate-600 dark:text-slate-400 mb-1">
                    Status
                  </label>
                  <select
                    value={positionForm.data.isActive ? '1' : '0'}
                    onChange={(e) => positionForm.setData('isActive', e.target.value === '1')}
                    className="h-10 w-full rounded-xl border border-slate-300 dark:border-slate-700 bg-white dark:bg-slate-800 px-3 text-sm text-slate-900 dark:text-slate-100 focus:border-emerald-500 focus:outline-none"
                  >
                    <option value="1">Active</option>
                    <option value="0">Inactive</option>
                  </select>
                </div>

                <div className="flex justify-end gap-2 pt-4 border-t border-slate-100 dark:border-slate-800">
                  <button
                    type="button"
                    onClick={() => setShowModal(false)}
                    className="rounded-xl px-4 py-2 text-sm font-medium text-slate-700 hover:bg-slate-100 dark:text-slate-300 dark:hover:bg-slate-800 transition"
                  >
                    Cancel
                  </button>
                  <button
                    type="submit"
                    disabled={positionForm.processing}
                    className="rounded-xl bg-emerald-600 hover:bg-emerald-700 px-5 py-2 text-sm font-semibold text-white shadow-sm transition"
                  >
                    {editingId ? 'Save Changes' : 'Create Position'}
                  </button>
                </div>
              </form>
            </div>
          </div>
        ) : null}

        {/* Modal: Delete Position */}
        {deletingId !== null ? (
          <div className="fixed inset-0 z-50 flex items-center justify-center bg-slate-900/40 p-4 backdrop-blur-sm">
            <div className="w-full max-w-md rounded-3xl bg-white dark:bg-slate-900 p-6 shadow-xl space-y-5 border border-slate-200 dark:border-slate-800">
              <div className="space-y-1">
                <h3 className="text-lg font-bold text-slate-900 dark:text-slate-100">Delete Position</h3>
                <p className="text-xs text-slate-500 dark:text-slate-400">
                  Are you sure you want to delete this position? This action cannot be undone.
                </p>
              </div>

              <div className="flex justify-end gap-2 pt-2">
                <button
                  type="button"
                  onClick={() => setDeletingId(null)}
                  className="rounded-xl px-4 py-2 text-sm font-medium text-slate-700 hover:bg-slate-100 dark:text-slate-300 dark:hover:bg-slate-800 transition"
                >
                  Cancel
                </button>
                <button
                  type="button"
                  onClick={() => {
                    router.delete(`/inertia/libraries/harmonized-staff/${deletingId}`, {
                      onSuccess: () => setDeletingId(null),
                    });
                  }}
                  className="rounded-xl bg-red-600 hover:bg-red-700 px-5 py-2 text-sm font-semibold text-white shadow-sm transition"
                >
                  Delete
                </button>
              </div>
            </div>
          </div>
        ) : null}
      </section>
    </AppLayout>
  );
}

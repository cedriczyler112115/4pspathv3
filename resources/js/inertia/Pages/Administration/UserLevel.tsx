import { Head, router, useForm } from '@inertiajs/react';
import { useState } from 'react';
import AppLayout from '../../Layouts/AppLayout';

type LevelRow = {
  levelId: number;
  levelName: string;
  isStatus: number;
  menuAccessSummary: { count: number; total: number; isAll: boolean };
};

type MenuRow = {
  id: number;
  label: string;
  key: string | null;
  href: string | null;
  icon: string | null;
  depth: number;
  userLevels: number[];
};

type Props = {
  appName: string;
  user: { name: string; email: string } | null;
  filters: { search: string; perPage: number };
  userLevels: {
    data: LevelRow[];
    from: number | null;
    total: number;
    currentPage: number;
    lastPage: number;
  };
  perPageOptions: Array<{ value: number; label: string }>;
  menuAccess: MenuRow[];
  navigation?: { sidebar?: any[] };
};

export default function UserLevel({
  appName,
  user,
  filters,
  userLevels,
  perPageOptions,
  menuAccess,
  navigation,
}: Props) {
  // Search & Pagination Form
  const filterForm = useForm({
    search: filters.search,
    perPage: String(filters.perPage),
  });

  // User Level Form Modal (Create / Edit)
  const [showLevelModal, setShowLevelModal] = useState(false);
  const [editingId, setEditingId] = useState<number | null>(null);

  const levelForm = useForm({
    editingId: null as number | null,
    level_name: '',
    is_status: '1',
  });

  // Menu Access Modal
  const [showAccessModal, setShowAccessModal] = useState(false);
  const [accessTargetLevel, setAccessTargetLevel] = useState<{ id: number; name: string } | null>(null);
  const [selectedMenuIds, setSelectedMenuIds] = useState<number[]>([]);
  const [menuSearch, setMenuSearch] = useState('');

  // Delete Confirmation Modal
  const [deletingId, setDeletingId] = useState<number | null>(null);

  const submitFilters = () => {
    router.get('/inertia/administration/user-level', filterForm.data, {
      preserveState: true,
      replace: true,
    });
  };

  const openCreateModal = () => {
    setEditingId(null);
    levelForm.setData({
      editingId: null,
      level_name: '',
      is_status: '1',
    });
    setShowLevelModal(true);
  };

  const openEditModal = (level: LevelRow) => {
    setEditingId(level.levelId);
    levelForm.setData({
      editingId: level.levelId,
      level_name: level.levelName,
      is_status: String(level.isStatus),
    });
    setShowLevelModal(true);
  };

  const handleSaveLevel = (e: React.FormEvent) => {
    e.preventDefault();
    levelForm.post('/inertia/administration/user-level', {
      onSuccess: () => {
        setShowLevelModal(false);
        setEditingId(null);
      },
    });
  };

  const openAccessModal = (level: LevelRow) => {
    setAccessTargetLevel({ id: level.levelId, name: level.levelName });

    // Determine initial checked menu items
    const selected: number[] = [];
    menuAccess.forEach((item) => {
      if (!item.userLevels || item.userLevels.length === 0 || item.userLevels.includes(level.levelId)) {
        selected.push(item.id);
      }
    });

    setSelectedMenuIds(selected);
    setMenuSearch('');
    setShowAccessModal(true);
  };

  const handleSelectAllMenuAccess = () => {
    setSelectedMenuIds(menuAccess.map((item) => item.id));
  };

  const handleDeselectAllMenuAccess = () => {
    setSelectedMenuIds([]);
  };

  const handleSaveMenuAccess = () => {
    if (!accessTargetLevel) return;

    router.patch(
      '/inertia/administration/user-level/menu-access',
      {
        levelId: accessTargetLevel.id,
        selectedMenuItemIds: selectedMenuIds,
      },
      {
        onSuccess: () => {
          setShowAccessModal(false);
          setAccessTargetLevel(null);
        },
      }
    );
  };

  const filteredMenuRows = menuAccess.filter((item) => {
    if (!menuSearch.trim()) return true;
    const query = menuSearch.toLowerCase();
    return (
      item.label.toLowerCase().includes(query) ||
      (item.key && item.key.toLowerCase().includes(query)) ||
      (item.href && item.href.toLowerCase().includes(query))
    );
  });

  return (
    <AppLayout appName={appName} user={user} sidebar={navigation?.sidebar ?? []}>
      <Head title="User Level" />

      <div className="space-y-6 rounded-[2rem] border border-slate-200 bg-white p-6 shadow-[0_20px_60px_rgba(15,23,42,0.08)] sm:p-8">
        {/* Header & Actions */}
        <div className="flex flex-col justify-between gap-4 border-b border-slate-100 pb-6 sm:flex-row sm:items-center">
          <div>
            <p className="text-xs font-semibold uppercase tracking-[0.3em] text-cyan-700">Administration</p>
            <h2 className="text-2xl font-bold tracking-tight text-slate-900">User Level Directory</h2>
            <p className="max-w-2xl text-sm leading-6 text-slate-600">
              Create and manage user role levels, toggle operational status, and assign sidebar menu access controls.
            </p>
          </div>

          <button
            type="button"
            onClick={openCreateModal}
            className="rounded-full bg-cyan-700 px-5 py-2.5 text-sm font-semibold text-white shadow-md transition hover:bg-cyan-800"
          >
            + Create User Level
          </button>
        </div>

        {/* Filter Controls Toolbar */}
        <div className="grid gap-3 sm:grid-cols-[1fr_auto]">
          <input
            value={filterForm.data.search}
            onChange={(e) => filterForm.setData('search', e.target.value)}
            onKeyDown={(e) => e.key === 'Enter' && submitFilters()}
            placeholder="Search by user level name..."
            className="h-10 rounded-xl border border-slate-200 px-3 text-sm focus:border-cyan-600 focus:outline-none"
          />

          <div className="flex gap-2">
            <select
              value={filterForm.data.perPage}
              onChange={(e) => {
                filterForm.setData('perPage', e.target.value);
                router.get(
                  '/inertia/administration/user-level',
                  { search: filterForm.data.search, perPage: e.target.value },
                  { replace: true }
                );
              }}
              className="h-10 rounded-xl border border-slate-200 px-3 text-sm focus:border-cyan-600 focus:outline-none"
            >
              {perPageOptions.map((opt) => (
                <option key={opt.value} value={opt.value}>
                  {opt.label} per page
                </option>
              ))}
            </select>

            <button
              type="button"
              onClick={submitFilters}
              className="h-10 rounded-xl bg-slate-900 px-5 text-sm font-medium text-white hover:bg-slate-800"
            >
              Search
            </button>
          </div>
        </div>

        {/* User Levels Data Table */}
        <div className="overflow-x-auto rounded-2xl border border-slate-200 bg-white">
          <table className="w-full text-left text-sm">
            <thead className="border-b border-slate-200 bg-slate-50/50 text-xs font-semibold uppercase text-slate-500">
              <tr>
                <th className="px-4 py-3">#</th>
                <th className="px-4 py-3">User Level Name</th>
                <th className="px-4 py-3">Sidebar Menu Access</th>
                <th className="px-4 py-3 text-center">Status</th>
                <th className="px-4 py-3 text-right">Actions</th>
              </tr>
            </thead>
            <tbody className="divide-y divide-slate-100">
              {userLevels.data.length === 0 ? (
                <tr>
                  <td colSpan={5} className="px-4 py-8 text-center text-slate-500">
                    No user levels found matching your query.
                  </td>
                </tr>
              ) : (
                userLevels.data.map((row, index) => (
                  <tr key={row.levelId} className="hover:bg-slate-50/50">
                    <td className="px-4 py-3 text-xs font-semibold text-slate-400">
                      {(userLevels.from ?? 1) + index}
                    </td>

                    <td className="px-4 py-3 font-semibold text-slate-900">{row.levelName}</td>

                    <td className="px-4 py-3 text-xs">
                      {row.menuAccessSummary.isAll ? (
                        <span className="rounded-full bg-emerald-50 px-2.5 py-0.5 font-bold text-emerald-800 border border-emerald-200">
                          Full Access (All Items)
                        </span>
                      ) : (
                        <span className="rounded-full bg-cyan-50 px-2.5 py-0.5 font-semibold text-cyan-800 border border-cyan-200">
                          {row.menuAccessSummary.count} of {row.menuAccessSummary.total} Menu Items
                        </span>
                      )}
                    </td>

                    <td className="px-4 py-3 text-center">
                      <span
                        className={`inline-flex rounded-full px-2.5 py-0.5 text-[10px] font-bold ${
                          row.isStatus === 1
                            ? 'bg-emerald-100 text-emerald-800'
                            : 'bg-slate-100 text-slate-500'
                        }`}
                      >
                        {row.isStatus === 1 ? 'Active' : 'Inactive'}
                      </span>
                    </td>

                    <td className="px-4 py-3 text-right">
                      <div className="flex justify-end gap-2">
                        <button
                          type="button"
                          onClick={() => openAccessModal(row)}
                          className="rounded-full border border-cyan-300 bg-cyan-50 px-3 py-1 text-xs font-bold text-cyan-800 hover:bg-cyan-100"
                        >
                          Menu Access
                        </button>
                        <button
                          type="button"
                          onClick={() => openEditModal(row)}
                          className="rounded-full border border-slate-300 px-3 py-1 text-xs font-semibold text-slate-700 hover:bg-slate-50"
                        >
                          Edit
                        </button>
                        <button
                          type="button"
                          onClick={() => setDeletingId(row.levelId)}
                          className="rounded-full border border-rose-200 px-3 py-1 text-xs font-semibold text-rose-700 hover:bg-rose-50"
                        >
                          Delete
                        </button>
                      </div>
                    </td>
                  </tr>
                ))
              )}
            </tbody>
          </table>
        </div>

        {/* Pagination Navigation */}
        {userLevels.lastPage > 1 ? (
          <div className="flex justify-between items-center pt-2">
            <span className="text-xs text-slate-500">
              Page {userLevels.currentPage} of {userLevels.lastPage} ({userLevels.total} Total)
            </span>
            <div className="flex gap-1">
              {Array.from({ length: userLevels.lastPage }, (_, i) => i + 1).map((pageNum) => (
                <button
                  key={pageNum}
                  type="button"
                  onClick={() =>
                    router.get(
                      '/inertia/administration/user-level',
                      { search: filterForm.data.search, perPage: filterForm.data.perPage, page: pageNum },
                      { replace: true }
                    )
                  }
                  className={`h-8 w-8 rounded-lg text-xs font-semibold ${
                    userLevels.currentPage === pageNum
                      ? 'bg-cyan-700 text-white'
                      : 'bg-slate-100 text-slate-700 hover:bg-slate-200'
                  }`}
                >
                  {pageNum}
                </button>
              ))}
            </div>
          </div>
        ) : null}

        {/* Modal: Create & Edit User Level */}
        {showLevelModal ? (
          <div className="fixed inset-0 z-50 flex items-center justify-center bg-slate-900/40 p-4 backdrop-blur-sm">
            <div className="w-full max-w-md rounded-3xl bg-white p-6 shadow-xl">
              <h3 className="text-lg font-bold text-slate-900">
                {editingId ? 'Edit User Level' : 'Create New User Level'}
              </h3>

              <form onSubmit={handleSaveLevel} className="mt-4 space-y-4">
                <div>
                  <label className="text-xs font-semibold text-slate-600">Level Name</label>
                  <input
                    value={levelForm.data.level_name}
                    onChange={(e) => levelForm.setData('level_name', e.target.value)}
                    placeholder="E.g., Administrator, Reviewer, Encoder"
                    className="mt-1 h-10 w-full rounded-xl border border-slate-300 px-3 text-sm focus:border-cyan-600 focus:outline-none"
                    required
                  />
                </div>

                <div>
                  <label className="text-xs font-semibold text-slate-600">Status</label>
                  <select
                    value={levelForm.data.is_status}
                    onChange={(e) => levelForm.setData('is_status', e.target.value)}
                    className="mt-1 h-10 w-full rounded-xl border border-slate-300 px-3 text-sm focus:border-cyan-600 focus:outline-none"
                  >
                    <option value="1">Active</option>
                    <option value="0">Inactive</option>
                  </select>
                </div>

                <div className="flex justify-end gap-3 pt-4 border-t border-slate-100">
                  <button
                    type="button"
                    onClick={() => setShowLevelModal(false)}
                    className="rounded-full border border-slate-300 px-4 py-2 text-sm font-medium text-slate-700 hover:bg-slate-50"
                  >
                    Cancel
                  </button>
                  <button
                    type="submit"
                    disabled={levelForm.processing}
                    className="rounded-full bg-cyan-700 px-5 py-2 text-sm font-semibold text-white hover:bg-cyan-800"
                  >
                    {editingId ? 'Update Level' : 'Create Level'}
                  </button>
                </div>
              </form>
            </div>
          </div>
        ) : null}

        {/* Modal: Sidebar Menu Access Control */}
        {showAccessModal && accessTargetLevel ? (
          <div className="fixed inset-0 z-50 flex items-center justify-center bg-slate-900/40 p-4 backdrop-blur-sm">
            <div className="w-full max-w-2xl max-h-[85vh] overflow-y-auto rounded-3xl bg-white p-6 shadow-xl">
              <div className="flex items-center justify-between border-b border-slate-100 pb-4">
                <div>
                  <h3 className="text-lg font-bold text-slate-900">
                    Manage Sidebar Menu Access
                  </h3>
                  <p className="text-xs font-semibold text-cyan-800">
                    User Level: {accessTargetLevel.name}
                  </p>
                </div>

                <div className="flex gap-2">
                  <button
                    type="button"
                    onClick={handleSelectAllMenuAccess}
                    className="rounded-full border border-slate-200 bg-slate-50 px-3 py-1 text-xs font-semibold text-slate-700 hover:bg-slate-100"
                  >
                    Select All
                  </button>
                  <button
                    type="button"
                    onClick={handleDeselectAllMenuAccess}
                    className="rounded-full border border-slate-200 bg-slate-50 px-3 py-1 text-xs font-semibold text-slate-700 hover:bg-slate-100"
                  >
                    Deselect All
                  </button>
                </div>
              </div>

              {/* Menu Filter */}
              <div className="mt-4">
                <input
                  value={menuSearch}
                  onChange={(e) => setMenuSearch(e.target.value)}
                  placeholder="Filter menu items..."
                  className="h-9 w-full rounded-xl border border-slate-200 px-3 text-xs focus:border-cyan-600 focus:outline-none"
                />
              </div>

              {/* Menu Tree List */}
              <div className="mt-3 max-h-80 overflow-y-auto rounded-2xl border border-slate-200 bg-slate-50/50 p-3 space-y-1.5">
                {filteredMenuRows.map((item) => {
                  const isChecked = selectedMenuIds.includes(item.id);
                  return (
                    <div
                      key={item.id}
                      style={{ paddingLeft: `${item.depth * 1.25}rem` }}
                      className="flex items-center gap-2.5 rounded-xl border border-slate-200 bg-white p-2.5 shadow-2xs"
                    >
                      <input
                        type="checkbox"
                        checked={isChecked}
                        onChange={(e) => {
                          if (e.target.checked) {
                            setSelectedMenuIds([...selectedMenuIds, item.id]);
                          } else {
                            setSelectedMenuIds(selectedMenuIds.filter((id) => id !== item.id));
                          }
                        }}
                        className="rounded border-slate-300 text-cyan-600 focus:ring-cyan-500"
                      />
                      <div className="min-w-0 flex-1">
                        <p className="text-xs font-bold text-slate-900">
                          {item.depth > 0 ? <span className="font-mono text-slate-400">— </span> : null}
                          {item.label}
                        </p>
                        <p className="text-[10px] font-mono text-slate-500">
                          {item.href || item.key || '(Parent Group)'}
                        </p>
                      </div>
                    </div>
                  );
                })}
              </div>

              <div className="flex justify-end gap-3 pt-4 border-t border-slate-100 mt-4">
                <button
                  type="button"
                  onClick={() => setShowAccessModal(false)}
                  className="rounded-full border border-slate-300 px-4 py-2 text-sm font-medium text-slate-700 hover:bg-slate-50"
                >
                  Cancel
                </button>
                <button
                  type="button"
                  onClick={handleSaveMenuAccess}
                  className="rounded-full bg-cyan-700 px-5 py-2 text-sm font-semibold text-white hover:bg-cyan-800"
                >
                  Save Access Rights
                </button>
              </div>
            </div>
          </div>
        ) : null}

        {/* Delete Confirmation Modal */}
        {deletingId ? (
          <div className="fixed inset-0 z-50 flex items-center justify-center bg-slate-900/40 p-4 backdrop-blur-sm">
            <div className="w-full max-w-md rounded-3xl bg-white p-6 shadow-xl text-center">
              <h3 className="text-lg font-bold text-slate-900">Delete User Level?</h3>
              <p className="mt-2 text-sm text-slate-600">
                Are you sure you want to delete this user level? Users assigned to this level will lose their access permissions.
              </p>
              <div className="mt-6 flex justify-center gap-3">
                <button
                  type="button"
                  onClick={() => setDeletingId(null)}
                  className="rounded-full border border-slate-300 px-4 py-2 text-sm font-medium text-slate-700"
                >
                  Cancel
                </button>
                <button
                  type="button"
                  onClick={() => {
                    router.delete(`/inertia/administration/user-level/${deletingId}`, {
                      onSuccess: () => setDeletingId(null),
                    });
                  }}
                  className="rounded-full bg-rose-600 px-5 py-2 text-sm font-semibold text-white hover:bg-rose-700"
                >
                  Delete Level
                </button>
              </div>
            </div>
          </div>
        ) : null}
      </div>
    </AppLayout>
  );
}

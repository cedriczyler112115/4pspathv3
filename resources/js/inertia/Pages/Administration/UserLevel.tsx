import { Head, router, useForm } from '@inertiajs/react';
import { useState } from 'react';
import AppLayout from '../../Layouts/AppLayout';
import { Shield, Plus, RotateCcw, Search, ChevronLeft, ChevronRight, X, Pencil, Trash2, SlidersHorizontal, CheckSquare, Square } from 'lucide-react';

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
    to: number | null;
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
  const filterForm = useForm({
    search: filters.search || '',
    perPage: String(filters.perPage || 10),
  });

  const [showLevelModal, setShowLevelModal] = useState(false);
  const [editingId, setEditingId] = useState<number | null>(null);

  const levelForm = useForm({
    editingId: null as number | null,
    level_name: '',
    is_status: '1',
  });

  const [showAccessModal, setShowAccessModal] = useState(false);
  const [accessTargetLevel, setAccessTargetLevel] = useState<{ id: number; name: string } | null>(null);
  const [selectedMenuIds, setSelectedMenuIds] = useState<number[]>([]);
  const [menuSearch, setMenuSearch] = useState('');

  const [deletingId, setDeletingId] = useState<number | null>(null);

  const submitFilters = (overrides?: Partial<typeof filterForm.data>) => {
    const data = { ...filterForm.data, ...overrides };
    router.get('/administration/user-level', data, {
      preserveState: true,
      replace: true,
    });
  };

  const resetFilters = () => {
    filterForm.setData({
      search: '',
      perPage: '10',
    });
    router.get('/administration/user-level', {}, { replace: true });
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
    levelForm.post('/administration/user-level', {
      onSuccess: () => {
        setShowLevelModal(false);
        setEditingId(null);
      },
    });
  };

  const openAccessModal = (level: LevelRow) => {
    setAccessTargetLevel({ id: level.levelId, name: level.levelName });

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
      '/administration/user-level/menu-access',
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
      <Head title="User Levels - Administration" />

      <div className="space-y-3">
        {/* TOP FILTER & ACTION CARD */}
        <div className="rounded-xl border border-border bg-card p-3 sm:p-4 shadow-2xs">
          {/* HEADER */}
          <div className="flex flex-col sm:flex-row sm:items-center justify-between gap-2 border-b border-border/80 pb-3 mb-3">
            <div className="flex items-center gap-2.5">
              <div className="size-8 rounded-lg bg-emerald-500/10 text-emerald-700 dark:text-emerald-400 flex items-center justify-center font-bold">
                <Shield className="size-4.5" />
              </div>
              <div>
                <h1 className="text-sm font-bold tracking-tight text-foreground flex items-center gap-2">
                  <span>User Level Directory</span>
                  <span className="rounded-full bg-emerald-500/10 text-emerald-700 dark:text-emerald-400 font-mono text-[10px] font-bold px-2 py-0.2 border border-emerald-500/20">
                    {userLevels.total} Total Levels
                  </span>
                </h1>
                <p className="text-[11px] text-muted-foreground">
                  Create and configure role levels, operational statuses, and sidebar menu access controls.
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
              <button
                type="button"
                onClick={openCreateModal}
                className="h-8 inline-flex items-center gap-1.5 rounded-lg bg-emerald-600 text-white px-3 text-xs font-semibold hover:bg-emerald-700 transition shadow-xs cursor-pointer"
              >
                <Plus className="size-3.5" />
                <span>Create User Level</span>
              </button>
            </div>
          </div>

          {/* FILTERS FORM */}
          <div className="grid gap-2.5 sm:grid-cols-2 lg:grid-cols-4">
            {/* Search Input */}
            <div className="space-y-1 sm:col-span-2">
              <label className="text-[11px] font-semibold text-muted-foreground">Search User Level</label>
              <div className="relative">
                <Search className="size-3.5 absolute left-2.5 top-1/2 -translate-y-1/2 text-muted-foreground" />
                <input
                  type="text"
                  value={filterForm.data.search}
                  onChange={(e) => filterForm.setData('search', e.target.value)}
                  onKeyDown={(e) => e.key === 'Enter' && submitFilters()}
                  placeholder="Search by user level title..."
                  className="h-8 w-full rounded-lg border border-input bg-background pl-8 pr-7 text-xs text-foreground placeholder:text-muted-foreground/60 outline-hidden focus:ring-2 focus:ring-ring"
                />
                {filterForm.data.search && (
                  <button
                    type="button"
                    onClick={() => {
                      filterForm.setData('search', '');
                      submitFilters({ search: '' });
                    }}
                    className="absolute right-2 top-1/2 -translate-y-1/2 text-muted-foreground hover:text-foreground"
                  >
                    <X className="size-3" />
                  </button>
                )}
              </div>
            </div>

            {/* Records per page */}
            <div className="space-y-1">
              <label className="text-[11px] font-semibold text-muted-foreground">Per Page</label>
              <select
                value={filterForm.data.perPage}
                onChange={(e) => {
                  filterForm.setData('perPage', e.target.value);
                  submitFilters({ perPage: e.target.value });
                }}
                className="h-8 w-full rounded-lg border border-input bg-background px-2.5 text-xs text-foreground outline-hidden focus:ring-2 focus:ring-ring cursor-pointer"
              >
                {perPageOptions.map((opt) => (
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
            <table className="w-full min-w-[700px] border-collapse text-xs text-left">
              <thead>
                <tr className="bg-muted/60 text-[11px] font-bold uppercase tracking-wider text-muted-foreground border-b border-border">
                  <th className="px-3 py-2 text-center w-12 border-r border-border">#</th>
                  <th className="px-3 py-2 border-r border-border">User Level Name</th>
                  <th className="px-3 py-2 border-r border-border">Sidebar Menu Access</th>
                  <th className="px-3 py-2 text-center w-28 border-r border-border">Status</th>
                  <th className="px-3 py-2 text-center w-36">Actions</th>
                </tr>
              </thead>
              <tbody className="divide-y divide-border">
                {userLevels.data.length === 0 ? (
                  <tr>
                    <td colSpan={5} className="px-4 py-12 text-center">
                      <div className="flex flex-col items-center justify-center space-y-2">
                        <div className="size-10 rounded-full bg-muted flex items-center justify-center text-muted-foreground">
                          <Shield className="size-5" />
                        </div>
                        <p className="text-xs font-bold text-foreground">No user levels found</p>
                        <p className="text-[11px] text-muted-foreground max-w-sm">
                          No role level records matched your search query.
                        </p>
                      </div>
                    </td>
                  </tr>
                ) : (
                  userLevels.data.map((row, index) => (
                    <tr key={row.levelId} className="hover:bg-muted/30 transition-colors">
                      <td className="px-3 py-2 text-center font-mono text-[11px] text-muted-foreground border-r border-border">
                        {(userLevels.from ?? 1) + index}
                      </td>

                      <td className="px-3 py-2 font-bold text-foreground border-r border-border">
                        {row.levelName}
                      </td>

                      <td className="px-3 py-2 border-r border-border">
                        {row.menuAccessSummary.isAll ? (
                          <span className="inline-flex items-center rounded-full bg-emerald-500/10 text-emerald-700 dark:text-emerald-300 font-mono text-[10px] font-bold px-2 py-0.2 border border-emerald-500/20">
                            Full Access (All Items)
                          </span>
                        ) : (
                          <span className="inline-flex items-center rounded-full bg-sky-500/10 text-sky-700 dark:text-sky-300 font-mono text-[10px] font-bold px-2 py-0.2 border border-sky-500/20">
                            {row.menuAccessSummary.count} of {row.menuAccessSummary.total} Menu Items
                          </span>
                        )}
                      </td>

                      <td className="px-3 py-2 text-center border-r border-border">
                        {row.isStatus === 1 ? (
                          <span className="inline-flex items-center rounded-full bg-emerald-500/10 text-emerald-700 dark:text-emerald-300 font-mono text-[10px] font-bold px-2 py-0.2 border border-emerald-500/20">
                            Active
                          </span>
                        ) : (
                          <span className="inline-flex items-center rounded-full bg-muted text-muted-foreground font-mono text-[10px] font-bold px-2 py-0.2 border border-border">
                            Inactive
                          </span>
                        )}
                      </td>

                      <td className="px-3 py-2 text-center">
                        <div className="inline-flex items-center justify-center gap-1">
                          <button
                            type="button"
                            onClick={() => openAccessModal(row)}
                            className="px-2 py-0.5 rounded-md border border-input bg-background text-[11px] font-medium text-foreground hover:bg-muted transition cursor-pointer"
                            title="Menu Access"
                          >
                            Access
                          </button>
                          <button
                            type="button"
                            onClick={() => openEditModal(row)}
                            title="Edit"
                            className="p-1 rounded-md text-muted-foreground hover:text-foreground hover:bg-muted transition cursor-pointer"
                          >
                            <Pencil className="size-3.5" />
                          </button>
                          <button
                            type="button"
                            onClick={() => setDeletingId(row.levelId)}
                            title="Delete"
                            className="p-1 rounded-md text-rose-600 hover:bg-rose-500/10 transition cursor-pointer"
                          >
                            <Trash2 className="size-3.5" />
                          </button>
                        </div>
                      </td>
                    </tr>
                  ))
                )}
              </tbody>
            </table>
          </div>

          {/* PAGINATION FOOTER */}
          {userLevels.lastPage > 1 && (
            <div className="border-t border-border px-3.5 py-2.5 flex flex-col sm:flex-row sm:items-center justify-between gap-2.5 bg-muted/20">
              <div className="text-[11px] text-muted-foreground">
                Showing <span className="font-bold text-foreground">{userLevels.from ?? 0}</span> to{' '}
                <span className="font-bold text-foreground">
                  {Math.min((userLevels.from ?? 1) + userLevels.data.length - 1, userLevels.total)}
                </span>{' '}
                of <span className="font-bold text-foreground">{userLevels.total}</span> user levels
              </div>

              <div className="flex items-center gap-1 flex-wrap">
                {/* Previous */}
                {userLevels.currentPage === 1 ? (
                  <span className="h-7 min-w-7 px-2 rounded-md flex items-center justify-center text-[11px] text-muted-foreground/50 border border-transparent select-none">
                    <ChevronLeft className="size-3.5" />
                  </span>
                ) : (
                  <button
                    type="button"
                    onClick={() =>
                      router.get(
                        '/administration/user-level',
                        { ...filterForm.data, page: userLevels.currentPage - 1 },
                        { replace: true, preserveState: true }
                      )
                    }
                    className="h-7 min-w-7 px-2 rounded-md flex items-center justify-center text-[11px] font-medium border border-input bg-background text-foreground hover:bg-muted transition"
                  >
                    <ChevronLeft className="size-3.5" />
                  </button>
                )}

                {/* Pages */}
                {Array.from({ length: userLevels.lastPage }, (_, i) => i + 1).map((page) => {
                  const isActive = page === userLevels.currentPage;
                  return (
                    <button
                      key={page}
                      type="button"
                      onClick={() =>
                        router.get(
                          '/administration/user-level',
                          { ...filterForm.data, page },
                          { replace: true, preserveState: true }
                        )
                      }
                      className={`h-7 min-w-7 px-2 rounded-md flex items-center justify-center text-[11px] font-medium transition-colors ${
                        isActive
                          ? 'bg-emerald-600 text-white font-bold shadow-2xs'
                          : 'border border-input bg-background text-foreground hover:bg-muted'
                      }`}
                    >
                      {page}
                    </button>
                  );
                })}

                {/* Next */}
                {userLevels.currentPage === userLevels.lastPage ? (
                  <span className="h-7 min-w-7 px-2 rounded-md flex items-center justify-center text-[11px] text-muted-foreground/50 border border-transparent select-none">
                    <ChevronRight className="size-3.5" />
                  </span>
                ) : (
                  <button
                    type="button"
                    onClick={() =>
                      router.get(
                        '/administration/user-level',
                        { ...filterForm.data, page: userLevels.currentPage + 1 },
                        { replace: true, preserveState: true }
                      )
                    }
                    className="h-7 min-w-7 px-2 rounded-md flex items-center justify-center text-[11px] font-medium border border-input bg-background text-foreground hover:bg-muted transition"
                  >
                    <ChevronRight className="size-3.5" />
                  </button>
                )}
              </div>
            </div>
          )}
        </div>

        {/* Modal: Create & Edit User Level */}
        {showLevelModal ? (
          <div className="fixed inset-0 z-50 flex items-center justify-center bg-black/60 backdrop-blur-xs p-4 animate-in fade-in duration-150">
            <div className="w-full max-w-md rounded-xl border border-border bg-card p-5 shadow-2xl space-y-4">
              <div className="flex items-start justify-between">
                <div>
                  <h3 className="text-sm font-bold text-foreground">
                    {editingId ? 'Edit User Level' : 'Create User Level'}
                  </h3>
                  <p className="text-xs text-muted-foreground mt-0.5">
                    Define role level title and active status.
                  </p>
                </div>
                <button
                  type="button"
                  onClick={() => setShowLevelModal(false)}
                  className="text-muted-foreground hover:text-foreground p-1 rounded-md transition"
                >
                  <X className="size-4" />
                </button>
              </div>

              <form onSubmit={handleSaveLevel} className="space-y-3">
                <div className="space-y-1">
                  <label className="text-[11px] font-semibold text-muted-foreground">Level Name</label>
                  <input
                    value={levelForm.data.level_name}
                    onChange={(e) => levelForm.setData('level_name', e.target.value)}
                    placeholder="E.g., Administrator, Reviewer, Encoder"
                    className="h-8 w-full rounded-lg border border-input bg-background px-2.5 text-xs text-foreground outline-hidden focus:ring-2 focus:ring-ring"
                    required
                  />
                </div>

                <div className="space-y-1">
                  <label className="text-[11px] font-semibold text-muted-foreground">Status</label>
                  <select
                    value={levelForm.data.is_status}
                    onChange={(e) => levelForm.setData('is_status', e.target.value)}
                    className="h-8 w-full rounded-lg border border-input bg-background px-2.5 text-xs text-foreground outline-hidden focus:ring-2 focus:ring-ring cursor-pointer"
                  >
                    <option value="1">Active</option>
                    <option value="0">Inactive</option>
                  </select>
                </div>

                <div className="flex justify-end gap-2 pt-3 border-t border-border">
                  <button
                    type="button"
                    onClick={() => setShowLevelModal(false)}
                    className="px-3 py-1.5 rounded-lg border border-input bg-background text-xs font-semibold text-foreground hover:bg-muted transition"
                  >
                    Cancel
                  </button>
                  <button
                    type="submit"
                    disabled={levelForm.processing}
                    className="px-3.5 py-1.5 rounded-lg bg-emerald-600 hover:bg-emerald-700 text-white text-xs font-semibold shadow-xs transition"
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
          <div className="fixed inset-0 z-50 flex items-center justify-center bg-black/60 backdrop-blur-xs p-4 animate-in fade-in duration-150">
            <div className="w-full max-w-2xl max-h-[85vh] overflow-y-auto rounded-xl border border-border bg-card p-5 shadow-2xl space-y-4">
              <div className="flex items-center justify-between border-b border-border pb-3">
                <div>
                  <h3 className="text-sm font-bold text-foreground">
                    Manage Sidebar Menu Access
                  </h3>
                  <p className="text-xs font-semibold text-emerald-600 dark:text-emerald-400">
                    User Level: {accessTargetLevel.name}
                  </p>
                </div>

                <div className="flex gap-1.5">
                  <button
                    type="button"
                    onClick={handleSelectAllMenuAccess}
                    className="px-2.5 py-1 rounded-md border border-input bg-background text-xs font-medium text-foreground hover:bg-muted transition"
                  >
                    Select All
                  </button>
                  <button
                    type="button"
                    onClick={handleDeselectAllMenuAccess}
                    className="px-2.5 py-1 rounded-md border border-input bg-background text-xs font-medium text-foreground hover:bg-muted transition"
                  >
                    Deselect All
                  </button>
                  <button
                    type="button"
                    onClick={() => setShowAccessModal(false)}
                    className="text-muted-foreground hover:text-foreground p-1 rounded-md transition"
                  >
                    <X className="size-4" />
                  </button>
                </div>
              </div>

              {/* Menu Filter */}
              <div>
                <input
                  value={menuSearch}
                  onChange={(e) => setMenuSearch(e.target.value)}
                  placeholder="Filter menu items..."
                  className="h-8 w-full rounded-lg border border-input bg-background px-2.5 text-xs text-foreground outline-hidden focus:ring-2 focus:ring-ring"
                />
              </div>

              {/* Menu Tree List */}
              <div className="max-h-80 overflow-y-auto rounded-lg border border-border bg-muted/20 p-2.5 space-y-1">
                {filteredMenuRows.map((item) => {
                  const isChecked = selectedMenuIds.includes(item.id);
                  return (
                    <div
                      key={item.id}
                      style={{ paddingLeft: `${item.depth * 1.25}rem` }}
                      className="flex items-center gap-2 rounded-lg border border-border/60 bg-card p-2 shadow-2xs"
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
                        className="size-3.5 rounded border-input text-emerald-600 focus:ring-emerald-500"
                      />
                      <div className="min-w-0 flex-1">
                        <p className="text-xs font-bold text-foreground">
                          {item.depth > 0 ? <span className="font-mono text-muted-foreground">— </span> : null}
                          {item.label}
                        </p>
                        <p className="text-[10px] font-mono text-muted-foreground">
                          {item.href || item.key || '(Parent Group)'}
                        </p>
                      </div>
                    </div>
                  );
                })}
              </div>

              <div className="flex justify-end gap-2 pt-3 border-t border-border">
                <button
                  type="button"
                  onClick={() => setShowAccessModal(false)}
                  className="px-3 py-1.5 rounded-lg border border-input bg-background text-xs font-semibold text-foreground hover:bg-muted transition"
                >
                  Cancel
                </button>
                <button
                  type="button"
                  onClick={handleSaveMenuAccess}
                  className="px-3.5 py-1.5 rounded-lg bg-emerald-600 hover:bg-emerald-700 text-white text-xs font-semibold shadow-xs transition"
                >
                  Save Access Rights
                </button>
              </div>
            </div>
          </div>
        ) : null}

        {/* Delete Confirmation Modal */}
        {deletingId ? (
          <div className="fixed inset-0 z-50 flex items-center justify-center bg-black/60 backdrop-blur-xs p-4 animate-in fade-in duration-150">
            <div className="w-full max-w-md rounded-xl border border-border bg-card p-5 shadow-2xl space-y-4">
              <div className="flex items-start justify-between">
                <div>
                  <h3 className="text-sm font-bold text-foreground">Delete User Level</h3>
                  <p className="text-xs text-muted-foreground mt-0.5">
                    Are you sure you want to delete this user level? Users assigned to this level will lose access permissions.
                  </p>
                </div>
                <button
                  type="button"
                  onClick={() => setDeletingId(null)}
                  className="text-muted-foreground hover:text-foreground p-1 rounded-md transition"
                >
                  <X className="size-4" />
                </button>
              </div>

              <div className="flex justify-end gap-2 pt-2 border-t border-border">
                <button
                  type="button"
                  onClick={() => setDeletingId(null)}
                  className="px-3 py-1.5 rounded-lg border border-input bg-background text-xs font-semibold text-foreground hover:bg-muted transition"
                >
                  Cancel
                </button>
                <button
                  type="button"
                  onClick={() => {
                    router.delete(`/administration/user-level/${deletingId}`, {
                      onSuccess: () => setDeletingId(null),
                    });
                  }}
                  className="px-3.5 py-1.5 rounded-lg bg-rose-600 hover:bg-rose-700 text-white text-xs font-semibold shadow-xs transition"
                >
                  Confirm &amp; Delete
                </button>
              </div>
            </div>
          </div>
        ) : null}
      </div>
    </AppLayout>
  );
}

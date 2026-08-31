import { Head, router, useForm } from '@inertiajs/react';
import { useState } from 'react';
import AppLayout from '../../Layouts/AppLayout';

type RowItem = {
  id: number;
  parent_id: number | null;
  label: string;
  key: string | null;
  href: string | null;
  icon: string | null;
  badge_text: string | null;
  badge_cls: string | null;
  sort_order: number;
  is_active: boolean;
  user_levels: number[];
};

type Row = {
  item: RowItem;
  depth: number;
};

type UserLevelOption = {
  level_id: number;
  level_name: string;
};

type ParentOption = {
  id: number;
  label: string;
};

type Props = {
  appName: string;
  user: { name: string; email: string } | null;
  filters: { search: string; status: string; hierarchy: string; userLevel: string };
  stats: { total: number; active: number; inactive: number; nested: number };
  rows: Row[];
  availableUserLevels: UserLevelOption[];
  parentOptions: ParentOption[];
  availableIcons: string[];
  badgeColors: string[];
  navigation?: { sidebar?: any[] };
};

export default function SidebarMenu({
  appName,
  user,
  filters,
  stats,
  rows,
  availableUserLevels,
  parentOptions,
  availableIcons,
  badgeColors,
  navigation,
}: Props) {
  const [showModal, setShowModal] = useState(false);
  const [editingId, setEditingId] = useState<number | null>(null);
  const [deletingId, setDeletingId] = useState<number | null>(null);
  const [iconSearch, setIconSearch] = useState('');

  const filterForm = useForm({
    search: filters.search,
    status: filters.status,
    hierarchy: filters.hierarchy,
    userLevel: filters.userLevel,
  });

  const itemForm = useForm({
    parent_id: '' as string | number,
    label: '',
    key: '',
    href: '',
    icon: '',
    badge_text: '',
    badge_cls: '',
    sort_order: 0,
    is_active: true,
    user_levels: [] as number[],
  });

  const submitFilters = () => {
    router.get('/inertia/settings/sidebar-menu', filterForm.data, {
      preserveState: true,
      replace: true,
    });
  };

  const resetFilters = () => {
    filterForm.setData({ search: '', status: 'all', hierarchy: 'all', userLevel: 'all' });
    router.get('/inertia/settings/sidebar-menu', { search: '', status: 'all', hierarchy: 'all', userLevel: 'all' }, {
      preserveState: true,
      replace: true,
    });
  };

  const openCreateModal = (parentId: number | null = null) => {
    setEditingId(null);
    itemForm.setData({
      parent_id: parentId ?? '',
      label: '',
      key: '',
      href: '',
      icon: '',
      badge_text: '',
      badge_cls: '',
      sort_order: 0,
      is_active: true,
      user_levels: [],
    });
    setShowModal(true);
  };

  const openEditModal = (item: RowItem) => {
    setEditingId(item.id);
    itemForm.setData({
      parent_id: item.parent_id ?? '',
      label: item.label,
      key: item.key ?? '',
      href: item.href ?? '',
      icon: item.icon ?? '',
      badge_text: item.badge_text ?? '',
      badge_cls: item.badge_cls ?? '',
      sort_order: item.sort_order,
      is_active: item.is_active,
      user_levels: item.user_levels || [],
    });
    setShowModal(true);
  };

  const handleFormSubmit = (e: React.FormEvent) => {
    e.preventDefault();
    if (editingId) {
      itemForm.patch(`/inertia/settings/sidebar-menu/${editingId}`, {
        onSuccess: () => {
          setShowModal(false);
          setEditingId(null);
        },
      });
    } else {
      itemForm.post('/inertia/settings/sidebar-menu', {
        onSuccess: () => {
          setShowModal(false);
        },
      });
    }
  };

  const filteredIcons = availableIcons.filter((icon) =>
    icon.toLowerCase().includes(iconSearch.toLowerCase())
  );

  return (
    <AppLayout appName={appName} user={user} sidebar={navigation?.sidebar ?? []}>
      <Head title="Sidebar Menu" />
      <div className="space-y-6 rounded-[2rem] border border-slate-200 bg-white p-6 shadow-[0_20px_60px_rgba(15,23,42,0.08)] sm:p-8">
        {/* Top Header & Actions */}
        <div className="flex flex-col justify-between gap-4 border-b border-slate-100 pb-6 sm:flex-row sm:items-center">
          <div>
            <p className="text-xs font-semibold uppercase tracking-[0.3em] text-cyan-700">Settings</p>
            <h2 className="text-2xl font-bold tracking-tight text-slate-900">Sidebar Menu Management</h2>
            <p className="max-w-2xl text-sm leading-6 text-slate-600">
              Configure system sidebar navigation, item hierarchy, icon themes, and user level permissions.
            </p>
          </div>

          <button
            type="button"
            onClick={() => openCreateModal()}
            className="rounded-full bg-cyan-700 px-5 py-2.5 text-sm font-semibold text-white shadow-md transition hover:bg-cyan-800"
          >
            + Create Menu Item
          </button>
        </div>

        {/* Statistics Widgets Banner */}
        <div className="grid gap-4 sm:grid-cols-2 lg:grid-cols-4">
          <div className="rounded-2xl border border-slate-200 bg-slate-50/80 p-4">
            <p className="text-xs font-semibold uppercase tracking-wider text-slate-500">Total Menu Items</p>
            <p className="mt-1 text-2xl font-bold text-slate-900">{stats.total}</p>
          </div>

          <div className="rounded-2xl border border-slate-200 bg-slate-50/80 p-4">
            <p className="text-xs font-semibold uppercase tracking-wider text-slate-500">Active Items</p>
            <p className="mt-1 text-2xl font-bold text-emerald-700">{stats.active}</p>
          </div>

          <div className="rounded-2xl border border-slate-200 bg-slate-50/80 p-4">
            <p className="text-xs font-semibold uppercase tracking-wider text-slate-500">Inactive Items</p>
            <p className="mt-1 text-2xl font-bold text-slate-500">{stats.inactive}</p>
          </div>

          <div className="rounded-2xl border border-slate-200 bg-slate-50/80 p-4">
            <p className="text-xs font-semibold uppercase tracking-wider text-slate-500">Nested Submenus</p>
            <p className="mt-1 text-2xl font-bold text-cyan-700">{stats.nested}</p>
          </div>
        </div>

        {/* Filter Controls Toolbar */}
        <div className="grid gap-3 sm:grid-cols-2 md:grid-cols-4 lg:grid-cols-5">
          <input
            value={filterForm.data.search}
            onChange={(e) => filterForm.setData('search', e.target.value)}
            placeholder="Search by label, key, href..."
            className="h-10 rounded-xl border border-slate-200 px-3 text-sm focus:border-cyan-600 focus:outline-none"
          />

          <select
            value={filterForm.data.status}
            onChange={(e) => filterForm.setData('status', e.target.value)}
            className="h-10 rounded-xl border border-slate-200 px-3 text-sm focus:border-cyan-600 focus:outline-none"
          >
            <option value="all">All Statuses</option>
            <option value="active">Active Only</option>
            <option value="inactive">Inactive Only</option>
          </select>

          <select
            value={filterForm.data.hierarchy}
            onChange={(e) => filterForm.setData('hierarchy', e.target.value)}
            className="h-10 rounded-xl border border-slate-200 px-3 text-sm focus:border-cyan-600 focus:outline-none"
          >
            <option value="all">All Levels</option>
            <option value="root">Top Level Only</option>
            <option value="nested">Nested Only</option>
          </select>

          <select
            value={filterForm.data.userLevel}
            onChange={(e) => filterForm.setData('userLevel', e.target.value)}
            className="h-10 rounded-xl border border-slate-200 px-3 text-sm focus:border-cyan-600 focus:outline-none"
          >
            <option value="all">All User Levels</option>
            {availableUserLevels.map((lvl) => (
              <option key={lvl.level_id} value={lvl.level_id}>
                {lvl.level_name}
              </option>
            ))}
          </select>

          <div className="flex gap-2">
            <button
              type="button"
              onClick={submitFilters}
              className="h-10 flex-1 rounded-xl bg-slate-900 text-sm font-medium text-white hover:bg-slate-800"
            >
              Filter
            </button>
            <button
              type="button"
              onClick={resetFilters}
              className="h-10 rounded-xl border border-slate-300 px-3 text-sm font-medium text-slate-700 hover:bg-slate-50"
            >
              Reset
            </button>
          </div>
        </div>

        {/* Menu Items Table */}
        {rows.length === 0 ? (
          <div className="rounded-2xl border border-dashed border-slate-200 bg-slate-50 p-12 text-center text-slate-500">
            No sidebar menu items found matching the selected filters.
          </div>
        ) : (
          <div className="overflow-x-auto rounded-2xl border border-slate-200 bg-white">
            <table className="w-full text-left text-sm">
              <thead className="border-b border-slate-200 bg-slate-50/50 text-xs font-semibold uppercase text-slate-500">
                <tr>
                  <th className="px-4 py-3">Label</th>
                  <th className="px-4 py-3">Route / Href</th>
                  <th className="px-4 py-3">Icon</th>
                  <th className="px-4 py-3">User Levels Access</th>
                  <th className="px-4 py-3 text-center">Order</th>
                  <th className="px-4 py-3 text-center">Status</th>
                  <th className="px-4 py-3 text-right">Actions</th>
                </tr>
              </thead>
              <tbody className="divide-y divide-slate-100">
                {rows.map(({ item, depth }) => (
                  <tr key={item.id} className="hover:bg-slate-50/50">
                    <td className="px-4 py-3">
                      <div
                        style={{ paddingLeft: `${depth * 1.25}rem` }}
                        className="flex items-center gap-2 font-semibold text-slate-900"
                      >
                        {depth > 0 ? <span className="text-slate-400 font-mono">— </span> : null}
                        <span>{item.label}</span>
                        {item.badge_text ? (
                          <span className="rounded-full bg-slate-100 px-2 py-0.5 text-[10px] font-bold text-slate-700">
                            {item.badge_text}
                          </span>
                        ) : null}
                      </div>
                    </td>

                    <td className="px-4 py-3 text-xs text-slate-600 font-mono">
                      {item.href || item.key || <span className="text-slate-400 font-sans italic">Parent Group</span>}
                    </td>

                    <td className="px-4 py-3 text-xs text-slate-600">
                      {item.icon ? (
                        <span className="rounded-md bg-slate-100 px-2 py-1 font-mono text-slate-700">
                          {item.icon}
                        </span>
                      ) : (
                        <span className="text-slate-400 italic">None</span>
                      )}
                    </td>

                    <td className="px-4 py-3 text-xs">
                      {item.user_levels && item.user_levels.length > 0 ? (
                        <div className="flex flex-wrap gap-1">
                          {item.user_levels.map((lvlId) => {
                            const lvl = availableUserLevels.find((l) => l.level_id === lvlId);
                            return (
                              <span
                                key={lvlId}
                                className="rounded-full bg-cyan-50 px-2 py-0.5 text-[10px] font-semibold text-cyan-800 border border-cyan-200"
                              >
                                {lvl ? lvl.level_name : `Level #${lvlId}`}
                              </span>
                            );
                          })}
                        </div>
                      ) : (
                        <span className="text-slate-400 italic">All Levels</span>
                      )}
                    </td>

                    <td className="px-4 py-3 text-center text-xs font-semibold text-slate-700">{item.sort_order}</td>

                    <td className="px-4 py-3 text-center">
                      <span
                        className={`inline-flex rounded-full px-2.5 py-0.5 text-[10px] font-bold ${
                          item.is_active
                            ? 'bg-emerald-100 text-emerald-800'
                            : 'bg-slate-100 text-slate-500'
                        }`}
                      >
                        {item.is_active ? 'Active' : 'Inactive'}
                      </span>
                    </td>

                    <td className="px-4 py-3 text-right">
                      <div className="flex justify-end gap-2">
                        <button
                          type="button"
                          onClick={() => openCreateModal(item.id)}
                          className="rounded-full border border-slate-200 px-2.5 py-1 text-xs font-semibold text-slate-700 hover:bg-slate-50"
                        >
                          + Child
                        </button>
                        <button
                          type="button"
                          onClick={() => openEditModal(item)}
                          className="rounded-full border border-slate-300 px-3 py-1 text-xs font-semibold text-slate-700 hover:bg-slate-50"
                        >
                          Edit
                        </button>
                        <button
                          type="button"
                          onClick={() => setDeletingId(item.id)}
                          className="rounded-full border border-rose-200 px-3 py-1 text-xs font-semibold text-rose-700 hover:bg-rose-50"
                        >
                          Delete
                        </button>
                      </div>
                    </td>
                  </tr>
                ))}
              </tbody>
            </table>
          </div>
        )}

        {/* Modal: Create & Edit Form */}
        {showModal ? (
          <div className="fixed inset-0 z-50 flex items-center justify-center bg-slate-900/40 p-4 backdrop-blur-sm">
            <div className="w-full max-w-2xl max-h-[90vh] overflow-y-auto rounded-3xl bg-white p-6 shadow-xl">
              <h3 className="text-lg font-bold text-slate-900">
                {editingId ? 'Edit Sidebar Menu Item' : 'Create Sidebar Menu Item'}
              </h3>

              <form onSubmit={handleFormSubmit} className="mt-4 space-y-4">
                <div className="grid gap-4 sm:grid-cols-2">
                  <div>
                    <label className="text-xs font-semibold text-slate-600">Parent Menu Item</label>
                    <select
                      value={itemForm.data.parent_id}
                      onChange={(e) => itemForm.setData('parent_id', e.target.value)}
                      className="mt-1 h-10 w-full rounded-xl border border-slate-300 px-3 text-sm focus:border-cyan-600 focus:outline-none"
                    >
                      <option value="">(None - Top Level)</option>
                      {parentOptions.map((opt) => (
                        <option key={opt.id} value={opt.id}>
                          {opt.label}
                        </option>
                      ))}
                    </select>
                  </div>

                  <div>
                    <label className="text-xs font-semibold text-slate-600">Display Label</label>
                    <input
                      value={itemForm.data.label}
                      onChange={(e) => itemForm.setData('label', e.target.value)}
                      placeholder="E.g., Dashboard, Annual Target"
                      className="mt-1 h-10 w-full rounded-xl border border-slate-300 px-3 text-sm focus:border-cyan-600 focus:outline-none"
                      required
                    />
                  </div>
                </div>

                <div className="grid gap-4 sm:grid-cols-2">
                  <div>
                    <label className="text-xs font-semibold text-slate-600">Target URL / Href</label>
                    <input
                      value={itemForm.data.href}
                      onChange={(e) => itemForm.setData('href', e.target.value)}
                      placeholder="E.g., /ipcrf/annualtarget"
                      className="mt-1 h-10 w-full rounded-xl border border-slate-300 px-3 text-sm focus:border-cyan-600 focus:outline-none"
                    />
                  </div>

                  <div>
                    <label className="text-xs font-semibold text-slate-600">Route Key (Optional)</label>
                    <input
                      value={itemForm.data.key}
                      onChange={(e) => itemForm.setData('key', e.target.value)}
                      placeholder="E.g., annualtarget.index"
                      className="mt-1 h-10 w-full rounded-xl border border-slate-300 px-3 text-sm focus:border-cyan-600 focus:outline-none"
                    />
                  </div>
                </div>

                {/* Icon Selection */}
                <div>
                  <div className="flex items-center justify-between">
                    <label className="text-xs font-semibold text-slate-600">Icon Name</label>
                    {itemForm.data.icon ? (
                      <span className="text-xs font-bold text-cyan-800 bg-cyan-50 px-2 py-0.5 rounded border border-cyan-200">
                        Selected: {itemForm.data.icon}
                      </span>
                    ) : null}
                  </div>
                  <input
                    value={iconSearch}
                    onChange={(e) => setIconSearch(e.target.value)}
                    placeholder="Search available icons..."
                    className="mt-1 h-9 w-full rounded-xl border border-slate-300 px-3 text-xs"
                  />
                  <div className="mt-2 max-h-32 overflow-y-auto rounded-xl border border-slate-200 bg-slate-50 p-2 flex flex-wrap gap-1">
                    <button
                      type="button"
                      onClick={() => itemForm.setData('icon', '')}
                      className={`px-2 py-1 text-xs rounded ${
                        !itemForm.data.icon ? 'bg-slate-900 text-white font-bold' : 'bg-white text-slate-700 border'
                      }`}
                    >
                      (No Icon)
                    </button>
                    {filteredIcons.slice(0, 30).map((icon) => (
                      <button
                        key={icon}
                        type="button"
                        onClick={() => itemForm.setData('icon', icon)}
                        className={`px-2 py-1 text-xs rounded ${
                          itemForm.data.icon === icon
                            ? 'bg-cyan-700 text-white font-bold'
                            : 'bg-white text-slate-700 border border-slate-200 hover:bg-slate-100'
                        }`}
                      >
                        {icon}
                      </button>
                    ))}
                  </div>
                </div>

                <div className="grid gap-4 sm:grid-cols-3">
                  <div>
                    <label className="text-xs font-semibold text-slate-600">Badge Text</label>
                    <input
                      value={itemForm.data.badge_text}
                      onChange={(e) => itemForm.setData('badge_text', e.target.value)}
                      placeholder="New, 5, Hot..."
                      className="mt-1 h-10 w-full rounded-xl border border-slate-300 px-3 text-sm"
                    />
                  </div>

                  <div>
                    <label className="text-xs font-semibold text-slate-600">Badge Color</label>
                    <select
                      value={itemForm.data.badge_cls}
                      onChange={(e) => itemForm.setData('badge_cls', e.target.value)}
                      className="mt-1 h-10 w-full rounded-xl border border-slate-300 px-3 text-sm"
                    >
                      <option value="">Default</option>
                      {badgeColors.map((color) => (
                        <option key={color} value={color}>
                          {color}
                        </option>
                      ))}
                    </select>
                  </div>

                  <div>
                    <label className="text-xs font-semibold text-slate-600">Sort Order</label>
                    <input
                      type="number"
                      value={itemForm.data.sort_order}
                      onChange={(e) => itemForm.setData('sort_order', Number(e.target.value))}
                      className="mt-1 h-10 w-full rounded-xl border border-slate-300 px-3 text-sm"
                      required
                    />
                  </div>
                </div>

                {/* User Levels Access Control */}
                <div>
                  <label className="text-xs font-semibold text-slate-600">User Level Restrictions</label>
                  <p className="text-[11px] text-slate-500">
                    Select which user levels can view this menu item. Leave empty to allow all levels.
                  </p>
                  <div className="mt-2 grid gap-2 sm:grid-cols-2 lg:grid-cols-3 rounded-xl border border-slate-200 p-3">
                    {availableUserLevels.map((lvl) => {
                      const isChecked = itemForm.data.user_levels.includes(lvl.level_id);
                      return (
                        <label key={lvl.level_id} className="flex items-center gap-2 text-xs text-slate-700 cursor-pointer">
                          <input
                            type="checkbox"
                            checked={isChecked}
                            onChange={(e) => {
                              if (e.target.checked) {
                                itemForm.setData('user_levels', [...itemForm.data.user_levels, lvl.level_id]);
                              } else {
                                itemForm.setData(
                                  'user_levels',
                                  itemForm.data.user_levels.filter((id) => id !== lvl.level_id)
                                );
                              }
                            }}
                            className="rounded border-slate-300 text-cyan-600 focus:ring-cyan-500"
                          />
                          <span>{lvl.level_name}</span>
                        </label>
                      );
                    })}
                  </div>
                </div>

                <div className="flex items-center gap-2 pt-2">
                  <input
                    type="checkbox"
                    id="is_active_check"
                    checked={itemForm.data.is_active}
                    onChange={(e) => itemForm.setData('is_active', e.target.checked)}
                    className="rounded border-slate-300 text-cyan-600"
                  />
                  <label htmlFor="is_active_check" className="text-xs font-semibold text-slate-800">
                    Active & Visible in Sidebar
                  </label>
                </div>

                <div className="flex justify-end gap-3 pt-4 border-t border-slate-100">
                  <button
                    type="button"
                    onClick={() => setShowModal(false)}
                    className="rounded-full border border-slate-300 px-4 py-2 text-sm font-medium text-slate-700 hover:bg-slate-50"
                  >
                    Cancel
                  </button>
                  <button
                    type="submit"
                    disabled={itemForm.processing}
                    className="rounded-full bg-cyan-700 px-5 py-2 text-sm font-semibold text-white hover:bg-cyan-800"
                  >
                    {editingId ? 'Update Menu Item' : 'Create Menu Item'}
                  </button>
                </div>
              </form>
            </div>
          </div>
        ) : null}

        {/* Delete Confirmation Modal */}
        {deletingId ? (
          <div className="fixed inset-0 z-50 flex items-center justify-center bg-slate-900/40 p-4 backdrop-blur-sm">
            <div className="w-full max-w-md rounded-3xl bg-white p-6 shadow-xl text-center">
              <h3 className="text-lg font-bold text-slate-900">Delete Sidebar Menu Item?</h3>
              <p className="mt-2 text-sm text-slate-600">
                Are you sure you want to delete this menu item and remove it from the sidebar navigation?
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
                    router.delete(`/inertia/settings/sidebar-menu/${deletingId}`, {
                      onSuccess: () => setDeletingId(null),
                    });
                  }}
                  className="rounded-full bg-rose-600 px-5 py-2 text-sm font-semibold text-white hover:bg-rose-700"
                >
                  Delete Item
                </button>
              </div>
            </div>
          </div>
        ) : null}
      </div>
    </AppLayout>
  );
}

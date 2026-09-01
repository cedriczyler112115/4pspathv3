import { Head, router, useForm } from '@inertiajs/react';
import { useState } from 'react';
import AppLayout from '../../Layouts/AppLayout';
import * as LucideIcons from 'lucide-react';
import { LayoutGrid, Plus, RotateCcw, Search, X, Pencil, Trash2, ChevronRight, CornerDownRight, AlertCircle } from 'lucide-react';

function isValidIconComponent(Comp: any): boolean {
  if (!Comp) return false;
  return typeof Comp === 'function' || (typeof Comp === 'object' && (Comp.$$typeof || Comp.render));
}

function getLucideIcon(name?: string | null) {
  if (!name) return null;

  try {
    if ((LucideIcons as any)[name] && isValidIconComponent((LucideIcons as any)[name])) {
      return (LucideIcons as any)[name];
    }

    const pascalName = name
      .split(/[-_]+/)
      .map((word) => word.charAt(0).toUpperCase() + word.slice(1).toLowerCase())
      .join('');

    if ((LucideIcons as any)[pascalName] && isValidIconComponent((LucideIcons as any)[pascalName])) {
      return (LucideIcons as any)[pascalName];
    }

    const aliasMap: Record<string, any> = {
      'dashboard': LucideIcons.LayoutDashboard,
      'chart-bar': LucideIcons.BarChart3,
      'chart-bar-square': LucideIcons.BarChart3,
      'shield-check': LucideIcons.ShieldCheck,
      'list-bullet': LucideIcons.ListOrdered,
      'user': LucideIcons.User,
      'arrow-left-end-on-rectangle': LucideIcons.LogOut,
      'building-library': LucideIcons.Building2,
      'user-group': LucideIcons.Users,
      'users': LucideIcons.UserCheck,
      'book-open-text': LucideIcons.BookOpen,
      'magnifying-glass': LucideIcons.Search,
      'viewfinder-circle': LucideIcons.Target,
      'document-text': LucideIcons.FileText,
      'calendar-date-range': LucideIcons.Calendar,
      'cog': LucideIcons.Settings,
      'cog-6-tooth': LucideIcons.Settings,
      'pencil-square': LucideIcons.Pencil,
      'arrow-path-rounded-square': LucideIcons.RefreshCw,
      'calendar-days': LucideIcons.CalendarDays,
      'clipboard-document-check': LucideIcons.CheckSquare,
      'folder-kanban': LucideIcons.FolderKanban,
      'sparkles': LucideIcons.Sparkles,
      'layers': LucideIcons.Layers,
    };

    if (aliasMap[name.toLowerCase()] && isValidIconComponent(aliasMap[name.toLowerCase()])) {
      return aliasMap[name.toLowerCase()];
    }
  } catch (e) {
    return LucideIcons.Folder;
  }

  return LucideIcons.Folder;
}

function pascalToKebab(str: string): string {
  return str
    .replace(/([a-z0-9])([A-Z])/g, '$1-$2')
    .replace(/([A-Z]+)([A-Z][a-z])/g, '$1-$2')
    .toLowerCase();
}

const allLucideIcons = Object.keys(LucideIcons)
  .filter((key) => {
    if (key === 'default' || key === 'createLucideIcon' || key.startsWith('Lucide') || key === 'icons') return false;
    const item = (LucideIcons as any)[key];
    return isValidIconComponent(item);
  })
  .map((key) => pascalToKebab(key));

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
    search: filters.search || '',
    status: filters.status || 'all',
    hierarchy: filters.hierarchy || 'all',
    userLevel: filters.userLevel || 'all',
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

  const submitFilters = (overrides?: Partial<typeof filterForm.data>) => {
    const data = { ...filterForm.data, ...overrides };
    router.get('/settings/sidebar-menu', data, {
      preserveState: true,
      replace: true,
    });
  };

  const resetFilters = () => {
    filterForm.setData({ search: '', status: 'all', hierarchy: 'all', userLevel: 'all' });
    router.get(
      '/settings/sidebar-menu',
      { search: '', status: 'all', hierarchy: 'all', userLevel: 'all' },
      { preserveState: true, replace: true }
    );
  };

  const openCreateModal = (parentId: number | null = null) => {
    setEditingId(null);
    itemForm.clearErrors();
    itemForm.setData({
      parent_id: parentId ? String(parentId) : '',
      label: '',
      key: '',
      href: '',
      icon: '',
      badge_text: '',
      badge_cls: '',
      sort_order: (rows.length + 1) * 10,
      is_active: true,
      user_levels: [],
    });
    setIconSearch('');
    setShowModal(true);
  };

  const openEditModal = (item: RowItem) => {
    setEditingId(item.id);
    itemForm.clearErrors();
    itemForm.setData({
      parent_id: item.parent_id ? String(item.parent_id) : '',
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
    setIconSearch(item.icon ?? '');
    setShowModal(true);
  };

  const handleFormSubmit = (e: React.FormEvent) => {
    e.preventDefault();
    if (editingId) {
      itemForm.patch(`/settings/sidebar-menu/${editingId}`, {
        onSuccess: () => {
          setShowModal(false);
          setEditingId(null);
        },
      });
    } else {
      itemForm.post('/settings/sidebar-menu', {
        onSuccess: () => {
          setShowModal(false);
        },
      });
    }
  };

  const mergedIconList = Array.from(
    new Set([...allLucideIcons, ...(availableIcons || [])])
  ).sort();

  const filteredIcons = mergedIconList.filter((icon) =>
    icon.toLowerCase().includes(iconSearch.toLowerCase())
  );

  if (iconSearch.trim() && !filteredIcons.some((i) => i.toLowerCase() === iconSearch.trim().toLowerCase())) {
    filteredIcons.unshift(iconSearch.trim());
  }

  return (
    <AppLayout appName={appName} user={user} sidebar={navigation?.sidebar ?? []}>
      <Head title="Sidebar Menu - Settings" />

      <div className="space-y-3">
        {/* TOP FILTER & ACTION CARD */}
        <div className="rounded-xl border border-border bg-card p-3 sm:p-4 shadow-2xs space-y-3">
          {/* HEADER */}
          <div className="flex flex-col sm:flex-row sm:items-center justify-between gap-2 border-b border-border/80 pb-3">
            <div className="flex items-center gap-2.5">
              <div className="size-8 rounded-lg bg-emerald-500/10 text-emerald-700 dark:text-emerald-400 flex items-center justify-center font-bold">
                <LayoutGrid className="size-4.5" />
              </div>
              <div>
                <h1 className="text-sm font-bold tracking-tight text-foreground flex items-center gap-2">
                  <span>Sidebar Menu Management</span>
                  <span className="rounded-full bg-emerald-500/10 text-emerald-700 dark:text-emerald-400 font-mono text-[10px] font-bold px-2 py-0.2 border border-emerald-500/20">
                    {stats.total} Total Items
                  </span>
                </h1>
                <p className="text-[11px] text-muted-foreground">
                  Configure navigation hierarchy, route mappings, badges, and user level permissions.
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
                onClick={() => openCreateModal()}
                className="h-8 inline-flex items-center gap-1.5 rounded-lg bg-emerald-600 text-white px-3 text-xs font-semibold hover:bg-emerald-700 transition shadow-xs cursor-pointer"
              >
                <Plus className="size-3.5" />
                <span>Create Menu Item</span>
              </button>
            </div>
          </div>

          {/* STATS STRIP */}
          <div className="grid grid-cols-2 sm:grid-cols-4 gap-2">
            <div className="rounded-lg border border-border bg-muted/20 px-3 py-2">
              <div className="text-[10px] font-semibold text-muted-foreground uppercase">Total Items</div>
              <div className="text-base font-bold text-foreground mt-0.5">{stats.total}</div>
            </div>
            <div className="rounded-lg border border-border bg-muted/20 px-3 py-2">
              <div className="text-[10px] font-semibold text-muted-foreground uppercase">Active Items</div>
              <div className="text-base font-bold text-emerald-600 dark:text-emerald-400 mt-0.5">{stats.active}</div>
            </div>
            <div className="rounded-lg border border-border bg-muted/20 px-3 py-2">
              <div className="text-[10px] font-semibold text-muted-foreground uppercase">Inactive Items</div>
              <div className="text-base font-bold text-muted-foreground mt-0.5">{stats.inactive}</div>
            </div>
            <div className="rounded-lg border border-border bg-muted/20 px-3 py-2">
              <div className="text-[10px] font-semibold text-muted-foreground uppercase">Nested Submenus</div>
              <div className="text-base font-bold text-sky-600 dark:text-sky-400 mt-0.5">{stats.nested}</div>
            </div>
          </div>

          {/* FILTERS FORM */}
          <div className="grid gap-2.5 sm:grid-cols-2 lg:grid-cols-4">
            {/* Search Input */}
            <div className="space-y-1">
              <label className="text-[11px] font-semibold text-muted-foreground">Search Label / URL</label>
              <div className="relative">
                <Search className="size-3.5 absolute left-2.5 top-1/2 -translate-y-1/2 text-muted-foreground" />
                <input
                  type="text"
                  value={filterForm.data.search}
                  onChange={(e) => filterForm.setData('search', e.target.value)}
                  onKeyDown={(e) => e.key === 'Enter' && submitFilters()}
                  placeholder="Search by label, key, href..."
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

            {/* Status Selector */}
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
                <option value="all">All Statuses</option>
                <option value="active">Active Only</option>
                <option value="inactive">Inactive Only</option>
              </select>
            </div>

            {/* Hierarchy Selector */}
            <div className="space-y-1">
              <label className="text-[11px] font-semibold text-muted-foreground">Hierarchy</label>
              <select
                value={filterForm.data.hierarchy}
                onChange={(e) => {
                  filterForm.setData('hierarchy', e.target.value);
                  submitFilters({ hierarchy: e.target.value });
                }}
                className="h-8 w-full rounded-lg border border-input bg-background px-2.5 text-xs text-foreground outline-hidden focus:ring-2 focus:ring-ring cursor-pointer"
              >
                <option value="all">All Levels</option>
                <option value="root">Top Level Only</option>
                <option value="nested">Nested Only</option>
              </select>
            </div>

            {/* User Level Selector */}
            <div className="space-y-1">
              <label className="text-[11px] font-semibold text-muted-foreground">User Level</label>
              <select
                value={filterForm.data.userLevel}
                onChange={(e) => {
                  filterForm.setData('userLevel', e.target.value);
                  submitFilters({ userLevel: e.target.value });
                }}
                className="h-8 w-full rounded-lg border border-input bg-background px-2.5 text-xs text-foreground outline-hidden focus:ring-2 focus:ring-ring cursor-pointer"
              >
                <option value="all">All User Levels</option>
                {availableUserLevels.map((lvl) => (
                  <option key={lvl.level_id} value={lvl.level_id}>
                    {lvl.level_name}
                  </option>
                ))}
              </select>
            </div>
          </div>
        </div>

        {/* RESULTS TABLE CARD */}
        <div className="rounded-xl border border-border bg-card shadow-2xs overflow-hidden">
          <div className="overflow-x-auto">
            <table className="w-full min-w-[750px] border-collapse text-xs text-left">
              <thead>
                <tr className="bg-muted/60 text-[11px] font-bold uppercase tracking-wider text-muted-foreground border-b border-border">
                  <th className="px-3 py-2 border-r border-border">Menu Label</th>
                  <th className="px-3 py-2 border-r border-border">Route / URL</th>
                  <th className="px-3 py-2 border-r border-border">Icon</th>
                  <th className="px-3 py-2 border-r border-border">Access Roles</th>
                  <th className="px-3 py-2 text-center w-16 border-r border-border">Order</th>
                  <th className="px-3 py-2 text-center w-24 border-r border-border">Status</th>
                  <th className="px-3 py-2 text-center w-36">Actions</th>
                </tr>
              </thead>
              <tbody className="divide-y divide-border">
                {rows.length === 0 ? (
                  <tr>
                    <td colSpan={7} className="px-4 py-12 text-center">
                      <div className="flex flex-col items-center justify-center space-y-2">
                        <div className="size-10 rounded-full bg-muted flex items-center justify-center text-muted-foreground">
                          <LayoutGrid className="size-5" />
                        </div>
                        <p className="text-xs font-bold text-foreground">No menu items found</p>
                        <p className="text-[11px] text-muted-foreground max-w-sm">
                          No sidebar menu records matched your search query.
                        </p>
                      </div>
                    </td>
                  </tr>
                ) : (
                  rows.map(({ item, depth }) => (
                    <tr key={item.id} className="hover:bg-muted/30 transition-colors">
                      <td className="px-3 py-2 border-r border-border">
                        <div
                          style={{ paddingLeft: `${depth * 1.25}rem` }}
                          className="flex items-center gap-1.5 font-bold text-foreground"
                        >
                          {depth > 0 ? <CornerDownRight className="size-3 text-muted-foreground shrink-0" /> : null}
                          <span>{item.label}</span>
                          {item.badge_text ? (
                            <span className="rounded-full bg-muted px-1.5 py-0.2 text-[9px] font-bold text-muted-foreground border border-border">
                              {item.badge_text}
                            </span>
                          ) : null}
                        </div>
                      </td>

                      <td className="px-3 py-2 font-mono text-[11px] text-muted-foreground border-r border-border">
                        {item.href || item.key || <span className="text-muted-foreground/60 italic">(Parent Group)</span>}
                      </td>

                      <td className="px-3 py-2 border-r border-border">
                        {item.icon ? (
                          <div className="inline-flex items-center gap-1.5 rounded-md bg-muted/60 px-2 py-0.5 border border-border">
                            {(() => {
                              const IconComp = getLucideIcon(item.icon);
                              return IconComp ? <IconComp className="size-3.5 text-emerald-600 dark:text-emerald-400 shrink-0" /> : null;
                            })()}
                            <span className="font-mono text-[10px] text-foreground">
                              {item.icon}
                            </span>
                          </div>
                        ) : (
                          <span className="text-muted-foreground/50 italic text-[11px]">None</span>
                        )}
                      </td>

                      <td className="px-3 py-2 border-r border-border">
                        {item.user_levels && item.user_levels.length > 0 ? (
                          <div className="flex flex-wrap gap-1">
                            {item.user_levels.map((lvlId) => {
                              const lvl = availableUserLevels.find((l) => l.level_id === lvlId);
                              return (
                                <span
                                  key={lvlId}
                                  className="inline-flex items-center rounded-full bg-sky-500/10 text-sky-700 dark:text-sky-300 font-mono text-[10px] font-bold px-2 py-0.2 border border-sky-500/20"
                                >
                                  {lvl ? lvl.level_name : `Level #${lvlId}`}
                                </span>
                              );
                            })}
                          </div>
                        ) : (
                          <span className="inline-flex items-center rounded-full bg-emerald-500/10 text-emerald-700 dark:text-emerald-300 font-mono text-[10px] font-bold px-2 py-0.2 border border-emerald-500/20">
                            All Roles
                          </span>
                        )}
                      </td>

                      <td className="px-3 py-2 text-center font-mono text-xs font-bold text-foreground border-r border-border">
                        {item.sort_order}
                      </td>

                      <td className="px-3 py-2 text-center border-r border-border">
                        {item.is_active ? (
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
                            onClick={() => openCreateModal(item.id)}
                            className="px-2 py-0.5 rounded-md border border-input bg-background text-[11px] font-medium text-foreground hover:bg-muted transition cursor-pointer"
                            title="Add Child Menu Item"
                          >
                            + Child
                          </button>
                          <button
                            type="button"
                            onClick={() => openEditModal(item)}
                            title="Edit"
                            className="p-1 rounded-md text-muted-foreground hover:text-foreground hover:bg-muted transition cursor-pointer"
                          >
                            <Pencil className="size-3.5" />
                          </button>
                          <button
                            type="button"
                            onClick={() => setDeletingId(item.id)}
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
        </div>

        {/* Modal: Create & Edit Form */}
        {showModal ? (
          <div className="fixed inset-0 z-50 flex items-center justify-center bg-black/60 backdrop-blur-xs p-4 animate-in fade-in duration-150">
            <div className="w-full max-w-2xl max-h-[90vh] overflow-y-auto rounded-xl border border-border bg-card p-5 shadow-2xl space-y-4">
              <div className="flex items-start justify-between">
                <div>
                  <h3 className="text-sm font-bold text-foreground">
                    {editingId ? 'Edit Sidebar Menu Item' : 'Create Sidebar Menu Item'}
                  </h3>
                  <p className="text-xs text-muted-foreground mt-0.5">
                    Configure display label, routes, hierarchy, and permissions.
                  </p>
                </div>
                <button
                  type="button"
                  onClick={() => setShowModal(false)}
                  className="text-muted-foreground hover:text-foreground p-1 rounded-md transition"
                >
                  <X className="size-4" />
                </button>
              </div>

              <form onSubmit={handleFormSubmit} className="space-y-3">
                {Object.keys(itemForm.errors).length > 0 && (
                  <div className="rounded-lg border border-rose-500/30 bg-rose-500/10 p-3 text-xs text-rose-600 dark:text-rose-400 space-y-1">
                    <div className="font-bold flex items-center gap-1.5">
                      <AlertCircle className="size-4 shrink-0" />
                      <span>Please correct the following errors:</span>
                    </div>
                    <ul className="list-disc list-inside space-y-0.5 text-[11px] pl-1">
                      {Object.entries(itemForm.errors).map(([field, msg]) => (
                        <li key={field}>{msg}</li>
                      ))}
                    </ul>
                  </div>
                )}

                <div className="grid gap-3 sm:grid-cols-2">
                  <div className="space-y-1">
                    <label className="text-[11px] font-semibold text-muted-foreground">Parent Menu Item</label>
                    <select
                      value={itemForm.data.parent_id}
                      onChange={(e) => itemForm.setData('parent_id', e.target.value)}
                      className="h-8 w-full rounded-lg border border-input bg-background px-2.5 text-xs text-foreground outline-hidden focus:ring-2 focus:ring-ring cursor-pointer"
                    >
                      <option value="">(None - Top Level Group)</option>
                      {parentOptions.map((opt) => (
                        <option key={opt.id} value={opt.id}>
                          {opt.label}
                        </option>
                      ))}
                    </select>
                    {itemForm.errors.parent_id && (
                      <p className="text-[10px] font-semibold text-rose-500">{itemForm.errors.parent_id}</p>
                    )}
                  </div>

                  <div className="space-y-1">
                    <label className="text-[11px] font-semibold text-muted-foreground">Display Label</label>
                    <input
                      value={itemForm.data.label}
                      onChange={(e) => itemForm.setData('label', e.target.value)}
                      placeholder="E.g., Dashboard, Annual Target"
                      className="h-8 w-full rounded-lg border border-input bg-background px-2.5 text-xs text-foreground outline-hidden focus:ring-2 focus:ring-ring"
                      required
                    />
                    {itemForm.errors.label && (
                      <p className="text-[10px] font-semibold text-rose-500">{itemForm.errors.label}</p>
                    )}
                  </div>
                </div>

                <div className="grid gap-3 sm:grid-cols-2">
                  <div className="space-y-1">
                    <label className="text-[11px] font-semibold text-muted-foreground">Target URL / Route Href</label>
                    <input
                      value={itemForm.data.href}
                      onChange={(e) => itemForm.setData('href', e.target.value)}
                      placeholder="E.g., /search"
                      className="h-8 w-full rounded-lg border border-input bg-background px-2.5 text-xs text-foreground outline-hidden focus:ring-2 focus:ring-ring"
                    />
                  </div>

                  <div className="space-y-1">
                    <label className="text-[11px] font-semibold text-muted-foreground">Route Key (Optional)</label>
                    <input
                      value={itemForm.data.key}
                      onChange={(e) => itemForm.setData('key', e.target.value)}
                      placeholder="E.g., search.index"
                      className="h-8 w-full rounded-lg border border-input bg-background px-2.5 text-xs text-foreground outline-hidden focus:ring-2 focus:ring-ring"
                    />
                  </div>
                </div>

                {/* Icon Selection */}
                <div className="space-y-1.5">
                  <div className="flex items-center justify-between">
                    <label className="text-[11px] font-semibold text-muted-foreground">
                      Icon Name <span className="text-[10px] font-normal text-muted-foreground">({filteredIcons.length} available)</span>
                    </label>
                    {itemForm.data.icon ? (
                      <span className="inline-flex items-center gap-1.5 text-[10px] font-bold text-emerald-700 dark:text-emerald-400 bg-emerald-500/10 px-2 py-0.5 rounded border border-emerald-500/20 font-mono">
                        {(() => {
                          const IconComp = getLucideIcon(itemForm.data.icon);
                          return IconComp ? <IconComp className="size-3.5 shrink-0" /> : null;
                        })()}
                        <span>Selected: {itemForm.data.icon}</span>
                      </span>
                    ) : null}
                  </div>

                  <div className="relative">
                    <input
                      value={itemForm.data.icon || ''}
                      onChange={(e) => {
                        const val = e.target.value;
                        itemForm.setData('icon', val);
                        setIconSearch(val);
                      }}
                      placeholder="Search or type icon name (e.g. user, search, shield-check, building)..."
                      className="h-8 w-full rounded-lg border border-input bg-background pl-2.5 pr-8 text-xs text-foreground outline-hidden focus:ring-2 focus:ring-ring font-mono"
                    />
                    {itemForm.data.icon ? (
                      <div className="absolute right-2 top-1/2 -translate-y-1/2 flex items-center gap-1 text-emerald-600 dark:text-emerald-400">
                        {(() => {
                          const IconComp = getLucideIcon(itemForm.data.icon);
                          return IconComp ? <IconComp className="size-4 shrink-0" /> : null;
                        })()}
                      </div>
                    ) : null}
                  </div>

                  <div className="max-h-48 overflow-y-auto rounded-lg border border-border bg-muted/20 p-2 grid grid-cols-2 sm:grid-cols-3 md:grid-cols-4 gap-1.5">
                    <button
                      type="button"
                      onClick={() => {
                        itemForm.setData('icon', '');
                        setIconSearch('');
                      }}
                      className={`flex items-center gap-1.5 px-2 py-1.5 text-xs rounded-md transition cursor-pointer ${
                        !itemForm.data.icon
                          ? 'bg-primary text-primary-foreground font-bold shadow-2xs'
                          : 'bg-background text-foreground border border-input hover:bg-muted'
                      }`}
                    >
                      <X className="size-3.5 shrink-0" />
                      <span className="truncate text-[11px]">(No Icon)</span>
                    </button>
                    {filteredIcons.slice(0, 100).map((icon) => {
                      const IconComp = getLucideIcon(icon);
                      const isSelected = itemForm.data.icon === icon;
                      return (
                        <button
                          key={icon}
                          type="button"
                          onClick={() => {
                            itemForm.setData('icon', icon);
                            setIconSearch(icon);
                          }}
                          className={`flex items-center gap-1.5 px-2 py-1.5 text-xs rounded-md transition cursor-pointer ${
                            isSelected
                              ? 'bg-emerald-600 text-white font-bold shadow-2xs'
                              : 'bg-background text-foreground border border-input hover:bg-muted'
                          }`}
                        >
                          {IconComp ? (
                            <IconComp className={`size-3.5 shrink-0 ${isSelected ? 'text-white' : 'text-emerald-600 dark:text-emerald-400'}`} />
                          ) : null}
                          <span className="truncate font-mono text-[10px]">{icon}</span>
                        </button>
                      );
                    })}
                  </div>
                </div>

                <div className="grid gap-3 sm:grid-cols-3">
                  <div className="space-y-1">
                    <label className="text-[11px] font-semibold text-muted-foreground">Badge Text</label>
                    <input
                      value={itemForm.data.badge_text}
                      onChange={(e) => itemForm.setData('badge_text', e.target.value)}
                      placeholder="New, 5, Beta..."
                      className="h-8 w-full rounded-lg border border-input bg-background px-2.5 text-xs text-foreground outline-hidden focus:ring-2 focus:ring-ring"
                    />
                  </div>

                  <div className="space-y-1">
                    <label className="text-[11px] font-semibold text-muted-foreground">Badge Color</label>
                    <select
                      value={itemForm.data.badge_cls}
                      onChange={(e) => itemForm.setData('badge_cls', e.target.value)}
                      className="h-8 w-full rounded-lg border border-input bg-background px-2.5 text-xs text-foreground outline-hidden focus:ring-2 focus:ring-ring cursor-pointer"
                    >
                      <option value="">Default</option>
                      {badgeColors.map((color) => (
                        <option key={color} value={color}>
                          {color}
                        </option>
                      ))}
                    </select>
                  </div>

                  <div className="space-y-1">
                    <label className="text-[11px] font-semibold text-muted-foreground">Sort Order</label>
                    <input
                      type="number"
                      value={itemForm.data.sort_order}
                      onChange={(e) => itemForm.setData('sort_order', Number(e.target.value))}
                      className="h-8 w-full rounded-lg border border-input bg-background px-2.5 text-xs text-foreground outline-hidden focus:ring-2 focus:ring-ring"
                      required
                    />
                  </div>
                </div>

                {/* User Levels Access Control */}
                <div className="space-y-1">
                  <label className="text-[11px] font-semibold text-muted-foreground">User Level Restrictions</label>
                  <p className="text-[10px] text-muted-foreground">
                    Select user levels that can view this menu item. Unchecked allows all levels.
                  </p>
                  <div className="grid gap-2 sm:grid-cols-2 lg:grid-cols-3 rounded-lg border border-border bg-muted/20 p-2.5 max-h-32 overflow-y-auto">
                    {availableUserLevels.map((lvl) => {
                      const isChecked = itemForm.data.user_levels.includes(lvl.level_id);
                      return (
                        <label key={lvl.level_id} className="flex items-center gap-2 text-xs text-foreground cursor-pointer">
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
                            className="size-3.5 rounded border-input text-emerald-600 focus:ring-emerald-500"
                          />
                          <span>{lvl.level_name}</span>
                        </label>
                      );
                    })}
                  </div>
                </div>

                <div className="flex items-center gap-2 pt-1">
                  <input
                    type="checkbox"
                    id="is_active_check"
                    checked={itemForm.data.is_active}
                    onChange={(e) => itemForm.setData('is_active', e.target.checked)}
                    className="size-3.5 rounded border-input text-emerald-600 focus:ring-emerald-500"
                  />
                  <label htmlFor="is_active_check" className="text-xs font-semibold text-foreground cursor-pointer">
                    Active &amp; Visible in Sidebar
                  </label>
                </div>

                <div className="flex justify-end gap-2 pt-3 border-t border-border">
                  <button
                    type="button"
                    onClick={() => setShowModal(false)}
                    className="px-3 py-1.5 rounded-lg border border-input bg-background text-xs font-semibold text-foreground hover:bg-muted transition"
                  >
                    Cancel
                  </button>
                  <button
                    type="submit"
                    disabled={itemForm.processing}
                    className="px-3.5 py-1.5 rounded-lg bg-emerald-600 hover:bg-emerald-700 text-white text-xs font-semibold shadow-xs transition"
                  >
                    {editingId ? 'Update Item' : 'Create Item'}
                  </button>
                </div>
              </form>
            </div>
          </div>
        ) : null}

        {/* Delete Confirmation Modal */}
        {deletingId ? (
          <div className="fixed inset-0 z-50 flex items-center justify-center bg-black/60 backdrop-blur-xs p-4 animate-in fade-in duration-150">
            <div className="w-full max-w-md rounded-xl border border-border bg-card p-5 shadow-2xl space-y-4">
              <div className="flex items-start justify-between">
                <div>
                  <h3 className="text-sm font-bold text-foreground">Delete Sidebar Menu Item</h3>
                  <p className="text-xs text-muted-foreground mt-0.5">
                    Are you sure you want to delete this menu item from navigation?
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
                    router.delete(`/settings/sidebar-menu/${deletingId}`, {
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

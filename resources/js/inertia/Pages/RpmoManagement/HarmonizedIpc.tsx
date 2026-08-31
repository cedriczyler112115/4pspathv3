import React, { useState, useEffect } from 'react';
import { Head, router, useForm } from '@inertiajs/react';
import AppLayout from '../../Layouts/AppLayout';
import FormattedText, { formatTextValue } from '../../Components/FormattedText';
import AutoResizingTextarea, { adjustTextareaHeight } from '../../Components/AutoResizingTextarea';

import {
  Plus,
  RotateCcw,
  Check,
  X,
  Lock,
  GripVertical,
  Pencil,
  Trash2,
  PlusCircle,
  FilePlus,
  ChevronRight,
  Sliders,
} from 'lucide-react';

type SubTargetRow = {
  id: number;
  indicatorId: number;
  kraCategory: number;
  activity: string;
  semester: string;
  newSemester: string;
  description: string;
  efficiency: string;
  quality: string;
  timeliness: string;
  movs: string;
  remarks: string;
  targetStatus: number;
  positionId: number | null;
};

type PendingSubTarget = {
  tempId: number;
  semester: string;
  description: string;
  efficiency: string;
  quality: string;
  timeliness: string;
  movs: string;
  remarks: string;
};

type GroupRow = {
  indicatorId: number;
  activity: string;
  category: number;
  targetStatus: number;
  rows: SubTargetRow[];
};

type DragPayload = {
  type: string;
  indicatorId: number;
  itemId: number;
  kra: number;
};

type Props = {
  appName: string;
  user: { name: string; email: string } | null;
  userProfile?: {
    fullName: string;
    position: string;
    designation: string;
    divisionName: string;
    sectionName: string;
  } | null;
  filters: {
    search: string;
    year: string;
    category: string;
    semester: string;
    position: string;
    perPage: number;
  };
  includeStrategicFunction: boolean;
  positions: Array<{ value: string; label: string }>;
  years: Array<{ value: string; label: string }>;
  categories: Array<{ value: string; label: string }>;
  semesters: Array<{ value: string; label: string }>;
  perPageOptions: Array<{ value: string; label: string }>;
  targets: {
    data: SubTargetRow[];
    from: number | null;
    to: number | null;
    total: number;
    currentPage: number;
    lastPage: number;
  };
  groups: GroupRow[];
  navigation?: { sidebar?: any[] };
};

function formatSemesterLabel(sem: string, semesters: Props['semesters']) {
  if (!sem) return '-';
  const found = semesters.find((s) => s.value === String(sem));
  return found ? found.label : sem;
}

export default function HarmonizedIpc({
  appName,
  user,
  filters,
  positions,
  years,
  categories,
  semesters,
  perPageOptions,
  targets,
  groups,
  navigation,
}: Props) {
  // Filter Form State
  const filterForm = useForm({
    search: filters.search,
    year: filters.year || String(new Date().getFullYear()),
    category: filters.category,
    semester: filters.semester,
    position: filters.position,
    perPage: String(filters.perPage),
  });

  // Inline Editing Group State & Pending Sub-targets
  const [editingIndicatorId, setEditingIndicatorId] = useState<number | null>(null);
  const [isCreatingSubTarget, setIsCreatingSubTarget] = useState<boolean>(false);
  const [pendingSubTargets, setPendingSubTargets] = useState<PendingSubTarget[]>([]);

  const inlineEditForm = useForm({
    activity: '',
    category: '2',
    editRows: {} as Record<
      number,
      {
        semester: string;
        description: string;
        efficiency: string;
        quality: string;
        timeliness: string;
        movs: string;
        remarks: string;
      }
    >,
    pendingSubTargets: [] as Array<{
      semester: string;
      description: string;
      efficiency: string;
      quality: string;
      timeliness: string;
      movs: string;
      remarks: string;
    }>,
  });

  // Add Target Modal State
  const [showAddModal, setShowAddModal] = useState(false);
  const [addingKraCategory, setAddingKraCategory] = useState<number>(1);

  const addForm = useForm({
    positionId: filters.position || '',
    year: filters.year || String(new Date().getFullYear()),
    category: 1,
    activity: '',
    semester: '1',
    description: '',
    efficiency: '',
    quality: '',
    timeliness: '',
    movs: '',
    remarks: '',
  });

  // Modals for Deleting & Drag Reordering
  const [deletingIndicatorId, setDeletingIndicatorId] = useState<number | null>(null);
  const [deletingSubTargetId, setDeletingSubTargetId] = useState<number | null>(null);
  const [draggingIndicatorId, setDraggingIndicatorId] = useState<number | null>(null);
  const [showMoveConfirmModal, setShowMoveConfirmModal] = useState(false);
  const [pendingMove, setPendingMove] = useState<{ source: DragPayload; target: DragPayload } | null>(null);

  // Right-Click Context Menu State
  const [contextMenu, setContextMenu] = useState<{
    x: number;
    y: number;
    indicatorId: number;
    subTargetId: number;
    category: number;
    subTargetCount: number;
    canDeleteTarget: boolean;
    canDeleteSubTarget: boolean;
    targetStatus: number;
  } | null>(null);

  const [activeSubMenu, setActiveSubMenu] = useState<'add' | 'delete' | null>(null);
  const [isDraggingMenu, setIsDraggingMenu] = useState(false);
  const [dragStart, setDragStart] = useState({ x: 0, y: 0 });
  const [initialMenuPos, setInitialMenuPos] = useState({ x: 0, y: 0 });

  // Close context menu on scroll or Escape
  useEffect(() => {
    const handleClose = () => {
      if (contextMenu) {
        setContextMenu(null);
        setActiveSubMenu(null);
      }
    };
    window.addEventListener('scroll', handleClose);
    window.addEventListener('keydown', (e) => e.key === 'Escape' && handleClose());
    return () => {
      window.removeEventListener('scroll', handleClose);
    };
  }, [contextMenu]);

  const submitFilters = (overrides = {}) => {
    const data = { ...filterForm.data, ...overrides };
    router.get('/inertia/rpmo-management/harmonized-ipc', data, {
      preserveState: true,
      replace: true,
    });
  };

  const resetFilters = () => {
    filterForm.setData({
      search: '',
      year: String(new Date().getFullYear()),
      category: '',
      semester: '',
      position: '',
      perPage: '10',
    });
    router.get(
      '/inertia/rpmo-management/harmonized-ipc',
      {
        search: '',
        year: String(new Date().getFullYear()),
        category: '',
        semester: '',
        position: '',
        perPage: '10',
      },
      { replace: true }
    );
  };

  const startInlineEdit = (group: GroupRow) => {
    setEditingIndicatorId(group.indicatorId);
    setIsCreatingSubTarget(false);
    setPendingSubTargets([]);

    const rowsMap: Record<
      number,
      {
        semester: string;
        description: string;
        efficiency: string;
        quality: string;
        timeliness: string;
        movs: string;
        remarks: string;
      }
    > = {};

    group.rows.forEach((r) => {
      rowsMap[r.id] = {
        semester: r.newSemester || r.semester || '1',
        description: formatTextValue(r.description, ''),
        efficiency: formatTextValue(r.efficiency, ''),
        quality: formatTextValue(r.quality, ''),
        timeliness: formatTextValue(r.timeliness, ''),
        movs: formatTextValue(r.movs, ''),
        remarks: formatTextValue(r.remarks, ''),
      };
    });

    inlineEditForm.setData({
      activity: formatTextValue(group.activity, ''),
      category: String(group.category),
      editRows: rowsMap,
      pendingSubTargets: [],
    });
  };

  const startInlineAddSubTarget = (group: GroupRow) => {
    setEditingIndicatorId(group.indicatorId);
    setIsCreatingSubTarget(true);

    const rowsMap: Record<
      number,
      {
        semester: string;
        description: string;
        efficiency: string;
        quality: string;
        timeliness: string;
        movs: string;
        remarks: string;
      }
    > = {};

    group.rows.forEach((r) => {
      rowsMap[r.id] = {
        semester: r.newSemester || r.semester || '1',
        description: formatTextValue(r.description, ''),
        efficiency: formatTextValue(r.efficiency, ''),
        quality: formatTextValue(r.quality, ''),
        timeliness: formatTextValue(r.timeliness, ''),
        movs: formatTextValue(r.movs, ''),
        remarks: formatTextValue(r.remarks, ''),
      };
    });

    const newPending: PendingSubTarget = {
      tempId: Date.now(),
      semester: '1',
      description: '',
      efficiency: '',
      quality: '',
      timeliness: '',
      movs: '',
      remarks: '',
    };

    setPendingSubTargets([newPending]);
    inlineEditForm.setData({
      activity: formatTextValue(group.activity, ''),
      category: String(group.category),
      editRows: rowsMap,
      pendingSubTargets: [
        {
          semester: '1',
          description: '',
          efficiency: '',
          quality: '',
          timeliness: '',
          movs: '',
          remarks: '',
        },
      ],
    });
  };

  const cancelInlineEdit = () => {
    setEditingIndicatorId(null);
    setIsCreatingSubTarget(false);
    setPendingSubTargets([]);
  };

  const handleSaveInlineEdit = (group: GroupRow) => {
    const firstRowId = group.rows[0]?.id;
    if (!firstRowId) return;

    inlineEditForm.patch(
      `/inertia/rpmo-management/harmonized-ipc/${group.indicatorId}/${firstRowId}`,
      {
        onSuccess: () => {
          cancelInlineEdit();
        },
      }
    );
  };

  const openAddModalForCategory = (catVal: number) => {
    if (!filterForm.data.position) {
      alert('Please select a position first before adding a target.');
      return;
    }
    setAddingKraCategory(catVal);
    addForm.setData({
      positionId: filterForm.data.position,
      year: filterForm.data.year || String(new Date().getFullYear()),
      category: catVal,
      activity: '',
      semester: '1',
      description: '',
      efficiency: '',
      quality: '',
      timeliness: '',
      movs: '',
      remarks: '',
    });
    setShowAddModal(true);
  };

  const handleSaveAddTarget = (e: React.FormEvent) => {
    e.preventDefault();
    addForm.post('/inertia/rpmo-management/harmonized-ipc', {
      onSuccess: () => {
        setShowAddModal(false);
      },
    });
  };

  const handleDrop = (source: DragPayload, target: DragPayload) => {
    if (source.indicatorId === target.indicatorId && source.type === target.type) return;

    if (source.kra !== target.kra) {
      setPendingMove({ source, target });
      setShowMoveConfirmModal(true);
      return;
    }

    router.post(
      '/inertia/rpmo-management/harmonized-ipc/reorder',
      { source, target },
      { preserveScroll: true }
    );
  };

  const confirmTargetMove = () => {
    if (!pendingMove) return;
    const { source, target } = pendingMove;
    setShowMoveConfirmModal(false);
    setPendingMove(null);

    router.post(
      '/inertia/rpmo-management/harmonized-ipc/reorder',
      { source, target },
      { preserveScroll: true }
    );
  };

  const openRightClickMenu = (
    e: React.MouseEvent,
    category: number,
    indicatorId: number,
    subTargetId: number,
    subTargetCount: number,
    targetStatus: number,
    isKraCol: boolean
  ) => {
    e.preventDefault();
    if (targetStatus === 3) return; // Locked target

    const canDeleteTarget = true;
    const canDeleteSubTarget = subTargetCount >= 2;

    let x = e.clientX;
    let y = e.clientY;

    const menuWidth = 220;
    const menuHeight = 240;

    if (x + menuWidth > window.innerWidth) {
      x = window.innerWidth - menuWidth - 12;
    }
    if (y + menuHeight > window.innerHeight) {
      y = window.innerHeight - menuHeight - 12;
    }

    setContextMenu({
      x: Math.max(8, x),
      y: Math.max(8, y),
      category,
      indicatorId,
      subTargetId,
      subTargetCount,
      canDeleteTarget,
      canDeleteSubTarget,
      targetStatus,
    });
    setActiveSubMenu(null);
  };

  const startMenuDrag = (e: React.PointerEvent) => {
    if (!contextMenu) return;
    setIsDraggingMenu(true);
    setDragStart({ x: e.clientX, y: e.clientY });
    setInitialMenuPos({ x: contextMenu.x, y: contextMenu.y });

    const onPointerMove = (pe: PointerEvent) => {
      const dx = pe.clientX - e.clientX;
      const dy = pe.clientY - e.clientY;
      setContextMenu((prev) =>
        prev
          ? {
              ...prev,
              x: Math.max(4, Math.min(window.innerWidth - 230, initialMenuPos.x + dx)),
              y: Math.max(4, Math.min(window.innerHeight - 250, initialMenuPos.y + dy)),
            }
          : null
      );
    };

    const onPointerUp = () => {
      setIsDraggingMenu(false);
      window.removeEventListener('pointermove', onPointerMove);
      window.removeEventListener('pointerup', onPointerUp);
    };

    window.addEventListener('pointermove', onPointerMove);
    window.addEventListener('pointerup', onPointerUp);
  };

  const selectedPositionLabel = () => {
    if (!filterForm.data.position) return 'All Positions';
    const found = positions.find((p) => p.value === filterForm.data.position);
    return found ? found.label : `Position #${filterForm.data.position}`;
  };

  return (
    <AppLayout appName={appName} user={user} sidebar={navigation?.sidebar ?? []}>
      <Head title="Harmonized IPC" />

      <section
        className="w-full space-y-6"
        onClick={() => {
          if (contextMenu) {
            setContextMenu(null);
            setActiveSubMenu(null);
          }
        }}
      >
        {/* Top Header & Position Selector */}
        <div className="flex flex-col gap-3 lg:flex-row lg:items-end lg:justify-between">
          <div className="space-y-1">
            <h1 className="text-xl font-bold tracking-tight text-slate-900 dark:text-slate-50">
              Harmonized IPC
            </h1>
            <p className="text-sm text-slate-500 dark:text-slate-400">
              Review harmonized IPC entries and manage targets.
            </p>
          </div>

          <div className="flex items-end gap-3">
            <div className="w-80 sm:w-[24rem]">
              <label className="block text-xs font-semibold text-slate-600 dark:text-slate-400 mb-1">
                Select Position
              </label>
              <select
                value={filterForm.data.position}
                onChange={(e) => {
                  filterForm.setData('position', e.target.value);
                  submitFilters({ position: e.target.value });
                }}
                className="h-10 w-full rounded-xl border border-slate-300 dark:border-slate-700 bg-white dark:bg-slate-800 px-3 text-sm focus:border-emerald-500 focus:outline-none dark:text-slate-100 font-semibold text-emerald-800 dark:text-emerald-300"
              >
                <option value="">Select Position</option>
                {positions.map((p) => (
                  <option key={p.value} value={p.value}>
                    {p.label}
                  </option>
                ))}
              </select>
            </div>
          </div>
        </div>

        {/* Outer Card Container */}
        <div className="rounded-2xl border border-slate-200 dark:border-slate-800 bg-white dark:bg-slate-900 p-4 shadow-sm space-y-4">
          {/* Filters Bar */}
          <div className="mb-4 border-b border-slate-100 dark:border-slate-800 pb-4">
            <div className="grid gap-3 sm:grid-cols-2 md:grid-cols-3 lg:grid-cols-7 items-end">
              <div className="lg:col-span-2">
                <label className="block text-xs font-semibold text-slate-600 dark:text-slate-400 mb-1">
                  Search
                </label>
                <input
                  type="text"
                  value={filterForm.data.search}
                  onChange={(e) => filterForm.setData('search', e.target.value)}
                  onKeyDown={(e) => e.key === 'Enter' && submitFilters()}
                  placeholder="Search harmonized targets..."
                  className="h-10 w-full rounded-xl border border-slate-300 dark:border-slate-700 bg-white dark:bg-slate-800 px-3 text-sm focus:border-emerald-500 focus:outline-none dark:text-slate-100"
                />
              </div>

              <div>
                <label className="block text-xs font-semibold text-slate-600 dark:text-slate-400 mb-1">
                  Year
                </label>
                <select
                  value={filterForm.data.year}
                  onChange={(e) => {
                    filterForm.setData('year', e.target.value);
                    submitFilters({ year: e.target.value });
                  }}
                  className="h-10 w-full rounded-xl border border-slate-300 dark:border-slate-700 bg-white dark:bg-slate-800 px-3 text-sm focus:border-emerald-500 focus:outline-none dark:text-slate-100"
                >
                  <option value="">All years</option>
                  {years.map((y) => (
                    <option key={y.value} value={y.value}>
                      {y.label}
                    </option>
                  ))}
                </select>
              </div>

              <div>
                <label className="block text-xs font-semibold text-slate-600 dark:text-slate-400 mb-1">
                  Category
                </label>
                <select
                  value={filterForm.data.category}
                  onChange={(e) => {
                    filterForm.setData('category', e.target.value);
                    submitFilters({ category: e.target.value });
                  }}
                  className="h-10 w-full rounded-xl border border-slate-300 dark:border-slate-700 bg-white dark:bg-slate-800 px-3 text-sm focus:border-emerald-500 focus:outline-none dark:text-slate-100"
                >
                  <option value="">All categories</option>
                  {categories.map((c) => (
                    <option key={c.value} value={c.value}>
                      {c.label}
                    </option>
                  ))}
                </select>
              </div>

              <div>
                <label className="block text-xs font-semibold text-slate-600 dark:text-slate-400 mb-1">
                  Semester
                </label>
                <select
                  value={filterForm.data.semester}
                  onChange={(e) => {
                    filterForm.setData('semester', e.target.value);
                    submitFilters({ semester: e.target.value });
                  }}
                  className="h-10 w-full rounded-xl border border-slate-300 dark:border-slate-700 bg-white dark:bg-slate-800 px-3 text-sm focus:border-emerald-500 focus:outline-none dark:text-slate-100"
                >
                  <option value="">All semesters</option>
                  {semesters.map((s) => (
                    <option key={s.value} value={s.value}>
                      {s.label}
                    </option>
                  ))}
                </select>
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
                    <option key={String(opt.value)} value={String(opt.value)}>
                      {opt.label}
                    </option>
                  ))}
                </select>
              </div>

              <div>
                <button
                  type="button"
                  onClick={resetFilters}
                  className="h-10 w-full inline-flex items-center justify-center gap-1.5 px-3 rounded-xl border border-slate-200 dark:border-slate-700 bg-white dark:bg-slate-800 text-xs font-semibold text-slate-700 dark:text-slate-300 hover:bg-slate-50 transition"
                >
                  <RotateCcw className="w-3.5 h-3.5" />
                  <span>Reset Filters</span>
                </button>
              </div>
            </div>
          </div>

          {/* Categorized Table Grid */}
          <div className="overflow-x-auto rounded-xl border border-slate-200 dark:border-slate-800">
            <table className="w-full min-w-[1100px] border-separate border-spacing-0 text-sm">
              <thead className="bg-slate-50 dark:bg-slate-800/50 text-left text-xs font-semibold uppercase tracking-wide text-slate-500 dark:text-slate-400">
                <tr>
                  <th className="border-b border-r border-slate-200 dark:border-slate-800 px-3 py-3 text-center w-[70px] min-w-[70px]">
                    Action
                  </th>
                  <th className="border-b border-r border-slate-200 dark:border-slate-800 px-3 py-3 whitespace-nowrap w-[250px] min-w-[250px]">
                    Activity / Indicator
                  </th>
                  <th className="border-b border-r border-slate-200 dark:border-slate-800 px-3 py-3 whitespace-nowrap w-[140px] min-w-[140px]">
                    Semester
                  </th>
                  <th className="border-b border-r border-slate-200 dark:border-slate-800 px-3 py-3 whitespace-nowrap min-w-[220px]">
                    Target / Measure
                  </th>
                  <th className="border-b border-r border-slate-200 dark:border-slate-800 px-3 py-3 whitespace-nowrap w-[110px] min-w-[110px]">
                    EFFICIENCY
                  </th>
                  <th className="border-b border-r border-slate-200 dark:border-slate-800 px-3 py-3 whitespace-nowrap w-[110px] min-w-[110px]">
                    QUALITY
                  </th>
                  <th className="border-b border-r border-slate-200 dark:border-slate-800 px-3 py-3 whitespace-nowrap w-[110px] min-w-[110px]">
                    TIMELINESS
                  </th>
                  <th className="border-b border-r border-slate-200 dark:border-slate-800 px-3 py-3 whitespace-nowrap w-[160px] min-w-[160px]">
                    MOVS
                  </th>
                  <th className="border-b border-slate-200 dark:border-slate-800 px-3 py-3 whitespace-nowrap w-[180px] min-w-[180px]">
                    REMARKS
                  </th>
                </tr>
              </thead>

              {categories
                .filter((cat) => !filterForm.data.category || filterForm.data.category === cat.value)
                .map((cat) => {
                  const catVal = Number(cat.value);
                  const catGroups = groups.filter((g) => g.category === catVal);

                  return (
                    <React.Fragment key={`cat-section-${cat.value}`}>
                      {/* Category Header Banner */}
                      <tbody
                        onDragOver={(e) => {
                          e.preventDefault();
                          e.dataTransfer.dropEffect = 'move';
                        }}
                        onDrop={(e) => {
                          e.preventDefault();
                          const raw = e.dataTransfer.getData('application/json');
                          if (!raw) return;
                          const source = JSON.parse(raw);
                          handleDrop(source, {
                            type: 'category',
                            indicatorId: 0,
                            itemId: 0,
                            kra: catVal,
                          });
                        }}
                      >
                        <tr className="bg-slate-100/70 dark:bg-slate-800/70">
                          <td colSpan={9} className="border-b border-slate-200 dark:border-slate-800 px-3 py-2.5">
                            <div className="flex items-center justify-between font-bold text-slate-900 dark:text-slate-100">
                              <span>{cat.label}</span>
                              <button
                                type="button"
                                onClick={() => openAddModalForCategory(catVal)}
                                className="inline-flex items-center gap-1 text-xs font-bold text-emerald-700 dark:text-emerald-400 hover:text-emerald-800"
                              >
                                <Plus className="w-3.5 h-3.5" />
                                <span>Add Target</span>
                              </button>
                            </div>
                          </td>
                        </tr>
                      </tbody>

                      {catGroups.length === 0 ? (
                        <tbody>
                          <tr>
                            <td colSpan={9} className="border-b border-slate-200 dark:border-slate-800 px-3 py-8 text-center text-slate-500">
                              {!filterForm.data.position
                                ? 'Please select a position to view harmonized IPC targets.'
                                : 'No record found in this category.'}
                            </td>
                          </tr>
                        </tbody>
                      ) : (
                        catGroups.map((group) => {
                          const isEditingGroup = editingIndicatorId === group.indicatorId;
                          const isEditingExistingRows = isEditingGroup && !isCreatingSubTarget;
                          const isPendingForThisGroup = isEditingGroup ? pendingSubTargets : [];
                          const rowSpan = group.rows.length + isPendingForThisGroup.length;
                          const isDragging = draggingIndicatorId === group.indicatorId;

                          return (
                            <tbody key={`group-body-${group.indicatorId}`}>
                              {group.rows.map((row, idx) => (
                                <tr
                                  key={`row-${group.indicatorId}-${row.id}`}
                                  onDragOver={(e) => {
                                    e.preventDefault();
                                    e.dataTransfer.dropEffect = 'move';
                                  }}
                                  onDrop={(e) => {
                                    e.preventDefault();
                                    const raw = e.dataTransfer.getData('application/json');
                                    if (!raw) return;
                                    const source = JSON.parse(raw);
                                    handleDrop(source, {
                                      type: 'main',
                                      indicatorId: group.indicatorId,
                                      itemId: row.id,
                                      kra: group.category,
                                    });
                                  }}
                                  className={`border-t border-slate-200/60 dark:border-slate-800/60 text-sm hover:bg-slate-50/50 dark:hover:bg-slate-800/30 transition-colors ${
                                    isEditingGroup ? 'bg-amber-50/90 dark:bg-amber-950/40' : ''
                                  } ${
                                    isDragging ? 'opacity-50 bg-slate-200 dark:bg-slate-800' : ''
                                  }`}
                                >
                                  {/* Main Indicator Rowspan Columns */}
                                  {idx === 0 ? (
                                    <>
                                      <td
                                        rowSpan={rowSpan}
                                        onContextMenu={(e) =>
                                          openRightClickMenu(
                                            e,
                                            group.category,
                                            group.indicatorId,
                                            row.id,
                                            group.rows.length,
                                            group.targetStatus,
                                            true
                                          )
                                        }
                                        className="border-b border-r border-slate-200 dark:border-slate-800 px-3 py-3 align-top text-center"
                                      >
                                        <div className="flex items-center justify-center gap-1">
                                          {isEditingGroup ? (
                                            <div className="flex flex-col gap-1 items-center">
                                              <button
                                                type="button"
                                                onClick={() => handleSaveInlineEdit(group)}
                                                className="w-10 h-7 rounded bg-emerald-600 text-white flex items-center justify-center shadow-xs hover:bg-emerald-700"
                                                title="Save Changes"
                                              >
                                                <Check className="w-4 h-4" />
                                              </button>
                                              <button
                                                type="button"
                                                onClick={cancelInlineEdit}
                                                className="w-10 h-7 rounded bg-amber-500 text-white flex items-center justify-center shadow-xs hover:bg-amber-600"
                                                title="Cancel"
                                              >
                                                <X className="w-4 h-4" />
                                              </button>
                                            </div>
                                          ) : group.targetStatus === 3 ? (
                                            <Lock className="w-4 h-4 text-slate-400" title="Locked target" />
                                          ) : (
                                            <div
                                              draggable
                                              onDragStart={(e) => {
                                                e.dataTransfer.effectAllowed = 'move';
                                                e.dataTransfer.setData(
                                                  'application/json',
                                                  JSON.stringify({
                                                    type: 'main',
                                                    indicatorId: group.indicatorId,
                                                    itemId: row.id,
                                                    kra: group.category,
                                                  })
                                                );
                                                setDraggingIndicatorId(group.indicatorId);
                                              }}
                                              onDragEnd={() => setDraggingIndicatorId(null)}
                                              className="p-1 cursor-grab active:cursor-grabbing text-slate-400 hover:text-slate-700 transition"
                                              title="Drag target to reorder or move category"
                                            >
                                              <GripVertical className="w-4 h-4" />
                                            </div>
                                          )}
                                        </div>
                                      </td>

                                      <td
                                        rowSpan={rowSpan}
                                        onContextMenu={(e) =>
                                          openRightClickMenu(
                                            e,
                                            group.category,
                                            group.indicatorId,
                                            row.id,
                                            group.rows.length,
                                            group.targetStatus,
                                            true
                                          )
                                        }
                                        className="border-b border-r border-slate-200 dark:border-slate-800 px-3 py-3 align-top text-slate-900 dark:text-slate-100 font-medium"
                                      >
                                        {isEditingExistingRows ? (
                                          <AutoResizingTextarea
                                            value={inlineEditForm.data.activity}
                                            onChange={(e) => inlineEditForm.setData('activity', e.target.value)}
                                            rows={2}
                                            className="w-full rounded-md border border-slate-300 p-2 text-xs"
                                          />
                                        ) : (
                                          <FormattedText value={group.activity} />
                                        )}
                                      </td>
                                    </>
                                  ) : null}

                                  {/* Sub-target Columns */}
                                  <td
                                    onContextMenu={(e) =>
                                      openRightClickMenu(
                                        e,
                                        group.category,
                                        group.indicatorId,
                                        row.id,
                                        group.rows.length,
                                        group.targetStatus,
                                        false
                                      )
                                    }
                                    className="border-b border-r border-slate-200 dark:border-slate-800 px-3 py-3 align-top text-xs text-slate-700 dark:text-slate-300"
                                  >
                                    {isEditingExistingRows ? (
                                      <select
                                        value={inlineEditForm.data.editRows[row.id]?.semester || '1'}
                                        onChange={(e) =>
                                          inlineEditForm.setData('editRows', {
                                            ...inlineEditForm.data.editRows,
                                            [row.id]: {
                                              ...inlineEditForm.data.editRows[row.id],
                                              semester: e.target.value,
                                            },
                                          })
                                        }
                                        className="w-full rounded-md border border-slate-300 p-1 text-xs"
                                      >
                                        {semesters.map((s) => (
                                          <option key={s.value} value={s.value}>
                                            {s.label}
                                          </option>
                                        ))}
                                      </select>
                                    ) : (
                                      formatSemesterLabel(row.newSemester || row.semester, semesters)
                                    )}
                                  </td>

                                  <td
                                    onContextMenu={(e) =>
                                      openRightClickMenu(
                                        e,
                                        group.category,
                                        group.indicatorId,
                                        row.id,
                                        group.rows.length,
                                        group.targetStatus,
                                        false
                                      )
                                    }
                                    className="border-b border-r border-slate-200 dark:border-slate-800 px-3 py-3 align-top text-xs text-slate-800 dark:text-slate-200"
                                  >
                                    {isEditingExistingRows ? (
                                      <AutoResizingTextarea
                                        value={inlineEditForm.data.editRows[row.id]?.description || ''}
                                        onChange={(e) =>
                                          inlineEditForm.setData('editRows', {
                                            ...inlineEditForm.data.editRows,
                                            [row.id]: {
                                              ...inlineEditForm.data.editRows[row.id],
                                              description: e.target.value,
                                            },
                                          })
                                        }
                                        rows={2}
                                        className="w-full rounded-md border border-slate-300 p-1 text-xs"
                                      />
                                    ) : (
                                      <FormattedText value={row.description} />
                                    )}
                                  </td>

                                  <td
                                    onContextMenu={(e) =>
                                      openRightClickMenu(
                                        e,
                                        group.category,
                                        group.indicatorId,
                                        row.id,
                                        group.rows.length,
                                        group.targetStatus,
                                        false
                                      )
                                    }
                                    className="border-b border-r border-slate-200 dark:border-slate-800 px-3 py-3 align-top text-xs text-slate-700 dark:text-slate-300"
                                  >
                                    {isEditingExistingRows ? (
                                      <AutoResizingTextarea
                                        value={inlineEditForm.data.editRows[row.id]?.efficiency || ''}
                                        onChange={(e) =>
                                          inlineEditForm.setData('editRows', {
                                            ...inlineEditForm.data.editRows,
                                            [row.id]: {
                                              ...inlineEditForm.data.editRows[row.id],
                                              efficiency: e.target.value,
                                            },
                                          })
                                        }
                                        rows={2}
                                        className="w-full rounded-md border border-slate-300 p-1 text-xs"
                                      />
                                    ) : (
                                      <FormattedText value={row.efficiency} />
                                    )}
                                  </td>

                                  <td
                                    onContextMenu={(e) =>
                                      openRightClickMenu(
                                        e,
                                        group.category,
                                        group.indicatorId,
                                        row.id,
                                        group.rows.length,
                                        group.targetStatus,
                                        false
                                      )
                                    }
                                    className="border-b border-r border-slate-200 dark:border-slate-800 px-3 py-3 align-top text-xs text-slate-700 dark:text-slate-300"
                                  >
                                    {isEditingExistingRows ? (
                                      <AutoResizingTextarea
                                        value={inlineEditForm.data.editRows[row.id]?.quality || ''}
                                        onChange={(e) =>
                                          inlineEditForm.setData('editRows', {
                                            ...inlineEditForm.data.editRows,
                                            [row.id]: {
                                              ...inlineEditForm.data.editRows[row.id],
                                              quality: e.target.value,
                                            },
                                          })
                                        }
                                        rows={2}
                                        className="w-full rounded-md border border-slate-300 p-1 text-xs"
                                      />
                                    ) : (
                                      <FormattedText value={row.quality} />
                                    )}
                                  </td>

                                  <td
                                    onContextMenu={(e) =>
                                      openRightClickMenu(
                                        e,
                                        group.category,
                                        group.indicatorId,
                                        row.id,
                                        group.rows.length,
                                        group.targetStatus,
                                        false
                                      )
                                    }
                                    className="border-b border-r border-slate-200 dark:border-slate-800 px-3 py-3 align-top text-xs text-slate-700 dark:text-slate-300"
                                  >
                                    {isEditingExistingRows ? (
                                      <AutoResizingTextarea
                                        value={inlineEditForm.data.editRows[row.id]?.timeliness || ''}
                                        onChange={(e) =>
                                          inlineEditForm.setData('editRows', {
                                            ...inlineEditForm.data.editRows,
                                            [row.id]: {
                                              ...inlineEditForm.data.editRows[row.id],
                                              timeliness: e.target.value,
                                            },
                                          })
                                        }
                                        rows={2}
                                        className="w-full rounded-md border border-slate-300 p-1 text-xs"
                                      />
                                    ) : (
                                      <FormattedText value={row.timeliness} />
                                    )}
                                  </td>

                                  <td
                                    onContextMenu={(e) =>
                                      openRightClickMenu(
                                        e,
                                        group.category,
                                        group.indicatorId,
                                        row.id,
                                        group.rows.length,
                                        group.targetStatus,
                                        false
                                      )
                                    }
                                    className="border-b border-r border-slate-200 dark:border-slate-800 px-3 py-3 align-top text-xs text-slate-700 dark:text-slate-300"
                                  >
                                    {isEditingExistingRows ? (
                                      <AutoResizingTextarea
                                        value={inlineEditForm.data.editRows[row.id]?.movs || ''}
                                        onChange={(e) =>
                                          inlineEditForm.setData('editRows', {
                                            ...inlineEditForm.data.editRows,
                                            [row.id]: {
                                              ...inlineEditForm.data.editRows[row.id],
                                              movs: e.target.value,
                                            },
                                          })
                                        }
                                        rows={2}
                                        className="w-full rounded-md border border-slate-300 p-1 text-xs"
                                      />
                                    ) : (
                                      <FormattedText value={row.movs} />
                                    )}
                                  </td>

                                  <td
                                    onContextMenu={(e) =>
                                      openRightClickMenu(
                                        e,
                                        group.category,
                                        group.indicatorId,
                                        row.id,
                                        group.rows.length,
                                        group.targetStatus,
                                        false
                                      )
                                    }
                                    className="border-b border-slate-200 dark:border-slate-800 px-3 py-3 align-top text-xs text-slate-700 dark:text-slate-300"
                                  >
                                    {isEditingExistingRows ? (
                                      <AutoResizingTextarea
                                        value={inlineEditForm.data.editRows[row.id]?.remarks || ''}
                                        onChange={(e) =>
                                          inlineEditForm.setData('editRows', {
                                            ...inlineEditForm.data.editRows,
                                            [row.id]: {
                                              ...inlineEditForm.data.editRows[row.id],
                                              remarks: e.target.value,
                                            },
                                          })
                                        }
                                        rows={2}
                                        className="w-full rounded-md border border-slate-300 p-1 text-xs"
                                      />
                                    ) : (
                                      <FormattedText value={row.remarks} />
                                    )}
                                  </td>
                                </tr>
                              ))}

                              {/* Render Pending Sub-target Input Rows ONLY when adding sub-target */}
                              {isEditingGroup && isCreatingSubTarget && pendingSubTargets.length > 0
                                ? pendingSubTargets.map((pending, pIdx) => (
                                    <tr
                                      key={`pending-${group.indicatorId}-${pending.tempId}`}
                                      className="border-t border-amber-300 bg-amber-100/90 dark:bg-amber-950/60 text-sm"
                                    >
                                      <td className="border-b border-r border-slate-200 dark:border-slate-800 px-3 py-3 align-top">
                                        <select
                                          value={inlineEditForm.data.pendingSubTargets[pIdx]?.semester || '1'}
                                          onChange={(e) => {
                                            const updated = [...inlineEditForm.data.pendingSubTargets];
                                            updated[pIdx] = {
                                              ...(updated[pIdx] || {
                                                semester: '1',
                                                description: '',
                                                efficiency: '',
                                                quality: '',
                                                timeliness: '',
                                                movs: '',
                                                remarks: '',
                                              }),
                                              semester: e.target.value,
                                            };
                                            inlineEditForm.setData('pendingSubTargets', updated);
                                          }}
                                          className="w-full rounded-md border border-amber-400 p-1 text-xs bg-white dark:bg-slate-900"
                                        >
                                          {semesters.map((s) => (
                                            <option key={s.value} value={s.value}>
                                              {s.label}
                                            </option>
                                          ))}
                                        </select>
                                      </td>

                                      <td className="border-b border-r border-slate-200 dark:border-slate-800 px-3 py-3 align-top">
                                         <AutoResizingTextarea
                                           value={inlineEditForm.data.pendingSubTargets[pIdx]?.description || ''}
                                           onChange={(e) => {
                                             const updated = [...inlineEditForm.data.pendingSubTargets];
                                             updated[pIdx] = {
                                               ...(updated[pIdx] || {
                                                 semester: '1',
                                                 description: '',
                                                 efficiency: '',
                                                 quality: '',
                                                 timeliness: '',
                                                 movs: '',
                                                 remarks: '',
                                               }),
                                               description: e.target.value,
                                             };
                                             inlineEditForm.setData('pendingSubTargets', updated);
                                           }}
                                           placeholder="Enter success indicator / description for new sub-target"
                                           rows={2}
                                           className="w-full rounded-md border border-amber-400 p-1.5 text-xs bg-white dark:bg-slate-900"
                                         />
                                      </td>

                                      <td className="border-b border-r border-slate-200 dark:border-slate-800 px-3 py-3 align-top">
                                         <AutoResizingTextarea
                                           value={inlineEditForm.data.pendingSubTargets[pIdx]?.efficiency || ''}
                                           onChange={(e) => {
                                             const updated = [...inlineEditForm.data.pendingSubTargets];
                                             updated[pIdx] = {
                                               ...(updated[pIdx] || {
                                                 semester: '1',
                                                 description: '',
                                                 efficiency: '',
                                                 quality: '',
                                                 timeliness: '',
                                                 movs: '',
                                                 remarks: '',
                                               }),
                                               efficiency: e.target.value,
                                             };
                                             inlineEditForm.setData('pendingSubTargets', updated);
                                           }}
                                           placeholder="Efficiency"
                                           rows={2}
                                           className="w-full rounded-md border border-amber-400 p-1.5 text-xs bg-white dark:bg-slate-900"
                                         />
                                      </td>

                                      <td className="border-b border-r border-slate-200 dark:border-slate-800 px-3 py-3 align-top">
                                         <AutoResizingTextarea
                                           value={inlineEditForm.data.pendingSubTargets[pIdx]?.quality || ''}
                                           onChange={(e) => {
                                             const updated = [...inlineEditForm.data.pendingSubTargets];
                                             updated[pIdx] = {
                                               ...(updated[pIdx] || {
                                                 semester: '1',
                                                 description: '',
                                                 efficiency: '',
                                                 quality: '',
                                                 timeliness: '',
                                                 movs: '',
                                                 remarks: '',
                                               }),
                                               quality: e.target.value,
                                             };
                                             inlineEditForm.setData('pendingSubTargets', updated);
                                           }}
                                           placeholder="Quality"
                                           rows={2}
                                           className="w-full rounded-md border border-amber-400 p-1.5 text-xs bg-white dark:bg-slate-900"
                                         />
                                      </td>

                                      <td className="border-b border-r border-slate-200 dark:border-slate-800 px-3 py-3 align-top">
                                         <AutoResizingTextarea
                                           value={inlineEditForm.data.pendingSubTargets[pIdx]?.timeliness || ''}
                                           onChange={(e) => {
                                             const updated = [...inlineEditForm.data.pendingSubTargets];
                                             updated[pIdx] = {
                                               ...(updated[pIdx] || {
                                                 semester: '1',
                                                 description: '',
                                                 efficiency: '',
                                                 quality: '',
                                                 timeliness: '',
                                                 movs: '',
                                                 remarks: '',
                                               }),
                                               timeliness: e.target.value,
                                             };
                                             inlineEditForm.setData('pendingSubTargets', updated);
                                           }}
                                           placeholder="Timeliness"
                                           rows={2}
                                           className="w-full rounded-md border border-amber-400 p-1.5 text-xs bg-white dark:bg-slate-900"
                                         />
                                      </td>

                                      <td className="border-b border-r border-slate-200 dark:border-slate-800 px-3 py-3 align-top">
                                         <AutoResizingTextarea
                                           value={inlineEditForm.data.pendingSubTargets[pIdx]?.movs || ''}
                                           onChange={(e) => {
                                             const updated = [...inlineEditForm.data.pendingSubTargets];
                                             updated[pIdx] = {
                                               ...(updated[pIdx] || {
                                                 semester: '1',
                                                 description: '',
                                                 efficiency: '',
                                                 quality: '',
                                                 timeliness: '',
                                                 movs: '',
                                                 remarks: '',
                                               }),
                                               movs: e.target.value,
                                             };
                                             inlineEditForm.setData('pendingSubTargets', updated);
                                           }}
                                           placeholder="MOVs"
                                           rows={2}
                                           className="w-full rounded-md border border-amber-400 p-1.5 text-xs bg-white dark:bg-slate-900"
                                         />
                                      </td>

                                      <td className="border-b border-slate-200 dark:border-slate-800 px-3 py-3 align-top">
                                         <AutoResizingTextarea
                                           value={inlineEditForm.data.pendingSubTargets[pIdx]?.remarks || ''}
                                           onChange={(e) => {
                                             const updated = [...inlineEditForm.data.pendingSubTargets];
                                             updated[pIdx] = {
                                               ...(updated[pIdx] || {
                                                 semester: '1',
                                                 description: '',
                                                 efficiency: '',
                                                 quality: '',
                                                 timeliness: '',
                                                 movs: '',
                                                 remarks: '',
                                               }),
                                               remarks: e.target.value,
                                             };
                                             inlineEditForm.setData('pendingSubTargets', updated);
                                           }}
                                           placeholder="Remarks"
                                           rows={2}
                                           className="w-full rounded-md border border-amber-400 p-1.5 text-xs bg-white dark:bg-slate-900"
                                         />
                                      </td>
                                    </tr>
                                  ))
                                : null}
                            </tbody>
                          );
                        })
                      )}
                    </React.Fragment>
                  );
                })}
            </table>
          </div>

          {/* Livewire Pagination Style */}
          {targets.lastPage > 1 ? (
            <nav
              role="navigation"
              aria-label="Pagination Navigation"
              className="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between pt-2"
            >
              <div className="text-sm text-slate-500 dark:text-slate-400">
                Showing {targets.from ?? 0} to {targets.to ?? 0} of {targets.total} records
              </div>

              <div className="flex flex-wrap items-center gap-1.5">
                {targets.currentPage === 1 ? (
                  <span className="inline-flex cursor-not-allowed items-center rounded-lg border border-slate-200 dark:border-slate-800 bg-slate-100 dark:bg-slate-800 px-3 py-2 text-sm text-slate-400 select-none">
                    Previous
                  </span>
                ) : (
                  <button
                    type="button"
                    onClick={() =>
                      router.get(
                        '/inertia/rpmo-management/harmonized-ipc',
                        { ...filterForm.data, page: targets.currentPage - 1 },
                        { replace: true, preserveState: true }
                      )
                    }
                    className="inline-flex cursor-pointer items-center rounded-lg border border-slate-200 dark:border-slate-800 bg-white dark:bg-slate-900 px-3 py-2 text-sm text-slate-700 dark:text-slate-200 hover:border-emerald-500/50 hover:bg-emerald-50/50 hover:text-emerald-600 transition-colors"
                  >
                    Previous
                  </button>
                )}

                {Array.from({ length: targets.lastPage }, (_, i) => i + 1).map((page) => {
                  if (page === targets.currentPage) {
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
                          '/inertia/rpmo-management/harmonized-ipc',
                          { ...filterForm.data, page },
                          { replace: true, preserveState: true }
                        )
                      }
                      className="inline-flex min-w-10 cursor-pointer items-center justify-center rounded-lg border border-slate-200 dark:border-slate-800 bg-white dark:bg-slate-900 px-3 py-2 text-sm text-slate-700 dark:text-slate-200 hover:border-emerald-500/50 hover:bg-emerald-50/50 hover:text-emerald-600 transition-colors"
                    >
                      {page}
                    </button>
                  );
                })}

                {targets.currentPage === targets.lastPage ? (
                  <span className="inline-flex cursor-not-allowed items-center rounded-lg border border-slate-200 dark:border-slate-800 bg-slate-100 dark:bg-slate-800 px-3 py-2 text-sm text-slate-400 select-none">
                    Next
                  </span>
                ) : (
                  <button
                    type="button"
                    onClick={() =>
                      router.get(
                        '/inertia/rpmo-management/harmonized-ipc',
                        { ...filterForm.data, page: targets.currentPage + 1 },
                        { replace: true, preserveState: true }
                      )
                    }
                    className="inline-flex cursor-pointer items-center rounded-lg border border-slate-200 dark:border-slate-800 bg-white dark:bg-slate-900 px-3 py-2 text-sm text-slate-700 dark:text-slate-200 hover:border-emerald-500/50 hover:bg-emerald-50/50 hover:text-emerald-600 transition-colors"
                  >
                    Next
                  </button>
                )}
              </div>
            </nav>
          ) : null}
        </div>

        {/* Livewire Floating Right-Click Context Menu */}
        {contextMenu ? (
          <div
            style={{ top: contextMenu.y, left: contextMenu.x, zIndex: 99999 }}
            className="fixed min-w-[14rem] rounded-xl border border-slate-200 dark:border-slate-700 bg-white dark:bg-slate-900 text-slate-900 dark:text-slate-100 p-1.5 text-xs font-medium shadow-2xl space-y-1"
          >
            {/* Draggable Menu Header */}
            <div
              onPointerDown={startMenuDrag}
              className="px-2.5 py-1.5 text-[10px] font-bold uppercase tracking-wider text-slate-400 border-b border-slate-100 dark:border-slate-800 mb-1 flex items-center justify-between cursor-move select-none"
              title="Drag to move popup"
            >
              <div className="flex items-center gap-1.5">
                <Sliders className="w-3.5 h-3.5 text-emerald-600 dark:text-emerald-400" />
                <span>OPTIONS</span>
              </div>
            </div>

            {/* Menu Item 1: Add Target Submenu */}
            <div className="relative">
              <button
                type="button"
                onMouseEnter={() => setActiveSubMenu('add')}
                onClick={() => setActiveSubMenu(activeSubMenu === 'add' ? null : 'add')}
                className={`flex w-full items-center justify-between gap-2 rounded-lg px-2.5 py-1.5 text-left text-slate-700 dark:text-slate-200 hover:bg-slate-100 dark:hover:bg-slate-800 transition-colors ${
                  activeSubMenu === 'add' ? 'bg-slate-100 dark:bg-slate-800' : ''
                }`}
              >
                <div className="flex items-center gap-2">
                  <PlusCircle className="w-4 h-4 text-slate-700 dark:text-slate-300" />
                  <span>Add Target</span>
                </div>
                <ChevronRight className="w-3.5 h-3.5 text-slate-400" />
              </button>

              {/* Submenu Floating Flyout for Add */}
              {activeSubMenu === 'add' ? (
                <div
                  style={{ top: -6, left: 198, zIndex: 100000 }}
                  className="absolute min-w-[12rem] rounded-xl border border-slate-200 dark:border-slate-700 bg-white dark:bg-slate-900 p-1.5 text-xs font-medium shadow-2xl space-y-1"
                >
                  <button
                    type="button"
                    onClick={() => {
                      openAddModalForCategory(contextMenu.category);
                      setContextMenu(null);
                      setActiveSubMenu(null);
                    }}
                    className="flex w-full items-center gap-2 rounded-lg px-2.5 py-1.5 text-left text-slate-700 dark:text-slate-200 hover:bg-slate-100 dark:hover:bg-slate-800"
                  >
                    <Plus className="w-4 h-4 text-slate-700 dark:text-slate-300" />
                    <span>Add new target</span>
                  </button>

                  <button
                    type="button"
                    onClick={() => {
                      const group = groups.find((g) => g.indicatorId === contextMenu.indicatorId);
                      if (group) startInlineAddSubTarget(group);
                      setContextMenu(null);
                      setActiveSubMenu(null);
                    }}
                    className="flex w-full items-center gap-2 rounded-lg px-2.5 py-1.5 text-left text-slate-700 dark:text-slate-200 hover:bg-slate-100 dark:hover:bg-slate-800"
                  >
                    <FilePlus className="w-4 h-4 text-slate-700 dark:text-slate-300" />
                    <span>Add sub-target</span>
                  </button>
                </div>
              ) : null}
            </div>

            {/* Menu Item 2: Edit Target */}
            <button
              type="button"
              onMouseEnter={() => setActiveSubMenu(null)}
              onClick={() => {
                const group = groups.find((g) => g.indicatorId === contextMenu.indicatorId);
                if (group) startInlineEdit(group);
                setContextMenu(null);
                setActiveSubMenu(null);
              }}
              className="flex w-full items-center gap-2 rounded-lg px-2.5 py-1.5 text-left text-slate-700 dark:text-slate-200 hover:bg-slate-100 dark:hover:bg-slate-800 transition-colors"
            >
              <Pencil className="w-4 h-4 text-amber-500" />
              <span>Edit Target</span>
            </button>

            <div className="my-1 border-t border-slate-100 dark:border-slate-800" />

            {/* Menu Item 3: Delete Submenu */}
            <div className="relative">
              <button
                type="button"
                onMouseEnter={() => setActiveSubMenu('delete')}
                onClick={() => setActiveSubMenu(activeSubMenu === 'delete' ? null : 'delete')}
                className={`flex w-full items-center justify-between gap-2 rounded-lg px-2.5 py-1.5 text-left text-rose-600 dark:text-rose-400 hover:bg-rose-50 dark:hover:bg-rose-950/40 transition-colors ${
                  activeSubMenu === 'delete' ? 'bg-rose-50 dark:bg-rose-950/40' : ''
                }`}
              >
                <div className="flex items-center gap-2">
                  <Trash2 className="w-4 h-4 text-rose-500" />
                  <span>Delete</span>
                </div>
                <ChevronRight className="w-3.5 h-3.5 text-slate-400" />
              </button>

              {/* Submenu Floating Flyout for Delete */}
              {activeSubMenu === 'delete' ? (
                <div
                  style={{ top: -6, left: 198, zIndex: 100000 }}
                  className="absolute min-w-[17rem] rounded-xl border border-slate-200 dark:border-slate-700 bg-white dark:bg-slate-900 p-1.5 text-xs font-medium shadow-2xl space-y-1"
                >
                  <button
                    type="button"
                    disabled={!contextMenu.canDeleteTarget}
                    onClick={() => {
                      if (!contextMenu.canDeleteTarget) return;
                      setDeletingIndicatorId(contextMenu.indicatorId);
                      setContextMenu(null);
                      setActiveSubMenu(null);
                    }}
                    className={`flex w-full items-center gap-2 rounded-lg px-2.5 py-1.5 text-left transition-colors ${
                      !contextMenu.canDeleteTarget
                        ? 'opacity-40 cursor-not-allowed text-slate-400'
                        : 'text-rose-600 hover:bg-rose-50 dark:hover:bg-rose-950/40'
                    }`}
                  >
                    <Trash2 className="w-4 h-4 text-rose-500" />
                    <span>Delete selected target and its sub-target</span>
                  </button>

                  <button
                    type="button"
                    disabled={!contextMenu.canDeleteSubTarget}
                    onClick={() => {
                      if (!contextMenu.canDeleteSubTarget) return;
                      setDeletingSubTargetId(contextMenu.subTargetId);
                      setContextMenu(null);
                      setActiveSubMenu(null);
                    }}
                    className={`flex w-full items-center gap-2 rounded-lg px-2.5 py-1.5 text-left transition-colors ${
                      !contextMenu.canDeleteSubTarget
                        ? 'opacity-40 cursor-not-allowed text-slate-400'
                        : 'text-rose-600 hover:bg-rose-50 dark:hover:bg-rose-950/40'
                    }`}
                  >
                    <Trash2 className="w-4 h-4 text-rose-500" />
                    <span>Delete selected sub-target only</span>
                  </button>
                </div>
              ) : null}
            </div>
          </div>
        ) : null}

        {/* Modal: Add Target */}
        {showAddModal ? (
          <div className="fixed inset-0 z-50 flex items-center justify-center bg-slate-900/40 p-4 backdrop-blur-sm">
            <div className="w-full max-w-4xl max-h-[90vh] overflow-y-auto rounded-3xl bg-white dark:bg-slate-900 p-6 shadow-xl space-y-5 border border-slate-200 dark:border-slate-800">
              <div className="space-y-1">
                <h3 className="text-lg font-bold text-slate-900 dark:text-slate-100">Add target</h3>
                <p className="text-xs text-slate-500 dark:text-slate-400">
                  Create a new target entry inside the selected KRA category.
                </p>
              </div>

              <form onSubmit={handleSaveAddTarget} className="space-y-4">
                <div className="grid gap-4 sm:grid-cols-4 bg-slate-50 dark:bg-slate-800/40 p-3 rounded-2xl">
                  <div>
                    <label className="block text-[11px] font-semibold text-slate-500 mb-1">Position</label>
                    <span className="inline-flex rounded-full bg-violet-100 text-violet-800 font-bold text-xs px-2.5 py-1">
                      {selectedPositionLabel()}
                    </span>
                  </div>

                  <div>
                    <label className="block text-[11px] font-semibold text-slate-500 mb-1">KRA Category</label>
                    <span className="inline-flex rounded-full bg-cyan-100 text-cyan-800 font-bold text-xs px-2.5 py-1">
                      {categories.find((c) => c.value === String(addingKraCategory))?.label || `Category #${addingKraCategory}`}
                    </span>
                  </div>

                  <div>
                    <label className="block text-[11px] font-semibold text-slate-500 mb-1">Year</label>
                    <span className="inline-flex rounded-full bg-slate-200 text-slate-800 font-bold text-xs px-2.5 py-1">
                      {addForm.data.year}
                    </span>
                  </div>

                  <div>
                    <label className="block text-[11px] font-semibold text-slate-500 mb-1">Semester</label>
                    <select
                      value={addForm.data.semester}
                      onChange={(e) => addForm.setData('semester', e.target.value)}
                      className="h-9 w-full rounded-xl border border-slate-300 dark:border-slate-700 bg-white dark:bg-slate-800 px-3 text-xs focus:border-emerald-500"
                    >
                      {semesters.map((s) => (
                        <option key={s.value} value={s.value}>
                          {s.label}
                        </option>
                      ))}
                    </select>
                  </div>
                </div>

                <div className="grid gap-4 md:grid-cols-2">
                  <div>
                    <label className="block text-xs font-semibold text-slate-600 dark:text-slate-400 mb-1">
                      Key Result Area (Activity)
                    </label>
                    <AutoResizingTextarea
                      value={addForm.data.activity}
                      onChange={(e) => addForm.setData('activity', e.target.value)}
                      rows={2}
                      className="w-full rounded-xl border border-slate-300 dark:border-slate-700 bg-white dark:bg-slate-800 p-2.5 text-xs text-slate-900 dark:text-slate-100 focus:border-emerald-500"
                      required
                    />
                  </div>

                  <div>
                    <label className="block text-xs font-semibold text-slate-600 dark:text-slate-400 mb-1">
                      Success Indicator (Description)
                    </label>
                    <AutoResizingTextarea
                      value={addForm.data.description}
                      onChange={(e) => addForm.setData('description', e.target.value)}
                      rows={2}
                      className="w-full rounded-xl border border-slate-300 dark:border-slate-700 bg-white dark:bg-slate-800 p-2.5 text-xs text-slate-900 dark:text-slate-100 focus:border-emerald-500"
                      required
                    />
                  </div>
                </div>

                <div className="flex items-center gap-3 py-1">
                  <div className="h-px flex-1 bg-slate-200 dark:bg-slate-800" />
                  <span className="text-xs font-bold uppercase tracking-wider text-slate-400">
                    Rating Guide
                  </span>
                  <div className="h-px flex-1 bg-slate-200 dark:bg-slate-800" />
                </div>

                <div className="grid gap-4 md:grid-cols-2">
                  <div>
                    <label className="block text-xs font-semibold text-slate-600 dark:text-slate-400 mb-1">
                      Efficiency
                    </label>
                    <AutoResizingTextarea
                      value={addForm.data.efficiency}
                      onChange={(e) => addForm.setData('efficiency', e.target.value)}
                      rows={2}
                      className="w-full rounded-xl border border-slate-300 dark:border-slate-700 bg-white dark:bg-slate-800 p-2.5 text-xs text-slate-900 dark:text-slate-100 focus:border-emerald-500"
                      required
                    />
                  </div>

                  <div>
                    <label className="block text-xs font-semibold text-slate-600 dark:text-slate-400 mb-1">
                      Quality
                    </label>
                    <AutoResizingTextarea
                      value={addForm.data.quality}
                      onChange={(e) => addForm.setData('quality', e.target.value)}
                      rows={2}
                      className="w-full rounded-xl border border-slate-300 dark:border-slate-700 bg-white dark:bg-slate-800 p-2.5 text-xs text-slate-900 dark:text-slate-100 focus:border-emerald-500"
                      required
                    />
                  </div>

                  <div>
                    <label className="block text-xs font-semibold text-slate-600 dark:text-slate-400 mb-1">
                      Timeliness
                    </label>
                    <AutoResizingTextarea
                      value={addForm.data.timeliness}
                      onChange={(e) => addForm.setData('timeliness', e.target.value)}
                      rows={2}
                      className="w-full rounded-xl border border-slate-300 dark:border-slate-700 bg-white dark:bg-slate-800 p-2.5 text-xs text-slate-900 dark:text-slate-100 focus:border-emerald-500"
                      required
                    />
                  </div>

                  <div>
                    <label className="block text-xs font-semibold text-slate-600 dark:text-slate-400 mb-1">
                      MOVs
                    </label>
                    <AutoResizingTextarea
                      value={addForm.data.movs}
                      onChange={(e) => addForm.setData('movs', e.target.value)}
                      rows={2}
                      className="w-full rounded-xl border border-slate-300 dark:border-slate-700 bg-white dark:bg-slate-800 p-2.5 text-xs text-slate-900 dark:text-slate-100 focus:border-emerald-500"
                      required
                    />
                  </div>

                  <div className="md:col-span-2">
                    <label className="block text-xs font-semibold text-slate-600 dark:text-slate-400 mb-1">
                      Remarks
                    </label>
                    <AutoResizingTextarea
                      value={addForm.data.remarks}
                      onChange={(e) => addForm.setData('remarks', e.target.value)}
                      rows={2}
                      className="w-full rounded-xl border border-slate-300 dark:border-slate-700 bg-white dark:bg-slate-800 p-2.5 text-xs text-slate-900 dark:text-slate-100 focus:border-emerald-500"
                    />
                  </div>
                </div>

                <div className="flex justify-end gap-2 pt-4 border-t border-slate-100 dark:border-slate-800">
                  <button
                    type="button"
                    onClick={() => setShowAddModal(false)}
                    className="rounded-xl px-4 py-2 text-sm font-medium text-slate-700 hover:bg-slate-100 dark:text-slate-300 transition"
                  >
                    Cancel
                  </button>
                  <button
                    type="submit"
                    disabled={addForm.processing}
                    className="rounded-xl bg-emerald-600 hover:bg-emerald-700 px-5 py-2 text-sm font-semibold text-white shadow-sm transition"
                  >
                    Save Target
                  </button>
                </div>
              </form>
            </div>
          </div>
        ) : null}

        {/* Modal: Move Target to Different Category */}
        {showMoveConfirmModal ? (
          <div className="fixed inset-0 z-50 flex items-center justify-center bg-slate-900/40 p-4 backdrop-blur-sm">
            <div className="w-full max-w-lg rounded-3xl bg-white dark:bg-slate-900 p-6 shadow-xl space-y-5 border border-slate-200 dark:border-slate-800">
              <div className="space-y-1">
                <h3 className="text-lg font-bold text-slate-900 dark:text-slate-100">
                  Move Target to Different Category?
                </h3>
                <p className="text-xs text-slate-500 dark:text-slate-400">
                  Are you sure you want to move this target to another category?
                </p>
              </div>

              <div className="flex justify-end gap-2 pt-2">
                <button
                  type="button"
                  onClick={() => {
                    setShowMoveConfirmModal(false);
                    setPendingMove(null);
                  }}
                  className="rounded-xl px-4 py-2 text-sm font-medium text-slate-700 hover:bg-slate-100 dark:text-slate-300 transition"
                >
                  Cancel
                </button>
                <button
                  type="button"
                  onClick={confirmTargetMove}
                  className="rounded-xl bg-emerald-600 hover:bg-emerald-700 px-5 py-2 text-sm font-semibold text-white shadow-sm transition"
                >
                  Confirm Move
                </button>
              </div>
            </div>
          </div>
        ) : null}

        {/* Modal: Delete Main Target */}
        {deletingIndicatorId !== null ? (
          <div className="fixed inset-0 z-50 flex items-center justify-center bg-slate-900/40 p-4 backdrop-blur-sm">
            <div className="w-full max-w-lg rounded-3xl bg-white dark:bg-slate-900 p-6 shadow-xl space-y-5 border border-slate-200 dark:border-slate-800">
              <div className="space-y-1">
                <h3 className="text-lg font-bold text-slate-900 dark:text-slate-100">
                  Delete selected target and its sub-target
                </h3>
                <p className="text-xs text-slate-500 dark:text-slate-400">
                  Are you sure you want to delete this target and all of its sub-targets? This action cannot be undone.
                </p>
              </div>

              <div className="flex justify-end gap-2 pt-2">
                <button
                  type="button"
                  onClick={() => setDeletingIndicatorId(null)}
                  className="rounded-xl px-4 py-2 text-sm font-medium text-slate-700 hover:bg-slate-100 dark:text-slate-300 transition"
                >
                  Cancel
                </button>
                <button
                  type="button"
                  onClick={() => {
                    router.delete(`/inertia/rpmo-management/harmonized-ipc/${deletingIndicatorId}`, {
                      onSuccess: () => setDeletingIndicatorId(null),
                    });
                  }}
                  className="rounded-xl bg-red-600 hover:bg-red-700 px-5 py-2 text-sm font-semibold text-white shadow-sm transition"
                >
                  Delete Target
                </button>
              </div>
            </div>
          </div>
        ) : null}

        {/* Modal: Delete Sub-target */}
        {deletingSubTargetId !== null ? (
          <div className="fixed inset-0 z-50 flex items-center justify-center bg-slate-900/40 p-4 backdrop-blur-sm">
            <div className="w-full max-w-lg rounded-3xl bg-white dark:bg-slate-900 p-6 shadow-xl space-y-5 border border-slate-200 dark:border-slate-800">
              <div className="space-y-1">
                <h3 className="text-lg font-bold text-slate-900 dark:text-slate-100">
                  Delete selected sub-target
                </h3>
                <p className="text-xs text-slate-500 dark:text-slate-400">
                  Are you sure you want to delete this sub-target? This action cannot be undone.
                </p>
              </div>

              <div className="flex justify-end gap-2 pt-2">
                <button
                  type="button"
                  onClick={() => setDeletingSubTargetId(null)}
                  className="rounded-xl px-4 py-2 text-sm font-medium text-slate-700 hover:bg-slate-100 dark:text-slate-300 transition"
                >
                  Cancel
                </button>
                <button
                  type="button"
                  onClick={() => {
                    router.delete(`/inertia/rpmo-management/harmonized-ipc-item/${deletingSubTargetId}`, {
                      onSuccess: () => setDeletingSubTargetId(null),
                    });
                  }}
                  className="rounded-xl bg-red-600 hover:bg-red-700 px-5 py-2 text-sm font-semibold text-white shadow-sm transition"
                >
                  Delete Sub-target
                </button>
              </div>
            </div>
          </div>
        ) : null}
      </section>
    </AppLayout>
  );
}

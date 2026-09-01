import React, { useState, useEffect, useRef } from 'react';
import { Head, router, useForm } from '@inertiajs/react';
import AppLayout from '../../Layouts/AppLayout';
import UserAvatar from '../../Components/UserAvatar';
import FormattedText, { formatTextValue } from '../../Components/FormattedText';
import AutoResizingTextarea, { adjustTextareaHeight } from '../../Components/AutoResizingTextarea';

import {
  Plus,
  RotateCcw,
  Check,
  X,
  Lock,
  Unlock,
  GripVertical,
  Pencil,
  Trash2,
  PlusCircle,
  FilePlus,
  ChevronRight,
  Sliders,
  Copy,
  Printer,
  Target,
  Search,
} from 'lucide-react';

type SubTargetRow = {
  id: number;
  indicatorId: number;
  newSemester: string;
  semester: string;
  displayOrder: number | null;
  description: string;
  efficiency: string;
  quality: string;
  timeliness: string;
  movs: string;
  remarks: string;
  indiStatus: number;
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
  targetGroupId: number | null;
  targetYear: number;
  kraCategory: number;
  displayOrder: number | null;
  activity: string;
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
  filters: {
    search: string;
    year: string;
    category: string;
    semester: string;
    perPage: string;
    showOnlyDuplicates: boolean;
  };
  includeStrategicFunction: boolean;
  userProfile: {
    fullName: string;
    position: string;
    designation: string;
    divisionName: string;
    sectionName: string;
  };
  isLocked: boolean;
  years: Array<{ value: string; label: string }>;
  categories: Array<{ value: string; label: string }>;
  semesters: Array<{ value: string; label: string }>;
  perPageOptions: Array<{ value: string; label: string }>;
  targets: {
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

function getPaginationPages(currentPage: number, lastPage: number): (number | string)[] {
  if (lastPage <= 7) {
    return Array.from({ length: lastPage }, (_, i) => i + 1);
  }

  if (currentPage <= 4) {
    return [1, 2, 3, 4, 5, '...', lastPage];
  }

  if (currentPage >= lastPage - 3) {
    return [1, '...', lastPage - 4, lastPage - 3, lastPage - 2, lastPage - 1, lastPage];
  }

  return [1, '...', currentPage - 1, currentPage, currentPage + 1, '...', lastPage];
}

export default function AnnualTarget({
  appName,
  user,
  filters,
  includeStrategicFunction,
  userProfile,
  isLocked,
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
    search: filters.search || '',
    year: filters.year || String(new Date().getFullYear()),
    category: filters.category || '',
    semester: filters.semester || '',
    perPage: String(filters.perPage || 10),
    duplicates: Boolean(filters.showOnlyDuplicates),
  });

  useEffect(() => {
    filterForm.setData({
      search: filters.search || '',
      year: filters.year || String(new Date().getFullYear()),
      category: filters.category || '',
      semester: filters.semester || '',
      perPage: String(filters.perPage || 10),
      duplicates: Boolean(filters.showOnlyDuplicates),
    });
  }, [filters.year, filters.category, filters.semester, filters.search, filters.perPage, filters.showOnlyDuplicates]);

  // Inline Editing Group State & Pending Sub-targets
  const [editingIndicatorId, setEditingIndicatorId] = useState<number | null>(null);
  const [isCreatingSubTarget, setIsCreatingSubTarget] = useState<boolean>(false);
  const [pendingSubTargets, setPendingSubTargets] = useState<PendingSubTarget[]>([]);

  const inlineEditForm = useForm({
    activity: '',
    category: 2,
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
    year: Number(filters.year) || new Date().getFullYear(),
    category: 1,
    activity: '',
    semester: 1,
    description: '',
    efficiency: '',
    quality: '',
    timeliness: '',
    movs: '',
    remarks: '',
  });

  // Modals for Deleting, Lock, Unlock, Copy
  const [deletingIndicatorId, setDeletingIndicatorId] = useState<number | null>(null);
  const [deletingSubTargetId, setDeletingSubTargetId] = useState<number | null>(null);
  const [showLockModal, setShowLockModal] = useState(false);
  const [showUnlockModal, setShowUnlockModal] = useState(false);
  const [showCopyModal, setShowCopyModal] = useState(false);

  // Copy Modal State
  const [copyTab, setCopyTab] = useState<'staff' | 'harmonized'>('staff');
  const [copyData, setCopyData] = useState<{
    staffUsers: Array<{ id: number; name: string; position?: string }>;
    harmonizedPositions: Array<{ id: number; name: string }>;
    copyTargets: {
      data: Array<{
        indicatorId: number;
        kraCategory: number;
        activity: string;
        targetYear: number;
        isExisting: boolean;
        subTargets: Array<{
          id: number;
          newSemester: number;
          description: string;
          efficiency: string;
          quality: string;
          timeliness: string;
          movs: string;
          remarks: string;
        }>;
      }>;
      total: number;
      currentPage: number;
      lastPage: number;
    };
  }>({
    staffUsers: [],
    harmonizedPositions: [],
    copyTargets: { data: [], total: 0, currentPage: 1, lastPage: 1 },
  });

  const [copyStaffUserId, setCopyStaffUserId] = useState('');
  const [copyStaffYear, setCopyStaffYear] = useState(filters.year || String(new Date().getFullYear()));
  const [copyStaffCategory, setCopyStaffCategory] = useState('');
  const [copyStaffSemester, setCopyStaffSemester] = useState('');
  const [copyStaffStatusFilter, setCopyStaffStatusFilter] = useState('');
  const [copyStaffSearch, setCopyStaffSearch] = useState('');
  const [copyStaffPage, setCopyStaffPage] = useState(1);

  const [copyHarmonizedPositionId, setCopyHarmonizedPositionId] = useState('');
  const [copyHarmonizedYear, setCopyHarmonizedYear] = useState(filters.year || String(new Date().getFullYear()));
  const [copyHarmonizedCategory, setCopyHarmonizedCategory] = useState('');
  const [copyHarmonizedSemester, setCopyHarmonizedSemester] = useState('');
  const [copyHarmonizedStatusFilter, setCopyHarmonizedStatusFilter] = useState('');
  const [copyHarmonizedSearch, setCopyHarmonizedSearch] = useState('');
  const [copyHarmonizedPage, setCopyHarmonizedPage] = useState(1);

  // Drag Reordering & Move Confirmation State
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

  // Close context menu on Escape
  useEffect(() => {
    const handleClose = (e: KeyboardEvent) => {
      if (e.key === 'Escape' && contextMenu) {
        setContextMenu(null);
        setActiveSubMenu(null);
      }
    };
    window.addEventListener('keydown', handleClose);
    return () => {
      window.removeEventListener('keydown', handleClose);
    };
  }, [contextMenu]);

  // Fetch copy target choices dynamically when filters change
  const fetchCopyTargets = () => {
    const params = new URLSearchParams({
      tab: copyTab,
      staffUserId: copyStaffUserId,
      harmonizedPositionId: copyHarmonizedPositionId,
      year: copyTab === 'staff' ? copyStaffYear : copyHarmonizedYear,
      category: copyTab === 'staff' ? copyStaffCategory : copyHarmonizedCategory,
      semester: copyTab === 'staff' ? copyStaffSemester : copyHarmonizedSemester,
      statusFilter: copyTab === 'staff' ? copyStaffStatusFilter : copyHarmonizedStatusFilter,
      search: copyTab === 'staff' ? copyStaffSearch : copyHarmonizedSearch,
      page: String(copyTab === 'staff' ? copyStaffPage : copyHarmonizedPage),
    });

    fetch(`/ipcrf/annualtarget/copy-data?${params.toString()}`)
      .then((res) => res.json())
      .then((data) => {
        setCopyData(data);
      })
      .catch(() => {});
  };

  useEffect(() => {
    if (showCopyModal) {
      fetchCopyTargets();
    }
  }, [
    showCopyModal,
    copyTab,
    copyStaffUserId,
    copyStaffYear,
    copyStaffCategory,
    copyStaffSemester,
    copyStaffStatusFilter,
    copyStaffSearch,
    copyStaffPage,
    copyHarmonizedPositionId,
    copyHarmonizedYear,
    copyHarmonizedCategory,
    copyHarmonizedSemester,
    copyHarmonizedStatusFilter,
    copyHarmonizedSearch,
    copyHarmonizedPage,
  ]);

  const openCopyTargetsModal = () => {
    setShowCopyModal(true);
  };

  const searchTimerRef = useRef<NodeJS.Timeout | null>(null);

  const submitFilters = (overrides: Record<string, any> = {}) => {
    if (searchTimerRef.current) clearTimeout(searchTimerRef.current);
    const payload = {
      search: filterForm.data.search,
      year: filterForm.data.year,
      category: filterForm.data.category,
      semester: filterForm.data.semester,
      perPage: filterForm.data.perPage,
      duplicates: filterForm.data.duplicates,
      page: 1,
      ...overrides,
    };
    router.post('/ipcrf/annualtarget/filter', payload, {
      replace: true,
      preserveScroll: true,
    });
  };

  const handleSearchChange = (val: string) => {
    filterForm.setData('search', val);
    if (searchTimerRef.current) clearTimeout(searchTimerRef.current);
    searchTimerRef.current = setTimeout(() => {
      submitFilters({ search: val, page: 1 });
    }, 350);
  };

  const handleYearChange = (year: string) => {
    filterForm.setData('year', year);
    submitFilters({ year, page: 1 });
  };

  const handleCategoryChange = (category: string) => {
    filterForm.setData('category', category);
    submitFilters({ category, page: 1 });
  };

  const handleSemesterChange = (semester: string) => {
    filterForm.setData('semester', semester);
    submitFilters({ semester, page: 1 });
  };

  const handlePerPageChange = (perPage: string) => {
    filterForm.setData('perPage', perPage);
    submitFilters({ perPage, page: 1 });
  };

  const resetFilters = () => {
    if (searchTimerRef.current) clearTimeout(searchTimerRef.current);
    filterForm.setData({
      search: '',
      year: String(new Date().getFullYear()),
      category: '',
      semester: '',
      perPage: '10',
      duplicates: false,
    });
    router.post(
      '/ipcrf/annualtarget/filter',
      {
        search: '',
        year: String(new Date().getFullYear()),
        category: '',
        semester: '',
        perPage: '10',
        duplicates: false,
        page: 1,
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
      category: group.kraCategory,
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

    inlineEditForm.setData({
      activity: formatTextValue(group.activity, ''),
      category: group.kraCategory,
      editRows: rowsMap,
      pendingSubTargets: [newPending],
    });

    setPendingSubTargets([newPending]);
  };

  const cancelInlineEdit = () => {
    setEditingIndicatorId(null);
    setIsCreatingSubTarget(false);
    setPendingSubTargets([]);
  };

  const handleSaveInlineEdit = (group: GroupRow) => {
    inlineEditForm.patch(`/ipcrf/annualtarget/${group.indicatorId}`, {
      onSuccess: () => {
        cancelInlineEdit();
      },
    });
  };

  const openAddModalForCategory = (catVal: number) => {
    setAddingKraCategory(catVal);
    addForm.setData({
      year: Number(filterForm.data.year) || new Date().getFullYear(),
      category: catVal,
      activity: '',
      semester: 1,
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
    addForm.post('/ipcrf/annualtarget/store', {
      onSuccess: () => {
        setShowAddModal(false);
      },
    });
  };

  const handleLockTargets = () => {
    router.post(
      '/ipcrf/annualtarget/lock',
      { year: filterForm.data.year },
      { onSuccess: () => setShowLockModal(false) }
    );
  };

  const handleUnlockTargets = () => {
    router.post(
      '/ipcrf/annualtarget/unlock',
      { year: filterForm.data.year },
      { onSuccess: () => setShowUnlockModal(false) }
    );
  };

  const handleCopySingleGroup = (indicatorId: number) => {
    const route = copyTab === 'staff' ? '/ipcrf/annualtarget/copy-staff' : '/ipcrf/annualtarget/copy-harmonized';
    const targetYear = Number(filterForm.data.year) || new Date().getFullYear();

    router.post(route, { indicatorId, targetYear }, { onSuccess: () => fetchCopyTargets() });
  };

  const handleDrop = (source: DragPayload, target: DragPayload) => {
    if (source.indicatorId === target.indicatorId && source.type === target.type) return;

    if (source.kra !== target.kra) {
      setPendingMove({ source, target });
      setShowMoveConfirmModal(true);
      return;
    }

    router.post('/ipcrf/annualtarget/reorder', { source, target }, { preserveScroll: true });
  };

  const confirmTargetMove = () => {
    if (!pendingMove) return;
    const { source, target } = pendingMove;
    setShowMoveConfirmModal(false);
    setPendingMove(null);

    router.post('/ipcrf/annualtarget/reorder', { source, target }, { preserveScroll: true });
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
    if (targetStatus === 3 || isLocked) return;

    const canDeleteTarget = true;
    const canDeleteSubTarget = subTargetCount >= 2;

    let x = window.scrollX + e.clientX;
    let y = window.scrollY + e.clientY;

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

  return (
    <AppLayout appName={appName} user={user} sidebar={navigation?.sidebar ?? []}>
      <Head title="Annual Target Matrix - IPCRF" />

      <div
        className="space-y-3"
        onClick={() => {
          if (contextMenu) {
            setContextMenu(null);
            setActiveSubMenu(null);
          }
        }}
      >
        {/* TOP FILTER, PROFILE & ACTIONS CARD */}
        <div className="rounded-xl border border-border bg-card p-3 sm:p-4 shadow-2xs space-y-3">
          {/* HEADER */}
          <div className="flex flex-col sm:flex-row sm:items-center justify-between gap-2 border-b border-border/80 pb-3">
            <div className="flex items-center gap-2.5">
              <div className="size-8 rounded-lg bg-emerald-500/10 text-emerald-700 dark:text-emerald-400 flex items-center justify-center font-bold">
                <Target className="size-4.5" />
              </div>
              <div>
                <h1 className="text-sm font-bold tracking-tight text-foreground flex items-center gap-2">
                  <span>Annual Performance Target Matrix</span>
                  <span className="rounded-full bg-emerald-500/10 text-emerald-700 dark:text-emerald-400 font-mono text-[10px] font-bold px-2 py-0.2 border border-emerald-500/20">
                    {targets.total} Total Targets
                  </span>
                </h1>
                <p className="text-[11px] text-muted-foreground">
                  Define annual commitments, KRA categories, success indicators, and semestral performance distributions.
                </p>
              </div>
            </div>

            {/* ACTION BUTTONS */}
            <div className="flex flex-wrap items-center gap-1.5">
              <button
                type="button"
                onClick={resetFilters}
                className="h-8 inline-flex items-center gap-1.5 rounded-lg border border-input bg-background px-2.5 text-xs font-medium text-muted-foreground hover:text-foreground hover:bg-muted transition cursor-pointer"
                title="Reset all filters"
              >
                <RotateCcw className="size-3" />
                <span>Reset</span>
              </button>

              {!isLocked ? (
                <button
                  type="button"
                  onClick={openCopyTargetsModal}
                  className="h-8 inline-flex items-center gap-1.5 rounded-lg bg-violet-600 hover:bg-violet-700 text-white px-3 text-xs font-semibold shadow-xs transition cursor-pointer"
                >
                  <Copy className="size-3.5" />
                  <span>Copy Target</span>
                </button>
              ) : null}

              <button
                type="button"
                onClick={() => window.print()}
                className="h-8 inline-flex items-center gap-1.5 rounded-lg border border-input bg-background px-2.5 text-xs font-medium text-foreground hover:bg-muted transition cursor-pointer"
              >
                <Printer className="size-3.5" />
                <span>Print</span>
              </button>

              {isLocked ? (
                <button
                  type="button"
                  onClick={() => setShowUnlockModal(true)}
                  className="h-8 inline-flex items-center gap-1.5 rounded-lg bg-amber-600 hover:bg-amber-700 px-3 text-xs font-semibold text-white transition shadow-xs cursor-pointer"
                >
                  <Unlock className="size-3.5" />
                  <span>Unlock Target</span>
                </button>
              ) : (
                <button
                  type="button"
                  disabled={!filterForm.data.year}
                  onClick={() => setShowLockModal(true)}
                  className="h-8 inline-flex items-center gap-1.5 rounded-lg bg-emerald-600 hover:bg-emerald-700 disabled:opacity-50 disabled:cursor-not-allowed px-3 text-xs font-semibold text-white transition shadow-xs cursor-pointer"
                >
                  <Check className="size-3.5" />
                  <span>Save &amp; Lock Target</span>
                </button>
              )}
            </div>
          </div>

          {/* USER PROFILE STRIP */}
          {userProfile && (
            <div className="rounded-lg border border-border bg-muted/30 p-2.5 overflow-x-auto">
              <table className="w-full border-0 border-collapse text-xs">
                <tbody>
                  <tr className="align-top">
                    <td className="pr-6 whitespace-nowrap">
                      <div className="text-[10px] font-semibold uppercase text-muted-foreground">Full Name</div>
                      <div className="mt-0.5 font-bold uppercase text-foreground flex items-center gap-2">
                        <UserAvatar
                          user={{
                            name: userProfile.fullName,
                            avatar_url: (userProfile as any).avatarUrl,
                            avatar: (userProfile as any).avatar,
                          }}
                          size="sm"
                        />
                        <span>{userProfile.fullName || '-'}</span>
                      </div>
                    </td>
                    <td className="pr-6 whitespace-nowrap">
                      <div className="text-[10px] font-semibold uppercase text-muted-foreground">Position</div>
                      <div className="mt-0.5 font-bold uppercase text-foreground">{userProfile.position || '-'}</div>
                    </td>
                    <td className="pr-6 whitespace-nowrap">
                      <div className="text-[10px] font-semibold uppercase text-muted-foreground">Designation</div>
                      <div className="mt-0.5 font-bold uppercase text-foreground">{userProfile.designation || '-'}</div>
                    </td>
                    <td className="pr-6 whitespace-nowrap">
                      <div className="text-[10px] font-semibold uppercase text-muted-foreground">Division Name</div>
                      <div className="mt-0.5 font-bold uppercase text-foreground">{userProfile.divisionName || '-'}</div>
                    </td>
                    <td className="whitespace-nowrap">
                      <div className="text-[10px] font-semibold uppercase text-muted-foreground">Section Name</div>
                      <div className="mt-0.5 font-bold uppercase text-foreground">{userProfile.sectionName || '-'}</div>
                    </td>
                  </tr>
                </tbody>
              </table>
            </div>
          )}

          {/* FILTERS FORM */}
          <div className="grid gap-2.5 sm:grid-cols-2 lg:grid-cols-6 items-end">
            {/* Search Input */}
            <div className="space-y-1 sm:col-span-2">
              <label className="text-[11px] font-semibold text-muted-foreground">Search Target Content</label>
              <div className="relative">
                <Search className="size-3.5 absolute left-2.5 top-1/2 -translate-y-1/2 text-muted-foreground" />
                <input
                  type="text"
                  value={filterForm.data.search}
                  onChange={(e) => handleSearchChange(e.target.value)}
                  placeholder="Search activity, description, outputs..."
                  className="h-8 w-full rounded-lg border border-input bg-background pl-8 pr-7 text-xs text-foreground placeholder:text-muted-foreground/60 outline-hidden focus:ring-2 focus:ring-ring"
                />
                {filterForm.data.search && (
                  <button
                    type="button"
                    onClick={() => {
                      filterForm.setData('search', '');
                      if (searchTimerRef.current) clearTimeout(searchTimerRef.current);
                      submitFilters({ search: '', page: 1 });
                    }}
                    className="absolute right-2 top-1/2 -translate-y-1/2 text-muted-foreground hover:text-foreground"
                  >
                    <X className="size-3" />
                  </button>
                )}
              </div>
            </div>

            {/* Year Selector */}
            <div className="space-y-1">
              <label className="text-[11px] font-semibold text-muted-foreground">Target Year</label>
              <select
                value={filterForm.data.year}
                onChange={(e) => handleYearChange(e.target.value)}
                className="h-8 w-full rounded-lg border border-input bg-background px-2.5 text-xs text-foreground outline-hidden focus:ring-2 focus:ring-ring cursor-pointer"
              >
                {years.map((y) => (
                  <option key={y.value} value={y.value}>
                    Year: {y.label}
                  </option>
                ))}
              </select>
            </div>

            {/* Category Selector */}
            <div className="space-y-1">
              <label className="text-[11px] font-semibold text-muted-foreground">KRA Category</label>
              <select
                value={filterForm.data.category}
                onChange={(e) => handleCategoryChange(e.target.value)}
                className="h-8 w-full rounded-lg border border-input bg-background px-2.5 text-xs text-foreground outline-hidden focus:ring-2 focus:ring-ring cursor-pointer"
              >
                <option value="">All Categories</option>
                {categories.map((c) => (
                  <option key={c.value} value={c.value}>
                    {c.label}
                  </option>
                ))}
              </select>
            </div>

            {/* Semester Selector */}
            <div className="space-y-1">
              <label className="text-[11px] font-semibold text-muted-foreground">Semester</label>
              <select
                value={filterForm.data.semester}
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

            {/* Records per page & duplicate toggle */}
            <div className="space-y-1">
              <label className="text-[11px] font-semibold text-muted-foreground">Per Page</label>
              <select
                value={filterForm.data.perPage}
                onChange={(e) => handlePerPageChange(e.target.value)}
                className="h-8 w-full rounded-lg border border-input bg-background px-2.5 text-xs text-foreground outline-hidden focus:ring-2 focus:ring-ring cursor-pointer"
              >
                {perPageOptions.map((opt) => (
                  <option key={String(opt.value)} value={String(opt.value)}>
                    {opt.label}
                  </option>
                ))}
              </select>
            </div>
          </div>
        </div>

          {/* Categorized Table Grid */}
          <div className="overflow-x-auto rounded-xl border border-border bg-card shadow-2xs">
            <table className="w-full min-w-[1100px] border-collapse text-xs text-left">
              <thead className="bg-muted/60 text-[11px] font-bold uppercase tracking-wider text-muted-foreground border-b border-border">
                <tr>
                  <th className="border-b border-r border-border px-3 py-3 text-center w-[70px] min-w-[70px] whitespace-nowrap">
                    Action
                  </th>
                  <th className="border-b border-r border-border px-3 py-3 whitespace-nowrap w-[250px] min-w-[250px]">
                    Key Result Area
                  </th>
                  <th className="border-b border-r border-border px-3 py-3 whitespace-nowrap w-[140px] min-w-[140px]">
                    Semester
                  </th>
                  <th className="border-b border-r border-border px-3 py-3 whitespace-nowrap min-w-[220px]">
                    Success Indicator
                  </th>
                  <th className="border-b border-r border-border px-3 py-3 whitespace-nowrap w-[110px] min-w-[110px]">
                    EFFICIENCY
                  </th>
                  <th className="border-b border-r border-border px-3 py-3 whitespace-nowrap w-[110px] min-w-[110px]">
                    QUALITY
                  </th>
                  <th className="border-b border-r border-border px-3 py-3 whitespace-nowrap w-[110px] min-w-[110px]">
                    TIMELINESS
                  </th>
                  <th className="border-b border-r border-border px-3 py-3 whitespace-nowrap w-[160px] min-w-[160px]">
                    MOVS
                  </th>
                  <th className="border-b border-border px-3 py-3 whitespace-nowrap w-[180px] min-w-[180px]">
                    REMARKS
                  </th>
                </tr>
              </thead>

              {categories
                .filter((cat) => !filterForm.data.category || filterForm.data.category === cat.value)
                .map((cat) => {
                  const catVal = Number(cat.value);
                  const catGroups = groups.filter((g) => g.kraCategory === catVal);

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
                        <tr className="bg-muted/80 border-b border-border font-bold">
                          <td colSpan={9} className="px-3 py-2">
                            <div className="sticky left-3 inline-flex items-center gap-2.5">
                              <span className="text-xs uppercase tracking-wide text-emerald-700 dark:text-emerald-400 font-bold">{cat.label}</span>
                              {!isLocked ? (
                                <button
                                  type="button"
                                  onClick={() => openAddModalForCategory(catVal)}
                                  className="inline-flex items-center gap-1 rounded-md bg-emerald-500/10 px-2 py-0.5 text-xs font-bold text-emerald-700 dark:text-emerald-400 hover:bg-emerald-500/20 border border-emerald-500/20 cursor-pointer shadow-2xs transition"
                                >
                                  <Plus className="size-3.5" />
                                  <span>Add Target</span>
                                </button>
                              ) : null}
                            </div>
                          </td>
                        </tr>
                      </tbody>

                      {catGroups.length === 0 ? (
                        <tbody>
                          <tr>
                            <td colSpan={9} className="border-b border-border px-3 py-8 text-center text-muted-foreground">
                              No record found in this category.
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
                                      kra: group.kraCategory,
                                    });
                                  }}
                                  className={`hover:bg-muted/30 transition-colors align-top border-b border-border ${
                                    isEditingGroup ? 'bg-sky-50/80 dark:bg-sky-950/40' : ''
                                  } ${
                                    isDragging ? 'opacity-50 bg-muted' : ''
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
                                            group.kraCategory,
                                            group.indicatorId,
                                            row.id,
                                            group.rows.length,
                                            group.targetStatus,
                                            true
                                          )
                                        }
                                        className="border-b border-r border-border px-3 py-3 align-top text-center"
                                      >
                                        <div className="flex items-center justify-center gap-1">
                                          {isEditingGroup ? (
                                            <div className="flex flex-col gap-1 items-center">
                                              <button
                                                type="button"
                                                onClick={() => handleSaveInlineEdit(group)}
                                                className="w-8 h-8 rounded-lg bg-emerald-600 hover:bg-emerald-700 text-white flex items-center justify-center shadow-xs transition"
                                                title="Save Changes"
                                              >
                                                <Check className="w-4 h-4 stroke-[3]" />
                                              </button>
                                              <button
                                                type="button"
                                                onClick={cancelInlineEdit}
                                                className="w-8 h-8 rounded-lg bg-amber-500 hover:bg-amber-600 text-white flex items-center justify-center shadow-xs transition"
                                                title="Cancel"
                                              >
                                                <X className="w-4 h-4 stroke-[3]" />
                                              </button>
                                            </div>
                                          ) : group.targetStatus === 3 || isLocked ? (
                                            <Lock className="w-4 h-4 text-muted-foreground" title="Locked target" />
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
                                                    kra: group.kraCategory,
                                                  })
                                                );
                                                setDraggingIndicatorId(group.indicatorId);
                                              }}
                                              onDragEnd={() => setDraggingIndicatorId(null)}
                                              className="p-1 cursor-grab active:cursor-grabbing text-muted-foreground hover:text-foreground transition"
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
                                            group.kraCategory,
                                            group.indicatorId,
                                            row.id,
                                            group.rows.length,
                                            group.targetStatus,
                                            true
                                          )
                                        }
                                        className="border-b border-r border-border px-3 py-3 align-top text-xs text-foreground font-medium"
                                      >
                                         {isEditingExistingRows ? (
                                            <AutoResizingTextarea
                                              value={inlineEditForm.data.activity}
                                              onChange={(e) => inlineEditForm.setData('activity', e.target.value)}
                                              rows={2}
                                              className="w-full rounded-lg border border-input bg-background p-2 text-xs text-foreground focus:outline-none focus:ring-2 focus:ring-ring shadow-inner"
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
                                        group.kraCategory,
                                        group.indicatorId,
                                        row.id,
                                        group.rows.length,
                                        group.targetStatus,
                                        false
                                      )
                                    }
                                    className="border-b border-r border-border px-3 py-3 align-top text-xs text-foreground"
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
                                        className="w-full rounded-lg border border-input bg-background p-1.5 text-xs text-foreground focus:outline-none focus:ring-2 focus:ring-ring"
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
                                        group.kraCategory,
                                        group.indicatorId,
                                        row.id,
                                        group.rows.length,
                                        group.targetStatus,
                                        false
                                      )
                                    }
                                    className="border-b border-r border-border px-3 py-3 align-top text-xs text-foreground"
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
                                          className="w-full rounded-lg border border-input bg-background p-2 text-xs text-foreground focus:outline-none focus:ring-2 focus:ring-ring shadow-inner"
                                        />
                                     ) : (
                                       <FormattedText value={row.description} />
                                     )}
                                   </td>

                                   <td
                                     onContextMenu={(e) =>
                                       openRightClickMenu(
                                         e,
                                         group.kraCategory,
                                         group.indicatorId,
                                         row.id,
                                         group.rows.length,
                                         group.targetStatus,
                                         false
                                       )
                                     }
                                     className="border-b border-r border-border px-3 py-3 align-top text-xs text-foreground"
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
                                         className="w-full rounded-lg border border-input bg-background p-2 text-xs text-foreground focus:outline-none focus:ring-2 focus:ring-ring shadow-inner"
                                       />
                                     ) : (
                                       <FormattedText value={row.efficiency} />
                                     )}
                                   </td>

                                   <td
                                     onContextMenu={(e) =>
                                       openRightClickMenu(
                                         e,
                                         group.kraCategory,
                                         group.indicatorId,
                                         row.id,
                                         group.rows.length,
                                         group.targetStatus,
                                         false
                                       )
                                     }
                                     className="border-b border-r border-border px-3 py-3 align-top text-xs text-foreground"
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
                                         className="w-full rounded-lg border border-input bg-background p-2 text-xs text-foreground focus:outline-none focus:ring-2 focus:ring-ring shadow-inner"
                                       />
                                     ) : (
                                       <FormattedText value={row.quality} />
                                     )}
                                   </td>

                                   <td
                                     onContextMenu={(e) =>
                                       openRightClickMenu(
                                         e,
                                         group.kraCategory,
                                         group.indicatorId,
                                         row.id,
                                         group.rows.length,
                                         group.targetStatus,
                                         false
                                       )
                                     }
                                     className="border-b border-r border-border px-3 py-3 align-top text-xs text-foreground"
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
                                         className="w-full rounded-lg border border-input bg-background p-2 text-xs text-foreground focus:outline-none focus:ring-2 focus:ring-ring shadow-inner"
                                       />
                                     ) : (
                                       <FormattedText value={row.timeliness} />
                                     )}
                                   </td>

                                   <td
                                     onContextMenu={(e) =>
                                       openRightClickMenu(
                                         e,
                                         group.kraCategory,
                                         group.indicatorId,
                                         row.id,
                                         group.rows.length,
                                         group.targetStatus,
                                         false
                                       )
                                     }
                                     className="border-b border-r border-border px-3 py-3 align-top text-xs text-foreground"
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
                                         className="w-full rounded-lg border border-input bg-background p-2 text-xs text-foreground focus:outline-none focus:ring-2 focus:ring-ring shadow-inner"
                                       />
                                     ) : (
                                       <FormattedText value={row.movs} />
                                     )}
                                   </td>

                                   <td
                                     onContextMenu={(e) =>
                                       openRightClickMenu(
                                         e,
                                         group.kraCategory,
                                         group.indicatorId,
                                         row.id,
                                         group.rows.length,
                                         group.targetStatus,
                                         false
                                       )
                                     }
                                     className="border-b border-border px-3 py-3 align-top text-xs text-foreground"
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
                                         className="w-full rounded-lg border border-input bg-background p-2 text-xs text-foreground focus:outline-none focus:ring-2 focus:ring-ring shadow-inner"
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
                                      className="border-b border-border bg-sky-50/90 dark:bg-sky-950/50 text-xs align-top"
                                    >
                                      <td className="border-b border-r border-border px-3 py-3 align-top">
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
                                          className="w-full rounded-lg border border-input bg-background p-1.5 text-xs text-foreground focus:outline-none focus:ring-2 focus:ring-ring"
                                        >
                                          {semesters.map((s) => (
                                            <option key={s.value} value={s.value}>
                                              {s.label}
                                            </option>
                                          ))}
                                        </select>
                                      </td>

                                       <td className="border-b border-r border-border px-3 py-3 align-top">
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
                                           className="w-full rounded-lg border border-input bg-background p-2 text-xs text-foreground focus:outline-none focus:ring-2 focus:ring-ring shadow-inner"
                                         />
                                       </td>

                                       <td className="border-b border-r border-border px-3 py-3 align-top">
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
                                           className="w-full rounded-lg border border-input bg-background p-2 text-xs text-foreground focus:outline-none focus:ring-2 focus:ring-ring shadow-inner"
                                         />
                                       </td>

                                       <td className="border-b border-r border-border px-3 py-3 align-top">
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
                                           className="w-full rounded-lg border border-input bg-background p-2 text-xs text-foreground focus:outline-none focus:ring-2 focus:ring-ring shadow-inner"
                                         />
                                       </td>

                                       <td className="border-b border-r border-border px-3 py-3 align-top">
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
                                           className="w-full rounded-lg border border-input bg-background p-2 text-xs text-foreground focus:outline-none focus:ring-2 focus:ring-ring shadow-inner"
                                         />
                                       </td>

                                       <td className="border-b border-r border-border px-3 py-3 align-top">
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
                                           className="w-full rounded-lg border border-input bg-background p-2 text-xs text-foreground focus:outline-none focus:ring-2 focus:ring-ring shadow-inner"
                                         />
                                       </td>

                                       <td className="border-b border-border px-3 py-3 align-top">
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
                                           className="w-full rounded-lg border border-input bg-background p-2 text-xs text-foreground focus:outline-none focus:ring-2 focus:ring-ring shadow-inner"
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
              <div className="text-xs text-muted-foreground">
                Showing {targets.from ?? 0} to {targets.to ?? 0} of {targets.total} records
              </div>

              <div className="flex flex-wrap items-center gap-1.5">
                {targets.currentPage === 1 ? (
                  <span className="inline-flex cursor-not-allowed items-center rounded-lg border border-border bg-muted/50 px-3 py-1.5 text-xs text-muted-foreground select-none">
                    Previous
                  </span>
                ) : (
                  <button
                    type="button"
                    onClick={() => submitFilters({ page: targets.currentPage - 1 })}
                    className="inline-flex cursor-pointer items-center rounded-lg border border-input bg-background px-3 py-1.5 text-xs text-foreground hover:bg-muted transition-colors"
                  >
                    Previous
                  </button>
                )}

                {getPaginationPages(targets.currentPage, targets.lastPage).map((page, pIdx) => {
                  if (typeof page === 'string') {
                    return (
                      <span
                        key={`ellipsis-${pIdx}`}
                        className="inline-flex min-w-8 items-center justify-center rounded-lg border border-border bg-background px-2.5 py-1.5 text-xs text-muted-foreground select-none"
                      >
                        {page}
                      </span>
                    );
                  }

                  if (page === targets.currentPage) {
                    return (
                      <span
                        key={page}
                        aria-current="page"
                        className="inline-flex min-w-8 cursor-pointer items-center justify-center rounded-lg border border-emerald-600 bg-emerald-600 px-2.5 py-1.5 text-xs font-semibold text-white shadow-xs"
                      >
                        {page}
                      </span>
                    );
                  }

                  return (
                    <button
                      key={page}
                      type="button"
                      onClick={() => submitFilters({ page })}
                      className="inline-flex cursor-pointer items-center justify-center min-w-8 rounded-lg border border-input bg-background px-2.5 py-1.5 text-xs text-foreground hover:bg-muted transition-colors"
                    >
                      {page}
                    </button>
                  );
                })}

                {targets.currentPage === targets.lastPage ? (
                  <span className="inline-flex cursor-not-allowed items-center rounded-lg border border-border bg-muted/50 px-3 py-1.5 text-xs text-muted-foreground select-none">
                    Next
                  </span>
                ) : (
                  <button
                    type="button"
                    onClick={() => submitFilters({ page: targets.currentPage + 1 })}
                    className="inline-flex cursor-pointer items-center rounded-lg border border-input bg-background px-3 py-1.5 text-xs text-foreground hover:bg-muted transition-colors"
                  >
                    Next
                  </button>
                )}
              </div>
            </nav>
          ) : null}
        </div>

        {/* Floating Right-Click Context Menu */}
        {contextMenu ? (
          <div
            style={{ top: contextMenu.y, left: contextMenu.x, zIndex: 99999 }}
            className="absolute min-w-[14rem] rounded-xl border border-border bg-popover text-popover-foreground p-1.5 text-xs font-medium shadow-2xl space-y-1"
          >
            {/* Menu Header */}
            <div
              className="px-2.5 py-1.5 text-[10px] font-bold uppercase tracking-wider text-muted-foreground border-b border-border mb-1 flex items-center justify-between select-none"
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
                className={`flex w-full items-center justify-between gap-2 rounded-lg px-2.5 py-1.5 text-left text-foreground hover:bg-muted transition-colors ${
                  activeSubMenu === 'add' ? 'bg-muted' : ''
                }`}
              >
                <div className="flex items-center gap-2">
                  <PlusCircle className="w-4 h-4 text-foreground" />
                  <span>Add Target</span>
                </div>
                <ChevronRight className="w-3.5 h-3.5 text-muted-foreground" />
              </button>

              {/* Submenu Floating Flyout for Add */}
              {activeSubMenu === 'add' ? (
                <div
                  style={{ top: -6, left: 198, zIndex: 100000 }}
                  className="absolute min-w-[12rem] rounded-xl border border-border bg-popover text-popover-foreground p-1.5 text-xs font-medium shadow-2xl space-y-1"
                >
                  <button
                    type="button"
                    onClick={() => {
                      openAddModalForCategory(contextMenu.category);
                      setContextMenu(null);
                      setActiveSubMenu(null);
                    }}
                    className="flex w-full items-center gap-2 rounded-lg px-2.5 py-1.5 text-left text-foreground hover:bg-muted"
                  >
                    <Plus className="w-4 h-4 text-foreground" />
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
                    className="flex w-full items-center gap-2 rounded-lg px-2.5 py-1.5 text-left text-foreground hover:bg-muted"
                  >
                    <FilePlus className="w-4 h-4 text-foreground" />
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
              className="flex w-full items-center gap-2 rounded-lg px-2.5 py-1.5 text-left text-foreground hover:bg-muted transition-colors"
            >
              <Pencil className="w-4 h-4 text-amber-500" />
              <span>Edit Target</span>
            </button>

            <div className="my-1 border-t border-border" />

            {/* Menu Item 3: Delete Submenu */}
            <div className="relative">
              <button
                type="button"
                onMouseEnter={() => setActiveSubMenu('delete')}
                onClick={() => setActiveSubMenu(activeSubMenu === 'delete' ? null : 'delete')}
                className={`flex w-full items-center justify-between gap-2 rounded-lg px-2.5 py-1.5 text-left text-rose-600 hover:bg-rose-50 dark:hover:bg-rose-950/40 transition-colors ${
                  activeSubMenu === 'delete' ? 'bg-rose-50 dark:bg-rose-950/40' : ''
                }`}
              >
                <div className="flex items-center gap-2">
                  <Trash2 className="w-4 h-4 text-rose-500" />
                  <span>Delete</span>
                </div>
                <ChevronRight className="w-3.5 h-3.5 text-muted-foreground" />
              </button>

              {/* Submenu Floating Flyout for Delete */}
              {activeSubMenu === 'delete' ? (
                <div
                  style={{ top: -6, left: 198, zIndex: 100000 }}
                  className="absolute min-w-[17rem] rounded-xl border border-border bg-popover text-popover-foreground p-1.5 text-xs font-medium shadow-2xl space-y-1"
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
                        ? 'opacity-40 cursor-not-allowed text-muted-foreground'
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
                        ? 'opacity-40 cursor-not-allowed text-muted-foreground'
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
          <div className="fixed inset-0 z-50 flex items-center justify-center bg-black/60 p-4 backdrop-blur-xs">
            <div className="w-full max-w-4xl max-h-[90vh] overflow-y-auto rounded-2xl bg-card p-6 shadow-2xl space-y-5 border border-border">
              <div className="space-y-1">
                <h3 className="text-base font-bold text-foreground">Add target</h3>
                <p className="text-xs text-muted-foreground">
                  Create a new target entry inside the selected KRA category.
                </p>
              </div>

              <form onSubmit={handleSaveAddTarget} className="space-y-4">
                <div className="grid gap-4 sm:grid-cols-4 bg-muted/40 p-3 rounded-xl border border-border">
                  <div>
                    <label className="block text-[11px] font-semibold text-muted-foreground mb-1">Position</label>
                    <span className="inline-flex rounded-full bg-violet-500/10 text-violet-700 dark:text-violet-400 font-bold text-xs px-2.5 py-1 border border-violet-500/20">
                      {userProfile.position || 'Staff'}
                    </span>
                  </div>

                  <div>
                    <label className="block text-[11px] font-semibold text-muted-foreground mb-1">KRA Category</label>
                    <span className="inline-flex rounded-full bg-cyan-500/10 text-cyan-700 dark:text-cyan-400 font-bold text-xs px-2.5 py-1 border border-cyan-500/20">
                      {categories.find((c) => c.value === String(addingKraCategory))?.label || `Category #${addingKraCategory}`}
                    </span>
                  </div>

                  <div>
                    <label className="block text-[11px] font-semibold text-muted-foreground mb-1">Year</label>
                    <span className="inline-flex rounded-full bg-muted text-foreground font-bold text-xs px-2.5 py-1 border border-border">
                      {addForm.data.year}
                    </span>
                  </div>

                  <div>
                    <label className="block text-[11px] font-semibold text-muted-foreground mb-1">Semester</label>
                    <select
                      value={addForm.data.semester}
                      onChange={(e) => addForm.setData('semester', Number(e.target.value))}
                      className="h-9 w-full rounded-lg border border-input bg-background px-3 text-xs text-foreground focus:ring-2 focus:ring-ring outline-hidden cursor-pointer"
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
                    <label className="block text-xs font-semibold text-foreground mb-1">
                      Key Result Area (Activity)
                    </label>
                    <AutoResizingTextarea
                      value={addForm.data.activity}
                      onChange={(e) => addForm.setData('activity', e.target.value)}
                      rows={2}
                      className="w-full rounded-lg border border-input bg-background p-2.5 text-xs text-foreground focus:outline-none focus:ring-2 focus:ring-ring"
                      required
                    />
                  </div>

                  <div>
                    <label className="block text-xs font-semibold text-foreground mb-1">
                      Success Indicator (Description)
                    </label>
                    <AutoResizingTextarea
                      value={addForm.data.description}
                      onChange={(e) => addForm.setData('description', e.target.value)}
                      rows={2}
                      className="w-full rounded-lg border border-input bg-background p-2.5 text-xs text-foreground focus:outline-none focus:ring-2 focus:ring-ring"
                      required
                    />
                  </div>
                </div>

                <div className="flex items-center gap-3 py-1">
                  <div className="h-px flex-1 bg-border" />
                  <span className="text-xs font-bold uppercase tracking-wider text-muted-foreground">
                    Rating Guide
                  </span>
                  <div className="h-px flex-1 bg-border" />
                </div>

                <div className="grid gap-4 md:grid-cols-2">
                  <div>
                    <label className="block text-xs font-semibold text-foreground mb-1">
                      Efficiency
                    </label>
                    <AutoResizingTextarea
                      value={addForm.data.efficiency}
                      onChange={(e) => addForm.setData('efficiency', e.target.value)}
                      rows={2}
                      className="w-full rounded-lg border border-input bg-background p-2.5 text-xs text-foreground focus:outline-none focus:ring-2 focus:ring-ring"
                      required
                    />
                  </div>

                  <div>
                    <label className="block text-xs font-semibold text-foreground mb-1">
                      Quality
                    </label>
                    <AutoResizingTextarea
                      value={addForm.data.quality}
                      onChange={(e) => addForm.setData('quality', e.target.value)}
                      rows={2}
                      className="w-full rounded-lg border border-input bg-background p-2.5 text-xs text-foreground focus:outline-none focus:ring-2 focus:ring-ring"
                      required
                    />
                  </div>

                  <div>
                    <label className="block text-xs font-semibold text-foreground mb-1">
                      Timeliness
                    </label>
                    <AutoResizingTextarea
                      value={addForm.data.timeliness}
                      onChange={(e) => addForm.setData('timeliness', e.target.value)}
                      rows={2}
                      className="w-full rounded-lg border border-input bg-background p-2.5 text-xs text-foreground focus:outline-none focus:ring-2 focus:ring-ring"
                      required
                    />
                  </div>

                  <div>
                    <label className="block text-xs font-semibold text-foreground mb-1">
                      MOVs
                    </label>
                    <AutoResizingTextarea
                      value={addForm.data.movs}
                      onChange={(e) => addForm.setData('movs', e.target.value)}
                      rows={2}
                      className="w-full rounded-lg border border-input bg-background p-2.5 text-xs text-foreground focus:outline-none focus:ring-2 focus:ring-ring"
                      required
                    />
                  </div>

                  <div className="md:col-span-2">
                    <label className="block text-xs font-semibold text-foreground mb-1">
                      Remarks
                    </label>
                    <AutoResizingTextarea
                      value={addForm.data.remarks}
                      onChange={(e) => addForm.setData('remarks', e.target.value)}
                      rows={2}
                      className="w-full rounded-lg border border-input bg-background p-2.5 text-xs text-foreground focus:outline-none focus:ring-2 focus:ring-ring"
                    />
                  </div>
                </div>

                <div className="flex justify-end gap-2 pt-4 border-t border-border">
                  <button
                    type="button"
                    onClick={() => setShowAddModal(false)}
                    className="rounded-lg px-4 py-2 text-xs font-medium text-muted-foreground hover:text-foreground hover:bg-muted transition cursor-pointer"
                  >
                    Cancel
                  </button>
                  <button
                    type="submit"
                    disabled={addForm.processing}
                    className="rounded-lg bg-emerald-600 hover:bg-emerald-700 px-5 py-2 text-xs font-semibold text-white shadow-xs transition cursor-pointer"
                  >
                    Save Target
                  </button>
                </div>
              </form>
            </div>
          </div>
        ) : null}

        {/* Modal: Copy Targets */}
        {showCopyModal ? (
          <div className="fixed inset-0 z-50 flex items-center justify-center bg-black/60 p-4 backdrop-blur-xs">
            <div className="w-full max-w-5xl max-h-[90vh] overflow-y-auto rounded-2xl bg-card p-6 shadow-2xl space-y-5 border border-border">
              <div className="flex items-center justify-between border-b border-border pb-4">
                <div>
                  <h3 className="text-base font-bold text-foreground">Copy Target</h3>
                  <p className="text-xs text-muted-foreground">
                    Copy target entries from staff annual targets or harmonized IPC targets into your list.
                  </p>
                </div>

                <div className="flex gap-1.5 bg-muted/60 p-1 rounded-lg border border-border">
                  <button
                    type="button"
                    onClick={() => {
                      setCopyTab('staff');
                      setCopyStaffPage(1);
                    }}
                    className={`px-3 py-1.5 text-xs font-bold rounded-md transition cursor-pointer ${
                      copyTab === 'staff'
                        ? 'bg-card text-foreground shadow-xs'
                        : 'text-muted-foreground hover:text-foreground'
                    }`}
                  >
                    Staff Target
                  </button>
                  <button
                    type="button"
                    onClick={() => {
                      setCopyTab('harmonized');
                      setCopyHarmonizedPage(1);
                    }}
                    className={`px-3 py-1.5 text-xs font-bold rounded-md transition cursor-pointer ${
                      copyTab === 'harmonized'
                        ? 'bg-card text-foreground shadow-xs'
                        : 'text-muted-foreground hover:text-foreground'
                    }`}
                  >
                    Harmonized Target
                  </button>
                </div>
              </div>

              {copyTab === 'staff' ? (
                <div className="space-y-4">
                  <div className="grid gap-3 sm:grid-cols-2 md:grid-cols-3 lg:grid-cols-6 items-end">
                    <div className="lg:col-span-2">
                      <label className="block text-xs font-semibold text-muted-foreground mb-1">
                        Staff Name
                      </label>
                      <select
                        value={copyStaffUserId}
                        onChange={(e) => {
                          setCopyStaffUserId(e.target.value);
                          setCopyStaffPage(1);
                        }}
                        className="h-9 w-full rounded-lg border border-input bg-background px-2.5 text-xs text-foreground outline-hidden focus:ring-2 focus:ring-ring cursor-pointer"
                      >
                        <option value="">Select Staff</option>
                        {copyData.staffUsers.map((u) => (
                          <option key={u.id} value={u.id}>
                            {u.name} {u.position ? `(${u.position})` : ''}
                          </option>
                        ))}
                      </select>
                    </div>

                    <div>
                      <label className="block text-xs font-semibold text-muted-foreground mb-1">
                        Year
                      </label>
                      <select
                        value={copyStaffYear}
                        onChange={(e) => {
                          setCopyStaffYear(e.target.value);
                          setCopyStaffPage(1);
                        }}
                        className="h-9 w-full rounded-lg border border-input bg-background px-2.5 text-xs text-foreground outline-hidden focus:ring-2 focus:ring-ring cursor-pointer"
                      >
                        {years.map((y) => (
                          <option key={y.value} value={y.value}>
                            {y.label}
                          </option>
                        ))}
                      </select>
                    </div>

                    <div>
                      <label className="block text-xs font-semibold text-muted-foreground mb-1">
                        Category
                      </label>
                      <select
                        value={copyStaffCategory}
                        onChange={(e) => {
                          setCopyStaffCategory(e.target.value);
                          setCopyStaffPage(1);
                        }}
                        className="h-9 w-full rounded-lg border border-input bg-background px-2.5 text-xs text-foreground outline-hidden focus:ring-2 focus:ring-ring cursor-pointer"
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
                      <label className="block text-xs font-semibold text-muted-foreground mb-1">
                        Semester
                      </label>
                      <select
                        value={copyStaffSemester}
                        onChange={(e) => {
                          setCopyStaffSemester(e.target.value);
                          setCopyStaffPage(1);
                        }}
                        className="h-9 w-full rounded-lg border border-input bg-background px-2.5 text-xs text-foreground outline-hidden focus:ring-2 focus:ring-ring cursor-pointer"
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
                      <label className="block text-xs font-semibold text-muted-foreground mb-1">
                        Existing Target
                      </label>
                      <select
                        value={copyStaffStatusFilter}
                        onChange={(e) => {
                          setCopyStaffStatusFilter(e.target.value);
                          setCopyStaffPage(1);
                        }}
                        className="h-9 w-full rounded-lg border border-input bg-background px-2.5 text-xs text-foreground outline-hidden focus:ring-2 focus:ring-ring cursor-pointer"
                      >
                        <option value="">All Status</option>
                        <option value="new">New Only</option>
                        <option value="existing">Already Existing Only</option>
                      </select>
                    </div>
                  </div>

                  {/* Staff Copy Table Listing */}
                  {!copyStaffUserId ? (
                    <div className="rounded-xl border border-dashed border-border p-12 text-center text-xs text-muted-foreground">
                      Please select a staff member to view their targets.
                    </div>
                  ) : copyData.copyTargets.data.length === 0 ? (
                    <div className="rounded-xl border border-dashed border-border p-12 text-center text-xs text-muted-foreground">
                      No targets found for the selected staff member and year.
                    </div>
                  ) : (
                    <div className="overflow-x-auto rounded-xl border border-border bg-card">
                      <table className="w-full text-left text-xs border-collapse">
                        <thead className="bg-muted/60 font-semibold uppercase text-muted-foreground border-b border-border">
                          <tr>
                            <th className="px-3 py-2 text-center w-36 border-r border-border">Action</th>
                            <th className="px-3 py-2 w-64 border-r border-border">Activity / Indicator</th>
                            <th className="px-3 py-2">Sub-Targets & Measures</th>
                          </tr>
                        </thead>
                        <tbody className="divide-y divide-border">
                          {copyData.copyTargets.data.map((group) => (
                            <tr key={group.indicatorId} className="hover:bg-muted/30 transition-colors">
                              <td className="px-3 py-3 align-top text-center whitespace-nowrap border-r border-border">
                                {group.isExisting ? (
                                  <div className="flex flex-col items-center gap-1.5">
                                    <span className="inline-flex items-center gap-1 rounded-full bg-amber-500/10 px-2 py-0.5 text-[10px] font-bold text-amber-600 dark:text-amber-400 border border-amber-500/20">
                                      Already Existing
                                    </span>
                                    <button
                                      type="button"
                                      onClick={() => handleCopySingleGroup(group.indicatorId)}
                                      className="rounded-lg bg-emerald-600 hover:bg-emerald-700 px-2.5 py-1 text-xs font-bold text-white shadow-xs transition cursor-pointer"
                                      title="Override and copy anyway"
                                    >
                                      Copy Anyway
                                    </button>
                                  </div>
                                ) : (
                                  <button
                                    type="button"
                                    onClick={() => handleCopySingleGroup(group.indicatorId)}
                                    className="rounded-lg bg-emerald-600 hover:bg-emerald-700 px-3 py-1 text-xs font-bold text-white shadow-xs transition cursor-pointer"
                                  >
                                    Copy
                                  </button>
                                )}
                              </td>

                              <td className="px-3 py-3 align-top font-medium text-foreground border-r border-border">
                                <FormattedText value={group.activity} />
                                <div className="mt-1 text-[11px] font-normal text-muted-foreground">
                                  Category: {categories.find((c) => c.value === String(group.kraCategory))?.label || group.kraCategory} | Year: {group.targetYear}
                                </div>
                              </td>

                              <td className="px-3 py-3 align-top space-y-2">
                                {group.subTargets.map((sub) => (
                                  <div key={sub.id} className="rounded-lg border border-border p-2 bg-muted/20 text-xs">
                                    <div className="font-semibold text-foreground">
                                      <FormattedText value={sub.description} />
                                    </div>
                                    <div className="mt-1 text-[11px] text-muted-foreground flex flex-wrap gap-3">
                                      <span>Sem: {formatSemesterLabel(String(sub.newSemester), semesters)}</span>
                                      <span>Eff: {sub.efficiency || '-'}</span>
                                      <span>Qual: {sub.quality || '-'}</span>
                                      <span>Time: {sub.timeliness || '-'}</span>
                                    </div>
                                  </div>
                                ))}
                              </td>
                            </tr>
                          ))}
                        </tbody>
                      </table>
                    </div>
                  )}
                </div>
              ) : (
                <div className="space-y-4">
                  <div className="grid gap-3 sm:grid-cols-2 md:grid-cols-3 lg:grid-cols-6 items-end">
                    <div className="lg:col-span-2">
                      <label className="block text-xs font-semibold text-muted-foreground mb-1">
                        Harmonized Position
                      </label>
                      <select
                        value={copyHarmonizedPositionId}
                        onChange={(e) => {
                          setCopyHarmonizedPositionId(e.target.value);
                          setCopyHarmonizedPage(1);
                        }}
                        className="h-9 w-full rounded-lg border border-input bg-background px-2.5 text-xs text-foreground outline-hidden focus:ring-2 focus:ring-ring cursor-pointer"
                      >
                        <option value="">Select Position</option>
                        {copyData.harmonizedPositions.map((p) => (
                          <option key={p.id} value={p.id}>
                            {p.name}
                          </option>
                        ))}
                      </select>
                    </div>

                    <div>
                      <label className="block text-xs font-semibold text-muted-foreground mb-1">
                        Year
                      </label>
                      <select
                        value={copyHarmonizedYear}
                        onChange={(e) => {
                          setCopyHarmonizedYear(e.target.value);
                          setCopyHarmonizedPage(1);
                        }}
                        className="h-9 w-full rounded-lg border border-input bg-background px-2.5 text-xs text-foreground outline-hidden focus:ring-2 focus:ring-ring cursor-pointer"
                      >
                        {years.map((y) => (
                          <option key={y.value} value={y.value}>
                            {y.label}
                          </option>
                        ))}
                      </select>
                    </div>

                    <div>
                      <label className="block text-xs font-semibold text-muted-foreground mb-1">
                        Category
                      </label>
                      <select
                        value={copyHarmonizedCategory}
                        onChange={(e) => {
                          setCopyHarmonizedCategory(e.target.value);
                          setCopyHarmonizedPage(1);
                        }}
                        className="h-9 w-full rounded-lg border border-input bg-background px-2.5 text-xs text-foreground outline-hidden focus:ring-2 focus:ring-ring cursor-pointer"
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
                      <label className="block text-xs font-semibold text-muted-foreground mb-1">
                        Semester
                      </label>
                      <select
                        value={copyHarmonizedSemester}
                        onChange={(e) => {
                          setCopyHarmonizedSemester(e.target.value);
                          setCopyHarmonizedPage(1);
                        }}
                        className="h-9 w-full rounded-lg border border-input bg-background px-2.5 text-xs text-foreground outline-hidden focus:ring-2 focus:ring-ring cursor-pointer"
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
                      <label className="block text-xs font-semibold text-muted-foreground mb-1">
                        Existing Target
                      </label>
                      <select
                        value={copyHarmonizedStatusFilter}
                        onChange={(e) => {
                          setCopyHarmonizedStatusFilter(e.target.value);
                          setCopyHarmonizedPage(1);
                        }}
                        className="h-9 w-full rounded-lg border border-input bg-background px-2.5 text-xs text-foreground outline-hidden focus:ring-2 focus:ring-ring cursor-pointer"
                      >
                        <option value="">All Status</option>
                        <option value="new">New Only</option>
                        <option value="existing">Already Existing Only</option>
                      </select>
                    </div>
                  </div>

                  {/* Harmonized Copy Table Listing */}
                  {!copyHarmonizedPositionId ? (
                    <div className="rounded-xl border border-dashed border-border p-12 text-center text-xs text-muted-foreground">
                      Please select a harmonized position to view targets.
                    </div>
                  ) : copyData.copyTargets.data.length === 0 ? (
                    <div className="rounded-xl border border-dashed border-border p-12 text-center text-xs text-muted-foreground">
                      No targets found for the selected harmonized position and year.
                    </div>
                  ) : (
                    <div className="overflow-x-auto rounded-xl border border-border bg-card">
                      <table className="w-full text-left text-xs border-collapse">
                        <thead className="bg-muted/60 font-semibold uppercase text-muted-foreground border-b border-border">
                          <tr>
                            <th className="px-3 py-2 text-center w-36 border-r border-border">Action</th>
                            <th className="px-3 py-2 w-64 border-r border-border">Activity / Indicator</th>
                            <th className="px-3 py-2">Sub-Targets & Measures</th>
                          </tr>
                        </thead>
                        <tbody className="divide-y divide-border">
                          {copyData.copyTargets.data.map((group) => (
                            <tr key={group.indicatorId} className="hover:bg-muted/30 transition-colors">
                              <td className="px-3 py-3 align-top text-center whitespace-nowrap border-r border-border">
                                {group.isExisting ? (
                                  <div className="flex flex-col items-center gap-1.5">
                                    <span className="inline-flex items-center gap-1 rounded-full bg-amber-500/10 px-2 py-0.5 text-[10px] font-bold text-amber-600 dark:text-amber-400 border border-amber-500/20">
                                      Already Existing
                                    </span>
                                    <button
                                      type="button"
                                      onClick={() => handleCopySingleGroup(group.indicatorId)}
                                      className="rounded-lg bg-emerald-600 hover:bg-emerald-700 px-2.5 py-1 text-xs font-bold text-white shadow-xs transition cursor-pointer"
                                      title="Override and copy anyway"
                                    >
                                      Copy Anyway
                                    </button>
                                  </div>
                                ) : (
                                  <button
                                    type="button"
                                    onClick={() => handleCopySingleGroup(group.indicatorId)}
                                    className="rounded-lg bg-emerald-600 hover:bg-emerald-700 px-3 py-1 text-xs font-bold text-white shadow-xs transition cursor-pointer"
                                  >
                                    Copy
                                  </button>
                                )}
                              </td>

                              <td className="px-3 py-3 align-top font-medium text-foreground border-r border-border">
                                <FormattedText value={group.activity} />
                                <div className="mt-1 text-[11px] font-normal text-muted-foreground">
                                  Category: {categories.find((c) => c.value === String(group.kraCategory))?.label || group.kraCategory} | Year: {group.targetYear}
                                </div>
                              </td>

                              <td className="px-3 py-3 align-top space-y-2">
                                {group.subTargets.map((sub) => (
                                  <div key={sub.id} className="rounded-lg border border-border p-2 bg-muted/20 text-xs">
                                    <div className="font-semibold text-foreground">
                                      <FormattedText value={sub.description} />
                                    </div>
                                    <div className="mt-1 text-[11px] text-muted-foreground flex flex-wrap gap-3">
                                      <span>Sem: {formatSemesterLabel(String(sub.newSemester), semesters)}</span>
                                      <span>Eff: {sub.efficiency || '-'}</span>
                                      <span>Qual: {sub.quality || '-'}</span>
                                      <span>Time: {sub.timeliness || '-'}</span>
                                    </div>
                                  </div>
                                ))}
                              </td>
                            </tr>
                          ))}
                        </tbody>
                      </table>
                    </div>
                  )}
                </div>
              )}

              <div className="flex justify-end gap-2 pt-4 border-t border-border">
                <button
                  type="button"
                  onClick={() => setShowCopyModal(false)}
                  className="rounded-lg px-4 py-2 text-xs font-medium text-muted-foreground hover:text-foreground hover:bg-muted transition cursor-pointer"
                >
                  Close
                </button>
              </div>
            </div>
          </div>
        ) : null}

        {/* Modal: Lock Targets */}
        {showLockModal ? (
          <div className="fixed inset-0 z-50 flex items-center justify-center bg-black/60 p-4 backdrop-blur-xs">
            <div className="w-full max-w-lg rounded-2xl bg-card p-6 shadow-2xl space-y-5 border border-border">
              <div className="space-y-1">
                <h3 className="text-base font-bold text-foreground">
                  Save and Lock Annual Target
                </h3>
                <p className="text-xs text-muted-foreground">
                  Are you sure you want to save and lock your annual target entries? Once locked, these targets will no longer be editable.
                </p>
              </div>

              <div className="rounded-xl border border-amber-500/30 bg-amber-500/10 p-4">
                <div className="flex items-start gap-3">
                  <div className="text-xs leading-relaxed text-amber-700 dark:text-amber-300">
                    <span className="font-bold">Important Notice: </span>
                    This will automatically create the 1st Semester and 2nd Semester Target in My Ratings link.
                  </div>
                </div>
              </div>

              <div className="flex justify-end gap-2 pt-2 border-t border-border">
                <button
                  type="button"
                  onClick={() => setShowLockModal(false)}
                  className="rounded-lg px-4 py-2 text-xs font-medium text-muted-foreground hover:text-foreground hover:bg-muted transition cursor-pointer"
                >
                  Cancel
                </button>
                <button
                  type="button"
                  onClick={handleLockTargets}
                  className="rounded-lg bg-emerald-600 hover:bg-emerald-700 text-white px-5 py-2 text-xs font-semibold shadow-xs transition cursor-pointer"
                >
                  Confirm and Lock
                </button>
              </div>
            </div>
          </div>
        ) : null}

        {/* Modal: Unlock Targets */}
        {showUnlockModal ? (
          <div className="fixed inset-0 z-50 flex items-center justify-center bg-black/60 p-4 backdrop-blur-xs">
            <div className="w-full max-w-lg rounded-2xl bg-card p-6 shadow-2xl space-y-5 border border-border">
              <div className="space-y-1">
                <h3 className="text-base font-bold text-foreground">
                  Unlock Annual Target
                </h3>
                <p className="text-xs text-muted-foreground">
                  Are you sure you want to unlock your annual target entries? Once unlocked, these targets can be edited and modified.
                </p>
              </div>

              <div className="flex justify-end gap-2 pt-2 border-t border-border">
                <button
                  type="button"
                  onClick={() => setShowUnlockModal(false)}
                  className="rounded-lg px-4 py-2 text-xs font-medium text-muted-foreground hover:text-foreground hover:bg-muted transition cursor-pointer"
                >
                  Cancel
                </button>
                <button
                  type="button"
                  onClick={handleUnlockTargets}
                  className="rounded-lg bg-emerald-600 hover:bg-emerald-700 text-white px-5 py-2 text-xs font-semibold shadow-xs transition cursor-pointer"
                >
                  Confirm and Unlock
                </button>
              </div>
            </div>
          </div>
        ) : null}

        {/* Modal: Move Target to Different Category */}
        {showMoveConfirmModal ? (
          <div className="fixed inset-0 z-50 flex items-center justify-center bg-black/60 p-4 backdrop-blur-xs">
            <div className="w-full max-w-lg rounded-2xl bg-card p-6 shadow-2xl space-y-5 border border-border">
              <div className="space-y-1">
                <h3 className="text-base font-bold text-foreground">
                  Move target to another KRA?
                </h3>
                <p className="text-xs text-muted-foreground">
                  This target will be moved to a different Key Result Area category. Confirm to save the new category and position.
                </p>
              </div>

              <div className="flex justify-end gap-2 pt-2 border-t border-border">
                <button
                  type="button"
                  onClick={() => {
                    setShowMoveConfirmModal(false);
                    setPendingMove(null);
                  }}
                  className="rounded-lg px-4 py-2 text-xs font-medium text-muted-foreground hover:text-foreground hover:bg-muted transition cursor-pointer"
                >
                  Cancel
                </button>
                <button
                  type="button"
                  onClick={confirmTargetMove}
                  className="rounded-lg bg-blue-600 hover:bg-blue-700 text-white px-5 py-2 text-xs font-semibold shadow-xs transition cursor-pointer"
                >
                  Confirm move
                </button>
              </div>
            </div>
          </div>
        ) : null}

        {/* Modal: Delete Main Target */}
        {deletingIndicatorId !== null ? (
          <div className="fixed inset-0 z-50 flex items-center justify-center bg-black/60 p-4 backdrop-blur-xs">
            <div className="w-full max-w-lg rounded-2xl bg-card p-6 shadow-2xl space-y-5 border border-border">
              <div className="space-y-1">
                <h3 className="text-base font-bold text-foreground">
                  Delete selected target and its sub-target
                </h3>
                <p className="text-xs text-muted-foreground">
                  Are you sure you want to delete this target and all of its sub-targets? This action cannot be undone.
                </p>
              </div>

              <div className="flex justify-end gap-2 pt-2 border-t border-border">
                <button
                  type="button"
                  onClick={() => setDeletingIndicatorId(null)}
                  className="rounded-lg px-4 py-2 text-xs font-medium text-muted-foreground hover:text-foreground hover:bg-muted transition cursor-pointer"
                >
                  Cancel
                </button>
                <button
                  type="button"
                  onClick={() => {
                    router.delete(`/ipcrf/annualtarget/${deletingIndicatorId}`, {
                      onSuccess: () => setDeletingIndicatorId(null),
                    });
                  }}
                  className="rounded-lg bg-rose-600 hover:bg-rose-700 px-5 py-2 text-xs font-semibold text-white shadow-xs transition cursor-pointer"
                >
                  Delete
                </button>
              </div>
            </div>
          </div>
        ) : null}

        {/* Modal: Delete Sub-target */}
        {deletingSubTargetId !== null ? (
          <div className="fixed inset-0 z-50 flex items-center justify-center bg-black/60 backdrop-blur-xs p-4 animate-in fade-in duration-150">
            <div className="w-full max-w-md rounded-xl border border-border bg-card p-5 shadow-2xl space-y-4">
              <div className="flex items-start justify-between">
                <div>
                  <h3 className="text-sm font-bold text-foreground">Delete Sub-target Indicator</h3>
                  <p className="text-xs text-muted-foreground mt-0.5">
                    Are you sure you want to remove this specific indicator entry?
                  </p>
                </div>
                <button
                  type="button"
                  onClick={() => setDeletingSubTargetId(null)}
                  className="text-muted-foreground hover:text-foreground p-1 rounded-md transition"
                >
                  <X className="size-4" />
                </button>
              </div>

              <div className="flex justify-end gap-2 pt-2 border-t border-border">
                <button
                  type="button"
                  onClick={() => setDeletingSubTargetId(null)}
                  className="px-3 py-1.5 rounded-lg border border-input bg-background text-xs font-semibold text-foreground hover:bg-muted transition"
                >
                  Cancel
                </button>
                <button
                  type="button"
                  onClick={() => {
                    router.delete(`/ipcrf/annualtarget-item/${deletingSubTargetId}`, {
                      onSuccess: () => setDeletingSubTargetId(null),
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
    </AppLayout>
  );
}

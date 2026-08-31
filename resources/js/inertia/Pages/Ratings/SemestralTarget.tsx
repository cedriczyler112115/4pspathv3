import React, { useState, useEffect, useMemo, Fragment } from 'react';
import { Head, Link, router, useForm } from '@inertiajs/react';
import {
  FileText,
  Trash2,
  CheckSquare,
  MessageSquare,
  Folder,
  ArrowLeft,
  RotateCcw,
  CheckCircle2,
  Lock,
  LockOpen,
  Clock,
  Printer,
  SlidersHorizontal,
  ChevronDown,
  ChevronRight,
  Plus,
  PlusCircle,
  Pencil,
  X,
  UploadCloud,
  File,
  ShieldCheck,
  AlertTriangle,
  GripVertical,
  ArrowUp,
  ArrowDown,
  Copy,
  Eye,
  Sliders,
  FilePlus,
  MinusCircle,
  Check,
} from 'lucide-react';
import AppLayout from '../../Layouts/AppLayout';
import FormattedText, { formatTextValue } from '../../Components/FormattedText';
import AutoResizingTextarea, { adjustTextareaHeight } from '../../Components/AutoResizingTextarea';

type IndicatorItem = {
  itemId: number;
  newSemester: number;
  description: string;
  efficiencyTarget: string;
  qualityTarget: string;
  timelinessTarget: string;
  movs: string;
  remarks: string;
  actualAccomplishment: string;
  actQuality: number | null;
  actEfficiency: number | null;
  actTimeliness: number | null;
  averageScore: number | null;
};

type IndicatorGroup = {
  indicatorId: number;
  kraCategory: number;
  activity: string;
  items: IndicatorItem[];
};

type AreaOfImprovement = {
  id: number;
  areas_improvement: string;
  development_activities: string;
  support_resources: string;
  progress_intervention: string;
  date_encoded?: string;
  encoded_by_name?: string;
};

type DeletedTarget = {
  id: number;
  sem_target_id: number;
  kra_category_label: string;
  activity: string;
  description: string;
  deleted_at: string;
  user_name: string;
  justification: string;
};

type CheckpointChange = {
  target_id: number;
  activity_title: string;
  justification: string;
  fields: Array<{
    field_label: string;
    old_value: string;
    new_value: string;
  }>;
};

type DocFile = {
  name: string;
  path: string;
  url: string;
  mime: string;
  size: number;
  type: string;
  modified_at: string;
};

type ContextMenuState = {
  x: number;
  y: number;
  indicatorId: number;
  itemId?: number;
  kraCategory: number;
  activity: string;
  isFirst: boolean;
  totalSubRows: number;
} | null;

type Props = {
  appName: string;
  user: { name: string; email: string } | null;
  rating: {
    id: number;
    year: string;
    semester: number;
    finalRating: string;
    adjectivalRating: string;
    lock: number;
    isReady: number;
    dateVerified: string | null;
    dateCreated: string | null;
    overallRemarks: string | null;
    recommendation?: string;
    strengths?: string;
  };
  userProfile: {
    fullName: string;
    position: string;
    designation: string;
    divisionName: string;
    sectionName: string;
  };
  functionScores?: {
    strategicScore: string;
    coreScore: string;
    supportScore: string;
    finalScore: string;
    adjectival: string;
  };
  includeStrategicFunction: boolean;
  indicators: IndicatorGroup[];
  areasOfImprovement: AreaOfImprovement[];
  deletedTargets?: DeletedTarget[];
  checkpointChanges?: CheckpointChange[];
  documentationFiles?: DocFile[];
  historyTargetIds?: number[];
  historyItemIds?: number[];
  navigation?: { sidebar?: any[] };
};

export default function SemestralTarget({
  appName,
  user,
  rating,
  userProfile,
  functionScores,
  includeStrategicFunction,
  indicators: initialIndicators,
  areasOfImprovement,
  deletedTargets = [],
  checkpointChanges = [],
  documentationFiles = [],
  historyTargetIds = [],
  historyItemIds = [],
  navigation,
}: Props) {
  const [activeTab, setActiveTab] = useState<'performance' | 'deleted' | 'checkpoint' | 'feedback' | 'documentation'>('performance');
  const [indicatorsList, setIndicatorsList] = useState<IndicatorGroup[]>(initialIndicators);

  useEffect(() => {
    setIndicatorsList(initialIndicators);
  }, [initialIndicators]);

  // Filters state for performance tab
  const [search, setSearch] = useState('');
  const [categoryFilter, setCategoryFilter] = useState('');
  const [perPage, setPerPage] = useState('10');
  const [currentPage, setCurrentPage] = useState(1);

  // Filtered and paginated targets calculation
  const allFilteredIndicators = useMemo(() => {
    return indicatorsList.filter((group) => {
      if (categoryFilter && String(group.kraCategory) !== String(categoryFilter)) {
        return false;
      }
      if (search) {
        const q = search.toLowerCase();
        const matchActivity = group.activity.toLowerCase().includes(q);
        const matchItems = group.items.some(
          (item) =>
            item.description.toLowerCase().includes(q) ||
            (item.efficiencyTarget || '').toLowerCase().includes(q) ||
            (item.qualityTarget || '').toLowerCase().includes(q) ||
            (item.timelinessTarget || '').toLowerCase().includes(q) ||
            (item.movs || '').toLowerCase().includes(q) ||
            (item.remarks || '').toLowerCase().includes(q)
        );
        return matchActivity || matchItems;
      }
      return true;
    });
  }, [indicatorsList, categoryFilter, search]);

  useEffect(() => {
    setCurrentPage(1);
  }, [search, categoryFilter, perPage]);

  const totalTargets = allFilteredIndicators.length;
  const isAllMode = perPage === 'all';
  const itemsPerPage = isAllMode ? Math.max(1, totalTargets) : Number(perPage);
  const totalPages = isAllMode ? 1 : Math.ceil(totalTargets / itemsPerPage) || 1;
  const pageToUse = Math.min(currentPage, totalPages);

  const paginatedIndicators = useMemo(() => {
    if (isAllMode) return allFilteredIndicators;
    const start = (pageToUse - 1) * itemsPerPage;
    return allFilteredIndicators.slice(start, start + itemsPerPage);
  }, [allFilteredIndicators, isAllMode, pageToUse, itemsPerPage]);

  const fromIndex = totalTargets === 0 ? 0 : (pageToUse - 1) * itemsPerPage + 1;
  const toIndex = isAllMode ? totalTargets : Math.min(pageToUse * itemsPerPage, totalTargets);

  // Search state for deleted targets
  const [deletedSearch, setDeletedSearch] = useState('');

  // Context Menu State & Sub-Menu Flyouts
  const [contextMenu, setContextMenu] = useState<ContextMenuState>(null);
  const [activeSubMenu, setActiveSubMenu] = useState<'add' | 'delete' | null>(null);

  // Dropdown states
  const [showOptionsDropdown, setShowOptionsDropdown] = useState(false);
  const [showPrintDropdown, setShowPrintDropdown] = useState(false);

  // Modals state
  const [editingItem, setEditingItem] = useState<IndicatorItem | null>(null);
  const [showAddModal, setShowAddModal] = useState(false);
  const [showAreaModal, setShowAreaModal] = useState(false);
  const [showMovStaffModal, setShowMovStaffModal] = useState(false);
  const [movTargetItemId, setMovTargetItemId] = useState<number | null>(null);
  const [deletingTargetId, setDeletingTargetId] = useState<number | null>(null);
  const [deleteJustification, setDeleteJustification] = useState('');
  const [deletingSubTargetId, setDeletingSubTargetId] = useState<number | null>(null);
  const [deleteSubJustification, setDeleteSubJustification] = useState('');
  const [checkpointItemId, setCheckpointItemId] = useState<number | null>(null);
  const [checkpointTargetId, setCheckpointTargetId] = useState<number | null>(null);
  const [previewFile, setPreviewFile] = useState<DocFile | null>(null);

  // Inline pending sub-target row state
  const [pendingSubTargetGroup, setPendingSubTargetGroup] = useState<{
    indicatorId: number;
    kraCategory: number;
    activity: string;
  } | null>(null);

  const [pendingForm, setPendingForm] = useState({
    description: '',
    efficiency: '',
    quality: '',
    timeliness: '',
    movs: '',
    remarks: '',
  });

  const handleSavePendingSubTarget = () => {
    if (!pendingSubTargetGroup) return;
    if (!pendingForm.description.trim()) {
      alert('Please enter a success indicator description for the sub-target.');
      return;
    }

    router.post(
      `/inertia/ipcrf/myratings/${rating.id}/target/${pendingSubTargetGroup.indicatorId}/subtarget`,
      {
        description: pendingForm.description,
        efficiency: pendingForm.efficiency,
        quality: pendingForm.quality,
        timeliness: pendingForm.timeliness,
        movs: pendingForm.movs,
        remarks: pendingForm.remarks,
      },
      {
        onSuccess: () => {
          setPendingSubTargetGroup(null);
          setPendingForm({
            description: '',
            efficiency: '',
            quality: '',
            timeliness: '',
            movs: '',
            remarks: '',
          });
        },
      }
    );
  };

  // Inline editing state for target group
  type EditingGroupItem = {
    itemId: number;
    description: string;
    efficiencyTarget: string;
    qualityTarget: string;
    timelinessTarget: string;
    movs: string;
    remarks: string;
  };

  type EditingGroupState = {
    indicatorId: number;
    activity: string;
    kraCategory: number;
    items: EditingGroupItem[];
  } | null;

  const [editingGroup, setEditingGroup] = useState<EditingGroupState>(null);

  // Edit History state
  type HistoryRecord = {
    id?: number;
    sem_target_id?: number;
    sem_item_id?: number;
    field_name?: string;
    field_label?: string;
    original_value?: string;
    old_value?: string;
    new_value?: string;
    justification?: string;
    user_name?: string;
    date_created?: string;
    is_separator?: boolean;
    separator_title?: string;
    justification_rowspan?: number;
  };

  const [showHistoryModal, setShowHistoryModal] = useState(false);
  const [historyRecords, setHistoryRecords] = useState<HistoryRecord[]>([]);
  const [historyTargetId, setHistoryTargetId] = useState<number | null>(null);
  const [historyItemId, setHistoryItemId] = useState<number | null>(null);
  const [isLoadingHistory, setIsLoadingHistory] = useState(false);

  const handleOpenEditHistory = (targetId: number, itemId?: number) => {
    setHistoryTargetId(targetId);
    setHistoryItemId(itemId || null);
    setIsLoadingHistory(true);
    setShowHistoryModal(true);

    const url = `/inertia/ipcrf/myratings/${rating.id}/target/${targetId}/history` + (itemId ? `?itemId=${itemId}` : '');
    fetch(url, {
      credentials: 'same-origin',
      headers: {
        'Accept': 'application/json',
        'X-Requested-With': 'XMLHttpRequest',
      },
    })
      .then((res) => {
        if (!res.ok) {
          console.error('Edit history fetch failed:', res.status, res.statusText);
          return { records: [] };
        }
        return res.json();
      })
      .then((data) => {
        setHistoryRecords(data.records || []);
        setIsLoadingHistory(false);
      })
      .catch((err) => {
        console.error('Failed to load edit history:', err);
        setHistoryRecords([]);
        setIsLoadingHistory(false);
      });
  };

  const handleDiscardHistory = () => {
    if (!historyTargetId) return;
    if (!confirm('Are you sure you want to discard all edit history records for this target?')) return;

    const url = `/inertia/ipcrf/myratings/${rating.id}/target/${historyTargetId}/history` + (historyItemId ? `?itemId=${historyItemId}` : '');
    router.delete(url, {
      onSuccess: () => {
        setShowHistoryModal(false);
        setHistoryRecords([]);
      },
    });
  };

  const handleStartEditGroup = (group: IndicatorGroup) => {
    setEditingGroup({
      indicatorId: group.indicatorId,
      activity: formatTextValue(group.activity, ''),
      kraCategory: group.kraCategory,
      items: group.items.map((item) => ({
        itemId: item.itemId,
        description: formatTextValue(item.description, ''),
        efficiencyTarget: formatTextValue(item.efficiencyTarget, ''),
        qualityTarget: formatTextValue(item.qualityTarget, ''),
        timelinessTarget: formatTextValue(item.timelinessTarget, ''),
        movs: formatTextValue(item.movs, ''),
        remarks: formatTextValue(item.remarks, ''),
      })),
    });
  };

  const handleSaveEditGroup = () => {
    if (!editingGroup) return;

    router.put(
      `/inertia/ipcrf/myratings/${rating.id}/target/${editingGroup.indicatorId}`,
      {
        activity: editingGroup.activity,
        kraCategory: editingGroup.kraCategory,
        items: editingGroup.items,
      },
      {
        onSuccess: () => {
          setEditingGroup(null);
        },
      }
    );
  };

  // Drag and drop state
  const [draggedGroupIndex, setDraggedGroupIndex] = useState<number | null>(null);

  // Form hooks
  const editForm = useForm({
    actualAccomplishment: '',
    actQuality: '',
    actEfficiency: '',
    actTimeliness: '',
    remarks: '',
  });

  const addForm = useForm({
    category: 2,
    activity: '',
    description: '',
    efficiency: '',
    quality: '',
    timeliness: '',
    movs: '',
    remarks: '',
  });

  const areaForm = useForm({
    areas_improvement: '',
    development_activities: '',
    support_resources: '',
    progress_intervention: '',
  });

  const isVerified = Boolean(rating.dateVerified);
  const isLocked = rating.lock === 2 || rating.isReady === 1;

  useEffect(() => {
    const handleWindowClick = () => {
      setContextMenu(null);
      setActiveSubMenu(null);
    };
    window.addEventListener('click', handleWindowClick);
    return () => window.removeEventListener('click', handleWindowClick);
  }, []);

  const handleContextMenu = (
    e: React.MouseEvent,
    indicatorId: number,
    kraCategory: number,
    activity: string,
    totalSubRows: number,
    itemId?: number,
    isFirst: boolean = true
  ) => {
    e.preventDefault();
    if (isLocked) return;
    const pageX = window.scrollX + e.clientX;
    const pageY = window.scrollY + e.clientY;
    setContextMenu({
      x: Math.max(8, pageX),
      y: Math.max(8, pageY),
      indicatorId,
      kraCategory,
      activity,
      itemId,
      isFirst,
      totalSubRows,
    });
    setActiveSubMenu(null);
  };

  const handleDragStart = (index: number) => {
    setDraggedGroupIndex(index);
  };

  const handleDragOver = (e: React.DragEvent) => {
    e.preventDefault();
  };

  const handleDrop = (dropIndex: number) => {
    if (draggedGroupIndex === null || draggedGroupIndex === dropIndex) return;

    const updated = [...indicatorsList];
    const [moved] = updated.splice(draggedGroupIndex, 1);
    updated.splice(dropIndex, 0, moved);

    setIndicatorsList(updated);
    setDraggedGroupIndex(null);

    const orderPayload = updated.map((item, idx) => ({
      indicatorId: item.indicatorId,
      displayOrder: idx + 1,
    }));

    router.post(
      `/inertia/ipcrf/myratings/${rating.id}/target/reorder`,
      { order: orderPayload },
      { preserveScroll: true }
    );
  };

  const handleMoveUp = (indicatorId: number) => {
    const idx = indicatorsList.findIndex((g) => g.indicatorId === indicatorId);
    if (idx <= 0) return;
    handleDrop(idx - 1);
  };

  const handleMoveDown = (indicatorId: number) => {
    const idx = indicatorsList.findIndex((g) => g.indicatorId === indicatorId);
    if (idx === -1 || idx >= indicatorsList.length - 1) return;
    handleDrop(idx + 1);
  };

  const handleEditOpen = (item: IndicatorItem) => {
    setEditingItem(item);
    editForm.setData({
      actualAccomplishment: item.actualAccomplishment || '',
      actQuality: item.actQuality ? String(item.actQuality) : '',
      actEfficiency: item.actEfficiency ? String(item.actEfficiency) : '',
      actTimeliness: item.actTimeliness ? String(item.actTimeliness) : '',
      remarks: item.remarks || '',
    });
  };

  const handleEditSubmit = (e: React.FormEvent) => {
    e.preventDefault();
    if (!editingItem) return;
    editForm.patch(`/inertia/ipcrf/myratings/${rating.id}/accomplishment/${editingItem.itemId}`, {
      onSuccess: () => setEditingItem(null),
    });
  };

  const handleAddSubmit = (e: React.FormEvent) => {
    e.preventDefault();
    addForm.post(`/inertia/ipcrf/myratings/${rating.id}/target`, {
      onSuccess: () => {
        setShowAddModal(false);
        addForm.reset();
      },
    });
  };

  const handleAreaSubmit = (e: React.FormEvent) => {
    e.preventDefault();
    areaForm.post(`/inertia/ipcrf/myratings/${rating.id}/areas-improvement`, {
      onSuccess: () => {
        setShowAreaModal(false);
        areaForm.reset();
      },
    });
  };

  const handleFileUpload = (e: React.ChangeEvent<HTMLInputElement>) => {
    if (!e.target.files || e.target.files.length === 0) return;
    const form = new FormData();
    Array.from(e.target.files).forEach((file) => {
      form.append('files[]', file);
    });
    router.post(`/inertia/ipcrf/myratings/${rating.id}/documentation`, form, {
      preserveScroll: true,
    });
  };

  const handleDocDelete = (fileName: string) => {
    if (confirm('Delete this documentation file?')) {
      router.delete(`/inertia/ipcrf/myratings/${rating.id}/documentation`, {
        data: { fileName },
        preserveScroll: true,
        onSuccess: () => setPreviewFile(null),
      });
    }
  };

  const handleCopyStaffMov = (sourceItemId: number) => {
    if (!movTargetItemId) return;
    router.post(
      `/inertia/ipcrf/myratings/${rating.id}/copy-movs`,
      { sourceItemId, targetItemId: movTargetItemId },
      {
        preserveScroll: true,
        onSuccess: () => setShowMovStaffModal(false),
      }
    );
  };

  const getKraLabel = (cat: number) => {
    switch (cat) {
      case 1:
        return 'Strategic Function';
      case 2:
        return 'Core Function';
      case 3:
        return 'Support Function';
      default:
        return 'Core Function';
    }
  };

  // Filtered indicators
  const filteredIndicators = indicatorsList.filter((ind) => {
    if (categoryFilter && String(ind.kraCategory) !== categoryFilter) return false;
    if (search) {
      const q = search.toLowerCase();
      const matchActivity = ind.activity.toLowerCase().includes(q);
      const matchItems = ind.items.some(
        (it) => it.description.toLowerCase().includes(q) || it.movs.toLowerCase().includes(q) || it.actualAccomplishment.toLowerCase().includes(q)
      );
      if (!matchActivity && !matchItems) return false;
    }
    return true;
  });

  // Filtered deleted targets
  const filteredDeletedTargets = deletedTargets.filter((t) => {
    if (!deletedSearch) return true;
    const q = deletedSearch.toLowerCase();
    return t.activity.toLowerCase().includes(q) || t.description.toLowerCase().includes(q) || t.justification.toLowerCase().includes(q);
  });

  const categoriesList = includeStrategicFunction ? [1, 2, 3] : [2, 3];

  return (
    <AppLayout appName={appName} user={user} sidebar={navigation?.sidebar ?? []}>
      <Head title={`Semestral Target - ${rating.year}`} />

      <section className="w-full space-y-6">
        {/* Top Title & Header */}
        <div className="flex flex-col gap-3 lg:flex-row lg:items-start lg:justify-between">
          <div className="space-y-1">
            <div className="flex items-center gap-2">
              <span className="text-xs font-bold uppercase tracking-widest text-emerald-700 dark:text-emerald-400">
                IPCRF Rating Workspace
              </span>
              <span className="rounded-full bg-slate-100 dark:bg-slate-800 px-2.5 py-0.5 text-xs font-semibold text-slate-700 dark:text-slate-300">
                {rating.year} • {rating.semester === 1 ? '1st Semester' : '2nd Semester'}
              </span>
            </div>
            <h1 className="text-xl font-bold tracking-tight text-slate-900 dark:text-slate-100">
              Individual Performance Commitment and Review
            </h1>
          </div>

          <div>
            <Link
              href="/inertia/ipcrf/myratings"
              className="inline-flex items-center gap-1.5 rounded-xl border border-slate-300 dark:border-slate-700 bg-white dark:bg-slate-800 px-4 py-2 text-xs font-semibold text-slate-700 dark:text-slate-200 hover:bg-slate-50 transition"
            >
              <ArrowLeft className="w-4 h-4" />
              <span>Back to My Ratings</span>
            </Link>
          </div>
        </div>

        {/* Function Scores Bar */}
        <div className="flex flex-wrap items-center justify-between gap-3 rounded-2xl border border-slate-200 dark:border-slate-800 bg-white dark:bg-slate-900 p-4 shadow-sm text-xs">
          <div className="flex flex-wrap items-center gap-2">
            <div className="flex items-center gap-2 rounded-xl border border-slate-200 dark:border-slate-800 bg-slate-50 dark:bg-slate-800/50 px-3 py-1.5">
              {includeStrategicFunction && (
                <>
                  <span className="text-slate-500 font-medium">Strategic Function Score:</span>
                  <span className="font-bold text-slate-900 dark:text-slate-100">{functionScores?.strategicScore || '0.00000'}</span>
                  <span className="text-slate-300 mx-1">•</span>
                </>
              )}
              <span className="text-slate-500 font-medium">Core Function Score:</span>
              <span className="font-bold text-slate-900 dark:text-slate-100">{functionScores?.coreScore || '0.00000'}</span>
              <span className="text-slate-300 mx-1">•</span>
              <span className="text-slate-500 font-medium">Support Function Score:</span>
              <span className="font-bold text-slate-900 dark:text-slate-100">{functionScores?.supportScore || '0.00000'}</span>
            </div>

            <div className="flex items-center gap-2 rounded-xl border border-slate-200 dark:border-slate-800 bg-slate-50 dark:bg-slate-800/50 px-3 py-1.5">
              <span className="text-slate-500 font-medium">Final Rating:</span>
              <span className="font-bold text-slate-900 dark:text-slate-100">{functionScores?.finalScore || rating.finalRating}</span>
              <span className="text-slate-300 mx-1">•</span>
              <span className="text-slate-500 font-medium">Adjectival:</span>
              <span className="font-bold text-emerald-600 dark:text-emerald-400">{functionScores?.adjectival || rating.adjectivalRating}</span>
            </div>
          </div>

          <div className="flex flex-wrap items-center gap-2">
            {/* Status Action Buttons */}
            {isVerified ? (
              <span className="inline-flex items-center gap-1.5 rounded-xl bg-emerald-600 px-3.5 py-2 text-xs font-semibold text-white">
                <CheckCircle2 className="w-4 h-4" />
                <span>Verified by Supervisor</span>
              </span>
            ) : rating.lock === 2 ? (
              <button
                type="button"
                className="inline-flex items-center gap-1.5 rounded-xl bg-amber-600 hover:bg-amber-700 px-3.5 py-2 text-xs font-semibold text-white transition"
              >
                <Clock className="w-4 h-4" />
                <span>Waiting for Verification</span>
              </button>
            ) : isLocked ? (
              <button
                type="button"
                onClick={() => router.post(`/inertia/ipcrf/myratings/${rating.id}/toggle-status`, { action: 'unlock' })}
                className="inline-flex items-center gap-1.5 rounded-xl border border-amber-300 bg-amber-50 dark:bg-amber-950 px-3.5 py-2 text-xs font-semibold text-amber-800 dark:text-amber-200 transition"
              >
                <LockOpen className="w-4 h-4" />
                <span>Ready / Locked (Unlock)</span>
              </button>
            ) : (
              <button
                type="button"
                onClick={() => router.post(`/inertia/ipcrf/myratings/${rating.id}/toggle-status`, { action: 'ready' })}
                className="inline-flex items-center gap-1.5 rounded-xl bg-slate-900 dark:bg-slate-100 hover:bg-slate-800 text-white dark:text-slate-900 px-3.5 py-2 text-xs font-semibold shadow-sm transition"
              >
                <Lock className="w-4 h-4" />
                <span>Save and Lock Semestral Target</span>
              </button>
            )}

            {/* Options Dropdown */}
            <div className="relative">
              <button
                type="button"
                onClick={() => setShowOptionsDropdown(!showOptionsDropdown)}
                className="inline-flex items-center gap-1.5 rounded-xl bg-violet-600 hover:bg-violet-700 px-3.5 py-2 text-xs font-semibold text-white transition"
              >
                <SlidersHorizontal className="w-4 h-4" />
                <span>Options</span>
                <ChevronDown className="w-3.5 h-3.5" />
              </button>

              {showOptionsDropdown && (
                <div className="absolute right-0 mt-2 w-56 rounded-xl border border-slate-200 dark:border-slate-800 bg-white dark:bg-slate-900 p-1 shadow-xl z-30 text-xs">
                  {!isLocked && (
                    <button
                      type="button"
                      onClick={() => {
                        setShowOptionsDropdown(false);
                        setActiveTab('deleted');
                      }}
                      className="w-full text-left px-3 py-2 rounded-lg hover:bg-slate-100 dark:hover:bg-slate-800 text-slate-700 dark:text-slate-200"
                    >
                      Recover Deleted Targets
                    </button>
                  )}
                  {isLocked && rating.lock !== 2 && (
                    <button
                      type="button"
                      onClick={() => {
                        setShowOptionsDropdown(false);
                        router.post(`/inertia/ipcrf/myratings/${rating.id}/toggle-status`, { action: 'unlock' });
                      }}
                      className="w-full text-left px-3 py-2 rounded-lg hover:bg-slate-100 dark:hover:bg-slate-800 text-slate-700 dark:text-slate-200"
                    >
                      Unlock Semestral Target
                    </button>
                  )}
                </div>
              )}
            </div>

            {/* Print Dropdown */}
            <div className="relative">
              <button
                type="button"
                onClick={() => setShowPrintDropdown(!showPrintDropdown)}
                className="inline-flex items-center gap-1.5 rounded-xl bg-emerald-600 hover:bg-emerald-700 px-3.5 py-2 text-xs font-semibold text-white transition"
              >
                <Printer className="w-4 h-4" />
                <span>Print</span>
                <ChevronDown className="w-3.5 h-3.5" />
              </button>

              {showPrintDropdown && (
                <div className="absolute right-0 mt-2 w-48 rounded-xl border border-slate-200 dark:border-slate-800 bg-white dark:bg-slate-900 p-1 shadow-xl z-30 text-xs">
                  <a
                    href={`/ipcrf/myratings/semestral-target/print-ipcrf?sem_id=${rating.id}`}
                    target="_blank"
                    rel="noreferrer"
                    className="block px-3 py-2 rounded-lg hover:bg-slate-100 dark:hover:bg-slate-800 text-slate-700 dark:text-slate-200"
                  >
                    Print IPCR-F
                  </a>
                  <a
                    href={`/ipcrf/myratings/semestral-target/print-checkpoint?sem_id=${rating.id}`}
                    target="_blank"
                    rel="noreferrer"
                    className="block px-3 py-2 rounded-lg hover:bg-slate-100 dark:hover:bg-slate-800 text-slate-700 dark:text-slate-200"
                  >
                    Print Checkpoint
                  </a>
                </div>
              )}
            </div>
          </div>
        </div>

        {/* User Profile Card */}
        <div className="rounded-2xl border border-slate-200 dark:border-slate-800 bg-white dark:bg-slate-900 p-4 shadow-sm">
          <div className="overflow-x-auto">
            <table className="w-full border-0 border-collapse">
              <tbody>
                <tr className="align-top text-xs">
                  <td className="pr-8 whitespace-nowrap">
                    <div className="text-[11px] text-slate-500">Full Name</div>
                    <div className="mt-1 font-semibold uppercase text-slate-900 dark:text-slate-100">{userProfile.fullName || '-'}</div>
                  </td>
                  <td className="pr-8 whitespace-nowrap">
                    <div className="text-[11px] text-slate-500">Position</div>
                    <div className="mt-1 font-semibold uppercase text-slate-900 dark:text-slate-100">{userProfile.position || '-'}</div>
                  </td>
                  <td className="pr-8 whitespace-nowrap">
                    <div className="text-[11px] text-slate-500">Designation</div>
                    <div className="mt-1 font-semibold uppercase text-slate-900 dark:text-slate-100">{userProfile.designation || '-'}</div>
                  </td>
                  <td className="pr-8 whitespace-nowrap">
                    <div className="text-[11px] text-slate-500">Division Name</div>
                    <div className="mt-1 font-semibold uppercase text-slate-900 dark:text-slate-100">{userProfile.divisionName || '-'}</div>
                  </td>
                  <td className="whitespace-nowrap">
                    <div className="text-[11px] text-slate-500">Section Name</div>
                    <div className="mt-1 font-semibold uppercase text-slate-900 dark:text-slate-100">{userProfile.sectionName || '-'}</div>
                  </td>
                </tr>
              </tbody>
            </table>
          </div>
        </div>

        {/* Unified Tabbed Navigation Container */}
        <div className="rounded-2xl border border-slate-200 dark:border-slate-800 bg-white dark:bg-slate-900 shadow-sm overflow-hidden">
          {/* Tab Header */}
          <div className="bg-slate-50 dark:bg-slate-800/80 px-6 pt-4 pb-0 border-b border-slate-200 dark:border-slate-800">
            <nav className="flex flex-wrap items-center gap-2" aria-label="Tabs">
              {[
                { id: 'performance', label: 'Performance Indicator', icon: FileText },
                { id: 'deleted', label: 'Deleted Target', icon: Trash2 },
                { id: 'checkpoint', label: 'Checkpoint Changes', icon: CheckSquare },
                { id: 'feedback', label: "Supervisor's Feedback", icon: MessageSquare },
                { id: 'documentation', label: 'Documentation', icon: Folder },
              ].map((tab) => {
                const Icon = tab.icon;
                const isActive = activeTab === tab.id;
                return (
                  <button
                    key={tab.id}
                    type="button"
                    onClick={() => setActiveTab(tab.id as any)}
                    className={`flex items-center gap-2 rounded-t-xl px-5 py-3 text-xs font-bold transition-all border-t border-x ${isActive
                        ? 'bg-emerald-600 text-white border-emerald-600 shadow-sm -mb-[1px] z-10'
                        : 'bg-slate-100 dark:bg-slate-800 text-slate-600 dark:text-slate-300 border-slate-300 dark:border-slate-700 hover:bg-slate-200 dark:hover:bg-slate-700'
                      }`}
                  >
                    <Icon className="w-4 h-4" />
                    <span>{tab.label}</span>
                  </button>
                );
              })}
            </nav>
          </div>

          {/* Tab Content Body */}
          <div className="p-6 min-h-[350px]">
            {/* Tab 1: Performance Indicator */}
            {activeTab === 'performance' && (
              <div className="space-y-6">
                {/* Search and Filters Bar */}
                <div className="flex flex-col gap-4 sm:flex-row sm:items-end sm:justify-between border-b border-slate-200 dark:border-slate-800 pb-4">
                  <div className="flex flex-wrap items-end gap-3 flex-1">
                    <div className="flex-1 min-w-[180px]">
                      <label className="block text-xs font-semibold text-slate-600 dark:text-slate-400 mb-1">Search</label>
                      <input
                        type="text"
                        value={search}
                        onChange={(e) => setSearch(e.target.value)}
                        placeholder="Search semestral targets..."
                        className="w-full h-9 rounded-xl border border-slate-300 dark:border-slate-700 bg-white dark:bg-slate-800 px-3 text-xs text-slate-900 dark:text-slate-100 focus:outline-none focus:ring-2 focus:ring-emerald-500/30"
                      />
                    </div>

                    <div className="w-40">
                      <label className="block text-xs font-semibold text-slate-600 dark:text-slate-400 mb-1">Category</label>
                      <select
                        value={categoryFilter}
                        onChange={(e) => setCategoryFilter(e.target.value)}
                        className="w-full h-9 rounded-xl border border-slate-300 dark:border-slate-700 bg-white dark:bg-slate-800 px-3 text-xs text-slate-900 dark:text-slate-100 focus:outline-none focus:ring-2 focus:ring-emerald-500/30"
                      >
                        <option value="">All categories</option>
                        {includeStrategicFunction && <option value="1">Strategic Function</option>}
                        <option value="2">Core Function</option>
                        <option value="3">Support Function</option>
                      </select>
                    </div>

                    <div className="w-40">
                      <label className="block text-xs font-semibold text-slate-600 dark:text-slate-400 mb-1">Records Per Page</label>
                      <select
                        value={perPage}
                        onChange={(e) => setPerPage(e.target.value)}
                        className="w-full h-9 rounded-xl border border-slate-300 dark:border-slate-700 bg-white dark:bg-slate-800 px-3 text-xs text-slate-900 dark:text-slate-100 focus:outline-none focus:ring-2 focus:ring-emerald-500/30"
                      >
                        <option value="10">10</option>
                        <option value="25">25</option>
                        <option value="50">50</option>
                        <option value="all">All</option>
                      </select>
                    </div>

                    <button
                      type="button"
                      onClick={() => {
                        setSearch('');
                        setCategoryFilter('');
                        setPerPage('10');
                        setCurrentPage(1);
                      }}
                      className="h-9 px-4 rounded-xl bg-slate-600 hover:bg-slate-700 text-white text-xs font-semibold flex items-center gap-1.5 transition"
                    >
                      <RotateCcw className="w-3.5 h-3.5" />
                      <span>Reset</span>
                    </button>
                  </div>
                </div>

                {/* EXACT LIVEWIRE TARGETS TABLE FORMAT */}
                {paginatedIndicators.length === 0 ? (
                  <div className="rounded-2xl border border-dashed border-slate-200 dark:border-slate-800 bg-slate-50 dark:bg-slate-800/30 p-12 text-center text-sm text-slate-500">
                    No semestral targets found matching your filter criteria.
                  </div>
                ) : (
                  <div className="space-y-4">
                    <div className="overflow-hidden rounded-xl border border-slate-200 dark:border-slate-800 bg-white dark:bg-slate-900 shadow-sm">
                      <table className="w-full text-left text-xs border-collapse">
                        {isLocked ? (
                          <colgroup>
                            <col style={{ width: '4%' }} />
                            <col style={{ width: '14%' }} />
                            <col style={{ width: '14%' }} />
                            <col style={{ width: '14%' }} />
                            <col style={{ width: '10%' }} />
                            <col style={{ width: '10%' }} />
                            <col style={{ width: '10%' }} />
                            <col style={{ width: '5%' }} />
                            <col style={{ width: '10%' }} />
                            <col style={{ width: '9%' }} />
                          </colgroup>
                        ) : (
                          <colgroup>
                            <col style={{ width: '5%' }} />
                            <col style={{ width: '17%' }} />
                            <col style={{ width: '17%' }} />
                            <col style={{ width: '16%' }} />
                            <col style={{ width: '16%' }} />
                            <col style={{ width: '16%' }} />
                            <col style={{ width: '16%' }} />
                            <col style={{ width: '13%' }} />
                          </colgroup>
                        )}

                        <thead className="bg-slate-100 dark:bg-slate-800/80 uppercase font-semibold text-slate-600 dark:text-slate-300 border-b border-slate-200 dark:border-slate-800">
                          <tr>
                            <th className="px-3 py-3 text-center border-r border-slate-200 dark:border-slate-800 whitespace-nowrap">Action</th>
                            <th className="px-3 py-3 border-r border-slate-200 dark:border-slate-800 whitespace-nowrap">Key Result Area</th>
                            <th className="px-3 py-3 border-r border-slate-200 dark:border-slate-800 whitespace-nowrap">Success Indicator</th>
                            {isLocked && <th className="px-3 py-3 border-r border-slate-200 dark:border-slate-800 whitespace-nowrap">Actual Accomplishment</th>}
                            <th className="px-3 py-3 border-r border-slate-200 dark:border-slate-800 whitespace-nowrap">EFFICIENCY</th>
                            <th className="px-3 py-3 border-r border-slate-200 dark:border-slate-800 whitespace-nowrap">QUALITY</th>
                            <th className="px-3 py-3 border-r border-slate-200 dark:border-slate-800 whitespace-nowrap">TIMELINESS</th>
                            {isLocked && <th className="px-3 py-3 text-center border-r border-slate-200 dark:border-slate-800 whitespace-nowrap">AVE</th>}
                            <th className="px-3 py-3 border-r border-slate-200 dark:border-slate-800 whitespace-nowrap">MOVS</th>
                            <th className="px-3 py-3 whitespace-nowrap">REMARKS</th>
                          </tr>
                        </thead>

                        {categoriesList.map((catId) => {
                          const catGroups = paginatedIndicators.filter((g) => g.kraCategory === catId);
                          if (catGroups.length === 0) return null;

                          return (
                            <tbody key={catId} className="divide-y divide-slate-200 dark:divide-slate-800">
                              <tr className="bg-slate-50 dark:bg-slate-800/40 border-b border-slate-200 dark:border-slate-800 font-bold">
                                <td colSpan={isLocked ? 10 : 8} className="px-3 py-2">
                                  <span className="text-xs uppercase tracking-wide text-emerald-800 dark:text-emerald-400">
                                    {getKraLabel(catId)}
                                  </span>
                                </td>
                              </tr>

                              {catGroups.map((group, groupIdx) => {
                                const isEditingThisGroup = editingGroup?.indicatorId === group.indicatorId;
                                const isPendingThisGroup = pendingSubTargetGroup?.indicatorId === group.indicatorId;
                                const totalSubRows = group.items.length + (isPendingThisGroup ? 1 : 0);
                                return (
                                  <Fragment key={group.indicatorId}>
                                    {group.items.map((item, itemIdx) => {
                                      const isFirstRow = itemIdx === 0;
                                      const itemEditIdx = isEditingThisGroup
                                        ? editingGroup.items.findIndex((i) => i.itemId === item.itemId)
                                        : -1;
                                      return (
                                        <tr
                                          key={item.itemId}
                                          onDragOver={handleDragOver}
                                          onDrop={() => isFirstRow && !isEditingThisGroup && handleDrop(groupIdx)}
                                          onContextMenu={(e) =>
                                            handleContextMenu(
                                              e,
                                              group.indicatorId,
                                              group.kraCategory,
                                              group.activity,
                                              totalSubRows,
                                              item.itemId,
                                              isFirstRow
                                            )
                                          }
                                          className={`hover:bg-slate-50/60 dark:hover:bg-slate-800/30 transition align-top border-b border-slate-200 dark:border-slate-800 ${isEditingThisGroup
                                              ? 'bg-sky-50/80 dark:bg-sky-950/50'
                                              : contextMenu && contextMenu.itemId === item.itemId
                                                ? 'bg-sky-50 dark:bg-sky-950/60'
                                                : ''
                                            }`}
                                        >
                                          {isFirstRow && (
                                            <td
                                              rowSpan={totalSubRows}
                                              className="px-2 py-3 text-center border-r border-slate-200 dark:border-slate-800 align-top"
                                            >
                                              <div className="flex flex-col items-center gap-2">
                                                {isEditingThisGroup ? (
                                                  <div className="flex flex-col items-center gap-1.5">
                                                    <button
                                                      type="button"
                                                      onClick={handleSaveEditGroup}
                                                      className="w-8 h-8 rounded-lg bg-emerald-600 hover:bg-emerald-700 text-white flex items-center justify-center shadow-md transition"
                                                      title="Save Changes"
                                                    >
                                                      <Check className="w-4 h-4 stroke-[3]" />
                                                    </button>
                                                    <button
                                                      type="button"
                                                      onClick={() => setEditingGroup(null)}
                                                      className="w-8 h-8 rounded-lg bg-amber-500 hover:bg-amber-600 text-white flex items-center justify-center shadow-md transition"
                                                      title="Cancel"
                                                    >
                                                      <X className="w-4 h-4 stroke-[3]" />
                                                    </button>
                                                  </div>
                                                ) : isPendingThisGroup ? (
                                                  <div className="flex flex-col items-center gap-1.5">
                                                    <button
                                                      type="button"
                                                      onClick={handleSavePendingSubTarget}
                                                      className="w-8 h-8 rounded-lg bg-emerald-600 hover:bg-emerald-700 text-white flex items-center justify-center shadow-md transition"
                                                      title="Save Sub-Target"
                                                    >
                                                      <Check className="w-4 h-4 stroke-[3]" />
                                                    </button>
                                                    <button
                                                      type="button"
                                                      onClick={() => setPendingSubTargetGroup(null)}
                                                      className="w-8 h-8 rounded-lg bg-amber-500 hover:bg-amber-600 text-white flex items-center justify-center shadow-md transition"
                                                      title="Cancel"
                                                    >
                                                      <X className="w-4 h-4 stroke-[3]" />
                                                    </button>
                                                  </div>
                                                ) : (
                                                  <>
                                                    {!isLocked && (
                                                      <div
                                                        draggable={isFirstRow && !isEditingThisGroup}
                                                        onDragStart={() => isFirstRow && !isEditingThisGroup && handleDragStart(groupIdx)}
                                                        className="cursor-grab active:cursor-grabbing text-slate-400 hover:text-slate-600 dark:hover:text-slate-200 p-1"
                                                        title="Drag to reorder"
                                                      >
                                                        <GripVertical className="w-4 h-4" />
                                                      </div>
                                                    )}
                                                    {isVerified && <CheckCircle2 className="w-5 h-5 text-emerald-600" />}
                                                    {(historyTargetIds.includes(group.indicatorId) ||
                                                      group.items.some((i) => historyItemIds.includes(i.itemId))) && (
                                                        <button
                                                          type="button"
                                                          onClick={() => handleOpenEditHistory(group.indicatorId)}
                                                          className="inline-flex items-center justify-center text-blue-600 hover:text-blue-800 dark:text-blue-400 dark:hover:text-blue-300 hover:bg-blue-100 dark:hover:bg-blue-950/60 rounded-md p-1 cursor-pointer transition-colors"
                                                          title="Show Edit History"
                                                        >
                                                          <Clock className="w-4 h-4" />
                                                        </button>
                                                      )}
                                                  </>
                                                )}
                                              </div>
                                            </td>
                                          )}

                                          {isFirstRow && (
                                            <td
                                              rowSpan={totalSubRows}
                                              className="px-3 py-3 border-r border-slate-200 dark:border-slate-800 font-bold text-slate-900 dark:text-slate-100 align-top"
                                            >
                                              {isEditingThisGroup ? (
                                                <div className="space-y-2">
                                                  <AutoResizingTextarea
                                                    rows={2}
                                                    value={editingGroup.activity}
                                                    onChange={(e) =>
                                                      setEditingGroup({ ...editingGroup, activity: e.target.value })
                                                    }
                                                    className="w-full rounded-lg border border-sky-300 dark:border-sky-700 bg-white dark:bg-slate-900 p-2 text-xs text-slate-900 dark:text-slate-100 focus:outline-none focus:ring-2 focus:ring-sky-500/40 shadow-inner"
                                                  />
                                                  <select
                                                    value={editingGroup.kraCategory}
                                                    onChange={(e) =>
                                                      setEditingGroup({
                                                        ...editingGroup,
                                                        kraCategory: Number(e.target.value),
                                                      })
                                                    }
                                                    className="w-full rounded-lg border border-sky-300 dark:border-sky-700 bg-white dark:bg-slate-900 p-1.5 text-xs text-slate-900 dark:text-slate-100 focus:outline-none focus:ring-2 focus:ring-sky-500/40"
                                                  >
                                                    {includeStrategicFunction && (
                                                      <option value={1}>Strategic Function</option>
                                                    )}
                                                    <option value={2}>Core Function</option>
                                                    <option value={3}>Support Function</option>
                                                  </select>
                                                </div>
                                              ) : (
                                                <FormattedText value={group.activity} />
                                              )}
                                            </td>
                                          )}

                                          {/* Success Indicator / Description */}
                                          <td className="px-3 py-3 border-r border-slate-200 dark:border-slate-800 text-slate-800 dark:text-slate-200 align-top">
                                            {isEditingThisGroup && itemEditIdx !== -1 ? (
                                              <AutoResizingTextarea
                                                rows={2}
                                                value={editingGroup.items[itemEditIdx].description}
                                                onChange={(e) => {
                                                  const newItems = [...editingGroup.items];
                                                  newItems[itemEditIdx].description = e.target.value;
                                                  setEditingGroup({ ...editingGroup, items: newItems });
                                                }}
                                                className="w-full rounded-lg border border-sky-300 dark:border-sky-700 bg-white dark:bg-slate-900 p-2 text-xs text-slate-900 dark:text-slate-100 focus:outline-none focus:ring-2 focus:ring-sky-500/40 shadow-inner"
                                              />
                                            ) : (
                                              <FormattedText value={item.description} />
                                            )}
                                          </td>

                                          {isLocked && (
                                            <td className="px-3 py-3 border-r border-slate-200 dark:border-slate-800 text-slate-700 dark:text-slate-300 align-top">
                                              <FormattedText value={item.actualAccomplishment} fallback="-" />
                                            </td>
                                          )}

                                          {/* RG Efficiency */}
                                          <td className="px-3 py-3 border-r border-slate-200 dark:border-slate-800 align-top">
                                            {isEditingThisGroup && itemEditIdx !== -1 ? (
                                              <AutoResizingTextarea
                                                rows={2}
                                                value={editingGroup.items[itemEditIdx].efficiencyTarget}
                                                onChange={(e) => {
                                                  const newItems = [...editingGroup.items];
                                                  newItems[itemEditIdx].efficiencyTarget = e.target.value;
                                                  setEditingGroup({ ...editingGroup, items: newItems });
                                                }}
                                                className="w-full rounded-lg border border-sky-300 dark:border-sky-700 bg-white dark:bg-slate-900 p-2 text-xs text-slate-900 dark:text-slate-100 focus:outline-none focus:ring-2 focus:ring-sky-500/40 shadow-inner"
                                              />
                                            ) : (
                                              <>
                                                <div className="font-medium text-slate-800 dark:text-slate-200">
                                                  <FormattedText value={item.efficiencyTarget} />
                                                </div>
                                                {isLocked && item.actEfficiency && (
                                                  <div className="mt-1 text-[11px] font-bold text-emerald-600">
                                                    Score: {item.actEfficiency}
                                                  </div>
                                                )}
                                              </>
                                            )}
                                          </td>

                                          {/* RG Quality */}
                                          <td className="px-3 py-3 border-r border-slate-200 dark:border-slate-800 align-top">
                                            {isEditingThisGroup && itemEditIdx !== -1 ? (
                                              <AutoResizingTextarea
                                                rows={2}
                                                value={editingGroup.items[itemEditIdx].qualityTarget}
                                                onChange={(e) => {
                                                  const newItems = [...editingGroup.items];
                                                  newItems[itemEditIdx].qualityTarget = e.target.value;
                                                  setEditingGroup({ ...editingGroup, items: newItems });
                                                }}
                                                className="w-full rounded-lg border border-sky-300 dark:border-sky-700 bg-white dark:bg-slate-900 p-2 text-xs text-slate-900 dark:text-slate-100 focus:outline-none focus:ring-2 focus:ring-sky-500/40 shadow-inner"
                                              />
                                            ) : (
                                              <>
                                                <div className="font-medium text-slate-800 dark:text-slate-200">
                                                  <FormattedText value={item.qualityTarget} />
                                                </div>
                                                {isLocked && item.actQuality && (
                                                  <div className="mt-1 text-[11px] font-bold text-emerald-600">
                                                    Score: {item.actQuality}
                                                  </div>
                                                )}
                                              </>
                                            )}
                                          </td>

                                          {/* RG Timeliness */}
                                          <td className="px-3 py-3 border-r border-slate-200 dark:border-slate-800 align-top">
                                            {isEditingThisGroup && itemEditIdx !== -1 ? (
                                              <AutoResizingTextarea
                                                rows={2}
                                                value={editingGroup.items[itemEditIdx].timelinessTarget}
                                                onChange={(e) => {
                                                  const newItems = [...editingGroup.items];
                                                  newItems[itemEditIdx].timelinessTarget = e.target.value;
                                                  setEditingGroup({ ...editingGroup, items: newItems });
                                                }}
                                                className="w-full rounded-lg border border-sky-300 dark:border-sky-700 bg-white dark:bg-slate-900 p-2 text-xs text-slate-900 dark:text-slate-100 focus:outline-none focus:ring-2 focus:ring-sky-500/40 shadow-inner"
                                              />
                                            ) : (
                                              <>
                                                <div className="font-medium text-slate-800 dark:text-slate-200">
                                                  <FormattedText value={item.timelinessTarget} />
                                                </div>
                                                {isLocked && item.actTimeliness && (
                                                  <div className="mt-1 text-[11px] font-bold text-emerald-600">
                                                    Score: {item.actTimeliness}
                                                  </div>
                                                )}
                                              </>
                                            )}
                                          </td>

                                          {isLocked && (
                                            <td className="px-3 py-3 text-center border-r border-slate-200 dark:border-slate-800 font-mono font-bold text-emerald-600 dark:text-emerald-400 align-top">
                                              {item.averageScore ? item.averageScore.toFixed(5) : '-'}
                                            </td>
                                          )}

                                          {/* RG MOVs */}
                                          <td className="px-3 py-3 border-r border-slate-200 dark:border-slate-800 align-top text-slate-700 dark:text-slate-300">
                                            {isEditingThisGroup && itemEditIdx !== -1 ? (
                                              <AutoResizingTextarea
                                                rows={2}
                                                value={editingGroup.items[itemEditIdx].movs}
                                                onChange={(e) => {
                                                  const newItems = [...editingGroup.items];
                                                  newItems[itemEditIdx].movs = e.target.value;
                                                  setEditingGroup({ ...editingGroup, items: newItems });
                                                }}
                                                className="w-full rounded-lg border border-sky-300 dark:border-sky-700 bg-white dark:bg-slate-900 p-2 text-xs text-slate-900 dark:text-slate-100 focus:outline-none focus:ring-2 focus:ring-sky-500/40 shadow-inner"
                                              />
                                            ) : (
                                              <div>
                                                <FormattedText value={item.movs} />
                                              </div>
                                            )}
                                          </td>

                                          {/* RG Remarks */}
                                          <td className="px-3 py-3 align-top text-slate-700 dark:text-slate-300">
                                            {isEditingThisGroup && itemEditIdx !== -1 ? (
                                              <AutoResizingTextarea
                                                rows={2}
                                                value={editingGroup.items[itemEditIdx].remarks}
                                                onChange={(e) => {
                                                  const newItems = [...editingGroup.items];
                                                  newItems[itemEditIdx].remarks = e.target.value;
                                                  setEditingGroup({ ...editingGroup, items: newItems });
                                                }}
                                                className="w-full rounded-lg border border-sky-300 dark:border-sky-700 bg-white dark:bg-slate-900 p-2 text-xs text-slate-900 dark:text-slate-100 focus:outline-none focus:ring-2 focus:ring-sky-500/40 shadow-inner"
                                              />
                                            ) : (
                                              <div>
                                                <FormattedText value={item.remarks} />
                                              </div>
                                            )}
                                          </td>
                                        </tr>
                                      );
                                    })}

                                    {/* Inline Pending Row for Sub-Target Creation */}
                                    {isPendingThisGroup && (
                                      <tr className="bg-amber-50/60 dark:bg-amber-950/40 border-b border-amber-200 dark:border-amber-900 align-top">
                                        <td className="p-2 border-r border-slate-200 dark:border-slate-800">
                                          <AutoResizingTextarea
                                            rows={2}
                                            value={pendingForm.description}
                                            onChange={(e) => setPendingForm({ ...pendingForm, description: e.target.value })}
                                            placeholder="Success Indicator Description..."
                                            className="w-full rounded-lg border border-amber-300 dark:border-amber-700 bg-white dark:bg-slate-900 p-2 text-xs text-slate-900 dark:text-slate-100 focus:outline-none focus:ring-2 focus:ring-amber-500/40 shadow-inner"
                                          />
                                        </td>
                                        {isLocked && <td className="p-2 border-r border-slate-200 dark:border-slate-800"></td>}
                                        <td className="p-2 border-r border-slate-200 dark:border-slate-800">
                                          <AutoResizingTextarea
                                            rows={2}
                                            value={pendingForm.efficiency}
                                            onChange={(e) => setPendingForm({ ...pendingForm, efficiency: e.target.value })}
                                            placeholder="RG Efficiency..."
                                            className="w-full rounded-lg border border-amber-300 dark:border-amber-700 bg-white dark:bg-slate-900 p-2 text-xs text-slate-900 dark:text-slate-100 focus:outline-none focus:ring-2 focus:ring-amber-500/40 shadow-inner"
                                          />
                                        </td>
                                        <td className="p-2 border-r border-slate-200 dark:border-slate-800">
                                          <AutoResizingTextarea
                                            rows={2}
                                            value={pendingForm.quality}
                                            onChange={(e) => setPendingForm({ ...pendingForm, quality: e.target.value })}
                                            placeholder="RG Quality..."
                                            className="w-full rounded-lg border border-amber-300 dark:border-amber-700 bg-white dark:bg-slate-900 p-2 text-xs text-slate-900 dark:text-slate-100 focus:outline-none focus:ring-2 focus:ring-amber-500/40 shadow-inner"
                                          />
                                        </td>
                                        <td className="p-2 border-r border-slate-200 dark:border-slate-800">
                                          <AutoResizingTextarea
                                            rows={2}
                                            value={pendingForm.timeliness}
                                            onChange={(e) => setPendingForm({ ...pendingForm, timeliness: e.target.value })}
                                            placeholder="RG Timeliness..."
                                            className="w-full rounded-lg border border-amber-300 dark:border-amber-700 bg-white dark:bg-slate-900 p-2 text-xs text-slate-900 dark:text-slate-100 focus:outline-none focus:ring-2 focus:ring-amber-500/40 shadow-inner"
                                          />
                                        </td>
                                        {isLocked && <td className="p-2 border-r border-slate-200 dark:border-slate-800"></td>}
                                        <td className="p-2 border-r border-slate-200 dark:border-slate-800">
                                          <AutoResizingTextarea
                                            rows={2}
                                            value={pendingForm.movs}
                                            onChange={(e) => setPendingForm({ ...pendingForm, movs: e.target.value })}
                                            placeholder="RG MOVs..."
                                            className="w-full rounded-lg border border-amber-300 dark:border-amber-700 bg-white dark:bg-slate-900 p-2 text-xs text-slate-900 dark:text-slate-100 focus:outline-none focus:ring-2 focus:ring-amber-500/40 shadow-inner"
                                          />
                                        </td>
                                        <td className="p-2">
                                          <AutoResizingTextarea
                                            rows={2}
                                            value={pendingForm.remarks}
                                            onChange={(e) => setPendingForm({ ...pendingForm, remarks: e.target.value })}
                                            placeholder="RG Remarks..."
                                            className="w-full rounded-lg border border-amber-300 dark:border-amber-700 bg-white dark:bg-slate-900 p-2 text-xs text-slate-900 dark:text-slate-100 focus:outline-none focus:ring-2 focus:ring-amber-500/40 shadow-inner"
                                          />
                                        </td>
                                      </tr>
                                    )}
                                  </Fragment>
                                );
                              })}
                            </tbody>
                          );
                        })}
                      </table>
                    </div>

                    {/* Pagination Controls Footer */}
                    <div className="flex flex-col items-center justify-between gap-3 sm:flex-row pt-4 border-t border-slate-200 dark:border-slate-800">
                      <div className="text-xs text-slate-500 dark:text-slate-400 font-medium">
                        {totalTargets > 0 ? (
                          isAllMode ? (
                            `Showing all ${totalTargets} targets`
                          ) : (
                            `Showing targets ${fromIndex}-${toIndex} of ${totalTargets}`
                          )
                        ) : (
                          'No targets found'
                        )}
                      </div>

                      {!isAllMode && totalPages > 1 && (
                        <nav className="flex flex-wrap items-center justify-center gap-1" aria-label="Target pagination">
                          <button
                            type="button"
                            onClick={() => setCurrentPage(1)}
                            disabled={pageToUse <= 1}
                            className="px-2.5 py-1.5 rounded-lg text-xs font-semibold text-slate-700 dark:text-slate-200 hover:bg-slate-100 dark:hover:bg-slate-800 disabled:opacity-40 disabled:cursor-not-allowed transition"
                          >
                            First
                          </button>
                          <button
                            type="button"
                            onClick={() => setCurrentPage((p) => Math.max(1, p - 1))}
                            disabled={pageToUse <= 1}
                            className="px-2.5 py-1.5 rounded-lg text-xs font-semibold text-slate-700 dark:text-slate-200 hover:bg-slate-100 dark:hover:bg-slate-800 disabled:opacity-40 disabled:cursor-not-allowed transition"
                          >
                            Previous
                          </button>

                          {Array.from({ length: totalPages }, (_, i) => i + 1).map((page) => (
                            <button
                              key={page}
                              type="button"
                              onClick={() => setCurrentPage(page)}
                              className={`px-3 py-1.5 rounded-lg text-xs font-semibold transition ${page === pageToUse
                                  ? 'bg-emerald-600 text-white shadow-sm'
                                  : 'text-slate-700 dark:text-slate-200 hover:bg-slate-100 dark:hover:bg-slate-800'
                                }`}
                            >
                              {page}
                            </button>
                          ))}

                          <button
                            type="button"
                            onClick={() => setCurrentPage((p) => Math.min(totalPages, p + 1))}
                            disabled={pageToUse >= totalPages}
                            className="px-2.5 py-1.5 rounded-lg text-xs font-semibold text-slate-700 dark:text-slate-200 hover:bg-slate-100 dark:hover:bg-slate-800 disabled:opacity-40 disabled:cursor-not-allowed transition"
                          >
                            Next
                          </button>
                          <button
                            type="button"
                            onClick={() => setCurrentPage(totalPages)}
                            disabled={pageToUse >= totalPages}
                            className="px-2.5 py-1.5 rounded-lg text-xs font-semibold text-slate-700 dark:text-slate-200 hover:bg-slate-100 dark:hover:bg-slate-800 disabled:opacity-40 disabled:cursor-not-allowed transition"
                          >
                            Last
                          </button>
                        </nav>
                      )}
                    </div>
                  </div>
                )}
              </div>
            )}

            {/* Tab 2: Deleted Target Repository */}
            {activeTab === 'deleted' && (
              <div className="space-y-6">
                <div className="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
                  <div className="relative flex-1 max-w-md">
                    <input
                      type="text"
                      value={deletedSearch}
                      onChange={(e) => setDeletedSearch(e.target.value)}
                      placeholder="Search deleted target, justification..."
                      className="w-full h-9 rounded-xl border border-slate-300 dark:border-slate-700 bg-white dark:bg-slate-800 px-3 text-xs text-slate-900 dark:text-slate-100 focus:outline-none focus:ring-2 focus:ring-emerald-500/30"
                    />
                  </div>
                  {deletedSearch && (
                    <button
                      type="button"
                      onClick={() => setDeletedSearch('')}
                      className="px-3 py-1.5 rounded-lg border border-slate-300 text-xs text-slate-600 hover:bg-slate-100"
                    >
                      Clear
                    </button>
                  )}
                </div>

                <div className="overflow-hidden rounded-xl border border-slate-200 dark:border-slate-800">
                  <table className="w-full text-xs text-left border-collapse">
                    <thead className="bg-slate-50 dark:bg-slate-800/80 font-semibold uppercase text-slate-500 dark:text-slate-400 border-b border-slate-200 dark:border-slate-800">
                      <tr>
                        <th className="px-3 py-2.5 border-r border-slate-200 dark:border-slate-800">KRA Category</th>
                        <th className="px-3 py-2.5 border-r border-slate-200 dark:border-slate-800">Key Result Area (Activity)</th>
                        <th className="px-3 py-2.5 border-r border-slate-200 dark:border-slate-800">Success Indicator (Description)</th>
                        <th className="px-3 py-2.5 border-r border-slate-200 dark:border-slate-800 whitespace-nowrap">Deleted Date & User</th>
                        <th className="px-3 py-2.5 border-r border-slate-200 dark:border-slate-800">Justification</th>
                        <th className="px-3 py-2.5 text-center">Action</th>
                      </tr>
                    </thead>
                    <tbody className="divide-y divide-slate-200 dark:divide-slate-800">
                      {filteredDeletedTargets.length > 0 ? (
                        filteredDeletedTargets.map((item) => (
                          <tr key={item.id} className="hover:bg-slate-50/50 dark:hover:bg-slate-800/30 transition align-top">
                            <td className="px-3 py-2.5 border-r border-slate-200 dark:border-slate-800 font-semibold text-slate-800 dark:text-slate-200">{item.kra_category_label}</td>
                            <td className="px-3 py-2.5 border-r border-slate-200 dark:border-slate-800 font-bold text-slate-900 dark:text-slate-100">
                              <FormattedText value={item.activity} />
                            </td>
                            <td className="px-3 py-2.5 border-r border-slate-200 dark:border-slate-800 text-slate-800 dark:text-slate-200 leading-relaxed">
                              <FormattedText value={item.description} />
                            </td>
                            <td className="px-3 py-2.5 border-r border-slate-200 dark:border-slate-800 text-slate-500 whitespace-nowrap">
                              <div>{item.deleted_at}</div>
                              <div className="text-[10px] font-semibold text-slate-600 dark:text-slate-400">{item.user_name}</div>
                            </td>
                            <td className="px-3 py-2.5 border-r border-slate-200 dark:border-slate-800 italic text-slate-700 dark:text-slate-300">
                              <FormattedText value={item.justification} />
                            </td>
                            <td className="px-3 py-2.5 text-center">
                              {!isLocked && (
                                <button
                                  type="button"
                                  onClick={() => router.post(`/inertia/ipcrf/myratings/${rating.id}/toggle-status`, { action: 'unlock' })}
                                  className="px-3 py-1 rounded-lg bg-emerald-600 text-white font-semibold hover:bg-emerald-700 text-xs shadow-xs"
                                >
                                  Restore
                                </button>
                              )}
                            </td>
                          </tr>
                        ))
                      ) : (
                        <tr>
                          <td colSpan={6} className="px-3 py-8 text-center text-slate-500">
                            No deleted targets found.
                          </td>
                        </tr>
                      )}
                    </tbody>
                  </table>
                </div>
              </div>
            )}

            {/* Tab 3: Checkpoint Changes */}
            {activeTab === 'checkpoint' && (
              <div className="space-y-6">
                {(checkpointItemId || checkpointTargetId) && (
                  <div className="flex items-center justify-between p-3 rounded-xl bg-blue-50 dark:bg-blue-950/40 border border-blue-200 dark:border-blue-800 text-xs text-blue-900 dark:text-blue-200">
                    <div className="flex items-center gap-2">
                      <Clock className="w-4 h-4 text-blue-600" />
                      <span>Showing edit history for selected target item</span>
                    </div>
                    <button
                      type="button"
                      onClick={() => {
                        setCheckpointItemId(null);
                        setCheckpointTargetId(null);
                      }}
                      className="px-2.5 py-1 rounded-lg bg-blue-600 hover:bg-blue-700 text-white font-semibold text-xs transition"
                    >
                      Show All History
                    </button>
                  </div>
                )}
                <div className="overflow-hidden rounded-xl border border-slate-200 dark:border-slate-800">
                  <table className="w-full text-xs text-left">
                    <thead className="bg-slate-50 dark:bg-slate-800/80 font-semibold uppercase text-slate-500">
                      <tr>
                        <th className="px-3 py-2.5 text-center w-[50px]">#</th>
                        <th className="px-3 py-2.5 w-[35%]">Original Success Indicator</th>
                        <th className="px-3 py-2.5 w-[35%]">Proposed Amendment</th>
                        <th className="px-3 py-2.5">Justification</th>
                      </tr>
                    </thead>
                    <tbody className="divide-y divide-slate-200 dark:divide-slate-800">
                      {checkpointChanges.filter((c: any) => {
                        if (!checkpointItemId && !checkpointTargetId) return true;
                        return (
                          (checkpointItemId && Number(c.sem_item_id) === Number(checkpointItemId)) ||
                          (checkpointTargetId && Number(c.sem_target_id) === Number(checkpointTargetId))
                        );
                      }).length > 0 ? (
                        checkpointChanges
                          .filter((c: any) => {
                            if (!checkpointItemId && !checkpointTargetId) return true;
                            return (
                              (checkpointItemId && Number(c.sem_item_id) === Number(checkpointItemId)) ||
                              (checkpointTargetId && Number(c.sem_target_id) === Number(checkpointTargetId))
                            );
                          })
                          .map((change, idx) => (
                            <tr key={idx} className="hover:bg-slate-50/50 dark:hover:bg-slate-800/30 transition align-top">
                              <td className="px-3 py-2 text-center font-bold text-slate-500">{idx + 1}</td>
                              <td className="px-3 py-2 space-y-1">
                                <div className="font-bold text-slate-900 dark:text-slate-100 mb-1">{change.activity_title}</div>
                                {change.fields.map((f, fi) => (
                                  <div key={fi}>
                                    <span className="font-semibold italic text-slate-500">{f.field_label}: </span>
                                    <span>{f.old_value}</span>
                                  </div>
                                ))}
                              </td>
                              <td className="px-3 py-2 space-y-1">
                                <div className="font-bold text-slate-900 dark:text-slate-100 mb-1">{change.activity_title}</div>
                                {change.fields.map((f, fi) => (
                                  <div key={fi}>
                                    <span className="font-semibold italic text-slate-500">{f.field_label}: </span>
                                    <span>{f.new_value}</span>
                                  </div>
                                ))}
                              </td>
                              <td className="px-3 py-2 italic text-slate-700 dark:text-slate-300">{change.justification}</td>
                            </tr>
                          ))
                      ) : (
                        <tr>
                          <td colSpan={4} className="px-3 py-8 text-center text-slate-500">
                            No checkpoint changes recorded yet.
                          </td>
                        </tr>
                      )}
                    </tbody>
                  </table>
                </div>
              </div>
            )}

            {/* Tab 4: Supervisor's Feedback */}
            {activeTab === 'feedback' && (
              <div className="space-y-6">
                {/* Status Banner */}
                <div
                  className={`rounded-2xl border p-6 flex flex-col items-center justify-center text-center space-y-2 ${isVerified
                      ? 'border-emerald-200 bg-emerald-50 dark:bg-emerald-950/40 text-emerald-900 dark:text-emerald-100'
                      : 'border-amber-200 bg-amber-50 dark:bg-amber-950/40 text-amber-900 dark:text-amber-100'
                    }`}
                >
                  <div className={`p-3 rounded-full ${isVerified ? 'bg-emerald-100 text-emerald-600' : 'bg-amber-100 text-amber-600'}`}>
                    {isVerified ? <ShieldCheck className="w-8 h-8" /> : <MessageSquare className="w-8 h-8" />}
                  </div>
                  <h3 className="text-xl font-bold">{isVerified ? 'Congratulations! You are now Verified.' : 'Pending Verification'}</h3>
                  <p className="text-xs max-w-md opacity-80">
                    {isVerified
                      ? 'This semestral target has been verified. Supervisor feedback, review notes, and approval details are available below.'
                      : 'This semestral target is pending verification. Supervisor feedback will appear once verification is completed.'}
                  </p>
                </div>

                {/* 4 Cards for Areas of Improvement */}
                <div>
                  <div className="flex items-center justify-between mb-3">
                    <h3 className="text-sm font-bold text-slate-900 dark:text-slate-100">Areas of Improvement & Development Plan</h3>
                    {!isLocked && (
                      <button
                        type="button"
                        onClick={() => setShowAreaModal(true)}
                        className="px-3 py-1.5 rounded-xl bg-slate-900 dark:bg-slate-100 text-white dark:text-slate-900 text-xs font-semibold hover:bg-slate-800"
                      >
                        + Add Development Plan Item
                      </button>
                    )}
                  </div>

                  {areasOfImprovement.length > 0 ? (
                    <div className="grid gap-4 md:grid-cols-2 lg:grid-cols-4">
                      {areasOfImprovement.map((area) => (
                        <div key={area.id} className="rounded-2xl border border-slate-200 dark:border-slate-800 bg-white dark:bg-slate-900 p-4 shadow-sm space-y-3">
                          <div>
                            <span className="text-[10px] font-bold uppercase tracking-wider text-amber-600">Areas of Improvement</span>
                            <p className="text-xs text-slate-800 dark:text-slate-200 mt-1 font-semibold">{area.areas_improvement}</p>
                          </div>
                          <div>
                            <span className="text-[10px] font-bold uppercase tracking-wider text-sky-600">Development Activities</span>
                            <p className="text-xs text-slate-700 dark:text-slate-300 mt-1">{area.development_activities}</p>
                          </div>
                          <div>
                            <span className="text-[10px] font-bold uppercase tracking-wider text-emerald-600">Support Resources</span>
                            <p className="text-xs text-slate-700 dark:text-slate-300 mt-1">{area.support_resources}</p>
                          </div>
                          <div>
                            <span className="text-[10px] font-bold uppercase tracking-wider text-violet-600">Progress Intervention</span>
                            <p className="text-xs text-slate-700 dark:text-slate-300 mt-1">{area.progress_intervention || '-'}</p>
                          </div>
                          {!isLocked && (
                            <button
                              type="button"
                              onClick={() => router.delete(`/inertia/ipcrf/myratings/${rating.id}/areas-improvement/${area.id}`)}
                              className="text-[11px] font-semibold text-rose-600 hover:underline pt-2 border-t border-slate-100 dark:border-slate-800 block w-full text-right"
                            >
                              Delete
                            </button>
                          )}
                        </div>
                      ))}
                    </div>
                  ) : (
                    <div className="rounded-2xl border border-dashed border-slate-200 dark:border-slate-800 bg-slate-50 dark:bg-slate-800/30 p-8 text-center text-xs text-slate-500">
                      No development plan items added yet. Click + Add Development Plan Item to record professional goals.
                    </div>
                  )}
                </div>

                {/* Recommendations & Strengths */}
                <div className="grid gap-4 md:grid-cols-2">
                  <div className="rounded-2xl border border-slate-200 dark:border-slate-800 bg-white dark:bg-slate-900 p-4 shadow-sm space-y-2">
                    <h4 className="text-xs font-bold uppercase tracking-wider text-rose-600">Rater's Comments, Recommendations & Commendations</h4>
                    <div className="text-xs text-slate-700 dark:text-slate-300 leading-relaxed" dangerouslySetInnerHTML={{ __html: rating.recommendation || '-' }} />
                  </div>

                  <div className="rounded-2xl border border-slate-200 dark:border-slate-800 bg-white dark:bg-slate-900 p-4 shadow-sm space-y-2">
                    <h4 className="text-xs font-bold uppercase tracking-wider text-emerald-600">Strengths</h4>
                    <div className="text-xs text-slate-700 dark:text-slate-300 leading-relaxed" dangerouslySetInnerHTML={{ __html: rating.strengths || '-' }} />
                  </div>
                </div>
              </div>
            )}

            {/* Tab 5: Documentation */}
            {activeTab === 'documentation' && (
              <div className="space-y-6">
                {/* Upload Zone */}
                <div className="rounded-2xl border border-dashed border-slate-300 dark:border-slate-700 bg-slate-50 dark:bg-slate-800/30 p-6 text-center">
                  <UploadCloud className="w-10 h-10 text-slate-400 mx-auto mb-2" />
                  <h4 className="text-sm font-bold text-slate-900 dark:text-slate-100">Upload Semestral Documentation & Attachments</h4>
                  <p className="text-xs text-slate-500 mb-4">Support PDF, Images, Word, PowerPoint (max 20MB)</p>
                  <label className="inline-flex items-center gap-2 rounded-xl bg-slate-900 dark:bg-slate-100 text-white dark:text-slate-900 px-4 py-2 text-xs font-semibold cursor-pointer hover:bg-slate-800 transition">
                    <span>Select Files</span>
                    <input type="file" multiple onChange={handleFileUpload} className="hidden" />
                  </label>
                </div>

                {/* Uploaded Files Grid */}
                <div>
                  <h4 className="text-xs font-bold uppercase tracking-wider text-slate-500 mb-3">Attached Documents ({documentationFiles.length})</h4>
                  {documentationFiles.length > 0 ? (
                    <div className="grid gap-3 sm:grid-cols-2 md:grid-cols-3 lg:grid-cols-4">
                      {documentationFiles.map((doc, idx) => (
                        <div key={idx} className="rounded-2xl border border-slate-200 dark:border-slate-800 bg-white dark:bg-slate-900 p-3 shadow-sm flex items-center gap-3">
                          <div className="p-2.5 rounded-xl bg-slate-100 dark:bg-slate-800 text-slate-600 dark:text-slate-300">
                            <File className="w-5 h-5" />
                          </div>
                          <div className="flex-1 min-w-0">
                            <p className="text-xs font-semibold text-slate-900 dark:text-slate-100 truncate">{doc.name}</p>
                            <p className="text-[10px] text-slate-500">{doc.modified_at}</p>
                          </div>
                          <div className="flex items-center gap-1">
                            <button
                              type="button"
                              onClick={() => setPreviewFile(doc)}
                              className="p-1 text-emerald-600 hover:text-emerald-700"
                              title="Preview Document"
                            >
                              <Eye className="w-4 h-4" />
                            </button>
                            <button
                              type="button"
                              onClick={() => handleDocDelete(doc.name)}
                              className="p-1 text-rose-600 hover:text-rose-700"
                              title="Delete Document"
                            >
                              <X className="w-4 h-4" />
                            </button>
                          </div>
                        </div>
                      ))}
                    </div>
                  ) : (
                    <div className="text-xs text-slate-500 text-center py-6">No documentation files attached.</div>
                  )}
                </div>
              </div>
            )}
          </div>
        </div>

        {/* EXACT LIVEWIRE RIGHT CLICK CONTEXT MENU & SUB-MENU FLYOUTS */}
        {contextMenu && (
          <div
            style={{ top: `${contextMenu.y}px`, left: `${contextMenu.x}px` }}
            className="absolute z-50 min-w-[14rem] rounded-xl border border-slate-200 dark:border-slate-800 bg-white dark:bg-slate-900 p-1.5 shadow-2xl animate-in fade-in-50 zoom-in-95 text-xs font-medium select-none text-slate-900 dark:text-slate-100"
            onClick={(e) => e.stopPropagation()}
          >
            {/* Header: OPTIONS */}
            <div className="px-2.5 py-1.5 text-[10px] font-bold uppercase tracking-wider text-slate-400 border-b border-slate-100 dark:border-slate-800 mb-1 flex items-center justify-between">
              <div className="flex items-center gap-1.5">
                <Sliders className="w-3.5 h-3.5 text-emerald-600 dark:text-emerald-400" />
                <span>OPTIONS</span>
              </div>
            </div>

            {/* Menu Item 1: Add Target (with flyout sub-menu trigger) */}
            {!isLocked && (
              <div
                className="relative"
                onMouseEnter={() => setActiveSubMenu('add')}
              >
                <button
                  type="button"
                  onClick={() => setActiveSubMenu(activeSubMenu === 'add' ? null : 'add')}
                  className={`flex w-full items-center justify-between gap-2 rounded-lg px-2.5 py-1.5 text-left transition-colors ${activeSubMenu === 'add' ? 'bg-slate-100 dark:bg-slate-800' : 'hover:bg-slate-100 dark:hover:bg-slate-800'
                    }`}
                >
                  <div className="flex items-center gap-2">
                    <PlusCircle className="w-4 h-4 text-slate-700 dark:text-slate-300" />
                    <span>Add Target</span>
                  </div>
                  <ChevronRight className="w-3.5 h-3.5 text-slate-400" />
                </button>

                {/* Sub-menu Flyout for Add Target */}
                {activeSubMenu === 'add' && (
                  <div className="absolute left-full top-0 ml-1 min-w-[12rem] rounded-xl border border-slate-200 dark:border-slate-800 bg-white dark:bg-slate-900 p-1.5 shadow-2xl z-50">
                    <button
                      type="button"
                      onClick={() => {
                        if (contextMenu) {
                          addForm.setData({
                            category: contextMenu.kraCategory,
                            activity: '',
                            description: '',
                            efficiency: '',
                            quality: '',
                            timeliness: '',
                            movs: '',
                            remarks: '',
                          });
                        }
                        setShowAddModal(true);
                        setContextMenu(null);
                        setActiveSubMenu(null);
                      }}
                      className="flex w-full items-center gap-2 rounded-lg px-2.5 py-1.5 text-left hover:bg-slate-100 dark:hover:bg-slate-800 transition-colors"
                    >
                      <Plus className="w-4 h-4 text-slate-700 dark:text-slate-300" />
                      <span>Add new target</span>
                    </button>

                    <button
                      type="button"
                      onClick={() => {
                        if (contextMenu) {
                          const targetGroup = indicatorsList.find((g) => g.indicatorId === contextMenu.indicatorId);
                          if (targetGroup) {
                            setPendingSubTargetGroup({
                              indicatorId: targetGroup.indicatorId,
                              kraCategory: targetGroup.kraCategory,
                              activity: targetGroup.activity,
                            });
                            setPendingForm({
                              description: '',
                              efficiency: '',
                              quality: '',
                              timeliness: '',
                              movs: '',
                              remarks: '',
                            });
                          }
                        }
                        setContextMenu(null);
                        setActiveSubMenu(null);
                      }}
                      className="flex w-full items-center gap-2 rounded-lg px-2.5 py-1.5 text-left hover:bg-slate-100 dark:hover:bg-slate-800 transition-colors"
                    >
                      <FilePlus className="w-4 h-4 text-slate-700 dark:text-slate-300" />
                      <span>Add sub-target</span>
                    </button>
                  </div>
                )}
              </div>
            )}

            {/* Menu Item 2: Edit Target */}
            {!isLocked && (
              <button
                type="button"
                onMouseEnter={() => setActiveSubMenu(null)}
                onClick={() => {
                  const targetGroup = indicatorsList.find((g) => g.indicatorId === contextMenu.indicatorId);
                  if (targetGroup) {
                    handleStartEditGroup(targetGroup);
                  }
                  setContextMenu(null);
                }}
                className="flex w-full items-center gap-2 rounded-lg px-2.5 py-1.5 text-left hover:bg-slate-100 dark:hover:bg-slate-800 transition-colors"
              >
                <Pencil className="w-4 h-4 text-amber-500 dark:text-amber-400" />
                <span>Edit Target</span>
              </button>
            )}

            {/* Menu Item 3: Show Edit History */}
            {(historyTargetIds.includes(contextMenu.indicatorId) ||
              (contextMenu.itemId ? historyItemIds.includes(contextMenu.itemId) : false)) && (
                <button
                  type="button"
                  onMouseEnter={() => setActiveSubMenu(null)}
                  onClick={() => {
                    if (contextMenu) {
                      handleOpenEditHistory(contextMenu.indicatorId, contextMenu.itemId);
                    }
                    setContextMenu(null);
                  }}
                  className="flex w-full items-center gap-2 rounded-lg px-2.5 py-1.5 text-left hover:bg-slate-100 dark:hover:bg-slate-800 transition-colors"
                >
                  <Clock className="w-4 h-4 text-blue-500 dark:text-blue-400" />
                  <span>Show Edit History</span>
                </button>
              )}

            {/* Divider */}
            <div className="my-1 border-t border-slate-100 dark:border-slate-800"></div>

            {/* Menu Item 4: Delete (with flyout sub-menu trigger) */}
            {!isLocked && (
              <div
                className="relative"
                onMouseEnter={() => setActiveSubMenu('delete')}
              >
                <button
                  type="button"
                  onClick={() => setActiveSubMenu(activeSubMenu === 'delete' ? null : 'delete')}
                  className={`flex w-full items-center justify-between gap-2 rounded-lg px-2.5 py-1.5 text-left text-rose-600 dark:text-rose-400 transition-colors ${activeSubMenu === 'delete' ? 'bg-rose-50 dark:bg-rose-950/40' : 'hover:bg-rose-50 dark:hover:bg-rose-950/40'
                    }`}
                >
                  <div className="flex items-center gap-2">
                    <Trash2 className="w-4 h-4 text-rose-500 dark:text-rose-400" />
                    <span>Delete</span>
                  </div>
                  <ChevronRight className="w-3.5 h-3.5 text-slate-400" />
                </button>

                {/* Sub-menu Flyout for Delete */}
                {activeSubMenu === 'delete' && (
                  <div className="absolute left-full top-0 ml-1 min-w-[17rem] rounded-xl border border-slate-200 dark:border-slate-800 bg-white dark:bg-slate-900 p-1.5 shadow-2xl z-50">
                    <button
                      type="button"
                      onClick={() => {
                        if (contextMenu) setDeletingTargetId(contextMenu.indicatorId);
                        setContextMenu(null);
                        setActiveSubMenu(null);
                      }}
                      className="flex w-full items-center gap-2 rounded-lg px-2.5 py-1.5 text-left text-rose-600 dark:text-rose-400 hover:bg-rose-50 dark:hover:bg-rose-950/40 transition-colors"
                    >
                      <Trash2 className="w-4 h-4 text-rose-500 dark:text-rose-400 shrink-0" />
                      <span>Delete selected target and its sub-target</span>
                    </button>

                    <button
                      type="button"
                      disabled={!contextMenu?.itemId || (contextMenu.totalSubRows ?? 0) <= 1}
                      onClick={() => {
                        if (contextMenu?.itemId && (contextMenu.totalSubRows ?? 0) > 1) {
                          setDeletingSubTargetId(contextMenu.itemId);
                        }
                        setContextMenu(null);
                        setActiveSubMenu(null);
                      }}
                      className={`flex w-full items-center gap-2 rounded-lg px-2.5 py-1.5 text-left transition-colors ${!contextMenu?.itemId || (contextMenu.totalSubRows ?? 0) <= 1
                          ? 'opacity-40 cursor-not-allowed text-slate-400 dark:text-slate-500'
                          : 'text-rose-600 dark:text-rose-400 hover:bg-rose-50 dark:hover:bg-rose-950/40'
                        }`}
                    >
                      <MinusCircle className="w-4 h-4 text-rose-500 dark:text-rose-400 shrink-0" />
                      <span>Delete selected sub-target only</span>
                    </button>
                  </div>
                )}
              </div>
            )}
          </div>
        )}

        {/* EXACT LIVEWIRE EDIT HISTORY MODAL */}
        {showHistoryModal && (
          <div className="fixed inset-0 z-50 flex items-center justify-center p-4 bg-slate-900/60 backdrop-blur-sm animate-in fade-in-50">
            <div
              className="relative w-full max-w-5xl rounded-2xl border border-slate-200 dark:border-slate-800 bg-white dark:bg-slate-900 p-6 shadow-2xl space-y-5 select-none"
              onClick={(e) => e.stopPropagation()}
            >
              {/* Modal Header */}
              <div className="flex items-start justify-between border-b border-slate-100 dark:border-slate-800 pb-4">
                <div>
                  <h3 className="text-lg font-bold text-slate-900 dark:text-slate-100">Edit History</h3>
                  <p className="text-xs text-slate-500 dark:text-slate-400 mt-1">
                    Review all modifications, edits, and justifications recorded for the selected target.
                  </p>
                </div>
                <button
                  type="button"
                  onClick={() => setShowHistoryModal(false)}
                  className="rounded-lg p-1 text-slate-400 hover:bg-slate-100 hover:text-slate-600 dark:hover:bg-slate-800 transition"
                >
                  <X className="w-5 h-5" />
                </button>
              </div>

              {/* Table Body Container */}
              <div className="max-h-[60vh] overflow-y-auto rounded-xl border border-slate-200 dark:border-slate-800">
                {isLoadingHistory ? (
                  <div className="p-12 text-center text-xs text-slate-500">Loading edit history...</div>
                ) : historyRecords.length === 0 ? (
                  <div className="p-12 text-center text-xs text-slate-500">
                    No edit history records found for this target entry.
                  </div>
                ) : (
                  <table className="w-full border-collapse text-xs">
                    <thead className="sticky top-0 bg-slate-100 dark:bg-slate-800 text-left font-semibold uppercase text-slate-600 dark:text-slate-300 border-b border-slate-200 dark:border-slate-700">
                      <tr>
                        <th className="border-r border-slate-200 dark:border-slate-700 px-3 py-2.5 font-bold">FIELD / TYPE</th>
                        <th className="border-r border-slate-200 dark:border-slate-700 px-3 py-2.5 font-bold">ORIGINAL / OLD VALUE</th>
                        <th className="border-r border-slate-200 dark:border-slate-700 px-3 py-2.5 font-bold">NEW VALUE</th>
                        <th className="border-r border-slate-200 dark:border-slate-700 px-3 py-2.5 font-bold whitespace-nowrap">DATE & USER</th>
                        <th className="px-3 py-2.5 font-bold">JUSTIFICATION</th>
                      </tr>
                    </thead>
                    <tbody className="divide-y divide-slate-200 dark:divide-slate-800">
                      {historyRecords.map((history, hIdx) => {
                        if (history.is_separator) {
                          return (
                            <tr key={`sep-${hIdx}`} className="bg-slate-100 dark:bg-slate-800/80 text-[11px] font-semibold">
                              <td
                                colSpan={3}
                                className="border-r border-slate-200 dark:border-slate-700 px-3 py-2 text-center tracking-wide uppercase text-slate-600 dark:text-slate-300 align-top"
                              >
                                <div className="truncate max-w-full font-bold">
                                  {`--- ${history.separator_title || 'GROUP'} ---`}
                                </div>
                              </td>
                              <td className="border-r border-slate-200 dark:border-slate-700 px-3 py-2 align-top text-slate-500 whitespace-nowrap bg-white dark:bg-slate-900">
                                <div>{history.date_created}</div>
                                <div className="text-[10px] font-semibold text-slate-500">{history.user_name}</div>
                              </td>
                              {(history.justification_rowspan ?? 1) > 0 && (
                                <td
                                  rowSpan={history.justification_rowspan}
                                  className="px-3 py-2 align-top text-slate-700 dark:text-slate-300 italic bg-white dark:bg-slate-900"
                                >
                                  <FormattedText value={history.justification} />
                                </td>
                              )}
                            </tr>
                          );
                        }

                        return (
                          <tr key={`row-${hIdx}`} className="border-b border-slate-200 dark:border-slate-800 hover:bg-slate-50/60 dark:hover:bg-slate-800/30">
                            <td className="border-r border-slate-200 dark:border-slate-800 px-3 py-2.5 align-top font-semibold text-slate-800 dark:text-slate-200 uppercase">
                              <div className="flex flex-col gap-1">
                                <span>{history.field_label || history.field_name}</span>
                                {history.action_type && (
                                  <span className={`px-1.5 py-0.5 rounded text-[10px] font-bold uppercase tracking-wider w-fit ${history.action_type === 'newly_added'
                                      ? 'bg-emerald-100 text-emerald-700 dark:bg-emerald-950 dark:text-emerald-300'
                                      : history.action_type === 'added_sub_target'
                                        ? 'bg-cyan-100 text-cyan-700 dark:bg-cyan-950 dark:text-cyan-300'
                                        : history.action_type === 'deleted'
                                          ? 'bg-rose-100 text-rose-700 dark:bg-rose-950 dark:text-rose-300'
                                          : 'bg-amber-100 text-amber-700 dark:bg-amber-950 dark:text-amber-300'
                                    }`}>
                                    {history.action_type.replace(/_/g, ' ')}
                                  </span>
                                )}
                              </div>
                            </td>
                            <td className="border-r border-slate-200 dark:border-slate-800 px-3 py-2.5 align-top text-slate-600 dark:text-slate-400">
                              <FormattedText value={history.old_value || history.original_value || '-'} fallback="-" />
                            </td>
                            <td className="border-r border-slate-200 dark:border-slate-800 px-3 py-2.5 align-top font-medium text-emerald-600 dark:text-emerald-400">
                              <FormattedText value={history.new_value || '-'} fallback="-" />
                            </td>
                            <td className="border-r border-slate-200 dark:border-slate-800 px-3 py-2.5 align-top text-slate-500 whitespace-nowrap">
                              <div>{history.date_created}</div>
                              <div className="text-[10px] font-semibold text-slate-500">{history.user_name}</div>
                            </td>
                            {(history.justification_rowspan ?? 1) > 0 && (
                              <td
                                rowSpan={history.justification_rowspan}
                                className="px-3 py-2.5 align-top text-slate-700 dark:text-slate-300 italic"
                              >
                                <FormattedText value={history.justification} />
                              </td>
                            )}
                          </tr>
                        );
                      })}
                    </tbody>
                  </table>
                )}
              </div>

              {/* Modal Footer */}
              <div className="flex items-center justify-between pt-2 border-t border-slate-100 dark:border-slate-800">
                {!isLocked && (
                  <button
                    type="button"
                    onClick={handleDiscardHistory}
                    disabled={historyRecords.length === 0}
                    className="h-9 px-4 rounded-xl bg-red-600 hover:bg-red-700 disabled:opacity-40 text-white text-xs font-semibold flex items-center gap-1.5 transition shadow"
                  >
                    <Trash2 className="w-3.5 h-3.5" />
                    <span>Discard</span>
                  </button>
                )}
                <button
                  type="button"
                  onClick={() => setShowHistoryModal(false)}
                  className="h-9 px-4 rounded-xl bg-slate-100 hover:bg-slate-200 dark:bg-slate-800 dark:hover:bg-slate-700 text-slate-700 dark:text-slate-200 text-xs font-semibold transition"
                >
                  Close
                </button>
              </div>
            </div>
          </div>
        )}

        {/* Get MOVs From Staff Modal */}
        {showMovStaffModal && (
          <div className="fixed inset-0 z-50 flex items-center justify-center bg-slate-900/50 backdrop-blur-sm p-4 animate-in fade-in">
            <div className="w-full max-w-2xl rounded-2xl border border-slate-200 dark:border-slate-800 bg-white dark:bg-slate-900 p-6 shadow-2xl space-y-4 max-h-[85vh] overflow-y-auto">
              <div className="flex items-start justify-between">
                <div>
                  <h3 className="text-base font-bold text-slate-900 dark:text-slate-100">Get MOVs From Staff</h3>
                  <p className="text-xs text-slate-500">Copy uploaded MOVs from staff targets into the currently selected item.</p>
                </div>
                <button type="button" onClick={() => setShowMovStaffModal(false)} className="text-slate-400 hover:text-slate-600">
                  <X className="w-4 h-4" />
                </button>
              </div>

              <div className="space-y-3 text-xs">
                {indicatorsList.map((group) =>
                  group.items.map((item) => (
                    <div key={item.itemId} className="flex items-center justify-between p-3 rounded-xl border border-slate-200 dark:border-slate-800 bg-slate-50 dark:bg-slate-800/40">
                      <div>
                        <p className="font-bold text-slate-900 dark:text-slate-100">{group.activity}</p>
                        <p className="text-slate-600 dark:text-slate-400 mt-0.5">{item.description}</p>
                        {item.movs && <p className="text-[11px] text-slate-500 mt-1">MOVs: {item.movs}</p>}
                      </div>
                      <button
                        type="button"
                        onClick={() => handleCopyStaffMov(item.itemId)}
                        className="px-3 py-1.5 rounded-xl bg-emerald-600 hover:bg-emerald-700 text-white font-semibold text-xs transition"
                      >
                        Use MOVs
                      </button>
                    </div>
                  ))
                )}
              </div>
            </div>
          </div>
        )}

        {/* Rate & Edit Modal */}
        {editingItem && (
          <div className="fixed inset-0 z-50 flex items-center justify-center bg-slate-900/50 backdrop-blur-sm p-4 animate-in fade-in">
            <div className="w-full max-w-xl rounded-2xl border border-slate-200 dark:border-slate-800 bg-white dark:bg-slate-900 p-6 shadow-2xl space-y-4">
              <div className="flex items-start justify-between">
                <div>
                  <h3 className="text-base font-bold text-slate-900 dark:text-slate-100">Record Actual Accomplishments & Ratings</h3>
                  <p className="text-xs text-slate-500 mt-0.5">{editingItem.description}</p>
                </div>
                <button type="button" onClick={() => setEditingItem(null)} className="text-slate-400 hover:text-slate-600">
                  <X className="w-4 h-4" />
                </button>
              </div>

              <form onSubmit={handleEditSubmit} className="space-y-4 text-xs">
                <div>
                  <label className="block font-semibold text-slate-700 dark:text-slate-300 mb-1">Actual Accomplishment</label>
                  <AutoResizingTextarea
                    rows={3}
                    value={editForm.data.actualAccomplishment}
                    onChange={(e) => editForm.setData('actualAccomplishment', e.target.value)}
                    placeholder="Describe specific outputs, quantities achieved..."
                    className="w-full rounded-xl border border-slate-300 dark:border-slate-700 bg-white dark:bg-slate-800 p-3 text-xs text-slate-900 dark:text-slate-100 focus:outline-none focus:ring-2 focus:ring-emerald-500/30"
                  />
                </div>

                <div className="grid gap-3 sm:grid-cols-3">
                  <div>
                    <label className="block font-semibold text-slate-700 dark:text-slate-300 mb-1">Quality Rating (1-5)</label>
                    <input
                      type="number"
                      step="0.01"
                      min="1"
                      max="5"
                      value={editForm.data.actQuality}
                      onChange={(e) => editForm.setData('actQuality', e.target.value)}
                      className="w-full h-9 rounded-xl border border-slate-300 dark:border-slate-700 bg-white dark:bg-slate-800 px-3 text-xs text-slate-900 dark:text-slate-100"
                    />
                  </div>
                  <div>
                    <label className="block font-semibold text-slate-700 dark:text-slate-300 mb-1">Efficiency Rating (1-5)</label>
                    <input
                      type="number"
                      step="0.01"
                      min="1"
                      max="5"
                      value={editForm.data.actEfficiency}
                      onChange={(e) => editForm.setData('actEfficiency', e.target.value)}
                      className="w-full h-9 rounded-xl border border-slate-300 dark:border-slate-700 bg-white dark:bg-slate-800 px-3 text-xs text-slate-900 dark:text-slate-100"
                    />
                  </div>
                  <div>
                    <label className="block font-semibold text-slate-700 dark:text-slate-300 mb-1">Timeliness Rating (1-5)</label>
                    <input
                      type="number"
                      step="0.01"
                      min="1"
                      max="5"
                      value={editForm.data.actTimeliness}
                      onChange={(e) => editForm.setData('actTimeliness', e.target.value)}
                      className="w-full h-9 rounded-xl border border-slate-300 dark:border-slate-700 bg-white dark:bg-slate-800 px-3 text-xs text-slate-900 dark:text-slate-100"
                    />
                  </div>
                </div>

                <div>
                  <label className="block font-semibold text-slate-700 dark:text-slate-300 mb-1">Remarks</label>
                  <input
                    type="text"
                    value={editForm.data.remarks}
                    onChange={(e) => editForm.setData('remarks', e.target.value)}
                    className="w-full h-9 rounded-xl border border-slate-300 dark:border-slate-700 bg-white dark:bg-slate-800 px-3 text-xs text-slate-900 dark:text-slate-100"
                  />
                </div>

                <div className="flex justify-end gap-2 pt-3 border-t border-slate-200 dark:border-slate-800">
                  <button
                    type="button"
                    onClick={() => setEditingItem(null)}
                    className="px-4 py-2 rounded-xl border border-slate-300 dark:border-slate-700 text-xs font-semibold text-slate-700 dark:text-slate-300 hover:bg-slate-50"
                  >
                    Cancel
                  </button>
                  <button
                    type="submit"
                    disabled={editForm.processing}
                    className="px-5 py-2 rounded-xl bg-emerald-600 hover:bg-emerald-700 text-white text-xs font-semibold shadow-sm"
                  >
                    Save Accomplishment Score
                  </button>
                </div>
              </form>
            </div>
          </div>
        )}

        {/* Add Target Modal */}
        {showAddModal && (
          <div className="fixed inset-0 z-50 flex items-center justify-center bg-slate-900/50 backdrop-blur-sm p-4 animate-in fade-in">
            <div className="w-full max-w-4xl max-h-[85vh] flex flex-col rounded-3xl border border-slate-200 dark:border-slate-800 bg-white dark:bg-slate-900 shadow-2xl overflow-hidden">
              {/* Modal Header */}
              <div className="px-6 py-4 border-b border-slate-200 dark:border-slate-800 flex items-center justify-between bg-slate-50/50 dark:bg-slate-900/50 shrink-0">
                <div>
                  <h3 className="text-lg font-bold text-slate-900 dark:text-slate-100">Add Semestral Target</h3>
                  <p className="text-xs text-slate-500 dark:text-slate-400 mt-0.5">
                    Create a new target entry inside your IPCRF semestral targets.
                  </p>
                </div>
                <button
                  type="button"
                  onClick={() => setShowAddModal(false)}
                  className="rounded-xl p-1.5 text-slate-400 hover:text-slate-600 dark:hover:text-slate-200 hover:bg-slate-100 dark:hover:bg-slate-800 transition"
                >
                  <X className="w-5 h-5" />
                </button>
              </div>

              {/* Modal Form Body */}
              <form onSubmit={handleAddSubmit} className="flex flex-col flex-1 overflow-hidden">
                <div className="p-6 overflow-y-auto flex-1 space-y-4 text-xs">
                  <div>
                    <label className="block text-xs font-semibold text-slate-700 dark:text-slate-300 mb-1">Category / Function</label>
                    <select
                      value={addForm.data.category}
                      onChange={(e) => addForm.setData('category', Number(e.target.value))}
                      className="w-full h-10 rounded-xl border border-slate-300 dark:border-slate-700 bg-white dark:bg-slate-800 px-3 text-xs font-medium text-slate-900 dark:text-slate-100 focus:outline-none focus:ring-2 focus:ring-emerald-500/30"
                    >
                      {includeStrategicFunction && <option value={1}>Strategic Function</option>}
                      <option value={2}>Core Function</option>
                      <option value={3}>Support Function</option>
                    </select>
                  </div>

                  <div className="grid gap-4 md:grid-cols-2">
                    <div>
                      <label className="block text-xs font-semibold text-slate-700 dark:text-slate-300 mb-1">Activity Title (Key Result Area)</label>
                      <AutoResizingTextarea
                        rows={2}
                        required
                        value={addForm.data.activity}
                        onChange={(e) => addForm.setData('activity', e.target.value)}
                        placeholder="Enter KRA / Activity title..."
                        className="w-full rounded-xl border border-slate-300 dark:border-slate-700 bg-white dark:bg-slate-800 p-3 text-xs text-slate-900 dark:text-slate-100 focus:outline-none focus:ring-2 focus:ring-emerald-500/30"
                      />
                    </div>

                    <div>
                      <label className="block text-xs font-semibold text-slate-700 dark:text-slate-300 mb-1">Success Indicator Description</label>
                      <AutoResizingTextarea
                        rows={2}
                        required
                        value={addForm.data.description}
                        onChange={(e) => addForm.setData('description', e.target.value)}
                        placeholder="Enter success indicator description..."
                        className="w-full rounded-xl border border-slate-300 dark:border-slate-700 bg-white dark:bg-slate-800 p-3 text-xs text-slate-900 dark:text-slate-100 focus:outline-none focus:ring-2 focus:ring-emerald-500/30"
                      />
                    </div>
                  </div>

                  <div className="flex items-center gap-3 py-1">
                    <div className="h-px flex-1 bg-slate-200 dark:bg-slate-800" />
                    <span className="text-xs font-bold uppercase tracking-wider text-slate-400 dark:text-slate-500">
                      Rating Guide Targets
                    </span>
                    <div className="h-px flex-1 bg-slate-200 dark:bg-slate-800" />
                  </div>

                  <div className="grid gap-4 md:grid-cols-3">
                    <div>
                      <label className="block text-xs font-semibold text-slate-700 dark:text-slate-300 mb-1">Efficiency Target</label>
                      <AutoResizingTextarea
                        rows={2}
                        required
                        value={addForm.data.efficiency}
                        onChange={(e) => addForm.setData('efficiency', e.target.value)}
                        placeholder="RG Efficiency target..."
                        className="w-full rounded-xl border border-slate-300 dark:border-slate-700 bg-white dark:bg-slate-800 p-3 text-xs text-slate-900 dark:text-slate-100 focus:outline-none focus:ring-2 focus:ring-emerald-500/30"
                      />
                    </div>

                    <div>
                      <label className="block text-xs font-semibold text-slate-700 dark:text-slate-300 mb-1">Quality Target</label>
                      <AutoResizingTextarea
                        rows={2}
                        required
                        value={addForm.data.quality}
                        onChange={(e) => addForm.setData('quality', e.target.value)}
                        placeholder="RG Quality target..."
                        className="w-full rounded-xl border border-slate-300 dark:border-slate-700 bg-white dark:bg-slate-800 p-3 text-xs text-slate-900 dark:text-slate-100 focus:outline-none focus:ring-2 focus:ring-emerald-500/30"
                      />
                    </div>

                    <div>
                      <label className="block text-xs font-semibold text-slate-700 dark:text-slate-300 mb-1">Timeliness Target</label>
                      <AutoResizingTextarea
                        rows={2}
                        required
                        value={addForm.data.timeliness}
                        onChange={(e) => addForm.setData('timeliness', e.target.value)}
                        placeholder="RG Timeliness target..."
                        className="w-full rounded-xl border border-slate-300 dark:border-slate-700 bg-white dark:bg-slate-800 p-3 text-xs text-slate-900 dark:text-slate-100 focus:outline-none focus:ring-2 focus:ring-emerald-500/30"
                      />
                    </div>
                  </div>

                  <div>
                    <label className="block text-xs font-semibold text-slate-700 dark:text-slate-300 mb-1">Means of Verification (MOVs)</label>
                    <AutoResizingTextarea
                      rows={2}
                      required
                      value={addForm.data.movs}
                      onChange={(e) => addForm.setData('movs', e.target.value)}
                      placeholder="Enter required MOVs..."
                      className="w-full rounded-xl border border-slate-300 dark:border-slate-700 bg-white dark:bg-slate-800 p-3 text-xs text-slate-900 dark:text-slate-100 focus:outline-none focus:ring-2 focus:ring-emerald-500/30"
                    />
                  </div>
                </div>

                {/* Modal Footer */}
                <div className="px-6 py-4 border-t border-slate-200 dark:border-slate-800 flex justify-end gap-2 bg-slate-50/50 dark:bg-slate-900/50 shrink-0">
                  <button
                    type="button"
                    onClick={() => setShowAddModal(false)}
                    className="px-4 py-2 rounded-xl border border-slate-300 dark:border-slate-700 text-xs font-semibold text-slate-700 dark:text-slate-300 hover:bg-slate-100 dark:hover:bg-slate-800 transition"
                  >
                    Cancel
                  </button>
                  <button
                    type="submit"
                    disabled={addForm.processing}
                    className="px-5 py-2 rounded-xl bg-emerald-600 hover:bg-emerald-700 text-white text-xs font-semibold shadow-sm transition disabled:opacity-50"
                  >
                    Save Target
                  </button>
                </div>
              </form>
            </div>
          </div>
        )}

        {/* Add Area of Improvement Modal */}
        {showAreaModal && (
          <div className="fixed inset-0 z-50 flex items-center justify-center bg-slate-900/50 backdrop-blur-sm p-4 animate-in fade-in">
            <div className="w-full max-w-lg rounded-2xl border border-slate-200 dark:border-slate-800 bg-white dark:bg-slate-900 p-6 shadow-2xl space-y-4">
              <div className="flex items-start justify-between">
                <h3 className="text-base font-bold text-slate-900 dark:text-slate-100">Add Professional Development Plan Item</h3>
                <button type="button" onClick={() => setShowAreaModal(false)} className="text-slate-400 hover:text-slate-600">
                  <X className="w-4 h-4" />
                </button>
              </div>

              <form onSubmit={handleAreaSubmit} className="space-y-3 text-xs">
                <div>
                  <label className="block font-semibold text-slate-700 dark:text-slate-300 mb-1">Areas of Improvement</label>
                  <input
                    type="text"
                    required
                    value={areaForm.data.areas_improvement}
                    onChange={(e) => areaForm.setData('areas_improvement', e.target.value)}
                    className="w-full h-9 rounded-xl border border-slate-300 dark:border-slate-700 bg-white dark:bg-slate-800 px-3 text-xs text-slate-900 dark:text-slate-100"
                  />
                </div>

                <div>
                  <label className="block font-semibold text-slate-700 dark:text-slate-300 mb-1">Development Activities</label>
                  <input
                    type="text"
                    required
                    value={areaForm.data.development_activities}
                    onChange={(e) => areaForm.setData('development_activities', e.target.value)}
                    className="w-full h-9 rounded-xl border border-slate-300 dark:border-slate-700 bg-white dark:bg-slate-800 px-3 text-xs text-slate-900 dark:text-slate-100"
                  />
                </div>

                <div>
                  <label className="block font-semibold text-slate-700 dark:text-slate-300 mb-1">Support / Resources Needed</label>
                  <input
                    type="text"
                    required
                    value={areaForm.data.support_resources}
                    onChange={(e) => areaForm.setData('support_resources', e.target.value)}
                    className="w-full h-9 rounded-xl border border-slate-300 dark:border-slate-700 bg-white dark:bg-slate-800 px-3 text-xs text-slate-900 dark:text-slate-100"
                  />
                </div>

                <div>
                  <label className="block font-semibold text-slate-700 dark:text-slate-300 mb-1">Progress / Intervention</label>
                  <input
                    type="text"
                    value={areaForm.data.progress_intervention}
                    onChange={(e) => areaForm.setData('progress_intervention', e.target.value)}
                    className="w-full h-9 rounded-xl border border-slate-300 dark:border-slate-700 bg-white dark:bg-slate-800 px-3 text-xs text-slate-900 dark:text-slate-100"
                  />
                </div>

                <div className="flex justify-end gap-2 pt-3 border-t border-slate-200 dark:border-slate-800">
                  <button
                    type="button"
                    onClick={() => setShowAreaModal(false)}
                    className="px-4 py-2 rounded-xl border border-slate-300 dark:border-slate-700 text-xs font-semibold text-slate-700 dark:text-slate-300 hover:bg-slate-50"
                  >
                    Cancel
                  </button>
                  <button
                    type="submit"
                    disabled={areaForm.processing}
                    className="px-5 py-2 rounded-xl bg-slate-900 dark:bg-slate-100 text-white dark:text-slate-900 text-xs font-semibold shadow-sm"
                  >
                    Save Plan Item
                  </button>
                </div>
              </form>
            </div>
          </div>
        )}

        {/* Delete Group Modal */}
        {deletingTargetId !== null && (
          <div className="fixed inset-0 z-50 flex items-center justify-center bg-slate-900/50 backdrop-blur-sm p-4 animate-in fade-in">
            <div className="w-full max-w-lg rounded-2xl border border-slate-200 dark:border-slate-800 bg-white dark:bg-slate-900 p-6 shadow-2xl space-y-4">
              <div className="flex items-start justify-between">
                <div>
                  <h3 className="text-lg font-bold text-slate-900 dark:text-slate-100">Delete Target Entry</h3>
                  <p className="text-xs text-slate-500 dark:text-slate-400 mt-1">
                    Are you sure you want to delete this semestral target entry? It can be recovered later using the Recover Deleted Targets menu.
                  </p>
                </div>
                <button type="button" onClick={() => { setDeletingTargetId(null); setDeleteJustification(''); }} className="text-slate-400 hover:text-slate-600">
                  <X className="w-4 h-4" />
                </button>
              </div>

              <div className="text-xs space-y-2 text-left">
                <label className="block font-semibold text-slate-700 dark:text-slate-300">
                  Justification <span className="text-rose-500">*</span>
                </label>
                <AutoResizingTextarea
                  rows={3}
                  required
                  value={deleteJustification}
                  onChange={(e) => setDeleteJustification(e.target.value)}
                  placeholder="Enter justification for deleting this target..."
                  className="w-full rounded-xl border border-slate-300 dark:border-slate-700 bg-white dark:bg-slate-800 p-3 text-xs text-slate-900 dark:text-slate-100 focus:outline-none focus:ring-2 focus:ring-rose-500/30"
                />
              </div>

              <div className="flex justify-end gap-2 pt-3 border-t border-slate-200 dark:border-slate-800">
                <button
                  type="button"
                  onClick={() => {
                    setDeletingTargetId(null);
                    setDeleteJustification('');
                  }}
                  className="px-4 py-2 rounded-xl border border-slate-300 dark:border-slate-700 text-xs font-semibold text-slate-700 dark:text-slate-300 hover:bg-slate-50"
                >
                  Cancel
                </button>
                <button
                  type="button"
                  onClick={() => {
                    router.delete(`/inertia/ipcrf/myratings/${rating.id}/target/${deletingTargetId}`, {
                      data: { justification: deleteJustification || 'Target Deleted' },
                      onSuccess: () => {
                        setDeletingTargetId(null);
                        setDeleteJustification('');
                      },
                    });
                  }}
                  className="px-5 py-2 rounded-xl bg-rose-600 hover:bg-rose-700 text-white text-xs font-semibold shadow-sm"
                >
                  Delete
                </button>
              </div>
            </div>
          </div>
        )}

        {/* Delete Sub-Target Modal */}
        {deletingSubTargetId !== null && (
          <div className="fixed inset-0 z-50 flex items-center justify-center bg-slate-900/50 backdrop-blur-sm p-4 animate-in fade-in">
            <div className="w-full max-w-lg rounded-2xl border border-slate-200 dark:border-slate-800 bg-white dark:bg-slate-900 p-6 shadow-2xl space-y-4">
              <div className="flex items-start justify-between">
                <div>
                  <h3 className="text-lg font-bold text-slate-900 dark:text-slate-100">Delete Sub-Target Entry</h3>
                  <p className="text-xs text-slate-500 dark:text-slate-400 mt-1">
                    Are you sure you want to delete this sub-target entry? It can be recovered later using the Recover Deleted Targets menu.
                  </p>
                </div>
                <button type="button" onClick={() => { setDeletingSubTargetId(null); setDeleteSubJustification(''); }} className="text-slate-400 hover:text-slate-600">
                  <X className="w-4 h-4" />
                </button>
              </div>

              <div className="text-xs space-y-2 text-left">
                <label className="block font-semibold text-slate-700 dark:text-slate-300">
                  Justification <span className="text-rose-500">*</span>
                </label>
                <AutoResizingTextarea
                  rows={3}
                  required
                  value={deleteSubJustification}
                  onChange={(e) => setDeleteSubJustification(e.target.value)}
                  placeholder="Enter justification for deleting this sub-target..."
                  className="w-full rounded-xl border border-slate-300 dark:border-slate-700 bg-white dark:bg-slate-800 p-3 text-xs text-slate-900 dark:text-slate-100 focus:outline-none focus:ring-2 focus:ring-rose-500/30"
                />
              </div>

              <div className="flex justify-end gap-2 pt-3 border-t border-slate-200 dark:border-slate-800">
                <button
                  type="button"
                  onClick={() => {
                    setDeletingSubTargetId(null);
                    setDeleteSubJustification('');
                  }}
                  className="px-4 py-2 rounded-xl border border-slate-300 dark:border-slate-700 text-xs font-semibold text-slate-700 dark:text-slate-300 hover:bg-slate-50"
                >
                  Cancel
                </button>
                <button
                  type="button"
                  onClick={() => {
                    router.delete(`/inertia/ipcrf/myratings/${rating.id}/subtarget/${deletingSubTargetId}`, {
                      data: { justification: deleteSubJustification || 'Sub-Target Deleted' },
                      onSuccess: () => {
                        setDeletingSubTargetId(null);
                        setDeleteSubJustification('');
                      },
                    });
                  }}
                  className="px-5 py-2 rounded-xl bg-rose-600 hover:bg-rose-700 text-white text-xs font-semibold shadow-sm"
                >
                  Delete
                </button>
              </div>
            </div>
          </div>
        )}

        {/* Attachment Lightbox Modal */}
        {previewFile && (
          <div className="fixed inset-0 z-50 flex items-center justify-center bg-slate-950/80 backdrop-blur-md p-4 animate-in fade-in">
            <div className="relative w-full max-w-4xl rounded-2xl border border-slate-800 bg-slate-900 p-4 text-white shadow-2xl flex flex-col max-h-[90vh]">
              <div className="flex items-center justify-between pb-3 border-b border-slate-800">
                <div className="flex items-center gap-2">
                  <File className="w-5 h-5 text-emerald-400" />
                  <span className="text-sm font-semibold truncate">{previewFile.name}</span>
                </div>
                <button type="button" onClick={() => setPreviewFile(null)} className="p-1 rounded-full hover:bg-slate-800 text-slate-400 hover:text-white">
                  <X className="w-5 h-5" />
                </button>
              </div>
              <div className="flex-1 overflow-hidden p-2 flex items-center justify-center min-h-[400px]">
                {previewFile.type === 'image' ? (
                  <img src={previewFile.url} alt={previewFile.name} className="max-h-full max-w-full rounded-lg object-contain" />
                ) : (
                  <iframe src={previewFile.url} title={previewFile.name} className="w-full h-[500px] rounded-lg bg-white" />
                )}
              </div>
            </div>
          </div>
        )}
      </section>
    </AppLayout>
  );
}

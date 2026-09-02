import React, { useState, useEffect, useMemo, Fragment } from 'react';
import { Head, Link, router, useForm } from '@inertiajs/react';
import UserAvatar from '../../Components/UserAvatar';
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
  ChevronUp,
  ChevronLeft,
  ChevronRight,
  Plus,
  PlusCircle,
  Pencil,
  X,
  UploadCloud,
  Upload,
  File,
  ShieldCheck,
  Search,
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
  Loader2,
  AlertCircle,
  Paperclip,
  Play,
  Presentation,
  BookOpen,
} from 'lucide-react';
import AppLayout from '../../Layouts/AppLayout';
import FormattedText, { formatTextValue } from '../../Components/FormattedText';
import AutoResizingTextarea, { adjustTextareaHeight } from '../../Components/AutoResizingTextarea';
import { toast } from '../../Components/ToastContainer';
import { readPersistedFilters, savePersistedFilters } from '../../lib/filterPersistence';

type ItemAttachment = {
  name: string;
  path: string;
  filename: string;
  url: string;
  type: 'pdf' | 'image';
  size: string;
};

type StaffMovUser = {
  id: number;
  name: string;
  position: string;
};

type StaffMovGroup = {
  semTargetId: number;
  activity: string;
  kraCategory: number;
  year: string;
  semester: number;
  staffName: string;
  items: Array<{
    itemId: number;
    description: string;
    attachmentCount: number;
  }>;
};

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
  scorecardEfficiency?: string | null;
  scorecardQuality?: string | null;
  scorecardTimeliness?: string | null;
  scorecardRemarks?: string | null;
  scorecardCreated?: number | null;
  scorecardCreatedByName?: string | null;
  averageScore: number | null;
  verified?: number;
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
  sem_item_id?: number | null;
  kra_category_label: string;
  activity: string;
  description: string;
  deleted_at: string;
  user_name: string;
  justification: string;
};

type CheckpointField = {
  field_name: string;
  field_label: string;
  order_rank: number;
  old_value: string;
  new_value: string;
};

type CheckpointItemGroup = {
  item_id: number;
  item_label: string;
  is_created: boolean;
  is_deleted: boolean;
  fields: CheckpointField[];
};

type CheckpointChange = {
  sem_target_id: number;
  activity_title: string;
  is_new_target: boolean;
  is_deleted: boolean;
  target_fields: CheckpointField[];
  item_groups: CheckpointItemGroup[];
  justification: string;
};

type DocFile = {
  name: string;
  path: string;
  url: string;
  mime: string;
  size: number;
  type: 'image' | 'pdf' | 'video' | 'presentation' | 'word' | 'other' | string;
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
  userProfile = {},
  functionScores = {},
  includeStrategicFunction,
  indicators: initialIndicators = [],
  areasOfImprovement = [],
  deletedTargets = [],
  checkpointChanges = [],
  documentationFiles = [],
  historyTargetIds = [],
  historyItemIds = [],
  navigation,
}: Props) {
  const filterPageKey = `semestral-target-${rating.id}`;
  const persistedPerformanceFilters = readPersistedFilters(filterPageKey, user, {
    search: '', category: '', targetStatus: '', perPage: '10', deletedSearch: '',
  });
  const [activeTab, setActiveTab] = useState<'performance' | 'deleted' | 'checkpoint' | 'feedback' | 'documentation'>('performance');
  const [indicatorsList, setIndicatorsList] = useState<IndicatorGroup[]>(initialIndicators || []);

  useEffect(() => {
    setIndicatorsList(initialIndicators || []);
  }, [initialIndicators]);

  // Local values map for locked/accomplishment inline editing & draft cache
  const [itemValues, setItemValues] = useState<Record<number, {
    actualAccomplishment: string;
    movs: string;
    remarks: string;
    actEfficiency: string;
    actQuality: string;
    actTimeliness: string;
    average?: string;
  }>>({});

  const [expandedItems, setExpandedItems] = useState<Record<string, boolean>>({});
  const [itemAttachmentCounts, setItemAttachmentCounts] = useState<Record<number, number>>({});

  // Filters state for performance tab
  const [search, setSearch] = useState(persistedPerformanceFilters.search);
  const [categoryFilter, setCategoryFilter] = useState(persistedPerformanceFilters.category);
  const [targetStatusFilter, setTargetStatusFilter] = useState(persistedPerformanceFilters.targetStatus);
  const [perPage, setPerPage] = useState(persistedPerformanceFilters.perPage);
  const [currentPage, setCurrentPage] = useState(1);

  // Search state for deleted targets
  const [deletedSearch, setDeletedSearch] = useState(persistedPerformanceFilters.deletedSearch);

  useEffect(() => {
    savePersistedFilters(filterPageKey, user, { search, category: categoryFilter, targetStatus: targetStatusFilter, perPage, deletedSearch });
  }, [filterPageKey, user, search, categoryFilter, targetStatusFilter, perPage, deletedSearch]);

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
  const [showEditJustificationModal, setShowEditJustificationModal] = useState(false);
  const [editJustificationText, setEditJustificationText] = useState('');
  const [isSavingEditGroup, setIsSavingEditGroup] = useState(false);
  const [checkpointItemId, setCheckpointItemId] = useState<number | null>(null);
  const [checkpointTargetId, setCheckpointTargetId] = useState<number | null>(null);
  const [previewFile, setPreviewFile] = useState<DocFile | null>(null);
  const [isUploadingDoc, setIsUploadingDoc] = useState(false);
  const [docUploadProgress, setDocUploadProgress] = useState(0);
  const [isDragOverDoc, setIsDragOverDoc] = useState(false);
  const docFileInputRef = React.useRef<HTMLInputElement>(null);
  const printDropdownRef = React.useRef<HTMLDivElement>(null);
  const optionsDropdownRef = React.useRef<HTMLDivElement>(null);
  const contextMenuRef = React.useRef<HTMLDivElement>(null);
  const [restoringId, setRestoringId] = useState<number | null>(null);
  const [showLockModal, setShowLockModal] = useState(false);
  const [showUnlockModal, setShowUnlockModal] = useState(false);
  const [showImReadyModal, setShowImReadyModal] = useState(false);
  const [showCancelReadyModal, setShowCancelReadyModal] = useState(false);
  const [confirmModal, setConfirmModal] = useState<{
    isOpen: boolean;
    title: string;
    message: string;
    confirmText?: string;
    cancelText?: string;
    variant?: 'primary' | 'danger' | 'warning' | 'emerald';
    icon?: 'lock' | 'unlock' | 'trash' | 'restore' | 'info';
    onConfirm: () => void;
  } | null>(null);

  // Upload MOVs Modal states
  const [showAttachmentModal, setShowAttachmentModal] = useState(false);
  const [attachmentItemId, setAttachmentItemId] = useState<number | null>(null);
  const [existingAttachments, setExistingAttachments] = useState<ItemAttachment[]>([]);
  const [isLoadingAttachments, setIsLoadingAttachments] = useState(false);
  const [isUploadingAttachments, setIsUploadingAttachments] = useState(false);
  const [queuedFiles, setQueuedFiles] = useState<Array<{ file: globalThis.File; name: string; url: string; type: 'pdf' | 'image'; size: string }>>([]);
  const [activeViewerIndex, setActiveViewerIndex] = useState<number>(-1);

  // Get MOVs From Staff Modal states
  const [showStaffMovModal, setShowStaffMovModal] = useState(false);
  const [staffMovItemId, setStaffMovItemId] = useState<number | null>(null);
  const [staffMovUsers, setStaffMovUsers] = useState<StaffMovUser[]>([]);
  const [selectedStaffUserId, setSelectedStaffUserId] = useState<string>('');
  const [staffMovSearch, setStaffMovSearch] = useState<string>('');
  const [staffMovSources, setStaffMovSources] = useState<StaffMovGroup[]>([]);
  const [isLoadingStaffMovs, setIsLoadingStaffMovs] = useState(false);
  const [isCopyingStaffMovs, setIsCopyingStaffMovs] = useState(false);
  const [staffMovContextYear, setStaffMovContextYear] = useState<string>('');
  const [staffMovContextSemester, setStaffMovContextSemester] = useState<string>('');

  const saveTimerRef = React.useRef<Record<string, any>>({});
  const lastSavedValuesRef = React.useRef<Record<number, any>>({});

  const isItemComplete = (item: IndicatorItem) => {
    if (!item) return false;
    const val = itemValues[item.itemId] || {};
    const naQ = Number(item.naQuantity) === 1;
    const naQl = Number(item.naQuality) === 1;
    const naT = Number(item.naTimeliness) === 1;
    const areAllNa = naQ && naQl && naT;
    if (areAllNa) return true;

    const q = String(val.actEfficiency ?? item.actEfficiency ?? '').trim().toUpperCase();
    const ql = String(val.actQuality ?? item.actQuality ?? '').trim().toUpperCase();
    const t = String(val.actTimeliness ?? item.actTimeliness ?? '').trim().toUpperCase();
    const accomp = String(val.actualAccomplishment ?? item.actualAccomplishment ?? '').trim();
    const movs = String(val.movs ?? item.movs ?? '').trim();
    const attachCount = Number(itemAttachmentCounts[item.itemId] ?? item.attachmentCount ?? 0);

    const isQValid = q === 'N/A' || naQ || (q !== '' && q !== '0' && q !== '0.00' && !isNaN(parseFloat(q)) && parseFloat(q) > 0);
    const isQlValid = ql === 'N/A' || naQl || (ql !== '' && ql !== '0' && ql !== '0.00' && !isNaN(parseFloat(ql)) && parseFloat(ql) > 0);
    const isTValid = t === 'N/A' || naT || (t !== '' && t !== '0' && t !== '0.00' && !isNaN(parseFloat(t)) && parseFloat(t) > 0);

    return isQValid && isQlValid && isTValid && accomp !== '' && movs !== '' && attachCount > 0;
  };

  // Filtered and paginated targets calculation
  const allFilteredIndicators = useMemo(() => {
    return (indicatorsList || []).filter((group) => {
      if (!group) return false;
      if (categoryFilter && String(group.kraCategory) !== String(categoryFilter)) {
        return false;
      }

      if (targetStatusFilter === 'checkpoint') {
        const hasHistory =
          (historyTargetIds || []).includes(group.indicatorId) ||
          (group.items || []).some((item) => (historyItemIds || []).includes(item.itemId));
        if (!hasHistory) return false;
      }

      if (targetStatusFilter === 'incomplete') {
        const hasIncompleteItem = (group.items || []).some((item) => !isItemComplete(item));
        if (!hasIncompleteItem) return false;
      }

      if (search) {
        const q = search.toLowerCase();
        const matchActivity = (group.activity || '').toLowerCase().includes(q);
        const matchItems = (group.items || []).some(
          (item) =>
            (item.description || '').toLowerCase().includes(q) ||
            (item.efficiencyTarget || '').toLowerCase().includes(q) ||
            (item.qualityTarget || '').toLowerCase().includes(q) ||
            (item.timelinessTarget || '').toLowerCase().includes(q) ||
            (item.movs || '').toLowerCase().includes(q) ||
            (item.remarks || '').toLowerCase().includes(q) ||
            (item.actualAccomplishment || '').toLowerCase().includes(q)
        );
        return matchActivity || matchItems;
      }
      return true;
    });
  }, [indicatorsList, categoryFilter, targetStatusFilter, search, itemValues, itemAttachmentCounts, historyTargetIds, historyItemIds]);

  // Check if ALL indicators across the entire semester target are complete
  const isEveryTargetComplete = useMemo(() => {
    if (!indicatorsList || indicatorsList.length === 0) return false;
    return indicatorsList.every(
      (group) => group.items && group.items.length > 0 && group.items.every((item) => isItemComplete(item))
    );
  }, [indicatorsList, itemValues, itemAttachmentCounts]);

  useEffect(() => {
    setCurrentPage(1);
  }, [search, categoryFilter, targetStatusFilter, perPage]);

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
      `/ipcrf/myratings/${rating.id}/target/${pendingSubTargetGroup.indicatorId}/subtarget`,
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

    const url = `/ipcrf/myratings/${rating.id}/target/${targetId}/history` + (itemId ? `?itemId=${itemId}` : '');
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

    const url = `/ipcrf/myratings/${rating.id}/target/${historyTargetId}/history` + (historyItemId ? `?itemId=${historyItemId}` : '');
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

  const handleRequestSaveEditGroup = () => {
    if (!editingGroup) return;

    if (!editingGroup.activity.trim()) {
      alert('Key Result Area is required.');
      return;
    }

    for (const item of editingGroup.items) {
      if (!item.description.trim()) {
        alert('Success Indicator description is required.');
        return;
      }
    }

    setEditJustificationText('');
    setShowEditJustificationModal(true);
  };

  const handleConfirmSaveEditGroup = () => {
    if (!editingGroup) return;

    if (!editJustificationText.trim()) {
      alert('Justification is required before saving changes.');
      return;
    }

    setIsSavingEditGroup(true);
    router.put(
      `/ipcrf/myratings/${rating.id}/target/${editingGroup.indicatorId}`,
      {
        activity: editingGroup.activity,
        kraCategory: editingGroup.kraCategory,
        items: editingGroup.items,
        justification: editJustificationText.trim(),
      },
      {
        onSuccess: () => {
          setShowEditJustificationModal(false);
          setEditingGroup(null);
          setEditJustificationText('');
          setIsSavingEditGroup(false);
        },
        onError: () => {
          setIsSavingEditGroup(false);
        },
      }
    );
  };

  const handleRestoreDeletedTarget = (item: DeletedTarget) => {
    setConfirmModal({
      isOpen: true,
      title: 'Restore Target',
      message: `Are you sure you want to restore "${item.activity}" back to its original location in active targets?`,
      confirmText: 'Confirm and Restore',
      variant: 'emerald',
      icon: 'restore',
      onConfirm: () => {
        setRestoringId(item.id);
        router.post(
          `/ipcrf/myratings/${rating.id}/target/${item.sem_target_id}/restore`,
          {
            itemId: item.sem_item_id || null,
          },
          {
            onFinish: () => {
              setRestoringId(null);
            },
          }
        );
      },
    });
  };

  // Drag and drop state
  const [draggedIndicatorId, setDraggedIndicatorId] = useState<number | null>(null);

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
  const isReadOnly = isVerified || Number(rating.lock) >= 2;
  const isLocked = Number(rating.lock) >= 1 || rating.isReady === 1;

  // Reset 'incomplete' filter if lock becomes 0 (not yet locked)
  useEffect(() => {
    if (Number(rating.lock) === 0 && targetStatusFilter === 'incomplete') {
      setTargetStatusFilter('');
    }
  }, [rating.lock]);

  useEffect(() => {
    const initialMap: Record<number, any> = {};
    const initialCounts: Record<number, number> = {};
    const baselineMap: Record<number, any> = {};
    const storageKey = `sem_target_drafts_${rating.id || 0}`;
    try {
      localStorage.removeItem(storageKey);
    } catch (e) { }

    initialIndicators.forEach((g) => {
      g.items.forEach((item) => {
        const baseline = {
          actualAccomplishment: formatTextValue(item.actualAccomplishment, ''),
          movs: formatTextValue(item.movs, ''),
          remarks: formatTextValue(item.remarks, ''),
          actEfficiency: item.actEfficiency ? String(item.actEfficiency) : '',
          actQuality: item.actQuality ? String(item.actQuality) : '',
          actTimeliness: item.actTimeliness ? String(item.actTimeliness) : '',
        };
        baselineMap[item.itemId] = { ...baseline };
        initialMap[item.itemId] = { ...baseline };
        initialCounts[item.itemId] = item.attachmentCount ?? 0;
      });
    });
    lastSavedValuesRef.current = baselineMap;
    setItemValues(initialMap);
    setItemAttachmentCounts(initialCounts);
  }, [initialIndicators, rating.id]);

  const saveLocalDraft = (_itemId: number, _data: any) => {
    // Local storage draft disabled as requested
  };

  const handleItemFieldChange = (itemId: number, field: string, value: string) => {
    setItemValues((prev) => ({
      ...prev,
      [itemId]: {
        ...prev[itemId],
        [field]: value,
      },
    }));
  };

  const scheduleSave = (itemId: number, field: string, value: string) => {
    handleItemFieldChange(itemId, field, value);
    saveLocalDraft(itemId, { [field]: value });
    const timerKey = `${itemId}_${field}`;
    if (saveTimerRef.current[timerKey]) {
      clearTimeout(saveTimerRef.current[timerKey]);
    }
    saveTimerRef.current[timerKey] = setTimeout(() => {
      saveField(itemId, field, value);
    }, 500);
  };

  const saveField = (itemId: number, field: string, value: string) => {
    const timerKey = `${itemId}_${field}`;
    if (saveTimerRef.current[timerKey]) {
      clearTimeout(saveTimerRef.current[timerKey]);
    }
    const current = itemValues[itemId] || {};
    const lastSaved = lastSavedValuesRef.current[itemId] || {};

    const updated = {
      actualAccomplishment: field === 'actualAccomplishment' ? value : (current.actualAccomplishment ?? ''),
      movs: field === 'movs' ? value : (current.movs ?? ''),
      remarks: field === 'remarks' ? value : (current.remarks ?? ''),
      actEfficiency: field === 'actEfficiency' ? value : (current.actEfficiency ?? ''),
      actQuality: field === 'actQuality' ? value : (current.actQuality ?? ''),
      actTimeliness: field === 'actTimeliness' ? value : (current.actTimeliness ?? ''),
    };

    const normalizeScore = (v: any) => {
      const str = String(v ?? '').trim().toUpperCase();
      if (!str || str === 'N/A') return str;
      const num = parseFloat(str);
      return !isNaN(num) ? num.toFixed(2) : str;
    };

    const normalizeText = (v: any) => String(v ?? '').replace(/\r\n/g, '\n').trim();

    const hasChanges =
      normalizeText(updated.actualAccomplishment) !== normalizeText(lastSaved.actualAccomplishment) ||
      normalizeText(updated.movs) !== normalizeText(lastSaved.movs) ||
      normalizeText(updated.remarks) !== normalizeText(lastSaved.remarks) ||
      normalizeScore(updated.actEfficiency) !== normalizeScore(lastSaved.actEfficiency) ||
      normalizeScore(updated.actQuality) !== normalizeScore(lastSaved.actQuality) ||
      normalizeScore(updated.actTimeliness) !== normalizeScore(lastSaved.actTimeliness);

    if (!hasChanges) {
      return;
    }

    saveLocalDraft(itemId, updated);
    lastSavedValuesRef.current[itemId] = { ...updated };

    router.patch(
      `/ipcrf/myratings/${rating.id}/accomplishment/${itemId}`,
      updated,
      {
        preserveScroll: true,
        preserveState: true,
        showProgress: false,
        onSuccess: (page) => {
          const flash = (page.props as any)?.flash || {};
          const msg = flash.success || (page.props as any)?.['flash.success'] || 'Accomplishment and ratings updated.';
          toast({ text: msg, variant: 'success' });
        },
        onError: (errors) => {
          const firstErr = Object.values(errors)[0] as string;
          if (firstErr) {
            toast({ text: firstErr, variant: 'danger' });
          }
        },
      }
    );
  };

  const computeItemAverage = (eff: any, qual: any, time: any) => {
    const parse = (v: any) => {
      if (!v || String(v).trim().toUpperCase() === 'N/A') return null;
      const n = parseFloat(String(v));
      return !isNaN(n) && n >= 1 && n <= 5 ? n : null;
    };
    const s = [parse(eff), parse(qual), parse(time)].filter((n) => n !== null) as number[];
    if (s.length === 0) {
      const isAllNa = [eff, qual, time].some((v) => String(v || '').trim().toUpperCase() === 'N/A');
      return isAllNa ? 'N/A' : '-';
    }
    return (s.reduce((a, b) => a + b, 0) / s.length).toFixed(2);
  };

  const focusNextInput = (el: HTMLElement) => {
    setTimeout(() => {
      const focusables = Array.from(
        document.querySelectorAll<HTMLElement>(
          'input:not([type="hidden"]):not([disabled]):not([tabindex="-1"]), textarea:not([disabled]):not([tabindex="-1"]), select:not([disabled]):not([tabindex="-1"]), button:not([disabled]):not([tabindex="-1"])'
        )
      );
      const idx = focusables.indexOf(el);
      if (idx > -1 && idx + 1 < focusables.length) {
        const next = focusables[idx + 1];
        next.focus();
        if ('select' in next && typeof (next as any).select === 'function') {
          (next as any).select();
        }
      }
    }, 10);
  };

  const handleScoreKeyDown = (
    e: React.KeyboardEvent<HTMLInputElement>,
    itemId: number,
    field: 'actEfficiency' | 'actQuality' | 'actTimeliness'
  ) => {
    if (e.key === 'n' || e.key === 'N') {
      e.preventDefault();
      handleItemFieldChange(itemId, field, 'N/A');
      saveField(itemId, field, 'N/A');
      focusNextInput(e.currentTarget);
    } else if (e.key === 'ArrowDown') {
      e.preventDefault();
      const current = itemValues[itemId]?.[field] ?? '';
      if (current.toUpperCase() === 'N/A') return;
      const num = parseFloat(current);
      if (!isNaN(num) && num > 1) {
        const updated = Math.max(1, Math.floor(num) - 1).toString();
        handleItemFieldChange(itemId, field, updated);
        scheduleSave(itemId, field, updated);
      } else if (!isNaN(num) && num <= 1) {
        // Already at minimum — do nothing
      } else {
        // Empty or invalid — start at 1
        handleItemFieldChange(itemId, field, '1');
        scheduleSave(itemId, field, '1');
      }
    } else if (e.key === 'ArrowUp') {
      e.preventDefault();
      const current = itemValues[itemId]?.[field] ?? '';
      if (current.toUpperCase() === 'N/A' || !current) {
        handleItemFieldChange(itemId, field, '1');
        scheduleSave(itemId, field, '1');
      } else {
        const num = parseFloat(current);
        if (isNaN(num)) {
          handleItemFieldChange(itemId, field, '1');
          scheduleSave(itemId, field, '1');
        } else if (num < 5) {
          const updated = Math.min(5, Math.ceil(num) + (Number.isInteger(num) ? 1 : 0)).toString();
          handleItemFieldChange(itemId, field, updated);
          scheduleSave(itemId, field, updated);
        }
        // At 5 — do nothing
      }
    }
  };

  const handleScoreInput = (
    e: React.ChangeEvent<HTMLInputElement>,
    itemId: number,
    field: 'actEfficiency' | 'actQuality' | 'actTimeliness'
  ) => {
    const raw = e.target.value;
    // Empty — clear
    if (raw === '') {
      handleItemFieldChange(itemId, field, '');
      return;
    }
    const upper = raw.trim().toUpperCase();
    // N/A shorthand
    if (upper === 'N' || upper === 'NA' || upper === 'N/' || upper === 'N/A') {
      handleItemFieldChange(itemId, field, 'N/A');
      if (upper === 'N/A') {
        saveField(itemId, field, 'N/A');
        focusNextInput(e.target);
      }
      return;
    }
    // Allow only digits and a single decimal point while typing
    let cleaned = raw.replace(/[^0-9.]/g, '');
    // Only one decimal point
    const dotIndex = cleaned.indexOf('.');
    if (dotIndex !== -1) {
      cleaned = cleaned.slice(0, dotIndex + 1) + cleaned.slice(dotIndex + 1).replace(/\./g, '');
    }
    // Cap first digit: can't start above 5
    if (cleaned.length === 1 && cleaned !== '' && parseFloat(cleaned) > 5) {
      cleaned = '5';
    }
    // If a complete number, clamp to [1, 5]
    const num = parseFloat(cleaned);
    if (!isNaN(num) && cleaned !== '' && !cleaned.endsWith('.')) {
      if (num > 5) {
        cleaned = '5';
      } else if (num < 1 && num > 0) {
        // Allow typing like "1." but block saving < 1 — validation on blur
        // Keep it for now to allow decimal typing (e.g., "1.2")
      }
    }
    handleItemFieldChange(itemId, field, cleaned);
    scheduleSave(itemId, field, cleaned);
  };

  const handleScoreBlur = (
    e: React.FocusEvent<HTMLInputElement>,
    itemId: number,
    field: 'actEfficiency' | 'actQuality' | 'actTimeliness'
  ) => {
    const raw = e.target.value.trim();
    if (raw === '' || raw.toUpperCase() === 'N/A') {
      saveField(itemId, field, raw);
      return;
    }
    const num = parseFloat(raw);
    if (isNaN(num) || num < 1) {
      // Invalid — clear the field
      handleItemFieldChange(itemId, field, '');
      saveField(itemId, field, '');
    } else if (num > 5) {
      handleItemFieldChange(itemId, field, '5');
      saveField(itemId, field, '5');
    } else {
      saveField(itemId, field, raw);
    }
  };

  const toggleExpanded = (itemId: number, field: string) => {
    const key = `${itemId}_${field}`;
    setExpandedItems((prev) => ({
      ...prev,
      [key]: !prev[key],
    }));
  };

  const getCsrfToken = () => {
    const metaToken = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content');
    if (metaToken) return metaToken;
    const match = document.cookie.match(/XSRF-TOKEN=([^;]+)/);
    if (match && match[1]) {
      return decodeURIComponent(match[1]);
    }
    return '';
  };

  const handleOpenAttachmentModal = async (itemId: number) => {
    setAttachmentItemId(itemId);
    setShowAttachmentModal(true);
    setQueuedFiles([]);
    setActiveViewerIndex(-1);
    setIsLoadingAttachments(true);
    try {
      const res = await fetch(`/ipcrf/myratings/${rating.id}/attachments/${itemId}`, {
        headers: {
          'Accept': 'application/json',
          'X-Requested-With': 'XMLHttpRequest',
        },
      });
      if (res.ok) {
        const data = await res.json();
        const attachments = Array.isArray(data) ? data : (data.attachments || []);
        setExistingAttachments(attachments);
        setItemAttachmentCounts((prev) => ({ ...prev, [itemId]: attachments.length }));
      }
    } catch (err) {
      console.error(err);
    } finally {
      setIsLoadingAttachments(false);
    }
  };

  const handleQueueSelectedFiles = (e: React.ChangeEvent<HTMLInputElement>) => {
    const files = Array.from(e.target.files || []);
    if (!files.length) return;
    const mapped = files.map((file) => ({
      file,
      name: file.name,
      url: file.type === 'application/pdf' ? '' : URL.createObjectURL(file),
      type: (file.type === 'application/pdf' ? 'pdf' : 'image') as 'pdf' | 'image',
      size: `${(file.size / 1024 / 1024).toFixed(2)} MB`,
    }));
    setQueuedFiles((prev) => [...prev, ...mapped]);
    e.target.value = '';
  };

  const handleRemoveQueuedFile = (index: number) => {
    setQueuedFiles((prev) => {
      const removed = prev[index];
      if (removed?.url) {
        URL.revokeObjectURL(removed.url);
      }
      return prev.filter((_, idx) => idx !== index);
    });
  };

  const handleUploadFiles = async () => {
    if (!attachmentItemId || queuedFiles.length === 0) return;
    setIsUploadingAttachments(true);
    try {
      const formData = new FormData();
      queuedFiles.forEach((q) => {
        formData.append('files[]', q.file);
      });
      const csrf = getCsrfToken();
      if (csrf) {
        formData.append('_token', csrf);
      }
      const headers: Record<string, string> = {
        'Accept': 'application/json',
        'X-Requested-With': 'XMLHttpRequest',
      };
      if (csrf) {
        headers['X-CSRF-TOKEN'] = csrf;
        headers['X-XSRF-TOKEN'] = csrf;
      }

      const res = await fetch(`/ipcrf/myratings/${rating.id}/attachments/${attachmentItemId}`, {
        method: 'POST',
        headers,
        body: formData,
      });
      if (res.ok) {
        const data = await res.json();
        const attachments = Array.isArray(data) ? data : (data.attachments || []);
        setExistingAttachments(attachments);
        setItemAttachmentCounts((prev) => ({ ...prev, [attachmentItemId]: attachments.length }));
        setIndicatorsList((prev) =>
          prev.map((group) => ({
            ...group,
            items: group.items.map((it) =>
              it.itemId === attachmentItemId
                ? { ...it, attachmentCount: attachments.length, hasAttachments: attachments.length > 0 ? 1 : null }
                : it
            ),
          }))
        );
        queuedFiles.forEach((q) => {
          if (q.url) URL.revokeObjectURL(q.url);
        });
        setQueuedFiles([]);
      } else {
        const text = await res.text();
        let errData: any = {};
        try {
          errData = JSON.parse(text);
        } catch {
          errData = {};
        }
        let errMsg = 'Unable to upload attachments.';
        if (errData.error) {
          errMsg = errData.error;
        } else if (errData.errors) {
          errMsg = Object.values(errData.errors).flat().join('\n');
        } else if (errData.message) {
          errMsg = errData.message;
        } else if (res.status === 419) {
          errMsg = 'Session or CSRF token expired. Please refresh the page and try again.';
        } else if (res.status === 413) {
          errMsg = 'The uploaded file is too large for the server. Please ensure files are under 10MB.';
        } else if (res.status === 403) {
          errMsg = 'You do not have permission to upload attachments for this target.';
        } else if (text && text.length < 200) {
          errMsg = text;
        }
        alert(errMsg);
      }
    } catch (err) {
      console.error(err);
      alert('Upload failed. Please try again.');
    } finally {
      setIsUploadingAttachments(false);
    }
  };

  const handleDeleteAttachment = async (filename: string) => {
    if (!attachmentItemId) return;
    setConfirmModal({
      isOpen: true,
      title: 'Delete Attachment',
      message: `Are you sure you want to delete this attachment (${filename})? This action cannot be undone.`,
      confirmText: 'Delete Attachment',
      variant: 'danger',
      icon: 'trash',
      onConfirm: async () => {
        try {
          const csrf = getCsrfToken();
          const headers: Record<string, string> = {
            'Content-Type': 'application/json',
            'Accept': 'application/json',
            'X-Requested-With': 'XMLHttpRequest',
          };
          if (csrf) {
            headers['X-CSRF-TOKEN'] = csrf;
            headers['X-XSRF-TOKEN'] = csrf;
          }

          const res = await fetch(`/ipcrf/myratings/${rating.id}/attachments/${attachmentItemId}/delete`, {
            method: 'POST',
            headers,
            body: JSON.stringify({ filename }),
          });
          if (res.ok) {
            const data = await res.json();
            const attachments = Array.isArray(data) ? data : (data.attachments || []);
            setExistingAttachments(attachments);
            setItemAttachmentCounts((prev) => ({ ...prev, [attachmentItemId]: attachments.length }));
            setIndicatorsList((prev) =>
              prev.map((group) => ({
                ...group,
                items: group.items.map((it) =>
                  it.itemId === attachmentItemId
                    ? { ...it, attachmentCount: attachments.length, hasAttachments: attachments.length > 0 ? 1 : null }
                    : it
                ),
              }))
            );
            if (activeViewerIndex >= attachments.length) {
              setActiveViewerIndex(attachments.length - 1);
            }
          }
        } catch (err) {
          console.error(err);
        }
      },
    });
  };

  const handleOpenStaffMovModal = async (itemId: number) => {
    setStaffMovItemId(itemId);
    setShowStaffMovModal(true);
    setSelectedStaffUserId('');
    setStaffMovSearch('');
    setIsLoadingStaffMovs(true);
    try {
      const res = await fetch(`/ipcrf/myratings/${rating.id}/staff-movs/${itemId}`, {
        headers: {
          'Accept': 'application/json',
          'X-Requested-With': 'XMLHttpRequest',
        },
      });
      if (res.ok) {
        const data = await res.json();
        setStaffMovUsers(data.users || []);
        setStaffMovSources(data.sources || []);
        setStaffMovContextYear(data.contextYear || '');
        setStaffMovContextSemester(data.contextSemester || '');
      }
    } catch (err) {
      console.error(err);
    } finally {
      setIsLoadingStaffMovs(false);
    }
  };

  const handleFilterStaffMovs = async (userId: string, search: string) => {
    if (!staffMovItemId) return;
    setSelectedStaffUserId(userId);
    setStaffMovSearch(search);
    setIsLoadingStaffMovs(true);
    try {
      const query = new URLSearchParams();
      if (userId) query.set('staffUserId', userId);
      if (search) query.set('search', search);
      const res = await fetch(`/ipcrf/myratings/${rating.id}/staff-movs/${staffMovItemId}?${query.toString()}`, {
        headers: {
          'Accept': 'application/json',
          'X-Requested-With': 'XMLHttpRequest',
        },
      });
      if (res.ok) {
        const data = await res.json();
        setStaffMovSources(data.sources || []);
      }
    } catch (err) {
      console.error(err);
    } finally {
      setIsLoadingStaffMovs(false);
    }
  };

  const handleCopyStaffMovs = async (sourceItemId: number) => {
    if (!staffMovItemId) return;
    setIsCopyingStaffMovs(true);
    try {
      const csrf = getCsrfToken();
      const headers: Record<string, string> = {
        'Content-Type': 'application/json',
        'Accept': 'application/json',
        'X-Requested-With': 'XMLHttpRequest',
      };
      if (csrf) {
        headers['X-CSRF-TOKEN'] = csrf;
        headers['X-XSRF-TOKEN'] = csrf;
      }

      const res = await fetch(`/ipcrf/myratings/${rating.id}/copy-staff-movs/${staffMovItemId}`, {
        method: 'POST',
        headers,
        body: JSON.stringify({ sourceItemId }),
      });
      if (res.ok) {
        const data = await res.json();
        const attachments = Array.isArray(data) ? data : (data.attachments || []);
        setExistingAttachments(attachments);
        setItemAttachmentCounts((prev) => ({ ...prev, [staffMovItemId]: attachments.length }));
        setIndicatorsList((prev) =>
          prev.map((group) => ({
            ...group,
            items: group.items.map((it) =>
              it.itemId === staffMovItemId
                ? { ...it, attachmentCount: attachments.length, hasAttachments: attachments.length > 0 ? 1 : null }
                : it
            ),
          }))
        );
        setShowStaffMovModal(false);
      }
    } catch (err) {
      console.error(err);
    } finally {
      setIsCopyingStaffMovs(false);
    }
  };

  const renderTargetTextWithShowMore = (
    text: string,
    itemId: number,
    field: 'efficiency' | 'quality' | 'timeliness'
  ) => {
    const formatted = formatTextValue(text, '');
    const clean = formatted.replace(/<[^>]*>?/gm, '').trim();
    if (!clean && !text) return null;

    const isExpanded = Boolean(expandedItems[`${itemId}_${field}`]);

    return (
      <div className="mt-1.5 w-full rounded-lg border border-border/80 bg-muted/30 overflow-hidden shadow-2xs transition-all">
        <button
          type="button"
          tabIndex={-1}
          onClick={() => toggleExpanded(itemId, field)}
          className="flex items-center justify-between gap-1.5 px-2.5 py-1.5 w-full text-[10px] font-semibold text-muted-foreground hover:text-foreground hover:bg-muted/50 transition cursor-pointer"
        >
          <div className="flex items-center gap-1.5 whitespace-nowrap shrink-0">
            <BookOpen className="w-3 h-3 text-emerald-600 dark:text-emerald-400 opacity-90 shrink-0" />
            <span className="whitespace-nowrap font-medium text-foreground">{isExpanded ? 'Hide rating guide' : 'Rating Guide'}</span>
          </div>
          {isExpanded ? (
            <ChevronUp className="w-3 h-3 text-emerald-600 dark:text-emerald-400 shrink-0" />
          ) : (
            <ChevronDown className="w-3 h-3 text-muted-foreground shrink-0" />
          )}
        </button>

        {isExpanded && (
          <div className="border-t border-border/60 p-2.5 text-[11px] leading-relaxed text-foreground/90 bg-background/50">
            <FormattedText value={text || '-'} />
          </div>
        )}
      </div>
    );
  };

  useEffect(() => {
    const handleOutsideClick = (e: MouseEvent) => {
      if (contextMenuRef.current && contextMenuRef.current.contains(e.target as Node)) {
        return;
      }
      setContextMenu(null);
      setActiveSubMenu(null);

      if (printDropdownRef.current && !printDropdownRef.current.contains(e.target as Node)) {
        setShowPrintDropdown(false);
      }
      if (optionsDropdownRef.current && !optionsDropdownRef.current.contains(e.target as Node)) {
        setShowOptionsDropdown(false);
      }
    };

    window.addEventListener('mousedown', handleOutsideClick);
    return () => window.removeEventListener('mousedown', handleOutsideClick);
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
    if (isReadOnly) return;
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

  const handleDragStart = (indicatorId: number) => {
    setDraggedIndicatorId(indicatorId);
  };

  const handleDragOver = (e: React.DragEvent) => {
    e.preventDefault();
  };

  const handleDrop = (targetIndicatorId: number) => {
    if (!draggedIndicatorId || draggedIndicatorId === targetIndicatorId) return;

    const fromIdx = indicatorsList.findIndex((g) => g.indicatorId === draggedIndicatorId);
    const toIdx = indicatorsList.findIndex((g) => g.indicatorId === targetIndicatorId);

    if (fromIdx === -1 || toIdx === -1 || fromIdx === toIdx) return;

    const updated = [...indicatorsList];
    const [moved] = updated.splice(fromIdx, 1);
    updated.splice(toIdx, 0, moved);

    setIndicatorsList(updated);
    setDraggedIndicatorId(null);

    const orderPayload = updated.map((item, idx) => ({
      indicatorId: item.indicatorId,
      displayOrder: idx + 1,
    }));

    router.post(
      `/ipcrf/myratings/${rating.id}/target/reorder`,
      { order: orderPayload },
      { preserveScroll: true }
    );
  };

  const handleMoveUp = (indicatorId: number) => {
    const idx = indicatorsList.findIndex((g) => g.indicatorId === indicatorId);
    if (idx <= 0) return;
    const targetGroup = indicatorsList[idx - 1];
    if (targetGroup) {
      setDraggedIndicatorId(indicatorId);
      handleDrop(targetGroup.indicatorId);
    }
  };

  const handleMoveDown = (indicatorId: number) => {
    const idx = indicatorsList.findIndex((g) => g.indicatorId === indicatorId);
    if (idx === -1 || idx >= indicatorsList.length - 1) return;
    const targetGroup = indicatorsList[idx + 1];
    if (targetGroup) {
      setDraggedIndicatorId(indicatorId);
      handleDrop(targetGroup.indicatorId);
    }
  };

  const handleLockSemestralTarget = () => {
    setShowLockModal(true);
  };

  const handleUnlockSemestralTarget = () => {
    setShowUnlockModal(true);
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
    editForm.patch(`/ipcrf/myratings/${rating.id}/accomplishment/${editingItem.itemId}`, {
      onSuccess: () => setEditingItem(null),
    });
  };

  const handleAddSubmit = (e: React.FormEvent) => {
    e.preventDefault();
    addForm.post(`/ipcrf/myratings/${rating.id}/target`, {
      onSuccess: () => {
        setShowAddModal(false);
        addForm.reset();
      },
    });
  };

  const handleAreaSubmit = (e: React.FormEvent) => {
    e.preventDefault();
    areaForm.post(`/ipcrf/myratings/${rating.id}/areas-improvement`, {
      onSuccess: () => {
        setShowAreaModal(false);
        areaForm.reset();
      },
    });
  };

  const handleDocUploadFiles = (files: FileList | File[]) => {
    if (!files || files.length === 0) return;
    const fileArray = Array.from(files);
    if (fileArray.length === 0) return;

    const form = new FormData();
    fileArray.forEach((file) => {
      form.append('files[]', file);
    });

    setIsUploadingDoc(true);
    setDocUploadProgress(0);

    router.post(`/ipcrf/myratings/${rating.id}/documentation`, form, {
      preserveScroll: true,
      onProgress: (progress) => {
        if (progress?.percentage) {
          setDocUploadProgress(progress.percentage);
        }
      },
      onSuccess: () => {
        setIsUploadingDoc(false);
        setDocUploadProgress(100);
        setTimeout(() => setDocUploadProgress(0), 700);
        if (docFileInputRef.current) {
          docFileInputRef.current.value = '';
        }
        toast({
          title: 'Success',
          message: 'Documentation files uploaded successfully.',
          variant: 'success',
        });
      },
      onError: (errs) => {
        setIsUploadingDoc(false);
        setDocUploadProgress(0);
        const errorMsg = Object.values(errs)[0] || 'Failed to upload documentation files.';
        toast({
          title: 'Upload Failed',
          message: String(errorMsg),
          variant: 'error',
        });
      },
      onFinish: () => {
        setIsUploadingDoc(false);
      },
    });
  };

  const handleDocDelete = (fileName: string) => {
    setConfirmModal({
      isOpen: true,
      title: 'Delete Documentation File',
      message: `Delete this documentation file from storage?`,
      confirmText: 'Remove',
      variant: 'danger',
      icon: 'trash',
      onConfirm: () => {
        router.delete(`/ipcrf/myratings/${rating.id}/documentation`, {
          data: { fileName },
          preserveScroll: true,
          onSuccess: () => {
            setPreviewFile(null);
            toast({
              title: 'Success',
              message: 'Documentation file deleted successfully.',
              variant: 'success',
            });
          },
          onError: (errs) => {
            const errorMsg = Object.values(errs)[0] || 'Failed to delete documentation file.';
            toast({
              title: 'Delete Failed',
              message: String(errorMsg),
              variant: 'error',
            });
          },
        });
      },
    });
  };

  const handleCopyStaffMov = (sourceItemId: number) => {
    if (!movTargetItemId) return;
    router.post(
      `/ipcrf/myratings/${rating.id}/copy-movs`,
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

  const scoresHeader = (
    <div className="hidden lg:flex items-center gap-1.5 text-xs">
      <div className="flex items-center gap-1.5 rounded-lg border border-border bg-muted/40 px-2.5 py-1 text-xs">
        {includeStrategicFunction && (
          <>
            <span className="text-muted-foreground font-medium text-[10px]">Strategic Score:</span>
            <span className="font-bold text-foreground font-mono text-[11px]">{functionScores?.strategicScore || '0.00000'}</span>
            <span className="text-border mx-0.5">•</span>
          </>
        )}
        <span className="text-muted-foreground font-medium text-[10px]">Core Score:</span>
        <span className="font-bold text-foreground font-mono text-[11px]">{functionScores?.coreScore || '0.00000'}</span>
        <span className="text-border mx-0.5">•</span>
        <span className="text-muted-foreground font-medium text-[10px]">Support Score:</span>
        <span className="font-bold text-foreground font-mono text-[11px]">{functionScores?.supportScore || '0.00000'}</span>
      </div>

      <div className="flex items-center gap-1.5 rounded-lg border border-border bg-muted/40 px-2.5 py-1 text-xs">
        <span className="text-muted-foreground font-medium text-[10px]">Final Rating:</span>
        <span className="font-bold text-foreground font-mono text-[11px]">{functionScores?.finalScore || rating.finalRating}</span>
        <span className="text-border mx-0.5">•</span>
        <span className="text-muted-foreground font-medium text-[10px]">Adjectival:</span>
        <span className="font-bold text-emerald-600 dark:text-emerald-400 text-[11px]">{functionScores?.adjectival || rating.adjectivalRating}</span>
      </div>
    </div>
  );

  return (
    <AppLayout appName={appName} user={user} sidebar={navigation?.sidebar ?? []} headerExtra={scoresHeader}>
      <Head title={`Semestral Target - ${rating.year}`} />

      <div className="space-y-3">
        {/* TOP FILTER & ACTION CARD */}
        <div className="rounded-xl border border-border bg-card p-3 sm:p-4 shadow-2xs space-y-3">
          {/* HEADER BAR */}
          <div className="flex flex-col sm:flex-row sm:items-center justify-between gap-2 border-b border-border/80 pb-3">
            <div className="flex items-center gap-2.5">
              <div className="size-8 rounded-lg bg-emerald-500/10 text-emerald-700 dark:text-emerald-400 flex items-center justify-center font-bold">
                <ShieldCheck className="size-4.5" />
              </div>
              <div>
                <h1 className="text-sm font-bold tracking-tight text-foreground flex items-center gap-2">
                  <span>Individual Performance Commitment &amp; Review</span>
                  <span className="rounded-full bg-emerald-500/10 text-emerald-700 dark:text-emerald-400 font-mono text-[10px] font-bold px-2 py-0.2 border border-emerald-500/20">
                    {rating.year} • {rating.semester === 1 ? '1st Semester' : '2nd Semester'}
                  </span>
                </h1>
                <p className="text-[11px] text-muted-foreground">
                  Manage semestral target commitments, accomplishments, ratings, documentation, and checkpoint changes.
                </p>
              </div>
            </div>

            <div className="flex items-center gap-2">
              {/* Status Action Buttons (Save & Lock Target, etc.) */}
              {isVerified ? (
                <span className="h-8 inline-flex items-center gap-1.5 rounded-lg bg-emerald-600 px-3 text-xs font-semibold text-white shadow-2xs">
                  <CheckCircle2 className="size-3.5" />
                  <span>Verified by Supervisor</span>
                </span>
              ) : rating.lock === 2 ? (
                <button
                  type="button"
                  id="waiting-verification-btn"
                  onClick={() => setShowCancelReadyModal(true)}
                  className="h-8 inline-flex items-center gap-1.5 rounded-lg bg-amber-600 hover:bg-amber-700 px-3 text-xs font-semibold text-white transition cursor-pointer shadow-2xs"
                  title="Click to cancel ready submission and re-enable editing"
                >
                  <Clock className="size-3.5" />
                  <span>Waiting for Verification</span>
                </button>
              ) : isLocked ? (
                isEveryTargetComplete ? (
                  <button
                    type="button"
                    id="im-ready-btn"
                    onClick={() => setShowImReadyModal(true)}
                    className="h-8 inline-flex items-center gap-1.5 rounded-lg bg-emerald-600 hover:bg-emerald-700 text-white px-3 text-xs font-semibold shadow-2xs transition cursor-pointer"
                  >
                    <CheckCircle2 className="size-3.5" />
                    <span>I'm Ready</span>
                  </button>
                ) : null
              ) : (
                <button
                  type="button"
                  onClick={handleLockSemestralTarget}
                  className="h-8 inline-flex items-center gap-1.5 rounded-lg bg-emerald-600 hover:bg-emerald-700 text-white px-3 text-xs font-semibold shadow-2xs transition cursor-pointer"
                >
                  <Lock className="size-3.5" />
                  <span>Save &amp; Lock Target</span>
                </button>
              )}

              {/* Options Dropdown */}
              <div ref={optionsDropdownRef} className="relative">
                <button
                  type="button"
                  onClick={() => setShowOptionsDropdown(!showOptionsDropdown)}
                  className="h-8 inline-flex items-center gap-1.5 rounded-lg border border-input bg-background px-2.5 text-xs font-medium text-foreground hover:bg-muted transition cursor-pointer shadow-2xs"
                >
                  <SlidersHorizontal className="size-3.5 text-muted-foreground" />
                  <span>Options</span>
                  <ChevronDown className="size-3" />
                </button>

                {showOptionsDropdown && (
                  <div className="absolute right-0 mt-2 w-56 rounded-xl border border-border bg-card p-1 shadow-xl z-30 text-xs animate-in fade-in-50 zoom-in-95">
                    {!isLocked && (
                      <button
                        type="button"
                        onClick={() => {
                          setShowOptionsDropdown(false);
                          setActiveTab('deleted');
                        }}
                        className="w-full text-left px-3 py-2 rounded-lg hover:bg-muted text-foreground flex items-center gap-2 cursor-pointer"
                      >
                        <RotateCcw className="size-3.5 text-muted-foreground" />
                        <span>Recover Deleted Targets</span>
                      </button>
                    )}
                    {isLocked && rating.lock !== 2 && (
                      <button
                        type="button"
                        onClick={() => {
                          setShowOptionsDropdown(false);
                          handleUnlockSemestralTarget();
                        }}
                        className="w-full text-left px-3 py-2 rounded-lg hover:bg-muted text-foreground flex items-center gap-2 cursor-pointer"
                      >
                        <LockOpen className="size-3.5 text-amber-600 dark:text-amber-400" />
                        <span>Unlock Semestral Target</span>
                      </button>
                    )}
                  </div>
                )}
              </div>

              {/* Print Dropdown */}
              <div ref={printDropdownRef} className="relative">
                <button
                  type="button"
                  onClick={() => setShowPrintDropdown(!showPrintDropdown)}
                  className="h-8 inline-flex items-center gap-1.5 rounded-lg border border-input bg-background px-2.5 text-xs font-medium text-foreground hover:bg-muted transition cursor-pointer shadow-2xs"
                >
                  <Printer className="size-3.5 text-muted-foreground" />
                  <span>Print</span>
                  <ChevronDown className="size-3" />
                </button>

                {showPrintDropdown && (
                  <div className="absolute right-0 mt-2 w-44 rounded-xl border border-border bg-card p-1 shadow-xl z-30 text-xs animate-in fade-in-50 zoom-in-95">
                    <a
                      href={`/ipcrf/myratings/semestral-target/print-ipcrf?sem_id=${rating.id}`}
                      target="_blank"
                      rel="noreferrer"
                      onClick={() => setShowPrintDropdown(false)}
                      className="block px-3 py-2 rounded-lg text-foreground hover:bg-muted transition"
                    >
                      Print IPCR-F
                    </a>
                    <a
                      href={`/ipcrf/myratings/semestral-target/print-checkpoint?sem_id=${rating.id}`}
                      target="_blank"
                      rel="noreferrer"
                      onClick={() => setShowPrintDropdown(false)}
                      className="block px-3 py-2 rounded-lg text-foreground hover:bg-muted transition"
                    >
                      Print Checkpoint
                    </a>
                  </div>
                )}
              </div>

              <Link
                href="/ipcrf/myratings"
                className="h-8 inline-flex items-center gap-1.5 rounded-lg border border-input bg-background px-2.5 text-xs font-medium text-muted-foreground hover:text-foreground hover:bg-muted transition cursor-pointer"
              >
                <ArrowLeft className="size-3.5" />
                <span>Back to My Ratings</span>
              </Link>
            </div>
          </div>

          {/* USER PROFILE INFO STRIP */}
          <div className="rounded-lg border border-border bg-muted/20 p-2 sm:p-2.5">
            <div className="grid grid-cols-2 sm:grid-cols-3 lg:grid-cols-5 gap-2.5 sm:gap-3 text-xs items-start">
              <div className="min-w-0 col-span-2 sm:col-span-1">
                <div className="text-[9px] text-muted-foreground font-semibold uppercase tracking-wider">Full Name</div>
                <div className="mt-0.5 font-bold uppercase text-foreground text-[10.5px] sm:text-[11px] flex items-center gap-1.5 min-w-0">
                  <UserAvatar
                    user={{
                      name: userProfile?.fullName,
                      avatar_url: (userProfile as any)?.avatarUrl,
                      avatar: (userProfile as any)?.avatar,
                    }}
                    size="xs"
                  />
                  <span className="truncate leading-tight" title={userProfile?.fullName || '-'}>
                    {userProfile?.fullName || '-'}
                  </span>
                </div>
              </div>
              <div className="min-w-0">
                <div className="text-[9px] text-muted-foreground font-semibold uppercase tracking-wider">Position</div>
                <div className="mt-0.5 font-bold uppercase text-foreground text-[10px] sm:text-[10.5px] leading-tight break-words" title={userProfile?.position || '-'}>
                  {userProfile?.position || '-'}
                </div>
              </div>
              <div className="min-w-0">
                <div className="text-[9px] text-muted-foreground font-semibold uppercase tracking-wider">Designation</div>
                <div className="mt-0.5 font-bold uppercase text-foreground text-[10px] sm:text-[10.5px] leading-tight break-words" title={userProfile?.designation || '-'}>
                  {userProfile?.designation || '-'}
                </div>
              </div>
              <div className="min-w-0">
                <div className="text-[9px] text-muted-foreground font-semibold uppercase tracking-wider">Division Name</div>
                <div className="mt-0.5 font-bold uppercase text-foreground text-[10px] sm:text-[10.5px] leading-tight break-words" title={userProfile?.divisionName || '-'}>
                  {userProfile?.divisionName || '-'}
                </div>
              </div>
              <div className="min-w-0 col-span-2 sm:col-span-1">
                <div className="text-[9px] text-muted-foreground font-semibold uppercase tracking-wider">Section Name</div>
                <div className="mt-0.5 font-bold uppercase text-foreground text-[10px] sm:text-[10.5px] leading-tight break-words" title={userProfile?.sectionName || '-'}>
                  {userProfile?.sectionName || '-'}
                </div>
              </div>
            </div>
          </div>
        </div>

        {/* UNIFIED TABBED NAVIGATION CONTAINER */}
        <div className="rounded-xl border border-border bg-card shadow-2xs overflow-hidden">
          {/* TAB HEADER */}
          <div className="bg-muted/40 px-3 pt-2 pb-0 border-b border-border">
            <nav className="flex flex-wrap items-center gap-1.5" aria-label="Tabs">
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
                    className={`flex items-center gap-1.5 rounded-t-lg px-3.5 py-2 text-xs font-bold transition cursor-pointer border-t border-x ${isActive
                        ? 'bg-card text-emerald-600 dark:text-emerald-400 border-border -mb-[1px] shadow-2xs z-10'
                        : 'text-muted-foreground hover:text-foreground hover:bg-muted/60 border-transparent'
                      }`}
                  >
                    <Icon className="size-3.5" />
                    <span>{tab.label}</span>
                  </button>
                );
              })}
            </nav>
          </div>

          {/* TAB CONTENT BODY */}
          <div className="p-3 sm:p-4 min-h-[350px]">
            {/* Tab 1: Performance Indicator */}
            {activeTab === 'performance' && (
              <div className="space-y-3">
                {/* SEARCH AND FILTERS BAR */}
                <div className="grid gap-2.5 sm:grid-cols-2 lg:grid-cols-5 items-end pb-3 border-b border-border/80">
                  <div className="space-y-1 sm:col-span-2">
                    <label className="text-[11px] font-semibold text-muted-foreground">Search Targets</label>
                    <div className="relative">
                      <Search className="size-3.5 absolute left-2.5 top-1/2 -translate-y-1/2 text-muted-foreground" />
                      <input
                        type="text"
                        value={search}
                        onChange={(e) => setSearch(e.target.value)}
                        placeholder="Search semestral targets, descriptions, movs..."
                        className="h-8 w-full rounded-lg border border-input bg-background pl-8 pr-7 text-xs text-foreground outline-hidden focus:ring-2 focus:ring-ring"
                      />
                      {search ? (
                        <button
                          type="button"
                          onClick={() => setSearch('')}
                          className="absolute right-2 top-1/2 -translate-y-1/2 text-muted-foreground hover:text-foreground"
                          title="Clear search"
                        >
                          <X className="size-3.5" />
                        </button>
                      ) : null}
                    </div>
                  </div>

                  <div className="space-y-1">
                    <label className="text-[11px] font-semibold text-muted-foreground">KRA Category</label>
                    <select
                      value={categoryFilter}
                      onChange={(e) => setCategoryFilter(e.target.value)}
                      className="h-8 w-full rounded-lg border border-input bg-background px-2.5 text-xs text-foreground outline-hidden focus:ring-2 focus:ring-ring cursor-pointer"
                    >
                      <option value="">All categories</option>
                      {includeStrategicFunction && <option value="1">Strategic Function</option>}
                      <option value="2">Core Function</option>
                      <option value="3">Support Function</option>
                    </select>
                  </div>

                  <div className="space-y-1">
                    <label className="text-[11px] font-semibold text-muted-foreground">Target Status</label>
                    <select
                      value={targetStatusFilter}
                      onChange={(e) => setTargetStatusFilter(e.target.value)}
                      className="h-8 w-full rounded-lg border border-input bg-background px-2.5 text-xs text-foreground outline-hidden focus:ring-2 focus:ring-ring cursor-pointer"
                    >
                      <option value="">All targets</option>
                      <option value="checkpoint">Has Checkpoint Target</option>
                      {Number(rating.lock) >= 1 && (
                        <option value="incomplete">Incomplete Target</option>
                      )}
                    </select>
                  </div>

                  <div className="flex items-center gap-2">
                    <div className="flex-1 flex items-center gap-2">
                      <label className="text-[11px] font-semibold text-muted-foreground whitespace-nowrap">Per Page</label>
                      <select
                        value={perPage}
                        onChange={(e) => setPerPage(e.target.value)}
                        className="h-8 w-full rounded-lg border border-input bg-background px-2.5 text-xs text-foreground outline-hidden focus:ring-2 focus:ring-ring cursor-pointer"
                      >
                        <option value="5">5</option>
                        <option value="10">10</option>
                        <option value="15">15</option>
                        <option value="25">25</option>
                        <option value="50">50</option>
                        <option value="100">100</option>
                        <option value="all">All</option>
                      </select>
                    </div>

                    <button
                      type="button"
                      onClick={() => {
                        setSearch('');
                        setCategoryFilter('');
                        setTargetStatusFilter('');
                        setPerPage('10');
                        setCurrentPage(1);
                      }}
                      className="h-8 inline-flex items-center gap-1.5 rounded-lg border border-input bg-background px-2.5 text-xs font-medium text-muted-foreground hover:text-foreground hover:bg-muted transition cursor-pointer shrink-0"
                      title="Reset filters"
                    >
                      <RotateCcw className="size-3" />
                      <span>Reset</span>
                    </button>
                  </div>
                </div>

                {/* EXACT LIVEWIRE TARGETS TABLE FORMAT */}
                {paginatedIndicators.length === 0 ? (
                  <div className="rounded-xl border border-dashed border-border bg-muted/20 p-8 text-center text-xs text-muted-foreground">
                    No semestral targets found matching your filter criteria.
                  </div>
                ) : (
                  <div className="space-y-4">
                    <div className="overflow-hidden rounded-xl border border-border bg-card shadow-2xs">
                      <table className="w-full table-fixed text-left text-xs border-collapse">
                        {isLocked ? (
                          <colgroup>
                            <col style={{ width: '4%' }} />
                            <col style={{ width: '13%' }} />
                            <col style={{ width: '14%' }} />
                            <col style={{ width: '15%' }} />
                            <col style={{ width: '9%' }} />
                            <col style={{ width: '9%' }} />
                            <col style={{ width: '9%' }} />
                            <col style={{ width: '5%' }} />
                            <col style={{ width: '11%' }} />
                            <col style={{ width: '11%' }} />
                          </colgroup>
                        ) : (
                          <colgroup>
                            <col style={{ width: '4%' }} />
                            <col style={{ width: '16%' }} />
                            <col style={{ width: '17%' }} />
                            <col style={{ width: '13%' }} />
                            <col style={{ width: '13%' }} />
                            <col style={{ width: '13%' }} />
                            <col style={{ width: '12%' }} />
                            <col style={{ width: '12%' }} />
                          </colgroup>
                        )}

                        <thead className="bg-muted/60 text-[11px] font-bold uppercase tracking-wider text-muted-foreground border-b border-border">
                          <tr>
                            <th className="px-3 py-3 text-center border-r border-border whitespace-nowrap">Action</th>
                            <th className="px-3 py-3 border-r border-border whitespace-nowrap">Key Result Area</th>
                            <th className="px-3 py-3 border-r border-border whitespace-nowrap">Success Indicator</th>
                            {isLocked && <th className="px-3 py-3 border-r border-border whitespace-nowrap">Actual Accomplishments</th>}
                            <th className="px-3 py-3 border-r border-border whitespace-nowrap">EFFICIENCY</th>
                            <th className="px-3 py-3 border-r border-border whitespace-nowrap">QUALITY</th>
                            <th className="px-3 py-3 border-r border-border whitespace-nowrap">TIMELINESS</th>
                            {isLocked && <th className="px-3 py-3 text-center border-r border-border whitespace-nowrap">AVE</th>}
                            <th className="px-3 py-3 border-r border-border whitespace-nowrap">MOVS</th>
                            <th className="px-3 py-3 whitespace-nowrap">REMARKS</th>
                          </tr>
                        </thead>

                        {categoriesList.map((catId) => {
                          const catGroups = paginatedIndicators.filter((g) => g.kraCategory === catId);
                          if (catGroups.length === 0) return null;

                          return (
                            <tbody key={catId} className="divide-y divide-border">
                              <tr className="bg-muted/80 border-b border-border font-bold">
                                <td colSpan={isLocked ? 10 : 8} className="px-3 py-2">
                                  <span className="text-xs uppercase tracking-wide text-emerald-700 dark:text-emerald-400 font-bold">
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
                                          onDrop={() => isFirstRow && !isEditingThisGroup && handleDrop(group.indicatorId)}
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
                                          className={`hover:bg-muted/30 transition-colors align-top border-b border-border ${isEditingThisGroup
                                            ? 'bg-sky-50/80 dark:bg-sky-950/40'
                                            : contextMenu && contextMenu.itemId === item.itemId
                                              ? 'bg-sky-50 dark:bg-sky-950/50'
                                              : ''
                                            }`}
                                        >
                                          {isFirstRow && (
                                            <td
                                              rowSpan={totalSubRows}
                                              className="px-2 py-3 text-center border-r border-border align-top h-1"
                                            >
                                              <div className="flex h-full min-h-[120px] flex-col justify-between items-center">
                                                <div className="flex flex-col items-center gap-2">
                                                  {isEditingThisGroup ? (
                                                    <div className="flex flex-col items-center gap-1.5">
                                                      <button
                                                        type="button"
                                                        onClick={handleRequestSaveEditGroup}
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
                                                      <div
                                                        draggable={isFirstRow && !isEditingThisGroup}
                                                        onDragStart={() => isFirstRow && !isEditingThisGroup && handleDragStart(group.indicatorId)}
                                                        className="cursor-grab active:cursor-grabbing text-muted-foreground hover:text-foreground p-1"
                                                        title="Drag to reorder"
                                                      >
                                                        <GripVertical className="w-4 h-4" />
                                                      </div>
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
                                              </div>
                                            </td>
                                          )}

                                          {isFirstRow && (
                                            <td
                                              rowSpan={totalSubRows}
                                              className="px-3 py-3 border-r border-border text-xs font-normal text-foreground align-top h-1"
                                            >
                                              <div className="flex h-full min-h-[120px] flex-col justify-between">
                                                <div>
                                                  {isEditingThisGroup ? (
                                                    <div className="space-y-2">
                                                      <AutoResizingTextarea
                                                        rows={2}
                                                        value={editingGroup.activity}
                                                        onChange={(e) =>
                                                          setEditingGroup({ ...editingGroup, activity: e.target.value })
                                                        }
                                                        className="w-full rounded-lg border border-input bg-background p-2 text-xs text-foreground focus:outline-none focus:ring-2 focus:ring-ring shadow-inner"
                                                      />
                                                      <select
                                                        value={editingGroup.kraCategory}
                                                        onChange={(e) =>
                                                          setEditingGroup({
                                                            ...editingGroup,
                                                            kraCategory: Number(e.target.value),
                                                          })
                                                        }
                                                        className="w-full rounded-lg border border-input bg-background p-1.5 text-xs text-foreground focus:outline-none focus:ring-2 focus:ring-ring"
                                                      >
                                                        {includeStrategicFunction && (
                                                          <option value={1}>Strategic Function</option>
                                                        )}
                                                        <option value={2}>Core Function</option>
                                                        <option value={3}>Support Function</option>
                                                      </select>
                                                    </div>
                                                  ) : (
                                                    <FormattedText value={(group.activity || '').replace(/<\/?(strong|b)\b[^>]*>/gi, '')} />
                                                  )}
                                                </div>

                                                {/* Target ID at the bottom of the Key Result Area column */}
                                                <div className="pt-2 mt-auto text-center">
                                                  <span className="text-[10px] font-semibold italic text-muted-foreground select-none">
                                                    <strong><em>{group.indicatorId}</em></strong>
                                                  </span>
                                                </div>
                                              </div>
                                            </td>
                                          )}

                                          {/* Success Indicator / Description */}
                                          <td className="px-3 py-3 border-r border-border text-foreground align-top">
                                            {isEditingThisGroup && itemEditIdx !== -1 ? (
                                              <AutoResizingTextarea
                                                rows={2}
                                                value={editingGroup.items[itemEditIdx].description}
                                                onChange={(e) => {
                                                  const newItems = [...editingGroup.items];
                                                  newItems[itemEditIdx].description = e.target.value;
                                                  setEditingGroup({ ...editingGroup, items: newItems });
                                                }}
                                                className="w-full rounded-lg border border-input bg-background p-2 text-xs text-foreground focus:outline-none focus:ring-2 focus:ring-ring shadow-inner"
                                              />
                                            ) : (
                                              <FormattedText value={item.description} />
                                            )}
                                          </td>

                                          {/* Actual Accomplishments */}
                                          {isLocked && (
                                            <td className="px-3 py-3 border-r border-border align-top text-xs h-1 relative">
                                              {Boolean(item.verified) && (
                                                <div
                                                  className="absolute top-2.5 right-2.5 inline-flex items-center text-emerald-600 dark:text-emerald-400"
                                                  title="Verified by supervisor"
                                                >
                                                  <CheckCircle2 className="size-4" />
                                                </div>
                                              )}
                                              <div className="flex h-full min-h-[140px] flex-col justify-between">
                                                <div className={`min-h-[140px] space-y-2 ${Boolean(item.verified) ? 'pr-6' : ''}`}>
                                                  {isReadOnly ? (
                                                    <div className="text-xs text-foreground leading-normal whitespace-pre-line min-h-[140px]">
                                                      {itemValues[item.itemId]?.actualAccomplishment || item.actualAccomplishment || '-'}
                                                    </div>
                                                  ) : (
                                                    <AutoResizingTextarea
                                                      rows={6}
                                                      value={itemValues[item.itemId]?.actualAccomplishment ?? item.actualAccomplishment ?? ''}
                                                      onChange={(e) => scheduleSave(item.itemId, 'actualAccomplishment', e.target.value)}
                                                      onBlur={(e) => saveField(item.itemId, 'actualAccomplishment', e.target.value)}
                                                      placeholder="Actual accomplishment..."
                                                      className="w-full rounded-md border border-input bg-background px-2.5 py-1.5 text-xs leading-4 text-foreground shadow-sm focus:outline-none focus:ring-1 focus:ring-emerald-500 min-h-[140px]"
                                                      style={{ minHeight: '140px' }}
                                                    />
                                                  )}
                                                </div>
                                                <div className="flex items-center justify-between gap-3 border-t border-border pt-2 mt-auto">
                                                  <div className="flex flex-col leading-none">
                                                    <span className="text-[10px] text-muted-foreground">
                                                      {(itemAttachmentCounts[item.itemId] ?? item.attachmentCount ?? 0) === 1
                                                        ? '1 file uploaded'
                                                        : `${itemAttachmentCounts[item.itemId] ?? item.attachmentCount ?? 0} files uploaded`}
                                                    </span>
                                                  </div>
                                                  {isReadOnly ? (
                                                    <button
                                                      type="button"
                                                      onClick={() => handleOpenAttachmentModal(item.itemId)}
                                                      className="inline-flex shrink-0 items-center gap-1 px-1 py-1 text-xs font-semibold text-emerald-600 dark:text-emerald-400 hover:text-emerald-700 cursor-pointer"
                                                    >
                                                      <Eye className="w-3.5 h-3.5" />
                                                      <span>Show MOVs</span>
                                                    </button>
                                                  ) : (
                                                    <button
                                                      type="button"
                                                      onClick={() => handleOpenAttachmentModal(item.itemId)}
                                                      className="inline-flex shrink-0 items-center gap-1 px-1 py-1 text-xs font-semibold text-emerald-600 dark:text-emerald-400 underline decoration-emerald-500/30 underline-offset-2 hover:text-emerald-700 cursor-pointer"
                                                    >
                                                      <Upload className="w-3.5 h-3.5" />
                                                      <span>Upload MOVs</span>
                                                    </button>
                                                  )}
                                                </div>
                                              </div>
                                            </td>
                                          )}

                                          {/* RG Efficiency */}
                                          <td className="px-3 py-3 border-r border-border align-top text-xs">
                                            {isEditingThisGroup && itemEditIdx !== -1 ? (
                                              <AutoResizingTextarea
                                                rows={2}
                                                value={editingGroup.items[itemEditIdx].efficiencyTarget}
                                                onChange={(e) => {
                                                  const newItems = [...editingGroup.items];
                                                  newItems[itemEditIdx].efficiencyTarget = e.target.value;
                                                  setEditingGroup({ ...editingGroup, items: newItems });
                                                }}
                                                className="w-full rounded-lg border border-input bg-background p-2 text-xs text-foreground focus:outline-none focus:ring-2 focus:ring-ring shadow-inner"
                                              />
                                            ) : isLocked ? (
                                              <div className="space-y-1">
                                                {isReadOnly ? (
                                                  <div className="w-full text-center text-xs font-bold text-foreground py-1 mb-1 rounded-md bg-muted/60 border border-border">
                                                    {itemValues[item.itemId]?.actEfficiency || (item.actEfficiency ? String(item.actEfficiency) : 'N/A')}
                                                  </div>
                                                ) : (
                                                  <input
                                                    type="text"
                                                    value={itemValues[item.itemId]?.actEfficiency ?? (item.actEfficiency ? String(item.actEfficiency) : '')}
                                                    onKeyDown={(e) => handleScoreKeyDown(e, item.itemId, 'actEfficiency')}
                                                    onInput={(e) => handleScoreInput(e as any, item.itemId, 'actEfficiency')}
                                                    onBlur={(e) => handleScoreBlur(e, item.itemId, 'actEfficiency')}
                                                    placeholder="Score (1-5 or N/A)"
                                                    className="w-full rounded-md border border-input bg-background px-2.5 py-1 text-center text-xs font-semibold text-foreground shadow-xs focus:border-emerald-500 focus:outline-none focus:ring-1 focus:ring-emerald-500 mb-1"
                                                  />
                                                )}
                                                {item.scorecardEfficiency && (
                                                  <div className="mt-1 flex flex-col items-center gap-0.5">
                                                    <span className="inline-flex items-center gap-1 rounded-md bg-purple-500/10 px-2 py-0.5 text-[10px] font-bold text-purple-700 dark:text-purple-300 border border-purple-500/20 shadow-2xs">
                                                      Scorecard: {item.scorecardEfficiency}
                                                    </span>
                                                    {item.scorecardCreatedByName && (
                                                      <span className="text-[9px] text-muted-foreground italic font-medium whitespace-nowrap truncate max-w-full">
                                                        By: {item.scorecardCreatedByName}
                                                      </span>
                                                    )}
                                                  </div>
                                                )}
                                                {renderTargetTextWithShowMore(item.efficiencyTarget, item.itemId, 'efficiency')}
                                              </div>
                                            ) : (
                                              <div className="font-medium text-foreground">
                                                <FormattedText value={item.efficiencyTarget} />
                                              </div>
                                            )}
                                          </td>

                                          {/* RG Quality */}
                                          <td className="px-3 py-3 border-r border-border align-top text-xs">
                                            {isEditingThisGroup && itemEditIdx !== -1 ? (
                                              <AutoResizingTextarea
                                                rows={2}
                                                value={editingGroup.items[itemEditIdx].qualityTarget}
                                                onChange={(e) => {
                                                  const newItems = [...editingGroup.items];
                                                  newItems[itemEditIdx].qualityTarget = e.target.value;
                                                  setEditingGroup({ ...editingGroup, items: newItems });
                                                }}
                                                className="w-full rounded-lg border border-input bg-background p-2 text-xs text-foreground focus:outline-none focus:ring-2 focus:ring-ring shadow-inner"
                                              />
                                            ) : isLocked ? (
                                              <div className="space-y-1">
                                                {isReadOnly ? (
                                                  <div className="w-full text-center text-xs font-bold text-foreground py-1 mb-1 rounded-md bg-muted/60 border border-border">
                                                    {itemValues[item.itemId]?.actQuality || (item.actQuality ? String(item.actQuality) : 'N/A')}
                                                  </div>
                                                ) : (
                                                  <input
                                                    type="text"
                                                    value={itemValues[item.itemId]?.actQuality ?? (item.actQuality ? String(item.actQuality) : '')}
                                                    onKeyDown={(e) => handleScoreKeyDown(e, item.itemId, 'actQuality')}
                                                    onInput={(e) => handleScoreInput(e as any, item.itemId, 'actQuality')}
                                                    onChange={(e) => handleScoreInput(e as any, item.itemId, 'actQuality')}
                                                    onBlur={(e) => handleScoreBlur(e, item.itemId, 'actQuality')}
                                                    placeholder="Score (1-5 or N/A)"
                                                    className="w-full rounded-md border border-input bg-background px-2.5 py-1 text-center text-xs font-semibold text-foreground shadow-xs focus:border-emerald-500 focus:outline-none focus:ring-1 focus:ring-emerald-500 mb-1"
                                                  />
                                                )}
                                                {item.scorecardQuality && (
                                                  <div className="mt-1 flex flex-col items-center gap-0.5">
                                                    <span className="inline-flex items-center gap-1 rounded-md bg-purple-500/10 px-2 py-0.5 text-[10px] font-bold text-purple-700 dark:text-purple-300 border border-purple-500/20 shadow-2xs">
                                                      Scorecard: {item.scorecardQuality}
                                                    </span>
                                                    {item.scorecardCreatedByName && (
                                                      <span className="text-[9px] text-muted-foreground italic font-medium whitespace-nowrap truncate max-w-full">
                                                        By: {item.scorecardCreatedByName}
                                                      </span>
                                                    )}
                                                  </div>
                                                )}
                                                {renderTargetTextWithShowMore(item.qualityTarget, item.itemId, 'quality')}
                                              </div>
                                            ) : (
                                              <div className="font-medium text-foreground">
                                                <FormattedText value={item.qualityTarget} />
                                              </div>
                                            )}
                                          </td>

                                          {/* RG Timeliness */}
                                          <td className="px-3 py-3 border-r border-border align-top text-xs">
                                            {isEditingThisGroup && itemEditIdx !== -1 ? (
                                              <AutoResizingTextarea
                                                rows={2}
                                                value={editingGroup.items[itemEditIdx].timelinessTarget}
                                                onChange={(e) => {
                                                  const newItems = [...editingGroup.items];
                                                  newItems[itemEditIdx].timelinessTarget = e.target.value;
                                                  setEditingGroup({ ...editingGroup, items: newItems });
                                                }}
                                                className="w-full rounded-lg border border-input bg-background p-2 text-xs text-foreground focus:outline-none focus:ring-2 focus:ring-ring shadow-inner"
                                              />
                                            ) : isLocked ? (
                                              <div className="space-y-1">
                                                {isReadOnly ? (
                                                  <div className="w-full text-center text-xs font-bold text-foreground py-1 mb-1 rounded-md bg-muted/60 border border-border">
                                                    {itemValues[item.itemId]?.actTimeliness || (item.actTimeliness ? String(item.actTimeliness) : 'N/A')}
                                                  </div>
                                                ) : (
                                                  <input
                                                    type="text"
                                                    value={itemValues[item.itemId]?.actTimeliness ?? (item.actTimeliness ? String(item.actTimeliness) : '')}
                                                    onKeyDown={(e) => handleScoreKeyDown(e, item.itemId, 'actTimeliness')}
                                                    onInput={(e) => handleScoreInput(e as any, item.itemId, 'actTimeliness')}
                                                    onChange={(e) => handleScoreInput(e as any, item.itemId, 'actTimeliness')}
                                                    onBlur={(e) => handleScoreBlur(e, item.itemId, 'actTimeliness')}
                                                    placeholder="Score (1-5 or N/A)"
                                                    className="w-full rounded-md border border-input bg-background px-2.5 py-1 text-center text-xs font-semibold text-foreground shadow-xs focus:border-emerald-500 focus:outline-none focus:ring-1 focus:ring-emerald-500 mb-1"
                                                  />
                                                )}
                                                {item.scorecardTimeliness && (
                                                  <div className="mt-1 flex flex-col items-center gap-0.5">
                                                    <span className="inline-flex items-center gap-1 rounded-md bg-purple-500/10 px-2 py-0.5 text-[10px] font-bold text-purple-700 dark:text-purple-300 border border-purple-500/20 shadow-2xs">
                                                      Scorecard: {item.scorecardTimeliness}
                                                    </span>
                                                    {item.scorecardCreatedByName && (
                                                      <span className="text-[9px] text-muted-foreground italic font-medium whitespace-nowrap truncate max-w-full">
                                                        By: {item.scorecardCreatedByName}
                                                      </span>
                                                    )}
                                                  </div>
                                                )}
                                                {renderTargetTextWithShowMore(item.timelinessTarget, item.itemId, 'timeliness')}
                                              </div>
                                            ) : (
                                              <div className="font-medium text-foreground">
                                                <FormattedText value={item.timelinessTarget} />
                                              </div>
                                            )}
                                          </td>

                                          {/* AVE */}
                                          {isLocked && (
                                            <td className="px-3 py-3 text-center border-r border-border font-mono font-bold text-emerald-600 dark:text-emerald-400 align-top text-xs">
                                              {computeItemAverage(
                                                itemValues[item.itemId]?.actEfficiency ?? item.actEfficiency,
                                                itemValues[item.itemId]?.actQuality ?? item.actQuality,
                                                itemValues[item.itemId]?.actTimeliness ?? item.actTimeliness
                                              )}
                                            </td>
                                          )}

                                          {/* RG MOVs */}
                                          <td className="px-3 py-3 border-r border-border align-top text-xs text-foreground">
                                            {isEditingThisGroup && itemEditIdx !== -1 ? (
                                              <AutoResizingTextarea
                                                rows={2}
                                                value={editingGroup.items[itemEditIdx].movs}
                                                onChange={(e) => {
                                                  const newItems = [...editingGroup.items];
                                                  newItems[itemEditIdx].movs = e.target.value;
                                                  setEditingGroup({ ...editingGroup, items: newItems });
                                                }}
                                                className="w-full rounded-lg border border-input bg-background p-2 text-xs text-foreground focus:outline-none focus:ring-2 focus:ring-ring shadow-inner leading-normal"
                                              />
                                            ) : isLocked ? (
                                              isReadOnly ? (
                                                <div className="text-xs text-foreground leading-normal whitespace-pre-line min-h-[200px]">
                                                  {itemValues[item.itemId]?.movs || item.movs || '-'}
                                                </div>
                                              ) : (
                                                <AutoResizingTextarea
                                                  rows={6}
                                                  value={itemValues[item.itemId]?.movs ?? item.movs ?? ''}
                                                  onChange={(e) => scheduleSave(item.itemId, 'movs', e.target.value)}
                                                  onBlur={(e) => saveField(item.itemId, 'movs', e.target.value)}
                                                  placeholder="MOVs..."
                                                  className="w-full rounded-md border border-input bg-background px-2.5 py-1.5 text-xs leading-4 text-foreground shadow-sm focus:outline-none focus:ring-1 focus:ring-emerald-500 min-h-[200px]"
                                                  style={{ minHeight: '200px' }}
                                                />
                                              )
                                            ) : (
                                              <div>
                                                <FormattedText value={item.movs} />
                                              </div>
                                            )}
                                          </td>

                                          {/* RG Remarks */}
                                          <td className="px-3 py-3 align-top text-xs text-foreground h-1">
                                            <div className="flex h-full min-h-[120px] flex-col justify-between">
                                              <div>
                                                {isEditingThisGroup && itemEditIdx !== -1 ? (
                                                  <AutoResizingTextarea
                                                    rows={2}
                                                    value={editingGroup.items[itemEditIdx].remarks}
                                                    onChange={(e) => {
                                                      const newItems = [...editingGroup.items];
                                                      newItems[itemEditIdx].remarks = e.target.value;
                                                      setEditingGroup({ ...editingGroup, items: newItems });
                                                    }}
                                                    className="w-full rounded-lg border border-input bg-background p-2 text-xs text-foreground focus:outline-none focus:ring-2 focus:ring-ring shadow-inner leading-normal"
                                                  />
                                                ) : isLocked ? (
                                                  isReadOnly ? (
                                                    <div className="space-y-2">
                                                      <div className="text-xs text-foreground leading-normal whitespace-pre-line min-h-[200px]">
                                                        {itemValues[item.itemId]?.remarks || item.remarks || '-'}
                                                      </div>
                                                      {item.scorecardRemarks && (
                                                        <div className="rounded-lg border border-purple-500/20 bg-purple-500/5 p-2 text-xs">
                                                          {item.scorecardCreatedByName && (
                                                            <div className="mb-1.5 border-b border-purple-500/20 pb-1">
                                                              <span className="text-[9px] text-muted-foreground italic font-medium whitespace-nowrap truncate max-w-full block">
                                                                By: {item.scorecardCreatedByName}
                                                              </span>
                                                            </div>
                                                          )}
                                                          <div className="text-xs text-foreground leading-normal whitespace-pre-line">
                                                            {item.scorecardRemarks}
                                                          </div>
                                                        </div>
                                                      )}
                                                    </div>
                                                  ) : (
                                                    <div className="space-y-2">
                                                      <AutoResizingTextarea
                                                        rows={6}
                                                        value={itemValues[item.itemId]?.remarks ?? item.remarks ?? ''}
                                                        onChange={(e) => scheduleSave(item.itemId, 'remarks', e.target.value)}
                                                        onBlur={(e) => saveField(item.itemId, 'remarks', e.target.value)}
                                                        placeholder="Remarks..."
                                                        className="w-full rounded-md border border-input bg-background px-2.5 py-1.5 text-xs leading-4 text-foreground shadow-sm focus:outline-none focus:ring-1 focus:ring-emerald-500 min-h-[200px]"
                                                        style={{ minHeight: '200px' }}
                                                      />
                                                      {item.scorecardRemarks && (
                                                        <div className="rounded-lg border border-purple-500/20 bg-purple-500/5 p-2 text-xs">
                                                          {item.scorecardCreatedByName && (
                                                            <div className="mb-1.5 border-b border-purple-500/20 pb-1">
                                                              <span className="text-[9px] text-muted-foreground italic font-medium whitespace-nowrap truncate max-w-full block">
                                                                By: {item.scorecardCreatedByName}
                                                              </span>
                                                            </div>
                                                          )}
                                                          <div className="text-xs text-foreground leading-normal whitespace-pre-line">
                                                            {item.scorecardRemarks}
                                                          </div>
                                                        </div>
                                                      )}
                                                    </div>
                                                  )
                                                ) : (
                                                  <div>
                                                    <FormattedText value={item.remarks} />
                                                  </div>
                                                )}
                                              </div>

                                              {/* Item ID at the bottom center of the Remarks column */}
                                              <div className="pt-2 mt-auto text-center">
                                                <span className="text-[10px] font-semibold italic text-muted-foreground select-none">
                                                  <strong><em>{item.itemId}</em></strong>
                                                </span>
                                              </div>
                                            </div>
                                          </td>
                                        </tr>
                                      );
                                    })}

                                    {/* Inline Pending Row for Sub-Target Creation */}
                                    {isPendingThisGroup && (
                                      <tr className="bg-amber-50/60 dark:bg-amber-950/40 border-b border-border align-top">
                                        <td className="p-2 border-r border-border">
                                          <AutoResizingTextarea
                                            rows={2}
                                            value={pendingForm.description}
                                            onChange={(e) => setPendingForm({ ...pendingForm, description: e.target.value })}
                                            placeholder="Success Indicator Description..."
                                            className="w-full rounded-lg border border-input bg-background p-2 text-xs text-foreground focus:outline-none focus:ring-2 focus:ring-ring shadow-inner"
                                          />
                                        </td>
                                        {isLocked && <td className="p-2 border-r border-border"></td>}
                                        <td className="p-2 border-r border-border">
                                          <AutoResizingTextarea
                                            rows={2}
                                            value={pendingForm.efficiency}
                                            onChange={(e) => setPendingForm({ ...pendingForm, efficiency: e.target.value })}
                                            placeholder="RG Efficiency..."
                                            className="w-full rounded-lg border border-input bg-background p-2 text-xs text-foreground focus:outline-none focus:ring-2 focus:ring-ring shadow-inner"
                                          />
                                        </td>
                                        <td className="p-2 border-r border-border">
                                          <AutoResizingTextarea
                                            rows={2}
                                            value={pendingForm.quality}
                                            onChange={(e) => setPendingForm({ ...pendingForm, quality: e.target.value })}
                                            placeholder="RG Quality..."
                                            className="w-full rounded-lg border border-input bg-background p-2 text-xs text-foreground focus:outline-none focus:ring-2 focus:ring-ring shadow-inner"
                                          />
                                        </td>
                                        <td className="p-2 border-r border-border">
                                          <AutoResizingTextarea
                                            rows={2}
                                            value={pendingForm.timeliness}
                                            onChange={(e) => setPendingForm({ ...pendingForm, timeliness: e.target.value })}
                                            placeholder="RG Timeliness..."
                                            className="w-full rounded-lg border border-input bg-background p-2 text-xs text-foreground focus:outline-none focus:ring-2 focus:ring-ring shadow-inner"
                                          />
                                        </td>
                                        {isLocked && <td className="p-2 border-r border-border"></td>}
                                        <td className="p-2 border-r border-border">
                                          <AutoResizingTextarea
                                            rows={2}
                                            value={pendingForm.movs}
                                            onChange={(e) => setPendingForm({ ...pendingForm, movs: e.target.value })}
                                            placeholder="RG MOVs..."
                                            className="w-full rounded-lg border border-input bg-background p-2 text-xs text-foreground focus:outline-none focus:ring-2 focus:ring-ring shadow-inner"
                                          />
                                        </td>
                                        <td className="p-2">
                                          <AutoResizingTextarea
                                            rows={2}
                                            value={pendingForm.remarks}
                                            onChange={(e) => setPendingForm({ ...pendingForm, remarks: e.target.value })}
                                            placeholder="RG Remarks..."
                                            className="w-full rounded-lg border border-input bg-background p-2 text-xs text-foreground focus:outline-none focus:ring-2 focus:ring-ring shadow-inner"
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
                    <div className="flex flex-col items-center justify-between gap-3 sm:flex-row pt-4 border-t border-border">
                      <div className="text-xs text-muted-foreground font-medium">
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
                            className="px-2.5 py-1.5 rounded-lg text-xs font-semibold text-foreground hover:bg-muted disabled:opacity-40 disabled:cursor-not-allowed transition"
                          >
                            First
                          </button>
                          <button
                            type="button"
                            onClick={() => setCurrentPage((p) => Math.max(1, p - 1))}
                            disabled={pageToUse <= 1}
                            className="px-2.5 py-1.5 rounded-lg text-xs font-semibold text-foreground hover:bg-muted disabled:opacity-40 disabled:cursor-not-allowed transition"
                          >
                            Previous
                          </button>

                          {Array.from({ length: totalPages }, (_, i) => i + 1).map((page) => (
                            <button
                              key={page}
                              type="button"
                              data-pagination-number={page}
                              onClick={() => setCurrentPage(page)}
                              className={`px-3 py-1.5 rounded-lg text-xs font-semibold transition ${page === pageToUse
                                ? 'bg-emerald-600 text-white shadow-xs'
                                : 'text-foreground hover:bg-muted'
                                }`}
                            >
                              {page}
                            </button>
                          ))}

                          <button
                            type="button"
                            onClick={() => setCurrentPage((p) => Math.min(totalPages, p + 1))}
                            disabled={pageToUse >= totalPages}
                            className="px-2.5 py-1.5 rounded-lg text-xs font-semibold text-foreground hover:bg-muted disabled:opacity-40 disabled:cursor-not-allowed transition"
                          >
                            Next
                          </button>
                          <button
                            type="button"
                            onClick={() => setCurrentPage(totalPages)}
                            disabled={pageToUse >= totalPages}
                            className="px-2.5 py-1.5 rounded-lg text-xs font-semibold text-foreground hover:bg-muted disabled:opacity-40 disabled:cursor-not-allowed transition"
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
                      className="w-full h-9 rounded-xl border border-input bg-background px-3 text-xs text-foreground focus:outline-none focus:ring-2 focus:ring-ring"
                    />
                  </div>
                  {deletedSearch && (
                    <button
                      type="button"
                      onClick={() => setDeletedSearch('')}
                      className="px-3 py-1.5 rounded-lg border border-input bg-background text-xs text-muted-foreground hover:bg-muted hover:text-foreground"
                    >
                      Clear
                    </button>
                  )}
                </div>

                <div className="overflow-hidden rounded-xl border border-border bg-card shadow-2xs">
                  <table className="w-full text-xs text-left border-collapse">
                    <thead className="bg-muted/60 font-semibold uppercase text-[11px] text-muted-foreground border-b border-border">
                      <tr>
                        <th className="px-3 py-2.5 border-r border-border">KRA Category</th>
                        <th className="px-3 py-2.5 border-r border-border">Key Result Area (Activity)</th>
                        <th className="px-3 py-2.5 border-r border-border">Success Indicator (Description)</th>
                        <th className="px-3 py-2.5 border-r border-border whitespace-nowrap">Deleted Date & User</th>
                        <th className="px-3 py-2.5 border-r border-border">Justification</th>
                        <th className="px-3 py-2.5 text-center">Action</th>
                      </tr>
                    </thead>
                    <tbody className="divide-y divide-border">
                      {filteredDeletedTargets.length > 0 ? (
                        filteredDeletedTargets.map((item) => (
                          <tr key={item.id} className="hover:bg-muted/30 transition-colors align-top">
                            <td className="px-3 py-2.5 border-r border-border font-semibold text-foreground">{item.kra_category_label}</td>
                            <td className="px-3 py-2.5 border-r border-border font-bold text-foreground">
                              <FormattedText value={item.activity} />
                            </td>
                            <td className="px-3 py-2.5 border-r border-border text-foreground leading-relaxed">
                              <FormattedText value={item.description} />
                            </td>
                            <td className="px-3 py-2.5 border-r border-border text-muted-foreground whitespace-nowrap">
                              <div>{item.deleted_at}</div>
                              <div className="text-[10px] font-semibold text-muted-foreground">{item.user_name}</div>
                            </td>
                            <td className="px-3 py-2.5 border-r border-border italic text-muted-foreground">
                              <FormattedText value={item.justification} />
                            </td>
                            <td className="px-3 py-2.5 text-center whitespace-nowrap">
                              {!isLocked && (
                                <button
                                  type="button"
                                  disabled={restoringId === item.id}
                                  onClick={() => handleRestoreDeletedTarget(item)}
                                  className="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-lg bg-emerald-600 hover:bg-emerald-700 disabled:opacity-50 text-white font-semibold text-xs shadow-xs transition"
                                  title="Restore target back to original location"
                                >
                                  <RotateCcw className={`w-3.5 h-3.5 ${restoringId === item.id ? 'animate-spin' : ''}`} />
                                  <span>{restoringId === item.id ? 'Restoring...' : 'Restore'}</span>
                                </button>
                              )}
                            </td>
                          </tr>
                        ))
                      ) : (
                        <tr>
                          <td colSpan={6} className="px-3 py-8 text-center text-muted-foreground">
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
              <div className="space-y-4">
                <div className="flex flex-wrap items-center justify-between gap-2">
                  {(checkpointItemId || checkpointTargetId) ? (
                    <div className="flex items-center gap-2 p-2 rounded-xl bg-blue-50 dark:bg-blue-950/40 border border-blue-200 dark:border-blue-800 text-xs text-blue-900 dark:text-blue-200">
                      <Clock className="w-4 h-4 text-blue-600 shrink-0" />
                      <span>Showing checkpoint history for selected item</span>
                      <button
                        type="button"
                        onClick={() => {
                          setCheckpointItemId(null);
                          setCheckpointTargetId(null);
                        }}
                        className="ml-2 px-2.5 py-0.5 rounded-lg bg-blue-600 hover:bg-blue-700 text-white font-semibold text-xs transition cursor-pointer"
                      >
                        Show All Checkpoint Changes
                      </button>
                    </div>
                  ) : (
                    <div className="text-xs text-muted-foreground">
                      Individual Performance Checkpoint Form Changes & Amendments
                    </div>
                  )}

                  <a
                    href={`/ipcrf/myratings/semestral-target/print-checkpoint?sem_id=${rating.id}`}
                    target="_blank"
                    rel="noreferrer"
                    className="inline-flex items-center gap-1.5 rounded-xl bg-sky-600 hover:bg-sky-700 text-white px-3.5 py-1.5 text-xs font-semibold shadow-sm transition cursor-pointer"
                  >
                    <Printer className="w-3.5 h-3.5" />
                    <span>Print Checkpoint Form</span>
                  </a>
                </div>

                <div className="overflow-hidden rounded-xl border border-border bg-card shadow-2xs">
                  <div className="overflow-x-auto">
                    <table className="w-full text-xs border-collapse text-left">
                      <thead>
                        <tr className="bg-muted/60 text-muted-foreground font-bold uppercase tracking-wider text-[11px] border-b border-border">
                          <th className="px-3 py-2.5 text-center w-[5%] border-r border-border">NO.</th>
                          <th className="px-3 py-2.5 w-[38%] border-r border-border">ORIGINAL SUCCESS INDICATOR</th>
                          <th className="px-3 py-2.5 w-[38%] border-r border-border">PROPOSED AMENDMENT</th>
                          <th className="px-3 py-2.5 w-[19%]">JUSTIFICATION</th>
                        </tr>
                      </thead>
                      <tbody className="divide-y divide-border">
                        {(() => {
                          const filteredRows = checkpointChanges.filter((c: any) => {
                            if (!checkpointItemId && !checkpointTargetId) return true;
                            if (checkpointTargetId && Number(c.sem_target_id) === Number(checkpointTargetId)) return true;
                            if (checkpointItemId && Array.isArray(c.item_groups)) {
                              return c.item_groups.some((ig: any) => Number(ig.item_id) === Number(checkpointItemId));
                            }
                            return false;
                          });

                          if (filteredRows.length === 0) {
                            return (
                              <tr>
                                <td colSpan={4} className="px-4 py-12 text-center text-sm font-medium text-muted-foreground">
                                  No checkpoint entries or target amendments recorded in edit history.
                                </td>
                              </tr>
                            );
                          }

                          return filteredRows.map((row: CheckpointChange, rIdx: number) => (
                            <React.Fragment key={row.sem_target_id || rIdx}>
                              {row.is_new_target && (
                                <tr className="bg-muted/80 font-bold border-y border-border">
                                  <td colSpan={4} className="py-1.5 px-3 text-center uppercase tracking-wide text-[11px] text-foreground">
                                    {`--- NEW TARGET ADDED: ${row.activity_title || 'NEW ENTRY'} ---`}
                                  </td>
                                </tr>
                              )}
                              <tr className="hover:bg-muted/30 transition-colors align-top">
                                {/* Row Number */}
                                <td className="px-3 py-3 text-center font-bold text-foreground border-r border-border">
                                  {rIdx + 1}
                                </td>

                                {/* Seamless Inner Table comparing Original vs Proposed */}
                                <td colSpan={2} className="p-0 align-top border-r border-border">
                                  <table className="w-full border-collapse table-fixed">
                                    <tbody>
                                      {/* Target-level fields */}
                                      {row.target_fields && row.target_fields.length > 0 && (
                                        <tr className="border-b border-border">
                                          <td className="w-1/2 p-2.5 align-top border-r border-border space-y-1.5">
                                            {row.target_fields.map((f, fi) => (
                                              <div key={fi}>
                                                <span className="font-bold italic text-foreground">{f.field_label}</span>
                                                <div className="text-foreground whitespace-pre-line leading-relaxed">{f.old_value}</div>
                                              </div>
                                            ))}
                                          </td>
                                          <td className="w-1/2 p-2.5 align-top space-y-1.5">
                                            {row.target_fields.map((f, fi) => (
                                              <div key={fi}>
                                                <span className="font-bold italic text-foreground">{f.field_label}</span>
                                                <div className={`whitespace-pre-line leading-relaxed ${f.new_value === 'For Deletion' ? 'text-red-600 dark:text-red-400 font-bold' : 'text-foreground'}`}>
                                                  {f.new_value}
                                                </div>
                                              </div>
                                            ))}
                                          </td>
                                        </tr>
                                      )}

                                      {/* Sub-target item groups */}
                                      {(row.item_groups || []).map((itemGroup, igIdx) => (
                                        <tr key={igIdx} className="border-b border-border last:border-b-0">
                                          <td className="w-1/2 p-2.5 align-top border-r border-border space-y-1.5">
                                            {itemGroup.item_label && (
                                              <div className={`text-[10px] font-bold uppercase tracking-wider ${itemGroup.is_deleted ? 'text-red-600 dark:text-red-400' : 'text-muted-foreground'}`}>
                                                {itemGroup.item_label}
                                              </div>
                                            )}
                                            {itemGroup.fields.map((f, fi) => (
                                              <div key={fi}>
                                                <span className="font-bold italic text-foreground">{f.field_label}</span>
                                                <div className="text-foreground whitespace-pre-line leading-relaxed">{f.old_value}</div>
                                              </div>
                                            ))}
                                          </td>
                                          <td className="w-1/2 p-2.5 align-top space-y-1.5">
                                            {itemGroup.item_label && (
                                              <div className={`text-[10px] font-bold uppercase tracking-wider ${itemGroup.is_deleted ? 'text-red-600 dark:text-red-400' : 'text-muted-foreground'}`}>
                                                {itemGroup.item_label}
                                              </div>
                                            )}
                                            {itemGroup.fields.map((f, fi) => (
                                              <div key={fi}>
                                                <span className="font-bold italic text-foreground">{f.field_label}</span>
                                                <div className={`whitespace-pre-line leading-relaxed ${f.new_value === 'For Deletion' ? 'text-red-600 dark:text-red-400' : 'text-foreground'}`}>
                                                  {f.new_value}
                                                </div>
                                              </div>
                                            ))}
                                          </td>
                                        </tr>
                                      ))}

                                      {(!row.target_fields || row.target_fields.length === 0) && (!row.item_groups || row.item_groups.length === 0) && (
                                        <tr>
                                          <td className="w-1/2 p-2.5 align-top border-r border-border text-muted-foreground">-</td>
                                          <td className="w-1/2 p-2.5 align-top text-muted-foreground">-</td>
                                        </tr>
                                      )}
                                    </tbody>
                                  </table>
                                </td>

                                {/* Justification */}
                                <td className="px-3 py-3 align-top text-foreground leading-relaxed whitespace-pre-line">
                                  {row.justification || '-'}
                                </td>
                              </tr>
                            </React.Fragment>
                          ));
                        })()}
                      </tbody>
                    </table>
                  </div>
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

                {/* Tabular Areas of Improvement & Development Plan */}
                <div className="space-y-3">
                  <div className="flex items-center justify-between">
                    <div>
                      <h3 className="text-sm font-bold text-foreground">
                        Areas of Improvement & Development Plan
                      </h3>
                      <p className="text-xs text-muted-foreground">
                        Professional development goals, learning activities, and required support resources.
                      </p>
                    </div>
                  </div>

                  <div className="overflow-hidden rounded-xl border border-border bg-card shadow-2xs">
                    <div className="overflow-x-auto">
                      <table className="w-full text-xs border-collapse text-left">
                        <thead>
                          <tr className="bg-muted/60 font-bold uppercase tracking-wider text-[11px] text-muted-foreground border-b border-border">
                            <th className="px-3 py-2.5 text-center w-[5%] border-r border-border">#</th>
                            <th className="px-3 py-2.5 w-[25%] border-r border-border">Aim / Areas of Improvement</th>
                            <th className="px-3 py-2.5 w-[25%] border-r border-border">Development Activities</th>
                            <th className="px-3 py-2.5 w-[25%] border-r border-border">Support / Resources Needed</th>
                            <th className="px-3 py-2.5 w-[20%]">Progress / Intervention</th>
                          </tr>
                        </thead>
                        <tbody className="divide-y divide-border">
                          {areasOfImprovement.length > 0 ? (
                            areasOfImprovement.map((area, idx) => (
                              <tr key={area.id || idx} className="hover:bg-muted/30 transition-colors align-top">
                                <td className="px-3 py-3 text-center font-bold text-muted-foreground border-r border-border">
                                  {idx + 1}
                                </td>
                                <td className="px-3 py-3 font-semibold text-foreground border-r border-border leading-relaxed whitespace-pre-line">
                                  {area.areas_improvement || '-'}
                                </td>
                                <td className="px-3 py-3 text-foreground border-r border-border leading-relaxed whitespace-pre-line">
                                  {area.development_activities || '-'}
                                </td>
                                <td className="px-3 py-3 text-foreground border-r border-border leading-relaxed whitespace-pre-line">
                                  {area.support_resources || '-'}
                                </td>
                                <td className="px-3 py-3 text-foreground leading-relaxed whitespace-pre-line">
                                  {area.progress_intervention || '-'}
                                </td>
                              </tr>
                            ))
                          ) : (
                            <tr>
                              <td colSpan={5} className="px-4 py-8 text-center text-xs text-muted-foreground">
                                No development plan items recorded yet.
                              </td>
                            </tr>
                          )}
                        </tbody>
                      </table>
                    </div>
                  </div>
                </div>

                {/* Recommendations & Strengths */}
                <div className="grid gap-4 md:grid-cols-2" dir="ltr" lang="en">
                  <div className="rounded-xl border border-border bg-card p-4 shadow-2xs space-y-2" dir="ltr" lang="en">
                    <h4 className="text-xs font-bold uppercase tracking-wider text-rose-600">Rater's Comments, Recommendations & Commendations</h4>
                    <FormattedText
                      value={rating.recommendation}
                      className="text-xs text-foreground"
                      fallback="-"
                    />
                  </div>

                  <div className="rounded-xl border border-border bg-card p-4 shadow-2xs space-y-2" dir="ltr" lang="en">
                    <h4 className="text-xs font-bold uppercase tracking-wider text-emerald-600">Strengths</h4>
                    <FormattedText
                      value={rating.strengths}
                      className="text-xs text-foreground"
                      fallback="-"
                    />
                  </div>
                </div>
              </div>
            )}

            {/* Tab 5: Documentation */}
            {activeTab === 'documentation' && (
              <div className="space-y-6">
                <div className="flex items-center gap-3">
                  <div className="size-10 rounded-xl bg-indigo-500/10 text-indigo-600 dark:text-indigo-400 flex items-center justify-center font-bold shrink-0">
                    <Folder className="size-5" />
                  </div>
                  <div>
                    <h3 className="text-sm font-bold text-foreground">
                      Semestral Documentation & Attachments
                    </h3>
                    <p className="text-xs text-muted-foreground">
                      Supporting documents, guidelines, and reference files for this semester.
                    </p>
                  </div>
                </div>

                <div className="space-y-4">
                  {/* Drag & Drop Upload Zone */}
                  <div
                    onDragOver={(e) => {
                      e.preventDefault();
                      setIsDragOverDoc(true);
                    }}
                    onDragLeave={(e) => {
                      e.preventDefault();
                      setIsDragOverDoc(false);
                    }}
                    onDrop={(e) => {
                      e.preventDefault();
                      setIsDragOverDoc(false);
                      if (e.dataTransfer.files && e.dataTransfer.files.length > 0) {
                        handleDocUploadFiles(e.dataTransfer.files);
                      }
                    }}
                    className={`rounded-2xl border border-dashed p-6 transition text-center ${isDragOverDoc
                        ? 'border-emerald-500 bg-emerald-500/5 dark:bg-emerald-500/10'
                        : 'border-border bg-muted/20'
                      }`}
                  >
                    <div className="flex flex-col items-center justify-center gap-3 text-center">
                      <div className="size-14 rounded-2xl bg-indigo-500/10 text-indigo-600 dark:text-indigo-400 flex items-center justify-center">
                        <UploadCloud className="size-7" />
                      </div>
                      <div className="space-y-1">
                        <h4 className="text-sm font-semibold text-foreground">
                          Drag and drop files here
                        </h4>
                        <p className="text-xs text-muted-foreground max-w-xl">
                          Accepted files: PDF, images, PowerPoint presentations, Word, and video files. You can upload multiple files at once.
                        </p>
                      </div>
                      <label
                        htmlFor="docFileInput"
                        className="inline-flex cursor-pointer items-center gap-2 rounded-lg bg-emerald-600 px-4 py-2 text-sm font-semibold text-white shadow-sm transition hover:bg-emerald-700"
                      >
                        <Paperclip className="size-4" />
                        <span>Choose Files</span>
                      </label>
                      <input
                        id="docFileInput"
                        ref={docFileInputRef}
                        type="file"
                        multiple
                        accept=".pdf,.jpg,.jpeg,.png,.gif,.webp,.bmp,.svg,.ppt,.pptx,.mp4,.mov,.avi,.mkv,.wmv,.webm,.m4v,.doc,.docx"
                        onChange={(e) => {
                          if (e.target.files && e.target.files.length > 0) {
                            handleDocUploadFiles(e.target.files);
                          }
                        }}
                        className="hidden"
                      />

                      {isUploadingDoc && (
                        <div className="w-full max-w-xl space-y-2 pt-2">
                          <div className="flex items-center justify-between text-[11px] text-muted-foreground">
                            <span>Uploading files...</span>
                            <span>{docUploadProgress}%</span>
                          </div>
                          <div className="h-2 overflow-hidden rounded-full bg-muted">
                            <div
                              className="h-full rounded-full bg-emerald-600 transition-[width] duration-150"
                              style={{ width: `${docUploadProgress}%` }}
                            />
                          </div>
                        </div>
                      )}
                    </div>
                  </div>

                  {/* Uploaded Files Grid matching Livewire exact layout */}
                  <div>
                    <h4 className="text-xs font-bold uppercase tracking-wider text-muted-foreground mb-3">
                      Attached Documents ({documentationFiles.length})
                    </h4>
                    {documentationFiles.length > 0 ? (
                      <div className="grid grid-cols-2 gap-2 sm:grid-cols-4 md:grid-cols-5 lg:grid-cols-8 xl:grid-cols-10">
                        {documentationFiles.map((file, idx) => (
                          <button
                            key={idx}
                            type="button"
                            onClick={() => setPreviewFile(file)}
                            className="group w-full min-w-0 overflow-hidden rounded-md border border-border bg-card text-left shadow-2xs transition hover:-translate-y-0.5 hover:border-emerald-500/60 hover:shadow-md cursor-pointer"
                          >
                            <div className="relative aspect-square w-full overflow-hidden bg-muted">
                              {file.type === 'image' ? (
                                <img
                                  src={file.url}
                                  alt={file.name}
                                  className="h-full w-full object-cover"
                                />
                              ) : file.type === 'pdf' ? (
                                <div className="flex h-full w-full items-center justify-center bg-red-50 text-red-600 dark:bg-red-950/30 dark:text-red-300">
                                  <FileText className="size-5" />
                                </div>
                              ) : file.type === 'presentation' ? (
                                <div className="flex h-full w-full items-center justify-center bg-orange-50 text-orange-600 dark:bg-orange-950/30 dark:text-orange-300">
                                  <Presentation className="size-5" />
                                </div>
                              ) : file.type === 'video' ? (
                                <div className="flex h-full w-full items-center justify-center bg-slate-900 text-white">
                                  <Play className="size-5" />
                                </div>
                              ) : (
                                <div className="flex h-full w-full items-center justify-center bg-indigo-50 text-indigo-600 dark:bg-indigo-950/30 dark:text-indigo-300">
                                  <File className="size-5" />
                                </div>
                              )}
                              <div className="absolute inset-x-0 bottom-0 bg-gradient-to-t from-black/70 to-transparent px-2 pb-1 pt-5">
                                <p className="truncate text-[10px] font-semibold text-white">
                                  {file.name}
                                </p>
                              </div>
                            </div>
                            <div className="space-y-0 p-1">
                              <div className="truncate text-[8px] font-medium leading-[10px] text-foreground">
                                {file.name}
                              </div>
                              <div className="mt-0.5 truncate text-[7px] leading-2 text-muted-foreground">
                                {file.modified_at}
                              </div>
                            </div>
                          </button>
                        ))}
                      </div>
                    ) : (
                      <div className="col-span-full flex flex-col items-center justify-center rounded-2xl border border-dashed border-border bg-muted/20 py-12 text-center">
                        <div className="size-12 rounded-full bg-indigo-500/10 text-indigo-600 dark:text-indigo-400 flex items-center justify-center">
                          <Folder className="size-6" />
                        </div>
                        <h4 className="mt-3 text-sm font-semibold text-foreground">
                          No Documentation Uploaded
                        </h4>
                        <p className="mt-1 text-xs text-muted-foreground max-w-sm">
                          Additional semestral resources, MOVs summaries, and reference documentation will appear here.
                        </p>
                      </div>
                    )}
                  </div>
                </div>
              </div>
            )}
          </div>
        </div>

        {/* EXACT LIVEWIRE RIGHT CLICK CONTEXT MENU & SUB-MENU FLYOUTS */}
        {contextMenu && !isReadOnly && (
          <div
            ref={contextMenuRef}
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
            {!isReadOnly && (
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
            {!isReadOnly && (
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
            {!isReadOnly && (
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

        {/* Edit Target Justification Modal */}
        {showEditJustificationModal && editingGroup && (
          <div className="fixed inset-0 z-50 flex items-center justify-center bg-slate-900/50 backdrop-blur-sm p-4 animate-in fade-in">
            <div className="w-full max-w-lg rounded-2xl border border-slate-200 dark:border-slate-800 bg-white dark:bg-slate-900 p-6 shadow-2xl space-y-4">
              <div className="flex items-start justify-between">
                <div>
                  <h3 className="text-lg font-bold text-slate-900 dark:text-slate-100">Edit Justification Required</h3>
                  <p className="text-xs text-slate-500 dark:text-slate-400 mt-1">
                    Please provide a reason / justification for modifying this semestral target.
                  </p>
                </div>
                <button
                  type="button"
                  onClick={() => {
                    setShowEditJustificationModal(false);
                    setEditJustificationText('');
                  }}
                  className="text-slate-400 hover:text-slate-600 dark:hover:text-slate-200"
                >
                  <X className="w-4 h-4" />
                </button>
              </div>

              <div className="text-xs space-y-2 text-left">
                <label className="block font-semibold text-slate-700 dark:text-slate-300">
                  Justification Remarks <span className="text-rose-500">*</span>
                </label>
                <AutoResizingTextarea
                  rows={3}
                  required
                  value={editJustificationText}
                  onChange={(e) => setEditJustificationText(e.target.value)}
                  placeholder="Enter the reason for updating this target..."
                  className="w-full rounded-xl border border-slate-300 dark:border-slate-700 bg-white dark:bg-slate-800 p-3 text-xs text-slate-900 dark:text-slate-100 focus:outline-none focus:ring-2 focus:ring-emerald-500/30"
                />
              </div>

              <div className="flex justify-end gap-2 pt-3 border-t border-slate-200 dark:border-slate-800">
                <button
                  type="button"
                  onClick={() => {
                    setShowEditJustificationModal(false);
                    setEditJustificationText('');
                  }}
                  className="px-4 py-2 rounded-xl border border-slate-300 dark:border-slate-700 text-xs font-semibold text-slate-700 dark:text-slate-300 hover:bg-slate-50 dark:hover:bg-slate-800 transition"
                >
                  Cancel
                </button>
                <button
                  type="button"
                  disabled={isSavingEditGroup || !editJustificationText.trim()}
                  onClick={handleConfirmSaveEditGroup}
                  className="px-5 py-2 rounded-xl bg-emerald-600 hover:bg-emerald-700 disabled:opacity-50 text-white text-xs font-semibold shadow-sm transition"
                >
                  {isSavingEditGroup ? 'Saving...' : 'Save Changes'}
                </button>
              </div>
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
                    router.delete(`/ipcrf/myratings/${rating.id}/target/${deletingTargetId}`, {
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
                    router.delete(`/ipcrf/myratings/${rating.id}/subtarget/${deletingSubTargetId}`, {
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

        {/* Upload MOVs Modal */}
        {showAttachmentModal && (
          <div
            className="fixed inset-0 z-50 flex items-center justify-center bg-slate-950/60 p-4 backdrop-blur-sm animate-in fade-in"
            onClick={() => {
              setShowAttachmentModal(false);
              setQueuedFiles([]);
              setActiveViewerIndex(-1);
            }}
          >
            <div
              className="relative flex flex-col w-[65vw] max-w-[65vw] h-[85vh] max-h-[85vh] rounded-2xl border border-slate-200 dark:border-slate-800 bg-white dark:bg-slate-900 p-6 shadow-2xl overflow-hidden"
              onClick={(e) => e.stopPropagation()}
            >
              {/* Header */}
              <div className="shrink-0 space-y-1 border-b border-slate-200 dark:border-slate-800 pb-3 mb-2 flex items-start justify-between">
                <div>
                  <h3 className="text-lg font-bold text-slate-900 dark:text-slate-100">
                    {isReadOnly ? 'MOVs / Attachments' : 'Upload MOVs / Attachments'}
                  </h3>
                  <p className="text-xs text-slate-500 dark:text-slate-400">
                    {isReadOnly
                      ? 'View uploaded image and PDF attachments for this target.'
                      : 'Select multiple image or PDF files. Maximum file size is 10MB per file.'}
                  </p>
                </div>
                <button
                  type="button"
                  onClick={() => {
                    setShowAttachmentModal(false);
                    setQueuedFiles([]);
                    setActiveViewerIndex(-1);
                  }}
                  className="rounded-lg p-1 text-slate-400 hover:text-slate-600 dark:hover:text-slate-200 hover:bg-slate-100 dark:hover:bg-slate-800"
                >
                  <X className="w-5 h-5" />
                </button>
              </div>

              {/* Scrollable Gallery */}
              <div className="min-h-0 flex-1 overflow-y-auto py-2 pr-1 space-y-2">
                <div className="flex items-center justify-between sticky top-0 bg-white/95 dark:bg-slate-900/95 backdrop-blur-sm z-10 py-1 border-b border-slate-100 dark:border-slate-800">
                  <h4 className="text-sm font-semibold text-slate-900 dark:text-slate-100">
                    Current Attachments
                  </h4>
                  <span className="text-xs text-slate-500 dark:text-slate-400">
                    {existingAttachments.length === 1 ? '1 file' : `${existingAttachments.length} files`}
                  </span>
                </div>

                {isLoadingAttachments ? (
                  <div className="flex items-center justify-center py-12 text-slate-400 gap-2">
                    <Loader2 className="w-5 h-5 animate-spin" />
                    <span className="text-xs">Loading attachments...</span>
                  </div>
                ) : existingAttachments.length > 0 ? (
                  <div className="grid grid-cols-5 gap-1.5 w-full">
                    {existingAttachments.map((att, idx) => (
                      <div
                        key={att.filename}
                        className="group relative w-full min-w-0 overflow-hidden rounded-md border border-slate-200 dark:border-slate-800 bg-slate-50 dark:bg-slate-800/40 text-left shadow-sm transition hover:-translate-y-0.5 hover:border-emerald-500 hover:shadow-md"
                      >
                        <button
                          type="button"
                          onClick={() => setActiveViewerIndex(idx)}
                          className="w-full text-left focus:outline-none"
                        >
                          <div className="aspect-square w-full overflow-hidden bg-slate-100 dark:bg-slate-800 flex items-center justify-center">
                            {att.type === 'pdf' ? (
                              <div className="flex h-full w-full flex-col items-center justify-center gap-1 bg-red-50 text-red-700 dark:bg-red-950/30 dark:text-red-300">
                                <FileText className="w-5 h-5 text-red-600" />
                                <span className="rounded bg-red-600 px-1 py-0.5 text-[7px] font-bold text-white uppercase">
                                  PDF
                                </span>
                              </div>
                            ) : (
                              <img
                                src={att.url}
                                alt={att.name}
                                loading="lazy"
                                className="h-full w-full object-cover transition duration-300 group-hover:scale-105"
                              />
                            )}
                          </div>
                          <div className="p-1 space-y-0">
                            <div
                              className="truncate text-[8px] font-medium leading-tight text-slate-900 dark:text-slate-100"
                              title={att.name}
                            >
                              {att.name}
                            </div>
                            <div className="text-[7px] leading-none text-slate-500 dark:text-slate-400 mt-0.5">
                              {att.size}
                            </div>
                          </div>
                        </button>
                        {!isReadOnly && (
                          <button
                            type="button"
                            onClick={(e) => {
                              e.stopPropagation();
                              handleDeleteAttachment(att.filename);
                            }}
                            className="absolute top-1 right-1 z-20 flex items-center justify-center rounded-full p-1 bg-red-600 text-white shadow-md transition hover:scale-110 opacity-80 hover:opacity-100"
                            title="Delete attachment"
                          >
                            <Trash2 className="w-3 h-3 text-white" />
                          </button>
                        )}
                      </div>
                    ))}
                  </div>
                ) : (
                  <div className="rounded-xl border border-dashed border-slate-300 dark:border-slate-800 bg-slate-50/50 dark:bg-slate-800/20 px-4 py-8 text-center text-xs text-slate-500 dark:text-slate-400">
                    No attachments uploaded yet.
                  </div>
                )}
              </div>

              {/* Footer / Upload Controls */}
              {!isReadOnly && (
                <div className="shrink-0 border-t border-slate-200 dark:border-slate-800 pt-3 mt-2 space-y-3 bg-white dark:bg-slate-900">
                  <div>
                    <div className="mb-1.5 text-xs font-medium text-slate-800 dark:text-slate-200">
                      Add Attachments
                    </div>
                    <input
                      type="file"
                      multiple
                      accept=".jpg,.jpeg,.png,.pdf,.jfif,.webp,image/jpeg,image/png,image/webp,application/pdf"
                      onChange={handleQueueSelectedFiles}
                      className="block w-full rounded-lg border border-slate-300 dark:border-slate-700 bg-white dark:bg-slate-800 px-3 py-1.5 text-xs text-slate-900 dark:text-slate-100 shadow-sm file:mr-3 file:py-1 file:px-2 file:rounded-md file:border-0 file:text-xs file:font-semibold file:bg-emerald-50 file:text-emerald-700 dark:file:bg-emerald-950 dark:file:text-emerald-300 hover:file:bg-emerald-100"
                    />
                  </div>

                  {queuedFiles.length > 0 && (
                    <div className="max-h-[140px] overflow-y-auto space-y-1.5">
                      <div className="flex items-center justify-between">
                        <span className="text-xs font-semibold text-slate-800 dark:text-slate-200">
                          Selected File Preview
                        </span>
                        <span className="text-xs text-slate-500">
                          {queuedFiles.length} {queuedFiles.length === 1 ? 'file' : 'files'}
                        </span>
                      </div>
                      <div className="grid grid-cols-5 gap-1.5 w-full">
                        {queuedFiles.map((q, idx) => (
                          <div
                            key={idx}
                            className="group relative w-full min-w-0 overflow-hidden rounded-md border border-dashed border-emerald-400 bg-emerald-50/50 dark:bg-emerald-950/20 text-left shadow-sm p-1"
                          >
                            <div className="aspect-square w-full overflow-hidden bg-white dark:bg-slate-800 rounded flex items-center justify-center">
                              {q.type === 'pdf' ? (
                                <div className="flex h-full w-full flex-col items-center justify-center gap-1 bg-red-50 text-red-700">
                                  <FileText className="w-5 h-5 text-red-600" />
                                  <span className="rounded bg-red-600 px-1 py-0.5 text-[7px] font-bold text-white uppercase">
                                    PDF
                                  </span>
                                </div>
                              ) : (
                                <img
                                  src={q.url}
                                  alt={q.name}
                                  className="h-full w-full object-cover"
                                />
                              )}
                            </div>
                            <div className="truncate text-[8px] font-medium text-slate-800 dark:text-slate-200 mt-1">
                              {q.name}
                            </div>
                            <button
                              type="button"
                              onClick={() => handleRemoveQueuedFile(idx)}
                              className="absolute top-1 right-1 rounded-full bg-rose-600 text-white p-0.5 hover:bg-rose-700"
                            >
                              <X className="w-3 h-3" />
                            </button>
                          </div>
                        ))}
                      </div>
                    </div>
                  )}

                  <div className="flex items-center justify-between pt-1">
                    <button
                      type="button"
                      onClick={() => {
                        if (attachmentItemId) handleOpenStaffMovModal(attachmentItemId);
                      }}
                      className="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-lg border border-slate-300 dark:border-slate-700 bg-white dark:bg-slate-800 text-slate-700 dark:text-slate-200 hover:bg-slate-50 dark:hover:bg-slate-700 text-xs font-semibold transition cursor-pointer"
                    >
                      <Upload className="w-4 h-4 text-emerald-600" />
                      <span>Get MOVs From Staff</span>
                    </button>

                    <div className="flex items-center gap-2">
                      <button
                        type="button"
                        disabled={queuedFiles.length === 0 || isUploadingAttachments}
                        onClick={handleUploadFiles}
                        className="inline-flex items-center gap-1.5 px-4 py-1.5 rounded-lg bg-emerald-600 text-white hover:bg-emerald-700 text-xs font-semibold disabled:opacity-50 disabled:cursor-not-allowed transition cursor-pointer"
                      >
                        {isUploadingAttachments ? (
                          <>
                            <Loader2 className="w-4 h-4 animate-spin" />
                            <span>Uploading...</span>
                          </>
                        ) : (
                          <span>Upload Files</span>
                        )}
                      </button>
                      <button
                        type="button"
                        onClick={() => {
                          setShowAttachmentModal(false);
                          setQueuedFiles([]);
                          setActiveViewerIndex(-1);
                        }}
                        className="px-3 py-1.5 rounded-lg text-slate-600 dark:text-slate-400 hover:bg-slate-100 dark:hover:bg-slate-800 text-xs font-medium transition cursor-pointer"
                      >
                        Close
                      </button>
                    </div>
                  </div>
                </div>
              )}

              {/* Lightbox / Preview Viewer */}
              {activeViewerIndex >= 0 && existingAttachments[activeViewerIndex] && (
                <div
                  className="absolute inset-0 z-50 flex items-center justify-center overflow-hidden rounded-2xl bg-slate-950/85 backdrop-blur-md p-4 animate-in fade-in"
                  onClick={() => setActiveViewerIndex(-1)}
                >
                  <div
                    className="relative flex h-full w-full flex-col overflow-hidden rounded-xl bg-slate-950/90 border border-slate-800 shadow-2xl"
                    onClick={(e) => e.stopPropagation()}
                  >
                    {/* Header */}
                    <div className="flex items-center justify-between border-b border-slate-800/80 px-4 py-2.5 bg-slate-950/70">
                      <div className="min-w-0 flex-1 pr-3">
                        <div className="truncate text-xs font-semibold text-slate-200">
                          {existingAttachments[activeViewerIndex].name}
                        </div>
                        <div className="text-[10px] text-slate-400">
                          {activeViewerIndex + 1} of {existingAttachments.length} • {existingAttachments[activeViewerIndex].size}
                        </div>
                      </div>
                      <div className="flex items-center gap-2">
                        {!isReadOnly && (
                          <button
                            type="button"
                            onClick={() => handleDeleteAttachment(existingAttachments[activeViewerIndex].filename)}
                            className="flex items-center gap-1.5 rounded-md px-2.5 py-1 text-[11px] font-semibold text-white bg-red-600 hover:bg-red-700 shadow-sm transition"
                          >
                            <Trash2 className="w-3.5 h-3.5" />
                            <span>Delete</span>
                          </button>
                        )}
                        <button
                          type="button"
                          onClick={() => setActiveViewerIndex(-1)}
                          className="rounded-full bg-slate-800/60 p-1.5 text-slate-300 hover:bg-red-600 hover:text-white transition"
                        >
                          <X className="w-4 h-4" />
                        </button>
                      </div>
                    </div>

                    {/* Preview Body */}
                    <div className="relative min-h-0 flex-1 overflow-hidden p-3 flex items-center justify-center bg-black/40">
                      {existingAttachments[activeViewerIndex].type === 'image' ? (
                        <img
                          src={existingAttachments[activeViewerIndex].url}
                          alt={existingAttachments[activeViewerIndex].name}
                          className="h-full w-full rounded-lg object-contain"
                        />
                      ) : (
                        <iframe
                          src={existingAttachments[activeViewerIndex].url}
                          className="h-full w-full rounded-lg bg-white shadow-md"
                          title="PDF attachment viewer"
                        />
                      )}
                    </div>

                    {/* Nav controls */}
                    {existingAttachments.length > 1 && (
                      <>
                        <button
                          type="button"
                          onClick={() =>
                            setActiveViewerIndex(
                              (activeViewerIndex - 1 + existingAttachments.length) % existingAttachments.length
                            )
                          }
                          className="absolute left-4 top-1/2 -translate-y-1/2 rounded-full bg-slate-900/90 border border-slate-700/80 p-3 text-white shadow-2xl hover:bg-emerald-600 transition"
                          title="Previous"
                        >
                          <ChevronLeft className="w-6 h-6" />
                        </button>
                        <button
                          type="button"
                          onClick={() =>
                            setActiveViewerIndex((activeViewerIndex + 1) % existingAttachments.length)
                          }
                          className="absolute right-4 top-1/2 -translate-y-1/2 rounded-full bg-slate-900/90 border border-slate-700/80 p-3 text-white shadow-2xl hover:bg-emerald-600 transition"
                          title="Next"
                        >
                          <ChevronRight className="w-6 h-6" />
                        </button>
                      </>
                    )}
                  </div>
                </div>
              )}
            </div>
          </div>
        )}

        {/* Get MOVs From Staff Modal */}
        {showStaffMovModal && (
          <div
            className="fixed inset-0 z-50 flex items-center justify-center bg-slate-950/60 p-4 backdrop-blur-sm animate-in fade-in"
            onClick={() => setShowStaffMovModal(false)}
          >
            <div
              className="flex flex-col w-[52vw] max-w-[52vw] h-[78vh] max-h-[78vh] rounded-2xl border border-slate-200 dark:border-slate-800 bg-white dark:bg-slate-900 p-6 shadow-2xl overflow-hidden space-y-4"
              onClick={(e) => e.stopPropagation()}
            >
              {/* Banner */}
              <div className="rounded-2xl border border-emerald-200/70 bg-gradient-to-r from-emerald-50 via-white to-slate-50 dark:from-emerald-950/35 dark:via-slate-900 dark:to-slate-950 px-4 py-3 shadow-sm flex items-center justify-between gap-3 shrink-0">
                <div className="space-y-0.5">
                  <h3 className="text-base font-bold text-slate-900 dark:text-slate-100">
                    Get MOVs From Staff
                  </h3>
                  <p className="text-xs text-slate-500 dark:text-slate-400">
                    Browse MOVs uploaded by other staff and copy them into the currently selected target.
                  </p>
                </div>
                <div className="flex items-center gap-2 rounded-full border border-emerald-200 bg-white dark:bg-slate-950 dark:border-emerald-900/60 px-3 py-1 text-xs font-medium text-emerald-800 dark:text-emerald-300 shadow-sm shrink-0">
                  <span className="rounded-full bg-emerald-600 px-2 py-0.5 text-[10px] font-semibold text-white">
                    Context
                  </span>
                  <span>Year: {staffMovContextYear || '-'}</span>
                  <span>|</span>
                  <span>Semester: {staffMovContextSemester === '1' ? '1st' : '2nd'}</span>
                </div>
              </div>

              {/* Filters */}
              <div className="grid grid-cols-12 gap-3 shrink-0">
                <div className="col-span-7 space-y-1">
                  <label className="text-xs font-medium text-slate-700 dark:text-slate-300">
                    Staff Name
                  </label>
                  <select
                    value={selectedStaffUserId}
                    onChange={(e) => handleFilterStaffMovs(e.target.value, staffMovSearch)}
                    className="w-full h-9 rounded-xl border border-slate-300 dark:border-slate-700 bg-white dark:bg-slate-800 px-3 text-xs text-slate-900 dark:text-slate-100 focus:outline-none focus:ring-2 focus:ring-emerald-500/30"
                  >
                    <option value="">All staff</option>
                    {staffMovUsers.map((u) => (
                      <option key={u.id} value={u.id}>
                        {u.name} {u.position ? `(${u.position})` : ''}
                      </option>
                    ))}
                  </select>
                </div>

                <div className="col-span-5 space-y-1">
                  <label className="text-xs font-medium text-slate-700 dark:text-slate-300">
                    Search
                  </label>
                  <input
                    type="text"
                    value={staffMovSearch}
                    onChange={(e) => handleFilterStaffMovs(selectedStaffUserId, e.target.value)}
                    placeholder="Search activity or description..."
                    className="w-full h-9 rounded-xl border border-slate-300 dark:border-slate-700 bg-white dark:bg-slate-800 px-3 text-xs text-slate-900 dark:text-slate-100 focus:outline-none focus:ring-2 focus:ring-emerald-500/30"
                  />
                </div>
              </div>

              {/* Sources List */}
              <div className="min-h-0 flex-1 overflow-y-auto rounded-xl border border-slate-200 dark:border-slate-800 bg-slate-50/50 dark:bg-slate-900/50 p-4">
                {isLoadingStaffMovs ? (
                  <div className="flex min-h-[16rem] items-center justify-center text-slate-400 gap-2">
                    <Loader2 className="w-5 h-5 animate-spin" />
                    <span className="text-sm">Loading staff MOVs...</span>
                  </div>
                ) : staffMovSources.length === 0 ? (
                  <div className="flex min-h-[16rem] items-center justify-center p-8 text-center text-sm text-slate-500">
                    Use the filters above to display MOVs from other staff.
                  </div>
                ) : (
                  <div className="space-y-4">
                    {staffMovSources.map((source) => (
                      <div
                        key={source.semTargetId}
                        className="rounded-xl border border-slate-200 dark:border-slate-800 bg-white dark:bg-slate-900 p-3.5 shadow-sm space-y-3"
                      >
                        <div className="flex items-center justify-between border-b border-slate-100 dark:border-slate-800 pb-2">
                          <div>
                            <div className="text-sm font-bold text-slate-900 dark:text-slate-100">
                              {source.staffName}
                            </div>
                            <div className="text-xs text-slate-500">
                              KRA Category: {getKraLabel(source.kraCategory)} | Year: {source.year} | Semester:{' '}
                              {source.semester === 1 ? '1st Semester' : '2nd Semester'}
                            </div>
                          </div>
                        </div>

                        <div className="space-y-2">
                          {source.items.map((it) => (
                            <div
                              key={it.itemId}
                              className="flex items-start justify-between gap-3 rounded-lg border border-slate-200 dark:border-slate-800 bg-slate-50/70 dark:bg-slate-800/40 p-3"
                            >
                              <div className="min-w-0 flex-1">
                                <div className="text-xs font-medium text-slate-900 dark:text-slate-100">
                                  <FormattedText value={it.description} />
                                </div>
                                <div className="mt-1 text-[11px] text-slate-500">
                                  Attachments found: {it.attachmentCount} {it.attachmentCount === 1 ? 'file' : 'files'}
                                </div>
                              </div>
                              <button
                                type="button"
                                disabled={isCopyingStaffMovs}
                                onClick={() => handleCopyStaffMovs(it.itemId)}
                                className="inline-flex items-center gap-1 px-3 py-1.5 rounded-lg bg-emerald-600 text-white hover:bg-emerald-700 text-xs font-semibold transition disabled:opacity-50 cursor-pointer"
                              >
                                {isCopyingStaffMovs ? (
                                  <Loader2 className="w-3.5 h-3.5 animate-spin" />
                                ) : (
                                  <Copy className="w-3.5 h-3.5" />
                                )}
                                <span>Use MOVs</span>
                              </button>
                            </div>
                          ))}
                        </div>
                      </div>
                    ))}
                  </div>
                )}
              </div>

              {/* Footer */}
              <div className="shrink-0 flex items-center justify-end pt-1 border-t border-slate-200 dark:border-slate-800">
                <button
                  type="button"
                  onClick={() => setShowStaffMovModal(false)}
                  className="px-4 py-1.5 rounded-lg text-slate-600 dark:text-slate-400 hover:bg-slate-100 dark:hover:bg-slate-800 text-xs font-medium transition cursor-pointer"
                >
                  Close
                </button>
              </div>
            </div>
          </div>
        )}

        {/* Full Preview Modal for Documentation file matching Livewire exactly */}
        {previewFile && (
          <div
            className="fixed inset-0 z-[60] flex items-center justify-center bg-slate-950/75 p-4 animate-in fade-in"
            onClick={() => setPreviewFile(null)}
          >
            <div
              className="flex h-[700px] max-h-[90vh] w-[95vw] sm:w-[70vw] flex-col overflow-hidden rounded-2xl border border-slate-700 bg-slate-900 shadow-2xl animate-in zoom-in-95"
              onClick={(e) => e.stopPropagation()}
            >
              {/* Modal Header */}
              <div className="flex items-center justify-between border-b border-slate-700 px-4 py-3">
                <div className="min-w-0 pr-4">
                  <h3 className="truncate text-sm font-semibold text-white">
                    {previewFile.name}
                  </h3>
                  <p className="text-xs text-slate-400">{previewFile.modified_at}</p>
                </div>
                <div className="flex items-center gap-2 shrink-0">
                  <button
                    type="button"
                    onClick={() => handleDocDelete(previewFile.name)}
                    className="inline-flex items-center gap-1.5 rounded-lg bg-rose-600 px-3 py-1.5 text-xs font-semibold text-white hover:bg-rose-700 transition cursor-pointer"
                  >
                    <Trash2 className="w-3.5 h-3.5" />
                    <span>Remove</span>
                  </button>
                  <button
                    type="button"
                    onClick={() => setPreviewFile(null)}
                    className="rounded-lg px-3 py-1.5 text-xs font-semibold text-slate-300 hover:bg-white/10 hover:text-white transition cursor-pointer"
                  >
                    Close
                  </button>
                </div>
              </div>

              {/* Modal Body */}
              <div className="min-h-0 flex-1 bg-black overflow-hidden flex items-center justify-center">
                {previewFile.type === 'image' ? (
                  <img
                    src={previewFile.url}
                    alt={previewFile.name}
                    className="h-full w-full object-contain"
                  />
                ) : previewFile.type === 'pdf' ? (
                  <iframe
                    src={previewFile.url}
                    title={previewFile.name}
                    className="h-full w-full border-0"
                  />
                ) : previewFile.type === 'presentation' || previewFile.type === 'word' ? (
                  <iframe
                    src={`https://view.officeapps.live.com/op/embed.aspx?src=${encodeURIComponent(previewFile.url)}`}
                    title={previewFile.name}
                    className="h-full w-full border-0"
                  />
                ) : previewFile.type === 'video' ? (
                  <video controls className="h-full w-full bg-black object-contain">
                    <source src={previewFile.url} />
                  </video>
                ) : (
                  <div className="flex h-full flex-col items-center justify-center p-6 text-center text-white space-y-3">
                    <File className="size-12 text-slate-400" />
                    <p className="text-sm text-slate-300">Preview is not available for this file type.</p>
                    <a
                      href={previewFile.url}
                      target="_blank"
                      rel="noreferrer"
                      className="inline-flex items-center gap-1.5 rounded-lg bg-emerald-600 px-4 py-2 text-xs font-semibold text-white hover:bg-emerald-700 transition"
                    >
                      Open File
                    </a>
                  </div>
                )}
              </div>
            </div>
          </div>
        )}

        {/* Modal: Save and Lock Semestral Target */}
        {showLockModal && (
          <div className="fixed inset-0 z-[9999] flex items-center justify-center bg-slate-950/60 backdrop-blur-sm p-4 animate-in fade-in duration-200">
            <div className="w-full max-w-md rounded-2xl bg-white dark:bg-slate-900 p-6 shadow-2xl space-y-4 border border-slate-200 dark:border-slate-800 animate-in zoom-in-95 duration-200">
              <div className="flex items-start gap-3.5">
                <div className="shrink-0 w-11 h-11 rounded-xl bg-slate-100 dark:bg-slate-800 flex items-center justify-center border border-slate-200 dark:border-slate-700 shadow-sm">
                  <Lock className="w-5 h-5 text-slate-800 dark:text-slate-200" />
                </div>
                <div className="space-y-1 min-w-0 flex-1">
                  <h3 className="text-sm font-bold text-slate-900 dark:text-slate-100">
                    Save and Lock Semestral Target
                  </h3>
                  <p className="text-xs text-slate-500 dark:text-slate-400 leading-relaxed">
                    Are you sure you want to save and lock this semestral target?
                  </p>
                </div>
              </div>

              <div className="rounded-xl border border-slate-200 dark:border-slate-800 bg-slate-50/80 dark:bg-slate-800/40 p-3 text-xs leading-relaxed text-slate-600 dark:text-slate-300">
                <span className="font-semibold text-slate-900 dark:text-slate-100">Note: </span>
                Once locked, the performance indicator table will enable rating inputs (Actual Accomplishments, MOVs, Remarks, and Average scores).
              </div>

              <div className="flex items-center justify-end gap-2 pt-1 border-t border-slate-100 dark:border-slate-800">
                <button
                  type="button"
                  onClick={() => setShowLockModal(false)}
                  className="rounded-xl px-4 py-2 text-xs font-semibold text-slate-600 dark:text-slate-400 hover:bg-slate-100 dark:hover:bg-slate-800 transition cursor-pointer"
                >
                  Cancel
                </button>
                <button
                  type="button"
                  onClick={() => {
                    setShowLockModal(false);
                    router.post(`/ipcrf/myratings/${rating.id}/toggle-status`, { action: 'lock' });
                  }}
                  className="inline-flex items-center gap-1.5 rounded-xl bg-slate-900 dark:bg-slate-100 hover:bg-slate-800 dark:hover:bg-slate-200 text-white dark:text-slate-900 px-4 py-2 text-xs font-semibold shadow-md transition cursor-pointer"
                >
                  <Lock className="w-3.5 h-3.5" />
                  <span>Confirm and Lock</span>
                </button>
              </div>
            </div>
          </div>
        )}

        {/* Modal: Unlock Semestral Target */}
        {showUnlockModal && (
          <div className="fixed inset-0 z-[9999] flex items-center justify-center bg-slate-950/60 backdrop-blur-sm p-4 animate-in fade-in duration-200">
            <div className="w-full max-w-md rounded-2xl bg-white dark:bg-slate-900 p-6 shadow-2xl space-y-4 border border-slate-200 dark:border-slate-800 animate-in zoom-in-95 duration-200">
              <div className="flex items-start gap-3.5">
                <div className="shrink-0 w-11 h-11 rounded-xl bg-amber-100 dark:bg-amber-950/80 flex items-center justify-center border border-amber-300 dark:border-amber-700/60 shadow-sm">
                  <LockOpen className="w-5 h-5 text-amber-700 dark:text-amber-300" />
                </div>
                <div className="space-y-1 min-w-0 flex-1">
                  <h3 className="text-sm font-bold text-slate-900 dark:text-slate-100">
                    Unlock Semestral Target
                  </h3>
                  <p className="text-xs text-slate-500 dark:text-slate-400 leading-relaxed">
                    Are you sure you want to unlock this semestral target?
                  </p>
                </div>
              </div>

              <div className="rounded-xl border border-amber-300/60 dark:border-amber-800/40 bg-amber-50/80 dark:bg-amber-950/30 p-3 text-xs leading-relaxed text-amber-900 dark:text-amber-200">
                <span className="font-semibold text-amber-950 dark:text-amber-100">Notice: </span>
                Unlocking will return your target to draft mode so you can add, edit, or reorder targets.
              </div>

              <div className="flex items-center justify-end gap-2 pt-1 border-t border-slate-100 dark:border-slate-800">
                <button
                  type="button"
                  onClick={() => setShowUnlockModal(false)}
                  className="rounded-xl px-4 py-2 text-xs font-semibold text-slate-600 dark:text-slate-400 hover:bg-slate-100 dark:hover:bg-slate-800 transition cursor-pointer"
                >
                  Cancel
                </button>
                <button
                  type="button"
                  onClick={() => {
                    setShowUnlockModal(false);
                    router.post(`/ipcrf/myratings/${rating.id}/toggle-status`, { action: 'unlock' });
                  }}
                  className="inline-flex items-center gap-1.5 rounded-xl bg-amber-600 hover:bg-amber-700 text-white px-4 py-2 text-xs font-semibold shadow-md transition cursor-pointer"
                >
                  <LockOpen className="w-3.5 h-3.5" />
                  <span>Confirm and Unlock</span>
                </button>
              </div>
            </div>
          </div>
        )}

        {/* Modal: Confirm Ready Submission */}
        {showImReadyModal && (
          <div className="fixed inset-0 z-[9999] flex items-center justify-center bg-slate-950/60 backdrop-blur-sm p-4 animate-in fade-in duration-200">
            <div className="w-full max-w-md rounded-2xl bg-white dark:bg-slate-900 p-6 shadow-2xl space-y-4 border border-slate-200 dark:border-slate-800 animate-in zoom-in-95 duration-200">
              <div className="flex items-start gap-3.5">
                <div className="shrink-0 w-11 h-11 rounded-xl bg-emerald-100 dark:bg-emerald-950/80 flex items-center justify-center border border-emerald-300 dark:border-emerald-800/60 shadow-sm">
                  <CheckCircle2 className="w-5 h-5 text-emerald-600 dark:text-emerald-400" />
                </div>
                <div className="space-y-1 min-w-0 flex-1">
                  <h3 className="text-sm font-bold text-slate-900 dark:text-slate-100">
                    Confirm Ready Submission
                  </h3>
                  <p className="text-xs text-slate-500 dark:text-slate-400 leading-relaxed">
                    Are you sure you are ready to submit your semestral target ratings? Once confirmed, your targets and scores will be locked (ipc_semester.lock = 2) and can no longer be edited.
                  </p>
                </div>
              </div>

              <div className="rounded-xl border border-emerald-200 dark:border-emerald-900/40 bg-emerald-50/80 dark:bg-emerald-950/30 p-3 text-xs leading-relaxed text-emerald-900 dark:text-emerald-200">
                <span className="font-semibold text-emerald-950 dark:text-emerald-100">Submission Notice: </span>
                This will submit your ratings for supervisor verification.
              </div>

              <div className="flex items-center justify-end gap-2 pt-1 border-t border-slate-100 dark:border-slate-800">
                <button
                  type="button"
                  onClick={() => setShowImReadyModal(false)}
                  className="rounded-xl px-4 py-2 text-xs font-semibold text-slate-600 dark:text-slate-400 hover:bg-slate-100 dark:hover:bg-slate-800 transition cursor-pointer"
                >
                  Cancel
                </button>
                <button
                  type="button"
                  onClick={() => {
                    setShowImReadyModal(false);
                    router.post(`/ipcrf/myratings/${rating.id}/toggle-status`, { action: 'ready' });
                  }}
                  className="inline-flex items-center gap-1.5 rounded-xl bg-emerald-600 hover:bg-emerald-700 text-white px-4 py-2 text-xs font-semibold shadow-md transition cursor-pointer"
                >
                  <CheckCircle2 className="w-3.5 h-3.5" />
                  <span>Yes, I'm Ready</span>
                </button>
              </div>
            </div>
          </div>
        )}

        {/* Modal: Confirm Cancel Ready / Reopen for Editing (lock = 1) */}
        {showCancelReadyModal && (
          <div className="fixed inset-0 z-[9999] flex items-center justify-center bg-slate-950/60 backdrop-blur-sm p-4 animate-in fade-in duration-200">
            <div className="w-full max-w-md rounded-2xl bg-white dark:bg-slate-900 p-6 shadow-2xl space-y-4 border border-slate-200 dark:border-slate-800 animate-in zoom-in-95 duration-200">
              <div className="flex items-start gap-3.5">
                <div className="shrink-0 w-11 h-11 rounded-xl bg-amber-100 dark:bg-amber-950/80 flex items-center justify-center border border-amber-300 dark:border-amber-800/60 shadow-sm">
                  <Clock className="w-5 h-5 text-amber-600 dark:text-amber-400" />
                </div>
                <div className="space-y-1 min-w-0 flex-1">
                  <h3 className="text-sm font-bold text-slate-900 dark:text-slate-100">
                    Cancel Ready Submission
                  </h3>
                  <p className="text-xs text-slate-500 dark:text-slate-400 leading-relaxed">
                    Are you sure you want to cancel your ready submission? This will update your status back to locked targets (ipc_semester.lock = 1), allowing you to edit accomplishment scores and attachments again.
                  </p>
                </div>
              </div>

              <div className="rounded-xl border border-amber-200 dark:border-amber-900/40 bg-amber-50/80 dark:bg-amber-950/30 p-3 text-xs leading-relaxed text-amber-900 dark:text-amber-200">
                <span className="font-semibold text-amber-950 dark:text-amber-100">Note: </span>
                Your semestral targets will remain locked from adding or deleting indicators, but accomplishments and MOVs will become editable.
              </div>

              <div className="flex items-center justify-end gap-2 pt-1 border-t border-slate-100 dark:border-slate-800">
                <button
                  type="button"
                  onClick={() => setShowCancelReadyModal(false)}
                  className="rounded-xl px-4 py-2 text-xs font-semibold text-slate-600 dark:text-slate-400 hover:bg-slate-100 dark:hover:bg-slate-800 transition cursor-pointer"
                >
                  Cancel
                </button>
                <button
                  type="button"
                  onClick={() => {
                    setShowCancelReadyModal(false);
                    router.post(`/ipcrf/myratings/${rating.id}/toggle-status`, { action: 'unready' });
                  }}
                  className="inline-flex items-center gap-1.5 rounded-xl bg-amber-600 hover:bg-amber-700 text-white px-4 py-2 text-xs font-semibold shadow-md transition cursor-pointer"
                >
                  <Clock className="w-3.5 h-3.5" />
                  <span>Confirm and Revert (Lock = 1)</span>
                </button>
              </div>
            </div>
          </div>
        )}

        {/* Generic Confirmation UI Modal */}
        {confirmModal && confirmModal.isOpen && (
          <div className="fixed inset-0 z-[9999] flex items-center justify-center bg-slate-950/60 backdrop-blur-sm p-4 animate-in fade-in duration-200">
            <div className="w-full max-w-md rounded-2xl bg-white dark:bg-slate-900 p-6 shadow-2xl space-y-4 border border-slate-200 dark:border-slate-800 animate-in zoom-in-95 duration-200">
              <div className="flex items-start gap-3.5">
                <div
                  className={`shrink-0 w-11 h-11 rounded-xl flex items-center justify-center shadow-sm ${confirmModal.variant === 'danger'
                      ? 'bg-red-100 text-red-600 dark:bg-red-950/80 dark:text-red-300 border border-red-300 dark:border-red-800/60'
                      : confirmModal.variant === 'emerald'
                        ? 'bg-emerald-100 text-emerald-600 dark:bg-emerald-950/80 dark:text-emerald-300 border border-emerald-300 dark:border-emerald-800/60'
                        : confirmModal.variant === 'warning'
                          ? 'bg-amber-100 text-amber-600 dark:bg-amber-950/80 dark:text-amber-300 border border-amber-300 dark:border-amber-800/60'
                          : 'bg-slate-100 text-slate-700 dark:bg-slate-800 dark:text-slate-300 border border-slate-200 dark:border-slate-700'
                    }`}
                >
                  {confirmModal.icon === 'trash' ? (
                    <Trash2 className="w-5 h-5" />
                  ) : confirmModal.icon === 'restore' ? (
                    <RotateCcw className="w-5 h-5" />
                  ) : confirmModal.icon === 'unlock' ? (
                    <LockOpen className="w-5 h-5" />
                  ) : confirmModal.icon === 'lock' ? (
                    <Lock className="w-5 h-5" />
                  ) : (
                    <AlertCircle className="w-5 h-5" />
                  )}
                </div>
                <div className="space-y-1 min-w-0 flex-1">
                  <h3 className="text-sm font-bold text-slate-900 dark:text-slate-100">
                    {confirmModal.title}
                  </h3>
                  <p className="text-xs text-slate-500 dark:text-slate-400 leading-relaxed">
                    {confirmModal.message}
                  </p>
                </div>
              </div>

              <div className="flex items-center justify-end gap-2 pt-1 border-t border-slate-100 dark:border-slate-800">
                <button
                  type="button"
                  onClick={() => setConfirmModal(null)}
                  className="rounded-xl px-4 py-2 text-xs font-semibold text-slate-600 dark:text-slate-400 hover:bg-slate-100 dark:hover:bg-slate-800 transition cursor-pointer"
                >
                  {confirmModal.cancelText || 'Cancel'}
                </button>
                <button
                  type="button"
                  onClick={() => {
                    const onConf = confirmModal.onConfirm;
                    setConfirmModal(null);
                    onConf();
                  }}
                  className={`inline-flex items-center gap-1.5 rounded-xl px-4 py-2 text-xs font-semibold shadow-md transition cursor-pointer ${confirmModal.variant === 'danger'
                      ? 'bg-red-600 hover:bg-red-700 text-white'
                      : confirmModal.variant === 'emerald'
                        ? 'bg-emerald-600 hover:bg-emerald-700 text-white'
                        : confirmModal.variant === 'warning'
                          ? 'bg-amber-600 hover:bg-amber-700 text-white'
                          : 'bg-slate-900 hover:bg-slate-800 dark:bg-slate-100 dark:hover:bg-white text-white dark:text-slate-900'
                    }`}
                >
                  {confirmModal.confirmText || 'Confirm'}
                </button>
              </div>
            </div>
          </div>
        )}
      </div>
    </AppLayout>
  );
}

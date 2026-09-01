import React, { useState, useEffect, useRef } from 'react';
import { Head, router, useForm } from '@inertiajs/react';
import AppLayout from '../../../Layouts/AppLayout';
import AutoResizingTextarea from '../../../Components/AutoResizingTextarea';
import { toast } from '../../../Components/ToastContainer';
import {
  Search,
  Loader2,
  Save,
  FileEdit,
  Hash,
  User,
  X,
  CheckCircle2,
} from 'lucide-react';

type TargetRecord = {
  target_id: number;
  kra_category: number;
  activity: string;
  rating_id: number | null;
  item_id: number;
  new_semester: number;
  description: string;
  rg_quantity: string;
  rg_quality: string;
  rg_timeliness: string;
  rg_movs: string;
  rg_remarks: string;
  target_year: string | number | null;
  rating_semester: number | null;
  rating_lock: number | null;
  staff_name: string;
  staff_email: string | null;
};

type Props = {
  appName?: string;
  user?: { name: string; email: string } | null;
  filters: {
    target_id: string;
  };
  targets: {
    data: TargetRecord[];
    total: number;
  };
  categories: Array<{ value: string; label: string }>;
  semesters: Array<{ value: string; label: string }>;
  navigation?: { sidebar?: any[] };
};

export default function ModifyTarget({
  appName = '4Ps PATH v3',
  user,
  filters,
  targets,
  categories,
  semesters,
  navigation,
}: Props) {
  // Filter form state (only Target ID)
  const filterForm = useForm({
    target_id: filters.target_id || '',
  });

  // Local editing row states keyed by item_id
  const [rowEdits, setRowEdits] = useState<
    Record<
      number,
      {
        target_id: number;
        kra_category: number;
        activity: string;
        new_semester: number;
        description: string;
        rg_quantity: string;
        rg_quality: string;
        rg_timeliness: string;
        rg_movs: string;
        rg_remarks: string;
      }
    >
  >({});

  const [savingRows, setSavingRows] = useState<Record<number, boolean>>({});
  const [savedStatus, setSavedStatus] = useState<Record<number, boolean>>({});
  const saveTimerRef = useRef<Record<number, any>>({});
  const lastSavedRef = useRef<Record<number, any>>({});

  // Sync initial targets data into editable local state
  useEffect(() => {
    const initialMap: Record<number, any> = {};
    const baselineMap: Record<number, any> = {};

    (targets.data || []).forEach((row) => {
      const state = {
        target_id: row.target_id,
        kra_category: row.kra_category,
        activity: row.activity || '',
        new_semester: row.new_semester,
        description: row.description || '',
        rg_quantity: row.rg_quantity || '',
        rg_quality: row.rg_quality || '',
        rg_timeliness: row.rg_timeliness || '',
        rg_movs: row.rg_movs || '',
        rg_remarks: row.rg_remarks || '',
      };
      initialMap[row.item_id] = { ...state };
      baselineMap[row.item_id] = { ...state };
    });

    setRowEdits(initialMap);
    lastSavedRef.current = baselineMap;
  }, [targets.data]);

  const handleFieldChange = (
    itemId: number,
    field: string,
    value: string | number
  ) => {
    setRowEdits((prev) => ({
      ...prev,
      [itemId]: {
        ...prev[itemId],
        [field]: value,
      },
    }));

    setSavedStatus((prev) => ({
      ...prev,
      [itemId]: false,
    }));
  };

  const saveRow = async (itemId: number, explicit = false) => {
    const current = rowEdits[itemId];
    if (!current) return;

    const last = lastSavedRef.current[itemId];
    if (
      !explicit &&
      last &&
      last.activity === current.activity &&
      last.kra_category === current.kra_category &&
      last.new_semester === current.new_semester &&
      last.description === current.description &&
      last.rg_quantity === current.rg_quantity &&
      last.rg_quality === current.rg_quality &&
      last.rg_timeliness === current.rg_timeliness
    ) {
      return;
    }

    setSavingRows((prev) => ({ ...prev, [itemId]: true }));

    try {
      const csrfToken =
        (document.querySelector('meta[name="csrf-token"]') as HTMLMetaElement)
          ?.content || '';

      const res = await fetch(
        `/administration/adjustment/modify-target/${itemId}/row`,
        {
          method: 'PATCH',
          headers: {
            'Content-Type': 'application/json',
            Accept: 'application/json',
            'X-Requested-With': 'XMLHttpRequest',
            'X-CSRF-TOKEN': csrfToken,
          },
          body: JSON.stringify(current),
        }
      );

      if (res.ok) {
        lastSavedRef.current[itemId] = { ...current };
        setSavedStatus((prev) => ({ ...prev, [itemId]: true }));
        if (explicit) {
          toast.success(
            `Target #${current.target_id} (Item #${itemId}) saved successfully.`
          );
        }
        setTimeout(() => {
          setSavedStatus((prev) => ({ ...prev, [itemId]: false }));
        }, 3000);
      } else {
        const data = await res.json().catch(() => ({}));
        toast.error(data.message || 'Failed to save target changes.');
      }
    } catch (e) {
      console.error('Error saving target modification:', e);
      toast.error('An unexpected error occurred while saving.');
    } finally {
      setSavingRows((prev) => ({ ...prev, [itemId]: false }));
    }
  };

  const scheduleDebouncedSave = (itemId: number) => {
    if (saveTimerRef.current[itemId]) {
      clearTimeout(saveTimerRef.current[itemId]);
    }
    saveTimerRef.current[itemId] = setTimeout(() => {
      saveRow(itemId, false);
    }, 1000);
  };

  const searchTimerRef = useRef<NodeJS.Timeout | null>(null);

  const handleTargetIdChange = (val: string) => {
    filterForm.setData('target_id', val);
    if (searchTimerRef.current) clearTimeout(searchTimerRef.current);
    searchTimerRef.current = setTimeout(() => {
      router.post(
        '/administration/adjustment/modify-target',
        { target_id: val.trim() },
        { preserveState: true, replace: true }
      );
    }, 400);
  };

  const handleSearch = (e?: React.FormEvent) => {
    if (e) e.preventDefault();
    if (searchTimerRef.current) clearTimeout(searchTimerRef.current);
    router.post(
      '/administration/adjustment/modify-target',
      { target_id: filterForm.data.target_id.trim() },
      { preserveState: true, replace: true }
    );
  };

  const handleClear = () => {
    if (searchTimerRef.current) clearTimeout(searchTimerRef.current);
    filterForm.setData('target_id', '');
    router.post(
      '/administration/adjustment/modify-target',
      { target_id: '' },
      { replace: true, preserveState: true }
    );
  };

  const getKraCategoryLabel = (cat: number) => {
    if (cat === 1) return 'Strategic Function';
    if (cat === 2) return 'Core Function';
    if (cat === 3) return 'Support Function';
    return `Category ${cat}`;
  };

  const isTargetSearched = Boolean(filters.target_id && filters.target_id.trim() !== '');

  return (
    <AppLayout
      appName={appName}
      user={user}
      sidebar={navigation?.sidebar ?? []}
    >
      <Head title="Modify Target - Admin Adjustment" />

      <div className="space-y-4">
        {/* SEARCH CARD - TARGET ID ONLY */}
        <div className="rounded-xl border border-border bg-card p-3.5 sm:p-4 shadow-2xs space-y-3.5">
          <div className="flex items-center gap-3 border-b border-border/80 pb-3">
            <div className="size-9 rounded-lg bg-emerald-500/10 text-emerald-700 dark:text-emerald-400 flex items-center justify-center font-bold">
              <FileEdit className="size-5" />
            </div>
            <div>
              <h1 className="text-sm font-bold tracking-tight text-foreground flex items-center gap-2">
                <span>Modify Target Form</span>
                {targets.total > 0 && (
                  <span className="rounded-full bg-emerald-500/10 text-emerald-700 dark:text-emerald-400 font-mono text-[10px] font-bold px-2 py-0.2 border border-emerald-500/20">
                    {targets.total} Item{targets.total > 1 ? 's' : ''} Found
                  </span>
                )}
              </h1>
              <p className="text-[11px] text-muted-foreground">
                Enter a Target ID (
                <code className="text-foreground font-semibold">
                  ipc_sem_targets_indicator.id
                </code>
                ) to view and modify activity, KRA category, semester, and rating guides in card forms.
              </p>
            </div>
          </div>

          {/* TARGET ID SEARCH FORM */}
          <form onSubmit={handleSearch} className="flex flex-col sm:flex-row items-stretch sm:items-end gap-2.5 max-w-xl">
            <div className="space-y-1 flex-1">
              <label className="text-[11px] font-semibold text-muted-foreground flex items-center gap-1.5">
                <Hash className="size-3 text-emerald-600 dark:text-emerald-400" />
                <span>Target ID (ipc_sem_targets_indicator.id)</span>
              </label>
              <div className="relative">
                <Hash className="size-3.5 absolute left-2.5 top-1/2 -translate-y-1/2 text-muted-foreground" />
                <input
                  type="text"
                  value={filterForm.data.target_id}
                  onChange={(e) => handleTargetIdChange(e.target.value)}
                  placeholder="Enter Target ID (e.g. 499)..."
                  className="h-9 w-full rounded-lg border border-input bg-background pl-8 pr-8 text-xs text-foreground placeholder:text-muted-foreground/60 outline-hidden focus:ring-2 focus:ring-ring font-mono"
                  autoFocus
                />
                {filterForm.data.target_id && (
                  <button
                    type="button"
                    onClick={handleClear}
                    className="absolute right-2.5 top-1/2 -translate-y-1/2 text-muted-foreground hover:text-foreground cursor-pointer"
                  >
                    <X className="size-3.5" />
                  </button>
                )}
              </div>
            </div>

            <button
              type="submit"
              className="h-9 inline-flex items-center justify-center gap-1.5 rounded-lg bg-emerald-600 hover:bg-emerald-700 text-white px-4 text-xs font-semibold shadow-xs transition cursor-pointer"
            >
              <Search className="size-3.5" />
              <span>Search Target</span>
            </button>
          </form>
        </div>

        {/* RESULTS: CARD FORMS */}
        {targets.data.length === 0 ? (
          <div className="rounded-xl border border-border bg-card p-12 text-center text-muted-foreground shadow-2xs">
            {isTargetSearched ? (
              <div className="flex flex-col items-center justify-center gap-2 max-w-md mx-auto">
                <div className="size-10 rounded-full bg-muted flex items-center justify-center text-muted-foreground mb-1">
                  <Search className="size-5" />
                </div>
                <p className="text-xs font-bold text-foreground">
                  Target ID #{filters.target_id} not found
                </p>
                <p className="text-[11px] text-muted-foreground">
                  No indicator record exists with Target ID #{filters.target_id}. Please check the ID and try again.
                </p>
              </div>
            ) : (
              <div className="flex flex-col items-center justify-center gap-2.5 max-w-md mx-auto">
                <div className="size-10 rounded-full bg-emerald-500/10 text-emerald-700 dark:text-emerald-400 flex items-center justify-center mb-1 border border-emerald-500/20">
                  <Hash className="size-5" />
                </div>
                <p className="text-xs font-bold text-foreground">
                  No Target Loaded
                </p>
                <p className="text-[11px] text-muted-foreground leading-relaxed">
                  Please enter a <span className="font-semibold text-foreground">Target ID</span> (e.g. 499) above and click <span className="font-semibold text-foreground">Search Target</span> to view and modify the target.
                </p>
              </div>
            )}
          </div>
        ) : (
          <div className="space-y-4">
            {targets.data.map((row) => {
              const edit = rowEdits[row.item_id] || {
                target_id: row.target_id,
                kra_category: row.kra_category,
                activity: row.activity || '',
                new_semester: row.new_semester,
                description: row.description || '',
                rg_quantity: row.rg_quantity || '',
                rg_quality: row.rg_quality || '',
                rg_timeliness: row.rg_timeliness || '',
                rg_movs: row.rg_movs || '',
                rg_remarks: row.rg_remarks || '',
              };

              const isSaving = Boolean(savingRows[row.item_id]);
              const isSaved = Boolean(savedStatus[row.item_id]);

              return (
                <div
                  key={`card-${row.item_id}`}
                  className="rounded-xl border border-border bg-card shadow-2xs overflow-hidden transition hover:border-border/90"
                >
                  {/* CARD HEADER */}
                  <div className="bg-muted/40 px-4 py-3 border-b border-border flex flex-col sm:flex-row sm:items-center justify-between gap-3">
                    <div className="flex flex-wrap items-center gap-2">
                      {/* Target ID Badge */}
                      <span className="inline-flex items-center gap-1 rounded-md bg-emerald-500/10 text-emerald-700 dark:text-emerald-400 font-mono font-bold text-xs px-2.5 py-1 border border-emerald-500/20">
                        <Hash className="size-3.5" />
                        <span>Target ID: #{row.target_id}</span>
                      </span>

                      {/* Item ID Badge */}
                      <span className="inline-flex items-center gap-1 rounded-md bg-sky-500/10 text-sky-700 dark:text-sky-400 font-mono text-xs px-2 py-0.5 border border-sky-500/20">
                        <span>Item ID: #{row.item_id}</span>
                      </span>

                      {row.rating_id && (
                        <span className="inline-flex items-center gap-1 rounded-md bg-muted px-2 py-0.5 text-xs text-muted-foreground border border-border">
                          <span>Rating #{row.rating_id}</span>
                        </span>
                      )}

                      {row.staff_name && row.staff_name !== 'N/A' && (
                        <span className="inline-flex items-center gap-1.5 text-xs text-foreground font-medium pl-1">
                          <User className="size-3.5 text-muted-foreground" />
                          <span>{row.staff_name}</span>
                          {row.target_year && (
                            <span className="text-[11px] text-muted-foreground">({row.target_year})</span>
                          )}
                        </span>
                      )}
                    </div>

                    {/* Header Action / Save Button */}
                    <div className="flex items-center gap-2.5">
                      {isSaved && (
                        <span className="inline-flex items-center gap-1 text-xs font-semibold text-emerald-600 dark:text-emerald-400 animate-in fade-in">
                          <CheckCircle2 className="size-4" />
                          <span>Saved</span>
                        </span>
                      )}

                      {isSaving && (
                        <span className="inline-flex items-center gap-1 text-xs text-muted-foreground animate-pulse">
                          <Loader2 className="size-3.5 animate-spin" />
                          <span>Saving...</span>
                        </span>
                      )}

                      <button
                        type="button"
                        disabled={isSaving}
                        onClick={() => saveRow(row.item_id, true)}
                        className="h-8 inline-flex items-center gap-1.5 rounded-lg bg-emerald-600 hover:bg-emerald-700 disabled:opacity-50 text-white px-3.5 text-xs font-semibold shadow-xs transition cursor-pointer"
                        title="Save target changes"
                      >
                        {isSaving ? (
                          <Loader2 className="size-3.5 animate-spin" />
                        ) : (
                          <Save className="size-3.5" />
                        )}
                        <span>Save Changes</span>
                      </button>
                    </div>
                  </div>

                  {/* CARD BODY */}
                  <div className="p-4 sm:p-5 space-y-4">
                    {/* Category & Semester Selectors */}
                    <div className="grid gap-3 sm:grid-cols-2">
                      <div className="space-y-1">
                        <label className="text-[11px] font-bold uppercase tracking-wider text-muted-foreground flex items-center justify-between">
                          <span>KRA Category</span>
                          <span className="text-[10px] text-emerald-600 dark:text-emerald-400 font-semibold normal-case">
                            {getKraCategoryLabel(edit.kra_category)}
                          </span>
                        </label>
                        <select
                          value={edit.kra_category}
                          onChange={(e) => {
                            const val = Number(e.target.value);
                            handleFieldChange(row.item_id, 'kra_category', val);
                            scheduleDebouncedSave(row.item_id);
                          }}
                          onBlur={() => saveRow(row.item_id, false)}
                          className="h-9 w-full rounded-lg border border-input bg-background px-3 text-xs text-foreground font-medium focus:outline-none focus:ring-2 focus:ring-ring cursor-pointer"
                        >
                          <option value={1}>Strategic Function</option>
                          <option value={2}>Core Function</option>
                          <option value={3}>Support Function</option>
                        </select>
                      </div>

                      <div className="space-y-1">
                        <label className="text-[11px] font-bold uppercase tracking-wider text-muted-foreground">
                          Semester
                        </label>
                        <select
                          value={edit.new_semester}
                          onChange={(e) => {
                            const val = Number(e.target.value);
                            handleFieldChange(row.item_id, 'new_semester', val);
                            scheduleDebouncedSave(row.item_id);
                          }}
                          onBlur={() => saveRow(row.item_id, false)}
                          className="h-9 w-full rounded-lg border border-input bg-background px-3 text-xs text-foreground font-medium focus:outline-none focus:ring-2 focus:ring-ring cursor-pointer"
                        >
                          <option value={1}>1st Semester</option>
                          <option value={2}>2nd Semester</option>
                        </select>
                      </div>
                    </div>

                    {/* Section: Key Result Area (Activity) */}
                    <div className="space-y-1">
                      <label className="text-[11px] font-bold uppercase tracking-wider text-muted-foreground">
                        Key Result Area (Activity)
                      </label>
                      <AutoResizingTextarea
                        value={edit.activity}
                        onChange={(e) => {
                          handleFieldChange(row.item_id, 'activity', e.target.value);
                          scheduleDebouncedSave(row.item_id);
                        }}
                        onBlur={() => saveRow(row.item_id, false)}
                        rows={2}
                        placeholder="Enter Key Result Area / Activity..."
                        className="w-full rounded-lg border border-input bg-background p-2.5 text-xs text-foreground leading-relaxed focus:outline-none focus:ring-2 focus:ring-ring shadow-inner"
                      />
                    </div>

                    {/* Section: Success Indicator (Description) */}
                    <div className="space-y-1">
                      <label className="text-[11px] font-bold uppercase tracking-wider text-muted-foreground">
                        Success Indicator (Description)
                      </label>
                      <AutoResizingTextarea
                        value={edit.description}
                        onChange={(e) => {
                          handleFieldChange(row.item_id, 'description', e.target.value);
                          scheduleDebouncedSave(row.item_id);
                        }}
                        onBlur={() => saveRow(row.item_id, false)}
                        rows={2}
                        placeholder="Enter Success Indicator description..."
                        className="w-full rounded-lg border border-input bg-background p-2.5 text-xs text-foreground leading-relaxed focus:outline-none focus:ring-2 focus:ring-ring shadow-inner"
                      />
                    </div>

                    {/* Performance Measures & Rating Guides */}
                    <div className="space-y-2 pt-1 border-t border-border/80">
                      <div className="text-[11px] font-bold uppercase tracking-wider text-muted-foreground">
                        Performance Measures &amp; Rating Guides
                      </div>
                      <div className="grid gap-3 sm:grid-cols-3">
                        {/* Efficiency */}
                        <div className="space-y-1">
                          <label className="text-[11px] font-semibold text-foreground flex items-center gap-1.5">
                            <span className="size-2 rounded-full bg-emerald-500" />
                            <span>EFFICIENCY (RG Quantity)</span>
                          </label>
                          <AutoResizingTextarea
                            value={edit.rg_quantity}
                            onChange={(e) => {
                              handleFieldChange(row.item_id, 'rg_quantity', e.target.value);
                              scheduleDebouncedSave(row.item_id);
                            }}
                            onBlur={() => saveRow(row.item_id, false)}
                            rows={3}
                            placeholder="Quantity / Efficiency rating guide..."
                            className="w-full rounded-lg border border-input bg-background p-2.5 text-xs text-foreground leading-relaxed focus:outline-none focus:ring-2 focus:ring-ring shadow-inner"
                          />
                        </div>

                        {/* Quality */}
                        <div className="space-y-1">
                          <label className="text-[11px] font-semibold text-foreground flex items-center gap-1.5">
                            <span className="size-2 rounded-full bg-sky-500" />
                            <span>QUALITY (RG Quality)</span>
                          </label>
                          <AutoResizingTextarea
                            value={edit.rg_quality}
                            onChange={(e) => {
                              handleFieldChange(row.item_id, 'rg_quality', e.target.value);
                              scheduleDebouncedSave(row.item_id);
                            }}
                            onBlur={() => saveRow(row.item_id, false)}
                            rows={3}
                            placeholder="Quality rating guide..."
                            className="w-full rounded-lg border border-input bg-background p-2.5 text-xs text-foreground leading-relaxed focus:outline-none focus:ring-2 focus:ring-ring shadow-inner"
                          />
                        </div>

                        {/* Timeliness */}
                        <div className="space-y-1">
                          <label className="text-[11px] font-semibold text-foreground flex items-center gap-1.5">
                            <span className="size-2 rounded-full bg-amber-500" />
                            <span>TIMELINESS (RG Timeliness)</span>
                          </label>
                          <AutoResizingTextarea
                            value={edit.rg_timeliness}
                            onChange={(e) => {
                              handleFieldChange(row.item_id, 'rg_timeliness', e.target.value);
                              scheduleDebouncedSave(row.item_id);
                            }}
                            onBlur={() => saveRow(row.item_id, false)}
                            rows={3}
                            placeholder="Timeliness rating guide..."
                            className="w-full rounded-lg border border-input bg-background p-2.5 text-xs text-foreground leading-relaxed focus:outline-none focus:ring-2 focus:ring-ring shadow-inner"
                          />
                        </div>
                      </div>
                    </div>
                  </div>
                </div>
              );
            })}
          </div>
        )}
      </div>
    </AppLayout>
  );
}

import { Head, router, useForm } from '@inertiajs/react';
import { useRef, useState } from 'react';
import AppLayout from '../../Layouts/AppLayout';
import { readPersistedFilters, savePersistedFilters } from '../../lib/filterPersistence';
import { Pencil, Trash2, Plus, RotateCcw, Search, ChevronLeft, ChevronRight, X } from 'lucide-react';

type SectionRow = {
  id: number;
  sectionName: string;
  divisionId: number;
  secAcronym: string | null;
  secStatus: number;
};

type DivisionOption = { id: number; name: string };

type Props = {
  appName: string;
  user: { name: string; email: string } | null;
  filters: { search: string; perPage: number };
  sections: {
    data: SectionRow[];
    from: number | null;
    to: number | null;
    total: number;
    currentPage: number;
    lastPage: number;
  };
  divisions: DivisionOption[];
  perPageOptions: Array<{ value: number; label: string }>;
  navigation?: { sidebar?: any[] };
};

export default function Section({ appName, user, filters, sections, divisions, perPageOptions, navigation }: Props) {
  const pageKey = 'libraries-section';
  const filterForm = useForm(readPersistedFilters(pageKey, user, {
    search: filters.search,
    perPage: String(filters.perPage),
  }));
  const [showModal, setShowModal] = useState(false);
  const [editingId, setEditingId] = useState<number | null>(null);
  const [deletingId, setDeletingId] = useState<number | null>(null);
  const sectionForm = useForm({
    sectionName: '',
    divisionId: '',
    secAcronym: '',
    secStatus: '1',
  });
  const searchTimerRef = useRef<NodeJS.Timeout | null>(null);

  const submitFilters = (overrides = {}) => {
    if (searchTimerRef.current) clearTimeout(searchTimerRef.current);
    const data = { ...filterForm.data, ...overrides };
    savePersistedFilters(pageKey, user, data);
    router.post('/libraries/section', data, { preserveState: true, replace: true, preserveScroll: true });
  };

  const handleSearchChange = (val: string) => {
    filterForm.setData('search', val);
    if (searchTimerRef.current) clearTimeout(searchTimerRef.current);
    searchTimerRef.current = setTimeout(() => submitFilters({ search: val, page: 1 }), 350);
  };

  const resetFilters = () => {
    if (searchTimerRef.current) clearTimeout(searchTimerRef.current);
    filterForm.setData({ search: '', perPage: '10' });
    savePersistedFilters(pageKey, user, { search: '', perPage: '10' });
    router.post('/libraries/section', { search: '', perPage: '10', page: 1 }, { replace: true, preserveState: true });
  };

  const openCreateModal = () => {
    setEditingId(null);
    sectionForm.setData({ sectionName: '', divisionId: '', secAcronym: '', secStatus: '1' });
    setShowModal(true);
  };

  const openEditModal = (row: SectionRow) => {
    setEditingId(row.id);
    sectionForm.setData({
      sectionName: row.sectionName,
      divisionId: String(row.divisionId),
      secAcronym: row.secAcronym || '',
      secStatus: String(row.secStatus),
    });
    setShowModal(true);
  };

  const handleFormSubmit = (e: React.FormEvent) => {
    e.preventDefault();
    if (editingId !== null) {
      sectionForm.patch(`/libraries/section/${editingId}`, { onSuccess: () => { setShowModal(false); setEditingId(null); } });
    } else {
      sectionForm.post('/libraries/section', { onSuccess: () => setShowModal(false) });
    }
  };

  return (
    <AppLayout appName={appName} user={user} sidebar={navigation?.sidebar ?? []}>
      <Head title="Section Library - Libraries" />

      <div className="space-y-3">
        <div className="rounded-xl border border-border bg-card p-3 sm:p-4 shadow-2xs">
          <div className="flex flex-col sm:flex-row sm:items-center justify-between gap-2 border-b border-border/80 pb-3 mb-3">
            <div className="flex items-center gap-2.5">
              <div className="size-8 rounded-lg bg-emerald-500/10 text-emerald-700 dark:text-emerald-400 flex items-center justify-center font-bold">
                <Plus className="size-4.5" />
              </div>
              <div>
                <h1 className="text-sm font-bold tracking-tight text-foreground flex items-center gap-2">
                  <span>Section Library</span>
                  <span className="rounded-full bg-emerald-500/10 text-emerald-700 dark:text-emerald-400 font-mono text-[10px] font-bold px-2 py-0.2 border border-emerald-500/20">
                    {sections.total} Total Sections
                  </span>
                </h1>
                <p className="text-[11px] text-muted-foreground">Manage section names, acronyms, division links, and statuses.</p>
              </div>
            </div>
            <div className="flex items-center gap-2">
              <button type="button" onClick={resetFilters} className="h-8 inline-flex items-center gap-1.5 rounded-lg border border-input bg-background px-2.5 text-xs font-medium text-muted-foreground hover:text-foreground hover:bg-muted transition cursor-pointer">
                <RotateCcw className="size-3" /><span>Reset</span>
              </button>
              <button type="button" onClick={openCreateModal} className="h-8 inline-flex items-center gap-1.5 rounded-lg bg-emerald-600 text-white px-3 text-xs font-semibold hover:bg-emerald-700 transition shadow-xs cursor-pointer">
                <Plus className="size-3.5" /><span>Add Section</span>
              </button>
            </div>
          </div>

          <div className="grid gap-2.5 sm:grid-cols-2 lg:grid-cols-4">
            <div className="space-y-1 sm:col-span-2">
              <label className="text-[11px] font-semibold text-muted-foreground">Search Section</label>
              <div className="relative">
                <Search className="size-3.5 absolute left-2.5 top-1/2 -translate-y-1/2 text-muted-foreground" />
                <input value={filterForm.data.search} onChange={(e) => handleSearchChange(e.target.value)} placeholder="Section name or acronym..." className="h-8 w-full rounded-lg border border-input bg-background pl-8 pr-7 text-xs text-foreground placeholder:text-muted-foreground/60 outline-hidden focus:ring-2 focus:ring-ring" />
                {filterForm.data.search && (
                  <button type="button" onClick={() => { filterForm.setData('search', ''); if (searchTimerRef.current) clearTimeout(searchTimerRef.current); submitFilters({ search: '', page: 1 }); }} className="absolute right-2 top-1/2 -translate-y-1/2 text-muted-foreground hover:text-foreground">
                    <X className="size-3" />
                  </button>
                )}
              </div>
            </div>
            <div className="space-y-1">
              <label className="text-[11px] font-semibold text-muted-foreground">Per Page</label>
              <select value={filterForm.data.perPage} onChange={(e) => { filterForm.setData('perPage', e.target.value); submitFilters({ perPage: e.target.value, page: 1 }); }} className="h-8 w-full rounded-lg border border-input bg-background px-2.5 text-xs text-foreground outline-hidden focus:ring-2 focus:ring-ring cursor-pointer">
                {perPageOptions.map((opt) => <option key={opt.value} value={opt.value}>{opt.label}</option>)}
              </select>
            </div>
          </div>
        </div>

        <div className="rounded-xl border border-border bg-card shadow-2xs overflow-hidden">
          <div className="overflow-x-auto">
            <table className="w-full min-w-[900px] border-collapse text-xs text-left">
              <thead>
                <tr className="bg-muted/60 text-[11px] font-bold uppercase tracking-wider text-muted-foreground border-b border-border">
                  <th className="px-3 py-2 text-center w-12 border-r border-border">#</th>
                  <th className="px-3 py-2 border-r border-border">Section Name</th>
                  <th className="px-3 py-2 border-r border-border">Acronym</th>
                  <th className="px-3 py-2 border-r border-border">Division</th>
                  <th className="px-3 py-2 border-r border-border text-center w-24">Status</th>
                  <th className="px-3 py-2 text-center w-24">Action</th>
                </tr>
              </thead>
              <tbody className="divide-y divide-border">
                {sections.data.length ? sections.data.map((row, index) => (
                  <tr key={row.id} className="hover:bg-muted/30 transition-colors">
                    <td className="px-3 py-2 text-center font-mono text-[11px] text-muted-foreground border-r border-border">{(sections.from ?? 1) + index}</td>
                    <td className="px-3 py-2 font-bold text-foreground border-r border-border">{row.sectionName}</td>
                    <td className="px-3 py-2 border-r border-border">{row.secAcronym || '—'}</td>
                    <td className="px-3 py-2 border-r border-border">{divisions.find((d) => d.id === row.divisionId)?.name ?? row.divisionId}</td>
                    <td className="px-3 py-2 border-r border-border text-center">{row.secStatus ? <span className="inline-flex rounded-full bg-emerald-500/10 text-emerald-700 px-2 py-0.5 text-[10px] font-bold border border-emerald-500/20">Active</span> : <span className="inline-flex rounded-full bg-muted text-muted-foreground px-2 py-0.5 text-[10px] font-bold border border-border">Inactive</span>}</td>
                    <td className="px-3 py-2 text-center"><div className="inline-flex items-center justify-center gap-1"><button type="button" onClick={() => openEditModal(row)} className="p-1 rounded-md text-muted-foreground hover:text-foreground hover:bg-muted transition cursor-pointer"><Pencil className="size-3.5" /></button><button type="button" onClick={() => setDeletingId(row.id)} className="p-1 rounded-md text-rose-600 hover:bg-rose-500/10 transition cursor-pointer"><Trash2 className="size-3.5" /></button></div></td>
                  </tr>
                )) : (
                  <tr><td colSpan={6} className="px-4 py-12 text-center"><div className="flex flex-col items-center justify-center space-y-2"><div className="size-10 rounded-full bg-muted flex items-center justify-center text-muted-foreground"><Search className="size-5" /></div><p className="text-xs font-bold text-foreground">No sections found</p><p className="text-[11px] text-muted-foreground max-w-sm">No section records matched your filter criteria.</p></div></td></tr>
                )}
              </tbody>
            </table>
          </div>

          {sections.lastPage > 1 && (
            <div className="border-t border-border px-3.5 py-2.5 flex flex-col sm:flex-row sm:items-center justify-between gap-2.5 bg-muted/20">
              <div className="text-[11px] text-muted-foreground">Showing <span className="font-bold text-foreground">{sections.from ?? 0}</span> to <span className="font-bold text-foreground">{sections.to ?? 0}</span> of <span className="font-bold text-foreground">{sections.total}</span> records</div>
              <div className="flex items-center gap-1 flex-wrap">
                {sections.currentPage === 1 ? <span className="h-7 min-w-7 px-2 rounded-md flex items-center justify-center text-[11px] text-muted-foreground/50 border border-transparent select-none"><ChevronLeft className="size-3.5" /></span> : <button type="button" onClick={() => submitFilters({ page: sections.currentPage - 1 })} className="h-7 min-w-7 px-2 rounded-md flex items-center justify-center text-[11px] font-medium border border-input bg-background text-foreground hover:bg-muted transition cursor-pointer"><ChevronLeft className="size-3.5" /></button>}
                {Array.from({ length: sections.lastPage }, (_, i) => i + 1).map((page) => {
                  const isActive = page === sections.currentPage;
                  return <button key={page} type="button" data-pagination-number={page} onClick={() => submitFilters({ page })} className={`h-7 min-w-7 px-2 rounded-md flex items-center justify-center text-[11px] font-medium transition-colors cursor-pointer ${isActive ? 'bg-emerald-600 text-white font-bold shadow-2xs' : 'border border-input bg-background text-foreground hover:bg-muted'}`}>{page}</button>;
                })}
                {sections.currentPage === sections.lastPage ? <span className="h-7 min-w-7 px-2 rounded-md flex items-center justify-center text-[11px] text-muted-foreground/50 border border-transparent select-none"><ChevronRight className="size-3.5" /></span> : <button type="button" onClick={() => submitFilters({ page: sections.currentPage + 1 })} className="h-7 min-w-7 px-2 rounded-md flex items-center justify-center text-[11px] font-medium border border-input bg-background text-foreground hover:bg-muted transition cursor-pointer"><ChevronRight className="size-3.5" /></button>}
              </div>
            </div>
          )}
        </div>

        {showModal ? (
          <div className="fixed inset-0 z-50 flex items-center justify-center bg-black/60 backdrop-blur-xs p-4 animate-in fade-in duration-150">
            <div className="w-full max-w-2xl rounded-xl border border-border bg-card p-5 shadow-2xl space-y-4">
              <div className="flex items-start justify-between">
                <div>
                  <h3 className="text-sm font-bold text-foreground">{editingId ? 'Edit Section' : 'Add Section'}</h3>
                  <p className="text-xs text-muted-foreground mt-0.5">{editingId ? 'Update section details.' : 'Enter new section details.'}</p>
                </div>
                <button type="button" onClick={() => setShowModal(false)} className="text-muted-foreground hover:text-foreground p-1 rounded-md transition"><X className="size-4" /></button>
              </div>
              <form onSubmit={handleFormSubmit} className="grid gap-3 sm:grid-cols-2">
                <div className="space-y-1 sm:col-span-2">
                  <label className="text-[11px] font-semibold text-muted-foreground">Section Name</label>
                  <input value={sectionForm.data.sectionName} onChange={(e) => sectionForm.setData('sectionName', e.target.value)} className="h-8 w-full rounded-lg border border-input bg-background px-2.5 text-xs text-foreground outline-hidden focus:ring-2 focus:ring-ring" required />
                </div>
                <div className="space-y-1">
                  <label className="text-[11px] font-semibold text-muted-foreground">Division</label>
                  <select value={sectionForm.data.divisionId} onChange={(e) => sectionForm.setData('divisionId', e.target.value)} className="h-8 w-full rounded-lg border border-input bg-background px-2.5 text-xs text-foreground outline-hidden focus:ring-2 focus:ring-ring cursor-pointer" required>
                    <option value="">Select division</option>
                    {divisions.map((division) => <option key={division.id} value={division.id}>{division.name}</option>)}
                  </select>
                </div>
                <div className="space-y-1">
                  <label className="text-[11px] font-semibold text-muted-foreground">Status</label>
                  <select value={sectionForm.data.secStatus} onChange={(e) => sectionForm.setData('secStatus', e.target.value)} className="h-8 w-full rounded-lg border border-input bg-background px-2.5 text-xs text-foreground outline-hidden focus:ring-2 focus:ring-ring cursor-pointer">
                    <option value="1">Active</option>
                    <option value="0">Inactive</option>
                  </select>
                </div>
                <div className="space-y-1 sm:col-span-2">
                  <label className="text-[11px] font-semibold text-muted-foreground">Acronym</label>
                  <input value={sectionForm.data.secAcronym} onChange={(e) => sectionForm.setData('secAcronym', e.target.value)} className="h-8 w-full rounded-lg border border-input bg-background px-2.5 text-xs text-foreground outline-hidden focus:ring-2 focus:ring-ring" />
                </div>
                <div className="flex justify-end gap-2 pt-3 border-t border-border sm:col-span-2">
                  <button type="button" onClick={() => setShowModal(false)} className="px-3 py-1.5 rounded-lg border border-input bg-background text-xs font-semibold text-foreground hover:bg-muted transition">Cancel</button>
                  <button type="submit" disabled={sectionForm.processing} className="px-3.5 py-1.5 rounded-lg bg-emerald-600 hover:bg-emerald-700 text-white text-xs font-semibold shadow-xs transition">{editingId ? 'Save Changes' : 'Create Section'}</button>
                </div>
              </form>
            </div>
          </div>
        ) : null}

        {deletingId !== null ? (
          <div className="fixed inset-0 z-50 flex items-center justify-center bg-black/60 backdrop-blur-xs p-4 animate-in fade-in duration-150">
            <div className="w-full max-w-md rounded-xl border border-border bg-card p-5 shadow-2xl space-y-4">
              <div className="flex items-start justify-between">
                <div>
                  <h3 className="text-sm font-bold text-foreground">Delete Section</h3>
                  <p className="text-xs text-muted-foreground mt-0.5">This will permanently delete the selected section record.</p>
                </div>
                <button type="button" onClick={() => setDeletingId(null)} className="text-muted-foreground hover:text-foreground p-1 rounded-md transition"><X className="size-4" /></button>
              </div>
              <div className="flex justify-end gap-2 pt-2 border-t border-border">
                <button type="button" onClick={() => setDeletingId(null)} className="px-3 py-1.5 rounded-lg border border-input bg-background text-xs font-semibold text-foreground hover:bg-muted transition">Cancel</button>
                <button type="button" onClick={() => router.delete(`/libraries/section/${deletingId}`, { onSuccess: () => setDeletingId(null) })} className="px-3.5 py-1.5 rounded-lg bg-rose-600 hover:bg-rose-700 text-white text-xs font-semibold shadow-xs transition">Confirm &amp; Delete</button>
              </div>
            </div>
          </div>
        ) : null}
      </div>
    </AppLayout>
  );
}

import { Head, Link, router, useForm } from '@inertiajs/react';
import { useRef, useState } from 'react';
import AppLayout from '../../Layouts/AppLayout';
import UserAvatar from '../../Components/UserAvatar';
import { Pencil, Trash2, Users as UsersIcon, Search, RotateCcw, X, Camera } from 'lucide-react';

type UserRow = {
  id: number;
  lastName: string;
  firstName: string;
  middleName: string;
  extensionName: string;
  fullName: string;
  email: string;
  contactNumber: string | null;
  position: string | null;
  designation: string | null;
  divisionId: string;
  divisionName: string;
  sectionId: string;
  sectionName: string;
  supervisorId: string;
  userLevelId: string;
  userLevelName: string | null;
  isSupervisor: boolean;
  canScorecard?: number;
  avatar?: string | null;
  avatarUrl?: string | null;
  isStatus: number;
};

type OptionItem = {
  id: string;
  name: string;
  divisionId?: string;
};

type Props = {
  appName: string;
  user: { name: string; email: string } | null;
  filters: {
    search: string;
    division: string;
    section: string;
    user_level_id: string;
    status: string;
    perPage: number;
  };
  users: {
    data: UserRow[];
    from: number | null;
    to: number | null;
    total: number;
    links: Array<{ url: string | null; label: string; active: boolean }>;
  };
  divisions: OptionItem[];
  sections: OptionItem[];
  supervisors: OptionItem[];
  userLevels: OptionItem[];
  statusOptions: Array<{ value: string; label: string }>;
  perPageOptions: Array<{ value: number; label: string }>;
  navigation?: { sidebar?: any[] };
};

export default function Users({
  appName,
  user,
  filters,
  users,
  divisions,
  sections,
  supervisors,
  userLevels,
  statusOptions,
  perPageOptions,
  navigation,
}: Props) {
  const filterForm = useForm({
    search: filters.search || '',
    division: filters.division || '',
    section: filters.section || '',
    user_level_id: filters.user_level_id || '',
    status: filters.status || '',
    perPage: String(filters.perPage || 10),
  });

  const [editingUser, setEditingUser] = useState<UserRow | null>(null);
  const [showEditModal, setShowEditModal] = useState(false);
  const [deletingUser, setDeletingUser] = useState<UserRow | null>(null);
  const fileInputRef = useRef<HTMLInputElement>(null);
  const [avatarPreview, setAvatarPreview] = useState<string | null>(null);

  const editForm = useForm({
    editLastName: '',
    editFirstName: '',
    editMiddleName: '',
    editExtensionName: '',
    email: '',
    editContactNumber: '',
    editPosition: '',
    editDesignation: '',
    editDivision: '',
    editSection: '',
    editSupervisorId: '',
    editUserLevelId: '',
    editIsSupervisor: false,
    editCanScorecard: false,
  });

  const handleAvatarSelect = (e: React.ChangeEvent<HTMLInputElement>) => {
    const file = e.target.files?.[0];
    if (!file || !editingUser) return;

    const reader = new FileReader();
    reader.onload = (event) => {
      setAvatarPreview(event.target?.result as string);
    };
    reader.readAsDataURL(file);

    const formData = new FormData();
    formData.append('editAvatar', file);
    formData.append('_method', 'PATCH');
    formData.append('editLastName', editForm.data.editLastName);
    formData.append('editFirstName', editForm.data.editFirstName);
    formData.append('editMiddleName', editForm.data.editMiddleName);
    formData.append('editExtensionName', editForm.data.editExtensionName);
    formData.append('editPosition', editForm.data.editPosition);
    formData.append('editDesignation', editForm.data.editDesignation);
    formData.append('editDivision', editForm.data.editDivision);
    formData.append('editSection', editForm.data.editSection);
    formData.append('editSupervisorId', editForm.data.editSupervisorId);
    formData.append('editUserLevelId', editForm.data.editUserLevelId);
    formData.append('editContactNumber', editForm.data.editContactNumber);
    formData.append('editIsSupervisor', editForm.data.editIsSupervisor ? '1' : '0');
    formData.append('editCanScorecard', editForm.data.editCanScorecard ? '1' : '0');

    router.post(`/administration/users/${editingUser.id}`, formData, {
      preserveScroll: true,
      onSuccess: () => {
        setAvatarPreview(null);
      },
    });
  };

  const handleRemoveAvatar = () => {
    if (!editingUser) return;
    router.patch(
      `/administration/users/${editingUser.id}`,
      { ...editForm.data, removeAvatar: true },
      {
        preserveScroll: true,
        onSuccess: () => {
          setAvatarPreview(null);
        },
      }
    );
  };

  const submitFilters = (overrides?: Partial<typeof filterForm.data>) => {
    const data = { ...filterForm.data, ...overrides };
    router.get('/administration/users', data, {
      preserveState: true,
      replace: true,
    });
  };

  const resetFilters = () => {
    filterForm.setData({
      search: '',
      division: '',
      section: '',
      user_level_id: '',
      status: '',
      perPage: '10',
    });
    router.get('/administration/users', {}, { replace: true });
  };

  const openEditModal = (row: UserRow) => {
    setEditingUser(row);
    editForm.setData({
      editLastName: row.lastName || '',
      editFirstName: row.firstName || '',
      editMiddleName: row.middleName || '',
      editExtensionName: row.extensionName || '',
      email: row.email || '',
      editContactNumber: row.contactNumber || '',
      editPosition: row.position || '',
      editDesignation: row.designation || '',
      editDivision: row.divisionId ? String(row.divisionId) : '',
      editSection: row.sectionId ? String(row.sectionId) : '',
      editSupervisorId: row.supervisorId ? String(row.supervisorId) : '',
      editUserLevelId: row.userLevelId ? String(row.userLevelId) : '',
      editIsSupervisor: Boolean(row.isSupervisor),
      editCanScorecard: Number(row.canScorecard) === 1 || Boolean(row.canScorecard),
    });
    setShowEditModal(true);
  };

  const handleEditSubmit = (e: React.FormEvent) => {
    e.preventDefault();
    if (!editingUser) return;

    editForm.patch(`/administration/users/${editingUser.id}`, {
      onSuccess: () => {
        setShowEditModal(false);
        setEditingUser(null);
      },
    });
  };

  const filterSectionOptions = filterForm.data.division
    ? sections.filter((s) => s.divisionId === filterForm.data.division)
    : sections;

  const editSectionOptions = editForm.data.editDivision
    ? sections.filter((s) => s.divisionId === editForm.data.editDivision)
    : sections;

  return (
    <AppLayout appName={appName} user={user} sidebar={navigation?.sidebar ?? []}>
      <Head title="Administration Users" />

      <div className="space-y-3">
        {/* TOP FILTER & ACTION CARD */}
        <div className="rounded-xl border border-border bg-card p-3 sm:p-4 shadow-2xs">
          {/* HEADER */}
          <div className="flex flex-col sm:flex-row sm:items-center justify-between gap-2 border-b border-border/80 pb-3 mb-3">
            <div className="flex items-center gap-2.5">
              <div className="size-8 rounded-lg bg-emerald-500/10 text-emerald-700 dark:text-emerald-400 flex items-center justify-center font-bold">
                <UsersIcon className="size-4.5" />
              </div>
              <div>
                <h1 className="text-sm font-bold tracking-tight text-foreground flex items-center gap-2">
                  <span>Administration Users</span>
                  <span className="rounded-full bg-emerald-500/10 text-emerald-700 dark:text-emerald-400 font-mono text-[10px] font-bold px-2 py-0.2 border border-emerald-500/20">
                    {users.total} Total Users
                  </span>
                </h1>
                <p className="text-[11px] text-muted-foreground">
                  Browse, review, and manage user accounts, permissions, and divisional assignments.
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
            </div>
          </div>

          {/* FILTERS FORM */}
          <div className="grid gap-2.5 sm:grid-cols-2 lg:grid-cols-6">
            {/* Search Input */}
            <div className="space-y-1 sm:col-span-2">
              <label className="text-[11px] font-semibold text-muted-foreground">Search Users</label>
              <div className="relative">
                <Search className="size-3.5 absolute left-2.5 top-1/2 -translate-y-1/2 text-muted-foreground" />
                <input
                  type="text"
                  value={filterForm.data.search}
                  onChange={(e) => filterForm.setData('search', e.target.value)}
                  onKeyDown={(e) => e.key === 'Enter' && submitFilters()}
                  placeholder="Full name, position, email, designation..."
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

            {/* Division */}
            <div className="space-y-1">
              <label className="text-[11px] font-semibold text-muted-foreground">Division</label>
              <select
                value={filterForm.data.division}
                onChange={(e) => {
                  filterForm.setData({ ...filterForm.data, division: e.target.value, section: '' });
                  submitFilters({ division: e.target.value, section: '' });
                }}
                className="h-8 w-full rounded-lg border border-input bg-background px-2.5 text-xs text-foreground outline-hidden focus:ring-2 focus:ring-ring cursor-pointer"
              >
                <option value="">All Divisions ({divisions.length})</option>
                {divisions.map((d) => (
                  <option key={d.id} value={d.id}>
                    {d.name}
                  </option>
                ))}
              </select>
            </div>

            {/* Section */}
            <div className="space-y-1">
              <label className="text-[11px] font-semibold text-muted-foreground">Section</label>
              <select
                value={filterForm.data.section}
                onChange={(e) => {
                  filterForm.setData('section', e.target.value);
                  submitFilters({ section: e.target.value });
                }}
                className="h-8 w-full rounded-lg border border-input bg-background px-2.5 text-xs text-foreground outline-hidden focus:ring-2 focus:ring-ring cursor-pointer"
              >
                <option value="">All Sections ({filterSectionOptions.length})</option>
                {filterSectionOptions.map((s) => (
                  <option key={s.id} value={s.id}>
                    {s.name}
                  </option>
                ))}
              </select>
            </div>

            {/* User Level */}
            <div className="space-y-1">
              <label className="text-[11px] font-semibold text-muted-foreground">User Level</label>
              <select
                value={filterForm.data.user_level_id}
                onChange={(e) => {
                  filterForm.setData('user_level_id', e.target.value);
                  submitFilters({ user_level_id: e.target.value });
                }}
                className="h-8 w-full rounded-lg border border-input bg-background px-2.5 text-xs text-foreground outline-hidden focus:ring-2 focus:ring-ring cursor-pointer"
              >
                <option value="">All User Levels ({userLevels.length})</option>
                {userLevels.map((ul) => (
                  <option key={ul.id} value={ul.id}>
                    {ul.name}
                  </option>
                ))}
              </select>
            </div>

            {/* Status */}
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
                {statusOptions.map((opt) => (
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
            <table className="w-full min-w-[950px] border-collapse text-xs text-left">
              <thead>
                <tr className="bg-muted/60 text-[11px] font-bold uppercase tracking-wider text-muted-foreground border-b border-border">
                  <th className="px-3 py-2 text-center w-12 border-r border-border">#</th>
                  <th className="px-3 py-2 border-r border-border">Full Name</th>
                  <th className="px-3 py-2 border-r border-border">Email</th>
                  <th className="px-3 py-2 border-r border-border">Contact</th>
                  <th className="px-3 py-2 border-r border-border">Position</th>
                  <th className="px-3 py-2 border-r border-border">Division / Section</th>
                  <th className="px-3 py-2 border-r border-border text-center">User Level</th>
                  <th className="px-3 py-2 border-r border-border text-center w-24">Status</th>
                  <th className="px-3 py-2 text-center w-28">Action</th>
                </tr>
              </thead>
              <tbody className="divide-y divide-border">
                {users.data.length === 0 ? (
                  <tr>
                    <td colSpan={9} className="px-4 py-12 text-center">
                      <div className="flex flex-col items-center justify-center space-y-2">
                        <div className="size-10 rounded-full bg-muted flex items-center justify-center text-muted-foreground">
                          <UsersIcon className="size-5" />
                        </div>
                        <p className="text-xs font-bold text-foreground">No users found</p>
                        <p className="text-[11px] text-muted-foreground max-w-sm">
                          No administration user accounts matched your search criteria.
                        </p>
                      </div>
                    </td>
                  </tr>
                ) : (
                  users.data.map((row, index) => (
                    <tr key={row.id} className="hover:bg-muted/30 transition-colors">
                      <td className="px-3 py-2 text-center font-mono text-[11px] text-muted-foreground border-r border-border">
                        {(users.from ?? 1) + index}
                      </td>

                      <td className="px-3 py-2 font-bold text-foreground border-r border-border">
                        <div className="flex items-center gap-2">
                          <UserAvatar user={{ name: row.fullName, avatar_url: row.avatarUrl, avatar: row.avatar }} size="sm" />
                          <span className="truncate">{row.fullName.toUpperCase()}</span>
                        </div>
                      </td>

                      <td className="px-3 py-2 text-muted-foreground border-r border-border font-mono text-[11px]">
                        {row.email ? (
                          <a href={`mailto:${row.email}`} className="text-emerald-700 dark:text-emerald-400 hover:underline">
                            {row.email}
                          </a>
                        ) : '—'}
                      </td>

                      <td className="px-3 py-2 whitespace-nowrap text-muted-foreground border-r border-border font-mono text-[11px]">
                        {row.contactNumber || '—'}
                      </td>

                      <td className="px-3 py-2 text-foreground border-r border-border truncate max-w-[160px]" title={row.position || ''}>
                        {row.position || '—'}
                      </td>

                      <td className="px-3 py-2 border-r border-border">
                        <div className="text-[11px] font-semibold text-foreground truncate max-w-[150px]">{row.divisionName || '—'}</div>
                        <div className="text-[10px] text-muted-foreground truncate max-w-[150px]">{row.sectionName || ''}</div>
                      </td>

                      <td className="px-3 py-2 whitespace-nowrap text-center border-r border-border">
                        {row.userLevelName ? (
                          <span className="inline-flex items-center rounded-full bg-violet-500/10 text-violet-700 dark:text-violet-300 font-mono text-[10px] font-bold px-2 py-0.2 border border-violet-500/20">
                            {row.userLevelName}
                          </span>
                        ) : (
                          <span className="text-xs text-muted-foreground">—</span>
                        )}
                      </td>

                      <td className="px-3 py-2 whitespace-nowrap text-center border-r border-border">
                        <button
                          type="button"
                          onClick={() =>
                            router.patch(`/administration/users/${row.id}/toggle-status`, {}, { preserveScroll: true })
                          }
                          className={`rounded-full font-mono text-[10px] font-bold px-2.5 py-0.5 transition cursor-pointer border ${
                            row.isStatus === 1
                              ? 'bg-emerald-500/10 text-emerald-700 dark:text-emerald-300 border-emerald-500/20 hover:bg-emerald-500/20'
                              : 'bg-muted text-muted-foreground border-border hover:bg-muted/80'
                          }`}
                        >
                          {row.isStatus === 1 ? 'Active' : 'Inactive'}
                        </button>
                      </td>

                      <td className="px-3 py-2 text-center">
                        <div className="inline-flex items-center justify-center gap-1">
                          <button
                            type="button"
                            onClick={() => openEditModal(row)}
                            title="Edit User"
                            className="p-1 rounded-md text-muted-foreground hover:text-foreground hover:bg-muted transition cursor-pointer"
                          >
                            <Pencil className="size-3.5" />
                          </button>
                          <button
                            type="button"
                            onClick={() => setDeletingUser({ id: row.id, name: row.fullName })}
                            title="Delete User"
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
          <div className="border-t border-border px-3.5 py-2.5 flex flex-col sm:flex-row sm:items-center justify-between gap-2.5 bg-muted/20">
            <div className="text-[11px] text-muted-foreground">
              Showing <span className="font-bold text-foreground">{users.from ?? 0}</span> to{' '}
              <span className="font-bold text-foreground">{users.to ?? 0}</span> of{' '}
              <span className="font-bold text-foreground">{users.total}</span> users
            </div>

            {users.links && users.links.length > 3 && (
              <div className="flex items-center gap-1 flex-wrap">
                {users.links.map((link, idx) => {
                  if (!link.url) {
                    return (
                      <span
                        key={idx}
                        className="h-7 min-w-7 px-2 rounded-md flex items-center justify-center text-[11px] text-muted-foreground/50 border border-transparent select-none"
                        dangerouslySetInnerHTML={{ __html: link.label }}
                      />
                    );
                  }

                  return (
                    <Link
                      key={idx}
                      href={link.url}
                      preserveState
                      preserveScroll
                      className={`h-7 min-w-7 px-2 rounded-md flex items-center justify-center text-[11px] font-medium transition-colors ${
                        link.active
                          ? 'bg-emerald-600 text-white font-bold shadow-2xs'
                          : 'border border-input bg-background text-foreground hover:bg-muted'
                      }`}
                      dangerouslySetInnerHTML={{ __html: link.label }}
                    />
                  );
                })}
              </div>
            )}
          </div>
        </div>

        {/* Modal: Edit User */}
        {showEditModal ? (
          <div className="fixed inset-0 z-50 flex items-center justify-center bg-black/60 backdrop-blur-xs p-4 animate-in fade-in duration-150">
            <div className="w-full max-w-4xl max-h-[90vh] overflow-y-auto rounded-xl border border-border bg-card p-5 shadow-2xl space-y-4">
              <div className="flex items-start justify-between border-b border-border/80 pb-3">
                <div className="flex items-center gap-3">
                  <UserAvatar
                    user={{
                      name: editingUser?.fullName,
                      avatar_url: avatarPreview || editingUser?.avatarUrl,
                      avatar: editingUser?.avatar,
                    }}
                    size="lg"
                    className="shadow-md shrink-0"
                  />
                  <div>
                    <h3 className="text-sm font-bold text-foreground">Edit User Profile</h3>
                    <p className="text-xs text-muted-foreground mt-0.5">
                      Update profile details, avatar, roles, and assignments for <strong className="text-foreground">{editingUser?.fullName}</strong>.
                    </p>
                    <div className="flex items-center gap-2 mt-2">
                      <input
                        ref={fileInputRef}
                        type="file"
                        accept="image/jpeg,image/png,image/webp,image/jpg"
                        className="hidden"
                        onChange={handleAvatarSelect}
                      />
                      <button
                        type="button"
                        onClick={() => fileInputRef.current?.click()}
                        className="h-6 px-2.5 rounded-md bg-muted hover:bg-muted/80 text-[11px] font-semibold text-foreground inline-flex items-center gap-1 transition cursor-pointer border border-border"
                      >
                        <Camera className="size-3" />
                        <span>Change Photo</span>
                      </button>
                      {(editingUser?.avatarUrl || avatarPreview) && (
                        <button
                          type="button"
                          onClick={handleRemoveAvatar}
                          className="h-6 px-2 rounded-md bg-destructive/10 text-destructive hover:bg-destructive/20 text-[11px] font-semibold inline-flex items-center gap-1 transition cursor-pointer"
                        >
                          <Trash2 className="size-3" />
                          <span>Remove</span>
                        </button>
                      )}
                    </div>
                  </div>
                </div>
                <button
                  type="button"
                  onClick={() => setShowEditModal(false)}
                  className="text-muted-foreground hover:text-foreground p-1 rounded-md transition"
                >
                  <X className="size-4" />
                </button>
              </div>

              <form onSubmit={handleEditSubmit} className="space-y-3">
                <div className="grid gap-3 sm:grid-cols-2">
                  <div className="space-y-1">
                    <label className="text-[11px] font-semibold text-muted-foreground">
                      Lastname
                    </label>
                    <input
                      value={editForm.data.editLastName}
                      onChange={(e) => editForm.setData('editLastName', e.target.value)}
                      className="h-8 w-full rounded-lg border border-input bg-background px-2.5 text-xs text-foreground outline-hidden focus:ring-2 focus:ring-ring"
                      required
                    />
                  </div>

                  <div className="space-y-1">
                    <label className="text-[11px] font-semibold text-muted-foreground">
                      Firstname
                    </label>
                    <input
                      value={editForm.data.editFirstName}
                      onChange={(e) => editForm.setData('editFirstName', e.target.value)}
                      className="h-8 w-full rounded-lg border border-input bg-background px-2.5 text-xs text-foreground outline-hidden focus:ring-2 focus:ring-ring"
                      required
                    />
                  </div>

                  <div className="space-y-1">
                    <label className="text-[11px] font-semibold text-muted-foreground">
                      Middlename
                    </label>
                    <input
                      value={editForm.data.editMiddleName}
                      onChange={(e) => editForm.setData('editMiddleName', e.target.value)}
                      className="h-8 w-full rounded-lg border border-input bg-background px-2.5 text-xs text-foreground outline-hidden focus:ring-2 focus:ring-ring"
                      required
                    />
                  </div>

                  <div className="space-y-1">
                    <label className="text-[11px] font-semibold text-muted-foreground">
                      Extension Name
                    </label>
                    <input
                      value={editForm.data.editExtensionName}
                      onChange={(e) => editForm.setData('editExtensionName', e.target.value)}
                      className="h-8 w-full rounded-lg border border-input bg-background px-2.5 text-xs text-foreground outline-hidden focus:ring-2 focus:ring-ring"
                    />
                  </div>

                  <div className="space-y-1">
                    <label className="text-[11px] font-semibold text-muted-foreground">
                      Email
                    </label>
                    <input
                      type="email"
                      value={editForm.data.email}
                      disabled
                      className="h-8 w-full rounded-lg border border-input bg-muted px-2.5 text-xs text-muted-foreground outline-hidden cursor-not-allowed opacity-80"
                    />
                  </div>

                  <div className="space-y-1">
                    <label className="text-[11px] font-semibold text-muted-foreground">
                      Contact Number
                    </label>
                    <input
                      value={editForm.data.editContactNumber}
                      onChange={(e) => editForm.setData('editContactNumber', e.target.value)}
                      className="h-8 w-full rounded-lg border border-input bg-background px-2.5 text-xs text-foreground outline-hidden focus:ring-2 focus:ring-ring"
                      required
                    />
                  </div>

                  <div className="space-y-1">
                    <label className="text-[11px] font-semibold text-muted-foreground">
                      Position
                    </label>
                    <input
                      value={editForm.data.editPosition}
                      onChange={(e) => editForm.setData('editPosition', e.target.value)}
                      className="h-8 w-full rounded-lg border border-input bg-background px-2.5 text-xs text-foreground outline-hidden focus:ring-2 focus:ring-ring"
                      required
                    />
                  </div>

                  <div className="space-y-1">
                    <label className="text-[11px] font-semibold text-muted-foreground">
                      Designation
                    </label>
                    <input
                      value={editForm.data.editDesignation}
                      onChange={(e) => editForm.setData('editDesignation', e.target.value)}
                      className="h-8 w-full rounded-lg border border-input bg-background px-2.5 text-xs text-foreground outline-hidden focus:ring-2 focus:ring-ring"
                      required
                    />
                  </div>

                  <div className="space-y-1">
                    <label className="text-[11px] font-semibold text-muted-foreground">
                      Division
                    </label>
                    <select
                      value={editForm.data.editDivision}
                      onChange={(e) => {
                        editForm.setData({ ...editForm.data, editDivision: e.target.value, editSection: '' });
                      }}
                      className="h-8 w-full rounded-lg border border-input bg-background px-2.5 text-xs text-foreground outline-hidden focus:ring-2 focus:ring-ring cursor-pointer"
                      required
                    >
                      <option value="">Select division</option>
                      {divisions.map((d) => (
                        <option key={d.id} value={d.id}>
                          {d.name}
                        </option>
                      ))}
                    </select>
                  </div>

                  <div className="space-y-1">
                    <label className="text-[11px] font-semibold text-muted-foreground">
                      Section
                    </label>
                    <select
                      value={editForm.data.editSection}
                      onChange={(e) => editForm.setData('editSection', e.target.value)}
                      className="h-8 w-full rounded-lg border border-input bg-background px-2.5 text-xs text-foreground outline-hidden focus:ring-2 focus:ring-ring cursor-pointer"
                      required
                    >
                      <option value="">Select section</option>
                      {editSectionOptions.map((s) => (
                        <option key={s.id} value={s.id}>
                          {s.name}
                        </option>
                      ))}
                    </select>
                  </div>

                  <div className="space-y-1">
                    <label className="text-[11px] font-semibold text-muted-foreground">
                      User Level
                    </label>
                    <select
                      value={editForm.data.editUserLevelId}
                      onChange={(e) => editForm.setData('editUserLevelId', e.target.value)}
                      className="h-8 w-full rounded-lg border border-input bg-background px-2.5 text-xs text-foreground outline-hidden focus:ring-2 focus:ring-ring cursor-pointer"
                    >
                      <option value="">Select user level</option>
                      {userLevels.map((ul) => (
                        <option key={ul.id} value={ul.id}>
                          {ul.name}
                        </option>
                      ))}
                    </select>
                  </div>

                  <div className="space-y-1">
                    <label className="text-[11px] font-semibold text-muted-foreground">
                      Supervisor
                    </label>
                    <select
                      value={editForm.data.editSupervisorId}
                      onChange={(e) => editForm.setData('editSupervisorId', e.target.value)}
                      className="h-8 w-full rounded-lg border border-input bg-background px-2.5 text-xs text-foreground outline-hidden focus:ring-2 focus:ring-ring cursor-pointer"
                    >
                      <option value="">Select supervisor</option>
                      {supervisors
                        .filter((sp) => !editingUser || sp.id !== String(editingUser.id))
                        .map((sp) => (
                          <option key={sp.id} value={sp.id}>
                            {sp.name}
                          </option>
                        ))}
                    </select>
                  </div>

                  <div className="space-y-2 sm:col-span-2">
                    <label className="flex cursor-pointer items-center gap-2 rounded-lg border border-input bg-background px-3 py-2">
                      <input
                        type="checkbox"
                        checked={editForm.data.editIsSupervisor}
                        onChange={(e) => editForm.setData('editIsSupervisor', e.target.checked)}
                        className="size-3.5 rounded border-input text-emerald-600 focus:ring-emerald-500"
                      />
                      <span className="text-xs text-foreground font-medium">
                        User has Supervisor privileges
                      </span>
                    </label>

                    <label className="flex cursor-pointer items-center gap-2 rounded-lg border border-input bg-background px-3 py-2">
                      <input
                        type="checkbox"
                        checked={editForm.data.editCanScorecard}
                        onChange={(e) => editForm.setData('editCanScorecard', e.target.checked)}
                        className="size-3.5 rounded border-input text-emerald-600 focus:ring-emerald-500"
                      />
                      <span className="text-xs text-foreground font-medium">
                        User can access RPMO Management / Scorecard (<code className="text-[10px] text-muted-foreground font-mono">can_scorecard = 1</code>)
                      </span>
                    </label>
                  </div>
                </div>

                <div className="flex justify-end gap-2 pt-3 border-t border-border">
                  <button
                    type="button"
                    onClick={() => setShowEditModal(false)}
                    className="px-3 py-1.5 rounded-lg border border-input bg-background text-xs font-semibold text-foreground hover:bg-muted transition cursor-pointer"
                  >
                    Cancel
                  </button>
                  <button
                    type="submit"
                    disabled={editForm.processing}
                    className="px-3.5 py-1.5 rounded-lg bg-emerald-600 hover:bg-emerald-700 text-white text-xs font-semibold shadow-xs transition cursor-pointer"
                  >
                    Save Changes
                  </button>
                </div>
              </form>
            </div>
          </div>
        ) : null}

        {/* Modal: Delete User */}
        {deletingUser ? (
          <div className="fixed inset-0 z-50 flex items-center justify-center bg-black/60 backdrop-blur-xs p-4 animate-in fade-in duration-150">
            <div className="w-full max-w-md rounded-xl border border-border bg-card p-5 shadow-2xl space-y-4">
              <div className="flex items-start justify-between">
                <div className="flex items-center gap-3">
                  <UserAvatar
                    user={{
                      name: deletingUser.fullName,
                      avatar_url: deletingUser.avatarUrl,
                      avatar: deletingUser.avatar,
                    }}
                    size="md"
                    className="shrink-0"
                  />
                  <div>
                    <h3 className="text-sm font-bold text-foreground">Delete User</h3>
                    <p className="text-xs text-muted-foreground mt-0.5">
                      This will permanently remove <strong className="text-foreground">{deletingUser.fullName}</strong> from the system.
                    </p>
                  </div>
                </div>
                <button
                  type="button"
                  onClick={() => setDeletingUser(null)}
                  className="text-muted-foreground hover:text-foreground p-1 rounded-md transition"
                >
                  <X className="size-4" />
                </button>
              </div>

              <div className="rounded-lg border border-border bg-muted/40 px-3.5 py-2.5 text-xs text-muted-foreground space-y-1">
                <div>
                  User: <span className="font-bold text-foreground">{deletingUser.name || '-'}</span>
                </div>
                <div>
                  User ID: <span className="font-mono font-bold text-foreground">{deletingUser.id || '-'}</span>
                </div>
              </div>

              <div className="flex justify-end gap-2 pt-2 border-t border-border">
                <button
                  type="button"
                  onClick={() => setDeletingUser(null)}
                  className="px-3 py-1.5 rounded-lg border border-input bg-background text-xs font-semibold text-foreground hover:bg-muted transition"
                >
                  Cancel
                </button>
                <button
                  type="button"
                  onClick={handleDeleteSubmit}
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

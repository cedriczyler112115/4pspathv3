import { Head, router, useForm } from '@inertiajs/react';
import { useState } from 'react';
import AppLayout from '../../Layouts/AppLayout';
import { Pencil, Trash2 } from 'lucide-react';

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
    status: string;
    perPage: number;
  };
  users: {
    data: UserRow[];
    from: number | null;
    to: number | null;
    total: number;
    currentPage: number;
    lastPage: number;
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
  // Filter Form
  const filterForm = useForm({
    search: filters.search,
    division: filters.division,
    section: filters.section,
    status: filters.status,
    perPage: String(filters.perPage),
  });

  // Edit Modal Form
  const [showEditModal, setShowEditModal] = useState(false);
  const [editingUserId, setEditingUserId] = useState<number | null>(null);

  const editForm = useForm({
    editLastName: '',
    editFirstName: '',
    editMiddleName: '',
    editExtensionName: '',
    editPosition: '',
    editDesignation: '',
    editDivision: '',
    editSection: '',
    editSupervisorId: '',
    editUserLevelId: '',
    editContactNumber: '',
    editIsSupervisor: false,
  });

  // Delete Modal
  const [deletingUser, setDeletingUser] = useState<{ id: number; name: string } | null>(null);

  const submitFilters = (overrides = {}) => {
    const data = { ...filterForm.data, ...overrides };
    router.get('/inertia/administration/users', data, {
      preserveState: true,
      replace: true,
    });
  };

  const openEditModal = (u: UserRow) => {
    setEditingUserId(u.id);
    editForm.setData({
      editLastName: u.lastName,
      editFirstName: u.firstName,
      editMiddleName: u.middleName,
      editExtensionName: u.extensionName,
      editPosition: u.position || '',
      editDesignation: u.designation || '',
      editDivision: u.divisionId,
      editSection: u.sectionId,
      editSupervisorId: u.supervisorId,
      editUserLevelId: u.userLevelId,
      editContactNumber: u.contactNumber || '',
      editIsSupervisor: u.isSupervisor,
    });
    setShowEditModal(true);
  };

  const handleUpdateUser = (e: React.FormEvent) => {
    e.preventDefault();
    if (!editingUserId) return;

    editForm.patch(`/inertia/administration/users/${editingUserId}`, {
      onSuccess: () => {
        setShowEditModal(false);
        setEditingUserId(null);
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

      <section className="w-full space-y-6">
        {/* Livewire Header Style */}
        <div className="mb-6">
          <h1 className="text-xl font-bold tracking-tight text-slate-900 dark:text-slate-50">
            Administration Users
          </h1>
          <p className="text-sm text-slate-500 dark:text-slate-400 mt-1">
            Browse and review users registered in the system.
          </p>
        </div>

        {/* Outer Card Container */}
        <div className="rounded-2xl border border-slate-200 dark:border-slate-800 bg-white dark:bg-slate-900 p-4 shadow-sm space-y-4">
          {/* Filters Bar */}
          <div className="mb-4 flex flex-col gap-4 border-b border-slate-100 dark:border-slate-800 pb-4">
            <div className="grid gap-4 sm:grid-cols-2 lg:grid-cols-4">
              <div>
                <label className="block text-xs font-semibold text-slate-600 dark:text-slate-400 mb-1">
                  Search
                </label>
                <input
                  type="text"
                  value={filterForm.data.search}
                  onChange={(e) => {
                    filterForm.setData('search', e.target.value);
                  }}
                  onKeyDown={(e) => e.key === 'Enter' && submitFilters()}
                  placeholder="Full name, position, or designation"
                  className="h-10 w-full rounded-xl border border-slate-300 dark:border-slate-700 bg-white dark:bg-slate-800 px-3 text-sm focus:border-emerald-500 focus:outline-none dark:text-slate-100"
                />
              </div>

              <div>
                <label className="block text-xs font-semibold text-slate-600 dark:text-slate-400 mb-1">
                  Division
                </label>
                <select
                  value={filterForm.data.division}
                  onChange={(e) => {
                    filterForm.setData({ ...filterForm.data, division: e.target.value, section: '' });
                    submitFilters({ division: e.target.value, section: '' });
                  }}
                  className="h-10 w-full rounded-xl border border-slate-300 dark:border-slate-700 bg-white dark:bg-slate-800 px-3 text-sm focus:border-emerald-500 focus:outline-none dark:text-slate-100"
                >
                  <option value="">All divisions</option>
                  {divisions.map((d) => (
                    <option key={d.id} value={d.id}>
                      {d.name}
                    </option>
                  ))}
                </select>
              </div>

              <div>
                <label className="block text-xs font-semibold text-slate-600 dark:text-slate-400 mb-1">
                  Section
                </label>
                <select
                  value={filterForm.data.section}
                  onChange={(e) => {
                    filterForm.setData('section', e.target.value);
                    submitFilters({ section: e.target.value });
                  }}
                  className="h-10 w-full rounded-xl border border-slate-300 dark:border-slate-700 bg-white dark:bg-slate-800 px-3 text-sm focus:border-emerald-500 focus:outline-none dark:text-slate-100"
                >
                  <option value="">All sections</option>
                  {filterSectionOptions.map((s) => (
                    <option key={s.id} value={s.id}>
                      {s.name}
                    </option>
                  ))}
                </select>
              </div>

              <div>
                <label className="block text-xs font-semibold text-slate-600 dark:text-slate-400 mb-1">
                  Status
                </label>
                <select
                  value={filterForm.data.status}
                  onChange={(e) => {
                    filterForm.setData('status', e.target.value);
                    submitFilters({ status: e.target.value });
                  }}
                  className="h-10 w-full rounded-xl border border-slate-300 dark:border-slate-700 bg-white dark:bg-slate-800 px-3 text-sm focus:border-emerald-500 focus:outline-none dark:text-slate-100"
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

          {/* Table Matching Livewire Bordered Grid Exact Layout */}
          <div className="w-full overflow-x-auto rounded-xl border border-slate-200 dark:border-slate-800">
            <table className="w-full border-separate border-spacing-0 text-sm">
              <thead className="bg-slate-50 dark:bg-slate-800/50 text-left text-xs font-semibold uppercase tracking-wide text-slate-500 dark:text-slate-400">
                <tr>
                  <th className="border-b border-r border-slate-200 dark:border-slate-800 px-2 py-3 whitespace-nowrap text-center first:rounded-tl-xl">
                    #
                  </th>
                  <th className="border-b border-r border-slate-200 dark:border-slate-800 px-3 py-3 whitespace-nowrap">
                    Full Name
                  </th>
                  <th className="border-b border-r border-slate-200 dark:border-slate-800 px-3 py-3 whitespace-nowrap">
                    Email
                  </th>
                  <th className="border-b border-r border-slate-200 dark:border-slate-800 px-3 py-3 whitespace-nowrap">
                    Contact Number
                  </th>
                  <th className="border-b border-r border-slate-200 dark:border-slate-800 px-3 py-3 whitespace-nowrap">
                    Position
                  </th>
                  <th className="border-b border-r border-slate-200 dark:border-slate-800 px-3 py-3 whitespace-nowrap">
                    Division
                  </th>
                  <th className="border-b border-r border-slate-200 dark:border-slate-800 px-3 py-3 whitespace-nowrap">
                    User Level
                  </th>
                  <th className="border-b border-slate-200 dark:border-slate-800 px-3 py-3 whitespace-nowrap">
                    Status
                  </th>
                  <th className="border-b border-slate-200 dark:border-slate-800 px-3 py-3 text-right whitespace-nowrap last:rounded-tr-xl">
                    Action
                  </th>
                </tr>
              </thead>
              <tbody className="divide-y divide-slate-100 dark:divide-slate-800">
                {users.data.length === 0 ? (
                  <tr>
                    <td colSpan={9} className="px-3 py-8 text-center text-slate-500 dark:text-slate-400">
                      No users found.
                    </td>
                  </tr>
                ) : (
                  users.data.map((row, index) => (
                    <tr key={row.id} className="border-t border-slate-200 dark:border-slate-800 text-sm hover:bg-slate-50/50 dark:hover:bg-slate-800/40">
                      <td className="border-b border-r border-slate-200 dark:border-slate-800 px-2 py-3 text-center text-slate-500 whitespace-nowrap">
                        {(users.from ?? 1) + index}
                      </td>

                      <td className="border-b border-r border-slate-200 dark:border-slate-800 px-3 py-3 font-medium text-slate-900 dark:text-slate-100">
                        {row.fullName.toUpperCase()}
                      </td>

                      <td className="border-b border-r border-slate-200 dark:border-slate-800 px-3 py-3 text-slate-700 dark:text-slate-300">
                        {row.email}
                      </td>

                      <td className="border-b border-r border-slate-200 dark:border-slate-800 px-3 py-3 whitespace-nowrap text-slate-700 dark:text-slate-300">
                        {row.contactNumber || ' - '}
                      </td>

                      <td className="border-b border-r border-slate-200 dark:border-slate-800 px-3 py-3 truncate max-w-[180px] text-slate-700 dark:text-slate-300">
                        {row.position ? (row.position.length > 25 ? `${row.position.substring(0, 25)}...` : row.position) : ' - '}
                      </td>

                      <td className="border-b border-r border-slate-200 dark:border-slate-800 px-3 py-3 truncate max-w-[120px] text-slate-700 dark:text-slate-300">
                        {row.divisionName ? (row.divisionName.length > 12 ? `${row.divisionName.substring(0, 12)}...` : row.divisionName) : ' - '}
                      </td>

                      <td className="border-b border-r border-slate-200 dark:border-slate-800 px-3 py-3 whitespace-nowrap">
                        {row.userLevelName ? (
                          <span className="inline-flex items-center rounded-full bg-violet-500/10 px-2.5 py-0.5 text-xs font-medium text-violet-700 dark:text-violet-300">
                            {row.userLevelName}
                          </span>
                        ) : (
                          <span className="text-xs text-slate-400">—</span>
                        )}
                      </td>

                      <td className="border-b border-slate-200 dark:border-slate-800 px-3 py-3 whitespace-nowrap">
                        <button
                          type="button"
                          onClick={() =>
                            router.patch(`/inertia/administration/users/${row.id}/toggle-status`, {}, { preserveScroll: true })
                          }
                          className={`rounded-full px-2.5 py-1 text-xs font-medium transition cursor-pointer ${
                            row.isStatus === 1
                              ? 'bg-emerald-500/10 text-emerald-600 hover:bg-emerald-500/15 dark:text-emerald-400'
                              : 'bg-slate-100 text-slate-600 hover:bg-slate-200 dark:bg-slate-800 dark:text-slate-400'
                          }`}
                        >
                          {row.isStatus === 1 ? 'Active' : 'Inactive'}
                        </button>
                      </td>

                      <td className="border-b border-slate-200 dark:border-slate-800 px-3 py-3 text-right whitespace-nowrap">
                        <div className="flex items-center justify-end gap-2">
                          <button
                            type="button"
                            onClick={() => openEditModal(row)}
                            className="inline-flex items-center gap-1 px-2.5 py-1.5 rounded-lg text-xs font-medium text-slate-700 hover:bg-slate-100 dark:text-slate-300 dark:hover:bg-slate-800 transition"
                          >
                            <Pencil className="w-3.5 h-3.5" />
                            <span>Edit</span>
                          </button>
                          <button
                            type="button"
                            onClick={() => setDeletingUser({ id: row.id, name: row.fullName })}
                            className="inline-flex items-center gap-1 px-2.5 py-1.5 rounded-lg text-xs font-medium text-red-600 hover:bg-red-50 dark:text-red-400 dark:hover:bg-red-500/10 transition"
                          >
                            <Trash2 className="w-3.5 h-3.5" />
                            <span>Delete</span>
                          </button>
                        </div>
                      </td>
                    </tr>
                  ))
                )}
              </tbody>
            </table>
          </div>

          {/* Livewire Pagination Style Matching vendor.pagination.users-pagination */}
          {users.lastPage > 1 ? (
            <nav
              role="navigation"
              aria-label="Pagination Navigation"
              className="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between pt-2"
            >
              <div className="text-sm text-slate-500 dark:text-slate-400">
                Showing {users.from ?? 0} to {users.to ?? 0} of {users.total} records
              </div>

              <div className="flex flex-wrap items-center gap-1.5">
                {/* Previous Button */}
                {users.currentPage === 1 ? (
                  <span className="inline-flex cursor-not-allowed items-center rounded-lg border border-slate-200 dark:border-slate-800 bg-slate-100 dark:bg-slate-800 px-3 py-2 text-sm text-slate-400 select-none">
                    Previous
                  </span>
                ) : (
                  <button
                    type="button"
                    onClick={() =>
                      router.get(
                        '/inertia/administration/users',
                        { ...filterForm.data, page: users.currentPage - 1 },
                        { replace: true, preserveState: true }
                      )
                    }
                    className="inline-flex cursor-pointer items-center rounded-lg border border-slate-200 dark:border-slate-800 bg-white dark:bg-slate-900 px-3 py-2 text-sm text-slate-700 dark:text-slate-200 hover:border-emerald-500/50 hover:bg-emerald-50/50 hover:text-emerald-600 dark:hover:bg-emerald-950/30 dark:hover:text-emerald-400 transition-colors"
                  >
                    Previous
                  </button>
                )}

                {/* Page Numbers */}
                {Array.from({ length: users.lastPage }, (_, i) => i + 1).map((page) => {
                  if (page === users.currentPage) {
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
                          '/inertia/administration/users',
                          { ...filterForm.data, page },
                          { replace: true, preserveState: true }
                        )
                      }
                      className="inline-flex min-w-10 cursor-pointer items-center justify-center rounded-lg border border-slate-200 dark:border-slate-800 bg-white dark:bg-slate-900 px-3 py-2 text-sm text-slate-700 dark:text-slate-200 hover:border-emerald-500/50 hover:bg-emerald-50/50 hover:text-emerald-600 dark:hover:bg-emerald-950/30 dark:hover:text-emerald-400 transition-colors"
                    >
                      {page}
                    </button>
                  );
                })}

                {/* Next Button */}
                {users.currentPage === users.lastPage ? (
                  <span className="inline-flex cursor-not-allowed items-center rounded-lg border border-slate-200 dark:border-slate-800 bg-slate-100 dark:bg-slate-800 px-3 py-2 text-sm text-slate-400 select-none">
                    Next
                  </span>
                ) : (
                  <button
                    type="button"
                    onClick={() =>
                      router.get(
                        '/inertia/administration/users',
                        { ...filterForm.data, page: users.currentPage + 1 },
                        { replace: true, preserveState: true }
                      )
                    }
                    className="inline-flex cursor-pointer items-center rounded-lg border border-slate-200 dark:border-slate-800 bg-white dark:bg-slate-900 px-3 py-2 text-sm text-slate-700 dark:text-slate-200 hover:border-emerald-500/50 hover:bg-emerald-50/50 hover:text-emerald-600 dark:hover:bg-emerald-950/30 dark:hover:text-emerald-400 transition-colors"
                  >
                    Next
                  </button>
                )}
              </div>
            </nav>
          ) : null}
        </div>

        {/* Modal: Edit User (Matching Flux Modal Overlay) */}
        {showEditModal ? (
          <div className="fixed inset-0 z-50 flex items-center justify-center bg-slate-900/40 p-4 backdrop-blur-sm">
            <div className="w-full max-w-4xl max-h-[90vh] overflow-y-auto rounded-3xl bg-white dark:bg-slate-900 p-6 shadow-xl space-y-5 border border-slate-200 dark:border-slate-800">
              <div className="space-y-1">
                <h3 className="text-lg font-bold text-slate-900 dark:text-slate-100">Edit user</h3>
                <p className="text-xs text-slate-500 dark:text-slate-400">
                  Update the selected user profile details.
                </p>
              </div>

              <form onSubmit={handleUpdateUser} className="space-y-4">
                <div className="grid gap-4 sm:grid-cols-2">
                  <div>
                    <label className="block text-xs font-semibold text-slate-600 dark:text-slate-400 mb-1">
                      Lastname
                    </label>
                    <input
                      value={editForm.data.editLastName}
                      onChange={(e) => editForm.setData('editLastName', e.target.value)}
                      className="h-10 w-full rounded-xl border border-slate-300 dark:border-slate-700 bg-white dark:bg-slate-800 px-3 text-sm text-slate-900 dark:text-slate-100 focus:border-emerald-500 focus:outline-none"
                      required
                    />
                  </div>

                  <div>
                    <label className="block text-xs font-semibold text-slate-600 dark:text-slate-400 mb-1">
                      Firstname
                    </label>
                    <input
                      value={editForm.data.editFirstName}
                      onChange={(e) => editForm.setData('editFirstName', e.target.value)}
                      className="h-10 w-full rounded-xl border border-slate-300 dark:border-slate-700 bg-white dark:bg-slate-800 px-3 text-sm text-slate-900 dark:text-slate-100 focus:border-emerald-500 focus:outline-none"
                      required
                    />
                  </div>

                  <div>
                    <label className="block text-xs font-semibold text-slate-600 dark:text-slate-400 mb-1">
                      Middlename
                    </label>
                    <input
                      value={editForm.data.editMiddleName}
                      onChange={(e) => editForm.setData('editMiddleName', e.target.value)}
                      className="h-10 w-full rounded-xl border border-slate-300 dark:border-slate-700 bg-white dark:bg-slate-800 px-3 text-sm text-slate-900 dark:text-slate-100 focus:border-emerald-500 focus:outline-none"
                      required
                    />
                  </div>

                  <div>
                    <label className="block text-xs font-semibold text-slate-600 dark:text-slate-400 mb-1">
                      Extension Name
                    </label>
                    <input
                      value={editForm.data.editExtensionName}
                      onChange={(e) => editForm.setData('editExtensionName', e.target.value)}
                      className="h-10 w-full rounded-xl border border-slate-300 dark:border-slate-700 bg-white dark:bg-slate-800 px-3 text-sm text-slate-900 dark:text-slate-100 focus:border-emerald-500 focus:outline-none"
                    />
                  </div>

                  <div>
                    <label className="block text-xs font-semibold text-slate-600 dark:text-slate-400 mb-1">
                      Position
                    </label>
                    <input
                      value={editForm.data.editPosition}
                      onChange={(e) => editForm.setData('editPosition', e.target.value)}
                      className="h-10 w-full rounded-xl border border-slate-300 dark:border-slate-700 bg-white dark:bg-slate-800 px-3 text-sm text-slate-900 dark:text-slate-100 focus:border-emerald-500 focus:outline-none"
                      required
                    />
                  </div>

                  <div>
                    <label className="block text-xs font-semibold text-slate-600 dark:text-slate-400 mb-1">
                      Designation
                    </label>
                    <input
                      value={editForm.data.editDesignation}
                      onChange={(e) => editForm.setData('editDesignation', e.target.value)}
                      className="h-10 w-full rounded-xl border border-slate-300 dark:border-slate-700 bg-white dark:bg-slate-800 px-3 text-sm text-slate-900 dark:text-slate-100 focus:border-emerald-500 focus:outline-none"
                      required
                    />
                  </div>

                  <div>
                    <label className="block text-xs font-semibold text-slate-600 dark:text-slate-400 mb-1">
                      Division
                    </label>
                    <select
                      value={editForm.data.editDivision}
                      onChange={(e) => {
                        editForm.setData({ ...editForm.data, editDivision: e.target.value, editSection: '' });
                      }}
                      className="h-10 w-full rounded-xl border border-slate-300 dark:border-slate-700 bg-white dark:bg-slate-800 px-3 text-sm text-slate-900 dark:text-slate-100 focus:border-emerald-500 focus:outline-none"
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

                  <div>
                    <label className="block text-xs font-semibold text-slate-600 dark:text-slate-400 mb-1">
                      Section
                    </label>
                    <select
                      value={editForm.data.editSection}
                      onChange={(e) => editForm.setData('editSection', e.target.value)}
                      className="h-10 w-full rounded-xl border border-slate-300 dark:border-slate-700 bg-white dark:bg-slate-800 px-3 text-sm text-slate-900 dark:text-slate-100 focus:border-emerald-500 focus:outline-none"
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

                  <div>
                    <label className="block text-xs font-semibold text-slate-600 dark:text-slate-400 mb-1">
                      User Level
                    </label>
                    <select
                      value={editForm.data.editUserLevelId}
                      onChange={(e) => editForm.setData('editUserLevelId', e.target.value)}
                      className="h-10 w-full rounded-xl border border-slate-300 dark:border-slate-700 bg-white dark:bg-slate-800 px-3 text-sm text-slate-900 dark:text-slate-100 focus:border-emerald-500 focus:outline-none"
                    >
                      <option value="">Select user level</option>
                      {userLevels.map((ul) => (
                        <option key={ul.id} value={ul.id}>
                          {ul.name}
                        </option>
                      ))}
                    </select>
                  </div>

                  <div>
                    <label className="block text-xs font-semibold text-slate-600 dark:text-slate-400 mb-1">
                      Supervisor
                    </label>
                    <select
                      value={editForm.data.editSupervisorId}
                      onChange={(e) => editForm.setData('editSupervisorId', e.target.value)}
                      className="h-10 w-full rounded-xl border border-slate-300 dark:border-slate-700 bg-white dark:bg-slate-800 px-3 text-sm text-slate-900 dark:text-slate-100 focus:border-emerald-500 focus:outline-none"
                      required
                    >
                      <option value="">Select supervisor</option>
                      {supervisors.map((sp) => (
                        <option key={sp.id} value={sp.id}>
                          {sp.name}
                        </option>
                      ))}
                    </select>
                  </div>

                  <div>
                    <label className="block text-xs font-semibold text-slate-600 dark:text-slate-400 mb-1">
                      Contact Number
                    </label>
                    <input
                      value={editForm.data.editContactNumber}
                      onChange={(e) => editForm.setData('editContactNumber', e.target.value)}
                      className="h-10 w-full rounded-xl border border-slate-300 dark:border-slate-700 bg-white dark:bg-slate-800 px-3 text-sm text-slate-900 dark:text-slate-100 focus:border-emerald-500 focus:outline-none"
                      required
                    />
                  </div>

                  <div className="space-y-2">
                    <label className="block text-xs font-semibold text-slate-600 dark:text-slate-400">
                      Is Supervisor
                    </label>
                    <label className="flex cursor-pointer items-center gap-3 rounded-xl border border-slate-200 dark:border-slate-700 bg-white dark:bg-slate-800 px-4 py-2.5">
                      <input
                        type="checkbox"
                        checked={editForm.data.editIsSupervisor}
                        onChange={(e) => editForm.setData('editIsSupervisor', e.target.checked)}
                        className="h-4 w-4 rounded border-slate-300 text-emerald-600 focus:ring-emerald-500"
                      />
                      <span className="text-sm text-slate-700 dark:text-slate-200 font-medium">
                        Make as Supervisor
                      </span>
                    </label>
                  </div>
                </div>

                <div className="flex justify-end gap-2 pt-4 border-t border-slate-100 dark:border-slate-800">
                  <button
                    type="button"
                    onClick={() => setShowEditModal(false)}
                    className="rounded-xl px-4 py-2 text-sm font-medium text-slate-700 hover:bg-slate-100 dark:text-slate-300 dark:hover:bg-slate-800 transition"
                  >
                    Cancel
                  </button>
                  <button
                    type="submit"
                    disabled={editForm.processing}
                    className="rounded-xl bg-emerald-600 hover:bg-emerald-700 px-5 py-2 text-sm font-semibold text-white shadow-sm transition"
                  >
                    Save changes
                  </button>
                </div>
              </form>
            </div>
          </div>
        ) : null}

        {/* Modal: Delete User (Matching Livewire Delete Modal) */}
        {deletingUser ? (
          <div className="fixed inset-0 z-50 flex items-center justify-center bg-slate-900/40 p-4 backdrop-blur-sm">
            <div className="w-full max-w-lg rounded-3xl bg-white dark:bg-slate-900 p-6 shadow-xl space-y-5 border border-slate-200 dark:border-slate-800">
              <div className="space-y-1">
                <h3 className="text-lg font-bold text-slate-900 dark:text-slate-100">Delete user</h3>
                <p className="text-xs text-slate-500 dark:text-slate-400">
                  This will permanently remove the selected user from the list.
                </p>
              </div>

              <div className="rounded-xl border border-slate-200 dark:border-slate-800 bg-slate-50 dark:bg-slate-800/40 px-4 py-3 text-sm text-slate-500 dark:text-slate-400 space-y-1">
                <div>
                  Selected user: <span className="font-semibold text-slate-900 dark:text-slate-100">{deletingUser.name || '-'}</span>
                </div>
                <div>
                  Selected user ID: <span className="font-semibold text-slate-900 dark:text-slate-100">{deletingUser.id || '-'}</span>
                </div>
              </div>

              <div className="flex justify-end gap-2 pt-2">
                <button
                  type="button"
                  onClick={() => setDeletingUser(null)}
                  className="rounded-xl px-4 py-2 text-sm font-medium text-slate-700 hover:bg-slate-100 dark:text-slate-300 dark:hover:bg-slate-800 transition"
                >
                  Cancel
                </button>
                <button
                  type="button"
                  onClick={() => {
                    router.delete(`/inertia/administration/users/${deletingUser.id}`, {
                      onSuccess: () => setDeletingUser(null),
                    });
                  }}
                  className="rounded-xl bg-red-600 hover:bg-red-700 px-5 py-2 text-sm font-semibold text-white shadow-sm transition"
                >
                  Delete
                </button>
              </div>
            </div>
          </div>
        ) : null}
      </section>
    </AppLayout>
  );
}

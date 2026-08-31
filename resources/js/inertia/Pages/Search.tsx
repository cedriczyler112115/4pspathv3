import { Head, router, useForm } from '@inertiajs/react';
import AppLayout from '../Layouts/AppLayout';

type UserRow = {
  id: number;
  last_name: string | null;
  first_name: string | null;
  middle_name: string | null;
  extension_name: string | null;
  email: string | null;
  contact_number: string | null;
  position: string | null;
  division_name: string | null;
  section_name: string | null;
};

type Props = {
  appName: string;
  user: { name: string; email: string } | null;
  filters: {
    search: string;
    division: string;
    section: string;
    perPage: number;
  };
  users: {
    data: UserRow[];
    from: number | null;
    links: Array<{ url: string | null; label: string; active: boolean }>;
  };
  divisions: Array<{ id: number; division_name: string }>;
  sections: Array<{ id: number; section_name: string }>;
  navigation?: { sidebar?: any[] };
};

function formatName(user: UserRow): string {
  return [
    user.last_name,
    user.first_name,
    user.middle_name,
    user.extension_name,
  ].filter(Boolean).join(' ').toUpperCase();
}

export default function Search({ appName, user, filters, users, divisions, sections, navigation }: Props) {
  const form = useForm({
    search: filters.search,
    division: filters.division,
    section: filters.section,
    perPage: String(filters.perPage),
  });

  const applyFilters = () => {
    router.get('/inertia/search', form.data, { preserveState: true, replace: true });
  };

  const reset = () => {
    router.get('/inertia/search', { search: '', division: '', section: '', perPage: 10 }, { replace: true });
  };

  return (
    <AppLayout appName={appName} user={user} sidebar={navigation?.sidebar ?? []}>
      <Head title="Search Users" />
      <div className="space-y-6 rounded-[2rem] border border-slate-200 bg-white p-6 shadow-[0_20px_60px_rgba(15,23,42,0.08)] sm:p-8">
        <div className="flex flex-col gap-2">
          <p className="text-xs font-semibold uppercase tracking-[0.3em] text-cyan-700">Directory</p>
          <h2 className="text-2xl font-semibold tracking-tight text-slate-900">Search Users</h2>
          <p className="max-w-2xl text-sm leading-6 text-slate-600">Find users by full name, division, or section.</p>
        </div>

        <div className="grid gap-4 lg:grid-cols-[1.4fr_1fr_1fr_auto]">
          <input
            value={form.data.search}
            onChange={(e) => form.setData('search', e.target.value)}
            placeholder="Search full name"
            className="h-11 rounded-2xl border border-slate-300 px-4 text-sm"
          />
          <select value={form.data.division} onChange={(e) => form.setData('division', e.target.value)} className="h-11 rounded-2xl border border-slate-300 px-4 text-sm">
            <option value="">All divisions</option>
            {divisions.map((division) => (
              <option key={division.id} value={division.id}>{division.division_name}</option>
            ))}
          </select>
          <select value={form.data.section} onChange={(e) => form.setData('section', e.target.value)} className="h-11 rounded-2xl border border-slate-300 px-4 text-sm">
            <option value="">All sections</option>
            {sections.map((section) => (
              <option key={section.id} value={section.id}>{section.section_name}</option>
            ))}
          </select>
          <div className="flex gap-2">
            <button type="button" onClick={applyFilters} className="h-11 rounded-2xl bg-slate-950 px-5 text-sm font-medium text-white">Search</button>
            <button type="button" onClick={reset} className="h-11 rounded-2xl border border-slate-300 px-5 text-sm font-medium text-slate-700">Reset</button>
          </div>
        </div>

        <div className="overflow-x-auto rounded-[1.5rem] border border-slate-200">
          <table className="w-full min-w-[900px] border-separate border-spacing-0 text-sm">
            <thead className="bg-slate-50 text-left text-xs font-semibold uppercase tracking-wide text-slate-500">
              <tr>
                <th className="border-b border-slate-200 px-3 py-3 text-center">#</th>
                <th className="border-b border-slate-200 px-3 py-3">Full Name</th>
                <th className="border-b border-slate-200 px-3 py-3">Division</th>
                <th className="border-b border-slate-200 px-3 py-3">Section</th>
                <th className="border-b border-slate-200 px-3 py-3">Position</th>
                <th className="border-b border-slate-200 px-3 py-3">Contact Number</th>
                <th className="border-b border-slate-200 px-3 py-3">Email</th>
              </tr>
            </thead>
            <tbody>
              {users.data.length > 0 ? users.data.map((row, index) => (
                <tr key={row.id} className="hover:bg-slate-50/70">
                  <td className="border-b border-slate-200 px-3 py-3 text-center text-slate-500">{(users.from ?? 1) + index}</td>
                  <td className="border-b border-slate-200 px-3 py-3 font-semibold text-slate-900">{formatName(row)}</td>
                  <td className="border-b border-slate-200 px-3 py-3 text-slate-700">{row.division_name || '-'}</td>
                  <td className="border-b border-slate-200 px-3 py-3 text-slate-700">{row.section_name || '-'}</td>
                  <td className="border-b border-slate-200 px-3 py-3 text-slate-700">{row.position || '-'}</td>
                  <td className="border-b border-slate-200 px-3 py-3 text-slate-700">{row.contact_number || '-'}</td>
                  <td className="border-b border-slate-200 px-3 py-3 text-slate-700">{row.email || '-'}</td>
                </tr>
              )) : (
                <tr>
                  <td colSpan={7} className="px-3 py-10 text-center text-sm text-slate-500">No users found for the selected filters.</td>
                </tr>
              )}
            </tbody>
          </table>
        </div>
      </div>
    </AppLayout>
  );
}

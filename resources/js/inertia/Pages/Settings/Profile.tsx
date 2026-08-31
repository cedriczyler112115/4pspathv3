import { Head, useForm } from '@inertiajs/react';
import AppLayout from '../../Layouts/AppLayout';

type Division = { id: number; division_name: string };
type Section = { id: number; section_name: string; division_id: number };
type Supervisor = {
  id: number;
  name?: string;
  last_name?: string;
  first_name?: string;
  middle_name?: string;
  extension_name?: string;
};

type ProfileProps = {
  appName: string;
  user: {
    id: number;
    name: string;
    email: string;
    last_name?: string | null;
    first_name?: string | null;
    middle_name?: string | null;
    extension_name?: string | null;
    position?: string | null;
    designation?: string | null;
    division_id?: number | null;
    section_id?: number | null;
    contact_number?: string | null;
    supervisor_id?: number | null;
  };
  divisions: Division[];
  sections: Section[];
  supervisors: Supervisor[];
};

export default function Profile({ appName, user, divisions, sections, supervisors }: ProfileProps) {
  const form = useForm({
    name: user?.name ?? '',
    email: user?.email ?? '',
    last_name: user?.last_name ?? '',
    first_name: user?.first_name ?? '',
    middle_name: user?.middle_name ?? '',
    extension_name: user?.extension_name ?? '',
    position: user?.position ?? '',
    designation: user?.designation ?? '',
    division_id: user?.division_id ? String(user.division_id) : '',
    section_id: user?.section_id ? String(user.section_id) : '',
    contact_number: user?.contact_number ?? '',
    supervisor_id: user?.supervisor_id ? String(user.supervisor_id) : '',
  });

  const filteredSections = form.data.division_id
    ? sections.filter((section) => String(section.division_id) === form.data.division_id)
    : sections;

  const fullName = (supervisor: Supervisor) => {
    return [supervisor.first_name, supervisor.middle_name, supervisor.last_name, supervisor.extension_name]
      .filter(Boolean)
      .join(' ')
      .trim() || supervisor.name || 'Unknown';
  };

  return (
    <AppLayout appName={appName} user={user}>
      <Head title="Profile" />
      <div className="rounded-[2rem] border border-slate-200 bg-white p-6 shadow-[0_20px_60px_rgba(15,23,42,0.08)] sm:p-8">
        <div className="flex flex-col gap-2">
          <p className="text-xs font-semibold uppercase tracking-[0.3em] text-cyan-700">My Account</p>
          <h2 className="text-2xl font-semibold tracking-tight text-slate-900">Profile settings</h2>
          <p className="max-w-2xl text-sm leading-6 text-slate-600">
            Update your personal, work, and contact information from this page.
          </p>
        </div>

        <form
          className="mt-6 space-y-6"
          onSubmit={(e) => {
            e.preventDefault();
            form.patch('/inertia/settings/profile', {
              preserveScroll: true,
            });
          }}
        >
          <div className="grid gap-4 md:grid-cols-2">
            <label className="block">
              <span className="mb-1 block text-sm font-medium text-slate-700">Lastname</span>
              <input
                value={form.data.last_name}
                onChange={(e) => form.setData('last_name', e.target.value)}
                className="h-11 w-full rounded-2xl border border-slate-300 bg-white px-4 text-sm outline-none transition focus:border-cyan-500 focus:ring-2 focus:ring-cyan-200"
              />
            </label>
            <label className="block">
              <span className="mb-1 block text-sm font-medium text-slate-700">Firstname</span>
              <input
                value={form.data.first_name}
                onChange={(e) => form.setData('first_name', e.target.value)}
                className="h-11 w-full rounded-2xl border border-slate-300 bg-white px-4 text-sm outline-none transition focus:border-cyan-500 focus:ring-2 focus:ring-cyan-200"
                autoFocus
                required
              />
            </label>
            <label className="block">
              <span className="mb-1 block text-sm font-medium text-slate-700">Middlename</span>
              <input
                value={form.data.middle_name}
                onChange={(e) => form.setData('middle_name', e.target.value)}
                className="h-11 w-full rounded-2xl border border-slate-300 bg-white px-4 text-sm outline-none transition focus:border-cyan-500 focus:ring-2 focus:ring-cyan-200"
              />
            </label>
            <label className="block">
              <span className="mb-1 block text-sm font-medium text-slate-700">Extension Name</span>
              <input
                value={form.data.extension_name}
                onChange={(e) => form.setData('extension_name', e.target.value)}
                className="h-11 w-full rounded-2xl border border-slate-300 bg-white px-4 text-sm outline-none transition focus:border-cyan-500 focus:ring-2 focus:ring-cyan-200"
              />
            </label>
          </div>

          <div className="grid gap-4 md:grid-cols-2">
            <label className="block">
              <span className="mb-1 block text-sm font-medium text-slate-700">Position</span>
              <input
                value={form.data.position}
                onChange={(e) => form.setData('position', e.target.value)}
                className="h-11 w-full rounded-2xl border border-slate-300 bg-white px-4 text-sm outline-none transition focus:border-cyan-500 focus:ring-2 focus:ring-cyan-200"
              />
            </label>
            <label className="block">
              <span className="mb-1 block text-sm font-medium text-slate-700">Designation</span>
              <input
                value={form.data.designation}
                onChange={(e) => form.setData('designation', e.target.value)}
                className="h-11 w-full rounded-2xl border border-slate-300 bg-white px-4 text-sm outline-none transition focus:border-cyan-500 focus:ring-2 focus:ring-cyan-200"
              />
            </label>
            <label className="block">
              <span className="mb-1 block text-sm font-medium text-slate-700">Division</span>
              <select
                value={form.data.division_id}
                onChange={(e) => {
                  form.setData('division_id', e.target.value);
                  form.setData('section_id', '');
                }}
                className="h-11 w-full rounded-2xl border border-slate-300 bg-white px-4 text-sm outline-none transition focus:border-cyan-500 focus:ring-2 focus:ring-cyan-200"
              >
                <option value="">Select division</option>
                {divisions.map((division) => (
                  <option key={division.id} value={division.id}>
                    {division.division_name}
                  </option>
                ))}
              </select>
            </label>
            <label className="block">
              <span className="mb-1 block text-sm font-medium text-slate-700">Section</span>
              <select
                value={form.data.section_id}
                onChange={(e) => form.setData('section_id', e.target.value)}
                className="h-11 w-full rounded-2xl border border-slate-300 bg-white px-4 text-sm outline-none transition focus:border-cyan-500 focus:ring-2 focus:ring-cyan-200"
              >
                <option value="">Select section</option>
                {filteredSections.map((section) => (
                  <option key={section.id} value={section.id}>
                    {section.section_name}
                  </option>
                ))}
              </select>
            </label>
          </div>

          <div className="grid gap-4 md:grid-cols-2">
            <label className="block">
              <span className="mb-1 block text-sm font-medium text-slate-700">Mobile Number</span>
              <input
                value={form.data.contact_number}
                onChange={(e) => form.setData('contact_number', e.target.value)}
                className="h-11 w-full rounded-2xl border border-slate-300 bg-white px-4 text-sm outline-none transition focus:border-cyan-500 focus:ring-2 focus:ring-cyan-200"
              />
            </label>
            <label className="block">
              <span className="mb-1 block text-sm font-medium text-slate-700">Your Supervisor</span>
              <select
                value={form.data.supervisor_id}
                onChange={(e) => form.setData('supervisor_id', e.target.value)}
                className="h-11 w-full rounded-2xl border border-slate-300 bg-white px-4 text-sm outline-none transition focus:border-cyan-500 focus:ring-2 focus:ring-cyan-200"
              >
                <option value="">Select supervisor</option>
                {supervisors.map((supervisor) => (
                  <option key={supervisor.id} value={supervisor.id}>
                    {fullName(supervisor)}
                  </option>
                ))}
              </select>
            </label>
          </div>

          <div className="rounded-2xl border border-slate-200 bg-slate-50 px-4 py-3">
            <div className="text-sm font-medium text-slate-900">Email</div>
            <div className="mt-1 text-sm text-slate-600">{form.data.email}</div>
          </div>

          <div className="flex flex-wrap items-center gap-3">
            <button
              type="submit"
              disabled={form.processing}
              className="inline-flex h-11 items-center justify-center rounded-2xl bg-slate-950 px-5 text-sm font-medium text-white transition hover:bg-slate-800 disabled:opacity-60"
            >
              {form.processing ? 'Saving...' : 'Save'}
            </button>
            {form.recentlySuccessful ? (
              <span className="text-sm font-medium text-emerald-700">Profile updated.</span>
            ) : null}
          </div>
        </form>
      </div>
    </AppLayout>
  );
}

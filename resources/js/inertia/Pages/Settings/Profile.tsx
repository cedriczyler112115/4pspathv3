import { Head, useForm } from '@inertiajs/react';
import AppLayout from '../../Layouts/AppLayout';
import { UserCircle, Save, Check } from 'lucide-react';

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
    is_supervisor?: boolean | number | null;
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
    is_supervisor: Boolean(user?.is_supervisor),
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
      <Head title="Profile Settings - 4Ps PATH" />

      <div className="space-y-3 max-w-3xl">
        <div className="rounded-xl border border-border bg-card p-3 sm:p-4 shadow-2xs space-y-3">
          {/* HEADER */}
          <div className="flex items-center gap-2.5 border-b border-border/80 pb-3">
            <div className="size-8 rounded-lg bg-emerald-500/10 text-emerald-700 dark:text-emerald-400 flex items-center justify-center font-bold">
              <UserCircle className="size-4.5" />
            </div>
            <div>
              <h1 className="text-sm font-bold tracking-tight text-foreground flex items-center gap-2">
                <span>Personal Profile Settings</span>
              </h1>
              <p className="text-[11px] text-muted-foreground">
                Update your personal details, designation, organization hierarchy, and direct supervisor.
              </p>
            </div>
          </div>

          <form
            className="space-y-3 pt-1"
            onSubmit={(e) => {
              e.preventDefault();
              form.patch('/settings/profile', {
                preserveScroll: true,
              });
            }}
          >
            {/* NAME FIELDS */}
            <div className="grid gap-2.5 sm:grid-cols-2 md:grid-cols-4">
              <div className="space-y-1">
                <label className="text-[11px] font-semibold text-muted-foreground">First Name</label>
                <input
                  value={form.data.first_name}
                  onChange={(e) => form.setData('first_name', e.target.value)}
                  className="h-8 w-full rounded-lg border border-input bg-background px-2.5 text-xs text-foreground outline-hidden focus:ring-2 focus:ring-ring"
                  required
                />
              </div>
              <div className="space-y-1">
                <label className="text-[11px] font-semibold text-muted-foreground">Middle Name</label>
                <input
                  value={form.data.middle_name}
                  onChange={(e) => form.setData('middle_name', e.target.value)}
                  className="h-8 w-full rounded-lg border border-input bg-background px-2.5 text-xs text-foreground outline-hidden focus:ring-2 focus:ring-ring"
                />
              </div>
              <div className="space-y-1">
                <label className="text-[11px] font-semibold text-muted-foreground">Last Name</label>
                <input
                  value={form.data.last_name}
                  onChange={(e) => form.setData('last_name', e.target.value)}
                  className="h-8 w-full rounded-lg border border-input bg-background px-2.5 text-xs text-foreground outline-hidden focus:ring-2 focus:ring-ring"
                  required
                />
              </div>
              <div className="space-y-1">
                <label className="text-[11px] font-semibold text-muted-foreground">Ext. Name</label>
                <input
                  value={form.data.extension_name}
                  onChange={(e) => form.setData('extension_name', e.target.value)}
                  placeholder="Jr., III..."
                  className="h-8 w-full rounded-lg border border-input bg-background px-2.5 text-xs text-foreground outline-hidden focus:ring-2 focus:ring-ring"
                />
              </div>
            </div>

            {/* WORK & DESIGNATION */}
            <div className="grid gap-2.5 sm:grid-cols-2">
              <div className="space-y-1">
                <label className="text-[11px] font-semibold text-muted-foreground">Position</label>
                <input
                  value={form.data.position}
                  onChange={(e) => form.setData('position', e.target.value)}
                  className="h-8 w-full rounded-lg border border-input bg-background px-2.5 text-xs text-foreground outline-hidden focus:ring-2 focus:ring-ring"
                />
              </div>
              <div className="space-y-1">
                <label className="text-[11px] font-semibold text-muted-foreground">Designation</label>
                <input
                  value={form.data.designation}
                  onChange={(e) => form.setData('designation', e.target.value)}
                  className="h-8 w-full rounded-lg border border-input bg-background px-2.5 text-xs text-foreground outline-hidden focus:ring-2 focus:ring-ring"
                />
              </div>
            </div>

            {/* DIVISION & SECTION */}
            <div className="grid gap-2.5 sm:grid-cols-2">
              <div className="space-y-1">
                <label className="text-[11px] font-semibold text-muted-foreground">Division</label>
                <select
                  value={form.data.division_id}
                  onChange={(e) => {
                    form.setData('division_id', e.target.value);
                    form.setData('section_id', '');
                  }}
                  className="h-8 w-full rounded-lg border border-input bg-background px-2.5 text-xs text-foreground outline-hidden focus:ring-2 focus:ring-ring cursor-pointer"
                >
                  <option value="">Select Division</option>
                  {divisions.map((division) => (
                    <option key={division.id} value={division.id}>
                      {division.division_name}
                    </option>
                  ))}
                </select>
              </div>
              <div className="space-y-1">
                <label className="text-[11px] font-semibold text-muted-foreground">Section</label>
                <select
                  value={form.data.section_id}
                  onChange={(e) => form.setData('section_id', e.target.value)}
                  className="h-8 w-full rounded-lg border border-input bg-background px-2.5 text-xs text-foreground outline-hidden focus:ring-2 focus:ring-ring cursor-pointer"
                >
                  <option value="">Select Section</option>
                  {filteredSections.map((section) => (
                    <option key={section.id} value={section.id}>
                      {section.section_name}
                    </option>
                  ))}
                </select>
              </div>
            </div>

            {/* CONTACT & SUPERVISOR */}
            <div className="grid gap-2.5 sm:grid-cols-2">
              <div className="space-y-1">
                <label className="text-[11px] font-semibold text-muted-foreground">Mobile Contact Number</label>
                <input
                  value={form.data.contact_number}
                  onChange={(e) => form.setData('contact_number', e.target.value)}
                  className="h-8 w-full rounded-lg border border-input bg-background px-2.5 text-xs text-foreground outline-hidden focus:ring-2 focus:ring-ring"
                />
              </div>
              <div className="space-y-1">
                <label className="text-[11px] font-semibold text-muted-foreground">Supervisor</label>
                <select
                  value={form.data.supervisor_id}
                  onChange={(e) => form.setData('supervisor_id', e.target.value)}
                  className="h-8 w-full rounded-lg border border-input bg-background px-2.5 text-xs text-foreground outline-hidden focus:ring-2 focus:ring-ring cursor-pointer"
                >
                  <option value="">Select Supervisor</option>
                  {supervisors.map((supervisor) => (
                    <option key={supervisor.id} value={supervisor.id}>
                      {fullName(supervisor)}
                    </option>
                  ))}
                </select>
              </div>
            </div>

            {/* SUPERVISOR PRIVILEGES */}
            <div>
              <label className="flex cursor-pointer items-center gap-2 rounded-lg border border-input bg-background px-3 py-2.5 hover:bg-muted/30 transition">
                <input
                  type="checkbox"
                  checked={form.data.is_supervisor}
                  onChange={(e) => form.setData('is_supervisor', e.target.checked)}
                  className="size-3.5 rounded border-input text-emerald-600 focus:ring-emerald-500 cursor-pointer"
                />
                <div>
                  <span className="text-xs text-foreground font-medium block">
                    User has Supervisor privileges
                  </span>
                  <span className="text-[11px] text-muted-foreground block">
                    Enable this if you supervise staff members and review or verify subordinate performance commitments.
                  </span>
                </div>
              </label>
            </div>

            <div className="rounded-lg border border-border bg-muted/30 px-3 py-2 flex items-center justify-between text-xs">
              <span className="text-muted-foreground">Registered Email:</span>
              <span className="font-bold text-foreground font-mono">{form.data.email}</span>
            </div>

            <div className="flex items-center justify-between pt-2 border-t border-border">
              {form.recentlySuccessful ? (
                <span className="inline-flex items-center gap-1 text-xs text-emerald-600 font-semibold">
                  <Check className="size-3.5" />
                  Profile updated successfully.
                </span>
              ) : <span />}

              <button
                type="submit"
                disabled={form.processing}
                className="inline-flex items-center gap-1.5 h-8 rounded-lg bg-emerald-600 px-3.5 text-xs font-semibold text-white hover:bg-emerald-700 transition shadow-xs disabled:opacity-50 cursor-pointer"
              >
                <Save className="size-3.5" />
                <span>{form.processing ? 'Saving...' : 'Save Profile'}</span>
              </button>
            </div>
          </form>
        </div>
      </div>
    </AppLayout>
  );
}

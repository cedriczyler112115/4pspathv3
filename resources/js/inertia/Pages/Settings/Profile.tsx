import { Head, router, useForm } from '@inertiajs/react';
import React, { useRef, useState, useMemo } from 'react';
import AppLayout from '../../Layouts/AppLayout';
import UserAvatar from '../../Components/UserAvatar';
import SearchableSelect from '../../Components/SearchableSelect';
import { UserCircle, Save, Check, Camera, Trash2, Upload, AlertTriangle, AlertCircle } from 'lucide-react';

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
    avatar?: string | null;
    avatar_url?: string | null;
  };
  divisions: Division[];
  sections: Section[];
  supervisors: Supervisor[];
  isProfileComplete?: boolean;
  missingFields?: string[];
};

export default function Profile({ appName, user, divisions, sections, supervisors, isProfileComplete = true, missingFields = [] }: ProfileProps) {
  const fileInputRef = useRef<HTMLInputElement>(null);
  const [avatarPreview, setAvatarPreview] = useState<string | null>(null);

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

  const handleAvatarSelect = (e: React.ChangeEvent<HTMLInputElement>) => {
    const file = e.target.files?.[0];
    if (!file) return;

    const reader = new FileReader();
    reader.onload = (event) => {
      setAvatarPreview(event.target?.result as string);
    };
    reader.readAsDataURL(file);

    const formData = new FormData();
    formData.append('avatar', file);

    router.post('/settings/profile', formData, {
      preserveScroll: true,
      onSuccess: () => {
        setAvatarPreview(null);
      },
    });
  };

  const handleRemoveAvatar = () => {
    router.post(
      '/settings/profile',
      { remove_avatar: '1' },
      {
        preserveScroll: true,
        onSuccess: () => {
          setAvatarPreview(null);
        },
      }
    );
  };

  const filteredSections = form.data.division_id
    ? sections.filter((section) => String(section.division_id) === form.data.division_id)
    : sections;

  const fullName = (supervisor: Supervisor) => {
    const parts = [
      supervisor.last_name ? `${supervisor.last_name},` : '',
      supervisor.first_name,
      supervisor.middle_name,
      supervisor.extension_name,
    ]
      .filter(Boolean)
      .join(' ')
      .trim();

    return (parts || supervisor.name || 'UNKNOWN').toUpperCase();
  };

  const supervisorOptions = useMemo(() => {
    return supervisors.map((s) => ({
      value: String(s.id),
      label: fullName(s),
    }));
  }, [supervisors]);

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
                Update your personal details, profile picture, designation, organization hierarchy, and direct supervisor.
              </p>
            </div>
          </div>

          {/* INCOMPLETE PROFILE WARNING BANNER */}
          {!isProfileComplete && (
            <div className="rounded-xl border border-amber-300 dark:border-amber-800/80 bg-amber-50/90 dark:bg-amber-950/40 p-3.5 sm:p-4 space-y-2 animate-in fade-in">
              <div className="flex items-center gap-2 text-amber-900 dark:text-amber-200 font-bold text-xs">
                <AlertTriangle className="size-4 text-amber-600 dark:text-amber-400 shrink-0" />
                <span>Complete Your Profile to Access the App</span>
              </div>
              <p className="text-[11px] text-amber-800/90 dark:text-amber-300/80 leading-relaxed">
                You must completely fill out all required personal and organizational details below before you can access modules, targets, ratings, and verifications in 4Ps PATH.
              </p>
              {missingFields.length > 0 && (
                <div className="pt-1">
                  <span className="text-[10.5px] font-semibold text-amber-950 dark:text-amber-200 block mb-1">
                    Missing required fields:
                  </span>
                  <div className="flex flex-wrap gap-1.5">
                    {missingFields.map((field) => (
                      <span
                        key={field}
                        className="inline-flex items-center rounded-md bg-amber-200/70 dark:bg-amber-900/60 px-2 py-0.5 text-[10px] font-semibold text-amber-900 dark:text-amber-200 border border-amber-300/60 dark:border-amber-800/60"
                      >
                        {field}
                      </span>
                    ))}
                  </div>
                </div>
              )}
            </div>
          )}

          {/* PROFILE PICTURE CARD */}
          <div className="rounded-xl border border-border bg-muted/30 p-3 sm:p-4 flex flex-col sm:flex-row items-center gap-4">
            <div className="relative group shrink-0">
              <UserAvatar
                user={{
                  ...user,
                  avatar_url: avatarPreview || user?.avatar_url,
                }}
                size="xl"
                className="shadow-md"
              />
              <button
                type="button"
                onClick={() => fileInputRef.current?.click()}
                className="absolute inset-0 rounded-full bg-black/40 text-white flex items-center justify-center opacity-0 group-hover:opacity-100 transition cursor-pointer"
                title="Change Profile Picture"
              >
                <Camera className="size-5" />
              </button>
            </div>

            <div className="space-y-1.5 text-center sm:text-left flex-1">
              <h3 className="text-xs font-bold text-foreground">Profile Picture</h3>
              <p className="text-[11px] text-muted-foreground">
                Upload a square profile photo (JPG, PNG, WEBP, max 2MB). Your profile photo replaces round initials across the application.
              </p>

              <div className="flex flex-wrap items-center justify-center sm:justify-start gap-2 pt-1">
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
                  className="h-7 px-3 rounded-lg bg-primary text-primary-foreground hover:bg-primary/90 text-xs font-semibold inline-flex items-center gap-1.5 shadow-xs transition cursor-pointer"
                >
                  <Upload className="size-3.5" />
                  <span>Upload Photo</span>
                </button>

                {(user?.avatar_url || avatarPreview) && (
                  <button
                    type="button"
                    onClick={handleRemoveAvatar}
                    className="h-7 px-3 rounded-lg border border-destructive/30 bg-destructive/10 text-destructive hover:bg-destructive/20 text-xs font-semibold inline-flex items-center gap-1.5 transition cursor-pointer"
                  >
                    <Trash2 className="size-3.5" />
                    <span>Remove Photo</span>
                  </button>
                )}
              </div>
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
                <label className="text-[11px] font-semibold text-muted-foreground flex items-center gap-1">
                  <span>First Name</span>
                  <span className="text-destructive">*</span>
                </label>
                <input
                  value={form.data.first_name}
                  onChange={(e) => form.setData('first_name', e.target.value)}
                  className={`h-8 w-full rounded-lg border ${form.errors.first_name ? 'border-destructive' : 'border-input'} bg-background px-2.5 text-xs text-foreground outline-hidden focus:ring-2 focus:ring-ring`}
                  required
                />
                {form.errors.first_name && <span className="text-[10px] text-destructive block">{form.errors.first_name}</span>}
              </div>
              <div className="space-y-1">
                <label className="text-[11px] font-semibold text-muted-foreground flex items-center gap-1">
                  <span>Middle Name</span>
                  <span className="text-destructive">*</span>
                </label>
                <input
                  value={form.data.middle_name}
                  onChange={(e) => form.setData('middle_name', e.target.value)}
                  className={`h-8 w-full rounded-lg border ${form.errors.middle_name ? 'border-destructive' : 'border-input'} bg-background px-2.5 text-xs text-foreground outline-hidden focus:ring-2 focus:ring-ring`}
                  required
                />
                {form.errors.middle_name && <span className="text-[10px] text-destructive block">{form.errors.middle_name}</span>}
              </div>
              <div className="space-y-1">
                <label className="text-[11px] font-semibold text-muted-foreground flex items-center gap-1">
                  <span>Last Name</span>
                  <span className="text-destructive">*</span>
                </label>
                <input
                  value={form.data.last_name}
                  onChange={(e) => form.setData('last_name', e.target.value)}
                  className={`h-8 w-full rounded-lg border ${form.errors.last_name ? 'border-destructive' : 'border-input'} bg-background px-2.5 text-xs text-foreground outline-hidden focus:ring-2 focus:ring-ring`}
                  required
                />
                {form.errors.last_name && <span className="text-[10px] text-destructive block">{form.errors.last_name}</span>}
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
                <label className="text-[11px] font-semibold text-muted-foreground flex items-center gap-1">
                  <span>Position</span>
                  <span className="text-destructive">*</span>
                </label>
                <input
                  value={form.data.position}
                  onChange={(e) => form.setData('position', e.target.value)}
                  className={`h-8 w-full rounded-lg border ${form.errors.position ? 'border-destructive' : 'border-input'} bg-background px-2.5 text-xs text-foreground outline-hidden focus:ring-2 focus:ring-ring`}
                  required
                />
                {form.errors.position && <span className="text-[10px] text-destructive block">{form.errors.position}</span>}
              </div>
              <div className="space-y-1">
                <label className="text-[11px] font-semibold text-muted-foreground flex items-center gap-1">
                  <span>Designation</span>
                  <span className="text-destructive">*</span>
                </label>
                <input
                  value={form.data.designation}
                  onChange={(e) => form.setData('designation', e.target.value)}
                  className={`h-8 w-full rounded-lg border ${form.errors.designation ? 'border-destructive' : 'border-input'} bg-background px-2.5 text-xs text-foreground outline-hidden focus:ring-2 focus:ring-ring`}
                  required
                />
                {form.errors.designation && <span className="text-[10px] text-destructive block">{form.errors.designation}</span>}
              </div>
            </div>

            {/* DIVISION & SECTION */}
            <div className="grid gap-2.5 sm:grid-cols-2">
              <div className="space-y-1">
                <label className="text-[11px] font-semibold text-muted-foreground flex items-center gap-1">
                  <span>Division</span>
                  <span className="text-destructive">*</span>
                </label>
                <select
                  value={form.data.division_id}
                  onChange={(e) => {
                    form.setData('division_id', e.target.value);
                    form.setData('section_id', '');
                  }}
                  className={`h-8 w-full rounded-lg border ${form.errors.division_id ? 'border-destructive' : 'border-input'} bg-background px-2.5 text-xs text-foreground outline-hidden focus:ring-2 focus:ring-ring cursor-pointer`}
                  required
                >
                  <option value="">Select Division</option>
                  {divisions.map((division) => (
                    <option key={division.id} value={division.id}>
                      {division.division_name}
                    </option>
                  ))}
                </select>
                {form.errors.division_id && <span className="text-[10px] text-destructive block">{form.errors.division_id}</span>}
              </div>
              <div className="space-y-1">
                <label className="text-[11px] font-semibold text-muted-foreground flex items-center gap-1">
                  <span>Section</span>
                  <span className="text-destructive">*</span>
                </label>
                <select
                  value={form.data.section_id}
                  onChange={(e) => form.setData('section_id', e.target.value)}
                  className={`h-8 w-full rounded-lg border ${form.errors.section_id ? 'border-destructive' : 'border-input'} bg-background px-2.5 text-xs text-foreground outline-hidden focus:ring-2 focus:ring-ring cursor-pointer`}
                  required
                >
                  <option value="">Select Section</option>
                  {filteredSections.map((section) => (
                    <option key={section.id} value={section.id}>
                      {section.section_name}
                    </option>
                  ))}
                </select>
                {form.errors.section_id && <span className="text-[10px] text-destructive block">{form.errors.section_id}</span>}
              </div>
            </div>

            {/* CONTACT & SUPERVISOR */}
            <div className="grid gap-2.5 sm:grid-cols-2">
              <div className="space-y-1">
                <label className="text-[11px] font-semibold text-muted-foreground flex items-center gap-1">
                  <span>Mobile Contact Number</span>
                  <span className="text-destructive">*</span>
                </label>
                <input
                  value={form.data.contact_number}
                  onChange={(e) => form.setData('contact_number', e.target.value)}
                  placeholder="e.g. 09123456789"
                  className={`h-8 w-full rounded-lg border ${form.errors.contact_number ? 'border-destructive' : 'border-input'} bg-background px-2.5 text-xs text-foreground outline-hidden focus:ring-2 focus:ring-ring`}
                  required
                />
                {form.errors.contact_number && <span className="text-[10px] text-destructive block">{form.errors.contact_number}</span>}
              </div>
              <div className="space-y-1">
                <label className="text-[11px] font-semibold text-muted-foreground flex items-center gap-1">
                  <span>Supervisor</span>
                  <span className="text-destructive">*</span>
                </label>
                <SearchableSelect
                  value={form.data.supervisor_id}
                  onChange={(val) => form.setData('supervisor_id', val)}
                  options={supervisorOptions}
                  placeholder="SELECT SUPERVISOR"
                  searchPlaceholder="Search supervisor by name..."
                  uppercase={true}
                  required={true}
                  error={Boolean(form.errors.supervisor_id)}
                />
                {form.errors.supervisor_id ? (
                  <span className="text-[10px] text-destructive block">{form.errors.supervisor_id}</span>
                ) : (
                  <span className="text-[10px] text-muted-foreground block">
                    Required for performance routing and verification
                  </span>
                )}
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

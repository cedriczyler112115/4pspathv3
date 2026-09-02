type PersistedUser = {
  id?: number | string | null;
  email?: string | null;
  name?: string | null;
} | null | undefined;

const STORAGE_PREFIX = '4ps-pathv3:filters:';

function getUserKey(user: PersistedUser): string {
  if (user?.id !== undefined && user?.id !== null && String(user.id).trim() !== '') {
    return `user:${String(user.id)}`;
  }

  if (user?.email && String(user.email).trim() !== '') {
    return `email:${String(user.email).trim().toLowerCase()}`;
  }

  if (user?.name && String(user.name).trim() !== '') {
    return `name:${String(user.name).trim().toLowerCase()}`;
  }

  return 'guest';
}

function getStorageKey(pageKey: string, user: PersistedUser): string {
  return `${STORAGE_PREFIX}${getUserKey(user)}:${pageKey}`;
}

export function hasPersistedFilters(pageKey: string, user: PersistedUser): boolean {
  if (typeof window === 'undefined') return false;

  try {
    return window.localStorage.getItem(getStorageKey(pageKey, user)) !== null;
  } catch {
    return false;
  }
}

export function readPersistedFilters<T extends Record<string, unknown>>(
  pageKey: string,
  user: PersistedUser,
  defaults: T,
): T {
  if (typeof window === 'undefined') {
    return defaults;
  }

  try {
    const raw = window.localStorage.getItem(getStorageKey(pageKey, user));
    if (!raw) {
      return defaults;
    }

    const parsed = JSON.parse(raw) as Partial<T>;
    const restored = { ...defaults };
    Object.keys(defaults).forEach((key) => {
      if (Object.prototype.hasOwnProperty.call(parsed, key)) {
        restored[key as keyof T] = parsed[key as keyof T] as T[keyof T];
      }
    });
    return restored;
  } catch {
    return defaults;
  }
}

export function savePersistedFilters<T extends Record<string, unknown>>(
  pageKey: string,
  user: PersistedUser,
  values: T,
): void {
  if (typeof window === 'undefined') {
    return;
  }

  try {
    window.localStorage.setItem(getStorageKey(pageKey, user), JSON.stringify(values));
  } catch {
    // Ignore quota / privacy mode errors.
  }
}

export function clearPersistedFilters(user?: PersistedUser): void {
  if (typeof window === 'undefined') {
    return;
  }

  try {
    const userKey = user ? getUserKey(user) : null;
    const keysToRemove: string[] = [];

    for (let i = 0; i < window.localStorage.length; i++) {
      const key = window.localStorage.key(i);
      if (!key || !key.startsWith(STORAGE_PREFIX)) {
        continue;
      }

      if (!userKey || key.startsWith(`${STORAGE_PREFIX}${userKey}:`)) {
        keysToRemove.push(key);
      }
    }

    keysToRemove.forEach((key) => window.localStorage.removeItem(key));
  } catch {
    // Ignore storage errors.
  }
}

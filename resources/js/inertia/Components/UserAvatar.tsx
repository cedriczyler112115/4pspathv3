import React from 'react';

type UserAvatarProps = {
  user?: {
    name?: string | null;
    first_name?: string | null;
    last_name?: string | null;
    avatar_url?: string | null;
    avatar?: string | null;
  } | null;
  size?: 'xs' | 'sm' | 'md' | 'lg' | 'xl';
  className?: string;
  fallbackInitials?: string;
};

const sizeClasses: Record<string, string> = {
  xs: 'size-5 text-[9px]',
  sm: 'size-7 text-[11px]',
  md: 'size-9 text-xs',
  lg: 'size-12 text-sm',
  xl: 'size-16 text-base',
};

export default function UserAvatar({
  user,
  size = 'sm',
  className = '',
  fallbackInitials,
}: UserAvatarProps) {
  const avatarUrl = user?.avatar_url || user?.avatar;

  const initials = React.useMemo(() => {
    if (fallbackInitials) return fallbackInitials;
    if (user?.first_name || user?.last_name) {
      const f = (user.first_name || '').trim()[0] || '';
      const l = (user.last_name || '').trim()[0] || '';
      return (f + l).toUpperCase() || 'U';
    }
    const nameStr = (user?.name || '').trim();
    if (!nameStr) return 'U';
    const parts = nameStr.split(/\s+/).filter(Boolean);
    if (parts.length >= 2) {
      return (parts[0][0] + parts[parts.length - 1][0]).toUpperCase();
    }
    return nameStr.slice(0, 2).toUpperCase();
  }, [user, fallbackInitials]);

  const baseSizeClass = sizeClasses[size] || 'size-7 text-[11px]';

  if (avatarUrl) {
    return (
      <img
        src={avatarUrl}
        alt={user?.name || 'User Profile Picture'}
        className={`${baseSizeClass} rounded-full object-cover shrink-0 border border-border/80 shadow-2xs ${className}`}
      />
    );
  }

  return (
    <div
      className={`${baseSizeClass} rounded-full bg-emerald-800 text-white flex items-center justify-center font-bold shrink-0 shadow-2xs ${className}`}
    >
      {initials}
    </div>
  );
}

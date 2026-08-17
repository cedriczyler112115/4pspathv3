[OPEN]

# Debug Session: dark-sidebar-blink

## Symptom
- In dark mode only, the left sidebar flashes/blinks during `wire:navigate` page transitions.
- In light mode, navigation is stable (no visible flash).

## Expected
- Dark mode navigation should be visually stable like light mode (no sidebar repaint/flash).

## Repro Steps (Target)
1. Set appearance to Dark.
2. Navigate between two internal links that use `wire:navigate` (example: Dashboard → Settings → Dashboard).
3. Observe the sidebar during navigation.

## Hypotheses (Falsifiable)
- H1: The `html.dark` class is being removed and re-added briefly during navigation, causing the sidebar background tokens (`--sidebar`) to switch momentarily.
- H2: CSS variables for sidebar/theme tokens are being re-applied on `livewire:navigated` (or other events) even when unchanged, triggering a repaint that is visible only in dark mode.
- H3: Livewire Navigate is replacing the sidebar DOM subtree and the repaint is more noticeable in dark mode due to different background/contrast; light mode hides it.
- H4: Some transition/animation (opacity/transform) is applied conditionally in dark mode (or by `color-scheme`) and gets re-triggered on each navigation.
- H5: A third-party script (Flux appearance bootstrap) runs again on navigation and toggles appearance state, causing a brief mismatch between `data-theme` and `html.dark`.

## Instrumentation Plan
- Add client-side event reporting for:
  - `livewire:navigating` and `livewire:navigated`
  - mutations to `document.documentElement.class`, `data-theme`, and inline `style`
  - computed sidebar background / foreground values around navigation

## Evidence Log
- Pre-fix: pending
- Post-fix: pending

## Status
- Next: start debug server, add instrumentation, reproduce, then confirm which hypothesis matches logs.


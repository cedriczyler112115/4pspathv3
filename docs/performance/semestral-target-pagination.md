# Semestral target filtering and pagination

The semestral-target page paginates indicator groups on the server. It first fetches one page of indicator IDs, then loads only the item rows belonging to those indicators. This prevents an indicator and its sub-targets from being split between pages and keeps the Livewire DOM bounded.

## Filtering

- Text search uses a 300 ms Livewire debounce.
- Filter changes reset the paginator to page one.
- Item filters use correlated `EXISTS` queries, avoiding large ID lists in PHP.
- Blade groups the returned rows by category and indicator in one pass.
- The paginator is memoized for the current Livewire request so repeated access does not rerun the query.

## Pagination

- Allowed page sizes are 10, 25, and 50 indicator groups. There is intentionally no unbounded `All` option or 100-group DOM payload.
- Numbered navigation includes first, previous, next, and last controls.
- More than seven pages are represented by the first page, the current-page neighborhood, ellipses, and the last page.
- Child Livewire keys use stable indicator IDs to avoid remounting unchanged row components.

## Database indexes

Run `php artisan migrate` to apply `2026_08_26_000001_add_semestral_target_query_indexes.php`. The indexes cover ownership, category/order filtering, item ordering and semester visibility, and checkpoint-history lookups. Free-text columns are not B-tree indexed because the page intentionally supports contains (`%term%`) matching on `TEXT` columns.

## DOM and runtime behavior

- Score-row and indicator-menu Alpine controllers are defined once in the Vite bundle. Rows render only their initial state instead of repeating controller source in every `x-data` attribute.
- Textarea autosizing uses three delegated document events and one animation-frame queue. All style writes are applied before measuring `scrollHeight`, followed by one batched height write phase.
- The mutation observer processes only newly added DOM subtrees. It never rescans the complete document after a Livewire morph.
- Context menus use `x-if`, so hidden teleported menu trees do not exist in the active DOM.
- Flux modals are rendered server-side only while their corresponding Livewire state is open.
- Pointer-driven menu movement is limited to one reactive position update per animation frame. One passive global scroll listener and one Escape listener replace per-indicator window listeners.

## Verification

After schema or query changes, verify:

1. Search sends one request after 300 ms of inactivity.
2. Changing any filter returns to page one.
3. Indicator groups are never split across pages.
4. First, previous, numbered, next, and last controls select the expected page.
5. The table-scoped loader appears without blocking page controls.
6. Query plans use the semestral target indexes for ownership, joins, ordering, and checkpoint filters.

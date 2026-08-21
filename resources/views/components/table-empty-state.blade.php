@props([
    'colspan' => 1,
    'message' => __('No records found.'),
])

<tr>
    <td colspan="{{ $colspan }}" class="border-b border-border px-3 py-8 text-center text-muted-foreground">
        {{ $message }}
    </td>
</tr>

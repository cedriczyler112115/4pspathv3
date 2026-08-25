<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Individual Performance Checkpoint Form (CY {{ $year }})</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    <style>
        @page {
            size: A4 landscape;
            margin: 8mm 10mm 10mm 10mm;
        }

        @media print {
            body {
                background: #ffffff !important;
                color: #000000 !important;
                -webkit-print-color-adjust: exact !important;
                print-color-adjust: exact !important;
            }

            .no-print {
                display: none !important;
            }

            .print-border-black {
                border-color: #000000 !important;
            }
        }

        body {
            font-family: ui-sans-serif, system-ui, -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, "Helvetica Neue", Arial, sans-serif;
            color: #000000;
            background-color: #f8fafc;
        }

        .th-header {
            background-color: #3e7d99 !important;
            color: #ffffff !important;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: 0.025em;
            font-size: 11px;
            padding: 8px 10px;
            border: 1px solid #1e4f66;
            text-align: center;
        }

        .table-checkpoint td {
            border: 1px solid #1e293b;
            padding: 8px 10px;
            vertical-align: top;
            font-size: 11px;
            line-height: 1.4;
        }

        .label-italic {
            font-weight: 700;
            font-style: italic;
        }
    </style>
</head>

<body class="min-h-screen p-4 sm:p-6 print:p-0">

    <!-- Floating Action Toolbar (Hidden when printing) -->
    <div
        class="no-print fixed top-4 right-4 z-50 flex items-center gap-2 rounded-xl border border-slate-300 bg-white/90 p-2 shadow-xl backdrop-blur-md dark:border-zinc-700 dark:bg-zinc-900/90">
        <button type="button" onclick="window.print()"
            class="inline-flex items-center gap-2 rounded-lg bg-sky-600 px-4 py-2 text-xs font-semibold text-white shadow-sm hover:bg-sky-700 transition-colors">
            <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2"
                stroke="currentColor" class="size-4">
                <path stroke-linecap="round" stroke-linejoin="round"
                    d="M6.72 13.829c-.24.03-.48.062-.72.096m.72-.096a42.415 42.415 0 0 1 10.56 0m-10.56 0L6.34 18m10.94-4.171c.24.03.48.062.72.096m-.72-.096L17.66 18m0 0 .229 2.523a1.125 1.125 0 0 1-1.12 1.227H7.231c-.662 0-1.18-.568-1.12-1.227L6.34 18m11.318 0h1.091A2.25 2.25 0 0 0 21 15.75V9.456c0-1.081-.768-2.015-1.837-2.175a48.055 48.055 0 0 0-1.913-.247M6.34 18H5.25A2.25 2.25 0 0 1 3 15.75V9.456c0-1.081.768-2.015 1.837-2.175a48.041 48.041 0 0 1 1.913-.247m10.5 0a48.536 48.536 0 0 0-10.5 0m10.5 0V3.375c0-.621-.504-1.125-1.125-1.125h-8.25c-.621 0-1.125.504-1.125 1.125v3.656" />
            </svg>
            Print Document
        </button>
        <button type="button" onclick="window.close()"
            class="inline-flex items-center gap-1.5 rounded-lg border border-slate-300 bg-white px-3 py-2 text-xs font-semibold text-slate-700 hover:bg-slate-100 dark:border-zinc-700 dark:bg-zinc-800 dark:text-zinc-200 dark:hover:bg-zinc-700 transition-colors">
            Close Tab
        </button>
    </div>

    <!-- Main Print Container -->
    <div class="mx-auto max-w-7xl bg-white p-6 shadow-md print:max-w-none print:p-0 print:shadow-none">

        <!-- Document Header Section -->
        <div class="relative mb-6">
            <!-- Top Right Annex Code -->
            <div class="text-right text-[10px] font-medium text-slate-600 print:text-black">
                Annex M. Individual Performance Checkpoint Form
            </div>

            <!-- Top Left DSWD & Bagong Pilipinas Logos -->
            <div class="flex items-center justify-between">
                <div class="flex items-center gap-3">
                    <img src="{{ asset('logos/dswd1.png') }}" alt="DSWD Logo" style="width: 150px;"
                        class="h-12 w-auto object-contain">
                    <img src="{{ asset('logos/bagongpilipinas1.png') }}" style="width: 50px;margin-top:-10px;"
                        alt="Bagong Pilipinas Logo" class="h-12 w-auto object-contain">
                </div>

                <!-- Center Title Header Block -->
                <div class="flex-1 text-center px-4">
                    <h1 class="text-xs font-bold uppercase tracking-wide text-slate-900 print:text-black">
                        DEPARTMENT OF SOCIAL WELFARE AND DEVELOPMENT
                    </h1>
                    <h2 class="text-sm font-extrabold uppercase tracking-wide text-slate-700 print:text-black mt-0.5">
                        Individual Performance Checkpoint Form
                    </h2>
                    <div class="text-xs font-bold text-slate-900 print:text-black mt-0.5">
                        CY {{ $year }}
                    </div>
                    <div class="text-xs font-extrabold uppercase tracking-wide text-slate-900 print:text-black mt-0.5">
                        FIELD OFFICE CARAGA - {{ $rateeDivision }}
                    </div>
                </div>

                <div class="w-32"></div>
            </div>
        </div>

        <!-- Checkpoint Table -->
        <div class="w-full overflow-hidden">
            <table class="table-checkpoint w-full border-collapse">
                <thead>
                    <tr>
                        <th class="th-header w-[5%]">NO.</th>
                        <th class="th-header w-[32%] text-left">ORIGINAL SUCCESS INDICATOR</th>
                        <th class="th-header w-[32%] text-left">PROPOSED AMENDMENT</th>
                        <th class="th-header w-[15%] text-left">JUSTIFICATION</th>
                        <th class="th-header w-[16%] text-left">REMARKS OF RATER</th>
                    </tr>
                </thead>
                <tbody>
                    @php $rowIndex = 1; @endphp

                    @forelse ($activeRows as $row)
                        @if ($row->is_new_target ?? false)
                            <tr class="bg-slate-100 dark:bg-zinc-800 font-bold border-y border-black">
                                <td colspan="5" class="py-1.5 px-3 text-center uppercase tracking-wide text-xs text-black">
                                    {{ '--- NEW TARGET ADDED: ' . ($row->activity_title ?: 'NEW ENTRY') . ' ---' }}
                                </td>
                            </tr>
                        @endif
                        <tr>
                            <!-- Row Number -->
                            <td class="text-center font-bold align-top">
                                {{ $rowIndex++ }}
                            </td>

                            <!-- Original Success Indicator (Target & Item Groups) -->
                            <td class="align-top">
                                <div class="space-y-3">
                                    @if (!empty($row->target_fields))
                                        <div class="space-y-1.5">
                                            @foreach ($row->target_fields as $field)
                                                <div>
                                                    <span class="label-italic">{{ $field->field_label }}</span><br>
                                                    <span
                                                        class="text-slate-900 print:text-black">{!! nl2br(e($field->old_value)) !!}</span>
                                                </div>
                                            @endforeach
                                        </div>
                                    @endif

                                    @foreach ($row->item_groups as $itemGroup)
                                        <div
                                            class="space-y-1.5 @if(count($row->item_groups) > 1 && (!$loop->first || !empty($row->target_fields))) pt-2 border-t border-slate-200 print:border-slate-400 @endif">
                                            @if (!empty($itemGroup->item_label))
                                                <div
                                                    class="text-[11px] font-bold uppercase tracking-wider text-slate-700 print:text-black">
                                                    {{ $itemGroup->item_label }}
                                                </div>
                                            @endif
                                            @foreach ($itemGroup->fields as $field)
                                                <div>
                                                    <span class="label-italic">{{ $field->field_label }}</span><br>
                                                    <span
                                                        class="text-slate-900 print:text-black">{!! nl2br(e($field->old_value)) !!}</span>
                                                </div>
                                            @endforeach
                                        </div>
                                    @endforeach
                                </div>
                            </td>

                            <!-- Proposed Amendment (Target & Item Groups) -->
                            <td class="align-top">
                                <div class="space-y-3">
                                    @if (!empty($row->target_fields))
                                        <div class="space-y-1.5">
                                            @foreach ($row->target_fields as $field)
                                                <div>
                                                    <span class="label-italic">{{ $field->field_label }}</span><br>
                                                    <span
                                                        class="text-slate-900 print:text-black">{!! nl2br(e($field->new_value)) !!}</span>
                                                </div>
                                            @endforeach
                                        </div>
                                    @endif

                                    @foreach ($row->item_groups as $itemGroup)
                                        <div
                                            class="space-y-1.5 @if(count($row->item_groups) > 1 && (!$loop->first || !empty($row->target_fields))) pt-2 border-t border-slate-200 print:border-slate-400 @endif">
                                            @if (!empty($itemGroup->item_label))
                                                <div
                                                    class="text-[11px] font-bold uppercase tracking-wider text-slate-700 print:text-black">
                                                    {{ $itemGroup->item_label }}
                                                </div>
                                            @endif
                                            @foreach ($itemGroup->fields as $field)
                                                <div>
                                                    <span class="label-italic">{{ $field->field_label }}</span><br>
                                                    <span
                                                        class="text-slate-900 print:text-black">{!! nl2br(e($field->new_value)) !!}</span>
                                                </div>
                                            @endforeach
                                        </div>
                                    @endforeach
                                </div>
                            </td>

                            <!-- Justification -->
                            <td class="align-top">
                                <span class="text-slate-900 print:text-black">{!! nl2br(e($row->justification)) !!}</span>
                            </td>

                            <!-- Remarks of Rater -->
                            <td class="align-top">
                                <div class="space-y-2">
                                    <div class="flex items-center gap-2">
                                        <div class="size-3.5 border border-black bg-white"></div>
                                        <span class="text-xs font-semibold">Approved</span>
                                    </div>
                                    <div class="flex items-center gap-2">
                                        <div class="size-3.5 border border-black bg-white"></div>
                                        <span class="text-xs font-semibold">Disapproved</span>
                                    </div>
                                    <div class="mt-3">
                                        <span class="font-semibold">Remarks:</span>
                                    </div>
                                </div>
                            </td>
                        </tr>
                    @empty
                        @if ($deletedRows->isEmpty())
                            <tr>
                                <td colspan="5" class="py-8 text-center text-sm font-medium text-slate-500">
                                    {{ __('No checkpoint entries or target amendments recorded in edit history.') }}
                                </td>
                            </tr>
                        @endif
                    @endforelse

                    <!-- Single Merged Separator Row for Deleted Targets -->
                    @if ($deletedRows->isNotEmpty())
                        <tr
                            class="bg-slate-100 dark:bg-zinc-800 font-bold border-y border-black text-red-900 dark:text-red-300">
                            <td colspan="5"
                                class="py-1.5 px-3 text-center uppercase tracking-wide text-xs text-red-900 dark:text-red-300">
                                --- DELETED TARGETS ---
                            </td>
                        </tr>

                        @foreach ($deletedRows as $row)
                            <tr>
                                <!-- Row Number -->
                                <td class="text-center font-bold align-top">
                                    {{ $rowIndex++ }}
                                </td>

                                <!-- Original Success Indicator -->
                                <td class="align-top">
                                    <div class="space-y-3">
                                        @if (!empty($row->target_fields))
                                            <div class="space-y-1.5">
                                                @foreach ($row->target_fields as $field)
                                                    <div>
                                                        <span class="label-italic">{{ $field->field_label }}</span><br>
                                                        <span
                                                            class="text-slate-900 print:text-black">{!! nl2br(e($field->old_value)) !!}</span>
                                                    </div>
                                                @endforeach
                                            </div>
                                        @endif

                                        @foreach ($row->item_groups as $itemGroup)
                                            <div
                                                class="space-y-1.5 @if(count($row->item_groups) > 1 && (!$loop->first || !empty($row->target_fields))) pt-2 border-t border-slate-200 print:border-slate-400 @endif">
                                                @if (!empty($itemGroup->item_label))
                                                    <div
                                                        class="text-[11px] font-bold uppercase tracking-wider text-slate-700 print:text-black">
                                                        {{ $itemGroup->item_label }}
                                                    </div>
                                                @endif
                                                @foreach ($itemGroup->fields as $field)
                                                    <div>
                                                        <span class="label-italic">{{ $field->field_label }}</span><br>
                                                        <span
                                                            class="text-slate-900 print:text-black">{!! nl2br(e($field->old_value)) !!}</span>
                                                    </div>
                                                @endforeach
                                            </div>
                                        @endforeach
                                    </div>
                                </td>

                                <!-- Proposed Amendment -->
                                <td class="align-top">
                                    <div class="space-y-3">
                                        @if (!empty($row->target_fields))
                                            <div class="space-y-1.5">
                                                @foreach ($row->target_fields as $field)
                                                    <div>
                                                        <span class="label-italic">{{ $field->field_label }}</span><br>
                                                        <span
                                                            class="text-slate-900 print:text-black">{!! nl2br(e($field->new_value)) !!}</span>
                                                    </div>
                                                @endforeach
                                            </div>
                                        @endif

                                        @foreach ($row->item_groups as $itemGroup)
                                            <div
                                                class="space-y-1.5 @if(count($row->item_groups) > 1 && (!$loop->first || !empty($row->target_fields))) pt-2 border-t border-slate-200 print:border-slate-400 @endif">
                                                @if (!empty($itemGroup->item_label))
                                                    <div
                                                        class="text-[11px] font-bold uppercase tracking-wider text-slate-700 print:text-black">
                                                        {{ $itemGroup->item_label }}
                                                    </div>
                                                @endif
                                                @foreach ($itemGroup->fields as $field)
                                                    <div>
                                                        <span class="label-italic">{{ $field->field_label }}</span><br>
                                                        <span
                                                            class="text-slate-900 print:text-black">{!! nl2br(e($field->new_value)) !!}</span>
                                                    </div>
                                                @endforeach
                                            </div>
                                        @endforeach
                                    </div>
                                </td>

                                <!-- Justification -->
                                <td class="align-top">
                                    <span class="text-slate-900 print:text-black">{!! nl2br(e($row->justification)) !!}</span>
                                </td>

                                <!-- Remarks of Rater -->
                                <td class="align-top">
                                    <div class="space-y-2">
                                        <div class="flex items-center gap-2">
                                            <div class="size-3.5 border border-black bg-white"></div>
                                            <span class="text-xs font-semibold">Approved</span>
                                        </div>
                                        <div class="flex items-center gap-2">
                                            <div class="size-3.5 border border-black bg-white"></div>
                                            <span class="text-xs font-semibold">Disapproved</span>
                                        </div>
                                        <div class="mt-3">
                                            <span class="font-semibold">Remarks:</span>
                                        </div>
                                    </div>
                                </td>
                            </tr>
                        @endforeach
                    @endif
                </tbody>
            </table>
        </div>

        <!-- Footer Signatures Section -->
        <div class="mt-12 grid grid-cols-3 gap-8 pt-4">
            <!-- Prepared By (Ratee) -->
            <div class="space-y-8">
                <div>
                    <div class="text-xs font-medium text-slate-700 print:text-black">Prepared by:</div>
                    <div class="text-xs font-medium text-slate-700 print:text-black">Position:</div>
                    <div class="text-xs font-medium text-slate-700 print:text-black">Date:</div>
                </div>
                <div class="text-center">
                    <div
                        class="inline-block border-b border-black pb-0.5 text-xs font-extrabold tracking-wide uppercase text-black">
                        {{ $rateeFullName }}
                    </div>
                    <div class="text-[11px] font-bold uppercase text-black mt-0.5">
                        {{ $rateePosition }}
                    </div>
                    <div class="text-[10px] text-slate-700 print:text-black mt-0.5">
                        {{ $dateFormatted }}
                    </div>
                </div>
            </div>

            <!-- Recommending Approval (Supervisor) -->
            <div class="space-y-8">
                <div>
                    <div class="text-xs font-medium text-slate-700 print:text-black">Recommending Approval:</div>
                    <div class="text-xs font-medium text-slate-700 print:text-black">Position:</div>
                    <div class="text-xs font-medium text-slate-700 print:text-black">Date:</div>
                </div>
                <div class="text-center">
                    <div
                        class="inline-block border-b border-black pb-0.5 text-xs font-extrabold tracking-wide uppercase text-black">
                        {{ $supFullName }}
                    </div>
                    <div class="text-[11px] font-bold uppercase text-black mt-0.5">
                        {{ $supPosition }}
                    </div>
                </div>
            </div>

            <!-- Approved By (Division Chief / Head) -->
            <div class="space-y-8">
                <div>
                    <div class="text-xs font-medium text-slate-700 print:text-black">Approved by:</div>
                    <div class="text-xs font-medium text-slate-700 print:text-black">Position:</div>
                    <div class="text-xs font-medium text-slate-700 print:text-black">Date:</div>
                </div>
                <div class="text-center">
                    <div
                        class="inline-block border-b border-black pb-0.5 text-xs font-bold tracking-wide uppercase text-slate-500 print:text-black">
                        {{ $appFullName }}
                    </div>
                    <div class="text-[11px] font-medium uppercase text-slate-500 print:text-black mt-0.5">
                        {{ $appPosition }}
                    </div>
                </div>
            </div>
        </div>

    </div>

    <!-- Auto Trigger Print -->
    <script>
        window.addEventListener('DOMContentLoaded', () => {
            setTimeout(() => {
                window.print();
            }, 600);
        });
    </script>
</body>

</html>
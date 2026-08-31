<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Individual Performance Commitment and Review Form (CY {{ $year }})</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    <style>
        @page { size: A4 landscape; margin: 5mm 6mm; }
        body { font-family: ui-sans-serif, system-ui, -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, Arial, sans-serif; color: #000; background: #fff; line-height: 1.2; }
        .ipcrf-table th, .ipcrf-table td { border: 1px solid #222; font-size: 9px; padding: 3px 5px; vertical-align: top; }
        .section-title { background: #5f87a6; color: #fff; font-weight: 700; text-transform: uppercase; font-size: 10px; padding: 3px 5px; }
        .header-cell { background: #dbe7ef; font-weight: 700; text-align: center; }
        .subheader-cell { background: #eef4f8; font-weight: 700; text-align: center; }
        .small { font-size: 9px; }
    </style>
</head>
<body class="p-2">
    <div class="no-print fixed top-4 right-4 z-50 flex gap-2">
        <button type="button" onclick="window.print()" class="rounded bg-sky-600 px-4 py-2 text-xs font-semibold text-white">Print Document</button>
        <button type="button" onclick="window.close()" class="rounded border px-4 py-2 text-xs font-semibold">Close Tab</button>
    </div>

    <div class="mx-auto max-w-[1600px]">
        <div class="relative mb-2">
            <div class="text-right text-[9px] font-medium">Annex F.2 Individual Performance Commitment and Review Form - Ratings</div>
            <div class="flex items-center justify-between mt-1">
                <div class="flex items-center gap-2">
                    <img src="{{ asset('logos/dswd1.png') }}" alt="DSWD" style="width:100px;">
                    <img src="{{ asset('logos/bagongpilipinas1.png') }}" alt="Bagong Pilipinas" style="width:35px;">
                </div>
                <div class="flex-1 text-center leading-tight">
                    <div class="text-xs font-bold">DEPARTMENT OF SOCIAL WELFARE AND DEVELOPMENT</div>
                    <div class="text-xs">Individual Performance Commitment and Review Form (IPCRF)</div>
                    <div class="text-xs font-semibold">{{ $semester }}ST SEMESTER, CY {{ $year }}</div>
                    <div class="text-xs font-semibold">{{ $rateeDivision }}</div>
                </div>
                <div class="w-24"></div>
            </div>
        </div>

        <table class="ipcrf-table w-full border-collapse">
            <thead>
                <tr>
                    <th rowspan="2" class="header-cell w-[20%]">Key Result Area (KRA)</th>
                    <th colspan="3" class="header-cell">Performance Commitment</th>
                    <th colspan="5" class="header-cell">Performance Evaluation</th>
                </tr>
                <tr>
                    <th class="subheader-cell w-[20%]">Success Indicator (SI)</th>
                    <th class="subheader-cell w-[20%]">Accomplishment</th>
                    <th class="subheader-cell w-[5%]">Efficiency (E)</th>
                    <th class="subheader-cell w-[5%]">Quality (Q)</th>
                    <th class="subheader-cell w-[5%]">Timeliness (T)</th>
                    <th class="subheader-cell w-[5%]">Average</th>
                    <th class="subheader-cell w-[12%]">Means of Verification</th>
                    <th class="subheader-cell w-[8%]">Remarks</th>
                </tr>
            </thead>
            <tbody>
                @forelse ($rows as $row)
                    <tr>
                        <td>
                            <div class="font-semibold">
                                {{ \App\Support\KraCategory::label((int) ($row->kra_category ?? 0)) }}
                            </div>
                            <div class="mt-0.5 text-[8px]">{{ $row->activity }}</div>
                        </td>
                        <td>{!! nl2br(e($row->description)) !!}</td>
                        <td>{!! nl2br(e($row->actual_accomp)) !!}</td>
                        <td class="text-center font-semibold">{{ $row->rg_quantity ?: '-' }}</td>
                        <td class="text-center font-semibold">{{ $row->rg_quality ?: '-' }}</td>
                        <td class="text-center font-semibold">{{ $row->rg_timeliness ?: '-' }}</td>
                        <td class="text-center font-bold">{{ $row->average ?: '-' }}</td>
                        <td>{!! nl2br(e($row->target_movs)) !!}</td>
                        <td>{!! nl2br(e($row->target_remarks)) !!}</td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="9" class="py-4 text-center text-xs">No IPCRF records found.</td>
                    </tr>
                @endforelse
            </tbody>
        </table>

        <div class="mt-4 grid grid-cols-3 gap-2 text-center small">
            <div class="border border-black p-3">
                <div class="font-bold">{{ $rateeFullName }}</div>
                <div>{{ $rateePosition }}</div>
                <div class="mt-1">Date Signed: ________</div>
            </div>
            <div class="border border-black p-3">
                <div class="font-bold">{{ $supFullName }}</div>
                <div>{{ $supPosition }}</div>
                <div class="mt-1">Date Signed: ________</div>
            </div>
            <div class="border border-black p-3">
                <div class="font-bold">APPROVED BY</div>
                <div class="text-[8px]">POSITION / DESIGNATION</div>
                <div class="mt-1">Date Signed: ________</div>
            </div>
        </div>
    </div>
</body>
</html>

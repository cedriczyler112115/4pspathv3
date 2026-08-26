<?php

require __DIR__ . '/vendor/autoload.php';

$app = require __DIR__ . '/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

$indicator = Illuminate\Support\Facades\DB::table('ipc_targets_indicators as iti')
    ->join('ipc_targets_indicators_itemlist as itl', 'itl.ind_id', '=', 'iti.id')
    ->select('iti.id', 'iti.user_id', 'iti.kra_category', 'iti.target_year', 'iti.activity')
    ->groupBy('iti.id', 'iti.user_id', 'iti.kra_category', 'iti.target_year', 'iti.activity')
    ->orderByDesc('iti.id')
    ->first();

if (! $indicator) {
    echo "no indicator\n";
    exit(0);
}

echo "indicator={$indicator->id} user={$indicator->user_id} kra={$indicator->kra_category} year={$indicator->target_year}\n";

$items = Illuminate\Support\Facades\DB::table('ipc_targets_indicators_itemlist')
    ->where('ind_id', $indicator->id)
    ->get();

echo 'items=' . $items->count() . "\n";

$component = new App\Livewire\Pages\AnnualTargetPage();
$component->yearFilter = (string) $indicator->target_year;

try {
    Illuminate\Support\Facades\DB::beginTransaction();
    $component->copyStaffTargetGroup((int) $indicator->id);
    Illuminate\Support\Facades\DB::rollBack();
    echo "copyStaffTargetGroup=ok\n";
} catch (Throwable $e) {
    if (Illuminate\Support\Facades\DB::transactionLevel() > 0) {
        Illuminate\Support\Facades\DB::rollBack();
    }
    echo get_class($e) . ': ' . $e->getMessage() . "\n";
}

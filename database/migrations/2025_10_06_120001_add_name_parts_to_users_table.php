<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            if (! Schema::hasColumn('users', 'last_name')) {
                $table->string('last_name', 150)->nullable()->after('name');
            }
            if (! Schema::hasColumn('users', 'first_name')) {
                $table->string('first_name', 150)->nullable()->after('last_name');
            }
            if (! Schema::hasColumn('users', 'middle_name')) {
                $table->string('middle_name', 150)->nullable()->after('first_name');
            }
            if (! Schema::hasColumn('users', 'extension_name')) {
                $table->string('extension_name', 50)->nullable()->after('middle_name');
            }
        });

        // Attempt best-effort backfill from existing 'name' field
        DB::table('users')->select(['id', 'name'])->orderBy('id')->chunkById(200, function ($users) {
            foreach ($users as $user) {
                if (empty($user->name)) {
                    continue;
                }
                [$first, $middle, $last, $ext] = $this->splitName($user->name);
                DB::table('users')->where('id', $user->id)->update([
                    'first_name' => $first,
                    'middle_name' => $middle,
                    'last_name' => $last,
                    'extension_name' => $ext,
                ]);
            }
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn(['last_name', 'first_name', 'middle_name', 'extension_name']);
        });
    }

    private function splitName(string $name): array
    {
        $name = trim(preg_replace('/\s+/', ' ', $name));
        $first = null; $middle = null; $last = null; $ext = null;

        $extPattern = '/^(jr\.?|sr\.?|ii|iii|iv)$/i';

        if (str_contains($name, ',')) {
            [$lastPart, $restPart] = array_map('trim', explode(',', $name, 2));
            $last = $lastPart ?: null;
            $tokens = $restPart !== '' ? explode(' ', $restPart) : [];
            if (!empty($tokens)) {
                $first = array_shift($tokens);
                if (!empty($tokens)) {
                    $maybeExt = end($tokens);
                    if ($maybeExt && preg_match($extPattern, $maybeExt)) {
                        $ext = $maybeExt;
                        array_pop($tokens);
                    }
                    $middle = !empty($tokens) ? implode(' ', $tokens) : null;
                }
            }
        } else {
            $tokens = explode(' ', $name);
            $tokens = array_values(array_filter($tokens, fn($t) => $t !== ''));
            if (count($tokens) === 1) {
                $first = $tokens[0];
            } elseif (count($tokens) >= 2) {
                // Check for extension at end
                $maybeExt = $tokens[count($tokens) - 1];
                if ($maybeExt && preg_match($extPattern, $maybeExt)) {
                    $ext = $maybeExt;
                    array_pop($tokens);
                }
                $last = array_pop($tokens);
                $first = array_shift($tokens);
                $middle = !empty($tokens) ? implode(' ', $tokens) : null;
            }
        }

        return [$first, $middle, $last, $ext];
    }
};
<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table): void {
            if (! Schema::hasColumn('users', 'lastname')) {
                $table->string('lastname')->nullable()->after('name');
            }

            if (! Schema::hasColumn('users', 'firstname')) {
                $table->string('firstname')->nullable()->after('lastname');
            }

            if (! Schema::hasColumn('users', 'middlename')) {
                $table->string('middlename')->nullable()->after('firstname');
            }

            if (! Schema::hasColumn('users', 'extension_name')) {
                $table->string('extension_name')->nullable()->after('middlename');
            }

            if (! Schema::hasColumn('users', 'position')) {
                $table->string('position')->nullable()->after('extension_name');
            }

            if (! Schema::hasColumn('users', 'designation')) {
                $table->string('designation')->nullable()->after('position');
            }

            if (! Schema::hasColumn('users', 'division')) {
                $table->string('division')->nullable()->after('designation');
            }

            if (! Schema::hasColumn('users', 'section')) {
                $table->string('section')->nullable()->after('division');
            }

            if (! Schema::hasColumn('users', 'mobile_number')) {
                $table->string('mobile_number')->nullable()->after('section');
            }

            if (! Schema::hasColumn('users', 'supervisor_id')) {
                $table->foreignId('supervisor_id')->nullable()->after('mobile_number')->constrained('users')->nullOnDelete();
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('users', function (Blueprint $table): void {
            if (Schema::hasColumn('users', 'supervisor_id')) {
                $table->dropConstrainedForeignId('supervisor_id');
            }

            $columns = array_values(array_filter([
                Schema::hasColumn('users', 'lastname') ? 'lastname' : null,
                Schema::hasColumn('users', 'firstname') ? 'firstname' : null,
                Schema::hasColumn('users', 'middlename') ? 'middlename' : null,
                Schema::hasColumn('users', 'extension_name') ? 'extension_name' : null,
                Schema::hasColumn('users', 'position') ? 'position' : null,
                Schema::hasColumn('users', 'designation') ? 'designation' : null,
                Schema::hasColumn('users', 'division') ? 'division' : null,
                Schema::hasColumn('users', 'section') ? 'section' : null,
                Schema::hasColumn('users', 'mobile_number') ? 'mobile_number' : null,
            ]));

            if ($columns !== []) {
                $table->dropColumn($columns);
            }
        });
    }
};

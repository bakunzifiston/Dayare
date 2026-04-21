<?php

use App\Models\BusinessUser;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        DB::table('businesses')
            ->select(['id', 'user_id'])
            ->orderBy('id')
            ->get()
            ->each(function (object $row): void {
                if (! $row->user_id) {
                    return;
                }

                DB::table('business_user')->updateOrInsert(
                    ['business_id' => (int) $row->id, 'user_id' => (int) $row->user_id],
                    ['role' => BusinessUser::ROLE_ORG_ADMIN, 'updated_at' => now(), 'created_at' => now()]
                );
            });

        DB::table('business_user')
            ->where('role', 'manager')
            ->update(['role' => BusinessUser::ROLE_OPERATIONS_MANAGER, 'updated_at' => now()]);

        DB::table('business_user')
            ->where('role', 'staff')
            ->update(['role' => BusinessUser::ROLE_INSPECTOR, 'updated_at' => now()]);
    }

    public function down(): void
    {
        DB::table('business_user')
            ->where('role', BusinessUser::ROLE_OPERATIONS_MANAGER)
            ->update(['role' => 'manager', 'updated_at' => now()]);

        DB::table('business_user')
            ->where('role', BusinessUser::ROLE_INSPECTOR)
            ->update(['role' => 'staff', 'updated_at' => now()]);
    }
};

<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasColumn('livestock', 'quality_band') && Schema::hasColumn('livestock', 'health_status')) {
            Schema::table('livestock', function (Blueprint $table) {
                $table->string('quality_band', 32)->nullable()->after('base_price');
            });

            DB::table('livestock')->whereNotNull('health_status')->update([
                'quality_band' => DB::raw('health_status'),
            ]);

            Schema::table('livestock', function (Blueprint $table) {
                $table->dropColumn('health_status');
            });
        }

        if ($this->indexExists('livestock', 'livestock_farm_id_type_breed_unique')) {
            if (! $this->indexExists('livestock', 'livestock_farm_id_index')) {
                Schema::table('livestock', function (Blueprint $table) {
                    $table->index('farm_id', 'livestock_farm_id_index');
                });
            }

            Schema::table('livestock', function (Blueprint $table) {
                $table->dropUnique(['farm_id', 'type', 'breed']);
            });
        }

        Schema::table('livestock', function (Blueprint $table) {
            if (! Schema::hasColumn('livestock', 'livestock_name')) {
                $table->string('livestock_name')->nullable()->after('farm_id');
            }
            if (! Schema::hasColumn('livestock', 'livestock_code')) {
                $table->string('livestock_code', 40)->nullable()->after('livestock_name');
            }
            if (! Schema::hasColumn('livestock', 'livestock_type')) {
                $table->string('livestock_type', 64)->nullable()->after('type');
            }
            if (! Schema::hasColumn('livestock', 'production_purpose')) {
                $table->string('production_purpose', 64)->nullable()->after('livestock_type');
            }
            if (! Schema::hasColumn('livestock', 'total_count')) {
                $table->unsignedInteger('total_count')->default(0)->after('available_quantity');
            }
            if (! Schema::hasColumn('livestock', 'male_count')) {
                $table->unsignedInteger('male_count')->default(0)->after('total_count');
            }
            if (! Schema::hasColumn('livestock', 'female_count')) {
                $table->unsignedInteger('female_count')->default(0)->after('male_count');
            }
            if (! Schema::hasColumn('livestock', 'young_count')) {
                $table->unsignedInteger('young_count')->default(0)->after('female_count');
            }
            if (! Schema::hasColumn('livestock', 'farming_method')) {
                $table->string('farming_method', 64)->nullable()->after('feeding_type');
            }
            if (! Schema::hasColumn('livestock', 'feeding_method')) {
                $table->string('feeding_method', 64)->nullable()->after('farming_method');
            }
            if (! Schema::hasColumn('livestock', 'water_source')) {
                $table->string('water_source', 64)->nullable()->after('feeding_method');
            }
            if (! Schema::hasColumn('livestock', 'acquisition_date')) {
                $table->date('acquisition_date')->nullable()->after('water_source');
            }
            if (! Schema::hasColumn('livestock', 'acquisition_source')) {
                $table->string('acquisition_source', 120)->nullable()->after('acquisition_date');
            }
            if (! Schema::hasColumn('livestock', 'health_status')) {
                $table->string('health_status', 32)->nullable()->after('acquisition_source');
            }
            if (! Schema::hasColumn('livestock', 'lifecycle_status')) {
                $table->string('lifecycle_status', 32)->default('active')->after('health_status');
            }
            if (! Schema::hasColumn('livestock', 'housing_location')) {
                $table->string('housing_location', 255)->nullable()->after('lifecycle_status');
            }
            if (! Schema::hasColumn('livestock', 'notes')) {
                $table->text('notes')->nullable()->after('housing_location');
            }
            if (! Schema::hasColumn('livestock', 'status')) {
                $table->string('status', 32)->default('active')->after('notes');
            }
            if (! Schema::hasColumn('livestock', 'created_by')) {
                $table->foreignId('created_by')->nullable()->after('status')->constrained('users')->nullOnDelete();
            }
            if (! Schema::hasColumn('livestock', 'deleted_at')) {
                $table->softDeletes();
            }
        });

        if (! $this->indexExists('livestock', 'livestock_farm_id_livestock_code_unique')) {
            Schema::table('livestock', function (Blueprint $table) {
                $table->unique(['farm_id', 'livestock_code']);
            });
        }

        DB::table('livestock')->orderBy('id')->lazyById()->each(function (object $row): void {
            if ($row->livestock_code !== null && $row->livestock_code !== '') {
                return;
            }

            $type = (string) ($row->type ?? 'livestock');
            $breed = trim((string) ($row->breed ?? ''));
            $name = $breed !== '' ? ucfirst($type).' · '.$breed : ucfirst($type);
            $code = sprintf('LSK-%d-%04d', (int) $row->farm_id, (int) $row->id);

            DB::table('livestock')->where('id', $row->id)->update([
                'livestock_name' => $row->livestock_name ?: $name,
                'livestock_code' => $code,
                'livestock_type' => $row->livestock_type ?: $type,
                'total_count' => (int) ($row->total_count ?? $row->total_quantity ?? 0),
                'health_status' => $row->health_status ?: (((int) ($row->sick_quantity ?? 0)) > 0 ? 'sick' : 'healthy'),
                'lifecycle_status' => $row->lifecycle_status ?: 'active',
                'status' => $row->status ?: 'active',
                'feeding_method' => $row->feeding_method ?: $row->feeding_type,
            ]);
        });
    }

    public function down(): void
    {
        if ($this->indexExists('livestock', 'livestock_farm_id_livestock_code_unique')) {
            Schema::table('livestock', function (Blueprint $table) {
                $table->dropUnique(['farm_id', 'livestock_code']);
            });
        }

        Schema::table('livestock', function (Blueprint $table) {
            if (Schema::hasColumn('livestock', 'deleted_at')) {
                $table->dropSoftDeletes();
            }
            if (Schema::hasColumn('livestock', 'created_by')) {
                $table->dropConstrainedForeignId('created_by');
            }
            foreach ([
                'livestock_name', 'livestock_code', 'livestock_type', 'production_purpose',
                'total_count', 'male_count', 'female_count', 'young_count',
                'farming_method', 'feeding_method', 'water_source', 'acquisition_date',
                'acquisition_source', 'health_status', 'lifecycle_status', 'housing_location',
                'notes', 'status',
            ] as $column) {
                if (Schema::hasColumn('livestock', $column)) {
                    $table->dropColumn($column);
                }
            }
        });

        if (! Schema::hasColumn('livestock', 'health_status')) {
            Schema::table('livestock', function (Blueprint $table) {
                $table->string('health_status', 32)->nullable()->after('base_price');
            });
        }

        if (Schema::hasColumn('livestock', 'quality_band')) {
            DB::table('livestock')->whereNotNull('quality_band')->update([
                'health_status' => DB::raw('quality_band'),
            ]);

            Schema::table('livestock', function (Blueprint $table) {
                $table->dropColumn('quality_band');
            });
        }

        if (! $this->indexExists('livestock', 'livestock_farm_id_type_breed_unique')) {
            Schema::table('livestock', function (Blueprint $table) {
                $table->unique(['farm_id', 'type', 'breed']);
            });
        }
    }

    private function indexExists(string $table, string $index): bool
    {
        $connection = Schema::getConnection();
        $database = $connection->getDatabaseName();

        $result = $connection->select(
            'SELECT COUNT(*) AS aggregate FROM information_schema.statistics WHERE table_schema = ? AND table_name = ? AND index_name = ?',
            [$database, $table, $index]
        );

        return (int) ($result[0]->aggregate ?? 0) > 0;
    }
};

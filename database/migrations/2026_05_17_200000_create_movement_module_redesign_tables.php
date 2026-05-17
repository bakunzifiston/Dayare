<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('permit_requests')) {
            Schema::create('permit_requests', function (Blueprint $table) {
                $table->id();
                $table->string('request_number', 32)->unique();
                $table->date('request_date');
                $table->foreignId('applicant_id')->constrained('users')->cascadeOnDelete();
                $table->foreignId('farm_id')->constrained('farms')->cascadeOnDelete();
                $table->foreignId('farmer_id')->constrained('businesses')->cascadeOnDelete();
                $table->string('movement_purpose', 64);
                $table->string('destination_type', 64);
                $table->string('destination_name')->nullable();
                $table->string('destination_district')->nullable();
                $table->string('destination_sector')->nullable();
                $table->string('destination_cell')->nullable();
                $table->string('destination_village')->nullable();
                $table->string('transport_method', 64)->nullable();
                $table->string('vehicle_plate_number', 50)->nullable();
                $table->date('proposed_departure_date');
                $table->date('expected_arrival_date');
                $table->text('remarks')->nullable();
                $table->string('status', 32)->default('draft');
                $table->foreignId('reviewed_by')->nullable()->constrained('users')->nullOnDelete();
                $table->timestamp('review_date')->nullable();
                $table->text('rejection_reason')->nullable();
                $table->timestamps();
                $table->softDeletes();

                $table->index(['farmer_id', 'status']);
                $table->index(['farm_id', 'request_date']);
            });
        }

        if (! Schema::hasTable('permit_request_animals')) {
            Schema::create('permit_request_animals', function (Blueprint $table) {
                $table->id();
                $table->foreignId('permit_request_id')->constrained()->cascadeOnDelete();
                $table->foreignId('animal_id')->constrained()->cascadeOnDelete();
                $table->foreignId('livestock_id')->nullable()->constrained('livestock')->nullOnDelete();
                $table->string('animal_identifier', 120)->nullable();
                $table->unsignedInteger('quantity')->default(1);
                $table->boolean('eligibility_passed')->default(false);
                $table->json('eligibility_issues')->nullable();
                $table->timestamps();

                $table->unique(['permit_request_id', 'animal_id']);
            });
        }

        if (! Schema::hasTable('movement_histories')) {
            Schema::create('movement_histories', function (Blueprint $table) {
                $table->id();
                $table->foreignId('animal_id')->constrained()->cascadeOnDelete();
                $table->foreignId('movement_permit_id')->constrained('movement_permits')->cascadeOnDelete();
                $table->date('movement_date');
                $table->foreignId('source_farm_id')->constrained('farms')->cascadeOnDelete();
                $table->string('source_location')->nullable();
                $table->string('destination_location')->nullable();
                $table->string('movement_purpose')->nullable();
                $table->string('transport_method')->nullable();
                $table->string('vehicle_plate_number', 50)->nullable();
                $table->string('status', 32)->default('planned');
                $table->foreignId('recorded_by')->nullable()->constrained('users')->nullOnDelete();
                $table->text('remarks')->nullable();
                $table->timestamps();

                $table->index(['animal_id', 'movement_date']);
                $table->index(['movement_permit_id', 'status']);
            });
        }

        Schema::table('movement_permits', function (Blueprint $table) {
            if (! Schema::hasColumn('movement_permits', 'permit_request_id')) {
                $table->foreignId('permit_request_id')->nullable()->after('id')->constrained('permit_requests')->nullOnDelete();
            }
            if (! Schema::hasColumn('movement_permits', 'verification_code')) {
                $table->string('verification_code', 20)->nullable()->unique()->after('verification_token');
            }
            if (! Schema::hasColumn('movement_permits', 'issuing_authority')) {
                $table->string('issuing_authority')->nullable()->after('issued_by');
            }
            if (! Schema::hasColumn('movement_permits', 'livestock_type')) {
                $table->string('livestock_type', 64)->nullable()->after('movement_reason');
            }
            if (! Schema::hasColumn('movement_permits', 'source_district')) {
                $table->string('source_district')->nullable()->after('origin_location');
            }
            if (! Schema::hasColumn('movement_permits', 'source_sector')) {
                $table->string('source_sector')->nullable()->after('source_district');
            }
            if (! Schema::hasColumn('movement_permits', 'source_cell')) {
                $table->string('source_cell')->nullable()->after('source_sector');
            }
            if (! Schema::hasColumn('movement_permits', 'source_village')) {
                $table->string('source_village')->nullable()->after('source_cell');
            }
            if (! Schema::hasColumn('movement_permits', 'destination_district')) {
                $table->string('destination_district')->nullable()->after('destination_location');
            }
            if (! Schema::hasColumn('movement_permits', 'destination_sector')) {
                $table->string('destination_sector')->nullable()->after('destination_district');
            }
            if (! Schema::hasColumn('movement_permits', 'destination_cell')) {
                $table->string('destination_cell')->nullable()->after('destination_sector');
            }
            if (! Schema::hasColumn('movement_permits', 'destination_village')) {
                $table->string('destination_village')->nullable()->after('destination_cell');
            }
            if (! Schema::hasColumn('movement_permits', 'qr_code_path')) {
                $table->string('qr_code_path')->nullable()->after('qr_code');
            }
            if (! Schema::hasColumn('movement_permits', 'owner_identification_number')) {
                $table->string('owner_identification_number', 32)->nullable()->after('owner_national_id');
            }
        });
    }

    public function down(): void
    {
        Schema::table('movement_permits', function (Blueprint $table) {
            foreach ([
                'permit_request_id', 'verification_code', 'issuing_authority', 'livestock_type',
                'source_district', 'source_sector', 'source_cell', 'source_village',
                'destination_district', 'destination_sector', 'destination_cell', 'destination_village',
                'qr_code_path', 'owner_identification_number',
            ] as $column) {
                if (Schema::hasColumn('movement_permits', $column)) {
                    if ($column === 'permit_request_id') {
                        $table->dropForeign(['permit_request_id']);
                    }
                    $table->dropColumn($column);
                }
            }
        });

        Schema::dropIfExists('movement_histories');
        Schema::dropIfExists('permit_request_animals');
        Schema::dropIfExists('permit_requests');
    }
};

<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('movement_permits', function (Blueprint $table) {
            if (! Schema::hasColumn('movement_permits', 'permit_type')) {
                $table->string('permit_type', 32)->default('farm_transfer')->after('permit_number');
            }
            if (! Schema::hasColumn('movement_permits', 'movement_reason')) {
                $table->string('movement_reason')->nullable()->after('permit_type');
            }
            if (! Schema::hasColumn('movement_permits', 'origin_location')) {
                $table->string('origin_location')->nullable()->after('source_farm_id');
            }
            if (! Schema::hasColumn('movement_permits', 'destination_location')) {
                $table->string('destination_location')->nullable()->after('origin_location');
            }
            if (! Schema::hasColumn('movement_permits', 'departure_date')) {
                $table->date('departure_date')->nullable()->after('destination_village_id');
            }
            if (! Schema::hasColumn('movement_permits', 'expected_arrival_date')) {
                $table->date('expected_arrival_date')->nullable()->after('departure_date');
            }
            if (! Schema::hasColumn('movement_permits', 'driver_name')) {
                $table->string('driver_name')->nullable()->after('vehicle_plate');
            }
            if (! Schema::hasColumn('movement_permits', 'driver_phone')) {
                $table->string('driver_phone', 50)->nullable()->after('driver_name');
            }
            if (! Schema::hasColumn('movement_permits', 'transporter_name')) {
                $table->string('transporter_name')->nullable()->after('driver_phone');
            }
            if (! Schema::hasColumn('movement_permits', 'permit_status')) {
                $table->string('permit_status', 32)->default('draft')->after('issued_by');
            }
            if (! Schema::hasColumn('movement_permits', 'veterinary_status')) {
                $table->string('veterinary_status', 32)->default('pending_inspection')->after('permit_status');
            }
            if (! Schema::hasColumn('movement_permits', 'movement_status')) {
                $table->string('movement_status', 32)->default('pending')->after('veterinary_status');
            }
            if (! Schema::hasColumn('movement_permits', 'qr_code')) {
                $table->text('qr_code')->nullable()->after('movement_status');
            }
            if (! Schema::hasColumn('movement_permits', 'verification_token')) {
                $table->string('verification_token', 40)->nullable()->unique()->after('qr_code');
            }
            if (! Schema::hasColumn('movement_permits', 'approved_by')) {
                $table->foreignId('approved_by')->nullable()->constrained('users')->nullOnDelete()->after('verification_token');
            }
            if (! Schema::hasColumn('movement_permits', 'notes')) {
                $table->text('notes')->nullable()->after('approved_by');
            }
            if (! Schema::hasColumn('movement_permits', 'attachment_path')) {
                $table->string('attachment_path')->nullable()->after('notes');
            }
            if (! Schema::hasColumn('movement_permits', 'pdf_path')) {
                $table->string('pdf_path')->nullable()->after('attachment_path');
            }
            if (! Schema::hasColumn('movement_permits', 'created_by')) {
                $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete()->after('pdf_path');
            }
            if (! Schema::hasColumn('movement_permits', 'deleted_at')) {
                $table->softDeletes();
            }
        });

        Schema::table('movement_permit_animals', function (Blueprint $table) {
            if (! Schema::hasColumn('movement_permit_animals', 'animal_id')) {
                $table->foreignId('animal_id')->nullable()->constrained()->nullOnDelete()->after('movement_permit_id');
            }
            if (! Schema::hasColumn('movement_permit_animals', 'movement_condition')) {
                $table->string('movement_condition', 32)->default('healthy')->after('quantity');
            }
            if (! Schema::hasColumn('movement_permit_animals', 'inspection_notes')) {
                $table->text('inspection_notes')->nullable()->after('movement_condition');
            }
            if (! Schema::hasColumn('movement_permit_animals', 'loading_status')) {
                $table->string('loading_status', 32)->default('pending')->after('inspection_notes');
            }
            if (! Schema::hasColumn('movement_permit_animals', 'arrival_status')) {
                $table->string('arrival_status', 32)->nullable()->after('loading_status');
            }
            if (! Schema::hasColumn('movement_permit_animals', 'notes')) {
                $table->text('notes')->nullable()->after('arrival_status');
            }
        });

        if (! Schema::hasTable('movement_transports')) {
            Schema::create('movement_transports', function (Blueprint $table) {
                $table->id();
                $table->foreignId('movement_permit_id')->constrained()->cascadeOnDelete();
                $table->string('vehicle_type', 64)->nullable();
                $table->string('vehicle_number', 50)->nullable();
                $table->string('trailer_number', 50)->nullable();
                $table->string('driver_name')->nullable();
                $table->string('driver_phone', 50)->nullable();
                $table->string('transporter_company')->nullable();
                $table->text('route_information')->nullable();
                $table->timestamp('departure_time')->nullable();
                $table->timestamp('arrival_time')->nullable();
                $table->unsignedInteger('estimated_duration')->nullable();
                $table->text('fuel_notes')->nullable();
                $table->string('emergency_contact')->nullable();
                $table->text('transport_notes')->nullable();
                $table->timestamps();

                $table->unique('movement_permit_id');
            });
        }

        if (! Schema::hasTable('movement_veterinary_approvals')) {
            Schema::create('movement_veterinary_approvals', function (Blueprint $table) {
                $table->id();
                $table->foreignId('movement_permit_id')->constrained()->cascadeOnDelete();
                $table->string('veterinarian_name');
                $table->date('inspection_date');
                $table->string('inspection_result', 32)->default('pending');
                $table->boolean('health_clearance')->default(false);
                $table->boolean('disease_check')->default(false);
                $table->boolean('quarantine_check')->default(false);
                $table->text('recommendations')->nullable();
                $table->string('approval_status', 32)->default('pending');
                $table->string('digital_signature')->nullable();
                $table->text('notes')->nullable();
                $table->timestamps();

                $table->unique('movement_permit_id');
            });
        }

        if (! Schema::hasTable('movement_logs')) {
            Schema::create('movement_logs', function (Blueprint $table) {
                $table->id();
                $table->foreignId('movement_permit_id')->constrained()->cascadeOnDelete();
                $table->string('action_type', 32);
                $table->foreignId('action_by')->nullable()->constrained('users')->nullOnDelete();
                $table->timestamp('action_date');
                $table->string('ip_address', 45)->nullable();
                $table->text('notes')->nullable();
                $table->timestamps();

                $table->index(['movement_permit_id', 'action_date'], 'mvlog_permit_date_idx');
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('movement_logs');
        Schema::dropIfExists('movement_veterinary_approvals');
        Schema::dropIfExists('movement_transports');

        Schema::table('movement_permit_animals', function (Blueprint $table) {
            foreach (['animal_id', 'movement_condition', 'inspection_notes', 'loading_status', 'arrival_status', 'notes'] as $column) {
                if (Schema::hasColumn('movement_permit_animals', $column)) {
                    $table->dropColumn($column);
                }
            }
        });

        Schema::table('movement_permits', function (Blueprint $table) {
            $columns = [
                'permit_type', 'movement_reason', 'origin_location', 'destination_location',
                'departure_date', 'expected_arrival_date', 'driver_name', 'driver_phone',
                'transporter_name', 'permit_status', 'veterinary_status', 'movement_status',
                'qr_code', 'verification_token', 'approved_by', 'notes', 'attachment_path',
                'pdf_path', 'created_by', 'deleted_at',
            ];
            foreach ($columns as $column) {
                if (Schema::hasColumn('movement_permits', $column)) {
                    $table->dropColumn($column);
                }
            }
        });
    }
};

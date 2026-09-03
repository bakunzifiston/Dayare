<?php

use App\Support\SalesComplianceCatalog;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('sales_compliance_sites', function (Blueprint $table) {
            $table->id();
            $table->foreignId('business_id')->constrained()->cascadeOnDelete();
            $table->string('site_type', 40);
            $table->string('name');
            $table->string('location_address');
            $table->foreignId('country_id')->nullable()->constrained('administrative_divisions')->nullOnDelete();
            $table->foreignId('province_id')->nullable()->constrained('administrative_divisions')->nullOnDelete();
            $table->foreignId('district_id')->nullable()->constrained('administrative_divisions')->nullOnDelete();
            $table->foreignId('sector_id')->nullable()->constrained('administrative_divisions')->nullOnDelete();
            $table->string('event_type')->nullable();
            $table->string('event_name')->nullable();
            $table->string('contact_name')->nullable();
            $table->string('contact_phone', 40)->nullable();
            $table->string('contact_email')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();

            $table->index(['business_id', 'site_type'], 'sales_comp_sites_type_idx');
        });

        Schema::create('sales_compliance_certificate_rules', function (Blueprint $table) {
            $table->id();
            $table->foreignId('business_id')->nullable()->constrained()->cascadeOnDelete();
            $table->string('site_type', 40);
            $table->string('meat_source', 40);
            $table->boolean('certificate_required')->default(true);
            $table->string('notes')->nullable();
            $table->timestamps();

            $table->unique(['business_id', 'site_type', 'meat_source'], 'sales_comp_cert_rules_unique');
        });

        Schema::create('sales_compliance_inspections', function (Blueprint $table) {
            $table->id();
            $table->foreignId('business_id')->constrained()->cascadeOnDelete();
            $table->foreignId('site_id')->constrained('sales_compliance_sites')->cascadeOnDelete();
            $table->foreignId('inspector_id')->nullable()->constrained('inspectors')->nullOnDelete();
            $table->foreignId('assigned_user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->date('scheduled_date');
            $table->time('scheduled_time');
            $table->string('status', 20)->default('pending');
            $table->string('meat_source', 40)->nullable();
            $table->text('inspector_notes')->nullable();
            $table->timestamp('completed_at')->nullable();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('updated_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();

            $table->index(['business_id', 'status', 'scheduled_date'], 'sales_comp_insp_status_date_idx');
            $table->index(['site_id', 'status'], 'sales_comp_insp_site_status_idx');
        });

        Schema::create('sales_compliance_checklist_responses', function (Blueprint $table) {
            $table->id();
            $table->foreignId('inspection_id')->constrained('sales_compliance_inspections')->cascadeOnDelete();
            $table->string('item_key', 80);
            $table->string('result', 20);
            $table->text('notes')->nullable();
            $table->timestamps();

            $table->unique(['inspection_id', 'item_key'], 'sales_comp_checklist_item_unique');
        });

        Schema::create('sales_compliance_product_lines', function (Blueprint $table) {
            $table->id();
            $table->foreignId('inspection_id')->constrained('sales_compliance_inspections')->cascadeOnDelete();
            $table->string('product_name');
            $table->string('quantity_description')->nullable();
            $table->string('certificate_status', 20);
            $table->unsignedSmallInteger('sort_order')->default(0);
            $table->timestamps();
        });

        Schema::create('sales_compliance_attachments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('inspection_id')->constrained('sales_compliance_inspections')->cascadeOnDelete();
            $table->string('original_name');
            $table->string('path');
            $table->string('mime', 120)->nullable();
            $table->unsignedInteger('size')->default(0);
            $table->foreignId('uploaded_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
        });

        Schema::create('sales_compliance_escalations', function (Blueprint $table) {
            $table->id();
            $table->foreignId('business_id')->constrained()->cascadeOnDelete();
            $table->foreignId('site_id')->constrained('sales_compliance_sites')->cascadeOnDelete();
            $table->foreignId('inspection_id')->nullable()->constrained('sales_compliance_inspections')->nullOnDelete();
            $table->string('status', 20)->default('open');
            $table->string('reason');
            $table->text('notes')->nullable();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('updated_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();

            $table->index(['business_id', 'status'], 'sales_comp_esc_status_idx');
        });

        $now = now();
        foreach (SalesComplianceCatalog::defaultCertificateRules() as $rule) {
            DB::table('sales_compliance_certificate_rules')->insert([
                'business_id' => null,
                'site_type' => $rule['site_type'],
                'meat_source' => $rule['meat_source'],
                'certificate_required' => $rule['certificate_required'],
                'notes' => $rule['certificate_required']
                    ? 'Default: certificate required for this meat source.'
                    : 'Default: certificate not required for own-farm source.',
                'created_at' => $now,
                'updated_at' => $now,
            ]);
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('sales_compliance_escalations');
        Schema::dropIfExists('sales_compliance_attachments');
        Schema::dropIfExists('sales_compliance_product_lines');
        Schema::dropIfExists('sales_compliance_checklist_responses');
        Schema::dropIfExists('sales_compliance_inspections');
        Schema::dropIfExists('sales_compliance_certificate_rules');
        Schema::dropIfExists('sales_compliance_sites');
    }
};

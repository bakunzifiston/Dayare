<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('animals', function (Blueprint $table) {
            if (! Schema::hasColumn('animals', 'public_verification_token')) {
                $table->string('public_verification_token', 64)->nullable()->unique()->after('qr_code');
            }
        });

        if (! Schema::hasTable('animal_certificate_templates')) {
            Schema::create('animal_certificate_templates', function (Blueprint $table) {
                $table->id();
                $table->foreignId('business_id')->constrained()->cascadeOnDelete();
                $table->string('template_name');
                $table->string('certificate_type', 64);
                $table->string('title_template');
                $table->text('header_note')->nullable();
                $table->text('footer_note')->nullable();
                $table->string('watermark_text')->nullable();
                $table->boolean('is_default')->default(false);
                $table->string('status', 32)->default('active');
                $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
                $table->timestamps();
                $table->softDeletes();

                $table->index(['business_id', 'certificate_type', 'status'], 'act_tpl_biz_type_status_idx');
            });
        }

        if (! Schema::hasTable('animal_certificates')) {
            Schema::create('animal_certificates', function (Blueprint $table) {
            $table->id();
            $table->foreignId('animal_id')->constrained()->cascadeOnDelete();
            $table->foreignId('template_id')->nullable()->constrained('animal_certificate_templates')->nullOnDelete();
            $table->string('certificate_number', 64)->unique();
            $table->string('certificate_type', 64);
            $table->string('certificate_title');
            $table->date('issue_date');
            $table->date('expiry_date')->nullable();
            $table->string('issued_by')->nullable();
            $table->string('veterinarian_name')->nullable();
            $table->string('verification_token', 64)->unique();
            $table->text('qr_code')->nullable();
            $table->string('digital_signature', 128)->nullable();
            $table->string('certificate_status', 32)->default('draft');
            $table->text('remarks')->nullable();
            $table->string('pdf_path')->nullable();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
            $table->softDeletes();

            $table->index(['animal_id', 'certificate_type', 'certificate_status'], 'acert_animal_type_status_idx');
            $table->index('expiry_date', 'acert_expiry_idx');
            });
        }

        if (! Schema::hasTable('animal_ownership_transfers')) {
            Schema::create('animal_ownership_transfers', function (Blueprint $table) {
            $table->id();
            $table->foreignId('animal_id')->constrained()->cascadeOnDelete();
            $table->string('previous_owner');
            $table->string('new_owner');
            $table->date('transfer_date');
            $table->string('transfer_reason')->nullable();
            $table->string('approved_by')->nullable();
            $table->text('notes')->nullable();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
            $table->softDeletes();

            $table->index(['animal_id', 'transfer_date'], 'aown_animal_date_idx');
            });
        }

        if (! Schema::hasTable('animal_certificate_logs')) {
            Schema::create('animal_certificate_logs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('animal_certificate_id')->constrained()->cascadeOnDelete();
            $table->string('action_type', 32);
            $table->foreignId('action_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('action_date')->useCurrent();
            $table->string('ip_address', 45)->nullable();
            $table->text('notes')->nullable();
            $table->timestamps();

            $table->index(['animal_certificate_id', 'action_date'], 'aclog_cert_date_idx');
            $table->index('action_type', 'aclog_action_idx');
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('animal_certificate_logs');
        Schema::dropIfExists('animal_ownership_transfers');
        Schema::dropIfExists('animal_certificates');
        Schema::dropIfExists('animal_certificate_templates');

        Schema::table('animals', function (Blueprint $table) {
            if (Schema::hasColumn('animals', 'public_verification_token')) {
                $table->dropColumn('public_verification_token');
            }
        });
    }
};

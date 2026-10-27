<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * SMS manager: campaigns (bulk sends), reusable templates and the local opt-out list. The
 * delivery log gains the text of staff-written messages (never of sign-in codes), the campaign,
 * the recipient account and the staff member who sent it.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('sms_campaigns', function (Blueprint $table) {
            $table->id();
            $table->string('name', 120);
            $table->text('body');
            $table->string('status', 16)->default('draft')->index();
            $table->string('audience', 16);
            $table->jsonb('audience_options')->nullable();
            $table->jsonb('recipients')->nullable();
            $table->jsonb('import_report')->nullable();
            $table->unsignedInteger('recipients_count')->default(0);
            $table->unsignedSmallInteger('segments')->default(1);
            $table->boolean('respect_stop_list')->default(true);
            $table->boolean('append_footer')->default(true);
            $table->unsignedInteger('sent_count')->default(0);
            $table->unsignedInteger('delivered_count')->default(0);
            $table->unsignedInteger('failed_count')->default(0);
            $table->timestamp('scheduled_at')->nullable()->index();
            $table->timestamp('started_at')->nullable();
            $table->timestamp('finished_at')->nullable();
            $table->text('error')->nullable();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
        });

        Schema::create('sms_templates', function (Blueprint $table) {
            $table->id();
            $table->string('name', 120);
            $table->text('body');
            $table->timestamps();
        });

        Schema::create('sms_opt_outs', function (Blueprint $table) {
            $table->id();
            $table->string('phone', 16)->unique();
            $table->string('reason', 255)->nullable();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
        });

        Schema::table('sms_messages', function (Blueprint $table) {
            $table->foreignId('campaign_id')->nullable()->after('id')->constrained('sms_campaigns')->nullOnDelete();
            $table->foreignId('user_id')->nullable()->after('campaign_id')->constrained('users')->nullOnDelete();
            $table->foreignId('sent_by')->nullable()->after('user_id')->constrained('users')->nullOnDelete();
            $table->text('body')->nullable()->after('purpose');
            $table->unsignedSmallInteger('segments')->default(1)->after('body');
            $table->timestamp('checked_at')->nullable()->after('delivered_at');
            $table->index(['campaign_id', 'status']);
            $table->index('created_at');
        });
    }

    public function down(): void
    {
        Schema::table('sms_messages', function (Blueprint $table) {
            $table->dropIndex(['campaign_id', 'status']);
            $table->dropIndex(['created_at']);
            $table->dropConstrainedForeignId('campaign_id');
            $table->dropConstrainedForeignId('user_id');
            $table->dropConstrainedForeignId('sent_by');
            $table->dropColumn(['body', 'segments', 'checked_at']);
        });

        Schema::dropIfExists('sms_opt_outs');
        Schema::dropIfExists('sms_templates');
        Schema::dropIfExists('sms_campaigns');
    }
};

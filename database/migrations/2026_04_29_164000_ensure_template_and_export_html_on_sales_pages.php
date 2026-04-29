<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('sales_pages', function (Blueprint $table) {
            if (!Schema::hasColumn('sales_pages', 'template')) {
                $table->string('template')->default('aurora')->after('unique_selling_points');
            }

            if (!Schema::hasColumn('sales_pages', 'export_html')) {
                $table->longText('export_html')->nullable()->after('content');
            }
        });
    }

    public function down(): void
    {
        Schema::table('sales_pages', function (Blueprint $table) {
            if (Schema::hasColumn('sales_pages', 'export_html')) {
                $table->dropColumn('export_html');
            }

            if (Schema::hasColumn('sales_pages', 'template')) {
                $table->dropColumn('template');
            }
        });
    }
};

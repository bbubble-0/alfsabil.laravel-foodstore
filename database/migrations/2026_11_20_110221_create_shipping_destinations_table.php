
<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('shipping_destinations', function (Blueprint $table) {
            $table->id();

            $table->foreignId('shipping_zone_id')
                ->constrained('shipping_zones')
                ->cascadeOnDelete();

            $table->string('province_name');
            $table->string('city_name');
            $table->string('district_name');
            $table->string('subdistrict_name')->nullable();
            $table->string('zip_code', 10)->nullable();

            $table->timestamps();

            $table->unique(
                ['province_name', 'city_name', 'district_name'],
                'shipping_destination_unique'
            );
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('shipping_destinations');
    }
};

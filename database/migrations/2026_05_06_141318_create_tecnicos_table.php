<?php

use Illuminate\Database\Migrations\Migration;

return new class extends Migration
{
    /**
     * Legacy V1 schema. Kept as an empty migration so fresh V13.3 databases
     * are created only by the consolidated June schema.
     */
    public function up(): void
    {
        //
    }

    public function down(): void
    {
        //
    }
};

<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Drop tables not used by the current application configuration.
     *
     * - sessions: SESSION_DRIVER=file (sessions stored on disk, not in MySQL)
     * - burial_certification: no runtime code; burial certification uses certification_details
     */
    public function up(): void
    {
        Schema::dropIfExists('burial_certification');
        Schema::dropIfExists('sessions');
    }

    public function down(): void
    {
        // Intentionally empty. Recreate via original migrations if needed.
    }
};

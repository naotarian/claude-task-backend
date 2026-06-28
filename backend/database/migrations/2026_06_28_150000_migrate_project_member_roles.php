<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Rename project member roles to the new scheme:
     * admin -> owner, member -> editor (viewer unchanged).
     */
    public function up(): void
    {
        DB::table('project_members')->where('role', 'admin')->update(['role' => 'owner']);
        DB::table('project_members')->where('role', 'member')->update(['role' => 'editor']);
    }

    public function down(): void
    {
        DB::table('project_members')->where('role', 'owner')->update(['role' => 'admin']);
        DB::table('project_members')->where('role', 'editor')->update(['role' => 'member']);
    }
};

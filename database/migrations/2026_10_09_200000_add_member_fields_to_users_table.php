<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->string('member_code')->nullable()->unique();
            $table->string('phone')->nullable();
            $table->string('family_phone')->nullable();
            $table->text('present_address')->nullable();
            $table->text('permanent_address')->nullable();
            $table->string('nid_path')->nullable();
            $table->string('cv_path')->nullable();
            $table->decimal('monthly_salary', 12, 2)->nullable();
        });

        $counter = 0;
        $members = DB::table('users')
            ->where('role', 'member')
            ->whereNull('member_code')
            ->orderBy('id')
            ->get();

        foreach ($members as $member) {
            DB::table('users')->where('id', $member->id)->update([
                'member_code' => 'M-' . str_pad((string) (++$counter), 4, '0', STR_PAD_LEFT),
            ]);
        }
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn([
                'member_code',
                'phone',
                'family_phone',
                'present_address',
                'permanent_address',
                'nid_path',
                'cv_path',
                'monthly_salary',
            ]);
        });
    }
};

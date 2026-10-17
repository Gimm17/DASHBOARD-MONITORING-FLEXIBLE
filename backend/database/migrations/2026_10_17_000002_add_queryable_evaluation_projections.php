<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('activities', fn (Blueprint $t) => $t->unsignedBigInteger('current_target_set_id')->nullable()->index());
        Schema::create('target_values', function (Blueprint $t) {
            $t->id();
            $t->foreignId('target_set_id')->constrained()->cascadeOnDelete();
            $t->foreignId('period_id')->constrained()->cascadeOnDelete();
            $t->foreignId('region_id')->constrained();
            $t->decimal('target', 18, 4);
            $t->decimal('final_target', 18, 4);
            $t->unique(['target_set_id', 'period_id', 'region_id'], 'target_projection_unique');
        });
        Schema::create('evaluation_items', function (Blueprint $t) {
            $t->id();
            $t->foreignId('revision_id')->constrained('evaluation_revisions');
            $t->foreignId('region_id')->constrained();
            $t->decimal('value', 18, 4)->nullable();
            $t->decimal('target', 18, 4);
            $t->decimal('final_target', 18, 4);
            $t->string('status', 30);
            $t->boolean('is_late');
            $t->unique(['revision_id', 'region_id']);
        });
        $periodIds = DB::table('periods')->pluck('id')->all();
        foreach (DB::table('target_sets')->orderBy('version')->get() as $set) {
            $rows = [];
            foreach (json_decode($set->data, true) as $region => $data) {
                foreach ($data['periods'] as $period => $target) {
                    if (in_array((int) $period, $periodIds, true)) {
                        $rows[] = ['target_set_id' => $set->id, 'region_id' => $region, 'period_id' => $period, 'target' => $target, 'final_target' => $data['final']];
                    }
                }
            }
            foreach (array_chunk($rows, 500) as $chunk) {
                DB::table('target_values')->insert($chunk);
            }
            DB::table('activities')->where('id', $set->activity_id)->update(['current_target_set_id' => $set->id]);
        }
        foreach (DB::table('evaluation_revisions')->get() as $revision) {
            foreach (json_decode($revision->snapshot, true)['cells'] as $region => $cell) {
                DB::table('evaluation_items')->insert(['revision_id' => $revision->id, 'region_id' => $region, 'value' => $cell['value'], 'target' => $cell['target'], 'final_target' => $cell['final_target'], 'status' => $cell['status'], 'is_late' => $cell['isLate']]);
            }
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('evaluation_items');
        Schema::dropIfExists('target_values');
        Schema::table('activities', fn (Blueprint $t) => $t->dropColumn('current_target_set_id'));
    }
};

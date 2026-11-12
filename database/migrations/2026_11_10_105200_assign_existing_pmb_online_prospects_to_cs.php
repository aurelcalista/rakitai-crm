<?php

use App\Models\Prospek;
use App\Models\User;
use Illuminate\Database\Migrations\Migration;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        $pmbProspeks = Prospek::withoutGlobalScopes()->where(function ($q) {
            $q->where('category', 'PMB Online')
              ->orWhere('source', 'Website PMB')
              ->orWhereNotNull('user_id');
        })
        ->whereNull('cs_id')
        ->get();

        foreach ($pmbProspeks as $prospek) {
            $cs = Prospek::findAvailableCs($prospek->wilayah_id);
            if ($cs) {
                $prospek->cs_id = $cs->id;
                if ($prospek->status_formulir === 'Sudah Isi' && empty($prospek->handover_at)) {
                    $prospek->handover_at = $prospek->updated_at ?: now();
                }
                $prospek->save();
            }
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // No-op
    }
};


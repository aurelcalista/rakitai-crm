<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Wilayah;

class PotensiWilayahController extends Controller
{
    public function index(Request $request)
    {
        $user = auth()->user();
        if (!in_array($user->role, ['Admin', 'HM', 'SPV'])) {
            abort(403, 'Unauthorized action.');
        }

        // Get parent Wilayahs (Kota/Kabupaten)
        $query = Wilayah::whereNull('parent_id')
            ->with(['children' => function($q) {
                $q->with(['assignedUsers' => function($qu) {
                    $qu->wherePivot('role', 'Sales')->wherePivot('is_active', true);
                }]);
            }])
            ->orderBy('nama');

        if (in_array($user->role, ['SPV', 'HM'])) {
            $wilayahIds = $user->activeWilayahIds();
            
            if (!empty($wilayahIds)) {
                $query->where(function($q) use ($wilayahIds) {
                    $q->whereIn('id', $wilayahIds)
                      ->orWhereHas('children', function($cq) use ($wilayahIds) {
                          $cq->whereIn('id', $wilayahIds);
                      });
                });
            } else {
                // Ensure no data is shown if no wilayah is assigned to SPV/HM
                $query->whereRaw('1 = 0');
            }
        }
        
        $wilayahs = $query->get();

        // Calculate stats manually for performance
        $wilayahStats = [];
        $allWilayahIds = [];
        
        foreach ($wilayahs as $w) {
            $allWilayahIds[] = $w->id;
            foreach ($w->children as $c) {
                $allWilayahIds[] = $c->id;
            }
        }

        // Fetch prospects for these territories
        $prospeks = \App\Models\Prospek::whereIn('wilayah_id', $allWilayahIds)
            ->select('id', 'wilayah_id', 'status')
            ->get();

        foreach ($allWilayahIds as $wid) {
            $wilayahStats[$wid] = [
                'prospek' => 0,
                'closing' => 0
            ];
        }

        foreach ($prospeks as $p) {
            if (isset($wilayahStats[$p->wilayah_id])) {
                $wilayahStats[$p->wilayah_id]['prospek']++;
                if (in_array($p->status, ['CLOSING', 'LUNAS'])) {
                    $wilayahStats[$p->wilayah_id]['closing']++;
                }
            }
        }

        return view('potensi-wilayah.index', compact('wilayahs', 'wilayahStats'));
    }
}

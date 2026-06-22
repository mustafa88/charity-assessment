<?php

namespace App\Http\Controllers;

use App\Models\Family;

class StatisticsController extends Controller
{
    /** إحصائيات العائلات المقبولة (أحدث تقييم قراره accepted). */
    public function index()
    {
        $maritalKeys = ['married', 'divorced', 'widowed', 'abandoned'];
        $houseKeys   = ['own', 'rent', 'family', 'other'];

        $stats = [
            'families'        => 0,
            'orphan_families' => 0,
            'orphans'         => ['total' => 0, 'm' => 0, 'f' => 0],
            'children'        => ['total' => 0, 'm' => 0, 'f' => 0],
            'needs_repair'    => 0,
            'marital'         => array_fill_keys($maritalKeys, 0),
            'house'           => array_fill_keys($houseKeys, 0),
        ];

        foreach (Family::with('assessments.members')->get() as $f) {
            $a = $f->assessments->first();          // الأحدث
            if (! $a || $a->decision !== 'accepted') continue;

            $stats['families']++;
            if ($a->needs_repair) $stats['needs_repair']++;

            if (isset($stats['marital'][$f->marital_status])) $stats['marital'][$f->marital_status]++;
            if (isset($stats['house'][$a->house_type]))       $stats['house'][$a->house_type]++;

            $familyHasOrphan = false;
            foreach ($a->members as $m) {
                $g = $m->gender === 'f' ? 'f' : 'm';
                $stats['children']['total']++;
                $stats['children'][$g]++;
                if ($m->is_orphan) {
                    $stats['orphans']['total']++;
                    $stats['orphans'][$g]++;
                    $familyHasOrphan = true;
                }
            }
            if ($familyHasOrphan) $stats['orphan_families']++;
        }

        return view('statistics.index', compact('stats'));
    }
}

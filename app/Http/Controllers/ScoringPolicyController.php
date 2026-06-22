<?php

namespace App\Http\Controllers;

use App\Models\ScoringPolicy;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class ScoringPolicyController extends Controller
{
    /** صفحة سياسة النقاط: المعتمدة حالياً + سجل الإصدارات. */
    public function index()
    {
        $active   = ScoringPolicy::where('is_active', true)->latest('version')->first();
        $policies = ScoringPolicy::orderByDesc('version')->get();

        return view('policies.index', compact('active', 'policies'));
    }

    /** أي تعديل = إصدار جديد. لا UPDATE على القديم إطلاقاً. */
    public function store(Request $r)
    {
        $data = $r->validate([
            'approval_threshold'   => 'required|numeric|min:0',
            'rent_bonus'           => 'required|integer',
            'marital_bonus'        => 'required|integer',
            'per_eligible_person'  => 'required|integer',
            'bands'                => 'required|array|size:3',
            'bands.*.max'          => 'nullable|numeric',
            'bands.*.points'       => 'required|integer',
            'missing_group_size'   => 'required|integer|min:1',
            'missing_group_points' => 'required|integer',
            'arch_points'          => 'required|array|size:4',
            'arch_points.*'        => 'required|integer',
        ]);

        $policy = DB::transaction(function () use ($data) {
            $next = (int) ScoringPolicy::max('version') + 1;
            ScoringPolicy::where('is_active', true)->update(['is_active' => false]);

            return ScoringPolicy::create($data + [
                'version'        => $next,
                'is_active'      => true,
                'effective_from' => now()->toDateString(),
            ]);
        });

        return redirect()->route('policies.index')->with('status', "تم حفظ إصدار جديد من السياسة (v{$policy->version}).");
    }
}

<?php

namespace App\Http\Controllers;

use App\Models\Family;
use App\Models\Supervisor;
use Illuminate\Http\Request;

class FamilyController extends Controller
{
    /**
     * تصفّح العائلات المقبولة حسب معيار مختار (?filter=):
     *   orphans  = فيها أيتام · repair = تحتاج ترميم ·
     *   married|divorced|widowed|abandoned = حسب الحالة الاجتماعية.
     * كلها ضمن العائلات المقبولة فقط (أحدث تقييم قراره accepted).
     */
    public function browse(Request $r)
    {
        $maritalKeys = ['married', 'divorced', 'widowed', 'abandoned'];
        $valid       = array_merge(['orphans', 'repair'], $maritalKeys);
        $filter      = $r->query('filter', 'orphans');
        if (! in_array($filter, $valid, true)) $filter = 'orphans';

        // العائلات المقبولة (أحدث تقييم) مع بياناتها للعرض.
        $accepted = collect();
        foreach (Family::with(['assessments.members', 'supervisor'])->get() as $f) {
            $a = $f->assessments->first();              // الأحدث
            if (! $a || $a->decision !== 'accepted') continue;
            $accepted->push((object) [
                'family'        => $f,
                'assessment'    => $a,
                'children'      => $a->members->count(),
                'orphans'       => $a->members->where('is_orphan', true)->count(),
            ]);
        }

        // عدّادات كل معيار.
        $counts = [
            'orphans' => $accepted->where('assessment.has_orphans', true)->count(),
            'repair'  => $accepted->where('assessment.needs_repair', true)->count(),
        ];
        foreach ($maritalKeys as $k) {
            $counts[$k] = $accepted->where('family.marital_status', $k)->count();
        }

        // التصفية حسب المعيار المختار.
        $rows = $accepted->filter(function ($x) use ($filter) {
            return match ($filter) {
                'orphans' => (bool) $x->assessment->has_orphans,
                'repair'  => (bool) $x->assessment->needs_repair,
                default   => $x->family->marital_status === $filter,
            };
        })->sortByDesc(fn ($x) => optional($x->assessment->visit_date)->timestamp)->values();

        return view('families.browse', compact('rows', 'filter', 'counts'));
    }

    /** العائلات المقبولة (أحدث تقييم) التي لم يُحدَّد لها مسؤول بعد. */
    public function unassigned()
    {
        $families = Family::with(['assessments', 'supervisor'])
            ->whereNull('supervisor_id')
            ->get()
            ->filter(function ($f) {
                $a = $f->assessments->first();        // الأحدث
                return $a && $a->decision === 'accepted';
            })
            ->values();

        $supervisors = Supervisor::orderBy('name')->get();

        return view('families.unassigned', compact('families', 'supervisors'));
    }

    /** تحديد/تغيير المسؤول عن عائلة (يقبل الفراغ = إزالة المسؤول). */
    public function assignSupervisor(Request $r, Family $family)
    {
        $data = $r->validate([
            'supervisor_id' => 'nullable|integer|exists:supervisors,id',
        ], [
            'supervisor_id.exists' => 'المسؤول المختار غير موجود.',
        ]);

        $family->update(['supervisor_id' => $data['supervisor_id'] ?? null]);

        return back()->with('status', 'تم تحديث المسؤول عن العائلة.');
    }
}

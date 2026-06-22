<?php

namespace App\Http\Controllers;

use App\Models\Family;
use App\Models\Supervisor;
use Illuminate\Http\Request;

class FamilyController extends Controller
{
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

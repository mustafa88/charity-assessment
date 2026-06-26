<?php

namespace App\Http\Controllers;

use App\Models\Family;
use Illuminate\Http\Request;

class MemberController extends Controller
{
    /**
     * تصفّح أفراد العائلات المقبولة حسب الحالة (?filter=):
     *   children = كل الأولاد (بنات + أولاد) · orphans = الأيتام ·
     *   higher_education = طالب جامعي · tutoring = يحتاج دعم (دروس تقوية) · contributes = يعمل/يساهم.
     * كلها ضمن العائلات المقبولة فقط (أحدث تقييم قراره accepted).
     */
    public function browse(Request $r)
    {
        // كل فلتر = شرط على الفرد (children بلا شرط = الكل).
        $predicates = [
            'children'         => fn ($m) => true,
            'orphans'          => fn ($m) => $m->is_orphan,
            'higher_education' => fn ($m) => $m->higher_education,
            'tutoring'         => fn ($m) => $m->needs_tutoring,
            'contributes'      => fn ($m) => $m->contributes,
        ];

        $filter = $r->query('filter', 'children');
        if (! array_key_exists($filter, $predicates)) $filter = 'children';

        $rows = collect();
        foreach (Family::with(['assessments.members', 'supervisor'])->get() as $f) {
            $a = $f->assessments->first();              // الأحدث
            if (! $a || $a->decision !== 'accepted') continue;

            foreach ($a->members as $m) {
                $rows->push((object) [
                    'name'             => $m->name,
                    'age'              => $m->dob ? $m->dob->age : null,
                    'gender'           => $m->gender,
                    'school'           => $m->school,
                    'is_orphan'        => (bool) $m->is_orphan,
                    'is_eligible'      => (bool) $m->is_eligible,
                    'contributes'      => (bool) $m->contributes,
                    'higher_education' => (bool) $m->higher_education,
                    'needs_tutoring'   => (bool) $m->needs_tutoring,
                    'mother'           => $f->wife_name,
                    'phone'            => $f->wife_phone ?: $f->husband_phone,
                    'supervisor'       => $f->supervisor?->name,
                    'family'           => $f->husband_name ?: ($f->wife_name ?: ('عائلة #' . $f->id)),
                    'assessment'       => $a,
                ]);
            }
        }

        $counts = [];
        foreach ($predicates as $key => $pred) {
            $counts[$key] = $rows->filter($pred)->count();
        }

        $rows = $rows->filter($predicates[$filter])
            ->sortBy([['supervisor', 'asc'], ['family', 'asc']])
            ->values();

        return view('members.browse', compact('rows', 'filter', 'counts'));
    }
}

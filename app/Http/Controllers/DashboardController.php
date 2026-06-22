<?php

namespace App\Http\Controllers;

use App\Models\Assessment;
use App\Models\Family;
use App\Models\ScoringPolicy;

class DashboardController extends Controller
{
    /** الصفحة الرئيسية: روابط لكل الأقسام مع عدّادات سريعة. */
    public function index()
    {
        $assessmentsCount = Assessment::count();
        $familiesCount    = Family::count();
        $active           = ScoringPolicy::where('is_active', true)->latest('version')->first();

        // عدّاد التقييمات حسب القرار (لبطاقات الحالات)
        $byStatus = [
            'accepted' => Assessment::where('decision', 'accepted')->count(),
            'pending'  => Assessment::where('decision', 'pending')->count(),
            'rejected' => Assessment::where('decision', 'rejected')->count(),
        ];

        // الزيارات القريبة: أحدث تقييم لكل عائلة، مقبول بالقرار اليدوي وله تاريخ زيارة تالية (نفس منطق صفحة الزيارات)
        $upcomingCount = Family::with('assessments')->get()
            ->map(fn ($f) => $f->assessments->first())
            ->filter(fn ($a) => $a && $a->next_visit_date && $a->decision === 'accepted')
            ->count();

        // أيتام للمراجعة: من بلغ 15+ ولا يزال يتيماً في أحدث تقييم مقبول (نفس منطق صفحة الأيتام)
        $orphanReviewCount = 0;
        // مقبولة بلا مسؤول: أحدث تقييم مقبول + لا مسؤول (نفس منطق صفحة العائلات بلا مسؤول)
        $unassignedCount = 0;
        foreach (Family::with('assessments.members')->get() as $f) {
            $a = $f->assessments->first();
            if (! $a || $a->decision !== 'accepted') continue;
            foreach ($a->members as $m) {
                if ($m->is_orphan && $m->dob && $m->dob->age >= 15) $orphanReviewCount++;
            }
            if ($f->supervisor_id === null) $unassignedCount++;
        }

        $supervisorsCount = \App\Models\Supervisor::count();

        return view('dashboard', compact(
            'assessmentsCount', 'familiesCount', 'upcomingCount', 'orphanReviewCount',
            'active', 'byStatus', 'unassignedCount', 'supervisorsCount'
        ));
    }
}

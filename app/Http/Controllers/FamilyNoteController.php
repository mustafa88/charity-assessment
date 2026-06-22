<?php

namespace App\Http\Controllers;

use App\Models\Family;
use Illuminate\Http\Request;

class FamilyNoteController extends Controller
{
    /** إضافة ملاحظة عامة للعائلة — تُحفظ بتاريخها وكاتبها (سجل لا يُعدّل). */
    public function store(Request $r, Family $family)
    {
        $data = $r->validate(['body' => 'required|string']);

        $family->notes()->create([
            'body'    => $data['body'],
            'user_id' => $r->user()?->id,
        ]);

        return redirect()->back()->with('status', 'تم حفظ الملاحظة.');
    }
}

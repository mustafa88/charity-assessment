<?php

namespace App\Http\Controllers;

use App\Models\Supervisor;
use Illuminate\Http\Request;

class SupervisorController extends Controller
{
    /** صفحة إدارة المسؤولين: قائمة + نموذج إضافة. */
    public function index()
    {
        $supervisors = Supervisor::withCount('families')->orderBy('name')->get();

        return view('supervisors.index', compact('supervisors'));
    }

    public function store(Request $r)
    {
        $data = $r->validate([
            'name'  => 'required|string|max:255',
            'phone' => 'nullable|string|max:50',
        ], [
            'name.required' => 'اسم المسؤول مطلوب.',
        ]);

        Supervisor::create($data);

        return redirect()->route('supervisors.index')->with('status', 'تمت إضافة المسؤول.');
    }

    public function update(Request $r, Supervisor $supervisor)
    {
        $data = $r->validate([
            'name'  => 'required|string|max:255',
            'phone' => 'nullable|string|max:50',
        ], [
            'name.required' => 'اسم المسؤول مطلوب.',
        ]);

        $supervisor->update($data);

        return redirect()->route('supervisors.index')->with('status', 'تم تحديث المسؤول.');
    }

    public function destroy(Supervisor $supervisor)
    {
        // يُمنع حذف مسؤول لديه عائلات مرتبطة — يجب نقلها/إفراغها أولاً.
        $count = $supervisor->families()->count();
        if ($count > 0) {
            return redirect()->route('supervisors.index')
                ->with('error', "لا يمكن حذف «{$supervisor->name}»: مسؤول عن {$count} عائلة. انقل عائلاته أولاً.");
        }

        $supervisor->delete();

        return redirect()->route('supervisors.index')->with('status', 'تم حذف المسؤول.');
    }
}

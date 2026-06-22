<?php

namespace App\Http\Controllers;

use App\Models\Family;
use App\Models\FamilyAttachment;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class FamilyAttachmentController extends Controller
{
    /** رفع مرفق (صورة أو PDF) للعائلة — يُخزَّن على disk(local) خلف auth. */
    public function store(Request $r, Family $family)
    {
        $data = $r->validate([
            // صور شائعة أو PDF فقط، حتى 10 ميغابايت.
            'file'        => 'required|file|mimes:jpg,jpeg,png,webp,gif,pdf|max:10240',
            'description' => 'nullable|string|max:255',
        ], [
            'file.required' => 'يرجى اختيار ملف.',
            'file.mimes'    => 'الملف يجب أن يكون صورة (jpg/png/webp/gif) أو PDF.',
            'file.max'      => 'حجم الملف يتجاوز الحد المسموح (10 ميغابايت).',
        ]);

        $uploaded = $r->file('file');
        $path = $uploaded->store('family-attachments/' . $family->id, 'local');

        $family->attachments()->create([
            'user_id'       => $r->user()?->id,
            'original_name' => $uploaded->getClientOriginalName(),
            'description'   => $data['description'] ?? null,
            'path'          => $path,
            'mime'          => $uploaded->getClientMimeType(),
            'size'          => $uploaded->getSize(),
        ]);

        return redirect()->back()->with('status', 'تم رفع المرفق.');
    }

    /** عرض/تنزيل المرفق (inline) — يُخدَم من القرص الخاص خلف auth. */
    public function show(FamilyAttachment $attachment)
    {
        abort_unless(Storage::disk('local')->exists($attachment->path), 404);

        return response()->file(
            Storage::disk('local')->path($attachment->path),
            [
                'Content-Type'        => $attachment->mime,
                'Content-Disposition' => 'inline; filename="' . $attachment->original_name . '"',
            ]
        );
    }

    /** حذف المرفق (الملف من القرص + الصف). */
    public function destroy(FamilyAttachment $attachment)
    {
        Storage::disk('local')->delete($attachment->path);
        $attachment->delete();

        return redirect()->back()->with('status', 'تم حذف المرفق.');
    }
}

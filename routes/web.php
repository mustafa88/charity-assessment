<?php

use App\Http\Controllers\AssessmentController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\FamilyAttachmentController;
use App\Http\Controllers\FamilyController;
use App\Http\Controllers\FamilyNoteController;
use App\Http\Controllers\MemberController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\ScoringPolicyController;
use App\Http\Controllers\StatisticsController;
use App\Http\Controllers\SupervisorController;
use Illuminate\Support\Facades\Route;

// الجذر → الصفحة الرئيسية (روابط الأقسام)
Route::get('/', [DashboardController::class, 'index'])->middleware('auth')->name('dashboard');

Route::middleware('auth')->group(function () {
    // التقييمات — صفحات حقيقية (كل شاشة لها رابطها)
    Route::get ('/assessments',              [AssessmentController::class, 'index'])->name('assessments.index');
    Route::get ('/upcoming-visits',          [AssessmentController::class, 'upcoming'])->name('visits.upcoming');
    Route::get ('/statistics',               [StatisticsController::class, 'index'])->name('statistics.index');
    Route::get ('/orphan-reviews',           [AssessmentController::class, 'orphanReviews'])->name('orphans.index');
    Route::get ('/families-browse',           [FamilyController::class, 'browse'])->name('families.browse');
    Route::get ('/members-browse',            [MemberController::class, 'browse'])->name('members.browse');
    Route::get ('/orphans',                   [AssessmentController::class, 'orphans'])->name('orphans.all');
    Route::get ('/orphans/pdf',               [AssessmentController::class, 'orphansPdf'])->name('orphans.pdf');
    Route::post('/members/{member}/remove-orphan', [AssessmentController::class, 'removeOrphan'])->name('members.removeOrphan');
    Route::get ('/assessments/create',       [AssessmentController::class, 'create'])->name('assessments.create');
    Route::post('/assessments',              [AssessmentController::class, 'store'])->name('assessments.store');
    Route::get ('/assessments/{a}',          [AssessmentController::class, 'show'])->name('assessments.show');
    Route::get ('/assessments/{a}/pdf',      [AssessmentController::class, 'pdf'])->name('assessments.pdf');
    Route::get ('/assessments/{a}/edit',     [AssessmentController::class, 'edit'])->name('assessments.edit');
    Route::put ('/assessments/{a}',          [AssessmentController::class, 'update'])->name('assessments.update');
    Route::post('/assessments/{a}/decision', [AssessmentController::class, 'decide'])->name('assessments.decide');
    Route::post('/assessments/{a}/convert',  [AssessmentController::class, 'convertToLatest'])->name('assessments.convert');

    // ملاحظات العائلة (سجل تراكمي مؤرّخ)
    Route::post('/families/{family}/notes', [FamilyNoteController::class, 'store'])->name('families.notes.store');

    // مرفقات العائلة (صور/PDF)
    Route::post  ('/families/{family}/attachments', [FamilyAttachmentController::class, 'store'])->name('families.attachments.store');
    Route::get   ('/attachments/{attachment}',       [FamilyAttachmentController::class, 'show'])->name('attachments.show');
    Route::delete('/attachments/{attachment}',       [FamilyAttachmentController::class, 'destroy'])->name('attachments.destroy');

    // المسؤولون عن العائلات + ربطهم
    Route::get   ('/supervisors',             [SupervisorController::class, 'index'])->name('supervisors.index');
    Route::post  ('/supervisors',             [SupervisorController::class, 'store'])->name('supervisors.store');
    Route::put   ('/supervisors/{supervisor}', [SupervisorController::class, 'update'])->name('supervisors.update');
    Route::delete('/supervisors/{supervisor}', [SupervisorController::class, 'destroy'])->name('supervisors.destroy');
    Route::get   ('/unassigned-families',     [FamilyController::class, 'unassigned'])->name('families.unassigned');
    Route::post  ('/families/{family}/supervisor', [FamilyController::class, 'assignSupervisor'])->name('families.assignSupervisor');

    // سياسة النقاط
    Route::get ('/scoring-policy',  [ScoringPolicyController::class, 'index'])->name('policies.index');
    Route::post('/scoring-policy',  [ScoringPolicyController::class, 'store'])->name('policies.store');

    // البروفايل (من Laravel Breeze)
    Route::get   ('/profile',  [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch ('/profile',  [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile',  [ProfileController::class, 'destroy'])->name('profile.destroy');
});

require __DIR__.'/auth.php';   // من Laravel Breeze

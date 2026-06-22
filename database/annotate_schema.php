<?php
/**
 * يضيف تعليقات عربية (--) داخل نص CREATE TABLE في sqlite_master دون لمس البيانات.
 *
 * آمن ومتجدّد: يقرأ تعريف الجدول الحالي من sqlite_master ويُبقي تعريفات الأعمدة
 * حرفياً كما هي، ويضيف فقط سطراً/تعليقاً بعد كل عمود. لذا يطابق المخطط الحيّ دائماً
 * (يغطّي الأعمدة الجديدة تلقائياً) ولا يسبب أي تعارض.
 *
 * التشغيل:  php database/annotate_schema.php
 */

$path = __DIR__ . '/database.sqlite';

// الجداول المستهدفة (جداول المجال فقط).
$tables = [
    'scoring_policies', 'families', 'assessments', 'family_members',
    'finances', 'home_needs', 'assessment_audits', 'family_notes', 'supervisors',
];

// وصف الأعمدة: مفتاح "table.column". الأعمدة غير المذكورة تُترك بلا تعليق.
$desc = [
    // عام
    '*.id'         => 'المعرّف الفريد',
    '*.created_at' => 'تاريخ إنشاء السجل',
    '*.updated_at' => 'تاريخ آخر تعديل',
    '*.assessment_id' => 'التقييم المرتبط (→ assessments)',
    '*.family_id'  => 'العائلة المرتبطة (→ families)',

    // scoring_policies
    'scoring_policies.version' => 'رقم الإصدار (يتزايد مع كل تعديل)',
    'scoring_policies.is_active' => 'الإصدار المعتمد حالياً؟ (0/1) — واحد فقط نشط',
    'scoring_policies.approval_threshold' => 'عتبة الاستحقاق (₪/فرد) للتوصية',
    'scoring_policies.rent_bonus' => 'نقاط مكافأة السكن بالإيجار',
    'scoring_policies.marital_bonus' => 'نقاط مكافأة الحالة الاجتماعية (مطلّق/أرمل/مهجور)',
    'scoring_policies.per_eligible_person' => 'نقاط لكل فرد مستحق',
    'scoring_policies.bands' => 'شرائح المتبقي للفرد (JSON: max/points)',
    'scoring_policies.missing_group_size' => 'حجم مجموعة النواقص',
    'scoring_policies.missing_group_points' => 'نقاط كل مجموعة نواقص',
    'scoring_policies.arch_points' => 'نقاط الحالة المعمارية (JSON: [ممتاز,جيد,سيئ,لايصلح])',
    'scoring_policies.effective_from' => 'تاريخ سريان الإصدار',

    // families
    'families.wife_name' => 'اسم الزوجة',
    'families.husband_name' => 'اسم الزوج',
    'families.wife_id' => 'رقم هوية الزوجة',
    'families.husband_id' => 'رقم هوية الزوج',
    'families.wife_dob' => 'تاريخ ميلاد الزوجة',
    'families.husband_dob' => 'تاريخ ميلاد الزوج',
    'families.marital_status' => 'الحالة الاجتماعية: married/divorced/widowed/abandoned',
    'families.wife_phone' => 'هاتف الزوجة',
    'families.husband_phone' => 'هاتف الزوج',
    'families.health_fund' => 'صندوق المرضى (التأمين الصحي)',
    'families.bank_name' => 'اسم البنك',
    'families.joint_account' => 'حساب بنكي مشترك (0/1)',
    'families.supervisor_id' => 'المسؤول عن العائلة (→ supervisors، قد يكون فارغاً حتى القبول)',
    'families.description' => 'وصف حرّ اختياري للعائلة',

    // assessments
    'assessments.scoring_policy_id' => 'لقطة إصدار السياسة وقت التقييم (→ scoring_policies)',
    'assessments.visit_date' => 'تاريخ الزيارة الميدانية',
    'assessments.visitors' => 'أسماء منفّذي الزيارة',
    'assessments.next_visit_date' => 'تاريخ الزيارة التالية (افتراضياً +6 أشهر)',
    'assessments.house_type' => 'نوع السكن: own ملك / rent إيجار / family عائلي / other أخرى',
    'assessments.has_orphans' => 'يوجد أيتام (0/1) — مُشتق آلياً',
    'assessments.needs_repair' => 'يحتاج ترميم (0/1)',
    'assessments.arch_condition' => 'الحالة المعمارية: 0 ممتاز / 1 جيد / 2 سيئ / 3 لا يصلح',
    'assessments.house_location' => 'موقع/عنوان المنزل',
    'assessments.repairs_notes' => 'ملاحظات الترميم',
    'assessments.total_score' => 'مجموع النقاط (يحسبه ScoringEngine)',
    'assessments.per_person_remaining' => 'المتبقي للفرد بالشيكل (محسوب)',
    'assessments.recommended' => 'التوصية الآلية بالاستحقاق (0/1) — ليست القرار',
    'assessments.decision' => 'القرار اليدوي: pending انتظار / accepted مقبول / rejected مرفوض',
    'assessments.decision_note' => 'ملاحظة على القرار اليدوي',
    'assessments.decided_at' => 'تاريخ اتخاذ القرار',

    // family_members
    'family_members.name' => 'اسم الفرد',
    'family_members.dob' => 'تاريخ الميلاد (يُحسب منه العمر للأهلية)',
    'family_members.gender' => 'الجنس: m ذكر / f أنثى',
    'family_members.school' => 'المدرسة',
    'family_members.needs_tutoring' => 'يحتاج دعم دراسي (0/1)',
    'family_members.tutor_subject' => 'مادة الدعم الدراسي',
    'family_members.higher_education' => 'طالب تعليم عالٍ/جامعي (0/1)',
    'family_members.marital_status' => 'الحالة الاجتماعية للفرد',
    'family_members.contributes' => 'يعمل ويساهم في مصروف البيت (0/1)',
    'family_members.is_orphan' => 'يتيم؟ (يُضبط آلياً: أرمل + عمر<15، الإزالة يدوية)',
    'family_members.is_eligible' => 'مستحق؟ (يحسبه ScoringEngine آلياً)',

    // finances
    'finances.type' => 'النوع: expense مصروف / income مدخول',
    'finances.category' => 'البند (إيجار، كهرباء، راتب...)',
    'finances.amount' => 'المبلغ بالشيكل',
    'finances.is_bimonthly' => 'لكل شهرين؟ يُقسَّم ÷2 للشهري (0/1)',
    'finances.notes' => 'ملاحظة',

    // home_needs
    'home_needs.item' => 'البند الناقص (ثلاجة، غسالة...)',

    // assessment_audits
    'assessment_audits.action' => 'نوع الإجراء: decided قرار / converted_policy تحويل إصدار',
    'assessment_audits.from_version' => 'إصدار السياسة قبل',
    'assessment_audits.to_version' => 'إصدار السياسة بعد',
    'assessment_audits.from_score' => 'النقاط قبل',
    'assessment_audits.to_score' => 'النقاط بعد',
    'assessment_audits.from_decision' => 'القرار قبل',
    'assessment_audits.to_decision' => 'القرار بعد',
    'assessment_audits.user_id' => 'المستخدم الذي نفّذ الإجراء',
    'assessment_audits.meta' => 'بيانات إضافية (JSON)',

    // family_notes
    'family_notes.body' => 'نص الملاحظة',
    'family_notes.user_id' => 'كاتب الملاحظة (→ users)',

    // supervisors
    'supervisors.name' => 'اسم المسؤول عن العائلة',
    'supervisors.phone' => 'هاتف المسؤول (اختياري)',
];

/** يقسّم قائمة الأعمدة على الفواصل العليا (خارج الأقواس). */
function splitTopLevel(string $inner): array
{
    $parts = [];
    $depth = 0; $buf = '';
    $len = strlen($inner);
    for ($i = 0; $i < $len; $i++) {
        $ch = $inner[$i];
        if ($ch === '(') $depth++;
        elseif ($ch === ')') $depth--;
        if ($ch === ',' && $depth === 0) { $parts[] = trim($buf); $buf = ''; continue; }
        $buf .= $ch;
    }
    if (trim($buf) !== '') $parts[] = trim($buf);
    return $parts;
}

/** يبني CREATE TABLE معلّقاً من النص الحالي (يحافظ على التعريفات حرفياً). */
function annotate(string $table, string $sql, array $desc): string
{
    $open = strpos($sql, '(');
    // إيجاد القوس المغلق المطابق للأول
    $depth = 0; $close = -1;
    for ($i = $open; $i < strlen($sql); $i++) {
        if ($sql[$i] === '(') $depth++;
        elseif ($sql[$i] === ')') { $depth--; if ($depth === 0) { $close = $i; break; } }
    }
    $inner = substr($sql, $open + 1, $close - $open - 1);
    $parts = splitTopLevel($inner);

    $lines = [];
    $n = count($parts);
    foreach ($parts as $idx => $p) {
        $comment = '';
        if (preg_match('/^"([^"]+)"/', $p, $m)) {            // تعريف عمود
            $col = $m[1];
            $comment = $desc["$table.$col"] ?? ($desc["*.$col"] ?? '');
        } elseif (preg_match('/^\s*foreign\s+key/i', $p)) {   // قيد مفتاح أجنبي
            $comment = 'علاقة (مفتاح أجنبي)';
        }
        $sep = $idx < $n - 1 ? ',' : '';
        $lines[] = '  ' . $p . $sep . ($comment !== '' ? '  -- ' . $comment : '');
    }

    return "CREATE TABLE \"$table\" (\n" . implode("\n", $lines) . "\n)";
}

$pdo = new PDO('sqlite:' . $path);
$pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

$ver = (int) $pdo->query('PRAGMA schema_version')->fetchColumn();
$pdo->exec('PRAGMA writable_schema=ON');
$upd = $pdo->prepare("UPDATE sqlite_master SET sql=:sql WHERE type='table' AND name=:name");

foreach ($tables as $t) {
    $row = $pdo->query("SELECT sql FROM sqlite_master WHERE type='table' AND name=" . $pdo->quote($t))->fetch(PDO::FETCH_ASSOC);
    if (! $row) { echo "skip (غير موجود): $t\n"; continue; }
    $new = annotate($t, $row['sql'], $desc);
    $upd->execute([':sql' => $new, ':name' => $t]);
    echo "annotated: $t\n";
}

$pdo->exec('PRAGMA schema_version=' . ($ver + 1));
$pdo->exec('PRAGMA writable_schema=OFF');
$pdo = null;

// التحقق من السلامة
$pdo = new PDO('sqlite:' . $path);
$pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
echo "\nintegrity_check: " . $pdo->query('PRAGMA integrity_check')->fetchColumn() . "\n";
$fk = $pdo->query('PRAGMA foreign_key_check')->fetchAll(PDO::FETCH_ASSOC);
echo "foreign_key_check: " . (count($fk) ? json_encode($fk) : 'ok (لا انتهاكات)') . "\n";

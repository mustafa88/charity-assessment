# CLAUDE.md — منظومة تقييم العائلات (مبرة عطاء)

> هذا الملف يُقرأ تلقائياً عند بداية كل جلسة Claude Code. يحتوي قرارات المعمارية الملزمة.
> ضعه في جذر مشروع Laravel.

## الهدف

رقمنة استمارة ورقية لتقييم العائلات المحتاجة. الجوهر ليس إدخال البيانات بل
**أتمتة محرك التقييم (Scoring)** الذي يحسب النقاط ويُنتج توصية بالاستحقاق — حالياً يُحسب يدوياً.

## الستاك

- **Laravel 12** + **Breeze (blade)** — مصادقة بسيطة، **مستخدم واحد بكل الصلاحيات** (لا أدوار).
- **قاعدة البيانات: SQLite فعلياً** (`database/database.sqlite`) — هي القاعدة التي **يعمل عليها المشروع الآن**
  (ملف واحد، بلا خادم منفصل). المنطق محايد للمحرّك فيمكن نقله لاحقاً إلى MySQL دون تغيير في الكود.
- الواجهة: **صفحات Laravel سيرفر-side حقيقية** (كل شاشة لها route + ملف Blade خاص)، تمتد من layout مشترك
  `resources/views/layouts/main.blade.php` (RTL). الإرسال POST/PUT عادي مع CSRF — **لا fetch ولا JSON API**.
- **Alpine.js** يُستخدم في شاشة النموذج فقط (إضافة/حذف صفوف الأبناء والمالية والنواقص + **المعاينة الحيّة** للنقاط + **تنبيه التحقّق**).
- الحساب في المتصفح للمعاينة الحيّة فقط؛ **القيمة المعتمدة دائماً من السيرفر** (تُحسب بعد الحفظ).
- **الأصول (CSS/JS):** Tailwind + Alpine عبر **Vite**. تُبنى مرة واحدة بـ `npm run build` وتُخدَم ثابتة من
  `public/build` (**لا حاجة لإبقاء `npm run dev` شغّالاً**). أي تعديل على Blade/CSS يتطلب إعادة `npm run build`.
- **تصدير PDF:** مكتبة **mPDF** (`mpdf/mpdf`) — PHP خالص، خط عربي مدمج `XB Riyaz`. تتطلّب تفعيل إضافة **gd** في `php.ini`.

## قرارات المعمارية الملزمة (لا تُخالَف دون مناقشة)

1. **`App\Services\ScoringEngine` هو المصدر الوحيد لحساب النقاط.** يعيش في السيرفر،
   يستقبل `(Assessment, ScoringPolicy)`. ممنوع تكرار منطق الحساب في أي مكان آخر.

2. **فصل الهوية عن التقييم:**
   - `families` = بيانات ثابتة (أسماء، هويات، تواريخ ميلاد، هواتف، بنك، **الحالة الاجتماعية**، صندوق المرضى).
   - `assessments` = كل زيارة مستقلة (سياسة + مالية + أبناء + نواقص + حالة معمارية خاصة بها).
   - هذا يعطي **تاريخ تقييمات** لنفس العائلة.

3. **السياسة مؤرشَفة بإصدارات (`scoring_policies`).** أي تعديل = **صف جديد** (version+1) +
   تبديل `is_active`. **ممنوع UPDATE على سياسة قائمة.** كل تقييم مرتبط بـ `scoring_policy_id` خاص به،
   فلا تتأثر التقييمات القديمة بأي تعديل لاحق.
   - **لا سياسة مزروعة افتراضياً.** عند غياب أي سياسة معتمدة، صفحة `policies.index` تعرض النموذج بقيم v1
     الافتراضية (عتبة 1200…) وأول حفظ يُنشئ **الإصدار 1** (`max(version)+1`). أما `assessments.create/store`
     فتَحرسان الغياب: إن لم توجد سياسة معتمدة تُعيدان التوجيه لـ `policies.index` برسالة بدل التعطّل
     (سابقاً كان `ScoringPolicy::active()` بـ `firstOrFail` يرمي 404).

4. **التوصية ≠ القرار.** حقلان منفصلان في `assessments`:
   - `recommended` (bool) — يحسبه المحرك (`per_person <= approval_threshold`).
   - `decision` (enum: pending|accepted|rejected) — **يضعه المستخدم يدوياً فقط**.
   - عملية `convertToLatest` تُعيد حساب النقاط لكن **لا تلمس `decision` إطلاقاً**.

5. **سجل تدقيق `assessment_audits`** — كل تحويل إصدار وكل قرار وكل **تغيّر لتاريخ الزيارة** يُسجَّل
   (تحويل: من→إلى للإصدار/النقاط · قرار: من→إلى · `revisited`: تواريخ الزيارة/الزيارة التالية القديمة→الجديدة في `meta`).
   عند تعديل تقييم (`update`) إذا تغيّر `visit_date` أو `next_visit_date` يُكتب صف `revisited` تلقائياً. لا UPDATE ولا DELETE عليه.

## منطق الحساب (ScoringEngine)

```
المصروف الشهري = Σ المصروفات (بنود is_bimonthly تُقسَّم ÷2)
المدخول الشهري = Σ المدخولات
المتبقي للعائلة = المدخول - المصروف
عدد الأهل = (married ? 2 : 1)          # طلاق/وفاة/تارك = 1
الأبناء المستحقون: تحت 18 | بنت 18+ غير عاملة | يعمل ويساهم (contributes) | طالب جامعي (higher_education)
عدد المستحقين = الأهل + الأبناء المستحقون (بحد أدنى 1)
المتبقي للفرد = المتبقي للعائلة ÷ عدد المستحقين
```

### النقاط (كلها من `scoring_policies`، v1 الافتراضية):
| المصدر | القاعدة | النقاط |
|---|---|---|
| نوع السكن | rent | rent_bonus = 1 |
| الحالة الاجتماعية | divorced/widowed/abandoned | marital_bonus = 1 |
| الأفراد المستحقون | × العدد | per_eligible_person = 1 |
| المتبقي للفرد | <500 / <1000 / ما فوق | bands: 3 / 2 / 1 |
| النواقص | floor(count/3) | missing_group: size=3, points=1 |
| الحالة المعمارية | ممتاز/جيد/سيئ/لا يصلح | arch_points = [0,1,2,3] |

**عتبة الاستحقاق (توصية):** `approval_threshold = 1200` ₪/فرد.

> ملاحظة مفتوحة: قاعدة "عدد الأهل 2/1" تحسين عن الورقة — يجب تأكيدها مع المبرة.

## جداول قاعدة البيانات

`scoring_policies` (version, is_active, approval_threshold, rent_bonus, marital_bonus,
per_eligible_person, bands json, missing_group_size, missing_group_points, arch_points json, effective_from)

`families` (هوية ثابتة: أسماء، هويات، تواريخ ميلاد، هواتف، marital_status، health_fund، bank،
**supervisor_id** (FK→supervisors، nullable)، **description** (وصف حرّ nullable)) ·
`supervisors` (المسؤولون عن العائلات: name, phone — جدول مرجعي يُدار من صفحته) ·
`assessments` (FK: family_id, scoring_policy_id؛ +visit_date, visitors, next_visit_date (افتراضياً visit_date+6 أشهر),
house_type, arch_condition, has_orphans, needs_repair, house_location, repairs_notes, total_score, per_person_remaining,
recommended, decision, decision_note, decided_at)

`family_members` (FK: assessment_id؛ name, dob, gender, school, needs_tutoring, tutor_subject,
higher_education, marital_status, contributes, is_orphan, is_eligible) · `finances` (FK: assessment_id؛
type expense|income, category, amount, is_bimonthly, notes) · `home_needs` (FK: assessment_id؛ item)
· `assessment_audits` · `family_notes` (FK: family_id؛ body, user_id, created_at — سجل ملاحظات تراكمي مؤرّخ لكل عائلة)
· `family_attachments` (FK: family_id, user_id؛ original_name, description (وصف حرّ يُكتب وقت الرفع، nullable), path, mime, size — مرفقات صور/PDF تُخزَّن على disk(local) وتُخدَم خلف auth)

> **عدد الجداول الفعلي:** 10 جداول مجال (migrations 000001–000012) + جداول Laravel الداخلية (users/cache/jobs...).
>
> **الحالة الاجتماعية — مصدر وحيد:** تعيش في **`families.marital_status`** فقط (يقرأها `ScoringEngine`، `applyOrphanRules`، وكل الواجهات).
> أُزيل عمود `assessments.marital_status` القديم نهائياً (migration 000011). لا تُكرَّر على التقييم.

## المسارات (routes/web.php — كلها سيرفر-side خلف `auth`)

| الطريقة | المسار | الاسم | الوظيفة |
|--------|--------|-------|---------|
| GET  | /                          | dashboard           | **الصفحة الرئيسية**: بطاقات روابط لكل الأقسام + عدّادات (`DashboardController`) |
| GET  | /assessments?status=       | assessments.index   | القائمة **مقسّمة حسب القرار** عبر `?status=accepted\|pending\|rejected` (افتراضي accepted) + تبويبات وعدّادات |
| GET  | /upcoming-visits           | visits.upcoming     | صفحة الزيارات القريبة المستحقّة (أحدث تقييم لكل عائلة) — زرّ «زيارة جديدة» يفتح **تعديل** أحدث تقييم (لا يُنشئ تقييماً/عائلة جديدة) |
| GET  | /statistics                | statistics.index    | **صفحة الإحصائيات** للعائلات المقبولة: عدد العائلات/عائلات الأيتام/الترميم، الأولاد والأيتام (مع تفصيل ذكور/إناث)، توزيع حسب الحالة الاجتماعية ونوع السكن (`StatisticsController`) |
| GET  | /orphan-reviews            | orphans.index       | صفحة مراجعة الأيتام الذين بلغوا 15+ (للموافقة اليدوية على الإخراج) |
| GET  | /families-browse?filter=   | families.browse     | **تصفّح العائلات المقبولة** حسب معيار: `orphans` (فيها أيتام) · `repair` (تحتاج ترميم) · `married\|divorced\|widowed\|abandoned` (الحالة الاجتماعية). تبويبات + عدّادات (`FamilyController@browse`) |
| GET  | /members-browse?filter=    | members.browse      | **تصفّح أفراد العائلات المقبولة حسب الحالة**: `children` (كل الأولاد) · `orphans` (الأيتام) · `higher_education` (طالب جامعي) · `tutoring` (يحتاج دعم/دروس تقوية) · `contributes` (يعمل/يساهم). تبويبات + عدّادات (`MemberController@browse`) |
| GET  | /orphans                   | orphans.all         | **قائمة جميع الأيتام** في العائلات المقبولة (الاسم/العمر/ولد-بنت/اسم الأم/الهاتف/المسؤول) + زر تصدير PDF |
| GET  | /orphans/pdf               | orphans.pdf         | **تصدير قائمة الأيتام PDF عربي** (mPDF، عرضي A4-L) — inline بتبويب جديد |
| POST | /members/{member}/remove-orphan | members.removeOrphan | موافقة يدوية: إخراج فرد من الأيتام (يُسجَّل كملاحظة عائلة) |
| GET  | /assessments/create        | assessments.create  | صفحة نموذج جديد (**إن لم توجد سياسة معتمدة → redirect لـ `policies.index` لإنشاء السياسة الأولى**) |
| POST | /assessments               | assessments.store   | إنشاء بالسياسة الحالية ← redirect للتفاصيل (يَحرس غياب السياسة أيضاً) |
| GET  | /assessments/{a}           | assessments.show    | صفحة التفاصيل (Blade صرف) + زر طباعة PDF |
| GET  | /assessments/{a}/pdf       | assessments.pdf     | **تصدير التقييم PDF عربي** (mPDF) — يُعرض inline بتبويب جديد |
| GET  | /assessments/{a}/edit      | assessments.edit    | صفحة نموذج التعديل |
| PUT  | /assessments/{a}           | assessments.update  | تحديث (إعادة حساب بسياسة التقييم نفسها) ← redirect |
| POST | /assessments/{a}/decision  | assessments.decide  | القرار اليدوي {decision, note} ← redirect |
| POST | /assessments/{a}/convert   | assessments.convert | تحويل للإصدار الأحدث ← redirect |
| GET  | /scoring-policy            | policies.index      | صفحة السياسة المعتمدة + سجل الإصدارات |
| POST | /scoring-policy            | policies.store      | حفظ = إصدار جديد ← redirect |
| POST | /families/{family}/notes   | families.notes.store | إضافة ملاحظة لعائلة (سجل تراكمي) ← redirect |
| POST | /families/{family}/attachments | families.attachments.store | **رفع مرفق** (صورة/PDF حتى 10MB) للعائلة ← redirect |
| GET  | /attachments/{attachment}  | attachments.show    | **عرض/تنزيل مرفق** inline من disk(local) خلف auth (binary response) |
| DELETE | /attachments/{attachment} | attachments.destroy | حذف مرفق (الملف من القرص + الصف) ← redirect |
| GET  | /unassigned-families       | families.unassigned | **عائلات مقبولة بلا مسؤول** + ربط سريع (`FamilyController`) |
| POST | /families/{family}/supervisor | families.assignSupervisor | تحديد/تغيير المسؤول عن العائلة ← redirect |
| GET  | /supervisors               | supervisors.index   | **إدارة المسؤولين** (قائمة + إضافة + تعديل/حذف) |
| POST | /supervisors               | supervisors.store   | إضافة مسؤول ← redirect |
| PUT  | /supervisors/{supervisor}  | supervisors.update  | تعديل مسؤول ← redirect |
| DELETE | /supervisors/{supervisor} | supervisors.destroy | حذف مسؤول — **يُمنع إن كان لديه عائلات مرتبطة** (يجب نقلها أولاً)؛ زر الحذف معطّل في الواجهة لمن `families_count>0` ← redirect |
| GET/PUT/DELETE | /profile         | profile.edit/update/destroy | إدارة الحساب (Breeze) |

> الـ controllers ترجّع `view()` / `redirect()` (لا JSON عدا الـ PDF الذي يُرجَع كـ binary response). أخطاء التحقق ترجع عبر `old()` + `$errors` ويُعاد ملء النموذج.

## بنية الواجهة (resources/views)

```
layouts/main.blade.php              ← الهيكل RTL + الشريط العلوي (رابط الرئيسية فقط + شارة السياسة + المستخدم/خروج؛ بقية الأقسام تُفتح من بطاقات الرئيسية) + رسائل flash/أخطاء + @stack('scripts')
dashboard.blade.php                 ← الصفحة الرئيسية (بطاقات الأقسام + عدّادات؛ تمتد layouts.main)
assessments/
  index.blade.php                   ← القائمة مقسّمة حسب القرار (تبويبات مقبولة/قيد الانتظار/مرفوضة عبر ?status=) + عدّادات
  show.blade.php                    ← التفاصيل + نماذج القرار/التحويل + ملاحظات العائلة + مرفقات العائلة (صور/PDF) + زر طباعة PDF (Blade صرف)
  create.blade.php · edit.blade.php ← يغلّفان النموذج المشترك
  partials/_form.blade.php          ← النموذج المشترك (Alpine: صفوف ديناميكية + معاينة حيّة + تحقّق/تنبيه، يُرسل POST/PUT عادي)
  pdf.blade.php                     ← قالب طباعة الـ PDF (HTML + CSS داخلي مناسب لـ mPDF، RTL — لا يمتد layouts.main)
statistics/index.blade.php          ← صفحة الإحصائيات (بطاقات + توزيعات للعائلات المقبولة)
orphans/index.blade.php             ← مراجعة الأيتام (15+) للموافقة اليدوية على الإخراج
orphans/all.blade.php               ← قائمة جميع الأيتام في العائلات المقبولة + زر تصدير PDF
orphans/pdf.blade.php               ← قالب طباعة قائمة الأيتام (مستقل، عرضي A4-L، لا يمتد layouts.main)
visits/upcoming.blade.php           ← الزيارات القريبة المستحقّة (أحدث تقييم لكل عائلة) — المقبولة فقط
families/browse.blade.php           ← تصفّح العائلات المقبولة (تبويبات: أيتام/ترميم + شرائط الحالة الاجتماعية) — جدول
members/browse.blade.php            ← تصفّح أفراد العائلات المقبولة (تبويبات: كل الأولاد/الأيتام) — جدول بشارات الحالة
families/unassigned.blade.php        ← عائلات مقبولة بلا مسؤول + ربط سريع inline
supervisors/index.blade.php         ← إدارة المسؤولين (إضافة/تعديل/حذف؛ Alpine لتبديل وضع التعديل)
policies/index.blade.php            ← سياسة النقاط (نموذج Blade صرف + سجل الإصدارات)
```

- موجّه Blade `@money($value)` مُسجَّل في `AppServiceProvider` لعرض المبالغ (₪).
- الشريط العلوي مبسّط: رابط «الرئيسية» فقط + شارة السياسة + اسم المستخدم/خروج. كل الأقسام تُفتح من بطاقات الصفحة الرئيسية (`dashboard`).
- في النموذج: كل checkbox مسبوق بـ `<input type="hidden" value="0">` بنفس الاسم لضمان إرسال false، والقيم المنطقية تُطبّع لأنواع حقيقية قبل تمريرها لـ Alpine.
- **الأيتام:** `family_members.is_orphan` يُضبط آلياً عند الحفظ (إنشاء/تعديل) عبر `applyOrphanRules` بشرط (حالة العائلة `widowed` + عمر الفرد < 15). **القاعدة تُضيف فقط ولا تُزيل.** و`assessments.has_orphans` **مُشتق آلياً** = وجود أي فرد يتيم (لا checkbox يدوي في النموذج). الإخراج من الأيتام **يدوي فقط** عبر صفحة `orphans.index` بموافقة المستخدم (متاح لمن بلغ 15+)، ويُسجَّل كملاحظة عائلة. في النموذج يُرسَل `is_orphan` كحقل مخفي (الحالة الثابتة) ويعيد السيرفر تطبيق القاعدة فوقه.
- **منع تكرار الهوية/الهاتف (نهائي):** الأعمدة `husband_id, wife_id, husband_phone, wife_phone` عليها **فهارس فريدة في القاعدة** (NULL متعدد مسموح؛ لذا تُطبَّع الفراغات إلى null في `validateData`). فوقها تحقّق سيرفر-side ودّي (`ensureIdentifiersUnique`) يفحص القيمة في عمودَي الزوج والزوجة معاً ويرفض التكرار برسالة واضحة قبل القاعدة. وتنبيه حيّ في النموذج بتضمين القيم الموجودة كـ JSON (بلا fetch) يقارنها Alpine أثناء الكتابة.

## التحقّق من النموذج (إلزامي — إنشاء/تعديل)

التحقّق على **طبقتين**؛ السيرفر هو المعتمد:

**1) السيرفر (المصدر المعتمد) — `validateData()` + `validationMessages()` + `ensureParentComplete()`:**
- **منفذو الزيارة:** `visit_date` + `visitors` → مطلوبان.
- **العائلة:** يجب اكتمال **أحد الزوجين على الأقل**: الاسم + الهوية + الهاتف + تاريخ الميلاد لنفس الطرف
  (يفرضها `ensureParentComplete` — قاعدة عبر-حقول لا تُعبَّر بقواعد Laravel العادية).
- **كل فرد مُضاف:** `name` + `dob` + `gender` → مطلوبة.
- **كل بند مالي مُضاف:** `category` + `amount` → مطلوبة، والمبلغ `numeric|min:0`.
- رسائل خطأ عربية واضحة عبر `validationMessages()`؛ الرفض يرجع بـ `old()` + `$errors`.

**2) العميل (تجربة فورية، Alpine في `_form.blade.php`):**
- `validateForm()` يُستدعى عند `@submit`؛ يمنع الإرسال ويبني `errors[]` + خريطة `invalid{}`.
- **بانر أحمر** أعلى النموذج يسرد ما ينقص بدقّة (مثل «الفرد #2: تاريخ الميلاد مطلوب»).
- **إطار أحمر** حول كل حقل ناقص عبر `ring(key)`، ويتحدّث **حيّاً** بعد أول محاولة (`x-effect` + `attempted`).
- متوافق مع تنبيهات تكرار الهوية/الهاتف (`dupId`/`dupPhone`) عبر دمج `:class` كمصفوفة.

## تصدير PDF (mPDF)

- `AssessmentController@pdf` → route `assessments.pdf` → يرندر `assessments/pdf.blade.php` ويمرّره لـ mPDF.
- إعداد mPDF: `mode=utf-8`, `format=A4`, `default_font=xbriyaz`, `SetDirectionality('rtl')`,
  `autoScriptToLang`/`autoLangToFont = true`، `tempDir = storage_path('app/mpdf')`.
- القالب `pdf.blade.php` **مستقل** (HTML + CSS داخلي، بلا Tailwind/Alpine، بلا `layouts.main`) لأن mPDF يدعم CSS محدوداً.
- يُرجَع كاستجابة `application/pdf` بترويسة `inline` (عرض داخل المتصفح). يشمل: الملخّص، الزيارة، العائلة، السكن،
  جدول الأفراد، المالية مع الإجماليات، النواقص، القرار، سجل التدقيق.
- **متطلّب:** إضافة `gd` مفعّلة في `php.ini`.

## تعليقات أعمدة قاعدة البيانات (SQLite)

- SQLite **لا تدعم** وصف الأعمدة مثل MySQL. الحل: تعليقات `--` داخل نص `CREATE TABLE` في `sqlite_master`.
- سكربت `database/annotate_schema.php` **متجدّد وآمن**: يقرأ تعريف كل جدول الحيّ ويُبقيه حرفياً ويضيف التعليق فقط
  (عبر `writable_schema`)، فيطابق المخطط الحالي دائماً ويغطّي الأعمدة الجديدة تلقائياً. الأوصاف في مصفوفة `$desc` (مفتاح `table.column`).
- تظهر بتبويب **Schema** في DB Browser for SQLite.
- **مهم:** أي `migrate:fresh` أو ترحيل يعيد بناء جدول (مثل إضافة FK) يمسح تعليقات ذلك الجدول → أعد تشغيل `php database/annotate_schema.php` بعده.

## الوضع الحالي

- ✅ migrations (12) + models (10 مجال + User) + ScoringEngine + Controllers (سيرفر-side) + seeder + routes + كل صفحات Blade — جاهزة ومفحوصة.
- ✅ **مرفقات العائلة:** `family_attachments` + `FamilyAttachmentController` (رفع/عرض/حذف) — صور/PDF على disk(local) تُخدَم خلف auth، تظهر في صفحة التفاصيل بمعاينة مصغّرة.
- ✅ Breeze مُركَّب (مصادقة + بروفايل)؛ بعد الدخول يوجّه إلى `dashboard` ← الصفحة الرئيسية (بطاقات الأقسام).
- ✅ الصفحة الرئيسية (بطاقات: مقبولة/قيد الانتظار/مرفوضة + الأقسام) + التحقّق الإلزامي + تصدير PDF + تعليقات الأعمدة — مُضافة ومفحوصة.
- ✅ التقييمات مقسّمة حسب القرار (تبويبات). `visits.upcoming` و`orphans.index` تعرضان **العائلات المقبولة فقط** (decision=accepted).
- ✅ ملاحظات العائلة (`family_notes` + `FamilyNoteController`) في صفحة التفاصيل.
- ✅ **المسؤولون عن العائلات:** جدول `supervisors` يُدار من صفحته؛ `families.supervisor_id` (اختياري) يُحدَّد من نموذج التقييم أو من صفحة «مقبولة بلا مسؤول» (ربط سريع). `families.description` وصف حرّ اختياري. كلاهما من هوية العائلة (لا التقييم).
- ⏳ ملاحظات مفتوحة: تأكيد قاعدة "عدد الأهل 2/1" مع المبرة (أدناه).

## التشغيل المحمول من فلاشة USB (بلا تثبيت)

البرنامج **قابل للنقل بالكامل** لأن القاعدة SQLite (ملف واحد) والأصول مبنية مسبقاً في `public/build`.
لا حاجة لـ XAMPP ولا MySQL ولا `npm` ولا إنترنت على الجهاز المضيف — فقط **PHP محمول**.

**بنية الفلاشة:**
```
USB:\
├── charity-assessment\   ← كامل مجلد المشروع (يحوي ملفّي الـ bat)
└── php\php.exe           ← PHP 8.2+ Windows x64 Thread Safe (ZIP، بلا تثبيت)
                              # VS16=بناء 8.2 · VS17=بناء 8.3+ — كلاهما يعمل (المشروع يتطلّب php ^8.2)
```

**تجهيز `php\php.ini` (مرة واحدة):** انسخ `php.ini-development` → `php.ini` وفعّل الإضافات:
`pdo_sqlite`, `sqlite3`, `gd` (ضرورية لـ mPDF/PDF), `fileinfo`, `mbstring`, `openssl`.

**أربعة ملفّات `.bat` جاهزة على جذر المشروع (يُنقَر عليها مباشرة):**
- `أول-تثبيت.bat` — **مرة واحدة بعد `git clone`:** ينسخ `.env.example`→`.env` + `key:generate` + ينشئ
  `database.sqlite` فارغة + `migrate --force` + تنظيف الكاش. (لا يَزرع سياسة — تُنشأ يدوياً من `policies.index`.)
- `تشغيل-البرنامج.bat` — يجد PHP في `..\php` أو `.\php`، ينظّف الكاش (`config:clear`/`view:clear`)،
  يفتح المتصفح على `http://127.0.0.1:8000`، ويُشغّل `php artisan serve`. تبقى نافذته مفتوحة أثناء الاستعمال.
- `تحديث.bat` — يأخذ نسخة احتياطية ثم `git pull` + `migrate --force` + تنظيف الكاش. يتطلّب **Git مثبّتاً** على الجهاز.
- `نسخة-احتياطية.bat` — ينسخ `database/database.sqlite` إلى `backups\database_<طابع-زمني>.sqlite`
  (طابع زمني عبر PowerShell)، ويُبقي آخر **30 نسخة** فقط ويحذف الأقدم.

**أعراف التشغيل المحمول:** لا تُنزع الفلاشة والنافذة السوداء مفتوحة (تلف محتمل للقاعدة)؛
خذ نسخة احتياطية دورية وانقل مجلد `backups\` إلى قرص آخر؛ سيناريو الاستخدام المعتمد: **شخص واحد/جهاز واحد**.

### المستودع على GitHub (تسليم التحديثات)

- المستودع **الخاص**: `https://github.com/mustafa88/charity-assessment` — يُدار من جهاز التطوير ويُسحَب على الفلاشة.
- **مرفوع عمداً خلافاً للمعتاد:** `vendor/` و`public/build/` (أُزيلا من `.gitignore`) كي يعمل التحديث بـ `git pull`
  دون حاجة لـ Composer/Node على جهاز مستخدم الفلاشة (يملك PHP المحمول فقط).
- **مستثنى (بيانات محلية لا تُرفع أبداً):** `database/*.sqlite` و`.env` و`storage/app/private/family-attachments`
  و`/backups` — حتى لا يطمسها `git pull`. لذا التثبيت الأول يبني القاعدة من الـ migrations عبر `أول-تثبيت.bat`.
- **دورة المطوّر:** بعد تعديل Blade/CSS → `npm run build` ثم commit لملفات `public/build`. بعد تغيير الاعتماديات →
  `composer install` ثم commit لـ `vendor`. ثم `git push`. يصل التحديث جاهزاً للفلاشة بـ «تحديث.bat».

## أوامر مهمة
```bash
php artisan migrate
php artisan db:seed --class=ScoringPolicySeeder   # الاسم القصير يتجنّب مشاكل الـ backslash (PowerShell)
php artisan db:seed --class=DemoFamiliesSeeder    # بيانات تجريبية للعرض: 24 عائلة كاملة (تراكمي — للتطوير/العرض فقط، لا التثبيت الحقيقي)
php artisan serve

npm run build                  # بناء الأصول (CSS/JS) — مطلوب بعد أي تعديل Blade/CSS
php database/annotate_schema.php   # إرجاع تعليقات أعمدة SQLite (بعد أي migrate:fresh)
```

> ملاحظة PowerShell: مرّر اسم seeder قصيراً (`ScoringPolicySeeder`) أو بين علامتي اقتباس مفردتين —
> الـ backslashes غير المُقتبَسة تُمسح فيفشل التحميل.

## أعراف
- منطق الحساب في `ScoringEngine` حصراً.
- كل تغيير في السياسة = إصدار جديد، لا تعديل في المكان.
- لا تشتقّ `decision` من `recommended` أبداً.
- الواجهة سيرفر-side: الـ controllers ترجّع `view()`/`redirect()` — لا JSON ولا fetch (استثناء وحيد: PDF كاستجابة binary).
- كل شاشة جديدة = route مُسمّى + ملف Blade يمتد `layouts.main` (عدا قالب الـ PDF المستقل). JS (Alpine) يُحقن عبر `@push('scripts')`.
- الحقول الإلزامية تُفرَض في **السيرفر أولاً** (`validateData`)، ثم تُكرَّر تجربةً في العميل (Alpine) — لا يُكتفى بالعميل.
- بعد تعديل أي Blade/CSS: `npm run build`. بعد أي `migrate:fresh`: `php database/annotate_schema.php`.
- **توثيق ذاتي:** أي إضافة أو تعديل جديد على المشروع (route/جدول/شاشة/ميزة/تبعية) **يُوثَّق فوراً في هذا الملف** ليبقى مطابقاً للمشروع 100%.

# موقع بيسان للتكييف والتبريد

موقع كامل مبني على تصاميم `stitch_` (نفس الهوية البصرية) مع لوحة تحكم مبنية على مخطط ERD.

| الطبقة | التقنيات |
|---|---|
| الواجهة الأمامية | HTML · CSS · JavaScript · Bootstrap 5.3 RTL · خط Cairo · Material Symbols |
| الواجهة الخلفية | PHP 8.1+ (PDO) · jQuery 3.7 (AJAX) · MySQL 8 / MariaDB 10.5+ |

---

## التشغيل محلياً

```bash
# 1) تثبيت PHP مع إضافات MySQL (Ubuntu)
sudo apt install php-cli php-mysql php-mbstring

# 2) إنشاء قاعدة البيانات والمستخدم ثم الجداول والبيانات التجريبية
sudo mysql < database/setup.sql
mysql -u bisan -p'Bisan#Db2026' bisan_db < database/schema.sql
mysql -u bisan -p'Bisan#Db2026' bisan_db < database/seed.sql

# 3) تشغيل الخادم
php -S localhost:8000
```

ثم افتح:
- الموقع: <http://localhost:8000>
- لوحة التحكم: <http://localhost:8000/admin/> ← في أول مرة تفتح صفحة **إنشاء حساب المشرف الأول** (`admin/setup.html`)، وتُغلق تلقائياً بعد إنشائه.

> بيانات الاتصال بقاعدة البيانات في `config/config.php`، ويمكن تجاوزها بمتغيرات البيئة
> `BISAN_DB_HOST / BISAN_DB_NAME / BISAN_DB_USER / BISAN_DB_PASS`. فعّل رسائل الأخطاء التفصيلية بـ `BISAN_DEBUG=1`.
> **غيّر كلمة مرور قاعدة البيانات قبل النشر** (في `database/setup.sql` و`config/config.php`).

> **ترقية قاعدة قائمة من الإصدار الأول** (بدون فقدان البيانات) — مرة واحدة فقط:
> `mysql -u bisan -p'Bisan#Db2026' bisan_db < database/migrate_v2.sql`

على Apache: انسخ المجلد إلى `/var/www/html/bisan` — مسار الموقع (`BASE_URL`) يُكتشف تلقائياً.

---

## هيكل المشروع

الواجهة الأمامية (HTML · CSS · JavaScript · Bootstrap) منفصلة تماماً عن الخلفية (PHP · jQuery):
ملفات `.html` لا تحتوي أي كود PHP، وتجلب بياناتها من واجهات PHP بصيغة JSON عبر jQuery (AJAX).

```
bisan/
├── index.html  products.html  product.html  services.html
├── maintenance.html  contact.html  about.html      ← صفحات الزوار (HTML فقط)
├── partials/header.html  footer.html               ← الرأس والفوتر المشتركان (تُحمَّل بـ jQuery)
├── admin/
│   ├── login.html  setup.html                      ← الدخول وإنشاء أول مشرف
│   ├── index.html                                  ← لوحة التحكم
│   ├── manage.html?e=...                           ← صفحة إدارة عامة لكل الكيانات
│   ├── settings.html                               ← بيانات الشركة
│   ├── auth.php                                    ← JSON: status · login · setup · logout · me
│   └── api.php                                     ← JSON: list · meta · save · patch · delete · dashboard · settings
├── api/                                            ← واجهات الزوار (JSON)
│   ├── site.php          ← بيانات الشركة ومحتوى الموقع + رمز CSRF
│   ├── catalog.php       ← الفئات، المنتجات المميزة، الخدمات، العلامات التجارية
│   ├── products.php      ← كتالوج المنتجات (فلترة/بحث/ترتيب/ترقيم)
│   ├── product.php       ← تفاصيل منتج + منتجات مشابهة
│   └── request.php       ← استقبال طلبات الصيانة ورسائل التواصل
├── includes/             ← entities.php · functions.php · db.php · auth.php · settings_schema.php
├── assets/css/style.css  ← رموز التصميم (ألوان/خطوط/مسافات Stitch) فوق Bootstrap
├── assets/js/
│   ├── main.js           ← تحميل الرأس/الفوتر وبيانات الشركة وربطها بالصفحة (data-text / data-src / data-href)
│   ├── home.js  products.js  product.js  services.js  maintenance.js
│   └── admin.js  auth.js  dashboard.js  manage.js  settings.js
├── database/             ← setup.sql · schema.sql · seed.sql · migrate_v2.sql
└── uploads/              ← صور المنتجات، مخططات AutoCAD، الشعار
```

> الموقع يحتاج خادماً يشغّل PHP (مثل `php -S` أو Apache)، لأن صفحات HTML تجلب بياناتها من ملفات PHP.

---

## من ERD إلى قاعدة البيانات

| كيان ERD | الجدول | العلاقات (حسب المخطط) | صفحة الإدارة |
|---|---|---|---|
| CUSTOMER | `customer` | MAKES · OWNS · BILLED_TO | العملاء |
| SERVICE | `service` | REQUESTED_AS (1:N) | الخدمات |
| SERVICE_REQUEST | `service_request` | ← CUSTOMER (MAKES)، ← SERVICE (REQUESTED_AS) — كلاهما إلزامي | طلبات الصيانة |
| TECHNICIAN | `technician` | PERFORMS (1:N) | الفنيون |
| VISIT | `visit` | ← SERVICE_REQUEST (REQUIRES)، ← TECHNICIAN (PERFORMS) — كلاهما إلزامي | الزيارات |
| SPARE_PART | `spare_part` | USED_IN | قطع الغيار |
| VISIT_PART | `visit_part` (مفتاح مركّب) | INCLUDES + USED_IN | قطع كل زيارة |
| PROJECT | `project` | ← CUSTOMER (OWNS) | المشاريع |
| AUTOCAD_PLAN | `autocad_plan` | ← PROJECT (HAS) | مخططات AutoCAD |
| AC_UNIT | `ac_unit` | ← PROJECT (CONTAINS) | وحدات التكييف |
| WARRANTY | `warranty` | ← AC_UNIT (COVERED_BY، 1:1 عبر UNIQUE) | الضمانات |
| INVOICE | `invoice` | ← CUSTOMER، ← SERVICE_REQUEST، ← PROJECT | الفواتير |

**المشاركة الكلية (الخط المزدوج في ERD)** مطبّقة كمفاتيح أجنبية `NOT NULL`، والحذف محمي (`RESTRICT`) حيث يلزم: لا تُحذف خدمة أو فني أو فئة أو قطعة غيار مرتبطة بسجلات.

**جداول المتطلبات الإضافية (خارج ERD):**

| كيان المتطلبات | الجدول | الحقول | العلاقة |
|---|---|---|---|
| Admin | `admin` | admin_id, name, email, password_hash, **role** (`super_admin` / `admin`) | — (إدارة المشرفين للمشرف العام فقط) |
| Company | `company` (سجل واحد) | name, description, logo, phone, email, address | — |
| Product Category | `category` | category_id, name, description | CATEGORY 1 — N PRODUCT |
| Product | `product` | product_id, name, brand, model, description, price, image, is_available, category_id (إلزامي) | ← CATEGORY |
| Contact Message | `contact_message` | message_id, customer_id, subject, message, date_created, status | CUSTOMER 1 — N CONTACT_MESSAGE |
| Maintenance Request | `service_request` + **phone, address, preferred_date** | انظر الجدول أعلاه | ← CUSTOMER، ← SERVICE |

- `setting`: محتوى الموقع الإضافي (نصوص الواجهة، الإحصائيات، الروابط) — بيانات الشركة الأساسية في `company`.
- حقول عرض في `service`: `base_price` (= price), `price_unit`, `icon`, `is_active`, `sort_order`.

**كيف تُربط نماذج الزوار بـ ERD:**
- **طلب صيانة** ⇒ يُبحث عن العميل بالهاتف (أو البريد) أو يُنشأ ⇒ `SERVICE_REQUEST` (النوع والأولوية يُشتقان من تصنيف الخدمة) مع الهاتف والعنوان والتاريخ المفضل ⇒ المشرف يُنشئ `VISIT` لاحقاً مع تعيين الفني.
- **رسالة تواصل** ⇒ `CUSTOMER` + `CONTACT_MESSAGE`. حالاتها: غير مقروءة / مقروءة / تم الرد / مؤرشفة.

**إجراءات تلقائية:**
- إضافة قطعة لزيارة تخصمها من المخزون، وتعديلها أو حذفها يعيد الكمية، ويُرفض الطلب إذا لم يكفِ المخزون.
- تاريخ انتهاء الضمان يُحسب تلقائياً من أطول مدة ضمان إذا تُرك فارغاً.
- الفاتورة يجب أن ترتبط بطلب صيانة أو بمشروع.
- لا يمكن حذف سجل له سجلات مرتبطة محمية، مثل قطعة غيار استُخدمت في زيارة.

### إضافة كيان جديد للوحة التحكم
أضف تعريفه في `includes/entities.php` (الجدول والحقول وأنواعها)، ثم رابطاً في `admin_menu()`. الجدول والنموذج والتحقق والبحث والترقيم تعمل تلقائياً.

---

## الأمان
- استعلامات مُعدّة (PDO prepared statements) في كل مكان، وأسماء الأعمدة تأتي من ملف الإعداد فقط.
- رمز CSRF مع كل طلب POST (نماذج و AJAX).
- كلمات المرور بـ `password_hash`، وقفل مؤقت بعد 5 محاولات دخول فاشلة، و"تذكرني" بتوكن مُجزّأ (hashed).
- رفع الملفات بقائمة امتدادات مسموحة، وتحقق من أن الصور صور فعلاً، وأسماء عشوائية، و`.htaccess` يمنع تنفيذ السكربتات داخل `uploads/`.
- مصيدة روبوتات (honeypot) وحدّ زمني لتكرار إرسال نماذج الزوار.
- كل المخرجات مُهرّبة (`h()` في PHP و `B.esc()` في JS).

## ملاحظات
- الصور التجريبية في `seed.sql` روابط من تصاميم Stitch على `lh3.googleusercontent.com`، وقد تتوقف عن العمل لاحقاً. ارفع صوراً بديلة من لوحة التحكم (المنتجات / بيانات الشركة).
- لعرض خريطة حقيقية: ضع رابط تضمين Google Maps في **بيانات الشركة**.

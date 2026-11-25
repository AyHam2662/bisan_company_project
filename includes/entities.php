<?php

class ValidationError extends RuntimeException
{
    public array $errors;
    public function __construct(array $errors, string $message = 'يرجى تصحيح الحقول المحددة.')
    {
        parent::__construct($message);
        $this->errors = $errors;
    }
}

const REF_CUSTOMER = ['customer', 'customer_id', "CONCAT(name, IFNULL(CONCAT(' — ', phone), ''))"];
const REF_SERVICE  = ['service', 'service_id', 'service_name'];
const REF_REQUEST  = ['service_request', 'request_id', "CONCAT('#MNT-', request_id, ' — ', (SELECT c.name FROM customer c WHERE c.customer_id = service_request.customer_id))"];
const REF_TECH     = ['technician', 'technician_id', 'name'];
const REF_VISIT    = ['visit', 'visit_id', "CONCAT('زيارة #', visit_id, ' — ', DATE_FORMAT(scheduled_date, '%Y-%m-%d'))"];
const REF_PART     = ['spare_part', 'part_id', "CONCAT(part_name, ' (', part_number, ') — المخزون: ', stock_quantity)"];
const REF_PROJECT  = ['project', 'project_id', 'title'];
const REF_UNIT     = ['ac_unit', 'unit_id', "CONCAT('#', unit_id, ' ', IFNULL(brand, ''), ' ', IFNULL(model, ''))"];
const REF_CATEGORY = ['category', 'category_id', 'name'];

function get_entities(): array
{
    static $entities = null;
    if ($entities !== null) {
        return $entities;
    }

    $entities = [
        'requests' => [
            'table' => 'service_request', 'pk' => 'request_id', 'icon' => 'build_circle',
            'title' => 'طلبات الصيانة', 'singular' => 'طلب صيانة',
            'order' => 't.date_created DESC',
            'search' => ['t.message', 't.request_id', 't.phone', 't.address'],
            'fields' => [
                'request_id'   => ['label' => 'رقم الطلب', 'type' => 'code', 'list' => true, 'form' => false],
                'customer_id'  => ['label' => 'العميل', 'type' => 'fk', 'ref' => REF_CUSTOMER, 'required' => true, 'list' => true],
                'service_id'   => ['label' => 'الخدمة', 'type' => 'fk', 'ref' => REF_SERVICE, 'required' => true, 'list' => true],
                'request_type' => ['label' => 'نوع الخدمة', 'type' => 'enum', 'labels' => 'request_type', 'required' => true, 'list' => true],
                'phone'        => ['label' => 'هاتف التواصل', 'type' => 'tel', 'list' => true],
                'preferred_date' => ['label' => 'التاريخ المفضل', 'type' => 'date', 'list' => true],
                'address'      => ['label' => 'العنوان', 'type' => 'text', 'list' => true, 'col' => 12],
                'priority'     => ['label' => 'الأولوية', 'type' => 'enum', 'labels' => 'priority', 'required' => true, 'list' => true],
                'status'       => ['label' => 'الحالة', 'type' => 'enum', 'labels' => 'request_status', 'required' => true, 'list' => true, 'inline' => true],
                'date_created' => ['label' => 'تاريخ الطلب', 'type' => 'datetime', 'list' => true, 'form' => false],
                'message'      => ['label' => 'وصف المشكلة', 'type' => 'textarea', 'col' => 12],
            ],
            'children' => [['visits', 'الزيارات', 'request_id', 'event'], ['invoices', 'الفواتير', 'request_id', 'receipt_long']],
        ],

        'messages' => [
            'table' => 'contact_message', 'pk' => 'message_id', 'icon' => 'mail',
            'title' => 'رسائل التواصل', 'singular' => 'رسالة',
            'order' => 't.date_created DESC',
            'search' => ['t.subject', 't.message'],
            'can_add' => false,
            'fields' => [
                'message_id'   => ['label' => '#', 'type' => 'number', 'list' => true, 'form' => false],
                'subject'      => ['label' => 'الموضوع', 'type' => 'text', 'required' => true, 'list' => true, 'col' => 12],
                'customer_id'  => ['label' => 'المرسل', 'type' => 'fk', 'ref' => REF_CUSTOMER, 'required' => true, 'list' => true],
                'date_created' => ['label' => 'التاريخ', 'type' => 'datetime', 'list' => true, 'form' => false],
                'status'       => ['label' => 'الحالة', 'type' => 'enum', 'labels' => 'message_status', 'required' => true, 'list' => true, 'inline' => true],
                'message'      => ['label' => 'نص الرسالة', 'type' => 'textarea', 'required' => true, 'col' => 12, 'list' => true],
            ],
        ],

        'visits' => [
            'table' => 'visit', 'pk' => 'visit_id', 'icon' => 'event',
            'title' => 'الزيارات الميدانية', 'singular' => 'زيارة',
            'order' => 't.scheduled_date DESC, t.visit_id DESC',
            'search' => ['t.notes'],
            'fields' => [
                'visit_id'       => ['label' => '#', 'type' => 'number', 'list' => true, 'form' => false],
                'request_id'     => ['label' => 'طلب الصيانة', 'type' => 'fk', 'ref' => REF_REQUEST, 'required' => true, 'list' => true],
                'technician_id'  => ['label' => 'الفني', 'type' => 'fk', 'ref' => REF_TECH, 'required' => true, 'list' => true],
                'scheduled_date' => ['label' => 'موعد الزيارة', 'type' => 'date', 'required' => true, 'list' => true],
                'arrival_time'   => ['label' => 'وقت الوصول', 'type' => 'time', 'list' => true],
                'notes'          => ['label' => 'ملاحظات', 'type' => 'textarea', 'col' => 12, 'list' => true],
            ],
            'children' => [['visit_parts', 'القطع', 'visit_id', 'settings_input_component']],
        ],

        'visit_parts' => [
            'table' => 'visit_part', 'pk' => ['visit_id', 'part_id'], 'icon' => 'settings_input_component',
            'title' => 'قطع الغيار المستخدمة في الزيارات', 'singular' => 'قطعة مستخدمة',
            'order' => 't.visit_id DESC',
            'fields' => [
                'visit_id' => ['label' => 'الزيارة', 'type' => 'fk', 'ref' => REF_VISIT, 'required' => true, 'list' => true],
                'part_id'  => ['label' => 'القطعة', 'type' => 'fk', 'ref' => REF_PART, 'required' => true, 'list' => true],
                'quantity' => ['label' => 'الكمية', 'type' => 'number', 'min' => 1, 'required' => true, 'list' => true],
            ],
            'before_save' => function (array $data, ?array $existingRow): array {
                $quantity = (int)$data['quantity'];
                $need = $quantity;
                if ($existingRow && (int)$existingRow['part_id'] === (int)$data['part_id']) {
                    $need = $quantity - (int)$existingRow['quantity'];
                }
                if ($need > 0) {
                    $stock = (int)db_fetch_value('SELECT stock_quantity FROM spare_part WHERE part_id = ? FOR UPDATE', [$data['part_id']]);
                    if ($stock < $need) {
                        throw new ValidationError(['quantity' => 'المخزون المتاح ' . $stock . ' فقط.']);
                    }
                }
                return $data;
            },
            'after_save' => function (array $data, ?array $existingRow): void {
                if ($existingRow) {
                    db_query('UPDATE spare_part SET stock_quantity = stock_quantity + ? WHERE part_id = ?', [(int)$existingRow['quantity'], $existingRow['part_id']]);
                }
                db_query('UPDATE spare_part SET stock_quantity = stock_quantity - ? WHERE part_id = ?', [(int)$data['quantity'], $data['part_id']]);
            },
            'after_delete' => function (array $existingRow): void {
                db_query('UPDATE spare_part SET stock_quantity = stock_quantity + ? WHERE part_id = ?', [(int)$existingRow['quantity'], $existingRow['part_id']]);
            },
        ],

        'invoices' => [
            'table' => 'invoice', 'pk' => 'invoice_id', 'icon' => 'receipt_long',
            'title' => 'الفواتير', 'singular' => 'فاتورة',
            'order' => 't.issue_date DESC, t.invoice_id DESC',
            'fields' => [
                'invoice_id'  => ['label' => 'رقم الفاتورة', 'type' => 'number', 'list' => true, 'form' => false],
                'customer_id' => ['label' => 'العميل', 'type' => 'fk', 'ref' => REF_CUSTOMER, 'required' => true, 'list' => true],
                'request_id'  => ['label' => 'طلب الصيانة', 'type' => 'fk', 'ref' => REF_REQUEST, 'list' => true, 'placeholder' => '— بدون —'],
                'project_id'  => ['label' => 'المشروع', 'type' => 'fk', 'ref' => REF_PROJECT, 'list' => true, 'placeholder' => '— بدون —'],
                'issue_date'  => ['label' => 'تاريخ الإصدار', 'type' => 'date', 'required' => true, 'list' => true, 'default' => 'today'],
                'amount'      => ['label' => 'المبلغ (₪)', 'type' => 'money', 'min' => 0, 'required' => true, 'list' => true],
                'status'      => ['label' => 'الحالة', 'type' => 'enum', 'labels' => 'invoice_status', 'required' => true, 'list' => true, 'inline' => true],
            ],
            'before_save' => function (array $data, ?array $existingRow): array {
                if (empty($data['request_id']) && empty($data['project_id'])) {
                    throw new ValidationError(['request_id' => 'اربط الفاتورة بطلب صيانة أو بمشروع.']);
                }
                return $data;
            },
        ],

        'customers' => [
            'table' => 'customer', 'pk' => 'customer_id', 'icon' => 'group',
            'title' => 'العملاء', 'singular' => 'عميل',
            'order' => 't.customer_id DESC',
            'search' => ['t.name', 't.phone', 't.email', 't.address'],
            'fields' => [
                'name'    => ['label' => 'الاسم', 'type' => 'text', 'required' => true, 'list' => true, 'maxlength' => 150],
                'type'    => ['label' => 'نوع العميل', 'type' => 'enum', 'labels' => 'customer_type', 'required' => true, 'list' => true],
                'phone'   => ['label' => 'الهاتف', 'type' => 'tel', 'list' => true],
                'email'   => ['label' => 'البريد الإلكتروني', 'type' => 'email', 'list' => true],
                'address' => ['label' => 'العنوان', 'type' => 'text', 'list' => true, 'col' => 12],
                'created_at' => ['label' => 'تاريخ التسجيل', 'type' => 'datetime', 'form' => false],
            ],
            'before_save' => function (array $data, ?array $existingRow): array {
                if (empty($data['phone']) && empty($data['email'])) {
                    throw new ValidationError(['phone' => 'أدخل الهاتف أو البريد الإلكتروني على الأقل.']);
                }
                return $data;
            },
            'children' => [
                ['requests', 'الطلبات', 'customer_id', 'build_circle'],
                ['messages', 'الرسائل', 'customer_id', 'mail'],
                ['projects', 'المشاريع', 'customer_id', 'apartment'],
                ['invoices', 'الفواتير', 'customer_id', 'receipt_long'],
            ],
        ],

        'projects' => [
            'table' => 'project', 'pk' => 'project_id', 'icon' => 'apartment',
            'title' => 'المشاريع', 'singular' => 'مشروع',
            'order' => 't.project_id DESC',
            'search' => ['t.title', 't.location'],
            'fields' => [
                'title'       => ['label' => 'عنوان المشروع', 'type' => 'text', 'required' => true, 'list' => true, 'col' => 12],
                'customer_id' => ['label' => 'العميل', 'type' => 'fk', 'ref' => REF_CUSTOMER, 'required' => true, 'list' => true],
                'type'        => ['label' => 'نوع المشروع', 'type' => 'enum', 'labels' => 'project_type', 'required' => true, 'list' => true],
                'location'    => ['label' => 'الموقع', 'type' => 'text', 'list' => true],
                'status'      => ['label' => 'الحالة', 'type' => 'enum', 'labels' => 'project_status', 'required' => true, 'list' => true, 'inline' => true],
                'start_date'  => ['label' => 'تاريخ البدء', 'type' => 'date', 'list' => true],
                'end_date'    => ['label' => 'تاريخ الانتهاء', 'type' => 'date', 'list' => true],
            ],
            'before_save' => function (array $data, ?array $existingRow): array {
                if (!empty($data['start_date']) && !empty($data['end_date']) && $data['end_date'] < $data['start_date']) {
                    throw new ValidationError(['end_date' => 'تاريخ الانتهاء قبل تاريخ البدء.']);
                }
                return $data;
            },
            'children' => [
                ['units', 'الوحدات', 'project_id', 'ac_unit'],
                ['plans', 'المخططات', 'project_id', 'architecture'],
                ['invoices', 'الفواتير', 'project_id', 'receipt_long'],
            ],
        ],

        'units' => [
            'table' => 'ac_unit', 'pk' => 'unit_id', 'icon' => 'ac_unit',
            'title' => 'وحدات التكييف المركّبة', 'singular' => 'وحدة تكييف',
            'order' => 't.unit_id DESC',
            'search' => ['t.model', 't.brand'],
            'fields' => [
                'unit_id'       => ['label' => '#', 'type' => 'number', 'list' => true, 'form' => false],
                'project_id'    => ['label' => 'المشروع', 'type' => 'fk', 'ref' => REF_PROJECT, 'required' => true, 'list' => true],
                'type'          => ['label' => 'النوع', 'type' => 'enum', 'labels' => 'unit_type', 'required' => true, 'list' => true],
                'brand'         => ['label' => 'العلامة التجارية', 'type' => 'text', 'list' => true],
                'model'         => ['label' => 'الموديل', 'type' => 'text', 'list' => true, 'mono' => true],
                'capacity_btu'  => ['label' => 'القدرة (BTU)', 'type' => 'number', 'min' => 0, 'step' => 1000, 'list' => true],
                'energy_rating' => ['label' => 'تصنيف الطاقة', 'type' => 'text', 'maxlength' => 10, 'placeholder' => 'A++', 'list' => true],
                'install_date'  => ['label' => 'تاريخ التركيب', 'type' => 'date', 'list' => true],
            ],
            'children' => [['warranties', 'الضمان', 'unit_id', 'verified_user']],
        ],

        'warranties' => [
            'table' => 'warranty', 'pk' => 'warranty_id', 'icon' => 'verified_user',
            'title' => 'الضمانات', 'singular' => 'ضمان',
            'order' => 't.end_date ASC',
            'fields' => [
                'unit_id'               => ['label' => 'وحدة التكييف', 'type' => 'fk', 'ref' => REF_UNIT, 'required' => true, 'list' => true],
                'system_warranty_years' => ['label' => 'ضمان النظام (سنوات)', 'type' => 'number', 'min' => 0, 'max' => 30, 'required' => true, 'list' => true],
                'motor_warranty_years'  => ['label' => 'ضمان الضاغط/الموتور (سنوات)', 'type' => 'number', 'min' => 0, 'max' => 30, 'required' => true, 'list' => true],
                'start_date'            => ['label' => 'تاريخ البدء', 'type' => 'date', 'required' => true, 'list' => true, 'default' => 'today'],
                'end_date'              => ['label' => 'تاريخ الانتهاء', 'type' => 'date', 'list' => true, 'help' => 'اتركه فارغاً ليُحسب تلقائياً من أطول مدة ضمان.'],
            ],
            'before_save' => function (array $data, ?array $existingRow): array {
                if (empty($data['end_date']) && !empty($data['start_date'])) {
                    $years = max((int)$data['system_warranty_years'], (int)$data['motor_warranty_years']);
                    $data['end_date'] = (new DateTime($data['start_date']))->modify("+{$years} years")->format('Y-m-d');
                }
                return $data;
            },
        ],

        'plans' => [
            'table' => 'autocad_plan', 'pk' => 'plan_id', 'icon' => 'architecture',
            'title' => 'مخططات AutoCAD', 'singular' => 'مخطط',
            'order' => 't.plan_id DESC',
            'fields' => [
                'project_id'    => ['label' => 'المشروع', 'type' => 'fk', 'ref' => REF_PROJECT, 'required' => true, 'list' => true],
                'version'       => ['label' => 'الإصدار', 'type' => 'text', 'maxlength' => 20, 'required' => true, 'list' => true, 'placeholder' => '1.0'],
                'approval_date' => ['label' => 'تاريخ الاعتماد', 'type' => 'date', 'list' => true],
                'file_url'      => ['label' => 'ملف المخطط', 'type' => 'file', 'folder' => 'plans', 'ext' => ['dwg', 'dxf', 'pdf', 'png', 'jpg', 'jpeg', 'zip'], 'required' => true, 'list' => true, 'col' => 12],
            ],
        ],

        'technicians' => [
            'table' => 'technician', 'pk' => 'technician_id', 'icon' => 'engineering',
            'title' => 'الفنيون', 'singular' => 'فني',
            'order' => 't.name',
            'search' => ['t.name', 't.phone', 't.specialty'],
            'fields' => [
                'name'         => ['label' => 'الاسم', 'type' => 'text', 'required' => true, 'list' => true],
                'phone'        => ['label' => 'الهاتف', 'type' => 'tel', 'required' => true, 'list' => true],
                'specialty'    => ['label' => 'التخصص', 'type' => 'text', 'list' => true],
                'availability' => ['label' => 'التوفر', 'type' => 'enum', 'labels' => 'availability', 'required' => true, 'list' => true, 'inline' => true],
            ],
            'children' => [['visits', 'الزيارات', 'technician_id', 'event']],
        ],

        'parts' => [
            'table' => 'spare_part', 'pk' => 'part_id', 'icon' => 'settings_input_component',
            'title' => 'قطع الغيار', 'singular' => 'قطعة غيار',
            'order' => 't.part_name',
            'search' => ['t.part_name', 't.part_number'],
            'fields' => [
                'part_number'    => ['label' => 'رقم القطعة', 'type' => 'text', 'required' => true, 'list' => true, 'mono' => true, 'maxlength' => 60],
                'part_name'      => ['label' => 'اسم القطعة', 'type' => 'text', 'required' => true, 'list' => true],
                'price'          => ['label' => 'السعر (₪)', 'type' => 'money', 'min' => 0, 'required' => true, 'list' => true],
                'stock_quantity' => ['label' => 'الكمية في المخزون', 'type' => 'number', 'min' => 0, 'required' => true, 'list' => true, 'low' => 5],
            ],
            'children' => [['visit_parts', 'الاستخدام', 'part_id', 'history']],
        ],

        'categories' => [
            'table' => 'category', 'pk' => 'category_id', 'icon' => 'category',
            'title' => 'فئات المنتجات', 'singular' => 'فئة',
            'order' => 't.sort_order, t.category_id',
            'extra_select' => ['products_count' => '(SELECT COUNT(*) FROM product p WHERE p.category_id = t.category_id)'],
            'fields' => [
                'name'           => ['label' => 'اسم الفئة', 'type' => 'text', 'required' => true, 'list' => true],
                'products_count' => ['label' => 'عدد المنتجات', 'type' => 'number', 'list' => true, 'form' => false, 'virtual' => true],
                'icon'           => ['label' => 'الأيقونة', 'type' => 'icon', 'required' => true, 'list' => true, 'help' => 'اسم أيقونة من Material Symbols مثل ac_unit'],
                'sort_order'     => ['label' => 'الترتيب', 'type' => 'number', 'list' => true],
                'description'    => ['label' => 'وصف قصير', 'type' => 'textarea', 'col' => 12, 'maxlength' => 300],
            ],
            'children' => [['products', 'المنتجات', 'category_id', 'inventory_2']],
        ],

        'products' => [
            'table' => 'product', 'pk' => 'product_id', 'icon' => 'inventory_2',
            'title' => 'المنتجات', 'singular' => 'منتج',
            'order' => 't.product_id DESC',
            'search' => ['t.name', 't.brand', 't.model'],
            'fields' => [
                'image'         => ['label' => 'الصورة', 'type' => 'image', 'folder' => 'products', 'ext' => ['jpg', 'jpeg', 'png', 'webp'], 'list' => true, 'col' => 12],
                'name'          => ['label' => 'اسم المنتج', 'type' => 'text', 'required' => true, 'list' => true, 'col' => 12],
                'brand'         => ['label' => 'العلامة التجارية', 'type' => 'text', 'required' => true, 'list' => true],
                'model'         => ['label' => 'الموديل', 'type' => 'text', 'list' => true, 'mono' => true],
                'category_id'   => ['label' => 'الفئة', 'type' => 'fk', 'ref' => REF_CATEGORY, 'required' => true, 'list' => true],
                'price'         => ['label' => 'السعر (₪)', 'type' => 'money', 'min' => 0, 'required' => true, 'list' => true],
                'capacity_btu'  => ['label' => 'القدرة (BTU)', 'type' => 'number', 'min' => 0, 'step' => 1000],
                'energy_rating' => ['label' => 'تصنيف الطاقة', 'type' => 'text', 'maxlength' => 10],
                'is_available'  => ['label' => 'التوفر', 'type' => 'bool', 'labels' => 'stock', 'list' => true, 'default' => 1],
                'is_featured'   => ['label' => 'منتج مميز في الرئيسية', 'type' => 'bool', 'labels' => 'yes_no', 'default' => 0],
                'description'   => ['label' => 'وصف المنتج', 'type' => 'textarea', 'col' => 12],
                'specs'         => ['label' => 'المواصفات الفنية', 'type' => 'textarea', 'col' => 12, 'help' => 'سطر لكل مواصفة بالشكل: العنوان | القيمة', 'placeholder' => "القدرة التبريدية | 18,000 BTU\nغاز التبريد | R32"],
            ],
        ],

        'services' => [
            'table' => 'service', 'pk' => 'service_id', 'icon' => 'handyman',
            'title' => 'الخدمات', 'singular' => 'خدمة',
            'order' => 't.sort_order, t.service_id',
            'search' => ['t.service_name', 't.description'],
            'fields' => [
                'service_name' => ['label' => 'اسم الخدمة', 'type' => 'text', 'required' => true, 'list' => true, 'col' => 12],
                'category'     => ['label' => 'التصنيف', 'type' => 'enum', 'labels' => 'service_category', 'required' => true, 'list' => true],
                'base_price'   => ['label' => 'السعر الابتدائي (₪)', 'type' => 'money', 'min' => 0, 'list' => true, 'help' => 'اتركه فارغاً لعرض "حسب المعاينة"'],
                'price_unit'   => ['label' => 'وحدة السعر', 'type' => 'text', 'placeholder' => '/ وحدة', 'maxlength' => 40],
                'icon'         => ['label' => 'الأيقونة', 'type' => 'icon', 'required' => true, 'list' => true],
                'is_active'    => ['label' => 'ظاهرة في الموقع', 'type' => 'bool', 'labels' => 'yes_no', 'list' => true, 'default' => 1],
                'sort_order'   => ['label' => 'الترتيب', 'type' => 'number'],
                'description'  => ['label' => 'الوصف', 'type' => 'textarea', 'col' => 12],
            ],
            'children' => [['requests', 'الطلبات', 'service_id', 'build_circle']],
        ],

        'admins' => [
            'table' => 'admin', 'pk' => 'admin_id', 'icon' => 'admin_panel_settings',
            'title' => 'المشرفون', 'singular' => 'مشرف',
            'order' => 't.admin_id',
            'role' => 'super_admin',
            'select_exclude' => ['password_hash', 'remember_token'],
            'fields' => [
                'name'       => ['label' => 'الاسم', 'type' => 'text', 'required' => true, 'list' => true],
                'email'      => ['label' => 'البريد الإلكتروني', 'type' => 'email', 'required' => true, 'list' => true],
                'role'       => ['label' => 'الدور', 'type' => 'enum', 'labels' => 'admin_role', 'required' => true, 'list' => true, 'default' => 'admin'],
                'password'   => ['label' => 'كلمة المرور', 'type' => 'password', 'virtual' => true, 'help' => '8 أحرف على الأقل. اتركها فارغة عند التعديل للإبقاء على الحالية.'],
                'created_at' => ['label' => 'تاريخ الإنشاء', 'type' => 'datetime', 'list' => true, 'form' => false],
            ],
            'before_save' => function (array $data, ?array $existingRow): array {
                $pass = (string)($_POST['password'] ?? '');
                if ($pass !== '') {
                    if (strlen($pass) < 8) {
                        throw new ValidationError(['password' => 'كلمة المرور يجب أن تكون 8 أحرف على الأقل.']);
                    }
                    $data['password_hash'] = password_hash($pass, PASSWORD_DEFAULT);
                } elseif (!$existingRow) {
                    throw new ValidationError(['password' => 'كلمة المرور مطلوبة للحساب الجديد.']);
                }
                if ($existingRow && (int)$existingRow['admin_id'] === (int)($_SESSION['admin_id'] ?? 0) && $data['role'] !== $existingRow['role']) {
                    throw new ValidationError(['role' => 'لا يمكنك تغيير دور حسابك الحالي.']);
                }
                return $data;
            },
            'before_delete' => function (array $existingRow): void {
                if ((int)$existingRow['admin_id'] === (int)($_SESSION['admin_id'] ?? 0)) {
                    throw new ValidationError([], 'لا يمكنك حذف حسابك الحالي.');
                }
            },
        ],
    ];

    return $entities;
}

function get_admin_menu(): array
{
    return [
        ['', [['dashboard', 'لوحة التحكم', 'admin/index.html', 'dashboard']]],
        ['الطلبات', [
            ['requests', 'طلبات الصيانة', 'admin/manage.html?e=requests', 'build_circle', 'new_requests'],
            ['messages', 'رسائل التواصل', 'admin/manage.html?e=messages', 'mail', 'new_messages'],
            ['visits', 'الزيارات', 'admin/manage.html?e=visits', 'event'],
            ['invoices', 'الفواتير', 'admin/manage.html?e=invoices', 'receipt_long'],
        ]],
        ['العملاء والمشاريع', [
            ['customers', 'العملاء', 'admin/manage.html?e=customers', 'group'],
            ['projects', 'المشاريع', 'admin/manage.html?e=projects', 'apartment'],
            ['units', 'وحدات التكييف', 'admin/manage.html?e=units', 'ac_unit'],
            ['warranties', 'الضمانات', 'admin/manage.html?e=warranties', 'verified_user'],
            ['plans', 'مخططات AutoCAD', 'admin/manage.html?e=plans', 'architecture'],
        ]],
        ['الفريق والمخزون', [
            ['technicians', 'الفنيون', 'admin/manage.html?e=technicians', 'engineering'],
            ['parts', 'قطع الغيار', 'admin/manage.html?e=parts', 'settings_input_component'],
        ]],
        ['الكتالوج', [
            ['categories', 'الفئات', 'admin/manage.html?e=categories', 'category'],
            ['products', 'المنتجات', 'admin/manage.html?e=products', 'inventory_2'],
            ['services', 'الخدمات', 'admin/manage.html?e=services', 'handyman'],
        ]],
        ['الإعدادات', [
            ['settings', 'بيانات الشركة', 'admin/settings.html', 'settings'],
            ['admins', 'المشرفون', 'admin/manage.html?e=admins', 'admin_panel_settings', null, 'super_admin'],
        ]],
    ];
}

function admin_has_access(array $admin, ?string $role): bool
{
    return $role === null || ($admin['role'] ?? '') === $role;
}

function get_admin_counters(): array
{
    return [
        'new_requests' => (int)db_fetch_value("SELECT COUNT(*) FROM service_request WHERE status = 'new'"),
        'new_messages' => (int)db_fetch_value("SELECT COUNT(*) FROM contact_message WHERE status = 'unread'"),
    ];
}

function get_entity_info(string $key): array
{
    $entity = get_entities()[$key];
    $fields = [];
    foreach ($entity['fields'] as $name => $field) {
        unset($field['ref']);
        if (in_array($field['type'], ['enum', 'bool'], true)) {
            $options = [];
            foreach (enum_labels($field['labels']) as $value => [$text, $tone]) {
                if (!in_array((string)$value, $field['exclude'] ?? [], true)) {
                    $options[] = ['value' => (string)$value, 'text' => $text, 'tone' => $tone];
                }
            }
            $field['options'] = $options;
        }
        if (($field['default'] ?? null) === 'today') {
            $field['default'] = date('Y-m-d');
        }
        $fields[$name] = $field;
    }
    $children = [];
    foreach ($entity['children'] ?? [] as [$child, $label, $col, $icon]) {
        $children[] = ['entity' => $child, 'label' => $label, 'col' => $col, 'icon' => $icon];
    }
    return [
        'key' => $key,
        'title' => $entity['title'],
        'singular' => $entity['singular'],
        'icon' => $entity['icon'],
        'pk' => (array)$entity['pk'],
        'fields' => $fields,
        'children' => $children,
        'can_add' => $entity['can_add'] ?? true,
        'can_delete' => $entity['can_delete'] ?? true,
        'searchable' => !empty($entity['search']),
    ];
}

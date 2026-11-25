<?php
return [
    [
        'title' => 'معلومات الشركة والهوية', 'icon' => 'badge',
        'fields' => [
            'company_name'        => ['label' => 'اسم الشركة', 'type' => 'text', 'required' => true],
            'company_short'       => ['label' => 'الاسم المختصر (في الرأس)', 'type' => 'text', 'required' => true],
            'company_tagline'     => ['label' => 'الشعار النصي', 'type' => 'text'],
            'company_description' => ['label' => 'وصف الشركة', 'type' => 'textarea', 'col' => 12],
            'logo'                => ['label' => 'شعار الشركة', 'type' => 'image'],
        ],
    ],
    [
        'title' => 'بيانات التواصل', 'icon' => 'contact_phone',
        'fields' => [
            'phone'           => ['label' => 'رقم الهاتف', 'type' => 'tel', 'required' => true],
            'mobile'          => ['label' => 'رقم الموبايل', 'type' => 'tel'],
            'emergency_phone' => ['label' => 'رقم الطوارئ (24/7)', 'type' => 'tel', 'required' => true],
            'whatsapp'        => ['label' => 'واتساب (بالصيغة الدولية)', 'type' => 'text', 'placeholder' => '970599412830'],
            'email'           => ['label' => 'البريد الإلكتروني', 'type' => 'email', 'required' => true],
            'sales_email'     => ['label' => 'بريد المبيعات', 'type' => 'email'],
            'address'         => ['label' => 'العنوان', 'type' => 'text', 'required' => true, 'col' => 12],
            'address_note'    => ['label' => 'وصف الموقع (أسفل الخريطة)', 'type' => 'text'],
            'working_hours'   => ['label' => 'ساعات العمل', 'type' => 'text'],
            'facebook_url'    => ['label' => 'رابط فيسبوك', 'type' => 'url'],
            'map_embed'       => ['label' => 'رابط تضمين خرائط Google', 'type' => 'url', 'col' => 12, 'help' => 'من Google Maps: مشاركة ← تضمين خريطة ← انسخ قيمة src فقط (https://www.google.com/maps/embed?...)'],
        ],
    ],
    [
        'title' => 'الصفحة الرئيسية', 'icon' => 'home',
        'fields' => [
            'hero_badge' => ['label' => 'الشارة أعلى العنوان', 'type' => 'text', 'col' => 12],
            'hero_title' => ['label' => 'العنوان الرئيسي', 'type' => 'text', 'col' => 12],
            'hero_text'  => ['label' => 'النص التعريفي', 'type' => 'textarea', 'col' => 12],
            'hero_image' => ['label' => 'صورة الواجهة', 'type' => 'image'],
            'about_image' => ['label' => 'صورة صفحة من نحن', 'type' => 'image'],
        ],
    ],
    [
        'title' => 'الإحصائيات', 'icon' => 'monitoring',
        'fields' => [
            'stat_customers'    => ['label' => 'عملاء راضون', 'type' => 'text'],
            'stat_units'        => ['label' => 'وحدات مركبة', 'type' => 'text'],
            'stat_years'        => ['label' => 'سنوات الخبرة', 'type' => 'text'],
            'stat_satisfaction' => ['label' => 'نسبة الرضا', 'type' => 'text'],
            'stat_engineers'    => ['label' => 'عدد المهندسين والفنيين', 'type' => 'text'],
        ],
    ],
];

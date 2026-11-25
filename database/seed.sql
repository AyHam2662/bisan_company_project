SET NAMES utf8mb4;

SET @img_inverter_a = 'assets/img/bisan-inverter-a.jpg';
SET @img_inverter_b = 'assets/img/bisan-inverter-b.jpg';
SET @img_classic_a = 'assets/img/bisan-classic-a.jpg';
SET @img_classic_b = 'assets/img/bisan-classic-b.jpg';
SET @img_floor = 'assets/img/bisan-floor.jpg';
SET @img_hero = 'assets/img/factory-aerial.jpg';
SET @img_logo = 'assets/img/logo.png';
SET @img_team = 'assets/img/factory-front.jpg';

INSERT INTO company (company_id, name, description, logo, phone, email, address) VALUES
(1, 'شركة بيسان للتكييف والتبريد',
    'رواد حلول التكييف والتبريد في فلسطين منذ عام 2017. نجمع بين الكفاءة الهندسية والالتزام الكامل بخدمة عملائنا في كافة المحافظات.',
    @img_logo, '02-298-4510', 'info@bisan-hvac.ps', 'رام الله والبيرة — شارع الإرسال، مبنى بيسان');

INSERT INTO setting (setting_key, setting_value) VALUES
('company_short',       'بيسان'),
('company_tagline',     'للتكييف والتبريد'),
('hero_image',          @img_hero),
('about_image',         @img_team),
('hero_badge',          '#لكل_بيت_فلسطيني — خبرة معتمدة منذ 2017'),
('hero_title',          'حلول التكييف والتبريد التي تثق بها'),
('hero_text',           'نوفر أحدث أنظمة التكييف المنزلي والتجاري في فلسطين مع خدمات تركيب احترافية وصيانة فورية على مدار الساعة بأيدي فنيين معتمدين.'),
('mobile',              '0599-412-830'),
('emergency_phone',     '0599-412-830'),
('sales_email',         'sales@bisan-hvac.ps'),
('address_note',        'شارع الإرسال، بالقرب من دوار الماصيون'),
('working_hours',       'السبت – الخميس: 8:30 ص – 6:00 م'),
('map_embed',           ''),
('facebook_url',        '#'),
('whatsapp',            '970599412830'),
('stat_customers',      '+4,850'),
('stat_units',          '+12,400'),
('stat_years',          '9'),
('stat_satisfaction',   '98.5%'),
('stat_engineers',      '38');

INSERT INTO category (category_id, name, description, icon, sort_order) VALUES
(1, 'مكيفات سبليت إنفرتر', 'تشغيل هادئ وتوفير عالٍ في استهلاك الكهرباء للمنازل والمكاتب.', 'ac_unit', 1),
(2, 'مكيفات سبليت كلاسيك', 'خيار اقتصادي موثوق بتصميم كلاسيكي وسعر مناسب لكل بيت.', 'mode_fan', 2),
(3, 'مكيفات عمودية Free Standing', 'قدرة تبريد عالية للقاعات الكبيرة والمساجد والمطاعم والصالات المفتوحة.', 'view_agenda', 3);

INSERT INTO product (category_id, name, brand, model, capacity_btu, energy_rating, price, is_available, is_featured, description, specs, image) VALUES
(1, 'مكيف بيسان سبليت إنفرتر 12,000 وحدة (بارد / حامي)', 'Bisan', 'BS-12INV', 12000, 'A++', 1950, 1, 1,
 'مكيف جداري من إنتاج مصنع بيسان في فلسطين بتقنية الإنفرتر الموفرة للطاقة، بتصميم أبيض أنيق وتشغيل هادئ يناسب غرف النوم والمكاتب الصغيرة، مع تبريد وتدفئة.',
 'القدرة التبريدية | 12,000 BTU (1 طن)\nنوع الضاغط | إنفرتر موفر للطاقة\nالتشغيل | تبريد وتدفئة\nالمواصفات | معتمد من المواصفات والمقاييس الفلسطينية IEC 60335\nبلد الصنع | فلسطين\nالضمان | 5 سنوات كاملة', @img_inverter_a),
(1, 'مكيف بيسان سبليت إنفرتر 18,000 وحدة (بارد / حامي)', 'Bisan', 'BS-18INV', 18000, 'A++', 2600, 1, 1,
 'إنفرتر موفر للطاقة بقدرة مناسبة لغرف المعيشة والصالونات، يحافظ على درجة حرارة ثابتة باستهلاك أقل للكهرباء.',
 'القدرة التبريدية | 18,000 BTU (1.5 طن)\nنوع الضاغط | إنفرتر موفر للطاقة\nالتشغيل | تبريد وتدفئة\nالمواصفات | معتمد من المواصفات والمقاييس الفلسطينية IEC 60335\nبلد الصنع | فلسطين\nالضمان | 5 سنوات كاملة', @img_inverter_b),
(1, 'مكيف بيسان سبليت إنفرتر 24,000 وحدة (بارد / حامي)', 'Bisan', 'BS-24INV', 24000, 'A+', 3300, 1, 0,
 'قدرة عالية للمساحات الواسعة والمحلات التجارية مع تقنية الإنفرتر وتوزيع هواء قوي ومتجانس.',
 'القدرة التبريدية | 24,000 BTU (2 طن)\nنوع الضاغط | إنفرتر موفر للطاقة\nالتشغيل | تبريد وتدفئة\nالمواصفات | معتمد من المواصفات والمقاييس الفلسطينية IEC 60335\nبلد الصنع | فلسطين\nالضمان | 5 سنوات كاملة', @img_inverter_a),
(2, 'مكيف بيسان سبليت كلاسيك 12,000 وحدة', 'Bisan', 'BS-12CL', 12000, 'A', 1700, 1, 1,
 'مكيف جداري بتصميم كلاسيكي بواجهة لامعة وشريط أسود، خيار اقتصادي بجودة عالية وسعر مناسب، متوفر ضمن حملة التقسيط.',
 'القدرة التبريدية | 12,000 BTU (1 طن)\nنوع الضاغط | روتاري (On/Off)\nالتشغيل | تبريد وتدفئة\nالمواصفات | معتمد من المواصفات والمقاييس الفلسطينية IEC 60335\nبلد الصنع | فلسطين\nالضمان | 5 سنوات كاملة', @img_classic_a),
(2, 'مكيف بيسان سبليت كلاسيك 18,000 وحدة', 'Bisan', 'BS-18CL', 18000, 'A', 2300, 1, 0,
 'أداء موثوق للغرف المتوسطة والكبيرة بواجهة فضية لامعة وتحكم سهل بجهاز التحكم عن بعد.',
 'القدرة التبريدية | 18,000 BTU (1.5 طن)\nنوع الضاغط | روتاري (On/Off)\nالتشغيل | تبريد وتدفئة\nالمواصفات | معتمد من المواصفات والمقاييس الفلسطينية IEC 60335\nبلد الصنع | فلسطين\nالضمان | 5 سنوات كاملة', @img_classic_b),
(3, 'مكيف بيسان عمودي Free Standing 48,000 وحدة', 'Bisan', 'BS-48FS', 48000, 'A', 6500, 1, 1,
 'جهاز عمودي بصوت هادئ يغطي مساحات واسعة مثل القاعات والمطاعم والمساجد، مع هواء نقي وتدفئة وتبريد.',
 'القدرة التبريدية | 48,000 BTU (4 طن)\nالمزايا | صوت هادئ، يغطي مساحة واسعة، هواء نقي\nالتشغيل | تبريد وتدفئة\nالمواصفات | معتمد من المواصفات والمقاييس الفلسطينية IEC 60335\nبلد الصنع | فلسطين\nالضمان | 5 سنوات كاملة', @img_floor);

INSERT INTO service (service_id, service_name, description, category, base_price, price_unit, icon, sort_order) VALUES
(1, 'تركيب وتأسيس المكيفات', 'تمديد مواسير نحاس إيطالية معزولة، تثبيت القواعد الخارجية، وفحص ضغط النيتروجين قبل التشغيل.', 'installation', 250, NULL, 'handyman', 1),
(2, 'صيانة دورية وغسيل شامل', 'تنظيف الوحدة الداخلية والخارجية بمواد معقمة، تسليك مجرى التصريف، ورفع كفاءة التبريد.', 'maintenance', 120, '/ وحدة', 'build_circle', 2),
(3, 'تعبئة غاز الفريون وكشف التسريب', 'فحص إلكتروني دقيق لنقاط اللحام وتعبئة غاز R410A أو R32 الأصلي بالوزن المعتمد.', 'gas', 160, NULL, 'air', 3),
(4, 'تنفيذ مشاريع التكييف المركزي VRF', 'دراسة الأحمال الحرارية، تصميم مخططات الدكت، وتنفيذ الأنظمة المركزية للمباني والمكاتب.', 'central', NULL, NULL, 'domain', 4),
(5, 'خدمة الطوارئ الميدانية', 'تدخل فوري لإصلاح الأعطال المفاجئة في المنازل وغرف التبريد التجارية والمطاعم.', 'emergency', 180, NULL, 'emergency', 5),
(6, 'عقود الصيانة السنوية للمؤسسات', 'زيارات وقائية مجدولة كل 3 أشهر مع أولوية في الاستجابة وخصومات على قطع الغيار.', 'contract', NULL, NULL, 'contract', 6);

INSERT INTO customer (customer_id, name, type, address, email, phone) VALUES
(1, 'خالد منصور',            'individual',  'رام الله — حي الطيرة',  'khaled@example.ps', '0599-100-201'),
(2, 'سوسن الحسن',            'individual',  'نابلس — رفيديا',         'sawsan@example.ps', '0598-220-310'),
(3, 'عيادة الشفاء التخصصية', 'institution', 'البيرة — شارع القدس',    'info@shifa.example.ps', '02-240-5501'),
(4, 'شركة الأفق الهندسية',   'company',     'رام الله — الماصيون',    'office@ofuq.example.ps', '02-296-7700'),
(5, 'عمر الخطيب',            'individual',  'الخليل — عين سارة',      'omar@example.ps', '0597-450-112');

INSERT INTO technician (technician_id, name, phone, specialty, availability) VALUES
(1, 'م. أحمد يوسف',  '0599-300-111', 'أنظمة VRF والتكييف المركزي', 'available'),
(2, 'محمد عودة',     '0599-300-222', 'تركيب وصيانة السبليت',       'busy'),
(3, 'يزن حمدان',     '0599-300-333', 'غرف التبريد والطوارئ',       'available');

INSERT INTO spare_part (part_id, part_number, part_name, price, stock_quantity) VALUES
(1, 'CAP-35UF',  'مكثف تشغيل 35 مايكروفاراد', 45,  40),
(2, 'GAS-R32-3', 'أسطوانة غاز R32 (3 كغم)',    180, 15),
(3, 'FAN-MTR-01','موتور مروحة خارجية',          320, 6),
(4, 'PCB-GR-18', 'لوحة تحكم إلكترونية Gree 18', 410, 4);

INSERT INTO service_request (request_id, customer_id, service_id, request_type, status, priority, phone, address, preferred_date, date_created, message) VALUES
(2039, 3, 4, 'installation', 'completed',   'high',   '02-240-5501', 'البيرة — شارع القدس', '2026-11-21', '2026-11-20 10:15:00', 'تكييف مركزي VRF لطابقين في العيادة.'),
(2040, 2, 1, 'installation', 'in_progress', 'normal', '0598-220-310', 'نابلس — رفيديا',      '2026-11-24', '2026-11-22 09:30:00', 'تركيب مكيف سبليت في غرفة النوم.'),
(2041, 1, 2, 'maintenance',  'new',         'normal', '0599-100-201', 'رام الله — حي الطيرة', '2026-11-24', '2026-11-23 08:45:00', 'المكيف لا يبرد بشكل جيد ويصدر صوتاً.');

INSERT INTO contact_message (message_id, customer_id, subject, message, date_created, status) VALUES
(1, 4, 'طلب عرض سعر لتكييف مبنى إداري',    'نرغب بعرض سعر لتكييف مبنى من 4 طوابق.', '2026-11-23 11:00:00', 'unread'),
(2, 5, 'استفسار عن توفر مكيف ميديا 48000', 'متى يتوفر المكيف الأرضي ميديا 48000؟',  '2026-11-22 14:20:00', 'replied');

INSERT INTO visit (visit_id, request_id, technician_id, scheduled_date, arrival_time, notes) VALUES
(1, 2039, 1, '2026-11-21', '09:00:00', 'تم تركيب الوحدة الخارجية وتوصيل الدكت.'),
(2, 2040, 2, '2026-11-24', NULL, 'بانتظار وصول الفني.');

INSERT INTO visit_part (visit_id, part_id, quantity) VALUES (1, 2, 2), (1, 1, 1);

INSERT INTO project (project_id, customer_id, title, location, type, start_date, end_date, status) VALUES
(1, 3, 'تكييف مركزي — عيادة الشفاء', 'البيرة — شارع القدس', 'medical', '2026-11-10', '2026-11-21', 'completed'),
(2, 4, 'مبنى الأفق الإداري', 'رام الله — الماصيون', 'commercial', '2026-12-01', NULL, 'planning');

INSERT INTO autocad_plan (plan_id, project_id, file_url, version, approval_date) VALUES
(1, 1, 'uploads/plans/shifa-hvac-v2.dwg', '2.0', '2026-11-08');

INSERT INTO ac_unit (unit_id, project_id, type, model, brand, capacity_btu, energy_rating, install_date) VALUES
(1, 1, 'central_vrf', 'RXYQ10T', 'Daikin', 120000, 'A', '2026-11-21'),
(2, 1, 'cassette', 'UT24F', 'LG', 24000, 'A+', '2026-11-21');

INSERT INTO warranty (warranty_id, unit_id, system_warranty_years, motor_warranty_years, start_date, end_date) VALUES
(1, 1, 3, 5, '2026-11-21', '2029-11-21'),
(2, 2, 2, 5, '2026-11-21', '2028-11-21');

INSERT INTO invoice (invoice_id, request_id, project_id, customer_id, issue_date, amount, status) VALUES
(1, 2039, 1, 3, '2026-11-21', 27400, 'partial'),
(2, 2040, NULL, 2, '2026-11-22', 250, 'unpaid');

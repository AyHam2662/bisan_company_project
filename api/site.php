<?php
require_once dirname(__DIR__) . '/includes/bootstrap.php';

$schema = require ROOT_DIR . '/includes/settings_schema.php';
$site = [];
foreach ($schema as $group) {
    foreach ($group['fields'] as $key => $field) {
        $value = get_setting($key);
        $site[$key] = $field['type'] === 'image' ? file_url($value, url('assets/img/placeholder.svg')) : $value;
    }
}
$site['company_short']   = get_setting('company_short', 'بيسان');
$site['company_tagline'] = get_setting('company_tagline', 'للتكييف والتبريد');
$site['address_note']    = get_setting('address_note', get_setting('address'));
$site['phone_href']      = phone_link($site['phone']);
$site['mobile_href']     = phone_link($site['mobile']);
$site['emergency_href']  = phone_link($site['emergency_phone']);
$site['email_href']      = 'mailto:' . $site['email'];
$site['sales_email_href'] = $site['sales_email'] !== '' ? 'mailto:' . $site['sales_email'] : '';
$site['whatsapp_url']    = $site['whatsapp'] !== '' ? 'https://wa.me/' . preg_replace('/\D/', '', $site['whatsapp']) : '';
$site['map_embed']       = preg_match('~^https://(www\.)?google\.com/maps/embed\?~', $site['map_embed']) ? $site['map_embed'] : '';
$site['founded']         = preg_match('/(19|20)\d{2}/', $site['company_description'], $match) ? $match[0] : '2017';
$site['year']            = date('Y');

send_json(['ok' => true, 'site' => $site, 'csrf' => csrf_token()]);

<?php
/*
Plugin Name: WP BrowserUpdate
Plugin URI: https://wpbu.steinbrecher.co/
Description: This plugin notifies website visitors to update their outdated browser in a non-intrusive way. Visit <a href="https://browserupdate.org/" title="browserupdate.org" target="_blank">browserupdate.org</a> for more information…
Version: 5.1
Author: Marco Steinbrecher
Author URI: https://profiles.wordpress.org/macsteini
Requires at least: 4.6
License: GPLv3 or later
License URI: http://gnu.org/licenses/gpl
*/

if (!defined('ABSPATH')) die();

define('MIN_PHP_VERSION', '7.4');

if (version_compare(PHP_VERSION, MIN_PHP_VERSION, '<')) {
add_action('admin_notices', function () {
echo '<div class="notice notice-error"><p><strong>'.sprintf(esc_html__('Your PHP v%s is outdated: This plugin requires PHP v%s or higher. Please update your PHP version or %s for compatibility with older PHP versions…', 'wp-browser-update'), esc_html(PHP_VERSION), esc_html(MIN_PHP_VERSION), '<a href="https://downloads.wordpress.org/plugin/wp-browser-update.4.8.1.zip" rel="noopener">'.esc_html__('download plugin version 4.8.1', 'wp-browser-update').'</a>').'</strong></p></div>';
});
deactivate_plugins(plugin_basename(__FILE__));
return;
}

function wpbu_fetchurl($url) {
$ch = curl_init($url);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
curl_setopt($ch, CURLOPT_TIMEOUT, 10);
curl_setopt($ch, CURLOPT_USERAGENT, 'Mozilla/5.0 (compatible; MyBrowserVersionBot/1.0)');
$data = curl_exec($ch);
curl_close($ch);
return $data;
}

function wpbu_getversion($url, $xpathQuery, $regex = '/\d+(\.\d+)+/') {
$html = wpbu_fetchurl($url);
if (!$html) return;

libxml_use_internal_errors(true);
$dom = new DOMDocument();
$dom->loadHTML($html);
libxml_clear_errors();

$xpath = new DOMXPath($dom);
$nodes = $xpath->query($xpathQuery);

if ($nodes->length>0) {
$text = $nodes->item(0)->textContent;
if (preg_match($regex, $text, $match)) {
return trim($match[0]);
} else return "Version number not found.";
} else return "Stable release not found.";
}

function wpbu_getversion_cached($url, $xpath, $regex = '/\d+(\.\d+)+/', $hours=6) {
$hours = apply_filters('wpbu_browser_version_cache_hours', $hours, $url, $xpath, $regex);
$key = 'wpbu_'.md5($url.$xpath.$regex);
$version = get_transient($key);
if ($version!==false) return $version;
$version = wpbu_getversion($url, $xpath, $regex);
if (is_string($version) && strlen($version)<255) set_transient($key, $version, $hours*HOUR_IN_SECONDS);
return $version;
}

function wpbu() {
$wpbu_vars = explode(' ', get_option('wp_browserupdate_browsers', '0 0 0 0 0'));
$wpbu_js = explode(' ', get_option('wp_browserupdate_js', '12 false true top true true true true'));
$browser = 'e:'.$wpbu_vars[0].',f:'.$wpbu_vars[1].',o:'.$wpbu_vars[2].',s:'.$wpbu_vars[3].(!isset($wpbu_vars[4])?'':',c:'.$wpbu_vars[4]);
echo '<script>
var $buoop = {required:{e:'.$wpbu_vars[0].',f:'.$wpbu_vars[1].',o:'.$wpbu_vars[2].',s:'.$wpbu_vars[3].(!isset($wpbu_vars[4])?'':',c:'.$wpbu_vars[4]).'},test:'.($wpbu_js[1] ?? '').',newwindow:'.($wpbu_js[2] ?? '').',style:"'.($wpbu_js[3] ?? '').'",insecure:'.($wpbu_js[4] ?? '').',unsupported:'.($wpbu_js[5] ?? '').',mobile:'.($wpbu_js[6] ?? '').',shift_page_down:'.($wpbu_js[7] ?? '').',api:2025.07};

function $buo_f(){
var e = document.createElement("script");
e.src = "//browser-update.org/update.min.js";
document.body.appendChild(e);
};
try {document.addEventListener("DOMContentLoaded", $buo_f, false)}
catch(e){window.attachEvent("onload", $buo_f)}
</script>';
}

function wpbu_administration() {
if (!current_user_can('manage_options')) wp_die(__('You do not have sufficient permissions to access this page.'));

if (isset($_POST['wpbu_submit']) and wp_verify_nonce($_POST['form_nonce'], 'test-nonce')) {
foreach (['wpbu_msie', 'wpbu_firefox', 'wpbu_opera', 'wpbu_safari', 'wpbu_google'] as $field) {
if (isset($_POST[$field])) $_POST[$field] = preg_replace('/[^0-9.\-]/', '', $_POST[$field]);
}

$fields_to_sanitize = ['wpbu_reminder', 'wpbu_testing', 'wpbu_newwindow', 'wpbu_style', 'wpbu_secis', 'wpbu_unsup', 'wpbu_mobile', 'wpbu_shift'];
foreach ($fields_to_sanitize as $field) {
if (isset($_POST[$field])) $_POST[$field] = sanitize_text_field($_POST[$field]);
}

$_POST['wpbu_css_buorg'] = sanitize_textarea_field($_POST['wpbu_css_buorg']);

$browsers = ['msie' => $_POST['wpbu_msie'] ?? '0', 'firefox'=> $_POST['wpbu_firefox'] ?? '0', 'opera'=> $_POST['wpbu_opera'] ?? '0', 'safari' => $_POST['wpbu_safari'] ?? '0', 'google' => $_POST['wpbu_google'] ?? '0'];

$js_settings = [(int) ($_POST['wpbu_reminder'] ?? 12), sanitize_text_field($_POST['wpbu_testing'] ?? 'false'), sanitize_text_field($_POST['wpbu_newwindow'] ?? 'false'), sanitize_text_field($_POST['wpbu_style'] ?? 'top'), sanitize_text_field($_POST['wpbu_secis'] ?? 'false'), sanitize_text_field($_POST['wpbu_unsup'] ?? 'false'), sanitize_text_field($_POST['wpbu_mobile'] ?? 'false'), sanitize_text_field($_POST['wpbu_shift'] ?? 'false')];

update_option('wp_browserupdate_browsers', implode(' ', $browsers));
update_option('wp_browserupdate_js', implode(' ', $js_settings));
update_option('wp_browserupdate_css_buorg', sanitize_textarea_field($_POST['wpbu_css_buorg'] ?? ''));

echo '<div class="updated"><p><strong>'.esc_html__('Settings saved.', 'wp-browser-update').'</strong></p></div>';
}

$wpbu_vars = explode(' ', get_option('wp_browserupdate_browsers', '0 0 0 0 0'));

$browsers = [
'msie' => [
'name' => 'Microsoft Edge',
'selected' => $wpbu_vars[0],
'download' => 'https://microsoft.com/edge',
'url' => 'https://en.wikipedia.org/wiki/Microsoft_Edge',
'xpath' => "//table[contains(@class,'infobox')]//tr[th//a[text()='Stable release(s)']]/following-sibling::tr[1]//table[contains(@class, 'infobox-subbox')]//tr[th[contains(text(),'Windows')]]/td",
],

'firefox' => [
'name' => 'Mozilla Firefox',
'selected' => $wpbu_vars[1],
'download' => 'https://firefox.com/',
'url' => 'https://en.wikipedia.org/wiki/Firefox',
'xpath' => "//table[contains(@class,'infobox')]//tr[th//a[text()='Stable release(s)']]/following-sibling::tr[1]//table[contains(@class, 'infobox-subbox')]//tr[th[text()='Standard']]/td",
'regex' => '/\d+(\.\d+)+/',
],

'opera' => [
'name' => 'Opera',
'selected' => $wpbu_vars[2],
'download' => 'https://opera.com/',
'url' => 'https://en.wikipedia.org/wiki/Opera_(web_browser)',
'xpath' => "//table[contains(@class,'infobox')]//tr[th//a[text()='Stable release']]/td",
],

'safari' => [
'name' => 'Apple Safari',
'selected' => $wpbu_vars[3],
'download' => 'https://support.apple.com/102665',
'url' => 'https://en.wikipedia.org/wiki/Safari_(web_browser)',
'xpath' => "//table[contains(@class,'infobox')]//tr[th//a[text()='Stable release(s)']]/following-sibling::tr[1]//table[contains(@class, 'infobox-subbox')]//tr[th[text()='macOS']]/td",
'regex' => '/\d+(\.\d+)+/',
],

'google' => [
'name' => 'Google Chrome',
'selected' => $wpbu_vars[4],
'download' => 'https://chrome.google.com/',
'url' => 'https://en.wikipedia.org/wiki/Google_Chrome',
'xpath' => "//table[contains(@class,'infobox')]//tr[th//a[text()='Stable release(s)']]/following-sibling::tr[1]//table[contains(@class, 'infobox-subbox')]//tr[th[contains(text(),'Windows')]]/td",
],
];

###
### One-time migration for negative values
###

$needs_migration = false;
foreach (array_keys($browsers) as $i => $key) {
$val = $wpbu_vars[$i] ?? '0';
if (is_numeric($val) && (float)$val < 0) {
$browser = $browsers[$key];
$regex = isset($browser['regex']) ? $browser['regex'] : '/\d+(\.\d+)+/';
$version = wpbu_getversion_cached($browser['url'], $browser['xpath'], $regex);
if (preg_match('/^([\d\.]+)/', $version, $m)) {
$current_version = $m[1];
$parts = explode('.', $current_version);
$diff = abs((int)$val);
$parts[0] = max(0, (int)$parts[0] - $diff);
$newval = implode('.', $parts);
$wpbu_vars[$i] = $newval;
$needs_migration = true;
}
}
}

if ($needs_migration) {
update_option('wp_browserupdate_browsers', implode(' ', $wpbu_vars));
$wpbu_vars = explode(' ', get_option('wp_browserupdate_browsers', '0 0 0 0 0'));
$browser_keys = array_keys($browsers);
foreach ($browser_keys as $i => $key) {
$browsers[$key]['selected'] = $wpbu_vars[$i] ?? '0';
}
}

###
###
###

echo '<div class="wrap"><form action="'.esc_url($_SERVER['REQUEST_URI']).'" method="post"><input type="hidden" name="form_nonce" value="'.esc_attr(wp_create_nonce('test-nonce')).'" /><h1>WP BrowserUpdate</h1><h2>'.esc_html__('Outdated Browser Versions', 'wp-browser-update').'</h2><p>'.esc_html__('Select the browser versions you consider outdated (including all earlier versions). If left unchanged, WP BrowserUpdate will use the default settings.', 'wp-browser-update').'</p><p>'.esc_html__('If you set the browser version to 0, a notification will be shown for every outdated browser version.', 'wp-browser-update').'</p>';

$output = '<table class="form-table">';

foreach ($browsers as $key => $browser) {
$version = '';
if (!empty($browser['url']) && !empty($browser['xpath'])) {
$regex = isset($browser['regex']) ? $browser['regex'] : '/\d+\.\d+\.\d+\.\d+/';
$version = wpbu_getversion_cached($browser['url'], $browser['xpath'], $regex);
}
$output .= '<tr><th scope="row"><label for="wpbu_'.esc_attr($key).'"><a href="'.$browser['download'].'" target="_blank">'.esc_html($browser['name']).'</a></label></th><td><input type="text" pattern="^[0-9.]+$" name="wpbu_'.esc_attr($key).'" id="wpbu_'.esc_attr($key).'" value="'.esc_attr($browser['selected']).'" title="'.__('Only numbers and dots are allowed', 'wp-browser-update').'" size="12" />';
if ($version) $output .= ' <small>'.esc_html__('Latest version', 'wp-browser-update').': '.esc_html($version).'</small>';
$output .= '</td></tr>';
}

echo $output.'</table>';

$wpbu_defaults = ['12', 'false', 'true', 'top', 'true', 'true', 'true', 'true'];
$wpbu_js = explode(' ', get_option('wp_browserupdate_js', implode(' ', $wpbu_defaults)));

$wpbu_keys = ['wpbu_reminder', 'wpbu_testing', 'wpbu_newwindow', 'wpbu_style', 'wpbu_secis', 'wpbu_unsup', 'wpbu_mobile', 'wpbu_shift'];
$wpbu_values = array_combine($wpbu_keys, $wpbu_js);

$select_fields = [
'wpbu_newwindow' => ['label' => __('Open Links in New Tab', 'wp-browser-update'), 'description' => __('Open the notification bar link in a new browser tab or window.', 'wp-browser-update'), 'value' => $wpbu_values['wpbu_newwindow']],
'wpbu_testing' => ['label' => __('Testing Mode', 'wp-browser-update'), 'description' => __('Always display the notification bar (useful for testing).', 'wp-browser-update'), 'value' => $wpbu_values['wpbu_testing']],
'wpbu_style' => ['label' => __('Notification Position', 'wp-browser-update'), 'description' => __('Select where the notification bar should appear on the page.', 'wp-browser-update'), 'value' => $wpbu_values['wpbu_style'], 'options' => ['top' => __('Top', 'wp-browser-update'), 'bottom' => __('Bottom', 'wp-browser-update'), 'corner' => __('Corner', 'wp-browser-update')]],
'wpbu_secis' => ['label' => __('Notify Security Risks', 'wp-browser-update'), 'description' => __('Alert users of all browser versions with serious security vulnerabilities.', 'wp-browser-update'), 'value' => $wpbu_values['wpbu_secis']],
'wpbu_unsup' => ['label' => __('Notify Unsupported Browsers', 'wp-browser-update'), 'description' => __('Include browsers that are no longer supported by their vendor.', 'wp-browser-update'), 'value' => $wpbu_values['wpbu_unsup']],
'wpbu_mobile' => ['label' => __('Notify Mobile Browsers', 'wp-browser-update'), 'description' => __('Enable notifications for mobile browsers.', 'wp-browser-update'), 'value' => $wpbu_values['wpbu_mobile']],
'wpbu_shift' => ['label' => __('Prevent Content Overlap', 'wp-browser-update'), 'description' => __('Adjust the page layout to avoid content being obscured by the notification bar (adds margin-top to the body tag).', 'wp-browser-update'), 'value' => $wpbu_values['wpbu_shift']]
];

$wpbu_reminder_field = ['label' => __('Reappearance Interval', 'wp-browser-update'), 'description' => __('How many hours before the message should reappear (0 = Always show)?', 'wp-browser-update'), 'value' => $wpbu_values['wpbu_reminder'], 'type' => 'number', 'min' => 0, 'max' => 99, 'step' => 1];

echo '<h2>'.esc_html__('Script Customizations', 'wp-browser-update').'</h2><p><label for="wpbu_reminder"><strong>'.esc_html__('Reappearance Interval', 'wp-browser-update').':</strong></label><br><input type="number" value="'.esc_attr($wpbu_reminder_field['value']).'" id="wpbu_reminder" name="wpbu_reminder" min="'.esc_attr($wpbu_reminder_field['min']).'" max="'.esc_attr($wpbu_reminder_field['max']).'" step="'.esc_attr($wpbu_reminder_field['step']).'" required placeholder="'.esc_attr($wpbu_reminder_field['description']).'"><br>'.esc_html__('How many hours before the message should reappear (0 = Always show)?', 'wp-browser-update').'</p>';

foreach ($select_fields as $name => $field) {
echo '<p><label for="'.esc_attr($name).'"><strong>'.esc_html($field['label']).':</strong></label><br><select id="'.esc_attr($name).'" name="'.esc_attr($name).'">';

if (!empty($field['options'])) {
foreach ($field['options'] as $key => $label) {
$selected = ($field['value'] === $key) ? ' selected="selected"' : '';
echo '<option value="'.esc_attr($key).'"'.$selected.'>'.esc_html($label).'</option>';
}
} else echo '<option value="true"'.($field['value'] === 'true' ? ' selected="selected"' : '').'>'.esc_html__('Yes', 'wp-browser-update').'</option><option value="false"'.($field['value'] === 'false' ? ' selected="selected"' : '').'>'.esc_html__('No', 'wp-browser-update').'</option>';

echo '</select><br>'.esc_html($field['description']).'</p>';
}

$wpbu_css_buorg = get_option('wp_browserupdate_css_buorg', '');

echo '<p><label for="wpbu_css_buorg"><strong>'.esc_html__('Custom CSS', 'wp-browser-update').':</strong></label><br><textarea id="wpbu_css_buorg" name="wpbu_css_buorg" rows="15" cols="45">'.esc_textarea($wpbu_css_buorg).'</textarea><br>'.sprintf(esc_html__('Override the default CSS with your own rules (%sread more%s) – leave blank to use the default.', 'wp-browser-update'), '<a href="https://browserupdate.org/customize.html" target="_blank">', '</a>').'</p><p class="submit"><input type="submit" name="wpbu_submit" id="submit" class="button button-primary" value="'.esc_html__('Update Settings', 'wp-browser-update').'" /></p></form></div>';
}

function wpbu_css() {
$wpbu_css_buorg = get_option('wp_browserupdate_css_buorg', '');
if (!empty($wpbu_css_buorg)) echo "<style>".$wpbu_css_buorg."\n\n</style>";
}

function wpbu_admin() {
add_options_page('WP BrowserUpdate', 'WP BrowserUpdate', 'manage_options', 'wp-browserupdate', 'wpbu_administration');
}

function wpbu_settings_link($links) {
return array_merge(array('settings' => '<a href="'.admin_url('options-general.php?page=wp-browserupdate').'">'.esc_html__('Settings').'</a>'), $links);
}

function wpbu_activation() {
}

function wpbu_plugin_links($links, $file) {
if ($file===plugin_basename(__FILE__)) $links[] = '<a target="_blank" rel="noopener noreferrer" href="https://wordpress.org/support/plugin/wp-browser-update" title="'.esc_html__('Get help', 'wp-browser-update').'">'.esc_html__('Support', 'wp-browser-update').'</a> | <a target="_blank" href="https://wpbu.steinbrecher.co/" title="'.esc_html__('Plugin Homepage', 'wp-browser-update').'">'.esc_html__('Plugin Homepage', 'wp-browser-update').'</a> | <a target="_blank" rel="noopener noreferrer" href="https://wordpress.org/support/plugin/wp-browser-update/reviews/#new-post" title="'.esc_attr__('Rate this plugin. Thanks for your support!', 'wp-browser-update').'">'.esc_html__('Rate this plugin', 'wp-browser-update').'</a>';
return $links;
}

register_activation_hook(__FILE__, 'wpbu_activation');
add_filter('plugin_action_links_'.basename(dirname(__FILE__)).'/'.basename(__FILE__), 'wpbu_settings_link');
add_filter('plugin_row_meta', 'wpbu_plugin_links', 10, 2);
add_action('wp_footer', 'wpbu');
add_action('wp_head', 'wpbu_css');
add_action('admin_menu', 'wpbu_admin');

<?php
/*
Plugin Name: WP BrowserUpdate
Plugin URI: https://wpbu.codyhq.com/
Description: This plugin informs website visitors to update their outdated browser in an unobtrusive way. Go to <a href="http://browserupdate.org/" title="browserupdate.org" target="_blank">browserupdate.org</a> for more information…
Version: 4.6.8
Author: Marco Steinbrecher
Author URI: http://profiles.wordpress.org/macsteini
Requires at least: 4.6
License: GPLv3 or later
License URI: http://gnu.org/licenses/gpl
*/

if (!defined('ABSPATH')) die();

function wpbu() {
$wpbu_vars = explode(' ', get_option('wp_browserupdate_browsers', '0 0 0 0 0'));
$wpbu_js = explode(' ', get_option('wp_browserupdate_js', '12 false true top true true true true'));
$browser = 'e:'.$wpbu_vars[0].',f:'.$wpbu_vars[1].',o:'.$wpbu_vars[2].',s:'.$wpbu_vars[3].(!isset($wpbu_vars[4])?'':',c:'.$wpbu_vars[4]);

echo '<script type="text/javascript">
var $buoop = {required:{'.$browser.'},test:'.(isset($wpbu_js[1]) ? $wpbu_js[1] : '').',newwindow:'.(isset($wpbu_js[2]) ? $wpbu_js[2] : '').',style:"'.(isset($wpbu_js[3]) ? $wpbu_js[3] : '').'",insecure:'.(isset($wpbu_js[4]) ? $wpbu_js[4] : '').',unsupported:'.(isset($wpbu_js[5]) ? $wpbu_js[5] : '').',mobile:'.(isset($wpbu_js[6]) ? $wpbu_js[6] : '').',shift_page_down:'.(isset($wpbu_js[7]) ? $wpbu_js[7] : '').',api:2024.10};

function $buo_f(){
var e = document.createElement("script");
e.src = "//browserupdate.org/update.min.js";
document.body.appendChild(e);
};
try {document.addEventListener("DOMContentLoaded", $buo_f, false)}
catch(e){window.attachEvent("onload", $buo_f)}
</script>';
}

function wpbu_administration() {
if (isset($_POST['wpbu_submit']) and wp_verify_nonce($_POST['form_nonce'], 'test-nonce')) {
$_POST['wpbu_msie'] = sanitize_text_field($_POST['wpbu_msie']);
$_POST['wpbu_firefox'] = sanitize_text_field($_POST['wpbu_firefox']);
$_POST['wpbu_opera'] = sanitize_text_field($_POST['wpbu_opera']);
$_POST['wpbu_safari'] = sanitize_text_field($_POST['wpbu_safari']);
$_POST['wpbu_google'] = sanitize_text_field($_POST['wpbu_google']);

$_POST['wpbu_reminder'] = sanitize_text_field($_POST['wpbu_reminder']);
$_POST['wpbu_testing'] = sanitize_text_field($_POST['wpbu_testing']);
$_POST['wpbu_newwindow'] = sanitize_text_field($_POST['wpbu_newwindow']);
$_POST['wpbu_style'] = sanitize_text_field($_POST['wpbu_style']);
$_POST['wpbu_secis'] = sanitize_text_field($_POST['wpbu_secis']);
$_POST['wpbu_unsup'] = sanitize_text_field($_POST['wpbu_unsup']);
$_POST['wpbu_mobile'] = sanitize_text_field($_POST['wpbu_mobile']);
$_POST['wpbu_shift'] = sanitize_text_field($_POST['wpbu_shift']);

$_POST['wpbu_css_buorg'] = sanitize_textarea_field($_POST['wpbu_css_buorg']);

update_option('wp_browserupdate_browsers', $_POST['wpbu_msie'].' '.$_POST['wpbu_firefox'].' '.$_POST['wpbu_opera'].' '.$_POST['wpbu_safari'].' '.$_POST['wpbu_google']);
update_option('wp_browserupdate_js', (int)$_POST['wpbu_reminder'].' '.$_POST['wpbu_testing'].' '.$_POST['wpbu_newwindow'].' '.$_POST['wpbu_style'].' '.(empty($_POST['wpbu_secis']) ? 'false' : $_POST['wpbu_secis']).' '.(empty($_POST['wpbu_unsup']) ? 'false' : $_POST['wpbu_unsup']).' '.(empty($_POST['wpbu_mobile']) ? 'false' : $_POST['wpbu_mobile']).' '.(empty($_POST['wpbu_shift']) ? 'false' : $_POST['wpbu_shift']));
update_option('wp_browserupdate_css_buorg', $_POST['wpbu_css_buorg']);
echo '<div class="updated"><p><strong>'.__('Settings saved.', 'wp-browser-update').'</strong></p></div>';
unset($_POST['form_nonce']);
unset($_POST['wpbu_submit']);
}

$morethan = [
['0', __('Every outdated version', 'wp-browser-update')],
['-5', __('More than five versions behind', 'wp-browser-update')],
['-4', __('More than four versions behind', 'wp-browser-update')],
['-3', __('More than three versions behind', 'wp-browser-update')],
['-2', __('More than two versions behind', 'wp-browser-update')],
['-1', __('More than one version behind', 'wp-browser-update')]
];

$wpbu_vars = explode(' ', get_option('wp_browserupdate_browsers', '0 0 0 0 0'));
$msie = $wpbu_vars[0];
$firefox = $wpbu_vars[1];
$opera = $wpbu_vars[2];
$safari = $wpbu_vars[3];
$google = empty($wpbu_vars[4]) ? '' : $wpbu_vars[4];

$wpbu_js = explode(' ', get_option('wp_browserupdate_js', '12 false true top true true true true'));
$wpbu_reminder = $wpbu_js[0];
$wpbu_testing = $wpbu_js[1];
$wpbu_newwindow = $wpbu_js[2];
$wpbu_style = $wpbu_js[3];
$wpbu_secis = $wpbu_js[4];
$wpbu_unsup = $wpbu_js[5];
$wpbu_mobile = $wpbu_js[6];
$wpbu_shift = $wpbu_js[7];

$wpbu_css_buorg = get_option('wp_browserupdate_css_buorg', '');

$msie_vers = array_merge($morethan, [[127, '<=127'], [120, '<=120'], [110, '<=110'], [100, '<=100'], [90, '<=90']]);
$firefox_vers = array_merge($morethan, [[131, '<=131'], [100, '<=100'], [90, '<=90'], [80, '<=80'], [70, '<=70']]);
$opera_vers = array_merge($morethan, [[114, '<=114'], [85, '<=85'], [75, '<=75'], [65, '<=65'], [55, '<=55']]);
$safari_vers = array_merge($morethan, [[18, '<=18'], [17, '<=17'], [16, '<=16'], [15, '<=15'], [14, '<=14']]);
$google_vers = array_merge($morethan, [[130, '<=130'], [120, '<=120'], [110, '<=110'], [100, '<=100'], [90, '<=90']]);

echo '<div class="wrap"><form action="'.$_SERVER['REQUEST_URI'].'" method="post"><input name="form_nonce" type="hidden" value="'.wp_create_nonce('test-nonce').'" /><h1>WP BrowserUpdate</h1><h2>'.__('Outdated Browser Versions', 'wp-browser-update').'</h2><p>'.__('Please choose which browser version you consider to be outdated (of course, this will include all versions below)… If you leave as is, WP BrowserUpdate uses the default values.', 'wp-browser-update').'</p><p>Microsoft IE/Edge: <select name="wpbu_msie">';

for ($x=0; $x<count($msie_vers); $x++) echo '<option value="'.$msie_vers[$x][0].'"'.($msie==$msie_vers[$x][0] ? ' selected="selected"' : '').'>'.$msie_vers[$x][1].'</option>';

echo '</select> <a href="http://microsoft.com/download/internet-explorer.aspx" title="'.__('Download', 'wp-browser-update').'" target="_blank">'.__('Download', 'wp-browser-update').'</a></p><p>Mozilla Firefox: <select name="wpbu_firefox">';

for ($x=0; $x<count($firefox_vers); $x++) echo '<option value="'.$firefox_vers[$x][0].'"'.($firefox==$firefox_vers[$x][0] ? ' selected="selected"' : '').'>'.$firefox_vers[$x][1].'</option>';

echo '</select> <a href="http://mozilla.org/firefox" title="'.__('Download', 'wp-browser-update').'" target="_blank">'.__('Download', 'wp-browser-update').'</a></p><p>Opera: <select name="wpbu_opera">';

for ($x=0; $x<count($opera_vers); $x++) echo '<option value="'.$opera_vers[$x][0].'"'.($opera==$opera_vers[$x][0] ? ' selected="selected"' : '').'>'.$opera_vers[$x][1].'</option>';

echo '</select> <a href="http://opera.com/" title="'.__('Download', 'wp-browser-update').'" target="_blank">'.__('Download', 'wp-browser-update').'</a></p><p>Apple Safari: <select name="wpbu_safari">';

for ($x=0; $x<count($safari_vers); $x++) echo '<option value="'.$safari_vers[$x][0].'"'.($safari==$safari_vers[$x][0] ? ' selected="selected"' : '').'>'.$safari_vers[$x][1].'</option>';

echo '</select> <a href="http://support.apple.com/HT204416" title="'.__('Download', 'wp-browser-update').'" target="_blank">'.__('Download', 'wp-browser-update').'</a></p><p>Google Chrome: <select name="wpbu_google">';

for ($x=0; $x<count($google_vers); $x++) echo '<option value="'.$google_vers[$x][0].'"'.($google==$google_vers[$x][0] ? ' selected="selected"' : '').'>'.$google_vers[$x][1].'</option>';

echo '</select> <a href="http://chrome.google.com/" title="'.__('Download', 'wp-browser-update').'" target="_blank">'.__('Download', 'wp-browser-update').'</a></p><h3>'.__('Script Customizations', 'wp-browser-update').'</h3><p>'.__('After how many hours the message should re-appear (0 = Show all the time):', 'wp-browser-update').'<br /><input type="number" value="'.$wpbu_reminder.'" name="wpbu_reminder" min="0" max="99" step="1" required placeholder="(min: 0, max: 99)" /></p><p>'.__('Open link on notification bar in a new browser window/tab:', 'wp-browser-update').'<br /><select name="wpbu_newwindow"><option value="true"'.($wpbu_newwindow=='true' ? ' selected="selected"' : '').'>'.__('Yes', 'wp-browser-update').'</option><option value="false"'.($wpbu_newwindow=='false' ? ' selected="selected"' : '').'>'.__('No', 'wp-browser-update').'</option></select></p><p>'.__('Always show notification bar (for testing purposes):', 'wp-browser-update').'<br /><select name="wpbu_testing"><option value="true"'.($wpbu_testing=='true' ? ' selected="selected"' : '').'>'.__('Yes', 'wp-browser-update').'</option><option value="false"'.($wpbu_testing=='false' ? ' selected="selected"' : '').'>'.__('No', 'wp-browser-update').'</option></select></p><p>'.__('Position where the notification will be displayed:', 'wp-browser-update').'<br /><select name="wpbu_style"><option value="top"'.($wpbu_style=='top' ? ' selected="selected"' : '').'>'.__('Top', 'wp-browser-update').'</option><option value="bottom"'.($wpbu_style=='bottom' ? ' selected="selected"' : '').'>'.__('Bottom', 'wp-browser-update').'</option><option value="corner"'.($wpbu_style=='corner' ? ' selected="selected"' : '').'>'.__('Corner', 'wp-browser-update').'</option></select></p><p>'.__('Notify all browser versions with severe security issues:', 'wp-browser-update').'<br /><select name="wpbu_secis"><option value="true"'.($wpbu_secis=='true' ? ' selected="selected"' : '').'>'.__('Yes', 'wp-browser-update').'</option><option value="false"'.($wpbu_secis=='false' ? ' selected="selected"' : '').'>'.__('No', 'wp-browser-update').'</option></select></p><p>'.__('Also notify all browsers that are not supported by the vendor anymore:', 'wp-browser-update').'<br /><select name="wpbu_unsup"><option value="true"'.($wpbu_unsup=='true' ? ' selected="selected"' : '').'>'.__('Yes', 'wp-browser-update').'</option><option value="false"'.($wpbu_unsup=='false' ? ' selected="selected"' : '').'>'.__('No', 'wp-browser-update').'</option></select></p><p>'.__('Notify mobile browsers:', 'wp-browser-update').'<br /><select name="wpbu_mobile"><option value="true"'.($wpbu_mobile=='true' ? ' selected="selected"' : '').'>'.__('Yes', 'wp-browser-update').'</option><option value="false"'.($wpbu_mobile=='false' ? ' selected="selected"' : '').'>'.__('No', 'wp-browser-update').'</option></select></p><p>'.__('Shift down the page in order not to obscure content behind the notification bar (adds margin-top to the body tag):', 'wp-browser-update').'<br /><select name="wpbu_shift"><option value="true"'.($wpbu_shift=='true' ? ' selected="selected"' : '').'>'.__('Yes', 'wp-browser-update').'</option><option value="false"'.($wpbu_shift=='false' ? ' selected="selected"' : '').'>'.__('No', 'wp-browser-update').'</option></select></p><h3>'.__('Change the CSS Style', 'wp-browser-update').'</h3><p>'.sprintf(__('You can overwrite the default CSS with your own rules (%sread more%s) – leave empty otherwise:', 'wp-browser-update'), '<a href="http://browser-update.org/customize.html" target="_blank">', "</a>").'</p><p><textarea name="wpbu_css_buorg" rows="15" cols="50" class="large-text code">'.$wpbu_css_buorg.'</textarea></p><p class="submit"><input type="submit" name="wpbu_submit" id="submit" class="button button-primary" value="'.__('Update Settings', 'wp-browser-update').'" /></p></form></div>';
}

function wpbu_css() {
$wpbu_css_buorg = get_option('wp_browserupdate_css_buorg', '');
if (!empty($wpbu_css_buorg)) echo "<style type=\"text/css\">".$wpbu_css_buorg."\r\n</style>";
}

function wpbu_admin() {
add_options_page('WP BrowserUpdate', 'WP BrowserUpdate', 'manage_options', 'wp-browserupdate', 'wpbu_administration');
}

function wpbu_settings_link($links) {
return array_merge(array('settings' => '<a href="'.admin_url('options-general.php?page=wp-browserupdate').'">'.__('Settings').'</a>'), $links);
}

function wpbu_activation() {
}

function wpbu_plugin_links($links, $file) {
if ($file===plugin_basename(__FILE__)) $links[] = '<a target="_blank" rel="noopener noreferrer" href="https://wordpress.org/support/plugin/wp-browser-update" title="'.__('Get help', 'wp-browser-update').'">'.__('Support', 'wp-browser-update').'</a> | <a target="_blank" href="https://codyhq.com/wpbu" title="'.__('Plugin Homepage', 'wp-browser-update').'">'.__('Plugin Homepage', 'wp-browser-update').'</a> | <a target="_blank" rel="noopener noreferrer" href="https://wordpress.org/support/plugin/wp-browser-update/reviews/#new-post" title="'.esc_attr__('Rate this plugin. Thanks for your support!', 'wp-browser-update').'">'.esc_html__('Rate this plugin', 'wp-browser-update').'</a>';
return $links;
}

register_activation_hook(__FILE__, 'wpbu_activation');
add_filter('plugin_action_links_'.basename(dirname(__FILE__)).'/'.basename(__FILE__), 'wpbu_settings_link');
add_filter('plugin_row_meta', 'wpbu_plugin_links', 10, 2);
add_action('wp_footer', 'wpbu');
add_action('wp_head', 'wpbu_css');
add_action('admin_menu', 'wpbu_admin');

?>
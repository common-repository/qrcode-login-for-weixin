<?php 

if ( ! defined( 'ABSPATH' ) )  die( 'Invalid request.' );

add_action('admin_init', 'my_general_section');  
function my_general_section() {  
    add_settings_section( 'qrcode_login_settings_section', '微信二维码登陆', 'qrcode_login_section_options_callback', 'general' );

    add_settings_field( 'wx_appid', '微信服务号AppID', 'qrcode_login_callback', 'general', 'qrcode_login_settings_section',
        array('wx_appid')  
    ); 

    add_settings_field('wx_secret', '微信服务号密匙', 'qrcode_login_callback', 'general', 'qrcode_login_settings_section', 
        array( 'wx_secret')  
    ); 

    add_settings_field('wx_token', '微信服务号Token', 'qrcode_login_callback', 'general', 'qrcode_login_settings_section', 
        array( 'wx_token')  
    ); 

    register_setting('general','wx_appid', 'esc_attr');
    register_setting('general','wx_secret', 'esc_attr');
    register_setting('general','wx_token', 'esc_attr');
}


function qrcode_login_section_options_callback() { // Section Callback
    echo '<p>注意：请务必确认你使用的服务号是认证好的。</p>';
    echo '<p>公众号后台接口配置里的url地址填写为：<span style="color:red">' . home_url('?ql=1');
    echo '</span></p>';  
}

function qrcode_login_callback($args) { 
    $option = get_option($args[0]);
    echo '<input type="text"  class="regular-text" id="'. esc_attr($args[0]) .'" name="'. esc_attr($args[0]) .'" value="'.esc_attr($option).'" />';
}
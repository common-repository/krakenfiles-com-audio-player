<?php
/**
 * Plugin Name: KrakenFiles.com audio player
 * Plugin URI: http://krakenfiles.com
 * Description: Replace all KrakenFiles links to embed audio player
 * Version: 1.0
 * Author: KrakenFiles.com
 * Author URI: http://krakenfiles.com/blog
 * License: GPLv2
 */

/*  Copyright 2018  KrakenFiles.com

    This program is free software; you can redistribute it and/or modify
    it under the terms of the GNU General Public License, version 2, as
    published by the Free Software Foundation.

    This program is distributed in the hope that it will be useful,
    but WITHOUT ANY WARRANTY; without even the implied warranty of
    MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
    GNU General Public License for more details.

    You should have received a copy of the GNU General Public License
    along with this program; if not, write to the Free Software
    Foundation, Inc., 51 Franklin St, Fifth Floor, Boston, MA  02110-1301  USA
*/

// Activation
register_activation_hook(__FILE__, 'kraken_set_up_options');

// Uninstall
register_uninstall_hook(__FILE__, 'kraken_delete_options');

// Set default kraken values
function kraken_set_up_options()
{
    add_option('krakendownbutt', 'above');
    add_option('krakenauto', '0');
    add_option('krakenwidth', '600');
}

// Cleaning after uninstall
function kraken_delete_options()
{
    delete_option('krakendownbutt');
    delete_option('krakenauto');
    delete_option('krakenwidth');
}

function kraken_replace_links_to_embed($the_content)
{
    preg_match_all("#https:\/\/krakenfiles.com\/view\/([a-zA-Z0-9]*)\/file.html#", $the_content, $matches, PREG_SET_ORDER);

    if ($matches) {

        $krakendownbutt = esc_attr(get_option('krakendownbutt', 'above'));
        $krakendownimg = plugins_url('/images/download_button.png', __FILE__);
        $krakenwidth = esc_attr(get_option('krakenwidth', '600'));

        if (esc_attr(get_option('krakenauto', '0')) === '1') {
            $krakenauto = 'true';
        } else {
            $krakenauto = 'false';
        }

        foreach ($matches as $data) {
            // Understanding the $data
            $krakenlink = esc_attr($data[0]);
            $krakenfile = esc_attr($data[1]);

            $dataJson = wp_remote_get('https://krakenfiles.com/json/' . $krakenfile);
            $infoData = json_decode($dataJson['body'], true);

            if($infoData['type'] != 'music') {
                return $the_content;
            }

	    if(!$infoData['hash']) {
	 	    $infoData['hash'] = 'null';
            }

            if ($krakendownbutt === 'above') {
                $the_content = str_replace($krakenlink, '<div style="text-align:center;"><a href="' . $krakenlink . '" title="Download ' . $infoData['title'] . '"><img align="middle" style="margin-left:auto; margin-right:auto;" src="' . $krakendownimg . '" /></a></div><br />'
                    . '<script type="text/javascript">var hash="' . $infoData['hash'] . '"; var date="' . $infoData['uploadDate'] . '"; var server ="' . $infoData['server'] . '"; var autoPlay="' . $krakenauto . '";  var width="' . $krakenwidth . '";</script><script type="text/javascript" src="//krakenfiles.com/js/player/embed.js"></script>', $the_content);
            } // Button under
            elseif ($krakendownbutt === 'under') {
                $the_content = str_replace($krakenlink, '<script type="text/javascript">var hash="' . $infoData['hash'] . '"; var date="' . $infoData['uploadDate'] . '"; var server ="' . $infoData['server'] . '"; var autoPlay="' . $krakenauto . '";  var width="' . $krakenwidth . '";</script><script type="text/javascript" src="//krakenfiles.com/js/player/embed.js"></script>'
                    . '<div style="text-align:center;"><a href="' . $krakenlink . '" title="Download ' . $infoData['title'] . '"><img align="middle" style="margin-left:auto; margin-right:auto;" src="' . $krakendownimg . '" /></a></div>', $the_content);
            } // No download button
            else {
                $the_content = str_replace($krakenlink, '<script type="text/javascript">var hash="' . $infoData['hash'] . '"; var date="' . $infoData['uploadDate'] . '"; var server ="' . $infoData['server'] . '"; var autoPlay="' . $krakenauto . '";  var width="' . $krakenwidth . '";</script><script type="text/javascript" src="//krakenfiles.com/js/player/embed.js"></script>', $the_content);
            }
        }
    }

    // Return changed or unchanged content
    return $the_content;
}

// Activate plugin when we see the_content
add_action('the_content', 'kraken_replace_links_to_embed');

function kraken_create_menu()
{
    // Create new options page
    add_options_page('KrakenFiles.com audio player options', 'KrakenFiles.com audio player', 'administrator', __FILE__, 'kraken_settings_page');

    // Call register settings function
    add_action('admin_init', 'kraken_register_settings');
}

add_action('admin_menu', 'kraken_create_menu');

function kraken_register_settings()
{
    register_setting('kraken-settings-group', 'krakendownbutt');
    register_setting('kraken-settings-group', 'krakenauto');
    register_setting('kraken-settings-group', 'krakenwidth');
}

//Localization
function kraken_translations_init()
{
    load_plugin_textdomain('krakenfiles-embed-audio', false, basename(dirname(__FILE__)) . '/languages');
}

add_action('init', 'kraken_translations_init');

function kraken_settings_page()
{
    ?>
    <div class="wrap">
        <h2>KrakenFiles Embed audio player</h2>

        <form method="post" action="options.php">
            <?php
            settings_fields('kraken-settings-group');
            do_settings_sections('kraken-settings-group');

            $krakendownbutt = esc_attr(get_option('krakendownbutt', 'above'));
            ?>
            <table class="form-table">
                <tr valign="top">
                    <th scope="row"><?php echo __('Download Button', 'krakenfiles-embed-audio'); ?></th>
                    <td><select name="krakendownbutt">
                            <option value="none" <?php selected($krakendownbutt, 'none'); ?> ><?php echo __('None', 'krakenfiles-embed-audio'); ?></option>
                            <option value="above" <?php selected($krakendownbutt, 'above'); ?> ><?php echo __('Above', 'krakenfiles-embed-audio'); ?></option>
                            <option value="under" <?php selected($krakendownbutt, 'under'); ?> ><?php echo __('Under', 'krakenfiles-embed-audio'); ?></option>
                        </select></td>
                </tr>

                <tr valign="top">
                    <th scope="row"><?php echo __('Autoplay', 'krakenfiles-embed-audio'); ?></th>
                    <td><input type="checkbox" name="krakenauto"
                               value="1" <?php checked('1', get_option('krakenauto', '0')); ?> /></td>
                </tr>

                <tr valign="top">
                    <th scope="row"><?php echo __('Width', 'krakenfiles-embed-audio'); ?></th>
                    <td><input type="number" min="60" name="krakenwidth"
                               value="<?php echo esc_attr(get_option('krakenwidth', '600')); ?>" required/> px
                    </td>
                </tr>

            </table>

            <?php

            // Security
            wp_nonce_field('kraken_form_check', 'kraken_check');

            // Compatibility check
            if (get_bloginfo('version') >= 3.1) {
                submit_button();
            } else { ?>
                <p class="submit"><input type="submit" name="submit" id="submit" class="button button-primary"
                                         value="<?php echo __('Save Changes', 'krakenfiles-embed-audio'); ?>"/></p>
            <?php } ?>

        </form>

        <?php echo __('Plugin created by', 'krakenfiles-embed-audio'); ?> <a href="http://krakenfiles.com">KrakenFiles.com</a>
        <br/>
    </div>
    <?php
}

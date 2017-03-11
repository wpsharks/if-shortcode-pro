<?php
/**
 * Template.
 *
 * @author @jaswsinc
 * @copyright WP Sharks™
 */
declare(strict_types=1);
namespace WebSharks\WpSharks\IfShortcode\Pro;

use WebSharks\WpSharks\IfShortcode\Pro\Classes;
use WebSharks\WpSharks\IfShortcode\Pro\Interfaces;
use WebSharks\WpSharks\IfShortcode\Pro\Traits;
#
use WebSharks\WpSharks\IfShortcode\Pro\Classes\AppFacades as a;
use WebSharks\WpSharks\IfShortcode\Pro\Classes\SCoreFacades as s;
use WebSharks\WpSharks\IfShortcode\Pro\Classes\CoreFacades as c;
#
use WebSharks\WpSharks\Core\Classes as SCoreClasses;
use WebSharks\WpSharks\Core\Interfaces as SCoreInterfaces;
use WebSharks\WpSharks\Core\Traits as SCoreTraits;
#
use WebSharks\Core\WpSharksCore\Classes as CoreClasses;
use WebSharks\Core\WpSharksCore\Classes\Core\Base\Exception;
use WebSharks\Core\WpSharksCore\Interfaces as CoreInterfaces;
use WebSharks\Core\WpSharksCore\Traits as CoreTraits;
#
use function assert as debug;
use function get_defined_vars as vars;

if (!defined('WPINC')) {
    exit('Do NOT access this file directly.');
}
$Form = $this->s::menuPageForm('§save-options');
?>
<?= $Form->openTag(); ?>
    <?= $Form->openTable(
        __('General Options', 'if-shortcode'),
        sprintf(__('Browse the <a href="%1$s" target="_blank">knowledge base</a> to learn more about these options.', 'if-shortcode'), esc_url(s::brandUrl('/kb')))
    ); ?>

        <?php if ($this->Wp->is_multisite) : ?>
            <?= $Form->selectRow([
                'label' => __('Enable Multisite <code>_for_blog=""</code> Attribute?', 'if-shortcode'),
                'tip'   => __('Allows cross-blog conditionals via <code>current_user_can=""</code>', 'if-shortcode'),

                'name'    => 'enable_for_blog_att',
                'value'   => s::getOption('enable_for_blog_att'),
                'options' => [
                    '0' => __('No', 'if-shortcode'),
                    '1' => __('Yes', 'if-shortcode'),
                ],
            ]); ?>
        <?php endif; ?>

        <?= $Form->selectRow([
            'label' => __('Enable Arbitrary Attributes?', 'if-shortcode'),
            'tip'   => __('In addition to Attributes that come with the [if] Shortcode already, this allows any PHP function to automatically become a Shortcode Attribute too, which is quite handy.', 'if-shortcode'),

            'name'    => 'enable_arbitrary_atts',
            'value'   => s::getOption('enable_arbitrary_atts'),
            'options' => [
                '0' => __('No', 'if-shortcode'),
                '1' => __('Yes', 'if-shortcode'),
            ],
        ]); ?>

        <?= $Form->textareaRow([
            'label' => __('Arbitrary Attribute Whitelist', 'if-shortcode'),
            'tip'   => __('If you enable Arbitrary Attributes, you can make them more secure by providing a whitelist.<hr />In other words, instead of allowing <em>any</em> PHP function to become an Attribute, allow only those you list here.<hr />Separate them with a space, comma, or line break.', 'if-shortcode'),

            'name'    => 'whitelisted_arbitrary_atts',
            'value'   => s::getOption('whitelisted_arbitrary_atts'),
        ]); ?>

        <?= $Form->selectRow([
            'label' => __('Enable <code>php=""</code> Attribute?', 'if-shortcode'),
            'tip'   => __('This allows raw PHP code to be used as an [if] condition.', 'if-shortcode'),

            'name'    => 'enable_php_att',
            'value'   => s::getOption('enable_php_att'),
            'options' => [
                '0' => __('No', 'if-shortcode'),
                '1' => __('Yes', 'if-shortcode'),
            ],
        ]); ?>

        <?= $Form->selectRow([
            'label' => __('Content Filters', 'if-shortcode'),
            'tip'   => __('This controls which built-in WordPress content filters are applied to content inside [if] shortcodes. Selecting all content filters is suggested.<hr />Note: <code>wp-markdown-extra</code> is only possible if you have WP Markdown Extra installed.<hr /><code>jetpack-markdown</code> is only possible if you have Jetpack installed with Markdown enabled. The same is true for <code>jetpack-latex</code>.', 'if-shortcode'),

            'name'     => 'content_filters',
            'multiple' => true, // i.e., An array.
            'value'    => s::getOption('content_filters'),
            'options'  => [
                'wp-markdown-extra'                 => 'wp-markdown-extra',
                'jetpack-markdown'                  => 'jetpack-markdown',
                'jetpack-latex'                     => 'jetpack-latex',
                'wptexturize'                       => 'wptexturize',
                'wpautop'                           => 'wpautop',
                'shortcode_unautop'                 => 'shortcode_unautop',
                'wp_make_content_images_responsive' => 'wp_make_content_images_responsive',
                'capital_P_dangit'                  => 'capital_P_dangit',
                'do_shortcode'                      => 'do_shortcode',
                'convert_smilies'                   => 'convert_smilies',
            ],
        ]); ?>

        <?= $Form->selectRow([
            'label' => __('<code>_debug=""</code> Default Value', 'if-shortcode'),
            'tip'   => __('When <code>_debug="true"</code> and there is a problem with your syntax, an error is displayed.<hr /><code>_debug="verbose"</code> displays additional details to help you diagnose problems.<hr />This setting controls the default value for this attribute.', 'if-shortcode'),

            'name'    => 'debug_att_default',
            'value'   => s::getOption('debug_att_default'),
            'options' => [
                '0' => __('false', 'if-shortcode'),
                '1' => __('true', 'if-shortcode'),
            ],
        ]); ?>

    <?= $Form->closeTable(); ?>
    <?= $Form->submitButton(); ?>
<?= $Form->closeTag(); ?>

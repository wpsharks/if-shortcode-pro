<?php
declare (strict_types = 1);
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

$Form = $this->s::menuPageForm('§save-options');
?>
<?= $Form->openTag(); ?>
    <?= $Form->openTable(
        __('General Shortcode Options'),
        sprintf(__('Browse the <a href="%1$s" target="_blank">knowledge base</a> to learn more about these options.'), esc_url(s::brandUrl('/kb')))
    ); ?>

        <?php if ($this->Wp->is_multisite) : ?>
            <?= $Form->selectRow([
                'label' => __('Enable Multisite <code>for_blog=""</code> Attribute?'),
                'tip'   => __('Allows cross-blog conditionals when used together with current_user_can="".'),

                'name'    => 'enable_for_blog_att',
                'value'   => s::getOption('enable_for_blog_att'),
                'options' => [
                    '0' => __('No'),
                    '1' => __('Yes'),
                ],
            ]); ?>
        <?php endif; ?>

        <?= $Form->selectRow([
            'label' => __('Enable Arbitrary Attributes?'),
            'tip'   => __('This allows any PHP function to automatically become a shortcode attribute.'),

            'name'    => 'enable_arbitrary_atts',
            'value'   => s::getOption('enable_arbitrary_atts'),
            'options' => [
                '0' => __('No'),
                '1' => __('Yes'),
            ],
        ]); ?>

        <?= $Form->textareaRow([
            'label' => __('Arbitrary Attribute Whitelist'),
            'tip'   => __('If you enable Arbitrary attributes, you can make them more secure by providing a whitelist. So instead of allowing <em>any</em> PHP function to become an attribute, allow only those you that list here.<hr />Please separate them with a space, comma, or line break.'),

            'name'    => 'whitelisted_arbitrary_atts',
            'value'   => s::getOption('whitelisted_arbitrary_atts'),
            'options' => [
                '0' => __('No'),
                '1' => __('Yes'),
            ],
        ]); ?>

        <?= $Form->selectRow([
            'label' => __('Enable <code>php=""</code> Attribute?'),
            'tip'   => __('This allows raw PHP code to be used as an [if] condition.'),

            'name'    => 'enable_php_att',
            'value'   => s::getOption('enable_php_att'),
            'options' => [
                '0' => __('No'),
                '1' => __('Yes'),
            ],
        ]); ?>

        <?= $Form->selectRow([
            'label' => __('<code>debug=""</code> Default Value'),
            'tip'   => __('When debug="true" and there is an error in your conditional syntax, an error is displayed on the site to make you aware.<hr />When debug="verbose", additional details are displayed to help you diagnose problems.<hr />This setting controls the default value for this attribute.'),

            'name'    => 'debug_att_default',
            'value'   => s::getOption('debug_att_default'),
            'options' => [
                '0'       => __('false'),
                '1'       => __('true'),
                'verbose' => __('verbose'),
            ],
        ]); ?>

    <?= $Form->closeTable(); ?>
    <?= $Form->submitButton(); ?>
<?= $Form->closeTag(); ?>

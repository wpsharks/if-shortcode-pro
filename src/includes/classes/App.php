<?php
declare (strict_types = 1);
namespace WebSharks\WpSharks\IfShortcode\Pro\Classes;

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

/**
 * App class.
 *
 * @since 160707.2545 Initial release.
 */
class App extends SCoreClasses\App
{
    /**
     * Version.
     *
     * @since 160707.2545 Initial release.
     *
     * @type string Version.
     */
    const VERSION = '160716.9756'; //v//

    /**
     * Constructor.
     *
     * @since 160707.2545 Initial release.
     *
     * @param array $instance Instance args.
     */
    public function __construct(array $instance = [])
    {
        $instance_base = [
            '©di' => [
                '©default_rule' => [
                    'new_instances' => [
                    ],
                ],
            ],

            '§specs' => [
                '§type' => 'plugin',
                '§file' => dirname(__FILE__, 4).'/plugin.php',
            ],
            '©brand' => [
                '©acronym' => 'IFSC',
                '©name'    => '[if] Shortcode',

                '©slug' => 'if-shortcode',
                '©var'  => 'if_shortcode',

                '©short_slug' => 'if-sc',
                '©short_var'  => 'if_sc',

                '©text_domain' => 'if-shortcode',
            ],

            '§pro_option_keys' => [
                'enable_php_att',
                'enable_for_blog_att',
                'enable_arbitrary_atts',
                'whitelisted_arbitrary_atts',
            ],
            '§default_options' => [
                'enable_php_att'             => '0',
                'enable_for_blog_att'        => '0',
                'enable_arbitrary_atts'      => '0',
                'whitelisted_arbitrary_atts' => '',
                'debug_att_default'          => '1',
            ],
        ];
        parent::__construct($instance_base, $instance);
    }

    /**
     * Other hook setup handler.
     *
     * @since 160707.2545 Initial release.
     */
    protected function onSetupOtherHooks()
    {
        parent::onSetupOtherHooks();

        // General shortcode-related hooks & filters.

        for ($_i = 0, $if_shortcode_name = $this->Utils->Shortcode->name, $if_shortcode_names = []; $_i < 5; ++$_i) {
            add_shortcode($if_shortcode_names[] = str_repeat('_', $_i).$if_shortcode_name, [$this->Utils->Shortcode, 'onShortcode']);
        } // unset($_i); // Housekeeping.

        add_filter('no_texturize_shortcodes', function (array $shortcodes) use ($if_shortcode_names) {
            return array_merge($shortcodes, $if_shortcode_names);
        }); // See: <http://jas.xyz/24AusB7> for more about this filter.

        add_filter('widget_text', 'do_shortcode'); // Enable shortcodes in widgets.

        // WooCommerce-specific hooks & filters.

        if (defined('WC_VERSION')) {
            add_action('save_post_product', [$this->Utils->WooCommerce, 'onSaveProduct']);
            add_action('save_post_product_variation', [$this->Utils->WooCommerce, 'onSaveProductVariation']);
        }

        // Menu page; i.e., settings.

        if ($this->Wp->is_admin) {
            add_action('admin_menu', [$this->Utils->MenuPage, 'onAdminMenu']);
        }
    }
}

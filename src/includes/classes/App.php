<?php
/**
 * Application.
 *
 * @author @jaswsinc
 * @copyright WP Sharks™
 */
declare(strict_types=1);
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
 * Application.
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
    const VERSION = '170724.11381'; //v//

    /**
     * Constructor.
     *
     * @since 160707.2545 Initial release.
     *
     * @param array $instance Instance args.
     */
    public function __construct(array $instance = [])
    {
        $Core = $GLOBALS[SCoreClasses\App::class];

        $instance_base = [
            '©di' => [
                '©default_rule' => [
                    'new_instances' => [],
                ],
            ],

            '§specs' => [
                '§in_wp'           => false,
                '§is_network_wide' => false,

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
                'content_filters',
            ],
            '§default_options' => [
                'enable_php_att'      => false,
                'enable_for_blog_att' => false,

                'enable_arbitrary_atts'      => false,
                'whitelisted_arbitrary_atts' => '',

                'content_filters' => [
                    'wp-markdown-extra',
                    'jetpack-markdown',
                    'jetpack-latex',
                    'wptexturize',
                    'wpautop',
                    'shortcode_unautop',
                    'wp_make_content_images_responsive',
                    'capital_P_dangit',
                    'do_shortcode',
                    'convert_smilies',
                ],
                'debug_att_default' => false,
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

        # Menu page settings.

        if ($this->Wp->is_admin) {
            add_action('admin_menu', [$this->Utils->MenuPage, 'onAdminMenu']);
        }
        # WooCommerce-specific hooks & filters.

        if ($this->Wp->is_woocommerce_active) {
            add_action('save_post_product', [$this->Utils->WooCommerce, 'onSaveProduct']);
            add_action('save_post_product_variation', [$this->Utils->WooCommerce, 'onSaveProductVariation']);
        }
        # Everything else on `init` w/ a late priority.

        add_action('init', function () {
            # General hooks & filters for the shortcode.

            add_filter('widget_text', 'do_shortcode'); // Shortcodes in widgets.

            for ($_i = 0, $tag_name = $this->Utils->Shortcode->tag_name, $tag_names = []; $_i < 5; ++$_i) {
                add_shortcode($tag_names[] = str_repeat('_', $_i).$tag_name, [$this->Utils->Shortcode, 'onShortcode']);
            } // unset($_i); // Housekeeping.

            add_filter('no_texturize_shortcodes', function (array $shortcodes) use ($tag_names) {
                return array_merge($shortcodes, $tag_names); // Merge `[if]` tag names.
            }); // See: <http://jas.xyz/24AusB7> for more about this filter.

            # Content-related hooks & filters.

            add_filter('the_content', [$this->Utils->Content, 'onTheContentPreserveIfs'], -100);

            // Restore `[if]` shortcodes 'after' content filters like `wpautop()` are done.
            // And, must restore 'before' `do_shortcode()` runs @ default priority of `11`.
            if (is_numeric($do_shortcode_priority = has_filter('the_content', 'do_shortcode'))) {
                add_filter('the_content', [$this->Utils->Content, 'onTheContentRestoreIfs'], (string) ($do_shortcode_priority - .1));
                // NOTE: This will normally translate to `(string)10.9`, which is what we'd like ideally.
                // If WP core forces an integer, it becomes `10`, and that's OK too, in most cases.

                // The trick is that an `(int)10` is the same priority as `wpautop()` and several others.
                // We need to come 'after' those, but 'before' `do_shortcode()` at `11`. So `10.9` is better.

                // If this ends up being `(int)10`, its fine, so long as this filter is added 'after' others.
                // To help make this more likely, this entire setup runs on `init` w/ a late priority.
            } else {
                add_filter('the_content', [$this->Utils->Content, 'onTheContentRestoreIfs'], '10.9');
            }
            # Content-related hooks & filters inside shortcode.

            $content_filters = s::getOption('content_filters'); // By site owner.

            s::addFilter('content', [$this->Utils->Shortcode, 'onContentForceNestedIfBlocks'], -10000);

            if (in_array('wp-markdown-extra', $content_filters, true) && s::canWpMdExtra()) {
                s::addFilter('content', c::class.'::stripLeadingIndents', -10000);
                s::addFilter('content', s::class.'::wpMdExtra', -10000);
            }
            if (in_array('jetpack-markdown', $content_filters, true) && s::jetpackCanMarkdown()) {
                s::addFilter('content', c::class.'::stripLeadingIndents', -10000);
                s::addFilter('content', s::class.'::jetpackMarkdown', -10000);
            }
            if (in_array('jetpack-latex', $content_filters, true) && s::jetpackCanLatex()) {
                s::addFilter('content', 'latex_markup', 9);
            }
            if (in_array('wptexturize', $content_filters, true)) {
                s::addFilter('content', 'wptexturize', 10);
            }
            if (in_array('wpautop', $content_filters, true)) {
                s::addFilter('content', 'wpautop', 10);
            }
            if (in_array('shortcode_unautop', $content_filters, true)) {
                s::addFilter('content', 'shortcode_unautop', 10);
            }
            if (in_array('wp_make_content_images_responsive', $content_filters, true)) {
                s::addFilter('content', 'wp_make_content_images_responsive', 10);
            }
            if (in_array('capital_P_dangit', $content_filters, true)) {
                s::addFilter('content', 'capital_P_dangit', 11);
            }
            if (in_array('do_shortcode', $content_filters, true)) {
                s::addFilter('content', 'do_shortcode', 11);
            }
            if (in_array('convert_smilies', $content_filters, true)) {
                s::addFilter('content', 'convert_smilies', 20);
            }
        }, 100);
    }
}

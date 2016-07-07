<?php
declare (strict_types = 1);
namespace WebSharks\WpSharks\IfShortcode\Pro\Classes\Utils;

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
 * Shortcode handler.
 *
 * @since 160707.2545 Initial release.
 */
class Shortcode extends SCoreClasses\SCore\Base\Core
{
    /**
     * Shortcode [if] name.
     *
     * @since 160707.2545 Initial release.
     *
     * @param string Shortcode [if] name.
     */
    public $name;

    /**
     * Shortcode [else] name.
     *
     * @since 160707.2545 Initial release.
     *
     * @param string Shortcode [else] name.
     */
    public $else_name;

    /**
     * Class constructor.
     *
     * @since 160707.2545 Initial release.
     *
     * @param Classes\App $App Instance.
     */
    public function __construct(Classes\App $App)
    {
        parent::__construct($App);

        $this->name      = s::applyFilters('name', 'if');
        $this->else_name = s::applyFilters('else_name', 'else');
    }

    /**
     * `[if /]` shortcode.
     *
     * @since 160707.2545 Initial release.
     *
     * @param array       $atts      Shortcode attributes.
     * @param string|null $content   Shortcode content.
     * @param string      $shortcode Shortcode name.
     */
    public function onShortcode(array $atts, $content, string $shortcode): string
    {
        $default_atts = [
            'expr'                => '',
            'is_user_logged_in'   => '',
            'current_user_can'    => '', 'for_blog' => '0',
            'current_user_option' => '', 'current_user_meta' => '',
            'satisfy'             => 'all',
        ];
        $atts    = c::mbTrim($atts);
        $atts    = shortcode_atts($default_atts, $atts, $shortcode);
        $content = (string) $content; // Force string value.

        $atts['expr'] = (string) $atts['expr'];
        $atts['expr'] = $atts['expr'] ? c::unescHtml($atts['expr']) : '';

        $atts['is_user_logged_in'] = (string) $atts['is_user_logged_in'];
        $atts['is_user_logged_in'] = isset($atts['is_user_logged_in'][0]) ? filter_var($atts['is_user_logged_in'], FILTER_VALIDATE_BOOLEAN) : null;

        $atts['current_user_can'] = (string) $atts['current_user_can'];
        $atts['current_user_can'] = $atts['current_user_can'] ? c::unescHtml($atts['current_user_can']) : '';

        $atts['current_user_option'] = (string) $atts['current_user_option'];
        $atts['current_user_option'] = $atts['current_user_option'] ? c::unescHtml($atts['current_user_option']) : '';

        $atts['current_user_meta'] = (string) $atts['current_user_meta'];
        $atts['current_user_meta'] = $atts['current_user_meta'] ? c::unescHtml($atts['current_user_meta']) : '';

        $atts['for_blog'] = (int) $atts['for_blog'];
        $atts['satisfy']  = $atts['satisfy'] === 'any' ? 'any' : 'all';

        $is_multisite    = is_multisite();
        $current_user_id = (int) get_current_user_id();
        $shortcode_depth = strspn($shortcode, '_'); // Based on a zero index.
        $else_tag        = '['.str_repeat('_', $shortcode_depth).$this->else_name.']';
        $conditions      = ''; // Initialize the full set of all conditions built below.
        /*
         * Construct content for if/else conditions.
         */
        if (mb_strpos($content, $else_tag) !== false) {
            list($content_if, $content_else) = explode($else_tag, $content, 2);
            $content_if                      = c::htmlTrim($content_if);
            $content_else                    = c::htmlTrim($content_else);
        } else {
            $content_if   = c::htmlTrim($content);
            $content_else = ''; // Default (empty).
        }
        /*
         * Add conditions from the `expr=""` attribute, if applicable.
         *
         * - [if expr="current_user_can('access_pkg_slug1')" /]
         * - [if expr="current_user_can('access_pkg_slug1') and current_user_can('access_pkg_slug2')" /]
         * - [if expr="current_user_can('access_ccap_one') or get_user_option('meta_key') === 'value'" /]
         */
        if ($atts['expr']) {
            if (!s::getOption('att_expr_enable')) {
                // This is disabled by default. If enabled, a filter can disable it on child sites of a network.
                // add_filter('s2member_x_options', function(array $options) { $options['if_shortcode_expr_enable'] = '0'; return $options; });
                trigger_error(sprintf(__('`[%1$s /]` shortcode attribute `expr=""` not enabled on this site.', 'if-shortcode'), $shortcode), E_USER_ERROR);
                return ''; // Return empty string in case of error handlers allowing this to slide.
            }
            if ($conditions) { // Taken as-is; raw PHP expression.
                $conditions .= ($atts['satisfy'] === 'any' ? ' || ' : ' && ').'('.$atts['expr'].')';
            } else {
                $conditions = '('.$atts['expr'].')';
            }
        }
        /*
         * Add conditions from the `is_user_logged_in=""` attribute, if applicable.
         *
         * - [if is_user_logged_in="true" /]
         * - [if is_user_logged_in="yes" /]
         * - [if is_user_logged_in="false" /], etc.
         */
        if (isset($atts['is_user_logged_in'])) {
            if ($atts['is_user_logged_in'] === false) {
                $_is_user_logged_in_condition = '!is_user_logged_in()';
            } else { // Default behavior is a boolean true.
                $_is_user_logged_in_condition = 'is_user_logged_in()';
            }
            if ($conditions) {
                $conditions .= ($atts['satisfy'] === 'any' ? ' || ' : ' && ').$_is_user_logged_in_condition;
            } else {
                $conditions = $_is_user_logged_in_condition;
            }
            // unset($_is_user_logged_in_condition); // Housekeeping.
        }
        /*
         * Add conditions from the `current_user_can=""` attribute, if applicable.
         *
         * - [if current_user_can="access_pkg_slug1" /]
         * - [if current_user_can="access_pkg_slug1 AND access_pkg_slug2" /]
         * - [if current_user_can="(access_pkg_slug1 AND access_pkg_slug2) OR (access_ccap_one AND access_ccap_two)" /]
         */
        if ($atts['current_user_can']) {
            if (mb_strpos($atts['current_user_can'], "'") !== false) {
                trigger_error(sprintf(__('`[%1$s /]` shortcode attribute `current_user_can="%2$s"` contains apostrophe.', 'if-shortcode'), $shortcode, $atts['current_user_can']), E_USER_ERROR);
                return ''; // Return empty string in case of error handlers allowing this to slide.
            } elseif (!preg_match('/\((?:(?:[^()]+)|(?R))*\)/u', '('.$atts['current_user_can'].')', $_m) || $_m[0] !== '('.$atts['current_user_can'].')') {
                trigger_error(sprintf(__('`[%1$s /]` shortcode attribute `current_user_can="%2$s"` contains unbalanced `()` brackets.', 'if-shortcode'), $shortcode, $atts['current_user_can']), E_USER_ERROR);
                return ''; // Return empty string in case of error handlers allowing this to slide.
            } elseif ($atts['for_blog'] && $is_multisite && !s::getOption('att_for_blog_enable')) {
                // This is disabled by default. If enabled, a filter can disable it on child sites of a network.
                // add_filter('woocommerce_s2member_x_options', function(array $options) { $options['if_shortcode_for_blog_enable'] = '0'; return $options; });
                trigger_error(sprintf(__('`[%1$s /]` shortcode attribute `for_blog=""` not enabled on this site.', 'if-shortcode'), $shortcode), E_USER_ERROR);
                return ''; // Return empty string in case of error handlers allowing this to slide.
            }
            $_current_user_can_conditions = ''; // Initialize conditions.
            $_previous_frag               = ''; // Initialize previous fragment.

            foreach (preg_split('/([()])|\s+/u', $atts['current_user_can'], -1, PREG_SPLIT_DELIM_CAPTURE | PREG_SPLIT_NO_EMPTY) as $_frag) {
                $_space = !$_current_user_can_conditions || !$_previous_frag || $_previous_frag === '(' || $_frag === '(' || $_frag === ')' ? '' : ' ';

                if (in_array($_lc_frag = mb_strtolower($_frag), ['(', ')', 'and', 'or', '&&', '||'], true)) {
                    $_frag = $_lc_frag === 'and' ? '&&' : $_frag;
                    $_frag = $_lc_frag === 'or' ? '||' : $_frag;
                    $_current_user_can_conditions .= $_space.$_frag;
                } elseif ($atts['for_blog'] && $is_multisite) { // Network-only.
                    $_current_user_can_conditions .= $_space.'current_user_can_for_blog('.$atts['for_blog'].', \''.$_frag.'\')';
                } else { // Build the `current_user_can('[cap]')` check.
                    $_current_user_can_conditions .= $_space.'current_user_can(\''.$_frag.'\')';
                }
                $_previous_frag = $_frag; // Previous fragment.
            } // unset($_frag, $_lc_frag, $_previous_frag, $_space); // Housekeeping.

            if ($_current_user_can_conditions) {
                if ($conditions) {
                    $conditions .= ($atts['satisfy'] === 'any' ? ' || ' : ' && ').'('.$_current_user_can_conditions.')';
                } else {
                    $conditions = '('.$_current_user_can_conditions.')';
                }
            } // unset($_current_user_can_conditions); // Housekeeping.
        }
        /*
         * Add conditions from the `current_user_option=""` attribute, if applicable.
         *
         * - [if current_user_option="key" /]
         * - [if current_user_option="key = value OR key = value" /]
         * - [if current_user_option="(key != value AND key != value) OR (key AND another_key > 4)" /]
         */
        if ($atts['current_user_option']) {
            if (mb_strpos($atts['current_user_option'], "'") !== false) {
                trigger_error(sprintf(__('`[%1$s /]` shortcode attribute `current_user_option="%2$s"` contains apostrophe.', 'if-shortcode'), $shortcode, $atts['current_user_option']), E_USER_ERROR);
                return ''; // Return empty string in case of error handlers allowing this to slide.
            } elseif (!preg_match('/\((?:(?:[^()]+)|(?R))*\)/u', '('.$atts['current_user_option'].')', $_m) || $_m[0] !== '('.$atts['current_user_option'].')') {
                trigger_error(sprintf(__('`[%1$s /]` shortcode attribute `current_user_option="%2$s"` contains unbalanced `()` brackets.', 'if-shortcode'), $shortcode, $atts['current_user_option']), E_USER_ERROR);
                return ''; // Return empty string in case of error handlers allowing this to slide.
            }
            $_current_user_option_conditions = ''; // Initialize conditions.
            $_previous_frag                  = ''; // Initialize previous fragment.

            foreach (preg_split('/([()])|\s+/u', $atts['current_user_option'], -1, PREG_SPLIT_DELIM_CAPTURE | PREG_SPLIT_NO_EMPTY) as $_frag) {
                $_space = !$_current_user_option_conditions || !$_previous_frag || $_previous_frag === '(' || $_frag === '(' || $_frag === ')' ? '' : ' ';

                if (in_array($_lc_frag = mb_strtolower($_frag), ['(', ')', 'and', 'or', '&&', '||'], true)) {
                    $_frag = $_lc_frag === 'and' ? '&&' : $_frag;
                    $_frag = $_lc_frag === 'or' ? '||' : $_frag;
                    $_current_user_option_conditions .= $_space.$_frag;
                } elseif (($_frag_parts = preg_split('/([<>!=]+)|\s+/u', $_frag, -1, PREG_SPLIT_DELIM_CAPTURE | PREG_SPLIT_NO_EMPTY))) {
                    $_operator = $_frag_parts[1] ?? ''; // Defaults to a boolean condition below; i.e., when empty.
                    $_operator = $_operator && preg_match('/^\=+$/u', $_operator) ? '===' : $_operator;
                    $_operator = $_operator && preg_match('/^\!\=+$/u', $_operator) ? '!==' : $_operator;
                    $_value    = $_operator && isset($_frag_parts[2]) ? $_frag_parts[2] : '';

                    if (!in_array($_operator, ['', '==', '!=', '===', '!==', '<=', '>=', '<>', '>', '<'], true)) {
                        trigger_error(sprintf(__('`[%1$s /]` shortcode attribute `current_user_option` contains invalid operator: %2$s.', 'if-shortcode'), $shortcode, $_operator), E_USER_ERROR);
                        return ''; // Return empty string in case of error handlers allowing this to slide.
                    }
                    $_current_user_option_conditions .= $_space.($_operator ? '(string)' : '').'get_user_option(\''.$_frag_parts[0].'\', '.$current_user_id.')'.($_operator ? ' '.$_operator.' \''.$_value.'\'' : '');
                }
                $_previous_frag = $_frag; // Previous fragment.
            } // unset($_frag, $_lc_frag, $_previous_frag, $_space, $_frag_parts, $_operator, $_value); // Housekeeping.

            if ($_current_user_option_conditions) {
                if ($conditions) {
                    $conditions .= ($atts['satisfy'] === 'any' ? ' || ' : ' && ').'('.$_current_user_can_conditions.')';
                } else {
                    $conditions = '('.$_current_user_option_conditions.')';
                }
            } // unset($_current_user_option_conditions); // Housekeeping.
        }
        /*
         * Add conditions from the `current_user_meta=""` attribute, if applicable.
         *
         * - [if current_user_meta="key" /]
         * - [if current_user_meta="key = value OR key = value" /]
         * - [if current_user_meta="(key != value AND key != value) OR (key AND another_key > 4)" /]
         */
        if ($atts['current_user_meta']) {
            if (mb_strpos($atts['current_user_meta'], "'") !== false) {
                trigger_error(sprintf(__('`[%1$s /]` shortcode attribute `current_user_meta="%2$s"` contains apostrophe.', 'if-shortcode'), $shortcode, $atts['current_user_meta']), E_USER_ERROR);
                return ''; // Return empty string in case of error handlers allowing this to slide.
            } elseif (!preg_match('/\((?:(?:[^()]+)|(?R))*\)/u', '('.$atts['current_user_meta'].')', $_m) || $_m[0] !== '('.$atts['current_user_meta'].')') {
                trigger_error(sprintf(__('`[%1$s /]` shortcode attribute `current_user_meta="%2$s"` contains unbalanced `()` brackets.', 'if-shortcode'), $shortcode, $atts['current_user_meta']), E_USER_ERROR);
                return ''; // Return empty string in case of error handlers allowing this to slide.
            }
            $_current_user_meta_conditions = ''; // Initialize conditions.
            $_previous_frag                = ''; // Initialize previous fragment.

            foreach (preg_split('/([()])|\s+/u', $atts['current_user_meta'], -1, PREG_SPLIT_DELIM_CAPTURE | PREG_SPLIT_NO_EMPTY) as $_frag) {
                $_space = !$_current_user_meta_conditions || !$_previous_frag || $_previous_frag === '(' || $_frag === '(' || $_frag === ')' ? '' : ' ';

                if (in_array($_lc_frag = mb_strtolower($_frag), ['(', ')', 'and', 'or', '&&', '||'], true)) {
                    $_frag = $_lc_frag === 'and' ? '&&' : $_frag;
                    $_frag = $_lc_frag === 'or' ? '||' : $_frag;
                    $_current_user_meta_conditions .= $_space.$_frag;
                } elseif (($_frag_parts = preg_split('/([<>!=]+)|\s+/u', $_frag, -1, PREG_SPLIT_DELIM_CAPTURE | PREG_SPLIT_NO_EMPTY))) {
                    $_operator = $_frag_parts[1] ?? ''; // Defaults to a boolean condition below; i.e., when empty.
                    $_operator = $_operator && preg_match('/^\=+$/u', $_operator) ? '===' : $_operator;
                    $_operator = $_operator && preg_match('/^\!\=+$/u', $_operator) ? '!==' : $_operator;
                    $_value    = $_operator && isset($_frag_parts[2]) ? $_frag_parts[2] : '';

                    if (!in_array($_operator, ['', '==', '!=', '===', '!==', '<=', '>=', '<>', '>', '<'], true)) {
                        trigger_error(sprintf(__('`[%1$s /]` shortcode attribute `current_user_meta` contains invalid operator: %2$s.', 'if-shortcode'), $shortcode, $_operator), E_USER_ERROR);
                        return ''; // Return empty string in case of error handlers allowing this to slide.
                    }
                    $_current_user_meta_conditions .= $_space.($_operator ? '(string)' : '').'get_user_meta('.$current_user_id.', \''.$_frag_parts[0].'\', true)'.($_operator ? ' '.$_operator.' \''.$_value.'\'' : '');
                }
                $_previous_frag = $_frag; // Previous fragment.
            } // unset($_frag, $_lc_frag, $_previous_frag, $_space, $_frag_parts, $_operator, $_value); // Housekeeping.

            if ($_current_user_meta_conditions) {
                if ($conditions) {
                    $conditions .= ($atts['satisfy'] === 'any' ? ' || ' : ' && ').'('.$_current_user_can_conditions.')';
                } else {
                    $conditions = '('.$_current_user_meta_conditions.')';
                }
            } // unset($_current_user_meta_conditions); // Housekeeping.
        }
        /*
         * Test the expression return value and deal with nested shortcodes.
         * This uses all possible conditions from above and tests them in one `eval()`.
         */
        return do_shortcode($conditions && c::phpEval('return ('.$conditions.');') ? $content_if : $content_else);
    }
}

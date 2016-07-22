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
 * Shortcode utils.
 *
 * @since 160707.2545 Initial release.
 */
class Shortcode extends SCoreClasses\SCore\Base\Core
{
    /**
     * `[if /]` shortcode name.
     *
     * @since 160709.39379 Refactor.
     *
     * @param string
     */
    public $tag_name;

    /**
     * `[else]` shortcode name.
     *
     * @since 160709.39379 Refactor.
     *
     * @param string
     */
    protected $else_tag_name;

    /**
     * Tag name as regex.
     *
     * @since 160722.45266 Nested 'blocks'.
     *
     * @param string
     */
    protected $tag_name_regex_frag;

    /**
     * Initialized?
     *
     * @since 160709.39379 Refactor.
     *
     * @param bool|null
     */
    protected $initialized;

    /**
     * Can `eval()`?
     *
     * @since 160709.39379 Refactor.
     *
     * @param bool|null
     */
    protected $can_eval;

    /**
     * `php=""` attribute enabled?
     *
     * @since 160709.39379 Refactor.
     *
     * @param bool|null
     */
    protected $enable_php_att;

    /**
     * `for_blog=""` attribute enabled?
     *
     * @since 160709.39379 Refactor.
     *
     * @param bool|null
     */
    protected $enable_for_blog_att;

    /**
     * Arbitrary attributes enabled?
     *
     * @since 160709.39379 Refactor.
     *
     * @param bool|null
     */
    protected $enable_arbitrary_atts;

    /**
     * Whitelisted arbitrary attributes.
     *
     * @since 160709.39379 Refactor.
     *
     * @param array|null
     */
    protected $whitelisted_arbitrary_atts;

    /**
     * Debug att default.
     *
     * @since 160709.39379 Refactor.
     *
     * @param string|null
     */
    protected $debug_att_default;

    /**
     * Current user ID.
     *
     * @since 160709.39379 Refactor.
     *
     * @param int|null
     */
    protected $current_user_id;

    /**
     * Current shortcode depth.
     *
     * @since 160709.39379 Refactor.
     *
     * @param int|null
     */
    protected $current_depth;

    /**
     * Current shortcode.
     *
     * @since 160709.39379 Refactor.
     *
     * @param string|null
     */
    protected $current_shortcode;

    /**
     * Current raw attributes.
     *
     * @since 160709.39379 Refactor.
     *
     * @param array|null
     */
    protected $current_raw_atts;

    /**
     * Current attributes.
     *
     * @since 160709.39379 Refactor.
     *
     * @param array|null
     */
    protected $current_atts;

    /**
     * Current conditions.
     *
     * @since 160709.39379 Refactor.
     *
     * @param string|null
     */
    protected $current_conditions;

    /**
     * Current errors.
     *
     * @since 160709.39379 Refactor.
     *
     * @param array|null
     */
    protected $current_errors;

    /**
     * Inside a top-level shortcode?
     *
     * @since 160722.57445 Refactor.
     *
     * @param bool|null
     */
    protected $in_top_level_shortcode;

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

        $this->tag_name            = s::applyFilters('tag_name', 'if');
        $this->else_tag_name       = s::applyFilters('else_tag_name', 'else');
        $this->tag_name_regex_frag = c::escRegex($this->tag_name);
    }

    /**
     * Maybe initialize.
     *
     * @since 160709.39379 Refactor.
     */
    protected function maybeInitialize()
    {
        if ($this->initialized) {
            return; // Did this already.
        }
        $this->initialized = true;

        $this->can_eval                   = c::canCallFunc('eval');
        $this->enable_php_att             = (bool) s::getOption('enable_php_att');
        $this->enable_for_blog_att        = (bool) s::getOption('enable_for_blog_att');
        $this->enable_arbitrary_atts      = (bool) s::getOption('enable_arbitrary_atts');
        $this->whitelisted_arbitrary_atts = $this->enable_arbitrary_atts ? preg_split('/[\s,]+/u', s::getOption('whitelisted_arbitrary_atts'), -1, PREG_SPLIT_NO_EMPTY) : [];
        $this->debug_att_default          = s::getOption('debug_att_default');
    }

    /**
     * `[if /]` shortcode.
     *
     * @since 160707.2545 Initial release.
     *
     * @param array|string $atts      Shortcode attributes.
     * @param string|null  $content   Shortcode content.
     * @param string       $shortcode Shortcode name.
     */
    public function onShortcode($atts = [], $content = '', $shortcode = ''): string
    {
        /*
         * Maybe initialize.
         */
        $this->maybeInitialize();

        /*
         * Content/shortcode.
         */
        $atts      = is_array($atts) ? $atts : [];
        $content   = (string) $content;
        $shortcode = (string) $shortcode;

        /*
         * Parse attributes.
         */
        $default_atts = [
            // PHP attribute.
            'php' => '', // PHP expression.

            // WordPress user-specific attributes.
            'current_user_is_logged_in' => '', // `true|false`.
            'current_user_can'          => '', // Role/cap expression.
            'current_user_option'       => '', // Meta key expression.
            'current_user_meta'         => '', // Meta key expression.

            // WooCommerce customer-specific attributes.
            'current_user_is_paying_customer' => '', // `true|false`.
            'current_user_bought_product'     => '', // Product ID (or SKU) expression.
            'current_user_can_download'       => '', // Product ID (or SKU) expression.

            // Attribute modifiers.
            '_for_blog' => '0', // A specific blog ID.
            '_satisfy'  => 'all', // `any` or `all` (default).

            // Debugging attributes.
            '_debug' => $this->debug_att_default,
            // `1|on|yes|true|0|off|no|false` (or `verbose`).
        ];
        $raw_atts = $atts; // Copy.
        $atts     = c::unescHtml($atts);
        $atts     = array_merge($default_atts, $atts);

        if ($shortcode) { // NOTE: We don't use `shortcode_atts()` on purpose.
            $atts = apply_filters('shortcode_atts_'.$shortcode, $atts, $default_atts, $raw_atts, $shortcode);
        } // However, this will still apply the filter like `shortcode_atts()` would do.

        $atts['_for_blog'] = (int) $atts['_for_blog'];
        $atts['_satisfy']  = $atts['_satisfy'] === 'any' ? 'any' : 'all';

        if ($atts['_debug'] && $atts['_debug'] !== 'verbose') {
            $atts['_debug'] = filter_var($atts['_debug'], FILTER_VALIDATE_BOOLEAN);
        } elseif ($atts['_debug'] !== 'verbose') {
            $atts['_debug'] = false;
        }

        /*
         * Set 'current' properties.
         */
        $this->current_user_id    = (int) get_current_user_id();
        $this->current_depth      = strspn($shortcode, '_');
        $this->current_shortcode  = $shortcode;
        $this->current_raw_atts   = $raw_atts;
        $this->current_atts       = $atts;
        $this->current_conditions = '';
        $this->current_errors     = [];

        /*
         * Initial validations.
         */
        if (!$this->can_eval) {
            $this->current_errors[] = sprintf(__('The `[%1$s]` shortcode requires the legitimate use of PHP `eval()`.', 'if-shortcode'), $this->current_shortcode).
                ' '.__('Unfortunately, `eval()` is currently disabled by your PHP configuration. Therefore, the use of this shortcode will not be possible.', 'if-shortcode').
                ' '.__('To resolve this issue, please contact your server administrator and ask them to enable `eval()` in PHP.', 'if-shortcode');
        }
        if ($this->current_atts['php'] && !$this->enable_php_att) {
            $this->current_errors[] = sprintf(__('`[%1$s]` shortcode attribute `php="%2$s"` is not enabled via plugin options.', 'if-shortcode'), $this->current_shortcode, $this->current_atts['php']);
        }
        if ($this->current_atts['_for_blog'] && !$this->enable_for_blog_att) {
            $this->current_errors[] = sprintf(__('`[%1$s]` shortcode attribute `_for_blog="%2$s"` is not enabled via plugin options.', 'if-shortcode'), $this->current_shortcode, $this->current_atts['_for_blog']);
        }
        if (isset($this->current_atts[0])) { // i.e., Attributes that do not have an `=""` value are numerically indexed by WP.
            $this->current_errors[] = sprintf(__('`[%1$s]` shortcode attribute names, by themselves, without `=""`, are not supported at this time; because that particular format depends on functionality which may change in a future version of WP.', 'if-shortcode'), $this->current_shortcode);
        }

        /*
         * Iterate all shortcode attributes.
         */
        foreach (array_keys($this->current_atts) as $_att_key) {
            switch ((string) $_att_key) {
                /*
                 * PHP attribute.
                 */

                /*
                 * `php="[expr]"`
                 */
                case 'php':
                    if ($this->current_atts[$_att_key]) {
                        $this->appendConditions($this->current_atts[$_att_key]);
                    }
                    break;

                /*
                 * WordPress user-specific attributes.
                 */

                /*
                 * `current_user_is_logged_in="[true|false]"`
                 */
                case 'current_user_is_logged_in':
                    if ($this->current_atts[$_att_key]) {
                        $_negating = $this->current_atts[$_att_key] === 'false' ? '!' : '';
                        $this->appendConditions($_negating.'is_user_logged_in()');
                    }
                    break;

                /*
                 * `current_user_can="[expr]"`
                 */
                case 'current_user_can':
                    if ($this->current_atts[$_att_key]) {
                        $this->appendConditions($this->simpleExpr($_att_key, function ($cap) {
                            return $this->current_atts['_for_blog'] && $this->Wp->is_multisite
                                ? 'current_user_can_for_blog('.$this->current_atts['_for_blog'].', '.c::sQuote($cap).')'
                                : 'current_user_can('.c::sQuote($cap).')';
                        }));
                    }
                    break;

                /*
                 * `current_user_option="[expr]"`
                 */
                case 'current_user_option':
                    if ($this->current_atts[$_att_key]) {
                        $this->appendConditions($this->simpleExpr($_att_key, function ($option_key) {
                            return '('.$this->current_user_id.' ? get_user_option('.c::sQuote($option_key).') : false)';
                        }));
                    }
                    break;

                /*
                 * `current_user_meta="[expr]"`
                 */
                case 'current_user_meta':
                    if ($this->current_atts[$_att_key]) {
                        $this->appendConditions($this->simpleExpr($_att_key, function ($meta_key) {
                            return '('.$this->current_user_id.' ? get_user_meta('.$this->current_user_id.', '.c::sQuote($meta_key).', true) : false)';
                        }));
                    }
                    break;

                /*
                 * WooCommerce customer-specific attributes.
                 */

                /*
                 * `current_user_is_paying_customer="[true|false]"`
                 */
                case 'current_user_is_paying_customer':
                    if ($this->current_atts[$_att_key]) {
                        $_negating = $this->current_atts[$_att_key] === 'false' ? '!' : '';
                        $this->appendConditions($_negating.'('.(int) $this->Wp->is_woocommerce_active.' && '.$this->current_user_id.' ? (bool) get_user_meta('.$this->current_user_id.', \'paying_customer\', true) : false)');
                    }
                    break;

                /*
                 * `current_user_bought_product="[expr]"`
                 */
                case 'current_user_bought_product':
                    if ($this->current_atts[$_att_key]) {
                        $this->appendConditions($this->simpleExpr($_att_key, function ($product_id_or_sku) {
                            return '('.(int) $this->Wp->is_woocommerce_active.' && '.$this->current_user_id.' ? '.a::class.'::wcCustomerBoughtProduct('.$this->current_user_id.', '.c::sQuote($product_id_or_sku).') : false)';
                        }));
                    }
                    break;

                /*
                 * `current_user_can_download="[expr]"`
                 */
                case 'current_user_can_download':
                    if ($this->current_atts[$_att_key]) {
                        $this->appendConditions($this->simpleExpr($_att_key, function ($product_id_or_sku) {
                            return '('.(int) $this->Wp->is_woocommerce_active.' && '.$this->current_user_id.' ? '.a::class.'::wcCustomerCanDownload('.$this->current_user_id.', '.c::sQuote($product_id_or_sku).') : false)';
                        }));
                    }
                    break;

                /*
                 * Arbitrary custom attributes.
                 */

                /*
                 * `[arbitrary]="[true|false]"` or `[arbitrary]="[expr]"`
                 */
                default: // NOTE: Arbitrary attributes must match `^[a-z][a-z0-9_]+[a-z0-9]$` to avoid compatibility issues in the future.
                    // For instance, WordPress v4.5.3 allows an attribute to start with `!`, but if that changes in the future it could lead to
                    // some conditionals breaking and/or not behaving as originally intended. Best to avoid things like this altogether.

                    if (is_int($_att_key)) { // Do not allow integer attribute keys; i.e., those without an `=""` value.
                        $this->current_errors[] = sprintf(__('`[%1$s]` shortcode attribute `%2$s`, by itself, without `=""`, is not supported. Instead, use `%2$s="true"`. Consult the shortcode documentation for assistance.', 'if-shortcode'), $this->current_shortcode, $this->current_atts[$_att_key]);
                        //
                    } elseif ($this->current_atts[$_att_key] && mb_strpos($_att_key, '_') !== 0) { // Skip over `_` modidifers.
                        //
                        if (!$this->enable_arbitrary_atts) {
                            $this->current_errors[] = sprintf(__('Arbitrary `[%1$s]` shortcode attribute `%2$s="%3$s"` is not enabled via plugin options.', 'if-shortcode'), $this->current_shortcode, $_att_key, $this->current_atts[$_att_key]);
                        } elseif ($this->whitelisted_arbitrary_atts && !in_array($_att_key, $this->whitelisted_arbitrary_atts, true)) {
                            $this->current_errors[] = sprintf(__('Arbitrary `[%1$s]` shortcode attribute `%2$s="%3$s"` is not whitelisted via plugin options.', 'if-shortcode'), $this->current_shortcode, $_att_key, $this->current_atts[$_att_key]);
                        } elseif (!preg_match('/^[a-z][a-z0-9_]+[a-z0-9]$/u', $_att_key)) { // Make this extra strict to avoid future compatibility issues.
                            $this->current_errors[] = sprintf(__('Arbitrary `[%1$s]` shortcode attribute `%2$s="%3$s"` contains invalid chars in the attribute name. Must match: `^[a-z][a-z0-9_]+[a-z0-9]$`.', 'if-shortcode'), $this->current_shortcode, $_att_key, $this->current_atts[$_att_key]);
                        //
                        } elseif ($this->current_atts[$_att_key] === 'true' || $this->current_atts[$_att_key] === 'false') {
                            $_negating                     = $this->current_atts[$_att_key] === 'false' ? '!' : '';
                            $_arbitrary_att_bool_condition = s::applyFilters('arbitrary_att_bool_condition', $_negating.$_att_key.'()', $_att_key, $this->current_atts[$_att_key]);
                            $this->appendConditions($_arbitrary_att_bool_condition); // Allows for a custom condition filter.
                        } else { // Treat it as a simple expression.
                            $_arbitrary_att_expr_callback = s::applyFilters('arbitrary_att_expr_callback', function ($args) use ($_att_key) {
                                return $_att_key.'('.implode(', ', c::sQuote(explode(',', $args))).')'; // Default callback.
                            }, $_att_key, $this->current_atts[$_att_key]); // Allows for a custom callback filter.

                            $this->appendConditions($this->simpleExpr($_att_key, $_arbitrary_att_expr_callback));
                        }
                    }
                    break;
            }
        } // unset($_att_key, $_negating, $_arbitrary_att_bool_condition, $_arbitrary_att_expr_callback); // Housekeeping.

        /*
         * Evaluate, if possible.
         */
        try { // We can catch problems in PHP 7+ via exception.
            if (!$this->current_errors && $this->current_conditions && $this->can_eval) {
                $conditions_true = c::phpEval('return ('.$this->current_conditions.');');
            } else {
                $conditions_true = false; // Force false; not possible.
            }
        } catch (\Throwable $eval_Throwable) {
            $conditions_true        = false; // Force false.
            $this->current_errors[] = $eval_Throwable->getMessage();
        }

        /*
         * Verbose debug output?
         */
        if ($this->current_atts['_debug'] === 'verbose') {
            $debug_verbose = '<pre style="padding:1em; border-radius:.25em; max-width:100%; overflow:auto; max-width:100%; overflow:auto;">'.
                                sprintf(__('<code>[%1$s]</code> Shortcode Verbose', 'if-shortcode'), esc_html($this->tag_name)).'<br />'.esc_html($this->recreate()).'<br /><br />'.
                                sprintf(__('<code>[%1$s]</code> Shortcode Atts', 'if-shortcode'), esc_html($this->tag_name)).'<br />'.c::mbTrim(c::dump($this->current_atts, true), "\n").'<br /><br />'.
                                sprintf(__('<code>[%1$s]</code> Shortcode Conditions', 'if-shortcode'), esc_html($this->tag_name)).'<br />'.esc_html($this->current_conditions).
                             '</pre>';
        } else {
            $debug_verbose = ''; // Default behavior (empty string).
        }

        /*
         * Do we have any errors?
         */
        if ($this->current_errors) {
            /*
             * Returns errors to browser?
             */
            if ($this->current_atts['_debug']) {
                //
                return $debug_verbose.// Verbose debug output (if enabled).
                        '<div style="background:#b30000; color:#ffffff; padding:1em; border-radius:.25em;">'.
                            '<h4 style="background:inherit; color:inherit; margin:0 0 .5em 0; padding:0 0 .5em 0; line-height:1em; border-bottom:1px solid;">'.
                                sprintf(_n('<code>[%1$s]</code> Shortcode Error', '<code>[%1$s]</code> Shortcode Errors', count($this->current_errors), 'if-shortcode'), esc_html($this->tag_name)).
                            '</h4>'.
                            '<pre style="background:#ffffff; color:#000000; padding:1em; margin:0 0 .5em 0; border-radius:.25em; max-width:100%; overflow:auto;">'.
                                esc_html($this->recreate()).
                            '</pre>'.
                            '<ul style="margin:0 0 0 2em; padding:0; background:inherit; color:inherit;">'.
                                '<li style="background:inherit; color:inherit; margin:0; padding:0;">'.
                                    implode(// Deals with more than a single error.
                                        '</li><li style="background:inherit; color:inherit; margin:1em 0 0 0; padding:0;">',
                                        c::markdown($this->current_errors, ['no_p' => true])
                                    ).
                                '</li>'.
                            '</ul>'.
                        '</div>';
            /*
             * Else, if debugging, trigger PHP warning.
             */
            } elseif ($this->App->Config->©debug['©enable']) {
                debug(0, c::issue(vars(), implode("\n", $this->current_errors)));
                trigger_error(implode("\n", $this->current_errors), E_USER_WARNING);
            }
            return ''; // Default behavior; fail silently with conditions forced to a false state.
        }

        /*
         * Parse content into if/else.
         */
        $else_tag = '['.str_repeat('_', $this->current_depth).$this->else_tag_name.']';

        if (mb_strpos($content, $else_tag) !== false) {
            list($content_if, $content_else) = explode($else_tag, $content, 2);
        } else {
            $content_if   = $content; // No `[else]` tag.
            $content_else = ''; // Default (empty).
        }

        /*
         * Apply shortcode content filters.
         */
        if ($conditions_true && $content_if) {
            $content_if = s::applyFilters('content', $content_if);
        } elseif (!$conditions_true && $content_else) {
            $content_else = s::applyFilters('content', $content_else);
        }

        /*
         * Return shortcode output.
         */
        $output        = $conditions_true ? $content_if : $content_else;
        return $output = $debug_verbose.$output; // With possible debug info.
    }

    /**
     * Build conditions from simple expression.
     *
     * @since 160709.39379 Refactor.
     *
     * @param string   $att      Shortcode attribute name.
     * @param callable $callback A callback handler.
     */
    protected function simpleExpr(string $att, callable $callback): string
    {
        if (!($conditions = c::simplePhpExpr($this->current_atts[$att] ?? '', $callback))) {
            $this->current_errors[] = sprintf(__('`[%1$s]` shortcode attribute contains an invalid (or imbalanced) expression: `%2$s="%3$s"`', 'if-shortcode'), $this->current_shortcode, $att, $this->current_atts[$att]);
        }
        return $conditions;
    }

    /**
     * Append conditions.
     *
     * @since 160709.39379 Refactor.
     *
     * @param string $conditions Conditions.
     */
    protected function appendConditions(string $conditions)
    {
        if (!$conditions) {
            return; // Nothing to do here.
        } elseif ($this->current_conditions) { // Conditions already exist?
            $this->current_conditions .= ($this->current_atts['_satisfy'] === 'any' ? ' || ' : ' && ').'('.$conditions.')';
        } else {
            $this->current_conditions = '('.$conditions.')';
        }
    }

    /**
     * Recreate shortcode.
     *
     * @since 160709.39379 Refactor.
     *
     * @return string Shortcode.
     */
    protected function recreate(): string
    {
        $shortcode = '['.$this->current_shortcode;

        foreach ($this->current_raw_atts as $_att_key => $_att_value) {
            if (is_int($_att_key)) {
                $shortcode .= ' '.$_att_value;
            } else {
                $shortcode .= ' '.$_att_key.'="'.$_att_value.'"';
            }
        } // unset($_att_key, $_att_value); // Housekeeping.

        return $shortcode .= ']';
    }

    /**
     * Forces nested `[_if]` 'blocks'.
     *
     * @since 160722.45266 Force nested `[_if]` 'blocks'.
     *
     * @param string|scalar $content Content to filter.
     *
     * @return $string Filtered content.
     */
    public function onContentforceNestedIfBlocks($content): string
    {
        $content = (string) $content;

        if (mb_strpos($content, '[_') === false) {
            return $content; // Nothing to do.
        }
        // Each `[if]` content fragment is treated as a stand-alone document.
        // Nested `[_if]` tags must be separated by 2+ line breaks so `wpautop()` will create
        // separate `<p>` blocks instead of using `<br />` tags.

        // i.e., We want to avoid `<p>` tags inside `<p>` tags.
        // e.g., `<p>if content<br /><p>nested _if content</p></p>`

        // By forcing nested `[_if]` blocks we get:
        // `<p>if content</p><p>nested _if content</p>`

        // This only impacts nested `[_if]` tags that are already on a line of their own.

        $regex          = '/(^|['."\n".']+)(['."\t".' ]*\[_+'.$this->tag_name_regex_frag.'\s)/u';
        return $content = preg_replace_callback($regex, function ($m) {
            return !isset($m[1][0]) || isset($m[1][1]) ? $m[0] : "\n\n".$m[2];
        }, c::normalizeEols($content));
    }

    /**
     * Do nested shortcodes.
     *
     * @since 160722.45266 Do nested shortcodes.
     *
     * @param string|scalar $content Content to filter.
     *
     * @return $string Filtered content.
     */
    public function onContentDoNestedShortcodes($content): string
    {
        // NOTE: This prevents filter corruption when doing nested shortcodes.
        // See `do{} while()` here: <https://developer.wordpress.org/reference/functions/apply_filters/>
        // In short, this prevents nested filters applied as a result of calling `do_shortcode()`,
        // from altering the 'current' outer filter in the WordPress core `do{} while()` loop.

        $content = (string) $content;

        // Backup filter state.

        $current_filter_by_ref       = &$GLOBALS['wp_filter'][current_filter()];
        $current_outer_filter_backup = $current_filter_by_ref;

        // Do nested shortcodes.

        $content = do_shortcode($content);

        // Restore filter state.

        $current_filter_by_ref = $current_outer_filter_backup;

        // Return filtered content now.

        return $content;
    }
}

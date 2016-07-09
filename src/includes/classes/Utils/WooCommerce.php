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
 * WooCommerce utils.
 *
 * @since 160709.39379 WooCommerce utils.
 */
class WooCommerce extends SCoreClasses\SCore\Base\Core
{
    /**
     * Product ID by SKU.
     *
     * @since 160709.39379 WooCommerce utils.
     *
     * @param string $product_sku Product SKU.
     *
     * @return int Product ID by SKU.
     */
    public function productIdBySku(string $product_sku): int
    {
        if ((string) (int) $product_sku === $product_sku) {
            // NOTE: Do not use all numeric SKUs.
            return (int) $product_sku; // Assume ID.
        } elseif (!is_array($by_sku = $this->productIdsBySku())) {
            return (int) wc_get_product_id_by_sku($product_sku);
        }
        return $by_sku[$product_sku] ?? 0;
    }

    /**
     * Product IDs by SKU.
     *
     * @since 160709.39379 WooCommerce utils.
     *
     * @return array|null Product IDs by SKU, else `null` if there are too many SKUs.
     *                    If the default limit 2500 is exceeded, this utility is simply not capable of working.
     *                    Instead, use `wc_get_product_id_by_sku()`. Or, don't use SKUs, use product IDs.
     */
    public function productIdsBySku()
    {
        $transient_key = 'woocommerce_product_ids_by_sku';
        if (is_array($by_sku = s::getTransient($transient_key))) {
            return $by_sku; // Already cached this.
        }
        $WpDb   = s::wpDb(); // DB object reference.
        $by_sku = []; // Initialize array of IDs by SKU.

        $sql = /* Query 2500 product SKUs. */ '
            SELECT
                SQL_CALC_FOUND_ROWS
                `posts`.`ID`,
                `postmeta`.`meta_value` AS `sku`

                FROM `'.esc_html($WpDb->posts).'` AS `posts`,
                     `'.esc_sql($WpDb->postmeta).'` AS `postmeta`

            WHERE `posts`.`ID` = `postmeta`.`post_id`
                AND `posts`.`post_type` IN(\'product\', \'product_variation\')
                AND `postmeta`.`meta_key` = \'_sku\' AND `postmeta`.`meta_value` != \'\'

            LIMIT '.($upper_limit = (int) s::applyFilters('woocommerce_product_ids_by_sku_limit', 2500));
        /*
         * NOTE: A warning should be given to site owners.
         * If you have more than 2500 SKUs, use IDs instead of SKUs.
         * Or, increase RAM and be aware that > 2500 product IDs will be in memory.
         *
         * The filter above allows for the upper limit to be increased to any amount you like.
         *  i.e., use filter: `if_shortcode_woocommerce_product_ids_by_sku_limit`.
         */
        if (($results = $WpDb->get_results($sql))) {
            if ((int) $WpDb->get_var('SELECT FOUND_ROWS()') > $limit) {
                return $by_sku = null; // Do not use. Do not save.
            }
            foreach ($results as $_result) {
                $by_sku[(string) $_result->sku] = (int) $_result->ID;
            } // unset($_result); // Housekeeping.
        }
        s::setTransient($transient_key, $by_sku);

        return $by_sku; // Keyed by SKU now.
    }

    /**
     * On product save.
     *
     * @since 160709.39379 WooCommerce utils.
     */
    public function onSaveProduct()
    {
        s::deleteTransient('woocommerce_product_ids_by_sku');
    }

    /**
     * On product variation save.
     *
     * @since 160709.39379 WooCommerce utils.
     */
    public function onSaveProductVariation()
    {
        s::deleteTransient('woocommerce_product_ids_by_sku');
    }
}

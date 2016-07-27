<?php
/**
 * WooCommerce.
 *
 * @author @jaswsinc
 * @copyright WP Sharks™
 */
declare (strict_types = 1);
namespace WebSharks\WpSharks\IfShortcode\Pro\Traits\Facades;

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
 * WooCommerce.
 *
 * @since 160524 Initial release.
 */
trait WooCommerce
{
    /**
     * @since 160524 Initial release.
     */
    public static function wcProductIdBySku(...$args)
    {
        return $GLOBALS[static::class]->Utils->WooCommerce->productIdBySku(...$args);
    }

    /**
     * @since 160720 Initial release.
     */
    public static function wcCustomerBoughtProduct(...$args)
    {
        return $GLOBALS[static::class]->Utils->WooCommerce->customerBoughtProduct(...$args);
    }

    /**
     * @since 160720 Initial release.
     */
    public static function wcCustomerCanDownload(...$args)
    {
        return $GLOBALS[static::class]->Utils->WooCommerce->customerCanDownload(...$args);
    }
}

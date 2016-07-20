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
 * Menu page utils.
 *
 * @since 160718.59682 Menu page utils.
 */
class MenuPage extends SCoreClasses\SCore\Base\Core
{
    /**
     * Adds menu pages.
     *
     * @since 160718.59682 Menu page utils.
     */
    public function onAdminMenu()
    {
        s::addMenuPageItem([
            'auto_prefix'   => false,
            'parent_page'   => 'options-general.php',
            'page_title'    => $this->App->Config->©brand['©name'],
            'menu_title'    => $this->App->Config->©brand['©name'],
            'template_file' => 'menu-page/default.php',

            'tabs' => [
                'default' => sprintf(__('%1$s Settings', 'wp-sharks-core'), esc_html($this->App->Config->©brand['©name'])),
            ],
        ]);
    }
}

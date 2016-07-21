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
 * Content utils.
 *
 * @since 16xxxx Content utils.
 */
class Content extends SCoreClasses\SCore\Base\Core
{
    /**
     * Tokenizers.
     *
     * @since 16xxxx Content utils.
     *
     * @type CoreClasses\Core\Tokenizer[]|null
     */
    protected $Tokenizers;

    /**
     * Preserve `[if][/if]`.
     *
     * @since 16xxxx Content utils.
     *
     * @param string|scalar $content Content.
     *
     * @return string $content Filtered content.
     */
    public function onTheContentPreserveIfs($content): string
    {
        $content            = (string) $content;
        $shortcode_tag_name = a::shortcodeTagName();

        if (mb_strpos($content, '['.$shortcode_tag_name) === false) {
            return $content; // Nothing to do.
        }
        $Tokenizer = c::tokenize($content, ['shortcodes'], [
            'shortcode_unautop_compat'          => true,
            'shortcode_tag_names'               => $shortcode_tag_name,
            'shortcode_unautop_compat_tag_name' => $shortcode_tag_name,
        ]); // Tokenize only the top-level `[if]` shortcode tag name.

        $this->Tokenizers[] = $Tokenizer;
        $content            = $Tokenizer->getString();

        return $content;
    }

    /**
     * Restore `[if][/if]`.
     *
     * @since 16xxxx Content utils.
     *
     * @param string|scalar $content Content.
     *
     * @return string $content Filtered content.
     */
    public function onTheContentRestoreIfs($content): string
    {
        $content = (string) $content;

        if (!$this->Tokenizers || !($Tokenizer = array_pop($this->Tokenizers))) {
            return $content; // Nothing to do.
        } // Pops last tokenizer off the stack ↑ also.

        $Tokenizer->setString($content);
        $content = $Tokenizer->restoreGetString();

        return $content;
    }
}

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
 * @since 160721.59154 Content utils.
 */
class Content extends SCoreClasses\SCore\Base\Core
{
    /**
     * Tokenizers.
     *
     * @since 160722.57445 Content utils.
     *
     * @type array|null
     */
    protected $Tokenizers;

    /**
     * Shortcode tag name.
     *
     * @since 160722.57445 Content utils.
     *
     * @param string
     */
    protected $tag_name;

    /**
     * Class constructor.
     *
     * @since 160722.57445 Content utils.
     *
     * @param Classes\App $App Instance.
     */
    public function __construct(Classes\App $App)
    {
        parent::__construct($App);

        $this->tag_name = a::shortcodeTagName();
    }

    /**
     * Preserve `[if][/if]`.
     *
     * @since 160721.59154 Content utils.
     *
     * @param string|scalar $content Content.
     *
     * @return string $content Filtered content.
     */
    public function onTheContentPreserveIfs($content): string
    {
        $content = (string) $content;

        if (mb_strpos($content, '['.$this->tag_name) === false) {
            $this->Tokenizers[] = null;
            return $content; // Nothing to do.
        }
        $Tokenizer = c::tokenize($content, ['shortcodes'], [
            'shortcode_unautop_compat'          => true,
            'shortcode_tag_names'               => $this->tag_name,
            'shortcode_unautop_compat_tag_name' => $this->tag_name,
        ]); // Tokenize only the top-level `[if]` shortcode tag name.

        $this->Tokenizers[] = $Tokenizer; // End of stack.
        return $content     = $Tokenizer->getString();
    }

    /**
     * Restore `[if][/if]`.
     *
     * @since 160721.59154 Content utils.
     *
     * @param string|scalar $content Content.
     *
     * @return string $content Filtered content.
     */
    public function onTheContentRestoreIfs($content): string
    {
        $content = (string) $content;

        if (!$this->Tokenizers) {
            debug(0, c::issue(vars(), 'Missing tokenizers.'));
            return $content; // Nothing to do.
        } elseif (!($Tokenizer = array_pop($this->Tokenizers))) {
            return $content; // Nothing to do.
        } // Pops last tokenizer off the stack ↑ also.

        $Tokenizer->setString($content);
        return $content = $Tokenizer->restoreGetString();
    }
}

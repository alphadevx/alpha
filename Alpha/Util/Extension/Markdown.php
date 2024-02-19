<?php

namespace Alpha\Util\Extension;

use Alpha\Util\Config\ConfigProvider;
use Michelf\MarkdownExtra;
use Alpha\Util\Service\ServiceFactory;

/**
 * A custom version of the Markdown class which uses the geshi library for rendering code.
 *
 * @since 1.0
 *
 * @author John Collins <dev@alphaframework.org>
 * @license http://www.opensource.org/licenses/bsd-license.php The BSD License
 * @copyright Copyright (c) 2021, John Collins (founder of Alpha Framework).
 * All rights reserved.
 *
 * <pre>
 * Redistribution and use in source and binary forms, with or
 * without modification, are permitted provided that the
 * following conditions are met:
 *
 * * Redistributions of source code must retain the above
 *   copyright notice, this list of conditions and the
 *   following disclaimer.
 * * Redistributions in binary form must reproduce the above
 *   copyright notice, this list of conditions and the
 *   following disclaimer in the documentation and/or other
 *   materials provided with the distribution.
 * * Neither the name of the Alpha Framework nor the names
 *   of its contributors may be used to endorse or promote
 *   products derived from this software without specific
 *   prior written permission.
 *
 * THIS SOFTWARE IS PROVIDED BY THE COPYRIGHT HOLDERS AND
 * CONTRIBUTORS "AS IS" AND ANY EXPRESS OR IMPLIED WARRANTIES,
 * INCLUDING, BUT NOT LIMITED TO, THE IMPLIED WARRANTIES OF
 * MERCHANTABILITY AND FITNESS FOR A PARTICULAR PURPOSE ARE
 * DISCLAIMED. IN NO EVENT SHALL THE COPYRIGHT HOLDER OR
 * CONTRIBUTORS BE LIABLE FOR ANY DIRECT, INDIRECT, INCIDENTAL,
 * SPECIAL, EXEMPLARY, OR CONSEQUENTIAL DAMAGES (INCLUDING, BUT
 * NOT LIMITED TO, PROCUREMENT OF SUBSTITUTE GOODS OR SERVICES;
 * LOSS OF USE, DATA, OR PROFITS; OR BUSINESS INTERRUPTION)
 * HOWEVER CAUSED AND ON ANY THEORY OF LIABILITY, WHETHER IN
 * CONTRACT, STRICT LIABILITY, OR TORT (INCLUDING NEGLIGENCE
 * OR OTHERWISE) ARISING IN ANY WAY OUT OF THE USE OF THIS
 * SOFTWARE, EVEN IF ADVISED OF THE POSSIBILITY OF SUCH DAMAGE.
 * </pre>
 */
class Markdown extends MarkdownExtra
{
    /**
     * Custom version of the _doCodeBlocks_callback method which invokes a Gheshi
     * object to render code.
     *
     * @since 1.0
     */
    public function _doCodeBlocks_callback($matches)
    {
        $config = ConfigProvider::getInstance();

        $codeblock = $matches[1];

        $codeblock = $this->outdent($codeblock);

        // trim leading newlines and trailing whitespace
        $codeblock = preg_replace(array('/\A\n+/', '/\n+\z/'), '', $codeblock);

        // find the code block and replace it with a blank
        $codeTypeTag = array();
        preg_match('/codeType=\[.*\]/', $codeblock, $codeTypeTag);
        $codeblock = preg_replace('/codeType=\[.*\]\n/', '', $codeblock);

        if (isset($codeTypeTag[0])) {
            $start = mb_strpos($codeTypeTag[0], '[');
            $end = mb_strpos($codeTypeTag[0], ']');
            $language = mb_substr($codeTypeTag[0], $start+1, $end-($start+1));
        } else {
            // will use php as a default language type when none is provided
            $language = 'php';
        }

        if ($config->get('cms.highlight.provider.name') != '') {
            $highlighter = ServiceFactory::getInstance($config->get('cms.highlight.provider.name'), 'Alpha\Util\Code\Highlight\HighlightProviderInterface');
            $codeblock = $highlighter->highlight($codeblock, $language);
        } else {
            $codeblock = htmlspecialchars($codeblock, ENT_NOQUOTES);

            # trim leading newlines and trailing newlines
            $codeblock = preg_replace('/\A\n+|\n+\z/', '', $codeblock);

            $codeblock = "<pre><code>$codeblock\n</code></pre>";
        }

        return "\n\n".$this->hashBlock($codeblock)."\n\n";
    }

    /**
     * Custom _doAnchors_inline_callback that renders links to external sites with a
     * target attribute and an icon.
     *
     * @since 1.0
     */
    public function _doAnchors_inline_callback($matches)
    {
        $config = ConfigProvider::getInstance();

        $link_text      = $this->runSpanGamut($matches[2]);
        $url            = $matches[3] == '' ? $matches[4] : $matches[3];
        $title          = & $matches[7];
        $attr = $this->doExtraAttributes("a", $dummy = & $matches[8]);

        $external = false;

        $parts = parse_url($url);

        /*
         * Only an external link if:
         *
         * 1. $url parses to a valid URL
         * 2. $url has a host part
         * 3. $url does not contain $config->get('app.url'), i.e. points to a local resource.
         */
        if (is_array($parts) && isset($parts['host']) && mb_strpos($url, $config->get('app.url')) === false) {
            $external = true;
        }

        $url = $this->encodeAttribute($url);

        $result = "<a href=\"$url\"";
        if (isset($title)) {
            $title = $this->encodeAttribute($title);
            $result .= " title=\"$title\"";
        }
        if ($external) {
            $result .= " target=\"$url\"";
        }
        $result .= $attr;

        $link_text = $this->runSpanGamut($link_text);
        $result .= ">$link_text</a>";

        return $this->hashPart($result);
    }

    /**
     * Custom version of the _doTable_callback(...) method which sets the table border and CSS style.
     *
     * @since 1.0
     */
    public function _doTable_callback($matches)
    {
        $head       = $matches[1];
        $underline  = $matches[2];
        $content    = $matches[3];

        # Remove any tailing pipes for each line.
        $head       = preg_replace('/[|] *$/m', '', $head);
        $underline  = preg_replace('/[|] *$/m', '', $underline);
        $content    = preg_replace('/[|] *$/m', '', $content);

        $attr = array();

        # Reading alignement from header underline.
        $separators = preg_split('/ *[|] */', $underline);
        foreach ($separators as $n => $s) {
            if (preg_match('/^ *-+: *$/', $s)) {
                $attr[$n] = $this->_doTable_makeAlignAttr('right');
            } elseif (preg_match('/^ *:-+: *$/', $s)) {
                $attr[$n] = $this->_doTable_makeAlignAttr('center');
            } elseif (preg_match('/^ *:-+ *$/', $s)) {
                $attr[$n] = $this->_doTable_makeAlignAttr('left');
            } else {
                $attr[$n] = '';
            }
        }

        # Parsing span elements, including code spans, character escapes,
        # and inline HTML tags, so that pipes inside those gets ignored.
        $head       = $this->parseSpan($head);
        $headers    = preg_split('/ *[|] */', $head);
        $col_count  = count($headers);
        $attr       = array_pad($attr, $col_count, '');

        # Write column headers.
        $text = "<table class=\"table table-bordered\">\n";
        $text .= "<thead>\n";
        $text .= "<tr>\n";
        foreach ($headers as $n => $header) {
            $text .= "  <th$attr[$n]>".$this->runSpanGamut(trim($header))."</th>\n";
        }
        $text .= "</tr>\n";
        $text .= "</thead>\n";

        # Split content by row.
        $rows = explode("\n", trim($content, "\n"));

        $text .= "<tbody>\n";
        foreach ($rows as $row) {
            # Parsing span elements, including code spans, character escapes,
            # and inline HTML tags, so that pipes inside those gets ignored.
            $row = $this->parseSpan($row);

            # Split row by cell.
            $row_cells = preg_split('/ *[|] */', $row, $col_count);
            $row_cells = array_pad($row_cells, $col_count, '');

            $text .= "<tr>\n";
            foreach ($row_cells as $n => $cell) {
                $text .= "  <td$attr[$n]>".$this->runSpanGamut(trim($cell))."</td>\n";
            }
            $text .= "</tr>\n";
        }
        $text .= "</tbody>\n";
        $text .= "</table>";

        return $this->hashBlock($text)."\n";
    }
}

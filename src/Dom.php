<?php
/**
 * MIT License <https://opensource.org/licenses/mit>
 *
 * Copyright (c) 2015 Kerem Güneş
 *
 * Permission is hereby granted, free of charge, to any person obtaining a copy
 * of this software and associated documentation files (the "Software"), to deal
 * in the Software without restriction, including without limitation the rights
 * to use, copy, modify, merge, publish, distribute, sublicense, and/or sell
 * copies of the Software, and to permit persons to whom the Software is furnished
 * to do so, subject to the following conditions:
 *
 * The above copyright notice and this permission notice shall be included in all
 * copies or substantial portions of the Software.
 *
 * THE SOFTWARE IS PROVIDED "AS IS", WITHOUT WARRANTY OF ANY KIND, EXPRESS OR
 * IMPLIED, INCLUDING BUT NOT LIMITED TO THE WARRANTIES OF MERCHANTABILITY,
 * FITNESS FOR A PARTICULAR PURPOSE AND NONINFRINGEMENT. IN NO EVENT SHALL THE
 * AUTHORS OR COPYRIGHT HOLDERS BE LIABLE FOR ANY CLAIM, DAMAGES OR OTHER
 * LIABILITY, WHETHER IN AN ACTION OF CONTRACT, TORT OR OTHERWISE, ARISING FROM,
 * OUT OF OR IN CONNECTION WITH THE SOFTWARE OR THE USE OR OTHER DEALINGS IN
 * THE SOFTWARE.
 */
declare(strict_types=1);

namespace froq\dom;

/**
 * Dom.
 * @package froq\dom
 * @object  froq\dom\Dom
 * @author  Kerem Güneş <k-gun@mail.com>
 * @since   3.0
 */
final /* static */ class Dom
{
    /**
     * Create html document.
     * @param  array|null $data
     * @return froq\dom\HtmlDocument
     */
    public static function createHtmlDocument(array $data = null): HtmlDocument
    {
        return new HtmlDocument($data);
    }

    /**
     * Create xml document.
     * @param  array|null $data
     * @param  string|null $xmlVersion
     * @param  string|null $xmlEncoding
     * @return froq\dom\XmlDocument
     */
    public static function createXmlDocument(array $data = null, string $xmlVersion = null,
        string $xmlEncoding = null): XmlDocument
    {
        return new XmlDocument($data, $xmlVersion, $xmlEncoding);
    }

    /**
     * Parse xml.
     * @param  any  $xml
     * @param  array $options
     * @return any
     * @throws froq\dom\DomException If $options['throwErrors'] is true.
     */
    public static function parseXml($xml, array $options = null)
    {
        $root = $xml;
        static $error, $xmlProperties;

        if (is_string($root)) {
            static $optionsDefault = [
                'validateOnParse' => false, 'preserveWhiteSpace' => false,
                'strictErrorChecking' => false, 'throwErrors' => false, 'flags' => 0
            ];

            ['validateOnParse' => $validateOnParse, 'preserveWhiteSpace' => $preserveWhiteSpace,
             'strictErrorChecking' => $strictErrorChecking, 'throwErrors' => $throwErrors, 'flags' => $flags
            ] = array_merge($optionsDefault, $options ?? []);

            $root = new \DOMDocument();
            $root->validateOnParse = !!$validateOnParse;
            $root->preserveWhiteSpace = !!$preserveWhiteSpace;
            $root->strictErrorChecking = !!$strictErrorChecking;

            libxml_use_internal_errors(true);
            $root->loadXml($xml, intval($flags) + (
                LIBXML_NOERROR | LIBXML_NOWARNING | LIBXML_BIGLINES |
                LIBXML_COMPACT | LIBXML_HTML_NOIMPLIED | LIBXML_HTML_NODEFDTD
            ));

            $error = libxml_get_last_error();
            if ($error) {
                libxml_clear_errors();
                $error->file = $error->file ?: 'n/a';
                $error->message = trim($error->message);
                if ($throwErrors) {
                    throw new DomException(sprintf('Parse error: %s (level:%s code:%s column:%s file:%s line:%s)',
                        $error->message, $error->level, $error->code, $error->column, $error->file, $error->line
                    ), $error->code);
                }
            }
        }

        $return = [];

        // some speed up..
        if ($xmlProperties === null) {
            $xmlProperties = [];
            if ($root->nodeType == XML_DOCUMENT_NODE) {
                // add real root tag, not #document
                $xmlProperties['@root'] = $root->firstChild->tagName ?? null;
                $xmlProperties['@error'] = $error ?: null;
                $xmlProperties['version'] = $root->xmlVersion;
                $xmlProperties['encoding'] = $root->xmlEncoding;
            }
            $return['@xml'] = $xmlProperties;
        }


        if ($root->hasAttributes()) {
            $attributes = $root->attributes;
            foreach ($attributes as $attribute) {
                $return['@attributes'][$attribute->name] = $attribute->value;
            }
        }

        if ($root->hasChildNodes()) {
            $nodes = $root->childNodes;
            if ($nodes->length == 1) {
                $node = $nodes->item(0);
                if ($node->nodeType == XML_TEXT_NODE) {
                    $return['@value'] = $node->nodeValue;
                    return count($return) == 1 ? $return['@value'] : $return;
                }
            }

            $groups = [];
            foreach ($nodes as $node) {
                $nodeName = $node->nodeName;
                $nodeType = $node->nodeType;
                if (!isset($return[$nodeName])) {
                    $return[$nodeName] = self::parseXml($node, $options);
                    // single node
                    if ($return[$nodeName] == []) {
                        $return[$nodeName] = null;
                    }
                } else {
                    // multi nodes
                    if (!isset($groups[$nodeName])) {
                        $groups[$nodeName] = 1;
                        $return[$nodeName] = [$return[$nodeName]];
                    }
                    $return[$nodeName][] = self::parseXml($node, $options);
                }
            }
        } elseif ($root->nodeType == XML_COMMENT_NODE) {
            $return = $root->nodeValue;
        }

        return $return;
    }
}

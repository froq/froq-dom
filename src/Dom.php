<?php
/**
 * Copyright (c) 2015 · Kerem Güneş
 * Apache License 2.0 <https://opensource.org/licenses/apache-2.0>
 */
declare(strict_types=1);

namespace froq\dom;

use froq\dom\{Document, DomDocument, XmlDocument, HtmlDocument};

/**
 * Dom.
 *
 * @package froq\dom
 * @object  froq\dom\Dom
 * @author  Kerem Güneş <k-gun@mail.com>
 * @since   3.0
 * @static
 */
final class Dom
{
    /**
     * Create xml document.
     * @param  array|null  $data
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
     * Create html document.
     * @param  array|null $data
     * @return froq\dom\HtmlDocument
     */
    public static function createHtmlDocument(array $data = null): HtmlDocument
    {
        return new HtmlDocument($data);
    }

    /**
     * Parse xml.
     * @param  any        $xml
     * @param  array|null $options
     * @return any|null
     */
    public static function parseXml($xml, array $options = null)
    {
        if ($xml === '')   return null;
        if ($xml === null) return null;

        $root = $xml;
        static $error, $xmlProperties, $toObject;

        if (is_string($root)) {
            $root = new DomDocument();
            $root->loadXml($xml, $options);
        }

        $ret = [];

        // Some speed...
        if ($xmlProperties === null) {
            $xmlProperties = [];
            if ($root->nodeType == XML_DOCUMENT_NODE) {
                // Add real root tag, not #document.
                $xmlProperties['@root'] = $root->firstChild->tagName ?? null;
                $xmlProperties['@error'] = $error ?: null;
                $xmlProperties['version'] = $root->xmlVersion;
                $xmlProperties['encoding'] = $root->xmlEncoding;
            }
            $ret['@xml'] = $xmlProperties;
        }
        if ($toObject === null) {
            $toObject = function ($in) use (&$toObject) {
                $in = (object) $in;
                foreach ($in as $key => $value) {
                    $in->{$key} = is_array($value) ? $toObject($value) : $value;
                }
                return $in;
            };
        }

        if ($root->hasAttributes()) {
            $attributes = $root->attributes;
            foreach ($attributes as $attribute) {
                $ret['@attributes'][$attribute->name] = $attribute->value;
            }
        }

        if ($root->hasChildNodes()) {
            $nodes = $root->childNodes;
            if ($nodes->length == 1) {
                $node = $nodes->item(0);
                if ($node->nodeType == XML_TEXT_NODE) {
                    $ret['@value'] = $node->nodeValue;
                    return count($ret) == 1 ? $ret['@value'] : $ret;
                }
            }

            $groups = [];
            foreach ($nodes as $node) {
                $nodeName = $node->nodeName;
                if (!isset($ret[$nodeName])) {
                    $ret[$nodeName] = self::parseXml($node, $options);
                    // Single node.
                    if ($ret[$nodeName] == []) {
                        $ret[$nodeName] = null;
                    }
                } else {
                    // Multi nodes.
                    if (!isset($groups[$nodeName])) {
                        $groups[$nodeName] = 1;
                        $ret[$nodeName] = [$ret[$nodeName]];
                    }
                    $ret[$nodeName][] = self::parseXml($node, $options);
                }
            }
        } elseif ($root->nodeType == XML_COMMENT_NODE) {
            $ret = $root->nodeValue;
        }

        $assoc = $options['assoc'] ?? true;
        if (!$assoc && is_array($ret)) {
            $ret = $toObject($ret);
        }

        return $ret;
    }
}

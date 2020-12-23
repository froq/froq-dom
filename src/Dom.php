<?php
/**
 * Copyright (c) 2015 · Kerem Güneş
 * Apache License 2.0 · http://github.com/froq/froq-dom
 */
declare(strict_types=1);

namespace froq\dom;

use froq\dom\{Document, DomDocument, XmlDocument, HtmlDocument};
use DOMNode;

/**
 * Dom.
 *
 * Represents a factory entity for XmlDocument/HtmlDocument classes, and contains a parser method for parsing
 * XML documents.
 *
 * @package froq\dom
 * @object  froq\dom\Dom
 * @author  Kerem Güneş
 * @since   3.0
 * @static
 */
final class Dom
{
    /**
     * Create an XML document.
     *
     * @param  array|null  $data
     * @param  string|null $version
     * @param  string|null $encoding
     * @return froq\dom\XmlDocument
     */
    public static function createXmlDocument(array $data = null, string $version = null, string $encoding = null): XmlDocument
    {
        return new XmlDocument($data, $version, $encoding);
    }

    /**
     * Create an HTML document.
     *
     * @param  array|null $data
     * @return froq\dom\HtmlDocument
     */
    public static function createHtmlDocument(array $data = null): HtmlDocument
    {
        return new HtmlDocument($data);
    }

    /**
     * Parse XML string or DOMNode.
     *
     * @param  string|DOMNode $xml
     * @param  array|null     $options
     * @return array|object|string|null
     */
    public static function parseXml(string|DOMNode $xml, array $options = null): array|object|string|null
    {
        if ($xml === '')   return null;
        if ($xml === null) return null;

        $root = $xml;

        if (is_string($root)) {
            $root = new DomDocument();
            $root->loadXmlSource($xml, $options);
        }

        $ret = [];

        if ($root->nodeType == XML_DOCUMENT_NODE) {
            // Add real root tag, not #document.
            $ret['@xml']['@root']    = $root->firstChild->nodeName ?? null;
            $ret['@xml']['version']  = $root->version;
            $ret['@xml']['encoding'] = $root->encoding;
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

        // Objectify.
        if (!($options['assoc'] ?? true) && is_array($ret)) {
            $ret = json_decode(json_encode($ret));
        }

        return $ret;
    }
}

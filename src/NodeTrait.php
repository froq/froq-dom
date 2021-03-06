<?php
/**
 * Copyright (c) 2015 · Kerem Güneş
 * Apache License 2.0 · http://github.com/froq/froq-dom
 */
declare(strict_types=1);

namespace froq\dom;

use froq\dom\{NodeList, Document};
use DOMNode, DOMNodeList;

/**
 * Node Trait.
 *
 * Represents a trait entity that provides some utility methods to DomDocument/DomElement classes.
 *
 * @package froq\dom
 * @object  froq\dom\NodeTrait
 * @author  Kerem Güneş
 * @since   4.0
 * @internal
 */
trait NodeTrait
{
    /**
     * Get node tag.
     *
     * @return string
     */
    public function tag(): string
    {
        return strtolower($this->nodeName);
    }

    /**
     * Get node path.
     *
     * @return string
     */
    public function path(): string
    {
        return $this->getNodePath();
    }

    /**
     * Get text contents.
     *
     * @return string|null
     */
    public function text(): string|null
    {
        $text = trim($this->textContent);

        return ($text !== '') ? $text : null;
    }

    /**
     * Get HTML contents.
     *
     * @param  bool $outer
     * @return string|null
     */
    public function html(bool $outer = false): string|null
    {
        // Also a document ($this) may be given.
        $doc = $this->ownerDocument ?? $this;
        $docType = $doc->getType();

        $html = '';

        if ($outer) {
            $html = ($docType == Document::TYPE_XML)
                ? $doc->saveXml($this) : $doc->saveHtml($this);
        } else {
            foreach ($this->childNodes as $node){
                if ($node->nodeType == XML_TEXT_NODE) {
                    $html .= $node->textContent;
                } elseif ($node->nodeType == XML_ELEMENT_NODE) {
                    $html .= ($docType == Document::TYPE_XML)
                        ? $doc->saveXml($node) : $doc->saveHtml($node);
                }
            }
        }

        $html = trim($html);

        return ($html !== '') ? $html : null;
    }

    /**
     * Get an attribute.
     *
     * @param  string $name
     * @param  bool   $useBaseUrl
     * @return string|null
     */
    public function attribute(string $name, bool $useBaseUrl = false): string|null
    {
        // Prevent returning "" from non-exist attributes.
        $value = $this->getAttribute($name);

        if ($value != '' && $useBaseUrl) {
            static $tags = ['a', 'img', 'link', 'iframe', 'audio', 'video', 'area',
                'track', 'embed', 'source', 'area', 'object'];

            if (in_array($this->tag(), $tags)) {
                $baseUrl = (string) $this->ownerDocument->getBaseUrl();
                $baseUrlParts = parse_url($baseUrl);

                if (isset($baseUrlParts['scheme'], $baseUrlParts['host'])) {
                    $value = ($value[0] == '/') // Use root for links that starts with "/".
                        ? $baseUrlParts['scheme'] .'://'. $baseUrlParts['host'] . $value
                        : $baseUrl . $value;
                }
            }
        }

        return $value;
    }

    /**
     * Get all attributes.
     *
     * @return array|null
     */
    public function attributes(): array|null
    {
        if ($this->hasAttributes()) {
            foreach ($this->attributes as $attribute) {
                $attributes[$attribute->name] = $attribute->value;
            }

            return $attributes;
        }

        return null;
    }

    /**
     * Get content value.
     *
     * @return string|null
     */
    public function value(): string|null
    {
        switch ($this->tag()) {
            case 'input':
                $type = $this->getAttribute('type');
                if ($type == 'radio' || $type == 'checkbox') {
                    return $this->hasAttribute('checked')
                         ? $this->getAttribute('value') : null;
                }
                return $this->getAttribute('value');
            case 'option':
                return $this->hasAttribute('selected')
                     ? $this->getAttribute('value') : null;
            case 'select':
                return ($option = $this->find('//option[@value][@selected]'))
                     ? $option->getAttribute('value') : null;
            case 'img': case 'image': case 'iframe':
            case 'audio': case 'video': case 'track':
            case 'embed': case 'source':
                return $this->getAttribute('src');
            case 'data': case 'meter':
                return $this->getAttribute('value');
            case 'time':
                return $this->getAttribute('datetime');
            case 'meta':
                return $this->getAttribute('content');
        }

        return $this->text();
    }

    /**
     * Get prev node.
     *
     * @return DOMNode|null
     */
    public function prev(): DOMNode|null
    {
        $prev = $this->previousSibling;

        while ($prev) {
            if ($prev->nodeType == XML_ELEMENT_NODE) {
                return $prev;
            }
            $prev = $prev->previousSibling;
        }

        return null;
    }

    /**
     * Get all prev nodes.
     *
     * @return DOMNodeList|null
     */
    public function prevAll(): DOMNodeList|null
    {
        $prev = $this->previousSibling;
        $prevs = [];

        while ($prev) {
            if ($prev->nodeType == XML_ELEMENT_NODE) {
                $prevs[] = $prev;
            }
            $prev = $prev->previousSibling;
        }

        return $prevs ? new NodeList($prevs) : null;
    }

    /**
     * Get next node.
     *
     * @return DOMNode|null
     */
    public function next(): DOMNode|null
    {
        $next = $this->nextSibling;

        while ($next) {
            if ($next->nodeType == XML_ELEMENT_NODE) {
                return $next;
            }
            $next = $next->nextSibling;
        }

        return null;
    }

    /**
     * Get all next nodes.
     *
     * @return DOMNodeList|null
     */
    public function nextAll(): DOMNodeList|null
    {
        $next = $this->nextSibling;
        $nexts = [];

        while ($next) {
            if ($next->nodeType == XML_ELEMENT_NODE) {
                $nexts[] = $next;
            }
            $next = $next->nextSibling;
        }

        return $nexts ? new NodeList($nexts) : null;
    }

    /**
     * Get parent node.
     *
     * @return DOMNode|null
     */
    public function parent(): DOMNode|null
    {
        return $this->parents(1)[0] ?? null;
    }

    /**
     * Get all parents.
     *
     * @param  int|null $limit
     * @return DOMNodeList|null
     */
    public function parents(int $limit = null): DOMNodeList|null
    {
        static $parentTypes = [XML_ELEMENT_NODE, XML_DOCUMENT_NODE, XML_HTML_DOCUMENT_NODE];

        [$parents, $parent, $i] = [[], $this->parentNode, 1];

        while ($parent && in_array($parent->nodeType, $parentTypes, true)) {
            [$parents[], $parent] = [$parent, $parent->parentNode];
            if ($limit && $i++ >= $limit) {
                break;
            }
        }

        return $parents ? new NodeList($parents) : null;
    }

    /**
     * Get a child node.
     *
     * @param  int $i
     * @return DOMNode|null
     */
    public function child(int $i): DOMNode|null
    {
        $children = $this->children();

        return $children[$i] ?? null;
    }

    /**
     * Get all children.
     *
     * @return DOMNodeList|null
     */
    public function children(): DOMNodeList|null
    {
        $nodes = [];

        if ($this->hasChildNodes()) {
            foreach ($this->childNodes as $node) {
                if ($node->nodeType == XML_ELEMENT_NODE) {
                    $nodes[] = $node;
                }
            }
        }

        return $nodes ? new NodeList($nodes) : null;
    }

    /**
     * @override
     */
    public function getAttribute(string $name): string|null
    {
        // Prevent returning "" from non-exist attributes.
        return parent::hasAttribute($name) ? parent::getAttribute($name) : null;
    }
}

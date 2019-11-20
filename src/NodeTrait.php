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

use froq\dom\{NodeList, Document};
use DOMNode, DOMNodeList;

// Suppress useless 'Declaration of ...' warnings.
@(function () {

/**
 * Node Trait.
 * @package froq\dom
 * @object  froq\dom\NodeTrait
 * @author  Kerem Güneş <k-gun@mail.com>
 * @since   4.0
 */
trait NodeTrait
{
    /**
     * Tag.
     * @return string
     */
    public function tag(): string
    {
        return strtolower($this->nodeName);
    }

    /**
     * Path.
     * @return string
     */
    public function path(): string
    {
        return $this->getNodePath();
    }

    /**
     * Text.
     * @return ?string
     */
    public function text(): ?string
    {
        $text = trim($this->textContent);

        return ($text !== '') ? $text : null;
    }

    /**
     * Html.
     * @param  bool $outer
     * @return ?string
     */
    public function html(bool $outer = false): ?string
    {
        // Also a document ($this) could be given.
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
     * Attribute.
     * @param  string $name
     * @param  bool   $useBaseUrl
     * @return ?string
     */
    public function attribute(string $name, bool $useBaseUrl = false): ?string
    {
        // Prevent returning "" from non-exist attributes.
        $value = $this->getAttribute($name);

        if ($value != null && $useBaseUrl) {
            static $tags = ['a', 'img', 'link', 'iframe', 'audio', 'video', 'area',
                'track', 'embed', 'source', 'area', 'object'];

            if (in_array($this->tag(), $tags)) {
                $baseUrl = (string) $this->ownerDocument->getBaseUrl();
                $baseUrlParts = parse_url($baseUrl);

                if (isset($baseUrlParts['scheme'], $baseUrlParts['host'])) {
                    // Use root for links that starts with "/".
                    if ($value[0] == '/') {
                        $value = $baseUrlParts['scheme'] .'://'. $baseUrlParts['host'] . $value;
                    } else {
                        $value = $baseUrl . $value;
                    }
                }
            }
        }

        return $value;
    }

    /**
     * Attributes.
     * @return ?array
     */
    public function attributes(): ?array
    {
        if ($this->hasAttributes()) {
            foreach ($this->attributes as $attribute) {
                $attributes[$attribute->name] = $attribute->value;
            }
        }

        return $attributes ?? null;
    }

    /**
     * Content.
     * @return ?string
     */
    public function value(): ?string
    {
        switch ($this->tag()) {
            case 'meta':
                return $this->getAttribute('content');
            case 'input':
                $type = $this->getAttribute('type');
                if (($type == 'radio' || $type == 'checkbox')) {
                    return $this->hasAttribute('value')
                        ? $this->getAttribute('value') : null;
                }
                return $this->getAttribute('value');
            case 'option':
                return $this->hasAttribute('selected')
                    ? $this->getAttribute('value') : null;
            case 'select':
                $options = $this->findAll('//option[@value][@selected]');
                return ($options && $options->count())
                    ? $options->first()->getAttribute('value') : null;
            case 'img': case 'iframe':
            case 'audio': case 'video':
            case 'track': case 'embed': case 'source':
                return $this->getAttribute('src');
            case 'data': case 'meter':
                return $this->getAttribute('value');
            case 'time':
                return $this->getAttribute('datetime');
        }

        return $this->text();
    }

    /**
     * Prev.
     * @return ?DOMNode
     */
    public function prev(): ?DOMNode
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
     * Prev all.
     * @return ?DOMNodeList
     */
    public function prevAll(): ?DOMNodeList
    {
        $prev = $this->previousSibling; $prevs = [];

        while ($prev) {
            if ($prev->nodeType == XML_ELEMENT_NODE) {
                $prevs[] = $prev;
            }
            $prev = $prev->previousSibling;
        }

        return $prevs ? new NodeList($prevs) : null;
    }

    /**
     * Next.
     * @return ?DOMNode
     */
    public function next(): ?DOMNode
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
     * Next all.
     * @return ?DOMNodeList
     */
    public function nextAll(): ?DOMNodeList
    {
        $next = $this->nextSibling; $nexts = [];

        while ($next) {
            if ($next->nodeType == XML_ELEMENT_NODE) {
                $nexts[] = $next;
            }
            $next = $next->nextSibling;
        }

        return $nexts ? new NodeList($nexts) : null;
    }

    /**
     * Parent.
     * @return ?DOMNode
     */
    public function parent(): ?DOMNode
    {
        $parents = $this->parents();

        return $parents[0] ?? null;
    }

    /**
     * Parents.
     * @return ?DOMNode
     */
    public function parents(): ?DOMNodeList
    {
        static $parentTypes = [XML_ELEMENT_NODE, XML_DOCUMENT_NODE, XML_HTML_DOCUMENT_NODE];

        $parent = $this->parentNode;
        $parents = [];

        while ($parent && in_array($parent->nodeType, $parentTypes)) {
            $parents[] = $parent;
            $parent = $parent->parentNode;
        }

        return $parents ? new NodeList($parents) : null;
    }

    /**
     * Child.
     * @param  int $i
     * @return ?DOMNode
     */
    public function child(int $i): ?DOMNode
    {
        $children = $this->children();

        return $children[$i] ?? null;
    }

    /**
     * Children.
     * @return ?DOMNodeList
     */
    public function children(): ?DOMNodeList
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
    public function getAttribute(string $name): ?string
    {
        // Prevent returning "" from non-exist attributes.
        return parent::hasAttribute($name) ? parent::getAttribute($name) : null;
    }
}

})();

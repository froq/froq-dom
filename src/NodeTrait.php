<?php declare(strict_types=1);
/**
 * Copyright (c) 2015 · Kerem Güneş
 * Apache License 2.0 · http://github.com/froq/froq-dom
 */
namespace froq\dom;

use DOMNode;

/**
 * A trait, provides some find utilities for `DomDocument` and `DomElement` classes.
 *
 * @package froq\dom
 * @class   froq\dom\NodeTrait
 * @author  Kerem Güneş
 * @since   4.0
 * @internal
 */
trait NodeTrait
{
    /**
     * @magic
     */
    public function __toString(): string
    {
        return $this->textContent;
    }

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
     * @param  callable|null $apply
     * @return string|null
     */
    public function text(callable $apply = null): string|null
    {
        if ($apply) {
            return $apply($this->textContent);
        }

        return $this->textContent;
    }

    /**
     * Get HTML contents.
     *
     * @param  bool          $outer
     * @param  callable|null $apply
     * @return string|null
     */
    public function html(bool $outer = false, callable $apply = null): string|null
    {
        // Also a document ($this) may be given.
        $doc     = $this->ownerDocument ?? $this;
        $docType = $doc->getType();

        if ($outer) {
            $html = ($docType === DOMDocument::TYPE_XML)
                ? $doc->saveXML($this) : $doc->saveHTML($this);
        } else {
            $html = '';

            foreach ($this->childNodes as $node){
                if ($node->nodeType === XML_TEXT_NODE) {
                    $html .= $node->textContent;
                } elseif ($node->nodeType === XML_ELEMENT_NODE) {
                    $html .= ($docType === Document::TYPE_XML)
                        ? $doc->saveXML($node) : $doc->saveHTML($node);
                }
            }
        }

        if ($apply) {
            return $apply($html);
        }

        return ($html !== false) ? $html : null;
    }

    /**
     * Set/get an attribute, optionally apply given callable to old value.
     *
     * @param  string               $name
     * @param  string|callable|null $value
     * @return string|null
     */
    public function attr(string $name, string|callable $value = null): string|null
    {
        if (func_num_args() === 1) {
            return $this->getAttribute($name);
        }

        if (is_callable($value)) {
            return $value($this->getAttribute($name));
        }

        $oldValue = $this->getAttribute($name);
        $this->setAttribute($name, $value);

        return $oldValue;
    }

    /**
     * Get an attribute, optionally with given base URL.
     *
     * @param  string      $name
     * @param  string|bool $baseUrl
     * @return string|null
     */
    public function attribute(string $name, string|bool $baseUrl = false): string|null
    {
        $value = (string) $this->getAttribute($name);

        if ($value === '') {
            return null;
        }

        if ($baseUrl) {
            static $tags = ['a', 'img', 'link', 'iframe', 'audio', 'video', 'area',
                'track', 'embed', 'source', 'area', 'object'];

            if (in_array($this->tag(), $tags, true)) {
                // Use document url.
                if ($baseUrl === true) {
                    $baseUrl = (string) $this->ownerDocument->getBaseUrl();
                }

                $url = http_parse_url($baseUrl);

                if (isset($url['origin'])) {
                    $value = ($value[0] === '/') // Use root for links starting with "/".
                        ? $url['origin'] . $value
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
        return $this->getAttributes() ?: null;
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
                if ($type === 'radio' || $type === 'checkbox') {
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
     * @return froq\dom\DomElement|null
     */
    public function prev(): DomElement|null
    {
        $prev = $this->previousSibling;

        while ($prev) {
            if ($prev->nodeType === XML_ELEMENT_NODE) {
                return $prev;
            }
            $prev = $prev->previousSibling;
        }

        return null;
    }

    /**
     * Get all prev nodes.
     *
     * @return froq\dom\DomElementList|null
     */
    public function prevAll(): DomElementList|null
    {
        $prev = $this->previousSibling;
        $prevs = [];

        while ($prev) {
            if ($prev->nodeType === XML_ELEMENT_NODE) {
                $prevs[] = $prev;
            }
            $prev = $prev->previousSibling;
        }

        return $prevs ? new DomElementList($prevs) : null;
    }

    /**
     * Get next node.
     *
     * @return froq\dom\DomElement|null
     */
    public function next(): DomElement|null
    {
        $next = $this->nextSibling;

        while ($next) {
            if ($next->nodeType === XML_ELEMENT_NODE) {
                return $next;
            }
            $next = $next->nextSibling;
        }

        return null;
    }

    /**
     * Get all next nodes.
     *
     * @return froq\dom\DomElementList|null
     */
    public function nextAll(): DomElementList|null
    {
        $next = $this->nextSibling;
        $nexts = [];

        while ($next) {
            if ($next->nodeType === XML_ELEMENT_NODE) {
                $nexts[] = $next;
            }
            $next = $next->nextSibling;
        }

        return $nexts ? new DomElementList($nexts) : null;
    }

    /**
     * Get a child node.
     *
     * @param  int $i
     * @return froq\dom\DomElement|null
     */
    public function child(int $i): DomElement|null
    {
        return $this->children()[$i] ?? null;
    }

    /**
     * Get all children.
     *
     * @return froq\dom\DomElementList|null
     */
    public function children(): DomElementList|null
    {
        $child = $this->firstChild;
        $children = [];

        while ($child) {
            if ($child->nodeType === XML_ELEMENT_NODE) {
                $children[] = $child;
            }
            $child = $child->nextSibling;
        }

        return $children ? new DomElementList($children) : null;
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
     * Get all parents with/without given limit.
     *
     * @param  int|null $limit
     * @return froq\dom\DomNodeList|null
     */
    public function parents(int $limit = null): DomNodeList|null
    {
        $parent = $this->parentNode;
        $parents = [];
        $i = 0;

        while ($parent) {
            if ($parent->nodeType === XML_ELEMENT_NODE ||
                $parent->nodeType === XML_DOCUMENT_NODE ||
                $parent->nodeType === XML_HTML_DOCUMENT_NODE) {
                $parents[] = $parent;
            }
            $parent = $parent->parentNode;

            if ($limit && ++$i >= $limit) {
                break;
            }
        }

        return $parents ? new DomNodeList($parents) : null;
    }

    /**
     * Set content.
     *
     * @param  string $content
     * @return void
     */
    public function setContent(string $content): void
    {
        $this->textContent = $content;
    }

    /**
     * Get content.
     *
     * @return string
     */
    public function getContent(): string
    {
        return $this->textContent;
    }

    /**
     * Set given attribute map.
     *
     * @param  array $attributes
     * @return self
     * @since  5.4
     */
    public function setAttributes(array $attributes): self
    {
        foreach ($attributes as $name => $value) {
            $this->setAttribute($name, $value);
        }
    }

    /**
     * Get attribute map, optionally by given names.
     *
     * @param  array|null $names
     * @return array
     * @since  5.4
     */
    public function getAttributes(array $names = null): array
    {
        $attributes = [];

        if ($this->hasAttributes()) {
            foreach ($this->attributes as $attribute) {
                $attributes[$attribute->name] = $attribute->value;
            }

            if ($names) {
                $attributes = array_filter($attributes,
                    fn(string $name): bool => in_array($name, $names),
                    ARRAY_FILTER_USE_KEY
                );
            }

        }

        return $attributes;
    }

    /**
     * @override
     */
    public function setAttribute(string $name, string|null $value): void
    {
        if ($value !== null) {
            parent::setAttribute($name, $value);
        } else {
            parent::removeAttribute($name);
        }
    }

    /**
     * @override
     */
    #[\ReturnTypeWillChange]
    public function getAttribute(string $name): string|null
    {
        // Prevent returning "" from non-exist attributes.
        return parent::hasAttribute($name) ? parent::getAttribute($name) : null;
    }
}

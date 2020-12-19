<?php
/**
 * Copyright (c) 2015 · Kerem Güneş
 * Apache License 2.0 <https://opensource.org/licenses/apache-2.0>
 */
declare(strict_types=1);

namespace froq\dom;

use froq\dom\DomException;
use froq\common\interface\Stringable;

/**
 * Document.
 *
 * Represents a base document entity for XmlDocument/HtmlDocument classes.
 *
 * @package froq\dom
 * @object  froq\dom\Document
 * @author  Kerem Güneş <k-gun@mail.com>
 * @since   3.0
 */
class Document implements Stringable
{
    /**
     * Types.
     * @const string
     */
    public const TYPE_XML = 'xml', TYPE_HTML = 'html';

    /** @var string */
    protected string $type;

    /** @var array */
    protected array $data;

    /**
     * Constructor.
     *
     * @param string     $type
     * @param array|null $data
     */
    public function __construct(string $type, array $data = null)
    {
        $this->setType($type);
        $this->setData($data ?? []);
    }

    /**
     * Set document type.
     *
     * @param  string $type
     * @return self
     * @throws froq\dom\DomException
     */
    public final function setType(string $type): self
    {
        if ($type != self::TYPE_XML && $type != self::TYPE_HTML) {
            throw new DomException("Invalid type, type must be 'xml' or 'html'");
        }

        $this->type = $type;

        return $this;
    }

    /**
     * Get document type.
     *
     * @return string
     */
    public final function getType(): string
    {
        return $this->type;
    }

    /**
     * Set document data.
     *
     * @param  array $data
     * @return self
     */
    public final function setData(array $data): self
    {
        $this->data = $data;

        return $this;
    }

    /**
     * Get document data.
     *
     * @return array
     */
    public final function getData(): array
    {
        return $this->data;
    }

    /**
     * @inheritDoc froq\common\interface\Stringable
     *
     * @param  bool   $indent
     * @param  string $indentString
     * @return string
     * @throws froq\dom\DomException
     */
    public final function toString(bool $indent = false, string $indentString = "\t"): string
    {
        $newLine = "\n";
        if (!$indent) {
            $newLine = '';
            $indentString = '';
        }

        $ret = '';

        if ($this->type == self::TYPE_HTML) {
            $ret = '<!DOCTYPE html>'. $newLine;
        } elseif ($this->type == self::TYPE_XML) {
            $ret = '<?xml version="'. $this->version .'" encoding="'. $this->encoding .'"?>'. $newLine;
        }

        $root = $this->data['@root'] ?? null;
        $root || throw new DomException("Invalid document data, no '@root' field found in given data");

        // Eg: [name, content?, @nodes?, @attributes?, @selfClosing?].
        @ [$rootName, $rootContent] = $root;
        $rootName || throw new DomException("Invalid document data, no '@root' tag field found in given data");

        $nodes = $root['@nodes'] ?? null;
        $attributes = $root['@attributes'] ?? null;
        $selfClosing = $root['@selfClosing'] ?? false; // Not usual but valid.

        // Open root tag.
        $ret .= "<{$rootName}";

        // Add attributes.
        if ($attributes != null) {
            $ret .= $this->generateAttributeString($attributes);
        }

        if ($selfClosing) {
            $ret .= " />{$newLine}"; // Value and nodes discarded.
        } else {
            $ret .= ">";

            if ($rootContent !== null && $rootContent !== '') {
                $rootContent = json_encode($rootContent, JSON_UNESCAPED_SLASHES | JSON_PRESERVE_ZERO_FRACTION);

                // Escape (<,>).
                $rootContent = str_replace(['<', '>'], ['&lt;', '&gt;'], trim($rootContent, '"'));

                $ret .= $newLine . $indentString . $rootContent;
                if ($nodes == null) {
                    $ret .= $newLine;
                }
            }

            // Add nodes.
            if ($nodes != null) {
                if ($newLine == '') {
                    foreach ($nodes as $node) {
                        $ret .= $this->generateNodeString($node, '', '', 0);
                    }
                } else {
                    $ret .= $newLine;
                    foreach ($nodes as $node) {
                        $ret .= $indentString;
                        $ret .= $this->generateNodeString($node, $newLine, $indentString, 1);
                    }
                }
            }

            // Close root tag.
            $ret .= "</{$rootName}>{$newLine}";
        }

        return $ret;
    }

    /**
     * Generate node string from a node.
     *
     * @param  array  $node
     * @param  string $newLine
     * @param  string $indentString
     * @param  int    $indentCount @internal
     * @return string
     */
    private function generateNodeString(array $node, string $newLine = '', string $indentString = '',
        int $indentCount = 1): string
    {
        // Eg: [name, content?, @nodes?, @attributes?, @selfClosing?].
        @ [$name, $content] = $node;
        $nodes = $node['@nodes'] ?? null;
        $attributes = $node['@attributes'] ?? null;
        $selfClosing = $node['@selfClosing'] ?? false;

        // Open tag.
        $ret = "<{$name}";

        // Add attributes.
        if ($attributes != null) {
            $ret .= $this->generateAttributeString($attributes);
        }

        if ($selfClosing) {
            $ret .= " />{$newLine}"; // Content and nodes discarded.
        } else {
            $ret .= ">";

            if ($content !== null && $content !== '') {
                $content = json_encode($content, JSON_UNESCAPED_SLASHES | JSON_PRESERVE_ZERO_FRACTION);

                // Escape (<,>).
                $content = str_replace(['<', '>'], ['&lt;', '&gt;'], trim($content, '"'));

                if ($nodes == null) {
                    $ret .= $content;
                } else {
                    $ret .= $newLine . str_repeat($indentString, $indentCount + 1) . $content;
                }
            }

            if ($nodes != null) {
                if ($newLine != null) {
                    $ret .= $newLine;
                    ++$indentCount;
                    foreach ($nodes as $node) {
                        $ret .= str_repeat($indentString, $indentCount);
                        $ret .= $this->generateNodeString($node, $newLine, $indentString, $indentCount);
                    }
                } else {
                    foreach ($nodes as $node) {
                        $ret .= $this->generateNodeString($node, $newLine, $indentString, $indentCount);
                    }
                }
            }

            if ($nodes != null && $newLine != null) {
                $ret .= str_repeat($indentString, --$indentCount);
            }

            // Close tag.
            $ret .= "</{$name}>{$newLine}";
        }

        return $ret;
    }

    /**
     * Generate attribute string from an attribute.
     *
     * @param  array $attributes
     * @return string
     * @throws froq\dom\DomException
     */
    private function generateAttributeString(array $attributes): string
    {
        $ret = '';

        // Validate name (@see http://www.w3.org/TR/2008/REC-xml-20081126/#NT-Name).
        static $notAllowedChars = '\'"=';
        static $namePattern = '~^
            [a-zA-Z_]+(?:[a-zA-Z0-9-_]+)?(?:(?:[:]+)?[a-zA-Z0-9-_:]+)? # name..
          | [:][a-zA-Z0-9-_:]*                                         # name:..
        $~x';

        foreach ($attributes as $name => $value) {
            $name = (string) $name;

            if (strpbrk($name, $notAllowedChars) !== false) {
                throw new DomException("No valid attribute name '%s' given (tip: don't use "
                    . "these characters '%s' in name)", [$name, $notAllowedChars]);
            } elseif (!preg_match($namePattern, $name)) {
                throw new DomException("No valid attribute name '%s' given (tip: use a name "
                    . "that matches with '%s'", [$name, $namePattern]);
            }

            $value = json_encode($value, JSON_UNESCAPED_SLASHES | JSON_PRESERVE_ZERO_FRACTION);

            // Escape (").
            $value = str_replace('"', '&#34;', trim($value, '"'));

            $ret .= " {$name}=\"{$value}\"";
        }

        return $ret;
    }
}

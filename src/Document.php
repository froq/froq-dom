<?php
/**
 * Copyright (c) 2015 · Kerem Güneş
 * Apache License 2.0 · http://github.com/froq/froq-dom
 */
declare(strict_types=1);

namespace froq\dom;

/**
 * A base class for `XmlDocument` and `HtmlDocument` classes.
 *
 * @package froq\dom
 * @object  froq\dom\Document
 * @author  Kerem Güneş
 * @since   3.0
 */
class Document implements \Stringable
{
    /**
     * Types.
     * @const string
     */
    public final const TYPE_XML = 'xml', TYPE_HTML = 'html';

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

    /** @magic */
    public function __toString(): string
    {
        return $this->toString();
    }

    /**
     * Set document type.
     *
     * @param  string $type
     * @return self
     * @throws froq\dom\DomException
     */
    public function setType(string $type): self
    {
        if ($type != self::TYPE_XML && $type != self::TYPE_HTML) {
            throw new DomException('Invalid type %s [valids: xml, html]', $type);
        }

        $this->type = $type;

        return $this;
    }

    /**
     * Get document type.
     *
     * @return string
     */
    public function getType(): string
    {
        return $this->type;
    }

    /**
     * Set document data.
     *
     * @param  array $data
     * @return self
     */
    public function setData(array $data): self
    {
        $this->data = $data;

        return $this;
    }

    /**
     * Get document data.
     *
     * @return array
     */
    public function getData(): array
    {
        return $this->data;
    }

    /**
     * Get representation of the document.
     *
     * @param  array|null $options
     * @return string
     * @throws froq\dom\DomException
     */
    public function toString(array $options = null): string
    {
        static $optionsDefault = [
            'indent' => false, 'indentString' => '  ',
        ];

        $options = array_options($options, $optionsDefault);

        $indent       = (bool)   $options['indent'];
        $indentString = (string) $options['indentString'];

        $newLine = "\n";
        if (!$indent) {
            $newLine = $indentString = '';
        }

        $ret = '';

        if ($this->type == self::TYPE_HTML) {
            $ret = '<!DOCTYPE html>';
        } elseif ($this->type == self::TYPE_XML) {
            $ret = sprintf('<?xml version="%s" encoding="%s"?>', $this->version, $this->encoding);
        }

        $ret .= $newLine;

        $root = (array) ($this->data['@root'] ?? null);
        $root || throw new DomException('Invalid document data, no @root field in given data');

        // Eg: [name, content?, @nodes?, @attributes?, @selfClosing?].
        [$rootName, $rootContent] = array_select($root, [0, 1]);
        $rootName || throw new DomException('Invalid document data, no @root tag field in given data');

        $nodes       = $root['@nodes']       ?? null;
        $attributes  = $root['@attributes']  ?? null;
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

        return trim($ret);
    }

    /**
     * Generate node string from a node.
     */
    private function generateNodeString(array $node, string $newLine = '', string $indentString = '',
        int $indentLevel = 1): string
    {
        // Eg: [name, content?, @nodes?, @attributes?, @selfClosing?].
        [$name, $content] = array_select($node, [0, 1]);
        $nodes       = $node['@nodes']       ?? null;
        $attributes  = $node['@attributes']  ?? null;
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
                    $ret .= $newLine . str_repeat($indentString, $indentLevel + 1) . $content;
                }
            }

            if ($nodes != null) {
                if ($newLine != null) {
                    $ret .= $newLine;
                    ++$indentLevel;
                    foreach ($nodes as $node) {
                        $ret .= str_repeat($indentString, $indentLevel);
                        $ret .= $this->generateNodeString($node, $newLine, $indentString, $indentLevel);
                    }
                } else {
                    foreach ($nodes as $node) {
                        $ret .= $this->generateNodeString($node, $newLine, $indentString, $indentLevel);
                    }
                }
            }

            if ($nodes != null && $newLine != null) {
                $ret .= str_repeat($indentString, --$indentLevel);
            }

            // Close tag.
            $ret .= "</{$name}>{$newLine}";
        }

        return $ret;
    }

    /**
     * Generate attribute string from an attribute.
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
                throw new DomException(
                    'Invalid attribute name %q given '.
                    '[tip: don\'t use these characters %q in name]',
                    [$name, $notAllowedChars]
                );
            } elseif (!preg_test($namePattern, $name)) {
                throw new DomException(
                    'Invalid attribute name %q given '.
                    '[tip: use a name that matches with %q',
                    [$name, $namePattern]
                );
            }

            $value = json_encode($value, JSON_UNESCAPED_SLASHES | JSON_PRESERVE_ZERO_FRACTION);

            // Escape (").
            $value = str_replace('"', '&#34;', trim($value, '"'));

            $ret .= " {$name}=\"{$value}\"";
        }

        return $ret;
    }
}

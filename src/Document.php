<?php
/**
 * Copyright (c) 2015 · Kerem Güneş
 * Apache License 2.0 <https://opensource.org/licenses/apache-2.0>
 */
declare(strict_types=1);

namespace froq\dom;

use froq\common\interfaces\Stringable;

/**
 * Document.
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
    public const TYPE_XML  = 'xml',
                 TYPE_HTML = 'html';

    /**
     * Xml version & encoding.
     * @const string
     */
    public const XML_VERSION  = '1.0',
                 XML_ENCODING = 'utf-8';

    /**
     * Type.
     * @var string
     */
    protected string $type;

    /**
     * Data.
     * @var array
     */
    protected array $data;

    /**
     * Xml version.
     * @var string
     */
    protected string $xmlVersion;

    /**
     * Xml encoding.
     * @var string
     */
    protected string $xmlEncoding;

    /**
     * Constructor.
     * @param string      $type
     * @param array|null  $data
     * @param string|null $xmlVersion
     * @param string|null $xmlEncoding
     */
    public function __construct(string $type, array $data = null,
                                string $xmlVersion = null, string $xmlEncoding = null)
    {
        $this->setType($type);
        $this->setData($data ?? []);

        if ($type == self::TYPE_XML) {
            $this->setXmlVersion($xmlVersion ?: self::XML_VERSION);
            $this->setXmlEncoding($xmlEncoding ?: self::XML_ENCODING);
        }
    }

    /**
     * Set type.
     * @param  string $type
     * @return self
     */
    public final function setType(string $type): self
    {
        $this->type = strtolower($type);

        return $this;
    }

    /**
     * Get type.
     * @return string
     */
    public final function getType(): string
    {
        return $this->type;
    }

    /**
     * Set data.
     * @param  array $data
     * @return self
     */
    public final function setData(array $data): self
    {
        $this->data = $data;

        return $this;
    }

    /**
     * Get data.
     * @return array
     */
    public final function getData(): array
    {
        return $this->data;
    }

    /**
     * Set xml version.
     * @param  string $xmlVersion
     * @return self
     */
    public final function setXmlVersion(string $xmlVersion): self
    {
        $this->xmlVersion = $xmlVersion;

        return $this;
    }

    /**
     * Get xml version.
     * @return ?string
     */
    public final function getXmlVersion(): ?string
    {
        return $this->xmlVersion ?? null;
    }

    /**
     * Set xml encoding.
     * @param  string $xmlEncoding
     * @return self
     */
    public final function setXmlEncoding(string $xmlEncoding): self
    {
        $this->xmlEncoding = $xmlEncoding;

        return $this;
    }

    /**
     * Get xml encoding.
     * @return ?string
     */
    public final function getXmlEncoding(): ?string
    {
        return $this->xmlEncoding ?? null;
    }

    /**
     * @inheritDoc froq\common\interfaces\Stringable
     *
     * @param      bool   $indent
     * @param      string $indentString
     * @return     string
     * @throws     froq\dom\DomException
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
            $ret = '<?xml version="'. $this->xmlVersion .'" encoding="'. $this->xmlEncoding .'"?>'. $newLine;
        }
        $root = $this->data['@root'] ?? null;
        if ($root == null) {
            throw new DomException('Invalid document data, no @root field found in given data');
        }

        // Eg: [name, content?, @nodes?, @attributes?, @selfClosing?].
        @ [$rootName, $rootContent] = $root;
        if ($rootName == null) {
            throw new DomException('Invalid document data, no @root tag field found in given data');
        }

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
                if (!is_scalar($rootContent)) {
                    $rootContent = json_encode($rootContent);
                }
                // Escape (<,>).
                $rootContent = str_replace(['<', '>'], ['&lt;', '&gt;'], $rootContent);

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
     * Generate node string.
     * @param  array  $node
     * @param  string $newLine
     * @param  string $indentString
     * @param  int    $indentCount @internal
     * @return string
     */
    private final function generateNodeString(array $node, string $newLine = '',
        string $indentString = '', int $indentCount = 1): string
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
                if (!is_scalar($content)) {
                    $content = json_encode($content);
                }
                // Escape (<,>).
                $content = str_replace(['<', '>'], ['&lt;', '&gt;'], $content);

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
     * Generate attribute string.
     * @param  array $attributes
     * @return string
     * @throws froq\dom\DomException
     */
    private final function generateAttributeString(array $attributes): string
    {
        $ret = '';

        // Validate name (@see http://www.w3.org/TR/2008/REC-xml-20081126/#NT-Name).
        static $notAllowedChars = '\'"=';
        static $namePattern = '~^
            [a-zA-Z_]+(?:[a-zA-Z0-9-_]+)?(?:(?:[:]+)?[a-zA-Z0-9-_:]+)? # name(..)
          | [:][a-zA-Z0-9-_:]*                                         # name:(..)
        $~x';

        foreach ($attributes as $name => $value) {
            $name = (string) $name;

            if (strpbrk($name, $notAllowedChars) !== false) {
                throw new DomException("No valid attribute name '{$name}' given (tip: don't use ".
                    "these characters '{$notAllowedChars}' in name)");
            } elseif (!preg_match($namePattern, $name)) {
                throw new DomException("No valid attribute name '{$name}' given (tip: use a name ".
                    "that matches with '{$namePattern}'");
            }

            if (!is_scalar($value)) {
                $value = json_encode($value);
            }

            // Escape (").
            $value = str_replace('"', '&#34;', $value);

            $ret .= " {$name}=\"{$value}\"";
        }

        return $ret;
    }
}

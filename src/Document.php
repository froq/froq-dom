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

namespace Froq\Dom;

/**
 * @package    Froq
 * @subpackage Froq\Dom
 * @object     Froq\Dom\Document
 * @author     Kerem Güneş <k-gun@mail.com>
 * @since      3.0
 */
/* abstract? */ class Document
{
    /**
     * Types.
     * @const string
     */
    public const TYPE_XML  = 'xml',
                 TYPE_HTML = 'html';

    /**
     * Xml stuff.
     * @const string
     */
    public const XML_VERSION  = '1.0',
                 XML_ENCODING = 'utf-8';

    /**
     * Type.
     * @var string
     */
    protected $type;

    /**
     * Xml version, encoding
     * @var string
     */
    protected $xmlVersion, $xmlEncoding;

    /**
     * Data.
     * @var array
     */
    protected $data;

    /**
     * Constructor.
     * @param string      $type
     * @param array|null  $data
     * @param string|null $xmlVersion
     * @param string|null $xmlEncoding
     */
    public function __construct(string $type, array $data = null, string $xmlVersion = null,
        string $xmlEncoding = null)
    {
        $this->setType($type);
        $data && $this->setData($data);
        $xmlVersion && $this->setXmlVersion($xmlVersion);
        $xmlEncoding && $this->setXmlEncoding($xmlEncoding);
    }

    public function __toString()
    {
        return $this->toString();
    }

    public final function setType(string $type): self
    {
        $this->type = strtolower($type);
        return $this;
    }
    public final function getType(): ?string
    {
        return $this->type;
    }

    public final function setData(array $data): self
    {
        $this->data = $data;
        return $this;
    }
    public final function getData(): ?array
    {
        return $this->data;
    }

    public final function setXmlVersion(string $xmlVersion): self
    {
        $this->xmlVersion = $xmlVersion;
        return $this;
    }
    public final function getXmlVersion(): ?string
    {
        return $this->xmlVersion;
    }

    public final function setXmlEncoding(string $xmlEncoding): self
    {
        $this->xmlEncoding = $xmlEncoding;
        return $this;
    }
    public final function getXmlEncoding(): ?string
    {
        return $this->xmlEncoding;
    }

    /**
     * To string.
     * @param  string $indentString
     * @return string
     */
    public function toString(string $indentString = "\t"): string
    {
        $return = '';
        if ($this->type == self::TYPE_HTML) {
            $return = "<!DOCTYPE html>\n";
        } elseif ($this->type == self::TYPE_XML) {
            $return = "<?xml version=\"". ($this->xmlVersion ?: self::XML_VERSION) ."\"".
                " encoding=\"". ($this->xmlEncoding ?: self::XML_ENCODING) ."\"?>\n";
        }

        $data = $this->data;
        if (empty($data['@root'])) {
            throw new DomException('No @root found in given document data');
        }

        @ [$rootName, $rootValue] = $data['@root'];
        if ($rootName == null) {
            throw new DomException('No @root tag found in given document data');
        }

        $attributes = $data['@root']['@attributes'] ?? null;
        $nodes = $data['@root']['@nodes'] ?? null;
        $selfClosing = $data['@root']['@selfClosing'] ?? false; // not usual but valid

        // open root tag
        $return .= "<{$rootName}";

        // add attributes
        if ($attributes != null) {
            $return .= self::toAttributeString($attributes);
        }

        if ($selfClosing) {
            $return .= " />\n"; // value and nodes discarded
        } else {
            $return .= ">";

            // add nodes
            if ($nodes != null) {
                $return .= "\n";
                foreach ($nodes as $node) {
                    $return .= $indentString;
                    $return .= self::toNodeString($node, $indentString, 1);
                }
            }

            // close root tag
            $return .= "</{$rootName}>\n";
        }

        return $return;
    }

    /**
     * To node string.
     * @param  array  $node
     * @param  string $indentString
     * @param  int    $indentCount @internal
     * @return string
     */
    public static function toNodeString(array $node, string $indentString = "\t",
        int $indentCount = 0): string
    {
        @ [$name, $value] = $node;
        $attributes = $node['@attributes'] ?? null;
        $nodes = $node['@nodes'] ?? null;
        $selfClosing = $node['@selfClosing'] ?? false;

        // open tag
        $return = "<{$name}";

        // add attributes
        if ($attributes != null) {
            $return .= self::toAttributeString($attributes);
        }

        if ($selfClosing) {
            $return .= " />\n"; // value and nodes discarded
        } else {
            $return .= ">";
            $hasNodes = !empty($nodes);
            if ($hasNodes) { // value discarded
                $return .= "\n";
                ++$indentCount;
                foreach ($nodes as $node) {
                    $return .= str_repeat($indentString, $indentCount);
                    $return .= self::toNodeString($node, $indentString, $indentCount);
                }
            } elseif ($value !== null) {
                if (!is_scalar($value)) {
                    $value = json_encode($value);
                }
                // escape [<,>]
                $return .= str_replace(['<', '>'], ['&lt;', '&gt;'], $value);
            }

            if ($hasNodes) {
                $return .= str_repeat($indentString, --$indentCount);
            }

            // close tag
            $return .= "</{$name}>\n";
        }

        return $return;
    }

    /**
     * To attribute string.
     * @param  array $attributes
     * @return string
     */
    public static function toAttributeString(array $attributes): string
    {
        $return = '';

        // http://www.w3.org/TR/2008/REC-xml-20081126/#NT-Name
        $namePattern = '~^
            [a-zA-Z_]+(?:[a-zA-Z0-9-_]+)?(?:(?:[:]+)?[a-zA-Z0-9-_:]+)? # name(..)
          | [:][a-zA-Z0-9-_:]*                                         # name:(..)
        $~x';
        foreach ($attributes as $name => $value) {
            $name = (string) $name;
            if (!preg_match($namePattern, $name)) {
                throw new DomException("No valid attribute name '{$name}' given (tip: use a name ".
                    "that matches with '{$namePattern}'");
            }

            // @todo maybe done by regexp above?
            if (strpbrk($name, '\'"=') !== false) {
                throw new DomException("No valid attribute name '{$name}' given (tip: don't use these ".
                    "characters in name [\",',=])");
            }

            if (!is_scalar($value)) {
                $value = json_encode($value);
            }

            // escape ["]
            $value = str_replace('"', '&#34;', $value);

            $return .= " {$name}=\"{$value}\"";
        }

        return $return;
    }
}

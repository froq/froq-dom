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

use froq\dom\{NodeTrait, NodeList, Document, DomElement, DomException};
use DOMNode, DOMNodeList, DOMXPath, DOMDocument as _DOMDocument;

// Suppress useless 'Declaration of ...' warnings.
@(function () {

/**
 * Dom Document.
 * @package froq\dom
 * @object  froq\dom\DomDocument
 * @author  Kerem Güneş <k-gun@mail.com>
 * @since   4.0
 */
class DomDocument extends _DOMDocument
{
    /**
     * Node trait.
     * @object froq\dom\NodeTrait
     */
    use NodeTrait;

    /**
     * Type.
     * @var string
     */
    private string $type;

    /**
     * Base url.
     * @var string
     */
    private string $baseUrl;

    /**
     * Constructor.
     * @param string      $version
     * @param string      $encoding
     * @param string|null $type
     * @param string|null $baseUrl
     */
    public function __construct(string $version = '', string $encoding = '', string $type = null,
        string $baseUrl = null)
    {
        $type    && $this->setType($type);
        $baseUrl && $this->setBaseUrl($baseUrl);

        // Without this: "PHP Warning:  DOMDocument::registerNodeClass(): Couldn't fetch ..."
        parent::__construct($version, $encoding);

        parent::registerNodeClass('DOMDocument', 'froq\dom\DomDocument');
        parent::registerNodeClass('DOMElement', 'froq\dom\DomElement');
    }

    /**
     * Set type.
     * @param  string $type
     * @return self
     * @throws froq\dom\DomException
     */
    public final function setType(string $type): self
    {
        if ($type != Document::TYPE_XML && $type != Document::TYPE_HTML) {
            throw new DomException('Invalid type, type could be xml or html');
        }

        $this->type = $type;

        return $this;
    }

    /**
     * Get type.
     * @return ?string
     */
    public final function getType(): ?string
    {
        return $this->type ?? null;
    }

    /**
     * Set base url.
     * @param  string $baseUrl
     * @return self
     * @throws froq\dom\DomException
     */
    public final function setBaseUrl(string $baseUrl): self
    {
        $baseUrl = self::prepareUrl($baseUrl);
        if ($baseUrl == null) {
            throw new DomException('Invalid URL');
        }

        $this->baseUrl = $baseUrl;
    }

    /**
     * Get base url.
     * @return ?string
     */
    public final function getBaseUrl(): ?string
    {
        return $this->baseUrl ?? null;
    }

    /**
     * Root.
     * @return ?DOMNode
     */
    public final function root(): ?DOMNode
    {
        return $this->firstChild ?? null;
    }

    /**
     * Load.
     * @param  string     $type
     * @param  string     $source
     * @param  array|null $options
     * @return self
     * @throws froq\dom\DomException
     */
    public final function load(string $type, string $source, array $options = null): self
    {
        // @important
        $this->setType($type);

        static $optionsDefault = [
            'validateOnParse' => false, 'preserveWhiteSpace' => false,
            'strictErrorChecking' => false, 'throwErrors' => true, 'flags' => 0
        ];

        // Html is more quiet.
        if ($type == Document::TYPE_HTML && !isset($options['throwErrors'])) {
            $optionsDefault['throwErrors'] = false;
        }

        @ ['validateOnParse' => $validateOnParse, 'preserveWhiteSpace' => $preserveWhiteSpace,
           'strictErrorChecking' => $strictErrorChecking, 'throwErrors' => $throwErrors, 'flags' => $flags
          ] = array_merge($optionsDefault, $options ?? []);

        $this->validateOnParse = (bool) $validateOnParse;
        $this->preserveWhiteSpace = (bool) $preserveWhiteSpace;
        $this->strictErrorChecking = (bool) $strictErrorChecking;

        $flags = ((int) $flags) | (
            LIBXML_NOERROR | LIBXML_NOWARNING | LIBXML_BIGLINES |
            LIBXML_COMPACT | LIBXML_HTML_NOIMPLIED | LIBXML_HTML_NODEFDTD
        );

        libxml_use_internal_errors(true);

        $source = trim($source);
        if ($type == Document::TYPE_XML) {
            parent::loadXml($source, $flags);
        } elseif ($type == Document::TYPE_HTML) {
            // Workaround for a proper encoding.
            if (strpos($source, '<?xml') !== 0) {
                $source = '<?xml'. $source;
            }
            parent::loadHtml($source, $flags);
        }

        $error = libxml_get_last_error();
        if ($error) {
            libxml_clear_errors();

            $error->file = $error->file ?: 'n/a';
            $error->message = trim($error->message);

            if ($throwErrors) {
                throw new DomException(sprintf(
                    'Parse error: %s (level:%s code:%s column:%s file:%s line:%s)',
                    $error->message, $error->level, $error->code, $error->column, $error->file, $error->line
                ), $error->code);
            }
        }

        if (isset($options['baseUrl'])) {
            $baseUrl = self::prepareUrl($options['baseUrl']);
            if ($baseUrl == null) {
                throw new DomException('Invalid URL');
            }
            $this->baseUrl = $baseUrl;
        } elseif ($base = $this->getBaseUrl()) {
            // May be set by setBaseUrl().
            $this->baseUrl = $base;
        } elseif ($base = $this->find('//base[@href]')) {
            // May be exists (<base href="...">) in dom document.
            $this->baseUrl = (string) $base->getAttribute('href');
        } elseif ($this->baseURI != null) {
            $this->baseUrl = $this->baseURI;
        }

        return $this;
    }

    /**
     * @override
     */
    public final function loadXml(string $source, array $options = null): self
    {
        return $this->load(Document::TYPE_XML, $source, $options);
    }

    /**
     * @override
     */
    public final function loadHtml(string $source, array $options = null): self
    {
        return $this->load(Document::TYPE_HTML, $source, $options);
    }

    /**
     * Xpath.
     * @return DOMXPath
     */
    public final function xpath(): DOMXPath
    {
        return new DOMXPath($this);
    }

    /**
     * Query.
     * @param  string       $query
     * @param  DOMNode|null $root
     * @return ?DOMNodeList
     * @throws froq\dom\DomException
     */
    public final function query(string $query, DOMNode $root = null): ?DOMNodeList
    {
        $query = trim($query);
        if ($query == '') {
            throw new DomException('Empty query given to '. __method__);
        }

        $nodes = $this->xpath()->query($query, $root);

        if ($nodes && $nodes->length > 0) {
            return new NodeList($nodes);
        }

        return null;
    }

    /**
     * Find.
     * @param  string       $query
     * @param  DOMNode|null $root
     * @return ?DOMNode
     */
    public final function find(string $query, DOMNode $root = null): ?DOMNode
    {
        $nodes = $this->query($query, $root);

        return ($nodes && $nodes->count()) ? $nodes->item(0) : null;
    }

    /**
     * Find all.
     * @param  string       $query
     * @param  DOMNode|null $root
     * @return ?DOMNodeList
     */
    public final function findAll(string $query, DOMNode $root = null): ?DOMNodeList
    {
        $nodes = $this->query($query, $root);

        return ($nodes && $nodes->count()) ? $nodes : null;
    }

    /**
     * Find by id.
     * @param  string $id
     * @return ?DOMNode
     */
    public final function findById(string $id): ?DOMNode
    {
        return $this->find("//*[@id='{$id}']");
    }

    /**
     * Find by name.
     * @param  string $name
     * @return ?DOMNode
     */
    public final function findByName(string $name): ?DOMNode
    {
        return $this->find("//*[@name='{$name}']");
    }

    /**
     * Find by tag.
     * @param  string       $tag
     * @param  DOMNode|null $root
     * @return ?DOMNodeList
     */
    public final function findByTag(string $tag, DOMNode $root = null): ?DOMNodeList
    {
        return ($root == null) // Root needs (.) first in query.
            ? $this->findAll("//{$tag}")
            : $this->findAll(".//{$tag}", $root);
    }

    /**
     * Find by class.
     * @param  string       $class
     * @param  DOMNode|null $root
     * @return ?DOMNodeList
     */
    public final function findByClass(string $class, DOMNode $root = null): ?DOMNodeList
    {
        return ($root == null) // Root needs (.) first in query.
            ? $this->findAll("//*[contains(@class, '{$class}')]")
            : $this->findAll(".//*[contains(@class, '{$class}')]", $root);
    }

    /**
     * Find by attribute.
     * @param  string       $name
     * @param  string|null  $value
     * @param  DOMNode|null $root
     * @return ?DOMNodeList
     */
    public final function findByAttribute(string $name, string $value = null, DOMNode $root = null): ?DOMNodeList
    {
        if ($value === null) {
            return ($root == null) // Root needs (.) first in query.
                ? $this->findAll("//*[@{$name}]")
                : $this->findAll(".//*[@{$name}]", $root);
        } else {
            $value = addcslashes($value, '"');

            return ($root == null) // Root needs (.) first in query.
                ? $this->findAll("//*[@{$name}='{$value}']")
                : $this->findAll(".//*[@{$name}='{$value}']", $root);
        }
    }

    /**
     * Parse url.
     * @param  string $url
     * @return ?string
     */
    private static final function prepareUrl(string $url): ?string
    {
        preg_match('~^(?:(?<scheme>\w+://|//))?
                      (?:(?<host>[\w\.\-]+\.\w{2,}))
                      (?:(?<rest>/.*))?~x', $url, $match);

        if (empty($match)) {
            return null;
        }

        // Ensure scheme.
        if (empty($match['scheme']) || $match['scheme'] == '//') {
            $match['scheme'] = 'http://';
        }

        return $match['scheme'] . $match['host'] . $match['rest'];
    }
}

})();

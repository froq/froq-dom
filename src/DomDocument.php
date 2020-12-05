<?php
/**
 * Copyright (c) 2015 · Kerem Güneş
 * Apache License 2.0 <https://opensource.org/licenses/apache-2.0>
 */
declare(strict_types=1);

namespace froq\dom;

use froq\dom\{DomException, NodeTrait, NodeList, Document, DomElement};
use DOMNode, DOMNodeList, DOMXPath, DOMDocument as _DOMDocument;

/**
 * Dom Document.
 *
 * Represents a read-only DOM document entity that provides a DOMElement structure with additional
 * utility methods such find(), findAll() etc. and NodeTrait methods, for loading XML/HTML documents
 * and querying nodes via XPath utilities.
 *
 * @package froq\dom
 * @object  froq\dom\DomDocument
 * @author  Kerem Güneş <k-gun@mail.com>
 * @since   4.0
 */
class DomDocument extends _DOMDocument
{
    /**
     * Node trait.
     * @see froq\dom\NodeTrait
     */
    use NodeTrait;

    /** @var string */
    private string $type;

    /** @var string */
    private string $baseUrl;

    /**
     * Constructor.
     *
     * @param string      $version
     * @param string      $encoding
     * @param string|null $type
     * @param string|null $baseUrl
     */
    public function __construct(string $version = '', string $encoding = '', string $type = null, string $baseUrl = null)
    {
        $type && $this->setType($type);
        $baseUrl && $this->setBaseUrl($baseUrl);

        // Without this: "PHP Warning:  DOMDocument::registerNodeClass(): Couldn't fetch ..."
        parent::__construct($version, $encoding);

        parent::registerNodeClass('DOMDocument', 'froq\dom\DomDocument');
        parent::registerNodeClass('DOMElement', 'froq\dom\DomElement');
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
        if ($type != Document::TYPE_XML && $type != Document::TYPE_HTML) {
            throw new DomException("Invalid type, type must be 'xml' or 'html'");
        }

        $this->type = $type;

        return $this;
    }

    /**
     * Get document type.
     *
     * @return string|null
     */
    public final function getType(): string|null
    {
        return $this->type ?? null;
    }

    /**
     * Set base URL.
     *
     * @param  string $baseUrl
     * @return self
     * @throws froq\dom\DomException
     */
    public final function setBaseUrl(string $baseUrl): self
    {
        $baseUrl = self::prepareUrl($baseUrl);
        $baseUrl || throw new DomException('Invalid URL');

        $this->baseUrl = $baseUrl;

        return $this;
    }

    /**
     * Get base URL.
     *
     * @return string|null
     */
    public final function getBaseUrl(): string|null
    {
        return $this->baseUrl ?? null;
    }

    /**
     * Get root node.
     *
     * @return DOMNode|null
     */
    public final function root(): DOMNode|null
    {
        return $this->firstChild ?? null;
    }

    /**
     * Load an XML/HTML source by type.
     *
     * @param  string     $type
     * @param  string     $source
     * @param  array|null $options
     * @return self
     * @throws froq\dom\DomException
     */
    public final function loadSource(string $type, string $source, array $options = null): self
    {
        // @important
        $this->setType($type);

        static $optionsDefault = [
            'validateOnParse' => false, 'preserveWhiteSpace' => false,
            'strictErrorChecking' => false, 'throwErrors' => true, 'flags' => 0
        ];

        // HTML is more quiet.
        if ($type == Document::TYPE_HTML && !isset($options['throwErrors'])) {
            $optionsDefault['throwErrors'] = false;
        }

        ['validateOnParse' => $validateOnParse, 'preserveWhiteSpace' => $preserveWhiteSpace,
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
                throw new DomException(
                    'Parse error: %s (level: %s code: %s column: %s file: %s line: %s)',
                    [$error->message, $error->level, $error->code, $error->column, $error->file, $error->line],
                    $error->code
                );
            }
        }

        if (isset($options['baseUrl'])) {
            $baseUrl = self::prepareUrl($options['baseUrl']);
            $baseUrl || throw new DomException('Invalid URL');

            $this->baseUrl = $baseUrl;
        } elseif ($base = $this->getBaseUrl()) {
            // May be set by setBaseUrl().
            $this->baseUrl = $base;
        } elseif ($base = $this->find('//base[@href]')) {
            // May be exists (<base href="...">) in dom document.
            $this->baseUrl = (string) $base->getAttribute('href');
        } elseif ($this->baseURI) {
            $this->baseUrl = $this->baseURI;
        }

        return $this;
    }

    /**
     * @alias to loadSource() for XML sources.
     */
    public final function loadXmlSource(string $source, array $options = null): self
    {
        return $this->loadSource(Document::TYPE_XML, $source, $options);
    }

    /**
     * @alias to loadSource() for HTML sources.
     */
    public final function loadHtmlSource(string $source, array $options = null): self
    {
        return $this->loadSource(Document::TYPE_HTML, $source, $options);
    }

    /**
     * Create an XPath object.
     *
     * @return DOMXPath
     */
    public final function xpath(): DOMXPath
    {
        return new DOMXPath($this);
    }

    /**
     * Run a XPath query returning a DOMNodeList or null if no matches.
     *
     * @param  string       $query
     * @param  DOMNode|null $root
     * @return DOMNodeList|null
     * @throws froq\dom\DomException
     */
    public final function query(string $query, DOMNode $root = null): DOMNodeList|null
    {
        $query = trim($query);
        $query || throw new DomException('Empty query given');

        $nodes = $this->xpath()->query($query, $root);

        if ($nodes && $nodes->length > 0) {
            return new NodeList($nodes);
        }

        return null;
    }

    /**
     * Run a "find" process return a DOMNode or null if no match.
     *
     * @param  string       $query
     * @param  DOMNode|null $root
     * @return DOMNode|null
     */
    public final function find(string $query, DOMNode $root = null): DOMNode|null
    {
        $nodes = $this->query($query, $root);

        return $nodes ? $nodes[0] : null;
    }

    /**
     * Run a "find all" process return a DOMNodeList or null if no matches.
     *
     * @param  string       $query
     * @param  DOMNode|null $root
     * @return DOMNodeList|null
     */
    public final function findAll(string $query, DOMNode $root = null): DOMNodeList|null
    {
        $nodes = $this->query($query, $root);

        return $nodes ? $nodes : null;
    }

    /**
     * Run a "find by id" process return a DOMNode or null if no match.
     *
     * @param  string $id
     * @return DOMNode|null
     */
    public final function findById(string $id): DOMNode|null
    {
        return $this->find("//*[@id='{$id}']");
    }

    /**
     * Run a "find by name" process return a DOMNode or null if no match.
     *
     * @param  string $name
     * @return DOMNode|null
     */
    public final function findByName(string $name): DOMNode|null
    {
        return $this->find("//*[@name='{$name}']");
    }

    /**
     * Run a "find by tag" process return a DOMNodeList or null if no matches.
     *
     * @param  string       $tag
     * @param  DOMNode|null $root
     * @return DOMNodeList|null
     */
    public final function findByTag(string $tag, DOMNode $root = null): DOMNodeList|null
    {
        return ($root == null) // Root needs (.) first in query.
            ? $this->findAll("//{$tag}")
            : $this->findAll(".//{$tag}", $root);
    }

    /**
     * Run a "find by class" process return a DOMNodeList or null if no matches.
     *
     * @param  string       $class
     * @param  DOMNode|null $root
     * @return DOMNodeList|null
     */
    public final function findByClass(string $class, DOMNode $root = null): DOMNodeList|null
    {
        return ($root == null) // Root needs (.) first in query.
            ? $this->findAll("//*[contains(@class, '{$class}')]")
            : $this->findAll(".//*[contains(@class, '{$class}')]", $root);
    }

    /**
     * Run a "find by attribute" process return a DOMNodeList or null if no matches.
     *
     * @param  string       $name
     * @param  string|null  $value
     * @param  DOMNode|null $root
     * @return DOMNodeList|null
     */
    public final function findByAttribute(string $name, string $value = null, DOMNode $root = null): DOMNodeList|null
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
     * Prepare validating given URL.
     *
     * @param  string $url
     * @return string|null
     */
    private static function prepareUrl(string $url): string|null
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

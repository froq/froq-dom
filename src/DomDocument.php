<?php declare(strict_types=1);
/**
 * Copyright (c) 2015 · Kerem Güneş
 * Apache License 2.0 · http://github.com/froq/froq-dom
 */
namespace froq\dom;

use DOMNode, DOMXPath;

/**
 * A read-only class, provides a `DOMElement` structure with some additional utility
 * methods such `find()`, `findAll()` etc. and `NodeTrait` methods, for querying
 * nodes via `XPath` utilities.
 *
 * @package froq\dom
 * @class   froq\dom\DomDocument
 * @author  Kerem Güneş
 * @since   4.0
 */
class DomDocument extends \DOMDocument
{
    use NodeTrait, NodeFindTrait;

    /** Type. */
    private string $type;

    /** Base URL. */
    private string|null $baseUrl;

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
        $type    && $this->setType($type);
        $baseUrl && $this->setBaseUrl($baseUrl);

        // Without this: "PHP Warning:  DOMDocument::registerNodeClass(): Couldn't fetch ..."
        parent::__construct($version, $encoding);

        parent::registerNodeClass('DOMDocument', DomDocument::class);
        parent::registerNodeClass('DOMElement', DomElement::class);
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
        $type = strtolower($type);

        if ($type !== Document::TYPE_XML && $type !== Document::TYPE_HTML) {
            throw new DomException('Invalid type %s [valids: xml, html]', $type);
        }

        $this->type = $type;

        return $this;
    }

    /**
     * Get document type.
     *
     * @return string|null
     */
    public function getType(): string|null
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
    public function setBaseUrl(string $baseUrl): self
    {
        $this->baseUrl = self::prepareUrl($baseUrl) ?? throw new DomException('Invalid URL');

        return $this;
    }

    /**
     * Get base URL.
     *
     * @return string|null
     */
    public function getBaseUrl(): string|null
    {
        return $this->baseUrl ?? null;
    }

    /**
     * Get root node.
     *
     * @return DOMNode|null
     */
    public function root(): DOMNode|null
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
    public function loadSource(string $type, string $source, array $options = null): self
    {
        $this->setType($type); // @important

        // HTML is more quiet.
        if ($type === Document::TYPE_HTML) {
            $options['throwErrors'] ??= false;
        }

        static $optionsDefault = [
            'validateOnParse'     => false, 'preserveWhiteSpace' => false,
            'strictErrorChecking' => false, 'throwErrors'        => true,
            'flags'               => 0
        ];

        $options = array_options($options, $optionsDefault);

        // Apply options & flags.
        $this->validateOnParse     = (bool) $options['validateOnParse'];
        $this->preserveWhiteSpace  = (bool) $options['preserveWhiteSpace'];
        $this->strictErrorChecking = (bool) $options['strictErrorChecking'];

        $flags = ((int) $options['flags']) | (
            LIBXML_NOERROR | LIBXML_NOWARNING | LIBXML_BIGLINES |
            LIBXML_COMPACT | LIBXML_HTML_NOIMPLIED | LIBXML_HTML_NODEFDTD
        );

        libxml_use_internal_errors(true);

        $source = trim($source);
        if ($type === Document::TYPE_XML) {
            parent::loadXml($source, $flags);
        } elseif ($type === Document::TYPE_HTML) {
            // Workaround for a proper encoding.
            if (!str_starts_with($source, '<?xml')) {
                $source = '<?xml' . $source;
            }
            parent::loadHtml($source, $flags);
        }

        // Handle error.
        $error = libxml_get_last_error();
        if ($error) {
            libxml_clear_errors();

            $error->file    = $error->file ?: 'n/a';
            $error->message = trim($error->message);

            if ($options['throwErrors']) {
                throw new DomException(
                    'Parse error: %s (level: %s code: %s column: %s file: %s line: %s)',
                    [$error->message, $error->level, $error->code, $error->column, $error->file, $error->line],
                    code: $error->code
                );
            }
        }

        // Set base URL.
        if (isset($options['baseUrl'])) {
            $this->baseUrl = self::prepareUrl($options['baseUrl']) ?? throw new DomException('Invalid URL');
        } elseif ($base = $this->getBaseUrl()) {
            // May be set by setBaseUrl().
            $this->baseUrl = $base;
        } elseif ($base = $this->find('//base[@href]')) {
            // May be exists (<base href="...">) in dom document.
            $this->baseUrl = (string) $base->getAttribute('href');
        } else {
            $this->baseUrl = $this->baseURI;
        }

        return $this;
    }

    /**
     * Load a XML source (@alias to loadSource() for XML sources).
     *
     * @param  string     $source
     * @param  array|null $options
     * @return self
     */
    public function loadXmlSource(string $source, array $options = null): self
    {
        return $this->loadSource(Document::TYPE_XML, $source, $options);
    }

    /**
     * Load an HTML source (@alias to loadSource() for HTML sources).
     *
     * @param  string     $source
     * @param  array|null $options
     * @return self
     */
    public function loadHtmlSource(string $source, array $options = null): self
    {
        return $this->loadSource(Document::TYPE_HTML, $source, $options);
    }

    /**
     * Create an XPath object using self instance.
     *
     * @return DOMXPath
     */
    public function xpath(): DOMXPath
    {
        return new DOMXPath($this);
    }

    /**
     * Run a XPath query returning a DomNodeList or null if no matches.
     *
     * @param  string       $query
     * @param  DOMNode|null $root
     * @return froq\dom\DomElementList|froq\dom\DomNodeList|null
     * @throws froq\dom\DomException
     */
    public function query(string $query, DOMNode $root = null): DomElementList|DomNodeList|null
    {
        $query = trim($query) ?: throw new DomException('Empty query');

        /** @var DOMNodeList|false */
        $nodes = $this->xpath()->query($query, $root);

        if ($nodes === false) {
            throw new DomException('Malformed query');
        }

        if ($nodes->length > 0) {
            return ($nodes[0] instanceof DomElement)
                ? new DomElementList($nodes) : new DomNodeList($nodes);
        }

        return null;
    }

    /**
     * Prepare validating given URL.
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
        if (empty($match['scheme']) || $match['scheme'] === '//') {
            $match['scheme'] = 'http://';
        }

        return $match['scheme'] . $match['host'] . $match['rest'];
    }
}

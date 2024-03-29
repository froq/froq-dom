<?php declare(strict_types=1);
/**
 * Copyright (c) 2015 · Kerem Güneş
 * Apache License 2.0 · http://github.com/froq/froq-dom
 */
namespace froq\dom;

/**
 * XML document class.
 *
 * @package froq\dom
 * @class   froq\dom\XmlDocument
 * @author  Kerem Güneş
 * @since   3.0
 */
class XmlDocument extends Document
{
    /** Default encoding & version. */
    public const ENCODING = 'utf-8', VERSION = '1.0';

    /** Encoding & version. */
    public string $encoding, $version;

    /**
     * Constructor.
     *
     * @param array|null  $data
     * @param string|null $encoding
     * @param string|null $version
     */
    public function __construct(array $data = null, string $encoding = null, string $version = null)
    {
        parent::__construct(Document::TYPE_XML, $data);

        $this->encoding = $encoding ?? self::ENCODING;
        $this->version  = $version  ?? self::VERSION;
    }
}

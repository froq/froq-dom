<?php
/**
 * Copyright (c) 2015 · Kerem Güneş
 * Apache License 2.0 · http://github.com/froq/froq-dom
 */
declare(strict_types=1);

namespace froq\dom;

/**
 * Xml Document.
 *
 * @package froq\dom
 * @object  froq\dom\XmlDocument
 * @author  Kerem Güneş
 * @since   3.0
 */
class XmlDocument extends Document
{
    /** @const string */
    public const ENCODING = 'utf-8', VERSION = '1.0';

    /** @var string */
    public string $encoding, $version;

    /**
     * Constructor.
     *
     * @param array|null $data
     * @param string     $encoding
     * @param string     $version
     */
    public function __construct(array $data = null, string $encoding = self::ENCODING, string $version = self::VERSION)
    {
        parent::__construct(Document::TYPE_XML, $data);

        $this->encoding = $encoding;
        $this->version  = $version;
    }
}

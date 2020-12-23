<?php
/**
 * Copyright (c) 2015 · Kerem Güneş
 * Apache License 2.0 · http://github.com/froq/froq-dom
 */
declare(strict_types=1);

namespace froq\dom;

use froq\dom\Document;

/**
 * Xml Document.
 *
 * @package froq\dom
 * @object  froq\dom\XmlDocument
 * @author  Kerem Güneş
 * @since   3.0
 */
final class XmlDocument extends Document
{
    /** @var string, string */
    protected string $encoding, $version;

    /**
     * Constructor.
     *
     * @param array|null  $data
     * @param string|null $encoding
     * @param string|null $version
     */
    public function __construct(array $data = null, string $encoding = null, string $version = null)
    {
        $this->encoding = $encoding ?: 'utf-8';
        $this->version  = $version  ?: '1.0';

        parent::__construct(Document::TYPE_XML, $data);
    }
}

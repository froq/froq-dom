<?php
/**
 * Copyright (c) 2015 · Kerem Güneş
 * Apache License 2.0 <https://opensource.org/licenses/apache-2.0>
 */
declare(strict_types=1);

namespace froq\dom;

use froq\dom\Document;

/**
 * Xml Document.
 *
 * @package froq\dom
 * @object  froq\dom\XmlDocument
 * @author  Kerem Güneş <k-gun@mail.com>
 * @since   3.0
 */
final class XmlDocument extends Document
{
    /**
     * Constructor.
     * @param array|null  $data
     * @param string|null $xmlVersion
     * @param string|null $xmlEncoding
     */
    public function __construct(array $data = null, string $xmlVersion = null, string $xmlEncoding = null)
    {
        parent::__construct(Document::TYPE_XML, $data, $xmlVersion, $xmlEncoding);
    }
}

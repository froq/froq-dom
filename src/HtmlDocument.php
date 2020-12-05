<?php
/**
 * Copyright (c) 2015 · Kerem Güneş
 * Apache License 2.0 <https://opensource.org/licenses/apache-2.0>
 */
declare(strict_types=1);

namespace froq\dom;

use froq\dom\Document;

/**
 * Html Document.
 *
 * @package froq\dom
 * @object  froq\dom\HtmlDocument
 * @author  Kerem Güneş <k-gun@mail.com>
 * @since   3.0
 */
final class HtmlDocument extends Document
{
    /**
     * Constructor.
     *
     * @param array|null $data
     */
    public function __construct(array $data = null)
    {
        parent::__construct(Document::TYPE_HTML, $data);
    }
}

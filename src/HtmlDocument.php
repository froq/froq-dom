<?php
/**
 * Copyright (c) 2015 · Kerem Güneş
 * Apache License 2.0 · http://github.com/froq/froq-dom
 */
declare(strict_types=1);

namespace froq\dom;

/**
 * Html Document.
 *
 * @package froq\dom
 * @object  froq\dom\HtmlDocument
 * @author  Kerem Güneş
 * @since   3.0
 */
class HtmlDocument extends Document
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

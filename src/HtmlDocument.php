<?php declare(strict_types=1);
/**
 * Copyright (c) 2015 · Kerem Güneş
 * Apache License 2.0 · http://github.com/froq/froq-dom
 */
namespace froq\dom;

/**
 * HTML document class.
 *
 * @package froq\dom
 * @class   froq\dom\HtmlDocument
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

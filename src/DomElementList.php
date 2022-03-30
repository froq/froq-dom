<?php
/**
 * Copyright (c) 2015 · Kerem Güneş
 * Apache License 2.0 · http://github.com/froq/froq-dom
 */
declare(strict_types=1);

namespace froq\dom;

/**
 * @package froq\dom
 * @object  froq\dom\DomElementList
 * @author  Kerem Güneş
 * @since   5.2
 */
class DomElementList extends DomNodeList
{
    /**
     * Constructor.
     *
     * @param iterable<DomElement> $items
     */
    public function __construct(iterable $items)
    {
        parent::__construct($items);
    }
}

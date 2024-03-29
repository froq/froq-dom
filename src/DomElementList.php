<?php declare(strict_types=1);
/**
 * Copyright (c) 2015 · Kerem Güneş
 * Apache License 2.0 · http://github.com/froq/froq-dom
 */
namespace froq\dom;

/**
 * A list class for collecting DOM elements.
 *
 * @package froq\dom
 * @class   froq\dom\DomElementList
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

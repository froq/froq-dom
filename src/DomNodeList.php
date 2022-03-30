<?php
/**
 * Copyright (c) 2015 · Kerem Güneş
 * Apache License 2.0 · http://github.com/froq/froq-dom
 */
declare(strict_types=1);

namespace froq\dom;

/**
 * @package froq\dom
 * @object  froq\dom\DomNodeList
 * @author  Kerem Güneş
 * @since   4.0, 5.2
 */
class DomNodeList extends \ItemList
{
    /**
     * Constructor.
     *
     * @param iterable<DOMNode> $items
     */
    public function __construct(iterable $items)
    {
        parent::__construct($items);
    }

    /**
     * @override
     */
    public function __debugInfo(): array
    {
        return ['length' => $this->length()] + parent::__debugInfo();
    }

    /**
     * @alias count()
     */
    public function length()
    {
        return $this->count();
    }
}

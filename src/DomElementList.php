<?php
/**
 * Copyright (c) 2015 · Kerem Güneş
 * Apache License 2.0 · http://github.com/froq/froq-dom
 */
declare(strict_types=1);

namespace froq\dom;

/**
 * Dom Element List.
 *
 * A read-only class, entends `DomNodeList` class and accepts `DomElement` items.
 *
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
     * @param  iterable<DomElement> $items
     * @throws froq\dom\DomException
     */
    public function __construct(iterable $items)
    {
        // We accept only dom elements here.
        foreach ($items as $item) {
            ($item instanceof DomElement) || throw new DomException(
                'Each item must be a %s, %t given', [DomElement::class, $item]
            );
        }

        parent::__construct($items);
    }

    /**
     * @throws ReadonlyError
     * @override
     */
    public function offsetSet(mixed $index, mixed $_): never
    {
        throw new \ReadonlyError($this);
    }

    /**
     * @throws ReadonlyError
     * @override
     */
    public function offsetUnset(mixed $index): never
    {
        throw new \ReadonlyError($this);
    }
}

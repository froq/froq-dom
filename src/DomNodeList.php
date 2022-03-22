<?php
/**
 * Copyright (c) 2015 · Kerem Güneş
 * Apache License 2.0 · http://github.com/froq/froq-dom
 */
declare(strict_types=1);

namespace froq\dom;

use DOMNode;

/**
 * Dom Node List.
 *
 * A read-only class, provides an extended `DOMNodeList` structure with some additional
 * utility methods and accepts `DOMNode` items.
 *
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
     * @param  iterable<DOMNode> $items
     * @throws froq\dom\DomException
     */
    public function __construct(iterable $items)
    {
        // We accept only DOMNode's here.
        foreach ($items as $item) {
            ($item instanceof DOMNode) || throw new DomException(
                'Each item must be a %s, %t given', [DOMNode::class, $item]
            );
        }

        parent::__construct($items, locked: true);
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

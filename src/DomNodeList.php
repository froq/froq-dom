<?php
/**
 * Copyright (c) 2015 · Kerem Güneş
 * Apache License 2.0 · http://github.com/froq/froq-dom
 */
declare(strict_types=1);

namespace froq\dom;

use froq\dom\DomException;
use froq\common\interface\Arrayable;
use froq\collection\iterator\{ArrayIterator, ReverseArrayIterator};
use DOMNode, ArrayAccess, IteratorAggregate, Traversable;

/**
 * Dom Node List.
 *
 * Represents a node list class that provides an extended `DOMNodeList` structure with additional
 * utility methods.
 *
 * @package froq\dom
 * @object  froq\dom\DomNodeList
 * @author  Kerem Güneş
 * @since   4.0, 5.2 Move as "DomNodeList" from "NodeList".
 */
class DomNodeList implements Arrayable, ArrayAccess, IteratorAggregate
{
    /** @var array<DOMNode> */
    protected array $items = [];

    /**
     * Constructor.
     *
     * @param  array<DOMNode>|Traversable<DOMNode> $items
     * @throws froq\dom\DomException
     */
    public function __construct(array|Traversable $items)
    {
        // We accept only DOMNode's here.
        foreach ($items as $item) {
            ($item instanceof DOMNode) || throw new DomException(
                'Each item must be a %s, %s given', [DOMNode::class, get_type($item)]
            );

            $this->items[] = $item;
        }
    }

    /**
     * @alias of count()
     */
    public function length()
    {
        return $this->count();
    }

    /**
     * Get an item.
     *
     * @param  int $i
     * @return DOMNode|null
     */
    public function item(int $i): DOMNode|null
    {
        return $this->items[$i] ?? null;
    }

    /**
     * Get all items.
     *
     * @return array<DOMNode>
     */
    public function items(): array
    {
        return $this->items;
    }

    /**
     * Get first item.
     *
     * @return DOMNode|null
     */
    public function first(): DOMNode|null
    {
        return $this->item(0);
    }

    /**
     * Get last item.
     *
     * @return DOMNode|null
     */
    public function last(): DOMNode|null
    {
        return $this->item($this->count() - 1);
    }

    /**
     * Call given function for each item of node list.
     *
     * @param  callable $func
     * @return self
     * @since  5.1
     */
    public function each(callable $func): self
    {
        each($this->items, $func);

        return $this;
    }

    /**
     * Filter node list.
     *
     * @param  callable $func
     * @return self
     */
    public function filter(callable $func): self
    {
        $this->items = array_filter($this->items, $func);

        return $this;
    }

    /**
     * Map node list.
     *
     * @param  callable $func
     * @return self
     */
    public function map(callable $func): self
    {
        $this->items = array_map($func, $this->items);

        return $this;
    }

    /**
     * Reverse node list.
     *
     * @return self
     */
    public function reverse(): self
    {
        $this->items = array_reverse($this->items);

        return $this;
    }

    /**
     * @inheritDoc froq\common\interface\Arrayable
     */
    public function toArray(): array
    {
        return $this->items;
    }

    /**
     * @inheritDoc Countable
     */
    public function count(): int
    {
        return count($this->items());
    }

    /**
     * @inheritDoc IteratorAggregate
     */
    public function getIterator(): ArrayIterator
    {
        return new ArrayIterator($this->items);
    }

    /**
     * @since 5.2
     */
    public function getReverseIterator(): ReverseArrayIterator
    {
        return new ReverseArrayIterator($this->items);
    }

    /**
     * @inheritDoc ArrayAccess
     */
    public function offsetExists($i)
    {
        return $this->item($i) != null;
    }

    /**
     * @inheritDoc ArrayAccess
     */
    public function offsetGet($i)
    {
        return $this->item($i);
    }

    /**
     * Block mutation attempts.
     *
     * @throws froq\dom\DomException
     */
    public function offsetSet($i, $node)
    {
        throw new DomException('Cannot modify read-only object ' . self::class);
    }

    /**
     * Block mutation attempts.
     *
     * @throws froq\dom\DomException
     */
    public function offsetUnset($i)
    {
        throw new DomException('Cannot modify read-only object ' . self::class);
    }
}

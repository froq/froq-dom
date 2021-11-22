<?php
/**
 * Copyright (c) 2015 · Kerem Güneş
 * Apache License 2.0 · http://github.com/froq/froq-dom
 */
declare(strict_types=1);

namespace froq\dom;

use froq\dom\DomException;
use froq\common\interface\Arrayable;
use froq\util\Arrays;
use DOMNode, DOMNodeList, IteratorAggregate, ArrayAccess, ArrayIterator, Traversable, Iterator;

/**
 * Node List.
 *
 * Represents a read-only node list entity that provides a DOMNodeList structure with additional
 * utility methods such map(), filter() and toArray().
 *
 * @package froq\dom
 * @object  froq\dom\NodeList
 * @author  Kerem Güneş
 * @since   4.0
 */
final class NodeList extends DOMNodeList implements Arrayable, ArrayAccess, IteratorAggregate
{
    /** @var array<DOMNode> */
    private array $items = [];

    /** @var int */
    private int $count = 0;

    /**
     * Constructor.
     * @param array<DOMNode>|Traversable $items
     * @throws froq\dom\DomException
     */
    public function __construct(array|Traversable $items)
    {
        foreach ($items as $item) {
            // We accept only dom nodes here.
            ($item instanceof DOMNode) || throw new DomException(
                'Each item must be a DOMNode, %s given', get_type($item)
            );

            $this->items[] = $item;
            $this->count++;
        }
    }

    /**
     * Since $length property is not writeable, this method simulates it.
     *
     * @return int
     */
    public function length(): int
    {
        return $this->count;
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
        return $this->item($this->count - 1);
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
        Arrays::each($this->items, $func);

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
        return $this->items();
    }

    /**
     * @inheritDoc Countable
     */
    public function count(): int
    {
        return $this->count;
    }

    /**
     * @inheritDoc IteratorAggregate
     */
    public function getIterator(): Iterator
    {
        return new ArrayIterator($this->items);
    }

    /**
     * @inheritDoc ArrayAccess
     */
    public function offsetGet($i): DOMNode|null
    {
        return $this->item($i);
    }

    /**
     * @inheritDoc ArrayAccess
     */
    public function offsetExists($i): bool
    {
        return $this->item($i) != null;
    }

    /**
     * Block mutation attempts.
     *
     * @throws froq\dom\DomException
     */
    public function offsetSet($i, $node): void
    {
        throw new DomException('Cannot modify read-only object ' . self::class);
    }

    /**
     * Block mutation attempts.
     *
     * @throws froq\dom\DomException
     */
    public function offsetUnset($i): void
    {
        throw new DomException('Cannot modify read-only object ' . self::class);
    }
}

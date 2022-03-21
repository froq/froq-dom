<?php
/**
 * Copyright (c) 2015 · Kerem Güneş
 * Apache License 2.0 · http://github.com/froq/froq-dom
 */
declare(strict_types=1);

namespace froq\dom;

use froq\common\interface\{Arrayable, Listable};
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
class DomNodeList implements Arrayable, Listable, \Countable, \ArrayAccess, \IteratorAggregate
{
    /** @var array<DOMNode> */
    protected array $items = [];

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

            $this->items[] = $item;
        }
    }

    /**
     * @alias count()
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
        return first($this->items);
    }

    /**
     * Get last item.
     *
     * @return DOMNode|null
     */
    public function last(): DOMNode|null
    {
        return last($this->items);
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
        $this->items = array_filter_list($this->items, $func);

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
     * Reduce.
     *
     * @param  mixed    $carry
     * @param  callable $func
     * @return mixed
     * @since  6.0
     */
    public function reduce(mixed $carry, callable $func): mixed
    {
        return array_reduce($this->items, $func, $carry);
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
     * @inheritDoc froq\common\interface\Listable
     */
    public function toList(): array
    {
        return array_list($this->items);
    }

    /**
     * @inheritDoc Countable
     */
    public function count(): int
    {
        return count($this->items);
    }

    /**
     * @inheritDoc IteratorAggregate
     */ #[\ReturnTypeWillChange]
    public function getIterator(): iterable
    {
        return new \ArrayIterator($this->items);
    }

    /**
     * @inheritDoc ArrayAccess
     */
    public function offsetExists(mixed $i): bool
    {
        return $this->item($i) !== null;
    }

    /**
     * @inheritDoc ArrayAccess
     */
    public function offsetGet(mixed $i): object|null
    {
        return $this->item($i);
    }

    /**
     * @inheritDoc ArrayAccess
     * @throws     ReadonlyClassError
     */
    public function offsetSet(mixed $i, mixed $_): never
    {
        throw new \ReadonlyClassError($this);
    }

    /**
     * @inheritDoc ArrayAccess
     * @throws     ReadonlyClassError
     */
    public function offsetUnset(mixed $i): never
    {
        throw new \ReadonlyClassError($this);
    }
}

<?php
/**
 * MIT License <https://opensource.org/licenses/mit>
 *
 * Copyright (c) 2015 Kerem Güneş
 *
 * Permission is hereby granted, free of charge, to any person obtaining a copy
 * of this software and associated documentation files (the "Software"), to deal
 * in the Software without restriction, including without limitation the rights
 * to use, copy, modify, merge, publish, distribute, sublicense, and/or sell
 * copies of the Software, and to permit persons to whom the Software is furnished
 * to do so, subject to the following conditions:
 *
 * The above copyright notice and this permission notice shall be included in all
 * copies or substantial portions of the Software.
 *
 * THE SOFTWARE IS PROVIDED "AS IS", WITHOUT WARRANTY OF ANY KIND, EXPRESS OR
 * IMPLIED, INCLUDING BUT NOT LIMITED TO THE WARRANTIES OF MERCHANTABILITY,
 * FITNESS FOR A PARTICULAR PURPOSE AND NONINFRINGEMENT. IN NO EVENT SHALL THE
 * AUTHORS OR COPYRIGHT HOLDERS BE LIABLE FOR ANY CLAIM, DAMAGES OR OTHER
 * LIABILITY, WHETHER IN AN ACTION OF CONTRACT, TORT OR OTHERWISE, ARISING FROM,
 * OUT OF OR IN CONNECTION WITH THE SOFTWARE OR THE USE OR OTHER DEALINGS IN
 * THE SOFTWARE.
 */
declare(strict_types=1);

namespace froq\dom;

use froq\dom\DomException;
use froq\interfaces\Arrayable;
use DOMNode, DOMNodeList;
use IteratorAggregate, ArrayAccess, ArrayIterator, Traversable;

// Suppress useless 'Declaration of ...' warnings.
@(function () {

/**
 * Node List.
 *
 * Represents a read-only entity class that provides a DOMNodeList structure with additional
 * utility methods such map(), filter() and toArray().
 *
 * @package froq\dom
 * @object  froq\dom\NodeList
 * @author  Kerem Güneş <k-gun@mail.com>
 * @since   4.0
 */
final class NodeList extends DOMNodeList implements Arrayable, IteratorAggregate, ArrayAccess
{
    /**
     * Items.
     * @var array
     */
    private array $items = [];

    /**
     * Constructor.
     * @param iterable $items
     */
    public function __construct(iterable $items)
    {
        if ($items instanceof Traversable) {
            $items = iterator_to_array($items);
        }

        $this->items = $items;
    }

    /**
     * Length.
     *
     * Since $length property is not writeable, this method simulates it.
     *
     * @return int
     */
    public function length(): int
    {
        return $this->count();
    }

    /**
     * Item.
     * @param  int $i
     * @return ?DOMNode
     */
    public function item(int $i): ?DOMNode
    {
        return $this->items[$i] ?? null;
    }

    /**
     * Items.
     * @return array
     */
    public function items(): array
    {
        return $this->items;
    }

    /**
     * First.
     * @return ?DOMNode
     */
    public function first(): ?DOMNode
    {
        return $this->item(0);
    }

    /**
     * Last.
     * @return ?DOMNode
     */
    public function last(): ?DOMNode
    {
        return $this->item($this->count() - 1);
    }

    /**
     * Each.
     * @param  callable $call
     * @return self
     */
    public function each(callable $call): self
    {
        foreach ($this->items as $i => $item) {
            $call($item, $i);
        }

        return $this;
    }

    /**
     * Map.
     * @param  callable $call
     * @return self
     */
    public function map(callable $call): self
    {
        $this->items = array_map($call, $this->items);

        return $this;
    }

    /**
     * Filter.
     * @param  callable $call
     * @return self
     */
    public function filter(callable $call): self
    {
        $this->items = array_filter($this->items, $call);

        return $this;
    }

    /**
     * Reverse.
     * @return self
     */
    public function reverse(): self
    {
        $this->items = array_reverse($this->items);

        return $this;
    }

    /**
     * @inheritDoc froq\interfaces\Stringable
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
        return count($this->items);
    }

    /**
     * @inheritDoc IteratorAggregate
     */
    public function getIterator(): iterable
    {
        return new ArrayIterator($this->items);
    }

    /**
     * @inheritDoc ArrayAccess
     */
    public function offsetGet($i): ?DOMNode
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
    public function offsetSet($i, $_): void
    {
        throw new DomException('Cannot modify read-only '. self::class .' object');
    }

    /**
     * Block mutation attempts.
     *
     * @throws froq\dom\DomException
     */
    public function offsetUnset($i): void
    {
        throw new DomException('Cannot modify read-only '. self::class .' object');
    }
}

})();

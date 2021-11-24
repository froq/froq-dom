<?php
/**
 * Copyright (c) 2015 · Kerem Güneş
 * Apache License 2.0 · http://github.com/froq/froq-dom
 */
declare(strict_types=1);

namespace froq\dom;

use froq\dom\{DomNodeList, DomElement, DomException};
use Traversable;

/**
 * Dom Element List.
 *
 * Represents an element list class that entends `DomNodeList` class.
 *
 * @package froq\dom
 * @object  froq\dom\DomElementList
 * @author  Kerem Güneş
 * @since   5.2
 */
final class DomElementList extends DomNodeList
{
    /**
     * Constructor.
     *
     * @param  array<DomElement>|Traversable<DomElement> $items
     * @throws froq\dom\DomException
     */
    public function __construct(array|Traversable $items)
    {
        // We accept only dom elements here.
        foreach ($items as $item) {
            ($item instanceof DomElement) || throw new DomException(
                'Each item must be a %s, %s given', [DomElement::class, get_type($item)]
            );
        }

        parent::__construct($items);
    }
}

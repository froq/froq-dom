<?php
/**
 * Copyright (c) 2015 · Kerem Güneş
 * Apache License 2.0 · http://github.com/froq/froq-dom
 */
declare(strict_types=1);

namespace froq\dom;

/**
 * A read-only class, provides a `DOMElement` structure with some additional utility
 * methods such `find()`, `findAll()` etc. and `NodeTrait` methods, for querying
 * nodes via `XPath` utilities.
 *
 * @package froq\dom
 * @object  froq\dom\DomElement
 * @author  Kerem Güneş
 * @since   4.0
 */
class DomElement extends \DOMElement
{
    use NodeTrait, NodeFindTrait;

    /**
     * Get id attribute.
     *
     * @return string|null
     * @since  5.2
     */
    public function id(): string|null
    {
        return $this->getAttribute('id');
    }

    /**
     * Get name attribute.
     *
     * @return string|null
     * @since  5.2
     */
    public function name(): string|null
    {
        return $this->getAttribute('name');
    }

    /**
     * Get class attribute.
     *
     * @return string|null
     * @since  5.2
     */
    public function class(): string|null
    {
        return $this->getAttribute('class');
    }
}

<?php
/**
 * Copyright (c) 2015 · Kerem Güneş
 * Apache License 2.0 <https://opensource.org/licenses/apache-2.0>
 */
declare(strict_types=1);

namespace froq\dom;

use froq\dom\{DomException, NodeTrait};
use DOMNode, DOMNodeList, DOMElement as _DOMElement;

/**
 * Dom Element.
 *
 * Represents a read-only DOM element entity that provides a DOMElement structure with additional
 * utility methods such find(), findAll() etc. and NodeTrait methods, for querying nodes via XPath
 * utilities.
 *
 * @package froq\dom
 * @object  froq\dom\DomElement
 * @author  Kerem Güneş <k-gun@mail.com>
 * @since   4.0
 */
class DomElement extends _DOMElement
{
    /**
     * Node trait.
     * @see froq\dom\NodeTrait
     */
    use NodeTrait;

    /**
     * Magic - call: proxy method for finder methods of owner document.
     *
     * @param  string $method
     * @param  array  $methodArgs
     * @return DOMNode|DOMNodeList|null
     * @throws froq\dom\DomException
     */
    public function __call(string $method, array $methodArgs): DOMNode|DOMNodeList|null
    {
        static $methods = ['find', 'findAll', 'findByTag', 'findByClass', 'findByAttribute'];

        if (in_array($method, $methods)) {
            return call_user_func_array([$this->ownerDocument, $method], [$methodArgs[0], $this]);
        }

        throw new DomException('Invalid method call as %s() on %s object, valids are: %s',
            [$method, $this::class, join(', ', $methods)]);
    }
}

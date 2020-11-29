<?php
/**
 * Copyright (c) 2015 · Kerem Güneş
 * Apache License 2.0 <https://opensource.org/licenses/apache-2.0>
 */
declare(strict_types=1);

namespace froq\dom;

use froq\dom\{NodeTrait, DomException};
use DOMElement as _DOMElement;

// Suppress useless 'Declaration of ...' warnings.
@(function () {

/**
 * Dom Element.
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
     * @object froq\dom\NodeTrait
     */
    use NodeTrait;

    /**
     * Call.
     *
     * Proxy method for finder methods of the owner document.
     *
     * @param  string $method
     * @param  array  $methodArgs
     * @return DOMNode|DOMNodeList|null
     * @throws froq\dom\DomException
     */
    public function __call(string $method, array $methodArgs)
    {
        static $methods = ['find', 'findAll', 'findByTag', 'findByClass', 'findByAttribute'];

        if (in_array($method, $methods)) {
            return call_user_func_array([$this->ownerDocument, $method], [$methodArgs[0], $this]);
        }

        throw new DomException('Invalid method call as "%s", available methods are: %s',
            [$method, join(', ', $methods)]);
    }
}

})();

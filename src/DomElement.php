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

use froq\dom\{NodeTrait, DomException};
use DOMElement as _DOMElement;

// Suppress useless 'Declaration of ...' warnings.
@(function () {

/**
 * Dom Element.
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

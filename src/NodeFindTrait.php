<?php
/**
 * Copyright (c) 2015 · Kerem Güneş
 * Apache License 2.0 · http://github.com/froq/froq-dom
 */
declare(strict_types=1);

namespace froq\dom;

use froq\dom\{Document, DomDocument, DomElement, DomNodeList};
use DOMNode;

/**
 * Node Find Trait.
 *
 * Represents a trait that provides some find utilities for `DomDocument` and `DomElement`
 * classes.
 *
 * @package froq\dom
 * @object  froq\dom\NodeFindTrait
 * @author  Kerem Güneş
 * @since   5.2 Separated from "DomDocument" methods.
 * @internal
 */
trait NodeFindTrait
{
    /**
     * Run a "find" process return a DOMNode or null if no match.
     *
     * @param  string       $query
     * @param  DOMNode|null $root
     * @return DOMNode|null
     */
    public final function find(string $query, DOMNode $root = null): DOMNode|null
    {
        $nodes = $this->executeQuery($query, $root);

        return $nodes ? $nodes[0] : null;
    }

    /**
     * Run a "find all" process return a DomNodeList or null if no matches.
     *
     * @param  string       $query
     * @param  DOMNode|null $root
     * @return DomNodeList|null
     */
    public final function findAll(string $query, DOMNode $root = null): DomNodeList|null
    {
        $nodes = $this->executeQuery($query, $root);

        return $nodes ? $nodes : null;
    }

    /**
     * Run a "find by tag" process return a DomNodeList or null if no matches.
     *
     * @param  string       $tag
     * @param  DOMNode|null $root
     * @return DomNodeList|null
     */
    public final function findByTag(string $tag, DOMNode $root = null): DomNodeList|null
    {
        return ($root === null) // Root needs (.) first in query.
             ? $this->findAll("//{$tag}")
             : $this->findAll(".//{$tag}", $root);
    }

    /**
     * Run a "find by id" process return a DOMNode or null if no match.
     *
     * @param  string $id
     * @return DOMNode|null
     */
    public final function findById(string $id): DOMNode|null
    {
        return $this->find("//*[@id='{$id}']");
    }

    /**
     * Run a "find by name" process return a DOMNode or null if no match.
     *
     * @param  string $name
     * @return DOMNode|null
     */
    public final function findByName(string $name): DOMNode|null
    {
        return $this->find("//*[@name='{$name}']");
    }

    /**
     * Run a "find by class" process return a DomNodeList or null if no matches.
     *
     * @param  string       $class
     * @param  DOMNode|null $root
     * @return DomNodeList|null
     */
    public final function findByClass(string $class, DOMNode $root = null): DomNodeList|null
    {
        return ($root === null) // Root needs (.) first in query.
             ? $this->findAll("//*[contains(@class, '{$class}')]")
             : $this->findAll(".//*[contains(@class, '{$class}')]", $root);
    }

    /**
     * Run a "find by attribute" process return a DomNodeList or null if no matches.
     *
     * @param  string       $name
     * @param  string|null  $value
     * @param  DOMNode|null $root
     * @return DomNodeList|null
     */
    public final function findByAttribute(string $name, string $value = null, DOMNode $root = null): DomNodeList|null
    {
        if ($value === null) {
            return ($root === null) // Root needs (.) first in query.
                 ? $this->findAll("//*[@{$name}]")
                 : $this->findAll(".//*[@{$name}]", $root);
        } else {
            $value = addcslashes($value, '"');

            return ($root === null) // Root needs (.) first in query.
                 ? $this->findAll("//*[@{$name}='{$value}']")
                 : $this->findAll(".//*[@{$name}='{$value}']", $root);
        }
    }

    /**
     * Execute given XPath query for user DomDocument or DomElement.
     *
     * @param  string       $query
     * @param  DOMNode|null $root
     * @return DOMNode|DomNodeList|null
     */
    private function executeQuery(string $query, DOMNode $root = null): DOMNode|DomNodeList|null
    {
        if ($this instanceof DomDocument) {
            return $this->query($query, $root);
        } elseif ($this instanceof DomElement) {
            return $this->ownerDocument->query($query, $this);
        }
    }
}

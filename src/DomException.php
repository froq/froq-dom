<?php declare(strict_types=1);
/**
 * Copyright (c) 2015 · Kerem Güneş
 * Apache License 2.0 · http://github.com/froq/froq-dom
 */
namespace froq\dom;

/**
 * @package froq\dom
 * @class   froq\dom\DomException
 * @author  Kerem Güneş
 * @since   3.0
 */
class DomException extends \froq\common\Exception
{
    public static function forInvalidType(string $type): static
    {
        return new static('Invalid type %q [valids: xml, html]', $type);
    }

    public static function forInvalidUrl(string $url): static
    {
        return new static('Invalid URL %q', $url);
    }

    public static function forEmptyQuery(): static
    {
        return new static('Empty query');
    }

    public static function forMalformedQuery(): static
    {
        return new static('Malformed query');
    }

    public static function forParseError(\LibXMLError $error): static
    {
        return new static(
            'Parse error: %s [level: %s, code: %s, column: %s, file: %s, line: %s]',
            [$error->message, $error->level, $error->code, $error->column, $error->file, $error->line],
            code: $error->code
        );
    }

    public static function forInvalidDocumentData(string $missing): static
    {
        return new static('Invalid document data, no @root %s in given data', $missing);
    }

    public static function forAttrNotAllowedChars(string $name, string $notAllowedChars): static
    {
        return new static(
            'Invalid attribute name %q given [tip: don\'t use these characters %q in name]',
            [$name, $notAllowedChars]
        );
    }

    public static function forAttrUnmatchedNamePattern(string $name, string $namePattern): static
    {
        return new static(
            'Invalid attribute name %q given [tip: use a name that matches with %q]',
            [$name, $namePattern]
        );
    }
}

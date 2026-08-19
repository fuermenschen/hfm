<?php

declare(strict_types=1);

namespace App\Support\AdminFiles;

class SvgNormalizer
{
    public function normalize(string $contents): string
    {
        throw_if(trim($contents) === '', \InvalidArgumentException::class, 'Datei enthält kein gültiges SVG.');

        throw_if(stripos($contents, '<!DOCTYPE') !== false || stripos($contents, '<!ENTITY') !== false, \InvalidArgumentException::class, 'SVG darf keine Dokumenttyp- oder Entity-Deklarationen enthalten.');

        $previousUseInternalErrors = libxml_use_internal_errors(true);

        try {
            $document = new \DOMDocument;
            $loaded = $document->loadXML($contents, LIBXML_NONET | LIBXML_NOERROR | LIBXML_NOWARNING);
        } finally {
            libxml_clear_errors();
            libxml_use_internal_errors($previousUseInternalErrors);
        }

        throw_if(! $loaded || $document->documentElement?->localName !== 'svg', \InvalidArgumentException::class, 'Datei enthält kein gültiges SVG.');

        $root = $document->documentElement;

        if ($root->hasAttribute('stroke') || $this->styleDefinesStroke($root->getAttribute('style'))) {
            return $contents;
        }

        $root->setAttribute('stroke', 'none');

        return $document->saveXML() ?: throw new \RuntimeException('SVG konnte nicht normalisiert werden.');
    }

    protected function styleDefinesStroke(string $style): bool
    {
        return preg_match('/(?:^|;)\s*stroke\s*:/i', $style) === 1;
    }
}

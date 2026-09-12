<?php

namespace App\Support;

use DOMDocument;
use DOMElement;
use DOMNode;
use DOMXPath;

/**
 * Makes an uploaded SVG safe to serve.
 *
 * An SVG is a document, not a picture: it can carry <script>, event handlers,
 * external references and embedded objects, and the browser will run all of
 * them because the file is served from our own origin. Uploading one is
 * therefore uploading code — so nothing reaches the disk until it has been
 * through here.
 *
 * The approach is an allow-list of elements and attributes. Anything not
 * recognised is removed rather than escaped, because a sanitiser that tries to
 * be clever about hostile input is a sanitiser with a bypass in it.
 */
class SvgSanitizer
{
    /** Elements that can appear in a static illustration. */
    private const ALLOWED_ELEMENTS = [
        'svg', 'g', 'defs', 'symbol', 'use', 'title', 'desc', 'metadata',
        'path', 'rect', 'circle', 'ellipse', 'line', 'polyline', 'polygon',
        'text', 'tspan', 'textpath',
        'linearGradient', 'radialGradient', 'stop', 'pattern',
        'clipPath', 'mask', 'filter',
        'feGaussianBlur', 'feOffset', 'feBlend', 'feColorMatrix', 'feComposite',
        'feFlood', 'feMerge', 'feMergeNode', 'feMorphology', 'feDropShadow',
        'style',
    ];

    /**
     * Attributes that carry presentation, never behaviour.
     *
     * Every on* handler is absent by construction, and so is anything that
     * could load a second document.
     */
    private const ALLOWED_ATTRIBUTES = [
        'id', 'class', 'style', 'transform', 'viewbox', 'xmlns', 'xmlns:xlink',
        'version', 'width', 'height', 'x', 'y', 'x1', 'y1', 'x2', 'y2',
        'cx', 'cy', 'r', 'rx', 'ry', 'd', 'points', 'dx', 'dy', 'rotate',
        'fill', 'fill-opacity', 'fill-rule', 'stroke', 'stroke-width',
        'stroke-linecap', 'stroke-linejoin', 'stroke-dasharray', 'stroke-dashoffset',
        'stroke-opacity', 'stroke-miterlimit', 'opacity', 'color',
        'offset', 'stop-color', 'stop-opacity', 'gradientunits', 'gradienttransform',
        'patternunits', 'patterncontenttunits', 'clip-path', 'clip-rule', 'mask', 'filter',
        'font-family', 'font-size', 'font-weight', 'font-style', 'text-anchor',
        'dominant-baseline', 'letter-spacing', 'preserveaspectratio',
        'stddeviation', 'result', 'in', 'in2', 'mode', 'type', 'values',
        'flood-color', 'flood-opacity', 'href', 'xlink:href',
    ];

    /**
     * Return a cleaned copy of the markup, or null if it is not usable SVG.
     */
    public function clean(string $svg): ?string
    {
        // A DOCTYPE is how external entities get declared, and entity expansion
        // is how a 3KB upload becomes a gigabyte of memory. Neither is needed
        // by any real icon.
        if (preg_match('/<!DOCTYPE|<!ENTITY/i', $svg)) {
            return null;
        }

        $document = new DOMDocument();
        $document->preserveWhiteSpace = false;
        $document->formatOutput = false;

        $previous = libxml_use_internal_errors(true);

        // LIBXML_NONET refuses network access during parsing; NOENT is
        // deliberately not passed, so entities are never substituted.
        $loaded = $document->loadXML($svg, LIBXML_NONET | LIBXML_NOERROR | LIBXML_NOWARNING);

        libxml_clear_errors();
        libxml_use_internal_errors($previous);

        if (! $loaded || ! $document->documentElement) {
            return null;
        }

        if (strtolower($document->documentElement->nodeName) !== 'svg') {
            return null;
        }

        $this->scrub($document->documentElement);

        $cleaned = $document->saveXML($document->documentElement);

        return $cleaned !== false ? $cleaned : null;
    }

    /**
     * Walk the tree, dropping anything not on the allow-list.
     */
    private function scrub(DOMNode $node): void
    {
        // Children are collected first: removing a node while iterating a live
        // DOMNodeList silently skips its sibling.
        $children = [];

        foreach ($node->childNodes as $child) {
            $children[] = $child;
        }

        foreach ($children as $child) {
            if (! $child instanceof DOMElement) {
                // Comments can hide markup that some parsers resurrect.
                if ($child->nodeType === XML_COMMENT_NODE) {
                    $node->removeChild($child);
                }

                continue;
            }

            $name = strtolower($child->nodeName);

            if (! in_array($name, array_map('strtolower', self::ALLOWED_ELEMENTS), true)) {
                $node->removeChild($child);

                continue;
            }

            if ($name === 'style') {
                $child->textContent = $this->cleanCss($child->textContent);
            }

            $this->scrubAttributes($child);
            $this->scrub($child);
        }
    }

    private function scrubAttributes(DOMElement $element): void
    {
        $attributes = [];

        foreach ($element->attributes as $attribute) {
            $attributes[] = $attribute->nodeName;
        }

        foreach ($attributes as $name) {
            $lower = strtolower($name);

            // Every event handler, in one rule.
            if (str_starts_with($lower, 'on')) {
                $element->removeAttribute($name);

                continue;
            }

            if (! in_array($lower, self::ALLOWED_ATTRIBUTES, true)) {
                $element->removeAttribute($name);

                continue;
            }

            $value = trim($element->getAttribute($name));

            // A reference may point inside this document, and nowhere else —
            // no javascript:, no remote fetches, no data: payloads.
            if (in_array($lower, ['href', 'xlink:href'], true) && ! str_starts_with($value, '#')) {
                $element->removeAttribute($name);

                continue;
            }

            if (preg_match('/javascript:|expression\s*\(|behaviou?r\s*:|@import|url\s*\(\s*["\']?\s*(?!#)/i', $value)) {
                $element->removeAttribute($name);
            }
        }
    }

    private function cleanCss(string $css): string
    {
        // Anything that fetches or executes. Plain declarations are untouched.
        return preg_replace(
            '/@import[^;]*;|expression\s*\([^)]*\)|javascript:|url\s*\(\s*["\']?\s*(?!#)[^)]*\)/i',
            '',
            $css
        ) ?? '';
    }
}

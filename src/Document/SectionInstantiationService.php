<?php

declare(strict_types=1);

namespace OdtTemplateEngine\Document;

use DOMElement;
use OdtTemplateEngine\OdtDocumentContext;
use OdtTemplateEngine\Template\TemplateProcessor;

/**
 * Instantiates one section by binding scalar values into a rewritten clone.
 */
final class SectionInstantiationService
{
    public function __construct(
        private readonly SectionCloneService $cloneService = new SectionCloneService(),
        private readonly TemplateProcessor $processor = new TemplateProcessor()
    ) {
    }

    /**
     * @param array<string, scalar|null> $values
     */
    public function instantiate(OdtDocumentContext $context, string $sectionName, array $values): DOMElement
    {
        foreach ($values as $key => $value) {
            if ($key === '' || ($value !== null && !is_scalar($value))) {
                throw new SectionInstantiationException($sectionName, 'invalid binding data', (string) $key);
            }
        }

        return $this->cloneService->cloneWithRewrittenIdentities(
            $context,
            $sectionName,
            function (DOMElement $clone, int $index) use ($values, $sectionName): void {
                $unsupported = $this->processor->unsupportedExpressions($clone);
                if ($unsupported !== []) {
                    throw new SectionInstantiationException(
                        $sectionName,
                        'unsupported expression in clone',
                        $unsupported[0]
                    );
                }

                $binding = [];
                foreach ($this->processor->scalarVariableNames($clone) as $variable) {
                    if (!preg_match('/^(.*)_' . $index . '$/', $variable, $match)) {
                        throw new SectionInstantiationException($sectionName, 'unrewritten clone variable', $variable);
                    }
                    $sourceVariable = $match[1];
                    if (!array_key_exists($sourceVariable, $values)) {
                        throw new SectionInstantiationException($sectionName, 'missing required value', $sourceVariable);
                    }
                    $binding[$variable] = $values[$sourceVariable] === null
                        ? ''
                        : (string) $values[$sourceVariable];
                }

                $this->processor->replaceScalarTextInSubtree(
                    $clone,
                    $binding,
                    [$this->processor, 'applyFilter']
                );
            },
            $this->lastInstance($context, $sectionName)
        );
    }

    private function lastInstance(OdtDocumentContext $context, string $prototypeName): ?DOMElement
    {
        $source = null;
        foreach ($context->contentDom()->getElementsByTagNameNS(
            'urn:oasis:names:tc:opendocument:xmlns:text:1.0',
            'section'
        ) as $node) {
            if ($node instanceof DOMElement && $node->getAttribute('text:name') === $prototypeName) {
                $source = $node;
                break;
            }
        }
        if (!$source instanceof DOMElement || !$source->parentNode) {
            return null;
        }

        $last = null;
        for ($sibling = $source->nextSibling; $sibling !== null; $sibling = $sibling->nextSibling) {
            if (!$sibling instanceof DOMElement || $sibling->nodeName !== 'text:section') {
                continue;
            }
            if (preg_match('/^' . preg_quote($prototypeName, '/') . '_\d+$/', $sibling->getAttribute('text:name')) === 1) {
                $last = $sibling;
            }
        }

        return $last;
    }
}

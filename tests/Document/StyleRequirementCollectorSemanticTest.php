<?php

declare(strict_types=1);

namespace OdtTemplateEngine\Tests\Document;

use DOMDocument;
use DOMNode;
use OdtTemplateEngine\Document\StyleRequirement;
use OdtTemplateEngine\Document\StyleRequirementCollector;
use OdtTemplateEngine\Elements\OdtElement;
use PHPUnit\Framework\TestCase;

final class StyleRequirementCollectorSemanticTest extends TestCase
{
    public function testSemanticCollectionTraversesOwnedElementsAndPreservesOccurrences(): void
    {
        $first = new StyleRequirement(
            StyleRequirement::KIND_REFERENCE,
            null,
            'paragraph',
            null,
            'First'
        );
        $second = new StyleRequirement(
            StyleRequirement::KIND_REFERENCE,
            null,
            'paragraph',
            null,
            'Second'
        );
        $root = (new SemanticComposite())->addElement(new SemanticProvider($first))->addElement(
            (new SemanticComposite())->addElement(new SemanticProvider($second))
        );

        $requirements = iterator_to_array((new StyleRequirementCollector())->collectSemantic($root), false);

        self::assertSame([$first, $second], $requirements);
    }
}

final class SemanticProvider extends OdtElement
{
    public function __construct(private StyleRequirement $requirement)
    {
    }

    public function toDomNode(DOMDocument $dom): DOMNode
    {
        return $dom->createElement('text:p');
    }

    public function registerStyles(): void
    {
    }

    public function getOwnStyleRequirements(): iterable
    {
        yield $this->requirement;
    }
}

final class SemanticComposite extends OdtElement
{
    public function registerStyles(): void
    {
    }

    public function toDomNode(DOMDocument $dom): DOMNode
    {
        return $dom->createElement('text:p');
    }
}

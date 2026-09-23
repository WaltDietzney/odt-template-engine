<?php

declare(strict_types=1);

namespace OdtTemplateEngine\Tests\Document;

use DOMDocument;
use DOMElement;
use OdtTemplateEngine\Document\DeclarativeConditionExecutionException;
use OdtTemplateEngine\Document\DeclarativeConditionExecutor;
use OdtTemplateEngine\OdtDocumentContext;
use OdtTemplateEngine\Template\ControlDescriptor;
use OdtTemplateEngine\Template\TemplateContract;
use OdtTemplateEngine\Template\TemplateContractInspector;
use PHPUnit\Framework\TestCase;

final class DeclarativeConditionExecutorTest extends TestCase
{
    private const OFFICE = 'urn:oasis:names:tc:opendocument:xmlns:office:1.0';
    private const STYLE = 'urn:oasis:names:tc:opendocument:xmlns:style:1.0';
    private const TEXT = 'urn:oasis:names:tc:opendocument:xmlns:text:1.0';

    public function testBodyIfTruePreservesTheNativeSectionAndItsContent(): void
    {
        [$context, $contract] = $this->fixture([
            ['body', '#if:show', 'BODY', null],
        ]);

        (new DeclarativeConditionExecutor())->execute($context, $contract, ['show' => true]);

        $section = $this->findSection($context->contentDom(), '#if:show');
        self::assertNotNull($section);
        self::assertSame('BODY', $section?->textContent);
        self::assertSame('AuthoredSectionStyle', $section?->getAttribute('text:style-name'));
    }

    public function testTrueConditionalSectionNormalizesAuthoredHiddenState(): void
    {
        [$context, $contract] = $this->fixture([
            ['body', '#if:show', 'BODY {{name}}', null, [], [
                'text:display' => 'none',
                'text:is-hidden' => 'true',
            ]],
        ]);

        (new DeclarativeConditionExecutor())->execute(
            $context,
            $contract,
            ['show' => true, 'name' => 'Visible body']
        );

        $section = $this->findSection($context->contentDom(), '#if:show');
        self::assertNotNull($section);
        self::assertSame('BODY Visible body', $section?->textContent);
        self::assertSame('', $section?->getAttributeNS(self::TEXT, 'display'));
        self::assertSame('', $section?->getAttributeNS(self::TEXT, 'is-hidden'));
    }

    public function testFalseConditionalSectionWithAuthoredHiddenStateIsRemoved(): void
    {
        [$context, $contract] = $this->fixture([
            ['body', '#if:show', 'BODY', null, [], ['text:display' => 'none']],
        ]);

        (new DeclarativeConditionExecutor())->execute($context, $contract, ['show' => false]);

        self::assertNull($this->findSection($context->contentDom(), '#if:show'));
    }

    public function testTrueVisibleConditionalSectionDoesNotReceiveVisibilityAttributes(): void
    {
        [$context, $contract] = $this->fixture([
            ['body', '#if:show', 'BODY', null],
        ]);

        (new DeclarativeConditionExecutor())->execute($context, $contract, ['show' => true]);

        $section = $this->findSection($context->contentDom(), '#if:show');
        self::assertNotNull($section);
        self::assertFalse($section?->hasAttributeNS(self::TEXT, 'display'));
        self::assertFalse($section?->hasAttributeNS(self::TEXT, 'is-hidden'));
    }

    public function testOrdinaryHiddenSectionIsNotChanged(): void
    {
        [$context, $contract] = $this->fixture([
            ['body', 'OrdinarySection', 'ORDINARY', null, [], [
                'text:display' => 'none',
                'text:is-hidden' => 'true',
            ]],
        ]);

        (new DeclarativeConditionExecutor())->execute($context, $contract, []);

        $section = $this->findSection($context->contentDom(), 'OrdinarySection');
        self::assertNotNull($section);
        self::assertSame('none', $section?->getAttributeNS(self::TEXT, 'display'));
        self::assertSame('true', $section?->getAttributeNS(self::TEXT, 'is-hidden'));
    }

    public function testBodyIfFalseRemovesTheCompleteSectionSubtree(): void
    {
        [$context, $contract] = $this->fixture([
            ['body', '#if:show', 'BODY', null],
        ]);

        (new DeclarativeConditionExecutor())->execute($context, $contract, ['show' => false]);

        self::assertNull($this->findSection($context->contentDom(), '#if:show'));
    }

    public function testIfnotUsesTheExistingConditionSemanticsAndNegatesTheResult(): void
    {
        [$context, $contract] = $this->fixture([
            ['body', '#ifnot:hidden', 'VISIBLE', null],
        ]);

        (new DeclarativeConditionExecutor())->execute($context, $contract, ['hidden' => false]);
        self::assertNotNull($this->findSection($context->contentDom(), '#ifnot:hidden'));

        [$context, $contract] = $this->fixture([
            ['body', '#ifnot:hidden', 'REMOVED', null],
        ]);
        (new DeclarativeConditionExecutor())->execute($context, $contract, ['hidden' => true]);
        self::assertNull($this->findSection($context->contentDom(), '#ifnot:hidden'));
    }

    public function testSameDeclarationNamesAcrossBodyHeaderFooterAndMasterPagesRemainBounded(): void
    {
        [$context, $contract] = $this->fixture([
            ['body', '#if:show', 'BODY', null],
            ['header', '#if:show', 'STANDARD HEADER', 'Standard'],
            ['footer', '#if:show', 'STANDARD FOOTER', 'Standard'],
            ['header', '#if:show', 'FIRST HEADER', 'First'],
        ]);

        (new DeclarativeConditionExecutor())->execute($context, $contract, ['show' => true]);

        self::assertSame(1, $this->sectionCount($context->contentDom(), '#if:show'));
        self::assertSame(3, $this->sectionCount($context->stylesDom(), '#if:show'));
        self::assertSame(2, $this->sectionCount($context->stylesDom(), '#if:show', 'style:header'));
        self::assertSame(1, $this->sectionCount($context->stylesDom(), '#if:show', 'style:footer'));
    }

    public function testHeaderAndFooterConditionsCanIndependentlySurviveOrBeRemoved(): void
    {
        [$context, $contract] = $this->fixture([
            ['header', '#if:showHeader', 'HEADER', 'Standard'],
            ['footer', '#if:showFooter', 'FOOTER', 'Standard'],
        ]);

        (new DeclarativeConditionExecutor())->execute(
            $context,
            $contract,
            ['showHeader' => true, 'showFooter' => false]
        );

        self::assertNotNull($this->findSection($context->stylesDom(), '#if:showHeader'));
        self::assertNull($this->findSection($context->stylesDom(), '#if:showFooter'));
    }

    public function testNestedConditionsExecuteParentFirstAndRemainWithinTheParentRegion(): void
    {
        [$context, $contract] = $this->fixture([
            ['body', '#if:outer', 'OUTER', null, [
                ['#if:inner', 'INNER'],
            ]],
        ]);

        (new DeclarativeConditionExecutor())->execute(
            $context,
            $contract,
            ['outer' => true, 'inner' => true]
        );

        self::assertNotNull($this->findSection($context->contentDom(), '#if:outer'));
        self::assertNotNull($this->findSection($context->contentDom(), '#if:inner'));

        [$context, $contract] = $this->fixture([
            ['body', '#if:outer', 'OUTER', null, [
                ['#if:inner', 'INNER'],
            ]],
        ]);
        (new DeclarativeConditionExecutor())->execute(
            $context,
            $contract,
            ['outer' => true, 'inner' => false]
        );
        self::assertNotNull($this->findSection($context->contentDom(), '#if:outer'));
        self::assertNull($this->findSection($context->contentDom(), '#if:inner'));

        [$context, $contract] = $this->fixture([
            ['body', '#if:outer', 'OUTER', null, [
                ['#if:inner', 'INNER'],
            ]],
        ]);
        (new DeclarativeConditionExecutor())->execute(
            $context,
            $contract,
            ['outer' => false, 'inner' => true]
        );
        self::assertNull($this->findSection($context->contentDom(), '#if:outer'));
        self::assertSame(0, $this->sectionCount($context->contentDom(), '#if:inner'));
    }

    public function testThreeLevelNativeOwnershipUsesTheDirectConditionalParent(): void
    {
        [$context, $contract] = $this->fixture([
            ['body', '#if:A', 'A', null, [
                ['#if:B', 'B', [
                    ['#if:C', 'C'],
                ]],
            ]],
        ]);

        $controls = array_values(array_filter(
            $contract->nativeObjects(),
            static fn ($object): bool => in_array($object->name(), ['#if:A', '#if:B', '#if:C'], true)
        ));
        $objects = [];
        foreach ($controls as $object) {
            $objects[$object->name()] = $object;
        }

        self::assertSame([], $objects['#if:A']->ownerIds());
        self::assertSame([$objects['#if:A']->id()], $objects['#if:B']->ownerIds());
        self::assertSame(
            [$objects['#if:A']->id(), $objects['#if:B']->id()],
            $objects['#if:C']->ownerIds()
        );

        (new DeclarativeConditionExecutor())->execute(
            $context,
            $contract,
            ['A' => true, 'B' => false, 'C' => true]
        );

        self::assertNotNull($this->findSection($context->contentDom(), '#if:A'));
        self::assertNull($this->findSection($context->contentDom(), '#if:B'));
        self::assertNull($this->findSection($context->contentDom(), '#if:C'));
    }

    public function testMixedNestedIfAndIfnotConditionsAreEvaluatedUsingTheSharedGrammar(): void
    {
        [$context, $contract] = $this->fixture([
            ['body', '#if:outer', 'OUTER', null, [
                ['#ifnot:blocked', 'INNER'],
            ]],
        ]);

        (new DeclarativeConditionExecutor())->execute(
            $context,
            $contract,
            ['outer' => true, 'blocked' => false]
        );

        self::assertNotNull($this->findSection($context->contentDom(), '#if:outer'));
        self::assertNotNull($this->findSection($context->contentDom(), '#ifnot:blocked'));
    }

    public function testMissingCarrierFailsExplicitly(): void
    {
        [$context, $contract] = $this->fixture([
            ['body', '#if:show', 'BODY', null],
        ]);
        $control = $contract->controls()[0];
        self::assertInstanceOf(ControlDescriptor::class, $control);
        $broken = new ControlDescriptor(
            $control->id(),
            $control->kind(),
            $control->representation(),
            $control->supportState(),
            $control->scope(),
            $control->markerEvidence(),
            $control->dependencyIds(),
            $control->createdScope(),
            'missing-native-object'
        );
        $brokenContract = new TemplateContract(
            $contract->bindings(),
            [$broken],
            $contract->nativeObjects(),
            $contract->dependencies(),
            $contract->diagnostics(),
            $contract->coverage(),
            $contract->capabilities()
        );

        $this->expectException(DeclarativeConditionExecutionException::class);
        (new DeclarativeConditionExecutor())->execute($context, $brokenContract, ['show' => true]);
    }

    /** @param list<array{0:string,1:string,2:string,3:?string,4?:list<array>,5?:array<string,string>}> $sections */
    private function fixture(array $sections): array
    {
        $sourceContent = $this->document('office:document-content');
        $body = $sourceContent->createElementNS(self::OFFICE, 'office:body');
        $text = $sourceContent->createElementNS(self::OFFICE, 'office:text');
        foreach ($sections as $definition) {
            if ($definition[0] === 'body') {
                $text->appendChild($this->section(
                    $sourceContent,
                    $definition[1],
                    $definition[2],
                    $definition[4] ?? [],
                    $definition[5] ?? []
                ));
            }
        }
        $body->appendChild($text);
        $sourceContent->documentElement->appendChild($body);

        $sourceStyles = $this->document('office:document-styles');
        $masters = [];
        foreach ($sections as $definition) {
            if ($definition[3] === null) {
                continue;
            }
            $masters[$definition[3]] ??= $this->masterPage($sourceStyles, $definition[3]);
            $carrier = $sourceStyles->createElementNS(self::STYLE, $definition[0] === 'header' ? 'style:header' : 'style:footer');
            $carrier->appendChild($this->section(
                $sourceStyles,
                $definition[1],
                $definition[2],
                [],
                $definition[5] ?? []
            ));
            $masters[$definition[3]]->appendChild($carrier);
        }
        foreach ($masters as $master) {
            $sourceStyles->documentElement->appendChild($master);
        }

        $workingContent = $this->copy($sourceContent);
        $workingStyles = $this->copy($sourceStyles);
        $contract = (new TemplateContractInspector())->inspect($sourceContent, $sourceStyles);

        return [
            new OdtDocumentContext($workingContent, $workingStyles, $this->document('office:document-meta')),
            $contract,
        ];
    }

    private function section(
        DOMDocument $dom,
        string $name,
        string $text,
        array $children = [],
        array $attributes = []
    ): DOMElement
    {
        $section = $dom->createElementNS(self::TEXT, 'text:section');
        $section->setAttribute('text:name', $name);
        $section->setAttribute('text:style-name', 'AuthoredSectionStyle');
        foreach ($attributes as $attribute => $value) {
            $section->setAttributeNS(self::TEXT, $attribute, $value);
        }
        $paragraph = $dom->createElementNS(self::TEXT, 'text:p');
        $paragraph->appendChild($dom->createTextNode($text));
        $section->appendChild($paragraph);
        foreach ($children as $child) {
            $section->appendChild($this->section($dom, $child[0], $child[1], $child[2] ?? []));
        }
        return $section;
    }

    private function masterPage(DOMDocument $dom, string $name): DOMElement
    {
        $master = $dom->createElementNS(self::STYLE, 'style:master-page');
        $master->setAttribute('style:name', $name);
        return $master;
    }

    private function sectionCount(DOMDocument $dom, string $name, ?string $carrier = null): int
    {
        if ($carrier === null) {
            return count(array_filter(
                iterator_to_array($dom->getElementsByTagNameNS(self::TEXT, 'section')),
                static fn ($section): bool => $section instanceof DOMElement
                    && $section->getAttribute('text:name') === $name
            ));
        }

        $count = 0;
        foreach ($dom->getElementsByTagNameNS(self::STYLE, substr($carrier, 6)) as $container) {
            foreach ($container->getElementsByTagNameNS(self::TEXT, 'section') as $section) {
                if ($section instanceof DOMElement && $section->getAttribute('text:name') === $name) {
                    ++$count;
                }
            }
        }
        return $count;
    }

    private function findSection(DOMDocument $dom, string $name): ?DOMElement
    {
        foreach ($dom->getElementsByTagNameNS(self::TEXT, 'section') as $section) {
            if ($section instanceof DOMElement && $section->getAttribute('text:name') === $name) {
                return $section;
            }
        }
        return null;
    }

    private function document(string $root): DOMDocument
    {
        $dom = new DOMDocument('1.0', 'UTF-8');
        $dom->loadXML(sprintf(
            '<%s xmlns:office="%s" xmlns:style="%s" xmlns:text="%s"/>',
            $root,
            self::OFFICE,
            self::STYLE,
            self::TEXT
        ));
        return $dom;
    }

    private function copy(DOMDocument $source): DOMDocument
    {
        $copy = new DOMDocument('1.0', 'UTF-8');
        $copy->loadXML($source->saveXML());
        return $copy;
    }
}

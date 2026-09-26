<?php

declare(strict_types=1);

namespace OdtTemplateEngine\Tests\Document;

use DOMDocument;
use DOMElement;
use OdtTemplateEngine\Document\DeclarativeConditionExecutor;
use OdtTemplateEngine\Document\DeclarativeForeachExecutionException;
use OdtTemplateEngine\OdtDocumentContext;
use OdtTemplateEngine\OdtTemplate;
use OdtTemplateEngine\Template\TemplateContract;
use OdtTemplateEngine\Template\TemplateContractInspector;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

final class DeclarativeForeachExecutionTest extends TestCase
{
    private const OFFICE = 'urn:oasis:names:tc:opendocument:xmlns:office:1.0';
    private const STYLE = 'urn:oasis:names:tc:opendocument:xmlns:style:1.0';
    private const TEXT = 'urn:oasis:names:tc:opendocument:xmlns:text:1.0';

    public function testBodyForeachBindsItemsAndExistingFilters(): void
    {
        [$context, $contract] = $this->fixture([
            $this->definition('body', '#foreach:people', '{{company}} / {{upper:position}}'),
        ]);

        (new DeclarativeConditionExecutor())->execute($context, $contract, [
            'people' => [
                ['company' => 'Firma A', 'position' => 'projektleiter'],
                ['company' => 'Firma B', 'position' => 'entwickler'],
            ],
        ]);

        self::assertSame(2, $this->sectionCount($context->contentDom(), '#foreach:people'));
        self::assertStringContainsString('Firma A / PROJEKTLEITER', $this->sectionTexts($context->contentDom()));
        self::assertStringContainsString('Firma B / ENTWICKLER', $this->sectionTexts($context->contentDom()));
        self::assertStringNotContainsString('{{company', $this->sectionTexts($context->contentDom()));
    }

    public function testBodyForeachPreservesCollectionOrderAndIdentityMapping(): void
    {
        [$context, $contract] = $this->fixture([
            $this->definition('body', '#foreach:people', '{{name}}'),
        ]);

        (new DeclarativeConditionExecutor())->execute($context, $contract, [
            'people' => [
                ['name' => 'A'],
                ['name' => 'B'],
                ['name' => 'C'],
            ],
        ]);

        self::assertSame(
            ['#foreach:people_1', '#foreach:people_2', '#foreach:people_3'],
            $this->orderedSectionNames($context->contentDom(), '#foreach:people')
        );
        self::assertSame(['A', 'B', 'C'], $this->orderedSectionTexts($context->contentDom(), '#foreach:people'));
    }

    public function testFooterForeachPreservesCollectionOrderAndIdentityMapping(): void
    {
        [$context, $contract] = $this->fixture([
            $this->definition('footer', '#foreach:footer_items', '{{label}}', 'Standard'),
        ]);

        (new DeclarativeConditionExecutor())->execute($context, $contract, [
            'footer_items' => [
                ['label' => 'A'],
                ['label' => 'B'],
                ['label' => 'C'],
            ],
        ]);

        self::assertSame(
            ['#foreach:footer_items_1', '#foreach:footer_items_2', '#foreach:footer_items_3'],
            $this->orderedSectionNames($context->stylesDom(), '#foreach:footer_items')
        );
        self::assertSame(['A', 'B', 'C'], $this->orderedSectionTexts($context->stylesDom(), '#foreach:footer_items'));
    }

    public function testBodyForeachOrderSurvivesSaveAndReopen(): void
    {
        $output = sys_get_temp_dir() . '/template-authoring-01d-order-' . uniqid('', true) . '.odt';
        $template = new class(__DIR__ . '/../fixtures/libreoffice-reference/odt/TEMPLATE-AUTHORING-01B-inspection-contract.odt') extends OdtTemplate {
            public function context(): OdtDocumentContext
            {
                return $this->documentContext();
            }
        };

        try {
            (new DeclarativeConditionExecutor())->execute($template->context(), $template->inspectTemplate(), [
                'show_profile' => true,
                'profile' => 'Profile',
                'hidden' => false,
                'experience' => [
                    ['company' => 'A', 'role' => 'A'],
                    ['company' => 'B', 'role' => 'B'],
                    ['company' => 'C', 'role' => 'C'],
                ],
            ]);
            $template->save($output);

            $reopened = new OdtTemplate($output);
            self::assertSame(
                ['#foreach:experience_1', '#foreach:experience_2', '#foreach:experience_3'],
                array_values(array_map(
                    static fn ($section): string => $section->name(),
                    array_filter(
                        $reopened->inspect()->sections(),
                        static fn ($section): bool => str_starts_with($section->name(), '#foreach:experience_')
                    )
                ))
            );
        } finally {
            if (is_file($output)) {
                unlink($output);
            }
        }
    }

    public function testEmptyCollectionConsumesPrototypeWithoutCreatingInstances(): void
    {
        [$context, $contract] = $this->fixture([
            $this->definition('body', '#foreach:people', '{{company}}'),
        ]);

        (new DeclarativeConditionExecutor())->execute($context, $contract, ['people' => []]);

        self::assertSame(0, $this->sectionCount($context->contentDom(), '#foreach:people'));
    }

    public function testEmptyItemRecordIsValidWhenNoItemDependencyIsRequired(): void
    {
        [$context, $contract] = $this->fixture([
            $this->definition('body', '#foreach:people', 'static content'),
        ]);

        (new DeclarativeConditionExecutor())->execute($context, $contract, [
            'people' => [[]],
        ]);

        self::assertSame(1, $this->sectionCount($context->contentDom(), '#foreach:people'));
        self::assertStringContainsString('static content', $this->sectionTexts($context->contentDom()));
    }

    /** @return iterable<string, array{array<string, mixed>}> */
    public static function invalidCollectionItems(): iterable
    {
        yield 'scalar item' => [['people' => ['scalar item']]];
        yield 'null item' => [['people' => [null]]];
        yield 'positional item' => [['people' => [['Alice', 'Developer']]]];
    }

    #[DataProvider('invalidCollectionItems')]
    public function testInvalidCollectionItemsFailExplicitly(array $values): void
    {
        [$context, $contract] = $this->fixture([
            $this->definition('body', '#foreach:people', 'static content'),
        ]);

        $this->expectException(DeclarativeForeachExecutionException::class);
        (new DeclarativeConditionExecutor())->execute($context, $contract, $values);
    }

    /** @return iterable<string, array{mixed}> */
    public static function invalidCollections(): iterable
    {
        yield 'missing' => [null];
        yield 'null' => [['people' => null]];
        yield 'scalar' => [['people' => 'not-a-collection']];
    }

    #[DataProvider('invalidCollections')]
    public function testInvalidCollectionValuesFailExplicitly(mixed $values): void
    {
        [$context, $contract] = $this->fixture([
            $this->definition('body', '#foreach:people', '{{company}}'),
        ]);

        $this->expectException(DeclarativeForeachExecutionException::class);
        (new DeclarativeConditionExecutor())->execute($context, $contract, $values ?? []);
    }

    public function testForeachConditionAndNestedForeachUseEachItemScope(): void
    {
        [$context, $contract] = $this->fixture([
            $this->definition('body', '#foreach:experience', '{{company}}', null, [
                $this->definition('body', '#if:current', 'Current {{position}}', null, [
                    $this->definition('body', '#foreach:projects', '{{project}}'),
                ]),
            ]),
        ]);

        (new DeclarativeConditionExecutor())->execute($context, $contract, [
            'experience' => [
                [
                    'company' => 'Firma A',
                    'position' => 'Projektleiter',
                    'current' => true,
                    'projects' => [
                        ['project' => 'A1'],
                        ['project' => 'A2'],
                    ],
                ],
                [
                    'company' => 'Firma B',
                    'position' => 'Entwickler',
                    'current' => false,
                    'projects' => [
                        ['project' => 'B1'],
                    ],
                ],
            ],
        ]);

        $texts = $this->sectionTexts($context->contentDom());
        self::assertStringContainsString('Firma A', $texts);
        self::assertStringContainsString('Current Projektleiter', $texts);
        self::assertStringContainsString('A1', $texts);
        self::assertStringContainsString('A2', $texts);
        self::assertStringContainsString('Firma B', $texts);
        self::assertStringNotContainsString('Current Entwickler', $texts);
        self::assertStringNotContainsString('B1', $texts);
        self::assertSame(2, $this->sectionCount($context->contentDom(), '#foreach:experience'));
    }

    public function testForeachIfnotUsesEachItemScope(): void
    {
        [$context, $contract] = $this->fixture([
            $this->definition('body', '#foreach:people', '{{name}}', null, [
                $this->definition('body', '#ifnot:archived', 'ACTIVE'),
            ]),
        ]);

        (new DeclarativeConditionExecutor())->execute($context, $contract, [
            'people' => [
                ['name' => 'Current', 'archived' => false],
                ['name' => 'Old', 'archived' => true],
            ],
        ]);

        self::assertStringContainsString('Current', $this->sectionTexts($context->contentDom()));
        self::assertStringContainsString('ACTIVE', $this->sectionTexts($context->contentDom()));
        self::assertStringContainsString('Old', $this->sectionTexts($context->contentDom()));
        self::assertSame(1, $this->sectionCount($context->contentDom(), '#ifnot:archived'));
    }

    public function testNestedForeachUsesIndependentCollectionSizesPerParentItem(): void
    {
        [$context, $contract] = $this->fixture([
            $this->definition('body', '#foreach:experience', '{{company}}', null, [
                $this->definition('body', '#foreach:projects', '{{project}}'),
            ]),
        ]);

        (new DeclarativeConditionExecutor())->execute($context, $contract, [
            'experience' => [
                [
                    'company' => 'A',
                    'projects' => [['project' => 'A1'], ['project' => 'A2']],
                ],
                [
                    'company' => 'B',
                    'projects' => [['project' => 'B1']],
                ],
            ],
        ]);

        $texts = $this->sectionTexts($context->contentDom());
        self::assertStringContainsString('A1', $texts);
        self::assertStringContainsString('A2', $texts);
        self::assertStringContainsString('B1', $texts);
        self::assertSame(3, $this->sectionCount($context->contentDom(), '#foreach:projects'));
    }

    public function testNestedForeachPreservesOuterAndLocalCollectionOrder(): void
    {
        [$context, $contract] = $this->fixture([
            $this->definition('body', '#foreach:outer', '', null, [
                $this->definition('body', '#foreach:inner', ''),
            ]),
        ]);

        (new DeclarativeConditionExecutor())->execute($context, $contract, [
            'outer' => [
                ['inner' => [['value' => 'A1'], ['value' => 'A2']]],
                ['inner' => [['value' => 'B1'], ['value' => 'B2']]],
            ],
        ]);

        self::assertSame(
            [
                '#foreach:outer_1',
                '#foreach:inner_1_1',
                '#foreach:inner_1_2',
                '#foreach:outer_2',
                '#foreach:inner_2_1',
                '#foreach:inner_2_2',
            ],
            $this->orderedSectionNames($context->contentDom(), '#foreach:')
        );
    }

    public function testConditionCanEnableOrRemoveNestedForeach(): void
    {
        [$context, $contract] = $this->fixture([
            $this->definition('body', '#if:show', 'OUTER', null, [
                $this->definition('body', '#foreach:items', '{{name}}'),
            ]),
        ]);

        (new DeclarativeConditionExecutor())->execute($context, $contract, [
            'show' => true,
            'items' => [['name' => 'One'], ['name' => 'Two']],
        ]);

        self::assertSame(2, $this->sectionCount($context->contentDom(), '#foreach:items'));
        self::assertStringContainsString('One', $this->sectionTexts($context->contentDom()));
        self::assertStringContainsString('Two', $this->sectionTexts($context->contentDom()));

        [$context, $contract] = $this->fixture([
            $this->definition('body', '#if:show', 'OUTER', null, [
                $this->definition('body', '#foreach:items', '{{name}}'),
            ]),
        ]);
        (new DeclarativeConditionExecutor())->execute($context, $contract, [
            'show' => false,
            'items' => [['name' => 'Not executed']],
        ]);

        self::assertSame(0, $this->sectionCount($context->contentDom(), '#foreach:items'));
        self::assertStringNotContainsString('Not executed', $this->sectionTexts($context->contentDom()));
    }

    public function testNestedCollectionFailureIsExplicit(): void
    {
        [$context, $contract] = $this->fixture([
            $this->definition('body', '#foreach:experience', '{{company}}', null, [
                $this->definition('body', '#foreach:projects', '{{project}}'),
            ]),
        ]);

        $this->expectException(DeclarativeForeachExecutionException::class);
        (new DeclarativeConditionExecutor())->execute($context, $contract, [
            'experience' => [['company' => 'Missing projects']],
        ]);
    }

    public function testHeaderAndFooterForeachRemainProvenanceBounded(): void
    {
        [$context, $contract] = $this->fixture([
            $this->definition('header', '#foreach:items', '{{name}}', 'Standard'),
            $this->definition('footer', '#foreach:items', '{{name}}', 'Standard'),
        ]);

        (new DeclarativeConditionExecutor())->execute($context, $contract, [
            'items' => [['name' => 'Header/Footer item']],
        ]);

        self::assertSame(1, $this->sectionCount($context->stylesDom(), '#foreach:items', 'style:header'));
        self::assertSame(1, $this->sectionCount($context->stylesDom(), '#foreach:items', 'style:footer'));
        self::assertSame(2, substr_count($this->sectionTexts($context->stylesDom()), 'Header/Footer item'));
    }

    public function testUserFieldInsideForeachIsNotBoundFromItemValues(): void
    {
        [$context, $contract] = $this->fixture([
            $this->definition('body', '#foreach:people', '{{name}}', null, [], true),
        ]);

        (new DeclarativeConditionExecutor())->execute($context, $contract, [
            'people' => [['name' => 'Item value one'], ['name' => 'Item value two']],
        ]);

        self::assertSame(2, $this->sectionCount($context->contentDom(), '#foreach:people'));
        self::assertSame(2, $this->userFieldCount($context->contentDom()));
        self::assertStringNotContainsString('Item value one', $this->userFieldTexts($context->contentDom()));
        self::assertStringNotContainsString('Item value two', $this->userFieldTexts($context->contentDom()));
    }

    /** @return array{0:OdtDocumentContext,1:TemplateContract} */
    private function fixture(array $definitions): array
    {
        $sourceContent = $this->document('office:document-content');
        $body = $sourceContent->createElementNS(self::OFFICE, 'office:body');
        $text = $sourceContent->createElementNS(self::OFFICE, 'office:text');
        foreach ($definitions as $definition) {
            if ($definition['region'] === 'body') {
                $text->appendChild($this->section($sourceContent, $definition));
            }
        }
        $body->appendChild($text);
        $sourceContent->documentElement->appendChild($body);

        $sourceStyles = $this->document('office:document-styles');
        $masters = [];
        foreach ($definitions as $definition) {
            if ($definition['region'] === 'body') {
                continue;
            }
            $masterName = $definition['owner'];
            $masters[$masterName] ??= $this->masterPage($sourceStyles, $masterName);
            $carrier = $sourceStyles->createElementNS(
                self::STYLE,
                $definition['region'] === 'header' ? 'style:header' : 'style:footer'
            );
            $carrier->appendChild($this->section($sourceStyles, $definition));
            $masters[$masterName]->appendChild($carrier);
        }
        foreach ($masters as $master) {
            $sourceStyles->documentElement->appendChild($master);
        }

        $contract = (new TemplateContractInspector())->inspect($sourceContent, $sourceStyles);
        return [
            new OdtDocumentContext(
                $this->copy($sourceContent),
                $this->copy($sourceStyles),
                $this->document('office:document-meta')
            ),
            $contract,
        ];
    }

    private function definition(
        string $region,
        string $name,
        string $text,
        ?string $owner = null,
        array $children = [],
        bool $userField = false
    ): array {
        return compact('region', 'name', 'text', 'owner', 'children', 'userField');
    }

    private function section(DOMDocument $dom, array $definition): DOMElement
    {
        $section = $dom->createElementNS(self::TEXT, 'text:section');
        $section->setAttribute('text:name', $definition['name']);
        $paragraph = $dom->createElementNS(self::TEXT, 'text:p');
        if ($definition['userField']) {
            $field = $dom->createElementNS(self::TEXT, 'text:user-field-get');
            $field->setAttribute('text:name', 'root_customer');
            $field->appendChild($dom->createTextNode('ROOT VALUE'));
            $paragraph->appendChild($field);
        }
        $paragraph->appendChild($dom->createTextNode($definition['text']));
        $section->appendChild($paragraph);
        foreach ($definition['children'] as $child) {
            $section->appendChild($this->section($dom, $child));
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
            $count = 0;
            foreach ($dom->getElementsByTagNameNS(self::TEXT, 'section') as $section) {
                if ($section instanceof DOMElement && str_starts_with($section->getAttribute('text:name'), $name . '_')) {
                    ++$count;
                }
            }
            return $count;
        }

        $count = 0;
        foreach ($dom->getElementsByTagNameNS(self::STYLE, substr($carrier, 6)) as $container) {
            foreach ($container->getElementsByTagNameNS(self::TEXT, 'section') as $section) {
                if ($section instanceof DOMElement && str_starts_with($section->getAttribute('text:name'), $name . '_')) {
                    ++$count;
                }
            }
        }
        return $count;
    }

    private function sectionTexts(DOMDocument $dom): string
    {
        $text = '';
        foreach ($dom->getElementsByTagNameNS(self::TEXT, 'section') as $section) {
            $text .= '|' . ($section->textContent ?? '');
        }
        return $text;
    }

    /** @return list<string> */
    private function orderedSectionNames(DOMDocument $dom, string $prefix): array
    {
        $names = [];
        foreach ($dom->getElementsByTagNameNS(self::TEXT, 'section') as $section) {
            if (!$section instanceof DOMElement) {
                continue;
            }
            $name = $section->getAttribute('text:name');
            if ($prefix === '#foreach:' || str_starts_with($name, $prefix . '_')) {
                $names[] = $name;
            }
        }

        return $names;
    }

    /** @return list<string> */
    private function orderedSectionTexts(DOMDocument $dom, string $prefix): array
    {
        $texts = [];
        foreach ($dom->getElementsByTagNameNS(self::TEXT, 'section') as $section) {
            if (!$section instanceof DOMElement) {
                continue;
            }
            $name = $section->getAttribute('text:name');
            if (str_starts_with($name, $prefix . '_')) {
                $texts[] = trim($section->textContent ?? '');
            }
        }

        return $texts;
    }

    private function userFieldCount(DOMDocument $dom): int
    {
        return $dom->getElementsByTagNameNS(self::TEXT, 'user-field-get')->length;
    }

    private function userFieldTexts(DOMDocument $dom): string
    {
        $text = '';
        foreach ($dom->getElementsByTagNameNS(self::TEXT, 'user-field-get') as $field) {
            $text .= '|' . ($field->textContent ?? '');
        }
        return $text;
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

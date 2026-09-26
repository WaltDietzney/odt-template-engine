<?php

declare(strict_types=1);

namespace OdtTemplateEngine\Tests\Integration;

use DOMDocument;
use DOMXPath;
use OdtTemplateEngine\Mapping\ApplicationPath;
use OdtTemplateEngine\Mapping\ConcreteMappingPreflight;
use OdtTemplateEngine\Mapping\DependencyMapping;
use OdtTemplateEngine\Mapping\MappingDefinition;
use OdtTemplateEngine\OdtTemplate;
use OdtTemplateEngine\Template\TemplateContract;
use OdtTemplateEngine\Tests\Support\MappingTemplateFixture;
use PHPUnit\Framework\TestCase;
use ZipArchive;

final class TemplateAuthoring01E3DependencyAutomationTest extends TestCase
{
    /** @var list<string> */
    private array $paths = [];

    protected function tearDown(): void
    {
        foreach ($this->paths as $path) {
            if (is_file($path)) {
                unlink($path);
            }
        }
    }

    public function testProjectionExecutesNestedScopesAndClassicConsumersWithoutApplicationNames(): void
    {
        $source = $this->fixture(
            additionalBody: '<text:p>{{upper:name}}</text:p><text:p>{{nl2br:bio}}</text:p>'
                . '<text:p>{{ul:skills}}</text:p><text:p>{{ol:skills}}</text:p>'
                . '<text:p>{{client}}</text:p>'
                . '<text:p><text:user-field-get text:name="client">Old</text:user-field-get></text:p>',
            experienceBody: '<text:p>{{company}}</text:p><text:p>{{upper:position}}</text:p>'
                . '<text:p>{{current}}</text:p>'
                . '<text:section text:name="#if:current"><text:p>Current {{company}}</text:p></text:section>'
                . '<text:section text:name="#foreach:projects"><text:p>{{title}}</text:p></text:section>',
            stylesBody: '<style:header><text:section text:name="#if:show_header">'
                . '<text:p>Header {{header_value}}</text:p>'
                . '<text:user-field-decls><text:user-field-decl text:name="client"'
                . ' office:value-type="string" office:string-value="Old"/></text:user-field-decls>'
                . '<text:p><text:user-field-get text:name="client">Old</text:user-field-get></text:p>'
                . '</text:section></style:header>'
                . '<style:footer><text:section text:name="#foreach:footers">'
                . '<text:p>{{label}}</text:p></text:section></style:footer>',
            contentDeclarations: '<text:user-field-decls><text:user-field-decl text:name="client"'
                . ' office:value-type="string" office:string-value="Old"/></text:user-field-decls>'
        );
        $template = new AutomationTestTemplate($source);
        $contract = $template->inspectTemplate();
        $data = [
            'person' => ['name' => 'Walter'],
            'jobs' => [
                [
                    'employer' => 'Firma A',
                    'position' => 'developer',
                    'current' => true,
                    'projects' => [['title' => 'A1'], ['title' => 'A2']],
                ],
                [
                    'employer' => 'Firma B',
                    'position' => 'consultant',
                    'current' => false,
                    'projects' => [['title' => 'B1']],
                ],
            ],
            'bio' => "first\nsecond",
            'skills' => "PHP\nODT",
            'show_header' => true,
            'header_value' => 'Header value',
            'footer_items' => [['label' => 'Footer A'], ['label' => 'Footer B']],
            'client' => 'Phase E client',
        ];
        $beforeData = serialize($data);
        $preflight = $this->preflight($template, $contract, $data);
        self::assertTrue($preflight->ready(), json_encode(array_map(
            static fn ($diagnostic): array => [$diagnostic->code(), $diagnostic->message(), $diagnostic->targetFamily(), $diagnostic->targetIdentity(), $diagnostic->sourcePath(), $diagnostic->context()],
            $preflight->diagnostics()
        )));

        $template->automateDependencies($contract, $preflight);
        $output = $this->saveOutput($template);
        $content = $this->part($output, 'content.xml');
        $styles = $this->part($output, 'styles.xml');

        self::assertStringContainsString('FILTER[upper]:Walter', $content);
        self::assertStringContainsString('Phase E client', $content);
        self::assertStringContainsString('Firma A', $content);
        self::assertStringContainsString('FILTER[upper]:developer', $content);
        self::assertStringContainsString('Current Firma A', $content);
        self::assertStringContainsString('1', $content);
        self::assertStringNotContainsString('Current Firma B', $content);
        self::assertStringContainsString('A1', $content);
        self::assertStringContainsString('A2', $content);
        self::assertStringContainsString('B1', $content);
        self::assertLessThan(strpos($content, 'A2'), strpos($content, 'A1'));
        self::assertLessThan(strpos($content, 'B1'), strpos($content, 'A2'));
        self::assertStringContainsString('text:line-break', $content);
        self::assertSame(2, substr_count($content, '<text:list '));
        self::assertStringContainsString('Header value', $styles);
        self::assertStringContainsString('office:string-value="Phase E client"', $styles);
        self::assertStringContainsString('Footer A', $styles);
        self::assertStringContainsString('Footer B', $styles);
        self::assertLessThan(strpos($styles, 'Footer B'), strpos($styles, 'Footer A'));
        self::assertStringNotContainsString('{{', $content . $styles);

        $contentDom = new DOMDocument();
        self::assertTrue($contentDom->loadXML($content));
        $xpath = new DOMXPath($contentDom);
        $xpath->registerNamespace('office', 'urn:oasis:names:tc:opendocument:xmlns:office:1.0');
        $xpath->registerNamespace('text', 'urn:oasis:names:tc:opendocument:xmlns:text:1.0');
        self::assertSame('Phase E client', $xpath->evaluate(
            'string(/office:document-content/office:body/office:text/text:user-field-decls/text:user-field-decl/@office:string-value)'
        ));
        self::assertSame(0, $xpath->query('//text:section[@text:name="#foreach:experience"]')->length);
        self::assertSame(2, $xpath->query('//text:section[starts-with(@text:name, "#foreach:experience_")]')->length);
        self::assertSame(1, $xpath->query('//text:section[@text:name="#foreach:experience_1"]/text:section[@text:name="#foreach:projects_1_1"]')->length);
        self::assertSame(1, $xpath->query('//text:section[@text:name="#foreach:experience_1"]/text:section[@text:name="#foreach:projects_1_2"]')->length);
        self::assertSame(1, $xpath->query('//text:section[@text:name="#foreach:experience_2"]/text:section[@text:name="#foreach:projects_2_1"]')->length);
        self::assertStringNotContainsString('employer', $content);
        self::assertSame($beforeData, serialize($data));
        self::assertNotEmpty($template->conditionExpressions);
        self::assertContains('show_header:root', $template->conditionExpressions);
        self::assertContains('current:1', $template->conditionExpressions);
        self::assertContains('current:', $template->conditionExpressions);
        self::assertNotEmpty($template->filters);
    }

    public function testEmptyCollectionAndFalseRootConditionRemoveOnlyOwnedSubtrees(): void
    {
        $source = $this->fixture(
            additionalBody: '<text:section text:name="#if:show"><text:p>shown</text:p></text:section>'
                . '<text:p>outside</text:p>',
            experienceBody: '<text:p>experience item</text:p>',
            stylesBody: '<style:header><text:section text:name="#if:show_header">header</text:section>'
                . '</style:header><style:footer><text:section text:name="#foreach:footers">'
                . '<text:p>footer item</text:p></text:section></style:footer>'
        );
        $template = new OdtTemplate($source);
        $contract = $template->inspectTemplate();
        $data = ['person' => ['name' => 'Ada'], 'jobs' => [], 'show' => false, 'show_header' => false, 'footer_items' => []];
        $preflight = $this->preflight($template, $contract, $data);
        self::assertTrue($preflight->ready(), json_encode(array_map(
            static fn ($diagnostic): array => [$diagnostic->code(), $diagnostic->message(), $diagnostic->targetFamily(), $diagnostic->targetIdentity(), $diagnostic->sourcePath(), $diagnostic->context()],
            $preflight->diagnostics()
        )));
        $template->automateDependencies($contract, $preflight);
        $output = $this->saveOutput($template);

        self::assertStringContainsString('outside', $this->part($output, 'content.xml'));
        self::assertStringNotContainsString('shown', $this->part($output, 'content.xml'));
        self::assertStringNotContainsString('#foreach:', $this->part($output, 'content.xml') . $this->part($output, 'styles.xml'));
        $stylesDom = new DOMDocument();
        self::assertTrue($stylesDom->loadXML($this->part($output, 'styles.xml')));
        $stylesXPath = new DOMXPath($stylesDom);
        $stylesXPath->registerNamespace('text', 'urn:oasis:names:tc:opendocument:xmlns:text:1.0');
        self::assertSame(0, $stylesXPath->query('//text:section')->length);
        self::assertSame(0, $stylesXPath->query('//text:section[contains(@text:name, "#if:") or contains(@text:name, "#foreach:")]')->length);
    }

    public function testTrueRootConditionExecutesItsNestedCollectionInTheProjectedScope(): void
    {
        $source = $this->fixture(
            additionalBody: '<text:section text:name="#if:enabled">'
                . '<text:section text:name="#foreach:items"><text:p>{{label}}</text:p></text:section>'
                . '</text:section>',
            experienceBody: '<text:p>unused experience prototype</text:p>'
        );
        $template = new OdtTemplate($source);
        $contract = $template->inspectTemplate();
        $data = [
            'person' => ['name' => 'Nested condition test'],
            'jobs' => [],
            'source_items' => [['label' => 'Item A'], ['label' => 'Item B']],
            'enabled' => true,
        ];
        $preflight = $this->preflight($template, $contract, $data);
        self::assertTrue($preflight->ready(), json_encode(array_map(
            static fn ($diagnostic): array => [$diagnostic->code(), $diagnostic->message(), $diagnostic->targetFamily(), $diagnostic->targetIdentity(), $diagnostic->sourcePath(), $diagnostic->context()],
            $preflight->diagnostics()
        )));

        $template->automateDependencies($contract, $preflight);
        $content = $this->part($this->saveOutput($template), 'content.xml');

        self::assertStringContainsString('Item A', $content);
        self::assertStringContainsString('Item B', $content);
        self::assertStringContainsString('text:name="#if:enabled"', $content);
        self::assertStringNotContainsString('#foreach:items"', $content);
        self::assertLessThan(strpos($content, 'Item B'), strpos($content, 'Item A'));
    }

    public function testAutomationRejectsNonReadyPreflightBeforeMutationAndAcceptsNoRawData(): void
    {
        $template = new OdtTemplate($this->fixture());
        $contract = $template->inspectTemplate();
        $failed = $this->preflight($template, $contract, []);
        self::assertFalse($failed->ready());
        $before = $this->part($this->saveOutput($template), 'content.xml');
        try {
            $template->automateDependencies($contract, $failed);
            self::fail('Non-READY preflight must not execute.');
        } catch (\InvalidArgumentException) {
            self::assertSame($before, $this->part($this->saveOutput($template), 'content.xml'));
        }
        $method = new \ReflectionMethod(OdtTemplate::class, 'automateDependencies');
        self::assertSame(2, $method->getNumberOfParameters());
        self::assertNotSame('array', (string) $method->getParameters()[1]->getType());
    }

    private function preflight(OdtTemplate $template, TemplateContract $contract, array $data): \OdtTemplateEngine\Mapping\ConcretePreflightResult
    {
        $rules = [
            ['person.name', 'name'],
            ['jobs[]', 'experience[]'],
            ['jobs[].employer', 'experience[].company'],
            ['jobs[].position', 'experience[].position'],
            ['jobs[].current', 'experience[].current'],
            ['jobs[].projects[]', 'experience[].projects[]'],
            ['source_items[]', 'items[]'],
            ['source_items[].label', 'items[].label'],
            ['footer_items[]', 'footers[]'],
            ['footer_items[].label', 'footers[].label'],
        ];
        $dependencyPaths = array_map(
            static fn ($dependency): string => $dependency->path(),
            $contract->dependencies()
        );
        $rules = array_values(array_filter(
            $rules,
            static fn (array $rule): bool => in_array($rule[1], $dependencyPaths, true)
        ));
        $definition = new MappingDefinition(array_map(
            static fn (array $rule): DependencyMapping => new DependencyMapping(
                ApplicationPath::parse($rule[0]),
                $rule[1]
            ),
            $rules
        ));
        try {
            return (new ConcreteMappingPreflight())->preflight(
                $definition,
                $contract,
                $data,
                $template->inspect()
            );
        } catch (\OdtTemplateEngine\Mapping\MappingResolutionException $exception) {
            self::fail(json_encode(array_map(
                static fn ($diagnostic): array => [
                    'code' => $diagnostic->code(),
                    'message' => $diagnostic->message(),
                    'context' => $diagnostic->context(),
                ],
                $exception->validationResult()->diagnostics()
            ), JSON_THROW_ON_ERROR));
        }
    }

    private function fixture(
        string $additionalBody = '',
        ?string $experienceBody = null,
        string $stylesBody = '',
        string $contentDeclarations = ''
    ): string {
        $path = MappingTemplateFixture::create($additionalBody, $experienceBody, $stylesBody, $contentDeclarations);
        $this->paths[] = $path;
        return $path;
    }

    private function saveOutput(OdtTemplate $template): string
    {
        $path = tempnam(sys_get_temp_dir(), 'phase-e3-output-');
        if (!is_string($path)) {
            self::fail('Unable to allocate a temporary output.');
        }
        unlink($path);
        $path .= '.odt';
        $this->paths[] = $path;
        $template->save($path);
        return $path;
    }

    private function part(string $package, string $name): string
    {
        $zip = new ZipArchive();
        self::assertTrue($zip->open($package) === true);
        $contents = $zip->getFromName($name);
        $zip->close();
        self::assertIsString($contents);
        return $contents;
    }
}

final class AutomationTestTemplate extends OdtTemplate
{
    /** @var list<string> */
    public array $filters = [];

    /** @var list<string> */
    public array $conditionExpressions = [];

    protected function applyFilter(string $filter, string $value, ?string $option = null): string
    {
        $this->filters[] = $filter;
        if ($filter === 'upper') {
            return 'FILTER[upper]:' . $value;
        }
        return parent::applyFilter($filter, $value, $option);
    }

    protected function evaluateCondition(string $expr, array $values): bool
    {
        $this->conditionExpressions[] = $expr . ':' . (string) ($values['current'] ?? 'root');
        return parent::evaluateCondition($expr, $values);
    }
}

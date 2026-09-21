<?php

declare(strict_types=1);

namespace OdtTemplateEngine\Tests\Integration;

use OdtTemplateEngine\Document\DeclarativeForeachExecutionException;
use OdtTemplateEngine\OdtTemplate;
use OdtTemplateEngine\Template\TemplateContract;
use OdtTemplateEngine\Tests\Support\MappingTemplateFixture;
use PHPUnit\Framework\TestCase;
use ZipArchive;

final class DeclarativeExecutionFacadeF6Test extends TestCase
{
    /** @var list<string> */
    private array $temporaryFiles = [];

    protected function tearDown(): void
    {
        foreach ($this->temporaryFiles as $path) {
            if (is_file($path)) {
                unlink($path);
            }
        }
    }

    public function testPublicFacadeExecutesNestedForeachConditionsAndScopedFilters(): void
    {
        $template = $this->template(experienceBody: $this->projectRecord());
        $contract = $template->inspectTemplate();

        $template->executeDeclarative($contract, [
            'experience' => [
                [
                    'company' => 'Aurora',
                    'role' => 'delivery lead',
                    'featured' => true,
                    'archived' => false,
                    'projects' => [['milestone' => 'Discovery'], ['milestone' => 'Review']],
                ],
                [
                    'company' => 'Beacon',
                    'role' => 'analyst',
                    'featured' => false,
                    'archived' => true,
                    'projects' => [['milestone' => 'Kickoff']],
                ],
            ],
        ]);

        $content = $this->savePart($template, 'content.xml');
        self::assertStringContainsString('Aurora / DELIVERY LEAD', $content);
        self::assertStringContainsString('Beacon / ANALYST', $content);
        self::assertStringContainsString('FEATURED', $content);
        self::assertStringContainsString('ACTIVE', $content);
        self::assertSame(1, substr_count($content, 'FEATURED'));
        self::assertSame(1, substr_count($content, 'ACTIVE'));
        self::assertStringContainsString('Milestone Discovery', $content);
        self::assertStringContainsString('Milestone Review', $content);
        self::assertStringContainsString('Milestone Kickoff', $content);
        self::assertLessThan(strpos($content, 'Beacon / ANALYST'), strpos($content, 'Aurora / DELIVERY LEAD'));
        self::assertLessThan(strpos($content, 'Review'), strpos($content, 'Discovery'));
        self::assertStringNotContainsString('text:name="#foreach:experience"', $content);
        self::assertStringNotContainsString('text:name="#foreach:projects"', $content);
        self::assertStringContainsString('text:name="#foreach:experience_1"', $content);
        self::assertStringContainsString('text:name="#foreach:projects_1_1"', $content);

        $reopened = new OdtTemplate($this->saveDocument($template));
        $reopenedSectionNames = array_map(
            static fn ($section): string => $section->name(),
            $reopened->inspect()->sections()
        );
        self::assertContains('#foreach:experience_1', $reopenedSectionNames);
        self::assertContains('#foreach:projects_1_1', $reopenedSectionNames);
    }

    public function testEmptyCollectionRemovesPrototypeAndCreatesNoInstances(): void
    {
        $template = $this->template(experienceBody: $this->projectRecord());
        $template->executeDeclarative($template->inspectTemplate(), ['experience' => []]);

        $content = $this->savePart($template, 'content.xml');
        self::assertStringNotContainsString('text:name="#foreach:experience"', $content);
        self::assertStringNotContainsString('text:name="#foreach:experience_', $content);
        self::assertStringContainsString('{{name}}', $content);
    }

    public function testMissingCollectionFailsWithExistingErrorAndLeavesWorkingDocumentUnchanged(): void
    {
        $template = $this->template(experienceBody: $this->projectRecord());
        $contract = $template->inspectTemplate();
        $contentBefore = $this->savePart($template, 'content.xml');
        $stylesBefore = $this->savePart($template, 'styles.xml');

        try {
            $template->executeDeclarative($contract, []);
            self::fail('A missing collection must retain Phase-D failure behavior.');
        } catch (DeclarativeForeachExecutionException) {
            self::assertSame($contentBefore, $this->savePart($template, 'content.xml'));
            self::assertSame($stylesBefore, $this->savePart($template, 'styles.xml'));
        }
    }

    public function testLaterFailureRollsBackEarlierFacadeMutationAndAllowsRetry(): void
    {
        $template = $this->template(
            additionalBody: '<text:section text:name="#foreach:broken"><text:p>Broken item</text:p></text:section>',
            experienceBody: '<text:p>{{company}}</text:p>'
        );
        $contract = $template->inspectTemplate();
        $contentBefore = $this->savePart($template, 'content.xml');
        $stylesBefore = $this->savePart($template, 'styles.xml');

        try {
            $template->executeDeclarative($contract, [
                'experience' => [['company' => 'Must roll back']],
                'broken' => 'not-a-collection',
            ]);
            self::fail('Expected a later declarative control to fail.');
        } catch (DeclarativeForeachExecutionException) {
            self::assertSame($contentBefore, $this->savePart($template, 'content.xml'));
            self::assertSame($stylesBefore, $this->savePart($template, 'styles.xml'));
        }

        $template->executeDeclarative($contract, [
            'experience' => [['company' => 'Retry succeeded']],
            'broken' => [],
        ]);
        self::assertStringContainsString('Retry succeeded', $this->savePart($template, 'content.xml'));
    }

    public function testPublicFacadeExecutesHeaderAndFooterControls(): void
    {
        $template = $this->template(stylesBody: '<style:header><text:section text:name="#foreach:header_items"><text:p>Header {{label}}</text:p></text:section></style:header>'
            . '<style:footer><text:section text:name="#foreach:footer_items"><text:p>Footer {{label}}</text:p></text:section></style:footer>');
        $template->executeDeclarative($template->inspectTemplate(), [
            'experience' => [],
            'header_items' => [['label' => 'North'], ['label' => 'South']],
            'footer_items' => [['label' => 'Confidential']],
        ]);

        $styles = $this->savePart($template, 'styles.xml');
        self::assertStringContainsString('Header North', $styles);
        self::assertStringContainsString('Header South', $styles);
        self::assertStringContainsString('Footer Confidential', $styles);
        self::assertStringNotContainsString('text:name="#foreach:header_items"', $styles);
        self::assertStringNotContainsString('text:name="#foreach:footer_items"', $styles);
    }

    public function testFacadeConsumesExplicitTemplateVocabularyWithoutRenderingOrSaving(): void
    {
        $template = $this->template(experienceBody: '<text:p>Company: {{company}} / {{upper:role}}</text:p>');
        $contract = $template->inspectTemplate();
        $template->assign(['experience' => [['company' => 'Assigned'],], 'name' => 'classic assigned']);
        $output = sys_get_temp_dir() . '/declarative-facade-no-save-' . uniqid('', true) . '.odt';
        $this->temporaryFiles[] = $output;
        self::assertFileDoesNotExist($output);

        $template->executeDeclarative($contract, [
            'experience' => [['company' => 'Explicit', 'role' => 'designer']],
        ]);

        self::assertFileDoesNotExist($output);
        $content = $this->savePart($template, 'content.xml');
        self::assertStringContainsString('Company: Explicit / DESIGNER', $content);
        self::assertStringContainsString('{{name}}', $content, 'Declarative execution must not implicitly render unrelated classic bindings.');
    }

    public function testExistingRenderAndPhaseEFacadeSignaturesRemainAvailable(): void
    {
        $template = $this->template(experienceBody: '<text:p>{{company}}</text:p>');
        $template->assign(['name' => 'Classic render']);
        $template->render();
        $content = $this->savePart($template, 'content.xml');
        self::assertStringContainsString('Classic render', $content);
        self::assertStringContainsString('text:name="#foreach:experience"', $content);

        self::assertSame(2, (new \ReflectionMethod(OdtTemplate::class, 'automate'))->getNumberOfParameters());
        self::assertSame(2, (new \ReflectionMethod(OdtTemplate::class, 'automateDependencies'))->getNumberOfParameters());
        self::assertSame(2, (new \ReflectionMethod(OdtTemplate::class, 'automateNativeObjectActions'))->getNumberOfParameters());
        self::assertSame(1, (new \ReflectionMethod(OdtTemplate::class, 'automateDocumentCapabilities'))->getNumberOfParameters());
        self::assertSame(TemplateContract::class, (string) (new \ReflectionMethod(OdtTemplate::class, 'executeDeclarative'))->getParameters()[0]->getType());
    }

    private function template(string $additionalBody = '', ?string $experienceBody = null, string $stylesBody = ''): OdtTemplate
    {
        $path = MappingTemplateFixture::create($additionalBody, $experienceBody, $stylesBody);
        $this->temporaryFiles[] = $path;

        return new OdtTemplate($path);
    }

    private function savePart(OdtTemplate $template, string $part): string
    {
        $path = $this->saveDocument($template);
        $archive = new ZipArchive();
        self::assertTrue($archive->open($path) === true);
        $content = $archive->getFromName($part);
        $archive->close();
        self::assertIsString($content);

        return $content;
    }

    private function saveDocument(OdtTemplate $template): string
    {
        $path = sys_get_temp_dir() . '/declarative-facade-' . uniqid('', true) . '.odt';
        $this->temporaryFiles[] = $path;
        $template->save($path);

        return $path;
    }

    private function projectRecord(): string
    {
        return '<text:p>Company: {{company}} / {{upper:role}}</text:p>'
            . '<text:section text:name="#if:featured"><text:p>FEATURED</text:p></text:section>'
            . '<text:section text:name="#ifnot:archived"><text:p>ACTIVE</text:p></text:section>'
            . '<text:section text:name="#foreach:projects"><text:p>Milestone {{milestone}}</text:p></text:section>';
    }
}

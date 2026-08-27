<?php

declare(strict_types=1);

namespace OdtTemplateEngine\Tests\Integration;

use DOMDocument;
use DOMElement;
use OdtTemplateEngine\Document\SectionInstantiationException;
use OdtTemplateEngine\OdtPackage;
use OdtTemplateEngine\OdtTemplate;
use PHPUnit\Framework\TestCase;

final class SectionInstantiationTest extends TestCase
{
    private const TEXT_NAMESPACE = 'urn:oasis:names:tc:opendocument:xmlns:text:1.0';

    /** @var list<string> */
    private array $outputs = [];

    protected function tearDown(): void
    {
        foreach ($this->outputs as $output) {
            if (is_file($output)) {
                unlink($output);
            }
        }
    }

    public function testInstancesBindUnsuffixedValuesLocallyAndReturnTypedTargets(): void
    {
        $template = new OdtTemplate($this->templatePath());
        $first = $template->section('ExperienceEntry')->instantiate($this->values('Aktuelle Position', 'Senior Projektmanager', 'Leitung eines interdisziplinären Projektteams.'));
        $second = $template->section('ExperienceEntry')->instantiate($this->values('Vorherige Position', 'Marketing-Spezialist', 'Entwicklung digitaler Marketingkampagnen.'));

        self::assertSame('ExperienceEntry_1', $first->name());
        self::assertSame('ExperienceEntry_2', $second->name());
        self::assertStringContainsString('Senior Projektmanager', $first->text());
        self::assertStringContainsString('Marketing-Spezialist', $second->text());
        self::assertStringContainsString('{{position}}', $template->section('ExperienceEntry')->text());
        self::assertStringNotContainsString('{{position_1}}', $first->text());
        self::assertStringNotContainsString('{{position_2}}', $second->text());
        self::assertSame([], $template->inspect()->diagnostics());
    }

    public function testFragmentedActivityExpressionIsBoundAndBookmarkIdentitiesRemainSeparate(): void
    {
        $template = new OdtTemplate($this->templatePath());
        $instance = $template->section('ExperienceEntry')->instantiate($this->values('Aktuelle Position', 'Senior Projektmanager', 'Teamleitung'));

        self::assertStringContainsString('Teamleitung', $instance->text());
        self::assertStringNotContainsString('{{activity_1}}', $instance->text());
        self::assertStringContainsString('Company_1', $this->nestedObjectNames($instance));
        self::assertStringContainsString('Activity_1', $this->nestedObjectNames($instance));
        self::assertStringContainsString('{{activity}}', $template->section('ExperienceEntry')->text());

        $activity = $this->section($this->contentDom($template), 'ActivityEntry_1');
        self::assertSame('T29', $activity->getElementsByTagNameNS(self::TEXT_NAMESPACE, 'span')->item(0)?->getAttribute('text:style-name'));
        self::assertNotNull($activity->getElementsByTagNameNS(self::TEXT_NAMESPACE, 'bookmark-start')->item(0));
        self::assertNotNull($activity->getElementsByTagNameNS(self::TEXT_NAMESPACE, 'bookmark-end')->item(0));
    }

    public function testMissingRequiredValueFailsAtomically(): void
    {
        $template = new OdtTemplate($this->templatePath());
        $before = $template->inspect()->toArray();

        try {
            $template->section('ExperienceEntry')->instantiate(['note' => 'Only note', 'position' => 'Missing activity']);
            self::fail('Expected missing clone-local value to fail.');
        } catch (SectionInstantiationException $exception) {
            self::assertSame('missing required value', $exception->reason());
            self::assertSame('activity', $exception->variableName());
        }

        self::assertSame($before, $template->inspect()->toArray());
    }

    public function testExtraValuesAreIgnoredAndInvalidValuesAreRejected(): void
    {
        $template = new OdtTemplate($this->templatePath());
        $values = $this->values('Aktuelle Position', 'Senior Projektmanager', 'Teamleitung');
        $values['unused'] = 'ignored';
        $instance = $template->section('ExperienceEntry')->instantiate($values);
        self::assertStringContainsString('Senior Projektmanager', $instance->text());

        $before = $template->inspect()->toArray();
        try {
            $template->section('ExperienceEntry')->instantiate([
                'note' => 'Invalid',
                'position' => ['not' => 'scalar'],
                'activity' => 'Invalid',
            ]);
            self::fail('Expected invalid binding data to fail.');
        } catch (SectionInstantiationException $exception) {
            self::assertSame('invalid binding data', $exception->reason());
        }
        self::assertSame($before, $template->inspect()->toArray());
    }

    public function testFilteredScalarExpressionUsesExistingProcessorSemantics(): void
    {
        $template = new OdtTemplate($this->templatePath());
        $source = $this->section($this->contentDom($template), 'ExperienceEntry');
        foreach ($source->getElementsByTagNameNS(self::TEXT_NAMESPACE, 'p') as $paragraph) {
            if ($paragraph->textContent === '{{note}}') {
                self::assertNotNull($paragraph->firstChild);
                $paragraph->firstChild->nodeValue = '{{upper:note}}';
                break;
            }
        }

        $instance = $template->section('ExperienceEntry')->instantiate($this->values('aktuelle position', 'Senior Projektmanager', 'Teamleitung'));

        self::assertStringContainsString('AKTUELLE POSITION', $instance->text());
    }

    public function testConditionsAndForeachRemainExplicitlyUnsupportedAndAtomic(): void
    {
        $template = new OdtTemplate($this->templatePath());
        $source = $this->section($this->contentDom($template), 'ExperienceEntry');
        $paragraph = $source->ownerDocument->createElementNS(self::TEXT_NAMESPACE, 'text:p');
        $paragraph->appendChild($source->ownerDocument->createTextNode('{{#if:active}}'));
        $source->appendChild($paragraph);
        $before = $template->inspect()->toArray();

        $this->expectException(SectionInstantiationException::class);
        try {
            $template->section('ExperienceEntry')->instantiate($this->values('Position', 'Role', 'Activity'));
        } finally {
            self::assertSame($before, $template->inspect()->toArray());
        }
    }

    public function testInstancesRemainOrderedAndSurviveSaveReopen(): void
    {
        $template = new OdtTemplate($this->templatePath());
        $template->section('ExperienceEntry')->instantiate($this->values('First', 'First role', 'First activity'));
        $template->section('ExperienceEntry')->instantiate($this->values('Second', 'Second role', 'Second activity'));
        $output = $this->outputPath();
        $template->save($output);

        $reopened = new OdtTemplate($output);
        self::assertStringContainsString('First role', $reopened->section('ExperienceEntry_1')->text());
        self::assertStringContainsString('Second role', $reopened->section('ExperienceEntry_2')->text());
        $names = array_map(static fn ($section): string => $section->name(), $reopened->inspect()->sections());
        self::assertLessThan(array_search('ExperienceEntry_2', $names, true), array_search('ExperienceEntry_1', $names, true));
    }

    public function testPrototypeTargetUsesCurrentContextAfterLoadAndInstancesAreIsolated(): void
    {
        $first = new OdtTemplate($this->templatePath());
        $prototype = $first->section('ExperienceEntry');
        $first->load();
        $instance = $prototype->instantiate($this->values('Reloaded', 'Reloaded role', 'Reloaded activity'));
        self::assertSame('ExperienceEntry_1', $instance->name());

        $second = new OdtTemplate($this->templatePath());
        self::assertSame('ExperienceEntry_1', $second->section('ExperienceEntry')->instantiate($this->values('Other', 'Other role', 'Other activity'))->name());
        self::assertSame(2, count(array_filter($first->inspect()->sections(), static fn ($section): bool => str_starts_with($section->name(), 'ExperienceEntry'))));
        self::assertSame(2, count(array_filter($second->inspect()->sections(), static fn ($section): bool => str_starts_with($section->name(), 'ExperienceEntry'))));
    }

    /** @return array<string, string> */
    private function values(string $note, string $position, string $activity): array
    {
        return compact('note', 'position', 'activity');
    }

    private function templatePath(): string
    {
        return dirname(__DIR__, 2) . '/samples/templates/sample_25_sectionClone.odt';
    }

    private function outputPath(): string
    {
        $output = sys_get_temp_dir() . '/odt-section-instance-' . uniqid('', true) . '.odt';
        $this->outputs[] = $output;
        return $output;
    }

    private function contentDom(OdtTemplate $template): DOMDocument
    {
        $reflection = new \ReflectionClass($template);
        $property = $reflection->getProperty('package');
        $property->setAccessible(true);
        /** @var OdtPackage $package */
        $package = $property->getValue($template);
        return $package->contentDom();
    }

    private function section(DOMDocument $dom, string $name): DOMElement
    {
        $xpath = new \DOMXPath($dom);
        $xpath->registerNamespace('text', self::TEXT_NAMESPACE);
        $section = $xpath->query(sprintf('//text:section[@text:name="%s"]', $name))->item(0);
        self::assertInstanceOf(DOMElement::class, $section);
        return $section;
    }

    /** @return string */
    private function nestedObjectNames(\OdtTemplateEngine\Document\SectionTarget $target): string
    {
        return implode(',', array_map(
            static fn ($object): string => $object->name(),
            $target->descriptor()->nestedNamedObjects()
        ));
    }
}

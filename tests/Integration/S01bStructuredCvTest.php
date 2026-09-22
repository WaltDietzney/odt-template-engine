<?php

declare(strict_types=1);

namespace OdtTemplateEngine\Tests\Integration;

use OdtTemplateEngine\OdtTemplate;
use PHPUnit\Framework\TestCase;
use ZipArchive;

final class S01bStructuredCvTest extends TestCase
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

    public function testPreparedTemplateContainsTheStructuredCvObjects(): void
    {
        $content = $this->readTemplatePart('content.xml');
        $styles = $this->readTemplatePart('styles.xml');
        $dom = new \DOMDocument();
        self::assertTrue($dom->loadXML($content));
        $xpath = new \DOMXPath($dom);
        $xpath->registerNamespace('text', 'urn:oasis:names:tc:opendocument:xmlns:text:1.0');
        $xpath->registerNamespace('draw', 'urn:oasis:names:tc:opendocument:xmlns:drawing:1.0');

        foreach (['TimelineWrapper', 'Experience', 'JobSection', 'ActivitySection', 'Education', 'EducationSection', 'AdditionalQualifications', 'QualificationSection'] as $name) {
            self::assertCount(1, $xpath->query('//text:section[@text:name="' . $name . '"]'));
        }
        self::assertCount(1, $xpath->query('//draw:frame[@draw:name="CVImage"]/draw:image'));
        self::assertStringContainsString('CVSidebarPage1', $content);
        self::assertStringContainsString('CVSidebarPage2', $content);
        self::assertStringContainsString('First Page', $styles);
        self::assertStringContainsString('CV Continuation', $styles);
    }

    public function testNestedCollectionsAndCompleteEmptyAreasUsePublicSectionApi(): void
    {
        $template = new OdtTemplate($this->templatePath());
        $experience = $template->section('Experience')->section('JobSection')->instantiateMany([
            ['JobDuration' => '2021', 'JobName' => 'Senior Project Manager', 'JobCompany' => 'Harbour Digital'],
        ]);
        self::assertCount(1, $experience);
        self::assertSame([], $experience[0]->section('ActivitySection')->instantiateMany([]));
        self::assertSame([], $template->section('Education')->instantiateMany([]));
        self::assertSame([], $template->section('AdditionalQualifications')->instantiateMany([]));

        $template->assign([
            'VName' => 'Andrew',
            'Name' => 'Thompson',
            'BDate' => '12.04.1984',
            'BTown' => 'Sydney, Australia',
        ]);
        $output = $this->temporaryPath('empty-sections');
        $template->render();
        $template->save($output);
        $content = $this->readOutputPart($output, 'content.xml');

        self::assertStringContainsString('Senior Project Manager', $content);
        self::assertStringNotContainsString('AUSBILDUNG', $content);
        self::assertStringNotContainsString('ZUSATZQUALIFIKATIONEN', $content);
        self::assertStringNotContainsString('ActivitySection', $content);
        self::assertStringNotContainsString('{{Education', $content);
        self::assertStringNotContainsString('{{Qualification', $content);
        self::assertStringNotContainsString('{{Activity', $content);
    }

    public function testNamedImageReplacementKeepsAuthoredFrameIdentity(): void
    {
        $template = new OdtTemplate($this->templatePath());
        $template->replaceImageByName('CVImage', $this->imagePath(), [
            'width' => '4.001cm',
            'height' => '3.799cm',
        ]);
        $output = $this->temporaryPath('image');
        $template->save($output);
        $content = $this->readOutputPart($output, 'content.xml');
        $manifest = $this->readOutputPart($output, 'META-INF/manifest.xml');

        self::assertStringContainsString('draw:name="CVImage"', $content);
        self::assertStringContainsString('svg:width="4.001cm"', $content);
        self::assertStringContainsString('svg:height="3.799cm"', $content);
        self::assertStringContainsString('Pictures/WaltDietzney.png', $content);
        self::assertStringContainsString('Pictures/WaltDietzney.png', $manifest);
    }

    private function templatePath(): string
    {
        return dirname(__DIR__, 2) . '/samples/templates/template_S01b_cv_structured.odt';
    }

    private function imagePath(): string
    {
        return dirname(__DIR__, 2) . '/assets/WaltDietzney.png';
    }

    private function readTemplatePart(string $entry): string
    {
        return $this->readZipPart($this->templatePath(), $entry);
    }

    private function readOutputPart(string $path, string $entry): string
    {
        return $this->readZipPart($path, $entry);
    }

    private function readZipPart(string $path, string $entry): string
    {
        $archive = new ZipArchive();
        self::assertTrue($archive->open($path) === true);
        $value = $archive->getFromName($entry);
        $archive->close();
        self::assertIsString($value);

        return $value;
    }

    private function temporaryPath(string $suffix): string
    {
        $path = sys_get_temp_dir() . '/s01b-' . $suffix . '-' . uniqid('', true) . '.odt';
        $this->temporaryFiles[] = $path;

        return $path;
    }
}

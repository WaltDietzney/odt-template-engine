<?php

declare(strict_types=1);

namespace OdtTemplateEngine\Tests\Integration;

use DOMDocument;
use DOMXPath;
use OdtTemplateEngine\OdtTemplate;
use PHPUnit\Framework\TestCase;
use ZipArchive;

final class TemplateAuthoring01FLearnNativeFieldsInspectionTest extends TestCase
{
    private const OFFICE_NS = 'urn:oasis:names:tc:opendocument:xmlns:office:1.0';
    private const TEXT_NS = 'urn:oasis:names:tc:opendocument:xmlns:text:1.0';

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

    public function testL09NativeTargetsResolveAndBoundedMutationsPreserveTargetIdentity(): void
    {
        $path = dirname(__DIR__, 2) . '/samples/templates/template_L09_native_objects.odt';
        $template = new OdtTemplate($path);
        $source = $template->inspectTemplate();
        $names = [];
        foreach ($source->nativeObjects() as $object) {
            $names[$object->kind() . ':' . $object->name()] = true;
        }

        foreach ([
            'bookmark:ClientName',
            'section:ProjectSummary',
            'table:ProjectMilestones',
            'frame:ProjectNote',
        ] as $identity) {
            self::assertArrayHasKey($identity, $names);
        }

        $bookmarkBefore = $template->bookmark('ClientName')->descriptor();
        $template->bookmark('ClientName')->replaceText('Aurora Studio');
        self::assertSame($bookmarkBefore->topology(), $template->bookmark('ClientName')->descriptor()->topology());
        self::assertSame('Aurora Studio', $template->bookmark('ClientName')->descriptor()->text());

        $template->section('ProjectSummary')->replaceContent(
            (new \OdtTemplateEngine\Elements\Paragraph())->addText('Updated summary')
        );
        self::assertSame('ProjectSummary', $template->section('ProjectSummary')->descriptor()->name());
        self::assertGreaterThanOrEqual(3, $template->table('ProjectMilestones')->descriptor()->rowCount());
        self::assertSame('ProjectNote', $template->frame('ProjectNote')->descriptor()->name());

        $output = $this->temporaryPath('l09');
        $template->save($output);
        $reopened = new OdtTemplate($output);
        self::assertSame('Aurora Studio', $reopened->bookmark('ClientName')->descriptor()->text());
        self::assertSame('ProjectSummary', $reopened->section('ProjectSummary')->descriptor()->name());
        self::assertSame('ProjectMilestones', $reopened->table('ProjectMilestones')->descriptor()->name());
        self::assertSame('ProjectNote', $reopened->frame('ProjectNote')->descriptor()->name());
        self::assertTrue($this->isValidOdt($output));
    }

    public function testL10PublicTemplateProjectsAndBindsDocumentGlobalStringUserField(): void
    {
        $sourcePath = dirname(__DIR__, 2) . '/samples/templates/template_L10_writer_user_fields.odt';
        $template = new OdtTemplate($sourcePath);
        $contract = $template->inspectTemplate();
        self::assertSame('READY', $contract->capabilities()->readiness('native_field_binding'));
        self::assertSame([], $contract->diagnostics());

        $fieldEvidence = array_values(array_filter(
            $contract->bindings(),
            static fn ($binding): bool => $binding->variableName() === 'customer'
        ));
        self::assertNotEmpty($fieldEvidence);
        self::assertContains('NATIVE_USER_FIELD_DECLARATION', array_map(static fn ($binding): string => $binding->kind(), $fieldEvidence));
        self::assertContains('NATIVE_USER_FIELD_REFERENCE', array_map(static fn ($binding): string => $binding->kind(), $fieldEvidence));

        $before = $contract->toArray();
        $template->setUserField('customer', 'Aurora Studio');
        self::assertSame($before, $template->inspectTemplate()->toArray());

        $output = $this->temporaryPath('l10');
        $template->save($output);
        self::assertSame(['Aurora Studio'], $this->declarationValues($output, 'content.xml'));
        self::assertSame(['Aurora Studio'], $this->declarationValues($output, 'styles.xml'));
        self::assertContains('Original customer', $this->referenceTexts($output, 'content.xml'));
        self::assertContains('Original customer', $this->referenceTexts($output, 'styles.xml'));

        $reopened = new OdtTemplate($output);
        self::assertSame('READY', $reopened->inspectTemplate()->capabilities()->readiness('native_field_binding'));
        self::assertTrue($this->isValidOdt($output));
    }

    public function testL11InspectionSampleEmitsSourceContractWithoutCreatingOutput(): void
    {
        $root = dirname(__DIR__, 2);
        $template = new OdtTemplate($root . '/samples/templates/template_L11_template_inspection.odt');
        $first = $template->inspectTemplate()->toArray();
        $second = $template->inspectTemplate()->toArray();
        self::assertSame($first, $second);
        self::assertNotEmpty($first['bindings']);
        self::assertNotEmpty($first['controls']);
        self::assertNotEmpty($first['native_objects']);
        self::assertNotEmpty($first['dependencies']);
        self::assertSame('READY', $first['capabilities']['inspection']);
        self::assertSame('READY', $first['capabilities']['native_field_binding']);
        self::assertSame([], $first['diagnostics']);

    }

    /** @return list<string> */
    private function declarationValues(string $path, string $part): array
    {
        $xpath = $this->partXPath($path, $part);
        $values = [];
        foreach ($xpath->query('//text:user-field-decl[@text:name="customer"]/@office:string-value') as $value) {
            $values[] = (string) $value->nodeValue;
        }

        return $values;
    }

    /** @return list<string> */
    private function referenceTexts(string $path, string $part): array
    {
        $xpath = $this->partXPath($path, $part);
        $values = [];
        foreach ($xpath->query('//text:user-field-get[@text:name="customer"]') as $reference) {
            $values[] = (string) $reference->textContent;
        }

        return $values;
    }

    private function partXPath(string $path, string $part): DOMXPath
    {
        $zip = new ZipArchive();
        self::assertTrue($zip->open($path) === true);
        $xml = $zip->getFromName($part);
        $zip->close();
        self::assertIsString($xml);
        $dom = new DOMDocument();
        self::assertTrue($dom->loadXML($xml));
        $xpath = new DOMXPath($dom);
        $xpath->registerNamespace('text', self::TEXT_NS);
        $xpath->registerNamespace('office', self::OFFICE_NS);

        return $xpath;
    }

    private function isValidOdt(string $path): bool
    {
        $zip = new ZipArchive();
        if ($zip->open($path) !== true) {
            return false;
        }
        $valid = $zip->locateName('content.xml') !== false
            && $zip->locateName('styles.xml') !== false
            && $zip->locateName('META-INF/manifest.xml') !== false;
        $zip->close();

        return $valid;
    }

    private function temporaryPath(string $suffix): string
    {
        $path = sys_get_temp_dir() . '/odt-f5-' . $suffix . '-' . bin2hex(random_bytes(5)) . '.odt';
        $this->temporaryFiles[] = $path;

        return $path;
    }
}

<?php

declare(strict_types=1);

namespace OdtTemplateEngine\Tests\Integration;

use DOMDocument;
use DOMElement;
use OdtTemplateEngine\OdtTemplate;
use OdtTemplateEngine\Template\DataScopeDescriptor;
use PHPUnit\Framework\TestCase;
use ZipArchive;

final class TemplateAuthoring01C3LibreOfficeUserFieldIntegrationTest extends TestCase
{
    private const OFFICE_NS = 'urn:oasis:names:tc:opendocument:xmlns:office:1.0';
    private const TEXT_NS = 'urn:oasis:names:tc:opendocument:xmlns:text:1.0';

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

    public function testLibreOfficeAuthoredBodyHeaderFieldInspectsAndBindsAcrossParts(): void
    {
        $fixture = $this->fixture('TEMPLATE-AUTHORING-01C-user-field-header.odt');
        $template = new OdtTemplate($fixture);
        $contract = $template->inspectTemplate();

        $customerDependencies = array_values(array_filter(
            $contract->dependencies(),
            static fn ($dependency): bool => $dependency->name() === 'customer'
        ));

        self::assertCount(1, $customerDependencies);
        self::assertSame(DataScopeDescriptor::ROOT, $customerDependencies[0]->scope()->kind());
        self::assertSame('customer', $customerDependencies[0]->path());

        $customerBindings = array_values(array_filter(
            $contract->bindings(),
            static fn ($binding): bool => $binding->variableName() === 'customer'
        ));

        self::assertNotEmpty($customerBindings);
        self::assertContains(
            'NATIVE_USER_FIELD_DECLARATION',
            array_map(static fn ($binding): string => $binding->kind(), $customerBindings)
        );
        self::assertContains(
            'NATIVE_USER_FIELD_REFERENCE',
            array_map(static fn ($binding): string => $binding->kind(), $customerBindings)
        );

        self::assertSame('READY', $contract->capabilities()->readiness('native_field_binding'));
        self::assertSame([], $contract->diagnostics());

        $before = $contract->toArray();
        $template->setUserField('customer', 'Maria');

        $output = $this->outputPath('body-header');
        $template->save($output);

        self::assertSame(['Maria'], $this->declarationValues($output, 'content.xml', 'customer'));
        self::assertSame(['Maria'], $this->declarationValues($output, 'styles.xml', 'customer'));

        // C2 deliberately leaves cached/materialized get text untouched.
        self::assertNotContains('Maria', $this->getTexts($output, 'content.xml', 'customer'));
        self::assertNotContains('Maria', $this->getTexts($output, 'styles.xml', 'customer'));

        // Original authored contract remains stable in the current instance.
        self::assertSame($before, $template->inspectTemplate()->toArray());

        // Saved output becomes the original source of a new instance.
        $reopened = new OdtTemplate($output);
        self::assertSame(
            'READY',
            $reopened->inspectTemplate()->capabilities()->readiness('native_field_binding')
        );
    }

    public function testLibreOfficeAuthoredForeachFixtureKeepsNativeUserFieldAtRootScope(): void
    {
        $fixture = $this->fixture('TEMPLATE-AUTHORING-01C-foreach-user-field.odt');
        $template = new OdtTemplate($fixture);
        $contract = $template->inspectTemplate();

        $paths = [];
        foreach ($contract->dependencies() as $dependency) {
            $paths[$dependency->path()] = $dependency;
        }

        self::assertArrayHasKey('experience[]', $paths);
        self::assertArrayHasKey('experience[].position', $paths);
        self::assertArrayHasKey('experience[].company', $paths);
        self::assertArrayHasKey('company_globa', $paths);
        self::assertSame(
            DataScopeDescriptor::ROOT,
            $paths['company_globa']->scope()->kind()
        );

        $fieldReference = array_values(array_filter(
            $contract->bindings(),
            static fn ($binding): bool =>
                $binding->kind() === 'NATIVE_USER_FIELD_REFERENCE'
                && $binding->variableName() === 'company_globa'
        ));

        self::assertCount(1, $fieldReference);
        self::assertSame(
            ['section:#foreach:experience'],
            $fieldReference[0]->provenance()->nativeOwnerChain()
        );

        $template
            ->section('#foreach:experience')
            ->instantiateMany([
                ['position' => 'Projektleiter', 'company' => 'Firma A'],
                ['position' => 'Entwickler', 'company' => 'Firma B'],
            ]);

        $output = $this->outputPath('foreach');
        $template->save($output);

        $content = $this->partXml($output, 'content.xml');
        self::assertStringContainsString('Firma A', $content);
        self::assertStringContainsString('Firma B', $content);
        self::assertSame(
            2,
            substr_count($content, 'text:user-field-get text:name="company_globa"')
        );
        self::assertSame(
            1,
            substr_count($content, 'text:user-field-decl')
        );
    }

    private function fixture(string $name): string
    {
        $path = dirname(__DIR__) . '/fixtures/libreoffice-reference/odt/' . $name;
        self::assertFileExists(
            $path,
            'Missing LibreOffice-authored Phase-C fixture: ' . $name
        );

        return $path;
    }

    /** @return list<string> */
    private function declarationValues(string $path, string $part, string $name): array
    {
        $dom = $this->partDom($path, $part);
        $values = [];

        foreach ($dom->getElementsByTagNameNS(self::TEXT_NS, 'user-field-decl') as $decl) {
            if (!$decl instanceof DOMElement
                || $decl->getAttributeNS(self::TEXT_NS, 'name') !== $name
            ) {
                continue;
            }

            $values[] = $decl->getAttributeNS(self::OFFICE_NS, 'string-value');
        }

        return $values;
    }

    /** @return list<string> */
    private function getTexts(string $path, string $part, string $name): array
    {
        $dom = $this->partDom($path, $part);
        $values = [];

        foreach ($dom->getElementsByTagNameNS(self::TEXT_NS, 'user-field-get') as $get) {
            if ($get instanceof DOMElement
                && $get->getAttributeNS(self::TEXT_NS, 'name') === $name
            ) {
                $values[] = $get->textContent;
            }
        }

        return $values;
    }

    private function partDom(string $path, string $part): DOMDocument
    {
        $dom = new DOMDocument('1.0', 'UTF-8');
        self::assertTrue($dom->loadXML($this->partXml($path, $part)));

        return $dom;
    }

    private function partXml(string $path, string $part): string
    {
        $zip = new ZipArchive();
        self::assertTrue($zip->open($path) === true);

        try {
            $xml = $zip->getFromName($part);
        } finally {
            $zip->close();
        }

        self::assertIsString($xml);

        return $xml;
    }

    private function outputPath(string $suffix): string
    {
        $path = tempnam(sys_get_temp_dir(), 'odt-template-authoring-c3-' . $suffix . '-');
        self::assertIsString($path);
        unlink($path);
        $path .= '.odt';
        $this->paths[] = $path;

        return $path;
    }
}

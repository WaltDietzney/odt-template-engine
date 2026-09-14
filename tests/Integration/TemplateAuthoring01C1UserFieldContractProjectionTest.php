<?php

declare(strict_types=1);

namespace OdtTemplateEngine\Tests\Integration;

use OdtTemplateEngine\OdtTemplate;
use OdtTemplateEngine\Template\DataScopeDescriptor;
use OdtTemplateEngine\Template\TemplateContractCapabilities;
use PHPUnit\Framework\TestCase;
use ZipArchive;

final class TemplateAuthoring01C1UserFieldContractProjectionTest extends TestCase
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

    public function testSupportedCrossPartUserFieldProjectsEvidenceAndOneRootDependency(): void
    {
        $contract = (new OdtTemplate($this->template(
            contentDeclarations:
                $this->stringDeclaration('customer', 'Walter')
                . $this->stringDeclaration('company_global', 'Global Company')
                . $this->stringDeclaration('condition_key', 'yes'),
            body:
                '<text:p>{{customer}}</text:p>'
                . '<text:p><text:user-field-get text:name="customer">Walter</text:user-field-get></text:p>'
                . '<text:section text:name="#foreach:experience">'
                . '<text:p>{{company}}</text:p>'
                . '<text:p><text:user-field-get text:name="company_global">Global Company</text:user-field-get></text:p>'
                . '</text:section>',
            headerDeclarations: $this->stringDeclaration('customer', 'Walter'),
            header:
                '<text:p><text:user-field-get text:name="customer">Walter</text:user-field-get></text:p>'
        )))->inspectTemplate();

        self::assertSame(
            TemplateContractCapabilities::READY,
            $contract->capabilities()->readiness('inspection')
        );
        self::assertSame(
            TemplateContractCapabilities::READY,
            $contract->capabilities()->readiness('dependency_mapping')
        );
        self::assertSame(
            TemplateContractCapabilities::READY,
            $contract->capabilities()->readiness('native_field_binding')
        );

        $dependencies = [];
        foreach ($contract->dependencies() as $dependency) {
            $dependencies[$dependency->path()] = $dependency;
        }

        self::assertArrayHasKey('customer', $dependencies);
        self::assertArrayHasKey('company_global', $dependencies);
        self::assertArrayHasKey('condition_key', $dependencies);
        self::assertArrayHasKey('experience[]', $dependencies);
        self::assertArrayHasKey('experience[].company', $dependencies);

        self::assertSame(
            DataScopeDescriptor::ROOT,
            $dependencies['customer']->scope()->kind()
        );
        self::assertSame(
            DataScopeDescriptor::ROOT,
            $dependencies['company_global']->scope()->kind()
        );
        self::assertSame(
            DataScopeDescriptor::ROOT,
            $dependencies['condition_key']->scope()->kind()
        );

        $customerBindings = array_values(array_filter(
            $contract->bindings(),
            static fn ($binding): bool => $binding->variableName() === 'customer'
        ));
        self::assertCount(4, $customerBindings);

        self::assertSame(
            [
                'SCALAR',
                'NATIVE_USER_FIELD_DECLARATION',
                'NATIVE_USER_FIELD_REFERENCE',
                'NATIVE_USER_FIELD_DECLARATION',
            ],
            array_map(static fn ($binding): string => $binding->kind(), array_slice($customerBindings, 0, 4))
        );

        $kinds = array_map(
            static fn ($binding): string => $binding->kind(),
            $customerBindings
        );
        self::assertContains('NATIVE_USER_FIELD_REFERENCE', $kinds);
        self::assertContains('NATIVE_USER_FIELD_DECLARATION', $kinds);

        foreach ($customerBindings as $binding) {
            self::assertSame(
                $dependencies['customer']->id(),
                $binding->dependencyId()
            );
        }

        $fieldReference = array_values(array_filter(
            $contract->bindings(),
            static fn ($binding): bool =>
                $binding->kind() === 'NATIVE_USER_FIELD_REFERENCE'
                && $binding->variableName() === 'company_global'
        ))[0];

        self::assertSame('content.xml', $fieldReference->provenance()->sourcePart());
        self::assertSame(
            ['section:#foreach:experience'],
            $fieldReference->provenance()->nativeOwnerChain()
        );
        self::assertSame(
            'text:user-field-get',
            $fieldReference->provenance()->carrierKind()
        );

        $headerReference = array_values(array_filter(
            $customerBindings,
            static fn ($binding): bool =>
                $binding->kind() === 'NATIVE_USER_FIELD_REFERENCE'
                && $binding->provenance()->sourcePart() === 'styles.xml'
        ))[0];

        self::assertSame('MASTER_PAGE_CONTENT', $headerReference->provenance()->regionKind());
        self::assertSame('Standard', $headerReference->provenance()->regionOwner());
        self::assertSame('text:user-field-get', $headerReference->provenance()->carrierKind());

        self::assertSame([], $contract->diagnostics());
    }

    public function testProblematicUserFieldEvidenceReturnsPartialContractAndStableDiagnostics(): void
    {
        $contract = (new OdtTemplate($this->template(
            contentDeclarations:
                $this->stringDeclaration('ok', 'yes')
                . $this->stringDeclaration('customer', 'Walter')
                . $this->stringDeclaration('customer', 'Maria')
                . $this->typedDeclaration('amount', 'float', '42'),
            body:
                '<text:p><text:user-field-get text:name="ok">yes</text:user-field-get></text:p>'
                . '<text:p><text:user-field-get text:name="customer">Walter</text:user-field-get></text:p>'
                . '<text:p><text:user-field-get text:name="amount">42</text:user-field-get></text:p>'
                . '<text:p><text:user-field-get text:name="missing">x</text:user-field-get></text:p>',
            headerDeclarations: '',
            header: ''
        )))->inspectTemplate();

        self::assertSame(
            TemplateContractCapabilities::READY,
            $contract->capabilities()->readiness('inspection')
        );
        self::assertSame(
            TemplateContractCapabilities::LIMITED,
            $contract->capabilities()->readiness('dependency_mapping')
        );
        self::assertSame(
            TemplateContractCapabilities::LIMITED,
            $contract->capabilities()->readiness('native_field_binding')
        );

        self::assertSame(
            ['ok'],
            array_values(array_map(
                static fn ($dependency): string => $dependency->path(),
                $contract->dependencies()
            ))
        );

        self::assertSame(
            [
                'ambiguous_user_field_declaration',
                'unsupported_user_field_type',
                'orphan_user_field_reference',
            ],
            array_map(
                static fn ($diagnostic): string => $diagnostic->code(),
                $contract->diagnostics()
            )
        );

        $states = [];
        foreach ($contract->bindings() as $binding) {
            if ($binding->variableName() !== null) {
                $states[$binding->variableName()][] = $binding->supportState();
            }
        }

        self::assertSame(['SUPPORTED', 'SUPPORTED'], $states['ok']);
        self::assertContains('AMBIGUOUS', $states['customer']);
        self::assertContains('UNSUPPORTED', $states['amount']);
        self::assertSame(['MALFORMED'], $states['missing']);
    }

    public function testCrossPartConflictsUseSpecificDiagnosticCodes(): void
    {
        $valueConflict = (new OdtTemplate($this->template(
            contentDeclarations: $this->stringDeclaration('customer', 'Walter'),
            body: '<text:p><text:user-field-get text:name="customer">Walter</text:user-field-get></text:p>',
            headerDeclarations: $this->stringDeclaration('customer', 'Maria'),
            header: '<text:p><text:user-field-get text:name="customer">Maria</text:user-field-get></text:p>'
        )))->inspectTemplate();

        self::assertSame(
            ['conflicting_user_field_value'],
            array_map(static fn ($diagnostic): string => $diagnostic->code(), $valueConflict->diagnostics())
        );
        self::assertSame(
            TemplateContractCapabilities::BLOCKED,
            $valueConflict->capabilities()->readiness('native_field_binding')
        );

        $typeConflict = (new OdtTemplate($this->template(
            contentDeclarations: $this->stringDeclaration('customer', 'Walter'),
            body: '<text:p><text:user-field-get text:name="customer">Walter</text:user-field-get></text:p>',
            headerDeclarations: $this->typedDeclaration('customer', 'float', '42'),
            header: '<text:p><text:user-field-get text:name="customer">42</text:user-field-get></text:p>'
        )))->inspectTemplate();

        self::assertSame(
            ['conflicting_user_field_type'],
            array_map(static fn ($diagnostic): string => $diagnostic->code(), $typeConflict->diagnostics())
        );
        self::assertSame(
            TemplateContractCapabilities::BLOCKED,
            $typeConflict->capabilities()->readiness('native_field_binding')
        );
    }

    public function testSerializationRemainsVersionOneAndDeterministic(): void
    {
        $template = new OdtTemplate($this->template(
            contentDeclarations: $this->stringDeclaration('customer', 'Walter'),
            body: '<text:p><text:user-field-get text:name="customer">Walter</text:user-field-get></text:p>',
            headerDeclarations: $this->stringDeclaration('customer', 'Walter'),
            header: '<text:p><text:user-field-get text:name="customer">Walter</text:user-field-get></text:p>'
        ));

        $first = $template->inspectTemplate()->toArray();
        $second = $template->inspectTemplate()->toArray();

        self::assertSame($first, $second);
        self::assertSame(1, $first['contract_version']);
        self::assertSame(
            ['inspection', 'dependency_mapping', 'native_field_binding'],
            array_keys($first['capabilities'])
        );

        $json = json_encode($first, JSON_THROW_ON_ERROR);
        self::assertStringContainsString('NATIVE_USER_FIELD_DECLARATION', $json);
        self::assertStringContainsString('NATIVE_USER_FIELD_REFERENCE', $json);
        self::assertStringNotContainsString('DOMElement', $json);
        self::assertStringNotContainsString('spl_object_id', $json);
    }

    private function template(
        string $contentDeclarations,
        string $body,
        string $headerDeclarations,
        string $header
    ): string {
        $path = tempnam(sys_get_temp_dir(), 'odt-template-authoring-c1-');
        self::assertIsString($path);
        unlink($path);
        $path .= '.odt';
        $this->paths[] = $path;

        $contentDeclBlock = $contentDeclarations === ''
            ? ''
            : '<text:user-field-decls>' . $contentDeclarations . '</text:user-field-decls>';
        $headerDeclBlock = $headerDeclarations === ''
            ? ''
            : '<text:user-field-decls>' . $headerDeclarations . '</text:user-field-decls>';

        $zip = new ZipArchive();
        self::assertTrue($zip->open($path, ZipArchive::CREATE | ZipArchive::OVERWRITE) === true);
        $zip->addFromString('mimetype', 'application/vnd.oasis.opendocument.text');
        $zip->addFromString(
            'content.xml',
            '<?xml version="1.0" encoding="UTF-8"?>'
            . '<office:document-content' . $this->namespaces() . '>'
            . '<office:automatic-styles/><office:body><office:text>'
            . $contentDeclBlock
            . $body
            . '</office:text></office:body></office:document-content>'
        );
        $zip->addFromString(
            'styles.xml',
            '<?xml version="1.0" encoding="UTF-8"?>'
            . '<office:document-styles' . $this->namespaces() . '>'
            . '<office:styles/><office:automatic-styles/><office:master-styles>'
            . '<style:master-page style:name="Standard"><style:header>'
            . $headerDeclBlock
            . $header
            . '</style:header></style:master-page>'
            . '</office:master-styles></office:document-styles>'
        );
        $zip->addFromString(
            'meta.xml',
            '<?xml version="1.0" encoding="UTF-8"?>'
            . '<office:document-meta xmlns:office="urn:oasis:names:tc:opendocument:xmlns:office:1.0">'
            . '<office:meta/></office:document-meta>'
        );
        $zip->addFromString(
            'META-INF/manifest.xml',
            '<?xml version="1.0" encoding="UTF-8"?>'
            . '<manifest:manifest xmlns:manifest="urn:oasis:names:tc:opendocument:xmlns:manifest:1.0">'
            . '<manifest:file-entry manifest:full-path="/" manifest:media-type="application/vnd.oasis.opendocument.text"/>'
            . '<manifest:file-entry manifest:full-path="content.xml" manifest:media-type="text/xml"/>'
            . '<manifest:file-entry manifest:full-path="styles.xml" manifest:media-type="text/xml"/>'
            . '<manifest:file-entry manifest:full-path="meta.xml" manifest:media-type="text/xml"/>'
            . '</manifest:manifest>'
        );
        $zip->close();

        return $path;
    }

    private function stringDeclaration(string $name, string $value): string
    {
        return '<text:user-field-decl'
            . ' office:value-type="string"'
            . ' office:string-value="' . htmlspecialchars($value, ENT_QUOTES | ENT_XML1) . '"'
            . ' text:name="' . htmlspecialchars($name, ENT_QUOTES | ENT_XML1) . '"/>';
    }

    private function typedDeclaration(string $name, string $type, string $value): string
    {
        return '<text:user-field-decl'
            . ' office:value-type="' . htmlspecialchars($type, ENT_QUOTES | ENT_XML1) . '"'
            . ' office:value="' . htmlspecialchars($value, ENT_QUOTES | ENT_XML1) . '"'
            . ' text:name="' . htmlspecialchars($name, ENT_QUOTES | ENT_XML1) . '"/>';
    }

    private function namespaces(): string
    {
        return ' xmlns:office="urn:oasis:names:tc:opendocument:xmlns:office:1.0"'
            . ' xmlns:text="urn:oasis:names:tc:opendocument:xmlns:text:1.0"'
            . ' xmlns:style="urn:oasis:names:tc:opendocument:xmlns:style:1.0"'
            . ' xmlns:table="urn:oasis:names:tc:opendocument:xmlns:table:1.0"'
            . ' xmlns:draw="urn:oasis:names:tc:opendocument:xmlns:drawing:1.0"';
    }
}

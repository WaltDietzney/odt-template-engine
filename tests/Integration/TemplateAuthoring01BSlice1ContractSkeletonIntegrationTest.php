<?php

declare(strict_types=1);

namespace OdtTemplateEngine\Tests\Integration;

use OdtTemplateEngine\OdtTemplate;
use OdtTemplateEngine\Template\TemplateContract;
use OdtTemplateEngine\Template\TemplateContractCapabilities;
use PHPUnit\Framework\TestCase;
use ZipArchive;

final class TemplateAuthoring01BSlice1ContractSkeletonIntegrationTest extends TestCase
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

    public function testInspectTemplateReturnsVersionedSourceContractAcrossBodyAndHeader(): void
    {
        $template = new OdtTemplate($this->createTemplate());

        $contract = $template->inspectTemplate();

        self::assertInstanceOf(TemplateContract::class, $contract);
        self::assertSame(1, $contract::CONTRACT_VERSION);

        self::assertSame(
            [
                'contract_version',
                'coverage',
                'bindings',
                'controls',
                'native_objects',
                'dependencies',
                'capabilities',
                'diagnostics',
            ],
            array_keys($contract->toArray())
        );

        self::assertSame(
            [
                [
                    'source_part' => 'content.xml',
                    'region_kind' => 'BODY',
                    'region_owner' => null,
                    'carrier_kind' => 'office:text',
                ],
                [
                    'source_part' => 'styles.xml',
                    'region_kind' => 'MASTER_PAGE_CONTENT',
                    'region_owner' => 'Standard',
                    'carrier_kind' => 'style:header',
                ],
            ],
            $contract->coverage()->inspectedRegions()
        );

        self::assertSame(
            TemplateContractCapabilities::READY,
            $contract->capabilities()->readiness('inspection')
        );
        self::assertSame(
            TemplateContractCapabilities::BLOCKED,
            $contract->capabilities()->readiness('dependency_mapping')
        );
    }

    public function testSlice1ProjectsVisibleBindingEvidenceFromSupportedRegionsOnly(): void
    {
        $contract = (new OdtTemplate($this->createTemplate()))->inspectTemplate();

        $bindings = $contract->bindings();
        self::assertCount(2, $bindings);

        self::assertSame(
            ['body_name', 'header_name'],
            array_map(
                static fn ($binding): ?string => $binding->variableName(),
                $bindings
            )
        );

        self::assertSame('content.xml', $bindings[0]->provenance()->sourcePart());
        self::assertSame('BODY', $bindings[0]->provenance()->regionKind());

        self::assertSame('styles.xml', $bindings[1]->provenance()->sourcePart());
        self::assertSame('MASTER_PAGE_CONTENT', $bindings[1]->provenance()->regionKind());
        self::assertSame('Standard', $bindings[1]->provenance()->regionOwner());

        self::assertSame(
            [],
            array_values(array_filter(
                $bindings,
                static fn ($binding): bool => $binding->variableName() === 'ignore_me'
            ))
        );

        self::assertSame([], $contract->controls());
        self::assertSame([], $contract->dependencies());
        self::assertSame([], $contract->diagnostics());
    }

    public function testSlice1ProjectsNativeObjectsFromBodyAndHeaderWithoutCollapsingNames(): void
    {
        $contract = (new OdtTemplate($this->createTemplate()))->inspectTemplate();

        $objects = $contract->nativeObjects();

        self::assertSame(
            [
                'section:Shared',
                'bookmark:BodyBookmark',
                'table:SharedTable',
                'frame:BodyFrame',
                'section:Shared',
                'bookmark:HeaderBookmark',
                'table:SharedTable',
                'frame:HeaderFrame',
            ],
            array_map(
                static fn ($item): string => $item->kind() . ':' . ($item->name() ?? ''),
                $objects
            )
        );

        self::assertSame('content.xml', $objects[0]->provenance()->sourcePart());
        self::assertSame('styles.xml', $objects[4]->provenance()->sourcePart());

        self::assertNotSame(
            $objects[0]->provenance()->evidenceId(),
            $objects[4]->provenance()->evidenceId()
        );
        self::assertNotSame(
            $objects[2]->provenance()->evidenceId(),
            $objects[6]->provenance()->evidenceId()
        );
    }

    public function testEvidenceIdsAndSerializationAreDeterministicForUnchangedSource(): void
    {
        $template = new OdtTemplate($this->createTemplate());

        $first = $template->inspectTemplate()->toArray();
        $second = $template->inspectTemplate()->toArray();

        self::assertSame($first, $second);

        $ids = [];
        foreach ($first['bindings'] as $binding) {
            $ids[] = $binding['provenance']['evidence_id'];
        }
        foreach ($first['native_objects'] as $object) {
            $ids[] = $object['provenance']['evidence_id'];
        }

        self::assertSame($ids, array_values(array_unique($ids)));
    }

    public function testInspectTemplateRemainsSourceStableAcrossWorkingDocumentRenderAndSave(): void
    {
        $template = new OdtTemplate($this->createTemplate());

        $before = $template->inspectTemplate()->toArray();

        $template->assign([
            'body_name' => 'Ada',
            'header_name' => 'Header Ada',
        ]);
        $template->render();

        $output = $this->outputPath();
        $template->save($output);

        $after = $template->inspectTemplate()->toArray();

        self::assertSame($before, $after);
    }

    private function createTemplate(): string
    {
        $path = tempnam(sys_get_temp_dir(), 'odt-template-authoring-b-slice1-');
        self::assertIsString($path);
        unlink($path);
        $path .= '.odt';
        $this->paths[] = $path;

        $zip = new ZipArchive();
        self::assertTrue(
            $zip->open($path, ZipArchive::CREATE | ZipArchive::OVERWRITE) === true
        );

        $zip->addFromString('mimetype', 'application/vnd.oasis.opendocument.text');

        $zip->addFromString(
            'content.xml',
            '<?xml version="1.0" encoding="UTF-8"?>'
            . '<office:document-content'
            . $this->namespaces()
            . '><office:automatic-styles/>'
            . '<office:body><office:text>'
            . '<text:p>Body {{body_name}}</text:p>'
            . '<text:section text:name="Shared"><text:p>Section body</text:p></text:section>'
            . '<text:p><text:bookmark text:name="BodyBookmark"/></text:p>'
            . '<table:table table:name="SharedTable">'
            . '<table:table-row><table:table-cell><text:p>Cell</text:p></table:table-cell></table:table-row>'
            . '</table:table>'
            . '<draw:frame draw:name="BodyFrame"/>'
            . '</office:text></office:body>'
            . '</office:document-content>'
        );

        $zip->addFromString(
            'styles.xml',
            '<?xml version="1.0" encoding="UTF-8"?>'
            . '<office:document-styles'
            . $this->namespaces()
            . '><office:styles>'
            . '<style:style style:name="IgnoredStyle" style:family="paragraph">'
            . '<style:text-properties fo:font-family="{{ignore_me}}"/>'
            . '</style:style>'
            . '</office:styles>'
            . '<office:automatic-styles/>'
            . '<office:master-styles>'
            . '<style:master-page style:name="Standard">'
            . '<style:header>'
            . '<text:p>Header {{header_name}}</text:p>'
            . '<text:section text:name="Shared"><text:p>Header section</text:p></text:section>'
            . '<text:p><text:bookmark text:name="HeaderBookmark"/></text:p>'
            . '<table:table table:name="SharedTable">'
            . '<table:table-row><table:table-cell><text:p>Header cell</text:p></table:table-cell></table:table-row>'
            . '</table:table>'
            . '<draw:frame draw:name="HeaderFrame"/>'
            . '</style:header>'
            . '</style:master-page>'
            . '</office:master-styles>'
            . '</office:document-styles>'
        );

        $zip->addFromString(
            'meta.xml',
            '<?xml version="1.0" encoding="UTF-8"?>'
            . '<office:document-meta'
            . ' xmlns:office="urn:oasis:names:tc:opendocument:xmlns:office:1.0">'
            . '<office:meta/>'
            . '</office:document-meta>'
        );

        $zip->addFromString(
            'META-INF/manifest.xml',
            '<?xml version="1.0" encoding="UTF-8"?>'
            . '<manifest:manifest'
            . ' xmlns:manifest="urn:oasis:names:tc:opendocument:xmlns:manifest:1.0">'
            . '<manifest:file-entry manifest:full-path="/"'
            . ' manifest:media-type="application/vnd.oasis.opendocument.text"/>'
            . '<manifest:file-entry manifest:full-path="content.xml"'
            . ' manifest:media-type="text/xml"/>'
            . '<manifest:file-entry manifest:full-path="styles.xml"'
            . ' manifest:media-type="text/xml"/>'
            . '<manifest:file-entry manifest:full-path="meta.xml"'
            . ' manifest:media-type="text/xml"/>'
            . '</manifest:manifest>'
        );

        $zip->close();

        return $path;
    }

    private function outputPath(): string
    {
        $path = tempnam(sys_get_temp_dir(), 'odt-template-authoring-b-slice1-out-');
        self::assertIsString($path);
        unlink($path);
        $path .= '.odt';
        $this->paths[] = $path;

        return $path;
    }

    private function namespaces(): string
    {
        return ' xmlns:office="urn:oasis:names:tc:opendocument:xmlns:office:1.0"'
            . ' xmlns:text="urn:oasis:names:tc:opendocument:xmlns:text:1.0"'
            . ' xmlns:table="urn:oasis:names:tc:opendocument:xmlns:table:1.0"'
            . ' xmlns:draw="urn:oasis:names:tc:opendocument:xmlns:drawing:1.0"'
            . ' xmlns:style="urn:oasis:names:tc:opendocument:xmlns:style:1.0"'
            . ' xmlns:svg="urn:oasis:names:tc:opendocument:xmlns:svg-compatible:1.0"'
            . ' xmlns:fo="urn:oasis:names:tc:opendocument:xmlns:xsl-fo-compatible:1.0"'
            . ' xmlns:loext="urn:org:documentfoundation:names:experimental:office:xmlns:loext:1.0"';
    }
}

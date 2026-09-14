<?php

declare(strict_types=1);

namespace OdtTemplateEngine\Tests\Integration;

use DOMDocument;
use DOMElement;
use OdtTemplateEngine\OdtTemplate;
use PHPUnit\Framework\TestCase;
use ZipArchive;

final class TemplateAuthoring01C0UserFieldLifecycleCharacterizationTest extends TestCase
{
    private const TEXT_NS = 'urn:oasis:names:tc:opendocument:xmlns:text:1.0';
    private const OFFICE_NS = 'urn:oasis:names:tc:opendocument:xmlns:office:1.0';

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

    public function testDeclarationMutationSurvivesRenderSaveReopenAndRepeatedApplication(): void
    {
        $templatePath = $this->createTemplate();
        $template = $this->templateWithFieldProbe($templatePath);

        $sourceContract = $template->inspectTemplate()->toArray();

        self::assertSame(
            [
                'content_declarations' => ['Walter'],
                'styles_declarations' => ['Walter'],
                'content_get_text' => ['Walter', 'Walter'],
                'styles_get_text' => ['Walter'],
            ],
            $template->userFieldState('customer')
        );

        self::assertSame(2, $template->setUserFieldStringValue('customer', 'Maria'));

        $template->assign(['classic_name' => 'Ada']);
        $template->render();

        self::assertSame(
            [
                'content_declarations' => ['Maria'],
                'styles_declarations' => ['Maria'],
                'content_get_text' => ['Walter', 'Walter'],
                'styles_get_text' => ['Walter'],
            ],
            $template->userFieldState('customer')
        );
        self::assertSame($sourceContract, $template->inspectTemplate()->toArray());

        $firstOutput = $this->outputPath('first');
        $template->save($firstOutput);

        $firstReopen = $this->templateWithFieldProbe($firstOutput);
        self::assertSame(
            [
                'content_declarations' => ['Maria'],
                'styles_declarations' => ['Maria'],
                'content_get_text' => ['Walter', 'Walter'],
                'styles_get_text' => ['Walter'],
            ],
            $firstReopen->userFieldState('customer')
        );

        self::assertSame(2, $firstReopen->setUserFieldStringValue('customer', 'Anna'));

        $secondOutput = $this->outputPath('second');
        $firstReopen->save($secondOutput);

        $secondReopen = $this->templateWithFieldProbe($secondOutput);
        self::assertSame(
            [
                'content_declarations' => ['Anna'],
                'styles_declarations' => ['Anna'],
                'content_get_text' => ['Walter', 'Walter'],
                'styles_get_text' => ['Walter'],
            ],
            $secondReopen->userFieldState('customer')
        );
    }

    public function testLoadRestoresOriginalTemplateDeclarationsAfterWorkingMutation(): void
    {
        $templatePath = $this->createTemplate();
        $template = $this->templateWithFieldProbe($templatePath);

        $before = $template->inspectTemplate()->toArray();

        self::assertSame(2, $template->setUserFieldStringValue('customer', 'Maria'));
        self::assertSame(['Maria'], $template->userFieldState('customer')['content_declarations']);
        self::assertSame(['Maria'], $template->userFieldState('customer')['styles_declarations']);

        $template->load();

        self::assertSame(
            [
                'content_declarations' => ['Walter'],
                'styles_declarations' => ['Walter'],
                'content_get_text' => ['Walter', 'Walter'],
                'styles_get_text' => ['Walter'],
            ],
            $template->userFieldState('customer')
        );
        self::assertSame($before, $template->inspectTemplate()->toArray());
    }

    public function testCrossPartLogicalFieldRequiresSynchronizedDeclarationMutation(): void
    {
        $templatePath = $this->createTemplate();
        $template = $this->templateWithFieldProbe($templatePath);

        self::assertSame(
            1,
            $template->setUserFieldStringValueInPart('content.xml', 'customer', 'Maria')
        );

        self::assertSame(
            [
                'content_declarations' => ['Maria'],
                'styles_declarations' => ['Walter'],
                'content_get_text' => ['Walter', 'Walter'],
                'styles_get_text' => ['Walter'],
            ],
            $template->userFieldState('customer')
        );

        $output = $this->outputPath('split');
        $template->save($output);

        $reopened = $this->templateWithFieldProbe($output);
        self::assertSame(['Maria'], $reopened->userFieldState('customer')['content_declarations']);
        self::assertSame(['Walter'], $reopened->userFieldState('customer')['styles_declarations']);
    }

    private function templateWithFieldProbe(string $path): OdtTemplate
    {
        return new class ($path) extends OdtTemplate {
            private const TEXT_NS = 'urn:oasis:names:tc:opendocument:xmlns:text:1.0';
            private const OFFICE_NS = 'urn:oasis:names:tc:opendocument:xmlns:office:1.0';

            public function setUserFieldStringValue(string $name, string $value): int
            {
                return $this->setInDom($this->documentContext()->contentDom(), $name, $value)
                    + $this->setInDom($this->documentContext()->stylesDom(), $name, $value);
            }

            public function setUserFieldStringValueInPart(
                string $part,
                string $name,
                string $value
            ): int {
                $dom = match ($part) {
                    'content.xml' => $this->documentContext()->contentDom(),
                    'styles.xml' => $this->documentContext()->stylesDom(),
                    default => throw new \InvalidArgumentException('Unsupported part: ' . $part),
                };

                return $this->setInDom($dom, $name, $value);
            }

            /** @return array<string, list<string>> */
            public function userFieldState(string $name): array
            {
                return [
                    'content_declarations' => $this->declarationValues(
                        $this->documentContext()->contentDom(),
                        $name
                    ),
                    'styles_declarations' => $this->declarationValues(
                        $this->documentContext()->stylesDom(),
                        $name
                    ),
                    'content_get_text' => $this->getTexts(
                        $this->documentContext()->contentDom(),
                        $name
                    ),
                    'styles_get_text' => $this->getTexts(
                        $this->documentContext()->stylesDom(),
                        $name
                    ),
                ];
            }

            private function setInDom(DOMDocument $dom, string $name, string $value): int
            {
                $count = 0;
                foreach ($dom->getElementsByTagNameNS(self::TEXT_NS, 'user-field-decl') as $decl) {
                    if (!$decl instanceof DOMElement
                        || $decl->getAttributeNS(self::TEXT_NS, 'name') !== $name
                    ) {
                        continue;
                    }

                    $decl->setAttributeNS(
                        self::OFFICE_NS,
                        'office:string-value',
                        $value
                    );
                    ++$count;
                }

                return $count;
            }

            /** @return list<string> */
            private function declarationValues(DOMDocument $dom, string $name): array
            {
                $values = [];
                foreach ($dom->getElementsByTagNameNS(self::TEXT_NS, 'user-field-decl') as $decl) {
                    if ($decl instanceof DOMElement
                        && $decl->getAttributeNS(self::TEXT_NS, 'name') === $name
                    ) {
                        $values[] = $decl->getAttributeNS(self::OFFICE_NS, 'string-value');
                    }
                }

                return $values;
            }

            /** @return list<string> */
            private function getTexts(DOMDocument $dom, string $name): array
            {
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
        };
    }

    private function createTemplate(): string
    {
        $path = tempnam(sys_get_temp_dir(), 'odt-template-authoring-c-r4-');
        self::assertIsString($path);
        unlink($path);
        $path .= '.odt';
        $this->paths[] = $path;

        $zip = new ZipArchive();
        self::assertTrue($zip->open($path, ZipArchive::CREATE | ZipArchive::OVERWRITE) === true);
        $zip->addFromString('mimetype', 'application/vnd.oasis.opendocument.text');

        $zip->addFromString(
            'content.xml',
            '<?xml version="1.0" encoding="UTF-8"?>'
            . '<office:document-content' . $this->namespaces() . '>'
            . '<office:automatic-styles/>'
            . '<office:body><office:text>'
            . '<text:user-field-decls>'
            . '<text:user-field-decl office:value-type="string"'
            . ' office:string-value="Walter" text:name="customer"/>'
            . '</text:user-field-decls>'
            . '<text:p>Classic {{classic_name}}</text:p>'
            . '<text:p>Customer: <text:user-field-get text:name="customer">Walter</text:user-field-get></text:p>'
            . '<text:p>Again: <text:user-field-get text:name="customer">Walter</text:user-field-get></text:p>'
            . '</office:text></office:body>'
            . '</office:document-content>'
        );

        $zip->addFromString(
            'styles.xml',
            '<?xml version="1.0" encoding="UTF-8"?>'
            . '<office:document-styles' . $this->namespaces() . '>'
            . '<office:styles/><office:automatic-styles/><office:master-styles>'
            . '<style:master-page style:name="Standard"><style:header>'
            . '<text:user-field-decls>'
            . '<text:user-field-decl office:value-type="string"'
            . ' office:string-value="Walter" text:name="customer"/>'
            . '</text:user-field-decls>'
            . '<text:p>Header: <text:user-field-get text:name="customer">Walter</text:user-field-get></text:p>'
            . '</style:header></style:master-page>'
            . '</office:master-styles></office:document-styles>'
        );

        $zip->addFromString(
            'meta.xml',
            '<?xml version="1.0" encoding="UTF-8"?>'
            . '<office:document-meta xmlns:office="' . self::OFFICE_NS . '">'
            . '<office:meta/></office:document-meta>'
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

    private function outputPath(string $suffix): string
    {
        $path = tempnam(sys_get_temp_dir(), 'odt-template-authoring-c-r4-' . $suffix . '-');
        self::assertIsString($path);
        unlink($path);
        $path .= '.odt';
        $this->paths[] = $path;

        return $path;
    }

    private function namespaces(): string
    {
        return ' xmlns:office="' . self::OFFICE_NS . '"'
            . ' xmlns:text="' . self::TEXT_NS . '"'
            . ' xmlns:style="urn:oasis:names:tc:opendocument:xmlns:style:1.0"'
            . ' xmlns:fo="urn:oasis:names:tc:opendocument:xmlns:xsl-fo-compatible:1.0"'
            . ' xmlns:loext="urn:org:documentfoundation:names:experimental:office:xmlns:loext:1.0"';
    }
}

<?php

declare(strict_types=1);

namespace OdtTemplateEngine\Tests\Integration;

use DOMDocument;
use DOMElement;
use OdtTemplateEngine\OdtTemplate;
use OdtTemplateEngine\Template\UserFieldBindingException;
use PHPUnit\Framework\TestCase;
use ZipArchive;

final class TemplateAuthoring01C2UserFieldBindingTest extends TestCase
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

    public function testBindingUpdatesAllAuthoritativeDeclarationsButNotGetDisplayText(): void
    {
        $template = new OdtTemplate($this->supportedTemplate());

        $sourceContract = $template->inspectTemplate()->toArray();

        $template->setUserField('customer', 'Maria');

        $output = $this->outputPath('bound');
        $template->save($output);

        self::assertSame(
            ['Maria'],
            $this->declarationValues($output, 'content.xml', 'customer')
        );
        self::assertSame(
            ['Maria'],
            $this->declarationValues($output, 'styles.xml', 'customer')
        );

        self::assertSame(
            ['Walter', 'Walter'],
            $this->getTexts($output, 'content.xml', 'customer')
        );
        self::assertSame(
            ['Walter'],
            $this->getTexts($output, 'styles.xml', 'customer')
        );

        self::assertSame($sourceContract, $template->inspectTemplate()->toArray());
    }

    public function testBindingSurvivesClassicRenderSaveReopenAndRepeatedBinding(): void
    {
        $template = new OdtTemplate($this->supportedTemplate());

        $template->setUserField('customer', 'Maria');
        $template->assign(['classic_name' => 'Ada']);
        $template->render();

        $firstOutput = $this->outputPath('first');
        $template->save($firstOutput);

        self::assertSame(
            ['Maria'],
            $this->declarationValues($firstOutput, 'content.xml', 'customer')
        );
        self::assertSame(
            ['Maria'],
            $this->declarationValues($firstOutput, 'styles.xml', 'customer')
        );

        $reopened = new OdtTemplate($firstOutput);
        $reopened->setUserField('customer', 'Anna');

        $secondOutput = $this->outputPath('second');
        $reopened->save($secondOutput);

        self::assertSame(
            ['Anna'],
            $this->declarationValues($secondOutput, 'content.xml', 'customer')
        );
        self::assertSame(
            ['Anna'],
            $this->declarationValues($secondOutput, 'styles.xml', 'customer')
        );

        $reopenedContract = (new OdtTemplate($secondOutput))->inspectTemplate();
        $customerBindings = array_values(array_filter(
            $reopenedContract->bindings(),
            static fn ($binding): bool => $binding->variableName() === 'customer'
        ));

        self::assertNotEmpty($customerBindings);
        foreach ($customerBindings as $binding) {
            self::assertSame('SUPPORTED', $binding->supportState());
        }
    }

    public function testLoadRestoresOriginalTemplateFieldValue(): void
    {
        $templatePath = $this->supportedTemplate();
        $template = new OdtTemplate($templatePath);

        $template->setUserField('customer', 'Maria');

        $mutatedOutput = $this->outputPath('mutated-before-load');
        $template->save($mutatedOutput);
        self::assertSame(
            ['Maria'],
            $this->declarationValues($mutatedOutput, 'content.xml', 'customer')
        );

        $template->load();

        $resetOutput = $this->outputPath('reset-after-load');
        $template->save($resetOutput);

        self::assertSame(
            ['Walter'],
            $this->declarationValues($resetOutput, 'content.xml', 'customer')
        );
        self::assertSame(
            ['Walter'],
            $this->declarationValues($resetOutput, 'styles.xml', 'customer')
        );
    }

    public function testUnknownAndEmptyNamesFailWithoutMutation(): void
    {
        $template = new OdtTemplate($this->supportedTemplate());

        foreach ([
            ['', UserFieldBindingException::MALFORMED],
            ['missing', UserFieldBindingException::NOT_FOUND],
        ] as [$name, $reason]) {
            $before = $this->saveSnapshot($template, 'before-' . ($name ?: 'empty'));

            try {
                $template->setUserField($name, 'Maria');
                self::fail('Expected UserFieldBindingException for ' . ($name ?: '<empty>'));
            } catch (UserFieldBindingException $exception) {
                self::assertSame($name, $exception->fieldName());
                self::assertSame($reason, $exception->reason());
            }

            $after = $this->saveSnapshot($template, 'after-' . ($name ?: 'empty'));
            self::assertSame(
                $this->declarationValues($before, 'content.xml', 'customer'),
                $this->declarationValues($after, 'content.xml', 'customer')
            );
            self::assertSame(
                $this->declarationValues($before, 'styles.xml', 'customer'),
                $this->declarationValues($after, 'styles.xml', 'customer')
            );
        }
    }

    public function testUnsupportedTypeFailsAtomically(): void
    {
        $template = new OdtTemplate($this->template(
            contentDeclarations:
                $this->typedDeclaration('amount', 'float', '42')
                . $this->stringDeclaration('customer', 'Walter'),
            body:
                '<text:p><text:user-field-get text:name="amount">42</text:user-field-get></text:p>'
                . '<text:p><text:user-field-get text:name="customer">Walter</text:user-field-get></text:p>',
            headerDeclarations: '',
            header: ''
        ));

        try {
            $template->setUserField('amount', '99');
            self::fail('Expected unsupported User Field type to fail.');
        } catch (UserFieldBindingException $exception) {
            self::assertSame('amount', $exception->fieldName());
            self::assertSame(UserFieldBindingException::UNSUPPORTED_TYPE, $exception->reason());
        }

        $output = $this->saveSnapshot($template, 'unsupported');
        self::assertSame(
            ['42'],
            $this->declarationValues($output, 'content.xml', 'amount')
        );
        self::assertSame(
            ['Walter'],
            $this->declarationValues($output, 'content.xml', 'customer')
        );
    }

    public function testAmbiguousCrossPartAndSameRegionStatesFailAtomically(): void
    {
        $cases = [
            'cross-part' => $this->template(
                contentDeclarations: $this->stringDeclaration('customer', 'Walter'),
                body: '<text:p><text:user-field-get text:name="customer">Walter</text:user-field-get></text:p>',
                headerDeclarations: $this->stringDeclaration('customer', 'Maria'),
                header: '<text:p><text:user-field-get text:name="customer">Maria</text:user-field-get></text:p>'
            ),
            'same-region' => $this->template(
                contentDeclarations:
                    $this->stringDeclaration('customer', 'Walter')
                    . $this->stringDeclaration('customer', 'Maria'),
                body: '<text:p><text:user-field-get text:name="customer">Walter</text:user-field-get></text:p>',
                headerDeclarations: '',
                header: ''
            ),
        ];

        foreach ($cases as $label => $path) {
            $template = new OdtTemplate($path);
            $before = $this->saveSnapshot($template, $label . '-before');

            try {
                $template->setUserField('customer', 'Anna');
                self::fail('Expected ambiguous User Field state to fail: ' . $label);
            } catch (UserFieldBindingException $exception) {
                self::assertSame('customer', $exception->fieldName());
                self::assertSame(UserFieldBindingException::AMBIGUOUS, $exception->reason());
            }

            $after = $this->saveSnapshot($template, $label . '-after');
            self::assertSame(
                $this->declarationValues($before, 'content.xml', 'customer'),
                $this->declarationValues($after, 'content.xml', 'customer')
            );
            self::assertSame(
                $this->declarationValues($before, 'styles.xml', 'customer'),
                $this->declarationValues($after, 'styles.xml', 'customer')
            );
        }
    }

    public function testClassicAssignmentDoesNotBindSameNamedUserField(): void
    {
        $template = new OdtTemplate($this->template(
            contentDeclarations: $this->stringDeclaration('customer', 'Walter'),
            body:
                '<text:p>{{customer}}</text:p>'
                . '<text:p><text:user-field-get text:name="customer">Walter</text:user-field-get></text:p>',
            headerDeclarations: '',
            header: ''
        ));

        $template->assign(['customer' => 'Maria']);
        $template->render();

        $output = $this->outputPath('classic-only');
        $template->save($output);

        self::assertSame(
            ['Walter'],
            $this->declarationValues($output, 'content.xml', 'customer')
        );

        $contentXml = $this->partXml($output, 'content.xml');
        self::assertStringContainsString('Maria', $contentXml);
        self::assertStringContainsString(
            '<text:user-field-get text:name="customer">Walter</text:user-field-get>',
            $contentXml
        );
    }

    private function supportedTemplate(): string
    {
        return $this->template(
            contentDeclarations: $this->stringDeclaration('customer', 'Walter'),
            body:
                '<text:p>{{classic_name}}</text:p>'
                . '<text:p><text:user-field-get text:name="customer">Walter</text:user-field-get></text:p>'
                . '<text:p><text:user-field-get text:name="customer">Walter</text:user-field-get></text:p>',
            headerDeclarations: $this->stringDeclaration('customer', 'Walter'),
            header:
                '<text:p><text:user-field-get text:name="customer">Walter</text:user-field-get></text:p>'
        );
    }

    private function saveSnapshot(OdtTemplate $template, string $suffix): string
    {
        $path = $this->outputPath($suffix);
        $template->save($path);

        return $path;
    }

    private function template(
        string $contentDeclarations,
        string $body,
        string $headerDeclarations,
        string $header
    ): string {
        $path = tempnam(sys_get_temp_dir(), 'odt-template-authoring-c2-');
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

            $type = $decl->getAttributeNS(self::OFFICE_NS, 'value-type');
            $values[] = $type === 'string'
                ? $decl->getAttributeNS(self::OFFICE_NS, 'string-value')
                : $decl->getAttributeNS(self::OFFICE_NS, 'value');
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
        $xml = $this->partXml($path, $part);
        $dom = new DOMDocument('1.0', 'UTF-8');
        self::assertTrue($dom->loadXML($xml));

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
        $path = tempnam(sys_get_temp_dir(), 'odt-template-authoring-c2-' . $suffix . '-');
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

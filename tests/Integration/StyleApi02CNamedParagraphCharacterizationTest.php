<?php

declare(strict_types=1);

namespace OdtTemplateEngine\Tests\Integration;

use LogicException;
use OdtTemplateEngine\Document\StyleRequirement;
use OdtTemplateEngine\Elements\Paragraph;
use OdtTemplateEngine\OdtTemplate;
use OdtTemplateEngine\Utils\StyleMapper;
use PHPUnit\Framework\Attributes\RunInSeparateProcess;
use PHPUnit\Framework\TestCase;
use ZipArchive;

final class StyleApi02CNamedParagraphCharacterizationTest extends TestCase
{
    #[RunInSeparateProcess]
    public function testLegacyNamedParagraphRegistrationMaterializesForNamedReference(): void
    {
        $styleName = 'StyleApi02CLegacyNamedParagraph';
        StyleMapper::registerParagraphStyle($styleName, [
            'margin-top' => '0.1cm',
            'margin-bottom' => '0.03cm',
        ]);

        $template = new OdtTemplate($this->templatePath('template_17_textfield.odt'));
        $output = sys_get_temp_dir() . '/odt-style-api-02c-legacy-' . bin2hex(random_bytes(6)) . '.odt';

        try {
            $template->setElement(
                'INLINE_BOX',
                (new Paragraph($styleName))->addText('STYLE-API-02C legacy named paragraph')
            );
            $template->save($output);

            $content = $this->zipEntry($output, 'content.xml');
            $styles = $this->zipEntry($output, 'styles.xml');

            self::assertStringContainsString('text:style-name="' . $styleName . '"', $content);
            self::assertStringContainsString('style:name="' . $styleName . '"', $styles);
            self::assertStringContainsString('fo:margin-top="0.1cm"', $styles);
            self::assertStringContainsString('fo:margin-bottom="0.03cm"', $styles);
        } finally {
            $template->cleanup();
            if (is_file($output)) {
                unlink($output);
            }
        }
    }

    public function testParagraphConvenienceOptionsAlreadyProduceTheTargetSemanticDefinitionShape(): void
    {
        $paragraph = new Paragraph('StyleApi02CSemanticParagraph', [
            'margin-top' => '0.1cm',
            'margin-bottom' => '0.03cm',
            'bold' => true,
            'color' => '#123456',
        ]);

        $requirements = iterator_to_array($paragraph->getOwnStyleRequirements(), false);

        self::assertCount(1, $requirements);
        $requirement = $requirements[0];
        self::assertInstanceOf(StyleRequirement::class, $requirement);
        self::assertSame(StyleRequirement::KIND_DEFINITION, $requirement->kind());
        self::assertSame(StyleRequirement::SCOPE_COMMON, $requirement->scope());
        self::assertSame('paragraph', $requirement->family());
        self::assertSame(StyleRequirement::PART_STYLES, $requirement->documentPart());
        self::assertSame('StyleApi02CSemanticParagraph', $requirement->name());
        self::assertSame('Standard', $requirement->parentStyleName());
        self::assertSame([
            'style:paragraph-properties' => [
                'fo:margin-top' => '0.1cm',
                'fo:margin-bottom' => '0.03cm',
            ],
            'style:text-properties' => [
                'fo:font-weight' => 'bold',
                'fo:color' => '#123456',
            ],
        ], $requirement->propertyGroups());
    }

    public function testDocumentLocalSemanticDefinitionsAreIdempotentAndConflictingDefinitionsFail(): void
    {
        $template = new StyleApi02CContextProbeTemplate($this->templatePath('template_17_textfield.odt'));

        try {
            $definition = $this->paragraphDefinition('StyleApi02CConflictProbe', '0.1cm');
            $template->registerStyleRequirement($definition);
            $template->registerStyleRequirement($definition);

            self::assertCount(1, $template->semanticDefinitions());

            $this->expectException(LogicException::class);
            $template->registerStyleRequirement(
                $this->paragraphDefinition('StyleApi02CConflictProbe', '0.2cm')
            );
        } finally {
            $template->cleanup();
        }
    }

    public function testLoadResetsDocumentLocalSemanticDefinitions(): void
    {
        $template = new StyleApi02CContextProbeTemplate($this->templatePath('template_17_textfield.odt'));

        try {
            $template->registerStyleRequirement(
                $this->paragraphDefinition('StyleApi02CLifecycleProbe', '0.1cm')
            );
            self::assertCount(1, $template->semanticDefinitions());

            $template->load();
            self::assertSame([], $template->semanticDefinitions());

            $template->registerStyleRequirement(
                $this->paragraphDefinition('StyleApi02CLifecycleProbe', '0.2cm')
            );
            self::assertCount(1, $template->semanticDefinitions());
            self::assertSame(
                '0.2cm',
                $template->semanticDefinitions()['paragraph|common|styles.xml|StyleApi02CLifecycleProbe']
                    ->propertyGroups()['style:paragraph-properties']['fo:margin-top']
            );
        } finally {
            $template->cleanup();
        }
    }

    private function paragraphDefinition(string $name, string $marginTop): StyleRequirement
    {
        return new StyleRequirement(
            StyleRequirement::KIND_DEFINITION,
            StyleRequirement::SCOPE_COMMON,
            'paragraph',
            StyleRequirement::PART_STYLES,
            $name,
            'Standard',
            [
                'style:paragraph-properties' => [
                    'fo:margin-top' => $marginTop,
                ],
            ]
        );
    }

    private function zipEntry(string $path, string $name): string
    {
        $zip = new ZipArchive();
        self::assertTrue($zip->open($path) === true);

        try {
            $value = $zip->getFromName($name);
            self::assertIsString($value);

            return $value;
        } finally {
            $zip->close();
        }
    }

    private function templatePath(string $name): string
    {
        $path = dirname(__DIR__, 2) . '/samples/templates/' . $name;
        self::assertFileExists($path);

        return $path;
    }
}

final class StyleApi02CContextProbeTemplate extends OdtTemplate
{
    public function registerStyleRequirement(StyleRequirement $requirement): void
    {
        $this->documentContext()->styleContext()->registerRequirement($requirement);
    }

    /** @return array<string, StyleRequirement> */
    public function semanticDefinitions(): array
    {
        return $this->documentContext()->styleContext()->semanticDefinitions();
    }
}

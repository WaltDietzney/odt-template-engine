<?php

declare(strict_types=1);

namespace OdtTemplateEngineTests\Integration;

use OdtTemplateEngine\Elements\Paragraph;
use OdtTemplateEngine\OdtTemplate;
use OdtTemplateEngine\Utils\StyleMapper;
use PHPUnit\Framework\TestCase;

final class StyleApi02HLegacyFacadeRetirementTest extends TestCase
{
    public function testStyleMapperNoLongerExposesRegistryFacades(): void
    {
        foreach ([
            'registerTextStyle',
            'setTextStyle',
            'registerParagraphStyle',
            'getRegisteredStyles',
            'getTextStyles',
            'getParagraphStyles',
            'getAllRegisteredStyles',
            'hasTextStyle',
        ] as $method) {
            self::assertFalse(method_exists(StyleMapper::class, $method), $method);
        }

        self::assertFalse(class_exists('OdtTemplateEngine\\Style\\LegacyStyleRegistry'));
    }

    public function testNamedReferenceRequiresCurrentDocumentOrLocalSemanticDefinition(): void
    {
        $template = new StyleApi02HInspectableTemplate($this->templatePath());
        $paragraph = new Paragraph('02H_Unresolved_' . bin2hex(random_bytes(4)));
        $template->setElement('INLINE_BOX', $paragraph);

        self::assertCount(1, $template->documentContextForTest()->styleContext()->unresolvedReferences());
    }

    private function templatePath(): string
    {
        return dirname(__DIR__, 2) . '/samples/templates/template_17_textfield.odt';
    }
}

final class StyleApi02HInspectableTemplate extends OdtTemplate
{
    public function documentContextForTest(): \OdtTemplateEngine\OdtDocumentContext
    {
        return $this->documentContext();
    }
}

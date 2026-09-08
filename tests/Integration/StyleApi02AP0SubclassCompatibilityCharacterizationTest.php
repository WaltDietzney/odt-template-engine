<?php

declare(strict_types=1);

namespace OdtTemplateEngine\Tests\Integration;

use DOMDocument;
use DOMNode;
use OdtTemplateEngine\Elements\OdtElement;
use OdtTemplateEngine\OdtTemplate;
use PHPUnit\Framework\TestCase;

final class StyleApi02AP0SubclassCompatibilityCharacterizationTest extends TestCase
{
    public function testLegacyAndSemanticElementHooksRemainOverrideDispatchPoints(): void
    {
        $element = new ExternalStyleHookProbeElement();
        $template = new ExternalStyleFacadeProbeTemplate($this->templatePath('template_18_ListStyles.odt'));
        $template->resetStyleFacadeCounters();

        try {
            $template->setElement('my_list', $element);

            self::assertGreaterThan(0, $element->semanticRequirementCalls);
            self::assertSame(0, $element->paragraphRequirementCalls);
            self::assertSame(0, $element->textRequirementCalls);
            self::assertGreaterThan(0, $element->frameRequirementCalls);
            self::assertGreaterThan(0, $element->imageRequirementCalls);
            self::assertGreaterThan(0, $element->fillImageRequirementCalls);

            self::assertSame(0, $template->ensureParagraphStylesExistCalls);
            self::assertSame(0, $template->ensureTextStylesExistCalls);
        } finally {
            $template->cleanup();
        }
    }

    private function templatePath(string $fileName): string
    {
        $path = dirname(__DIR__, 2) . '/samples/templates/' . $fileName;
        self::assertFileExists($path);

        return $path;
    }
}

final class ExternalStyleHookProbeElement extends OdtElement
{
    public int $semanticRequirementCalls = 0;
    public int $paragraphRequirementCalls = 0;
    public int $textRequirementCalls = 0;
    public int $frameRequirementCalls = 0;
    public int $imageRequirementCalls = 0;
    public int $fillImageRequirementCalls = 0;

    public function getOwnStyleRequirements(): iterable
    {
        ++$this->semanticRequirementCalls;

        return [];
    }

    public function getOwnRequiredParagraphStyles(): array
    {
        ++$this->paragraphRequirementCalls;

        return [
            'StyleApi02AExternalParagraph' => [
                'margin-top' => '0.01cm',
            ],
        ];
    }

    public function getOwnRequiredStyles(): array
    {
        ++$this->textRequirementCalls;

        return [
            'StyleApi02AExternalText' => [
                'fo:font-weight' => 'bold',
            ],
        ];
    }

    public function getOwnFrameStyleRequirements(): array
    {
        ++$this->frameRequirementCalls;

        return [];
    }

    public function getOwnImageStyleRequirements(): array
    {
        ++$this->imageRequirementCalls;

        return [];
    }

    public function getOwnFillImageRequirements(): array
    {
        ++$this->fillImageRequirementCalls;

        return [];
    }

    public function toDomNode(DOMDocument $dom): DOMNode
    {
        $paragraph = $dom->createElement('text:p');
        $paragraph->appendChild($dom->createTextNode('External style hook probe'));

        return $paragraph;
    }
}

final class ExternalStyleFacadeProbeTemplate extends OdtTemplate
{
    public int $ensureParagraphStylesExistCalls = 0;
    public int $ensureTextStylesExistCalls = 0;

    public function resetStyleFacadeCounters(): void
    {
        $this->ensureParagraphStylesExistCalls = 0;
        $this->ensureTextStylesExistCalls = 0;
    }

    public function ensureParagraphStylesExist(array $styleMap): void
    {
        ++$this->ensureParagraphStylesExistCalls;
        parent::ensureParagraphStylesExist($styleMap);
    }

    protected function ensureTextStylesExist(array $styleMap): void
    {
        ++$this->ensureTextStylesExistCalls;
        parent::ensureTextStylesExist($styleMap);
    }
}

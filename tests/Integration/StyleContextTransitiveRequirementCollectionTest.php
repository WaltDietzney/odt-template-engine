<?php

declare(strict_types=1);

namespace OdtTemplateEngine\Tests\Integration;

use DOMDocument;
use DOMNode;
use OdtTemplateEngine\Elements\OdtElement;
use OdtTemplateEngine\Elements\Paragraph;
use OdtTemplateEngine\OdtTemplate;
use PHPUnit\Framework\TestCase;

final class StyleContextTransitiveRequirementCollectionTest extends TestCase
{
    public function testNestedFrameAndTextRequirementsAreAdoptedFromOwnedDescendants(): void
    {
        $paragraph = (new Paragraph())->addText('nested', ['bold' => true]);
        $box = (new \OdtTemplateEngine\Elements\DrawTextBox('nested-box', [
            'background-color' => '#d5f5e3',
        ]))->addElement($paragraph);
        $template = new TransitiveInspectableTemplate($this->templatePath());

        $template->setElement('my_list', $box);

        self::assertCount(1, $template->frameStyles());
        self::assertCount(1, $template->textStyles());
    }

    public function testEquivalentDuplicateRequirementsRemainIdempotent(): void
    {
        $definition = ['style:wrap' => 'none'];
        $root = (new TransitiveRequirementComposite())
            ->addElement(new TransitiveRequirementElement($definition))
            ->addElement(new TransitiveRequirementElement($definition));
        $template = new TransitiveInspectableTemplate($this->templatePath());

        $template->setElement('my_list', $root);

        self::assertSame(['shared-style' => $definition], $template->imageStyles());
    }

    public function testConflictingDuplicateRequirementsReachStyleContext(): void
    {
        $root = (new TransitiveRequirementComposite())
            ->addElement(new TransitiveRequirementElement(['style:wrap' => 'none']))
            ->addElement(new TransitiveRequirementElement(['style:wrap' => 'parallel']));
        $template = new TransitiveInspectableTemplate($this->templatePath());

        $this->expectException(\LogicException::class);
        $this->expectExceptionMessage('Image style "shared-style"');
        $template->setElement('my_list', $root);
    }

    public function testNestedImageRequirementsRemainDocumentLocal(): void
    {
        $templateA = new TransitiveInspectableTemplate($this->templatePath());
        $templateB = new TransitiveInspectableTemplate($this->templatePath());
        $imageA = new \OdtTemplateEngine\Elements\ImageElement($this->imagePath(), ['width' => '2cm']);
        $imageB = new \OdtTemplateEngine\Elements\ImageElement($this->imagePath(), ['width' => '4cm']);

        $templateA->setElement('my_list', (new Paragraph())->addElement($imageA));
        $templateB->setElement('my_list', (new Paragraph())->addElement($imageB));

        self::assertArrayHasKey((string) array_key_first($imageA->getImageStyleRequirements()), $templateA->imageStyles());
        self::assertArrayNotHasKey((string) array_key_first($imageA->getImageStyleRequirements()), $templateB->imageStyles());
        self::assertArrayHasKey((string) array_key_first($imageB->getImageStyleRequirements()), $templateB->imageStyles());
        self::assertArrayNotHasKey((string) array_key_first($imageB->getImageStyleRequirements()), $templateA->imageStyles());
    }

    private function templatePath(): string
    {
        return dirname(__DIR__, 2) . '/samples/templates/template_18_ListStyles.odt';
    }

    private function imagePath(): string
    {
        return dirname(__DIR__, 2) . '/assets/WaltDietzney.png';
    }
}

final class TransitiveInspectableTemplate extends OdtTemplate
{
    /** @return array<string, array<string, mixed>> */
    public function imageStyles(): array
    {
        return $this->documentContext()->styleContext()->imageStyles();
    }

    /** @return array<string, array<string, mixed>> */
    public function frameStyles(): array
    {
        return $this->documentContext()->styleContext()->frameStyles();
    }

    /** @return array<string, array<string, mixed>> */
    public function textStyles(): array
    {
        return $this->documentContext()->styleContext()->textStyles();
    }
}

final class TransitiveRequirementElement extends OdtElement
{
    /** @param array<string, mixed> $definition */
    public function __construct(private array $definition)
    {
    }

    public function toDomNode(DOMDocument $dom): DOMNode
    {
        return $dom->createElement('text:span');
    }

    public function registerStyles(): void
    {
    }

    /** @return array<string, array<string, mixed>> */
    public function getOwnImageStyleRequirements(): array
    {
        return ['shared-style' => $this->definition];
    }
}

final class TransitiveRequirementComposite extends OdtElement
{
    public function registerStyles(): void
    {
    }

    public function toDomNode(DOMDocument $dom): DOMNode
    {
        return $dom->createElement('text:p');
    }
}

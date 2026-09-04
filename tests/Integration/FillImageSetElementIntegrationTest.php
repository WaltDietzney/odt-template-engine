<?php

declare(strict_types=1);

namespace OdtTemplateEngine\Tests\Integration;

use DOMElement;
use DOMXPath;
use OdtTemplateEngine\Elements\CircularImageElement;
use OdtTemplateEngine\Elements\DrawTextBox;
use OdtTemplateEngine\OdtTemplate;
use PHPUnit\Framework\TestCase;

final class FillImageSetElementIntegrationTest extends TestCase
{
    /** @var list<OdtTemplate> */
    private array $templates = [];

    protected function tearDown(): void
    {
        foreach ($this->templates as $template) {
            $template->cleanup();
        }
    }

    public function testSetElementRegistersAndMaterializesFillImageBeforeSave(): void
    {
        $template = $this->template();
        $image = $this->circularImage();

        $template->setElement('test1', $image);

        self::assertSame(
            [$this->fillImageName()],
            $template->fillImageRequirementNamesForTest()
        );
        $declaration = $template->fillImageDeclarationForTest($this->fillImageName());
        self::assertInstanceOf(DOMElement::class, $declaration);
        self::assertSame(
            'Pictures/' . basename($this->imagePath()),
            $declaration->getAttributeNS('http://www.w3.org/1999/xlink', 'href')
        );
    }

    public function testNestedCircularImageDependencyIsPreparedTransitivelyBeforeSave(): void
    {
        $template = $this->template();
        $box = (new DrawTextBox('E4NestedBox'))->addElement($this->circularImage());

        $template->setElement('test1', $box);

        self::assertSame(
            [$this->fillImageName()],
            $template->fillImageRequirementNamesForTest()
        );
        self::assertInstanceOf(
            DOMElement::class,
            $template->fillImageDeclarationForTest($this->fillImageName())
        );
    }

    public function testExistingTargetFillImageRemainsAuthoritativeDuringSetElement(): void
    {
        $template = $this->template();
        $template->addFillImageDeclarationForTest(
            $this->fillImageName(),
            'Pictures/authored.png'
        );

        $template->setElement('test1', $this->circularImage());

        self::assertSame(1, $template->fillImageDeclarationCountForTest($this->fillImageName()));
        $declaration = $template->fillImageDeclarationForTest($this->fillImageName());
        self::assertInstanceOf(DOMElement::class, $declaration);
        self::assertSame(
            'Pictures/authored.png',
            $declaration->getAttributeNS('http://www.w3.org/1999/xlink', 'href')
        );
    }

    private function template(): OdtTemplate
    {
        $template = new class($this->templatePath('sample_textfeld.odt')) extends OdtTemplate {
            /** @return list<string> */
            public function fillImageRequirementNamesForTest(): array
            {
                return array_map(
                    static fn ($requirement): string => $requirement->name(),
                    $this->documentContext()->fillImageRequirements()->requirements()
                );
            }

            public function fillImageDeclarationForTest(string $name): ?DOMElement
            {
                $xpath = $this->fillImageXPathForTest();
                $node = $xpath->query('//draw:fill-image[@draw:name="' . $name . '"]')->item(0);

                return $node instanceof DOMElement ? $node : null;
            }

            public function fillImageDeclarationCountForTest(string $name): int
            {
                return $this->fillImageXPathForTest()
                    ->query('//draw:fill-image[@draw:name="' . $name . '"]')
                    ->length;
            }

            public function addFillImageDeclarationForTest(string $name, string $href): void
            {
                $dom = $this->documentContext()->stylesDom();
                $xpath = $this->fillImageXPathForTest();
                $officeStyles = $xpath->query('//office:styles')->item(0);
                if (!$officeStyles instanceof DOMElement) {
                    self::fail('Expected office:styles in test template.');
                }

                $fillImage = $dom->createElementNS(
                    'urn:oasis:names:tc:opendocument:xmlns:drawing:1.0',
                    'draw:fill-image'
                );
                $fillImage->setAttributeNS(
                    'urn:oasis:names:tc:opendocument:xmlns:drawing:1.0',
                    'draw:name',
                    $name
                );
                $fillImage->setAttributeNS(
                    'http://www.w3.org/1999/xlink',
                    'xlink:href',
                    $href
                );
                $officeStyles->insertBefore($fillImage, $officeStyles->firstChild);
            }

            private function fillImageXPathForTest(): DOMXPath
            {
                $xpath = new DOMXPath($this->documentContext()->stylesDom());
                $xpath->registerNamespace(
                    'office',
                    'urn:oasis:names:tc:opendocument:xmlns:office:1.0'
                );
                $xpath->registerNamespace(
                    'draw',
                    'urn:oasis:names:tc:opendocument:xmlns:drawing:1.0'
                );

                return $xpath;
            }
        };
        $this->templates[] = $template;

        return $template;
    }

    private function circularImage(): CircularImageElement
    {
        return new CircularImageElement($this->imagePath(), [
            'width' => '3cm',
            'height' => '3cm',
        ]);
    }

    private function fillImageName(): string
    {
        return 'cv_photo_' . pathinfo($this->imagePath(), PATHINFO_FILENAME);
    }

    private function templatePath(string $name): string
    {
        return dirname(__DIR__, 2) . '/samples/templates/' . $name;
    }

    private function imagePath(): string
    {
        return dirname(__DIR__, 2) . '/assets/WaltDietzney.png';
    }
}

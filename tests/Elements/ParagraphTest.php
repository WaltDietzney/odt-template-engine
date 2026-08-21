<?php

declare(strict_types=1);

namespace OdtTemplateEngine\Tests\Elements;

use DOMDocument;
use OdtTemplateEngine\Elements\Paragraph;
use PHPUnit\Framework\TestCase;

final class ParagraphTest extends TestCase
{
    public function testRendersPlainTextLineBreakAndTab(): void
    {
        $paragraph = (new Paragraph())
            ->addText('Hello')
            ->addLineBreak()
            ->addTab()
            ->addText('World');

        $dom = new DOMDocument('1.0', 'UTF-8');
        $node = $paragraph->toDomNode($dom);

        self::assertSame('text:p', $node->nodeName);
        self::assertSame('Standard', $node->getAttribute('text:style-name'));
        self::assertSame('HelloWorld', $node->textContent);
        self::assertSame(1, $node->getElementsByTagName('text:line-break')->length);
        self::assertSame(1, $node->getElementsByTagName('text:tab')->length);
    }

    public function testRendersHyperlinkWithOdfLinkAttributes(): void
    {
        $paragraph = (new Paragraph())
            ->addHyperlink('Example', 'https://example.com');

        $dom = new DOMDocument('1.0', 'UTF-8');
        $node = $paragraph->toDomNode($dom);
        $link = $node->getElementsByTagName('text:a')->item(0);

        self::assertNotNull($link);
        self::assertSame('https://example.com', $link->getAttribute('xlink:href'));
        self::assertSame('simple', $link->getAttribute('xlink:type'));
        self::assertSame('new', $link->getAttribute('xlink:show'));
        self::assertSame('Example', $link->textContent);
    }

    public function testTracksInlineAndParagraphStyles(): void
    {
        $paragraph = new Paragraph('CustomParagraph', [
            'text-align' => 'center',
            'margin-top' => '0.2cm',
        ]);
        $paragraph->addText('Styled text', ['bold' => true]);

        self::assertCount(1, $paragraph->getRequiredStyles());
        self::assertSame(
            [
                'CustomParagraph' => [
                    'text-align' => 'center',
                    'margin-top' => '0.2cm',
                ],
            ],
            $paragraph->getRequiredParagraphStyles()
        );
    }

    public function testCanMarkParagraphAsBulletedOrNumbered(): void
    {
        $bullet = (new Paragraph())->setBulleted();
        $numbered = (new Paragraph())->setNumbered();

        self::assertTrue($bullet->isList());
        self::assertTrue($numbered->isList());

        $dom = new DOMDocument('1.0', 'UTF-8');

        self::assertSame(
            'Bullet_20_Symbol',
            $bullet->toDomNode($dom)->getAttribute('text:style-name')
        );
        self::assertSame(
            'Numbering_20_Symbol',
            $numbered->toDomNode($dom)->getAttribute('text:style-name')
        );
    }
}

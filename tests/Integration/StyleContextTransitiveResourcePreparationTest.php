<?php

declare(strict_types=1);

namespace OdtTemplateEngine\Tests\Integration;

use OdtTemplateEngine\Elements\CircularImageElement;
use OdtTemplateEngine\Elements\DrawTextBox;
use OdtTemplateEngine\Elements\ImageElement;
use OdtTemplateEngine\Elements\ListElement;
use OdtTemplateEngine\Elements\OdtElement;
use OdtTemplateEngine\Elements\Paragraph;
use OdtTemplateEngine\Elements\RichTable;
use OdtTemplateEngine\Elements\RichTableCell;
use OdtTemplateEngine\Elements\RichText;
use OdtTemplateEngine\OdtTemplate;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use ZipArchive;

final class StyleContextTransitiveResourcePreparationTest extends TestCase
{
    /** @param callable(string): OdtElement $factory */
    #[DataProvider('compositionProvider')]
    public function testOwnedSubtreeImageResourcesReachPackage(
        string $case,
        callable $factory
    ): void {
        $template = new OdtTemplate($this->templatePath());
        $template->setElement('my_list', $factory($this->imagePath()));
        $output = sys_get_temp_dir() . '/d5e-' . $case . '-' . bin2hex(random_bytes(4)) . '.odt';

        try {
            $template->save($output);
            $zip = new ZipArchive();
            self::assertTrue($zip->open($output) === true, $case);
            try {
                self::assertNotFalse($zip->locateName('Pictures/WaltDietzney.png'), $case);
                $manifest = $zip->getFromName('META-INF/manifest.xml');
                self::assertIsString($manifest, $case);
                self::assertStringContainsString('Pictures/WaltDietzney.png', $manifest, $case);
            } finally {
                $zip->close();
            }
        } finally {
            if (is_file($output)) {
                unlink($output);
            }
        }
    }

    public function testRepeatedUseOfOneNestedImageDoesNotDuplicatePackageEntries(): void
    {
        $image = new ImageElement($this->imagePath());
        $template = new OdtTemplate($this->templatePath());
        $template->setElement('my_list', (new Paragraph())->addElement($image)->addElement($image));
        $output = $this->temporaryOutput('repeated');

        try {
            $template->save($output);
            $zip = new ZipArchive();
            self::assertTrue($zip->open($output));
            try {
                self::assertSame(1, $this->archiveEntryCount($zip, 'Pictures/WaltDietzney.png'));
                $manifest = (string) $zip->getFromName('META-INF/manifest.xml');
                self::assertSame(1, substr_count($manifest, 'Pictures/WaltDietzney.png'));
            } finally {
                $zip->close();
            }
        } finally {
            unlink($output);
        }
    }

    public function testTwoDocumentsPrepareOnlyTheirOwnNestedResources(): void
    {
        $templateA = new OdtTemplate($this->templatePath());
        $templateB = new OdtTemplate($this->templatePath());
        $templateA->setElement('my_list', (new Paragraph())->addElement(new ImageElement($this->imagePath())));
        $templateB->setElement('my_list', (new Paragraph())->addElement(new ImageElement($this->logoPath())));
        $outputA = $this->temporaryOutput('document-a');
        $outputB = $this->temporaryOutput('document-b');

        try {
            $templateA->save($outputA);
            $templateB->save($outputB);
            self::assertTrue($this->contains($outputA, 'Pictures/WaltDietzney.png'));
            self::assertFalse($this->contains($outputA, 'Pictures/Logo.png'));
            self::assertTrue($this->contains($outputB, 'Pictures/Logo.png'));
            self::assertFalse($this->contains($outputB, 'Pictures/WaltDietzney.png'));
        } finally {
            unlink($outputA);
            unlink($outputB);
        }
    }

    public function testMultipleNestedImagesPrepareEachDistinctPackageResource(): void
    {
        $paragraph = (new Paragraph())
            ->addElement(new ImageElement($this->imagePath()))
            ->addElement(new ImageElement($this->logoPath()));
        $template = new OdtTemplate($this->templatePath());
        $template->setElement('my_list', $paragraph);
        $output = $this->temporaryOutput('multiple');

        try {
            $template->save($output);
            self::assertTrue($this->contains($output, 'Pictures/WaltDietzney.png'));
            self::assertTrue($this->contains($output, 'Pictures/Logo.png'));
        } finally {
            unlink($output);
        }
    }

    /** @return array<string, array{string, callable(string): OdtElement}> */
    public static function compositionProvider(): array
    {
        return [
            'paragraph-image' => ['Paragraph -> ImageElement', static fn (string $p): OdtElement => (new Paragraph())->addElement(new ImageElement($p))],
            'richtext-paragraph-image' => ['RichText -> Paragraph -> ImageElement', static fn (string $p): OdtElement => (new RichText())->addParagraph((new Paragraph())->addElement(new ImageElement($p)))],
            'richtext-direct-image' => ['RichText -> ImageElement', static fn (string $p): OdtElement => (new RichText())->addElement(new ImageElement($p))],
            'textbox-image' => ['DrawTextBox -> ImageElement', static fn (string $p): OdtElement => (new DrawTextBox('box'))->addElement(new ImageElement($p))],
            'textbox-richtext-image' => ['DrawTextBox -> RichText -> ImageElement', static fn (string $p): OdtElement => (new DrawTextBox('box'))->addElement((new RichText())->addImage(new ImageElement($p)))],
            'list-image' => ['ListElement -> Paragraph -> ImageElement', static fn (string $p): OdtElement => (new ListElement())->addItem((new Paragraph())->addElement(new ImageElement($p)))],
            'nested-list-image' => ['nested ListElement -> Paragraph -> ImageElement', static function (string $p): OdtElement {
                return (new ListElement())->addSubList((new ListElement())->addItem((new Paragraph())->addElement(new ImageElement($p))));
            }],
            'table-paragraph-image' => ['RichTable -> Cell -> Paragraph -> ImageElement', static fn (string $p): OdtElement => (new RichTable())->addRow([new RichTableCell((new Paragraph())->addElement(new ImageElement($p)))])],
            'table-richtext-image' => ['RichTable -> Cell -> RichText -> ImageElement', static fn (string $p): OdtElement => (new RichTable())->addRow([new RichTableCell((new RichText())->addImage(new ImageElement($p)))])],
            'paragraph-circular' => ['Paragraph -> CircularImageElement', static fn (string $p): OdtElement => (new Paragraph())->addElement(new CircularImageElement($p))],
            'textbox-circular' => ['DrawTextBox -> CircularImageElement', static fn (string $p): OdtElement => (new DrawTextBox('box'))->addElement(new CircularImageElement($p))],
        ];
    }

    private function templatePath(): string
    {
        return dirname(__DIR__, 2) . '/samples/templates/template_18_ListStyles.odt';
    }

    private function imagePath(): string
    {
        return dirname(__DIR__, 2) . '/assets/WaltDietzney.png';
    }

    private function logoPath(): string
    {
        return dirname(__DIR__, 2) . '/assets/Logo.png';
    }

    private function temporaryOutput(string $label): string
    {
        return sys_get_temp_dir() . '/d5e-' . $label . '-' . bin2hex(random_bytes(4)) . '.odt';
    }

    private function contains(string $path, string $entry): bool
    {
        $zip = new ZipArchive();
        self::assertTrue($zip->open($path) === true);
        try {
            return $zip->locateName($entry) !== false;
        } finally {
            $zip->close();
        }
    }

    private function archiveEntryCount(ZipArchive $zip, string $entry): int
    {
        $count = 0;
        for ($index = 0; $index < $zip->numFiles; $index++) {
            if ($zip->getNameIndex($index) === $entry) {
                $count++;
            }
        }

        return $count;
    }
}

<?php 
use OdtTemplateEngine\Elements\ImageElement;
use OdtTemplateEngine\Elements\Paragraph;
use OdtTemplateEngine\Elements\RichText;
use OdtTemplateEngine\OdtTemplate;

$template = new OdtTemplate('templates/template_element_image.odt');

$rich = new RichText();

$tile= new Paragraph('Title');
$tile->addText('Image Settings');

$rich->addParagraph($tile)->addParagraph();

$imagePath = realpath(__DIR__ . '/../assets/Logo.png');
echo $imagePath;

function lorem(): string {
    return 'Lorem ipsum dolor sit amet, consectetur adipiscing elit. Integer nec odio. Praesent libero. Sed cursus ante dapibus diam.';
}

function addTestCase(RichText $rich, string $heading, ImageElement $image, string $textAbove = null, string $textBelow = null): void {
    $headingPara = new Paragraph();
    $headingPara->setParagraphStyle('Heading 2');
    $headingPara->addText($heading,['italic'=>true]);
    $rich->addParagraph($headingPara);
    

    if ($textAbove) {
        $rich->addParagraph($textAbove);
    }

    $p = new Paragraph();
    $p->addElement($image);
    $rich->addParagraph($p);

    if ($textBelow) {
        $rich->addParagraph($textBelow);
    }

    // Abstand nach jedem Fall
    $rich->addParagraph(new Paragraph());
}

// 1. Inline (as-char)
addTestCase($rich, '1. Inline (as-char)', new ImageElement($imagePath, [
    'width' => '4cm',
    'height' => '2cm',
    'anchor' => 'as-char'
]), lorem(), lorem());

// 2. Float right (anchor = paragraph)
addTestCase($rich, '2. Float right', new ImageElement($imagePath, [
    'width' => '3.5cm',
    'height' => '2.5cm',
    'anchor' => 'paragraph',
    'align' => 'right',
]), lorem(), lorem());

// 3. Float left (anchor = paragraph)
addTestCase($rich, '3. Float left', new ImageElement($imagePath, [
    'width' => '3.5cm',
    'height' => '2.5cm',
    'anchor' => 'paragraph',
    'align' => 'left',
]), lorem(), lorem());

// 4. Absolut positioniert (anchor = paragraph, x)
addTestCase($rich, '4. Absolut positioniert', new ImageElement($imagePath, [
    'width' => '4cm',
    'height' => '3cm',
    'anchor' => 'paragraph',
    'align' => 'absolute',
    'rel' => 'page-content',
    'x' => '5cm',
]), lorem(), lorem());

// 5. Kein spezielles Alignment (default)
addTestCase($rich, '5. Standard (centered)', new ImageElement($imagePath, [
    'width' => '4cm',
    'height' => '2cm',
    'anchor' => 'paragraph',
]), lorem(), lorem());


// 📥 Einfügen und Speichern
$template->setElement('imageRun', $rich);
$template->save('output/output_image_element.odt');

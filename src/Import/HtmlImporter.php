<?php

namespace OdtTemplateEngine\Import;

use DOMDocument;
use DOMNode;
use DOMElement;
use DOMText;
use OdtTemplateEngine\Elements\OdtElement;
use OdtTemplateEngine\Elements\RichText;
use OdtTemplateEngine\Elements\Paragraph;
use OdtTemplateEngine\Elements\ImageElement;
use OdtTemplateEngine\OdtTemplate;

/**
 * HtmlImporter ist eine Hilfsklasse, die HTML-Fragmente in RichText-Objekte konvertiert.
 * Diese Klasse analysiert HTML und erstellt aus den HTML-Tags die entsprechenden ODT-Elemente wie Text, Paragraphen und Bilder.
 */
class HtmlImporter
{
    /**
     * Wandelt einen HTML-String in ein RichText-Objekt um.
     * 
     * Diese Methode parst den HTML-String und erstellt aus den HTML-Tags die entsprechenden ODT-Elemente
     * wie Text, Absätze und andere Formate.
     * 
     * @param string $html Der HTML-String, der in RichText umgewandelt werden soll.
     * @return RichText Das RichText-Objekt, das die konvertierten HTML-Inhalte enthält.
     */
    public static function fromHtml(string $html): RichText
    {
        $doc = new DOMDocument();
        // HTML korrekt laden (UTF-8, ohne zusätzliche <html><body>-Tags)
        libxml_use_internal_errors(true);
        $doc->loadHTML('<?xml encoding="utf-8" ?><body>' . $html . '</body>');
        libxml_clear_errors();

        $body = $doc->getElementsByTagName('body')->item(0);

        $rich = new RichText();
        foreach ($body->childNodes as $child) {
            self::processNode($child, $rich);
        }

        return $rich;
    }

    /**
     * Verarbeitet einen einzelnen Knoten im HTML und fügt den entsprechenden Inhalt dem RichText-Objekt hinzu.
     * 
     * Diese Methode behandelt Textknoten, HTML-Elemente und deren Attribute und erstellt die entsprechenden ODT-Elemente.
     * 
     * @param DOMNode $node Der zu verarbeitende DOM-Knoten.
     * @param RichText $rich Das RichText-Objekt, dem der Inhalt hinzugefügt werden soll.
     * @param Paragraph|null $currentParagraph Der aktuelle Absatz, in den der Text eingefügt werden soll.
     */
    protected static function processNode(DOMNode $node, RichText $rich, ?Paragraph $currentParagraph = null): void
    {
        // 🧱 Textknoten verarbeiten
        if ($node instanceof DOMText) {
            $text = $node->wholeText;

            if (trim($text) !== '') {
                if (!$currentParagraph) {
                    $currentParagraph = new Paragraph();
                    $rich->addParagraph($currentParagraph);
                }
                $currentParagraph->addText($text);
            }
            return;
        }

        // ❌ Ignoriere Nicht-Elemente
        if (!($node instanceof DOMElement)) {
            return;
        }

        $tag = strtolower($node->nodeName);
        $style = self::parseStyleAttribute($node);

        switch ($tag) {
            case 'p':
                $para = new Paragraph();
                $rich->addParagraph($para);
                foreach ($node->childNodes as $child) {
                    self::processNode($child, $rich, $para);
                }
                break;

            case 'br':
                if ($currentParagraph) {
                    $currentParagraph->addLineBreak();
                }
                break;

            case 'strong':
            case 'b':
                $style['bold'] = true;
                foreach ($node->childNodes as $child) {
                    self::processStyledNode($child, $rich, $currentParagraph, $style);
                }
                break;

            case 'em':
            case 'i':
                $style['italic'] = true;
                foreach ($node->childNodes as $child) {
                    self::processStyledNode($child, $rich, $currentParagraph, $style);
                }
                break;

            case 'u':
                $style['underline'] = true;
                foreach ($node->childNodes as $child) {
                    self::processStyledNode($child, $rich, $currentParagraph, $style);
                }
                break;

            case 'span':
                foreach ($node->childNodes as $child) {
                    self::processStyledNode($child, $rich, $currentParagraph, $style);
                }
                break;

            case 'a':
                $href = $node->getAttribute('href');
                $label = trim($node->textContent);
                $style['color'] = '#0000ff';
                $style['underline'] = true;
                $style['href'] = $href;

                if (!$currentParagraph) {
                    $currentParagraph = new Paragraph();
                    $rich->addParagraph($currentParagraph);
                }

                $currentParagraph->addText($label, $style);
                break;

            case 'h1':
            case 'h2':
            case 'h3':
            case 'h4':
            case 'h5':
            case 'h6':
                $level = (int) substr($tag, 1); // aus "h2" → 2
                $styleName = "Heading $level";  // z. B. "Heading 2"

                $heading = new Paragraph();
                $heading->setParagraphStyle($styleName);
                $heading->addText(trim($node->textContent));
                $rich->addParagraph($heading);
                break;

            case 'ul':
                foreach ($node->childNodes as $liNode) {
                    if (strtolower($liNode->nodeName) === 'li') {
                        $para = new Paragraph();
                        $para->setBulleted();
                        $para->addText(trim($liNode->textContent));
                        $rich->addParagraph($para);
                    }
                }
                break;

            case 'ol':
                foreach ($node->childNodes as $liNode) {
                    if (strtolower($liNode->nodeName) === 'li') {
                        $para = new Paragraph();
                        $para->setNumbered();
                        $para->addText(trim($liNode->textContent));
                        $rich->addParagraph($para);
                    }
                }
                break;

            case 'img':
                $src = $node->getAttribute('src');
                $path = realpath($src);
                if (!$path || !file_exists($path)) {
                    break;
                }

                $width = $node->getAttribute('width') ?: '5cm';
                $height = $node->getAttribute('height') ?: '3cm';

                $styleOptions = self::parseStyleAttribute($node);

                $imageOptions = array_merge([
                    'width' => $width,
                    'height' => $height,
                ], $styleOptions);

                $image = new ImageElement($path, $imageOptions);

                $para = new Paragraph();
                $para->addElement($image);
                $rich->addParagraph($para);
                break;

            case 'blockquote':
                $para = new Paragraph();
                $para->setParagraphStyle('Quote');
                $para->addText(trim($node->textContent));
                $rich->addParagraph($para);
                break;

            default:
                foreach ($node->childNodes as $child) {
                    self::processNode($child, $rich, $currentParagraph);
                }
        }
    }

    /**
     * Verarbeitet einen stilisierten Knoten und fügt den Text dem RichText mit den angegebenen Stil-Optionen hinzu.
     * 
     * @param DOMNode $node Der zu verarbeitende DOM-Knoten.
     * @param RichText $rich Das RichText-Objekt, dem der Inhalt hinzugefügt werden soll.
     * @param Paragraph|null $currentParagraph Der aktuelle Absatz, in den der Text eingefügt werden soll.
     * @param array $style Das Stil-Array, das auf den Text angewendet wird.
     */
    protected static function processStyledNode(DOMNode $node, RichText $rich, ?Paragraph $currentParagraph, array $style): void
    {
        if ($node instanceof DOMText) {
            $text = $node->wholeText;
            if ($text !== '') {
                if (!$currentParagraph) {
                    $currentParagraph = new Paragraph();
                    $rich->addParagraph($currentParagraph);
                }
                $currentParagraph->addText($text, $style);
            }
        } else {
            self::processNode($node, $rich, $currentParagraph);
        }
    }

    /**
     * Parst das Style-Attribut eines HTML-Elements und gibt die entsprechenden Stil-Optionen zurück.
     * 
     * Diese Methode extrahiert und verarbeitet die CSS-Stile eines HTML-Elements und wandelt sie in ein Array um,
     * das für ODT-Elemente verwendet werden kann.
     * 
     * @param DOMElement $node Das HTML-Element, dessen Style-Attribut verarbeitet werden soll.
     * @return array Das Array mit den extrahierten Stil-Optionen.
     */
    public static function parseStyleAttribute(DOMElement $node): array
    {
        $options = [];

        $style = $node->getAttribute('style');
        if (!$style) {
            $options['anchor'] = 'as-char';
            return $options;
        }

        $styles = [];
        foreach (explode(';', $style) as $item) {
            if (strpos($item, ':') !== false) {
                [$key, $value] = explode(':', $item, 2);
                $styles[trim($key)] = trim($value);
            }
        }

        // 🔕 Display none → Bild ignorieren
        if (!empty($styles['display']) && strtolower($styles['display']) === 'none') {
            $options['ignore'] = true;
            return $options;
        }

        // 📐 Größe
        if (!empty($styles['width'])) {
            $options['width'] = $styles['width'];
        }

        if (!empty($styles['height'])) {
            $options['height'] = $styles['height'];
        }

        // 🧲 Float
        if (!empty($styles['float'])) {
            if ($styles['float'] === 'right') {
                $options['anchor'] = 'paragraph';
                $options['wrap'] = 'none';
                $options['align'] = 'right';
            } elseif ($styles['float'] === 'left') {
                $options['anchor'] = 'paragraph';
                $options['wrap'] = 'none';
                $options['align'] = 'left';
            } elseif ($styles['float'] === 'none') {
                $options['anchor'] = 'as-char';
            }
        }

        // 🧭 Positionierung
        if (!empty($styles['position']) && $styles['position'] === 'absolute') {
            $options['anchor'] = 'paragraph';
            $options['style:horizontal-pos'] = 'from-left';
            $options['style:horizontal-rel'] = 'page-content';
        }

        if (!empty($styles['left'])) {
            $options['svg:x'] = $styles['left'];
        }

        if (!empty($styles['top'])) {
            $options['svg:y'] = $styles['top'];
        }

        if (!empty($styles['margin-left'])) {
            $options['svg:x'] = $styles['margin-left'];
        }

        if (!empty($styles['margin-top'])) {
            $options['svg:y'] = $styles['margin-top'];
        }

        // display: block / inline (falls nicht von float überlagert)
        if (!empty($styles['display'])) {
            if ($styles['display'] === 'block') {
                $options['anchor'] = 'paragraph';
            } elseif ($styles['display'] === 'inline') {
                $options['anchor'] = 'as-char';
            }
        }

        // 🔙 Fallback
        if (empty($options['anchor'])) {
            $options['anchor'] = 'as-char';
        }

        return $options;
    }
}

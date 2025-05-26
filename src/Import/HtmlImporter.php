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
use OdtTemplateEngine\Elements\ListElement;
use OdtTemplateEngine\Utils\StyleMapper;
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
            case 'em':
            case 'i':
            case 'u':
            case 'mark':
            case 'del':
            case 'sub':
            case 'sup':
                $option = self::getRawStyleForTag($tag);
                $style = StyleMapper::mapTextStyleOptions($option);
                StyleMapper::registerTextStyle($style);
                foreach ($node->childNodes as $child) {
                    self::processStyledNode($child, $rich, $currentParagraph, $style);
                }
                break;

            case 'span':
                // 1. CSS lesen
                $rawStyle = $node->getAttribute('style');

                // 2. CSS parsen → ['fo:color' => '#FF0000', ...]
                $odtStyle = StyleMapper::parseInlineStyle($rawStyle);

                // 3. Stil registrieren (für automatic-styles)
                StyleMapper::registerTextStyle($odtStyle);

                // 4. Verarbeite Kinder mit Style-Array
                foreach ($node->childNodes as $child) {
                    if ($child instanceof DOMText) {
                        if (!$currentParagraph) {
                            $currentParagraph = new Paragraph();
                            $rich->addParagraph($currentParagraph);
                        }
                        $currentParagraph->addText($child->wholeText, $odtStyle); // ✅ Style als Array übergeben
                    } else {
                        self::processStyledNode($child, $rich, $currentParagraph, $odtStyle);
                    }
                }
                break;


            case 'a':
                $href = $node->getAttribute('href');
                $label = trim($node->textContent);
                if (empty($style['color'])) {
                    $style['color'] = '#0000ff';
                }
                if (!isset($style['underline'])) {
                    $style['underline'] = true;
                }
                if (!$currentParagraph) {
                    $currentParagraph = new Paragraph();
                    $rich->addParagraph($currentParagraph);
                }
                $currentParagraph->addHyperlink($label, $href, $style);
                break;

            case 'h1':
            case 'h2':
            case 'h3':
            case 'h4':
            case 'h5':
            case 'h6':
                $level = (int) substr($tag, 1);
                $styleName = "Heading $level";
                $heading = new Paragraph();
                $heading->setParagraphStyle($styleName);
                $heading->addText(trim($node->textContent));
                $rich->addParagraph($heading);
                break;

            case 'ul':
            case 'ol':
                $isOrdered = ($tag === 'ol');
                $listType = $isOrdered ? 'numbered' : 'bullet';
                $list = new ListElement($listType);

                foreach ($node->childNodes as $liNode) {
                    if (strtolower($liNode->nodeName) === 'li') {
                        $para = new Paragraph();
                        $sublist = null;

                        foreach ($liNode->childNodes as $child) {
                            if ($child->nodeName === 'ul' || $child->nodeName === 'ol') {
                                // rekursiv verschachtelte Liste extrahieren
                                ob_start(); // optional zur Fehlervermeidung
                                self::processNode($child, $rich, null);
                                ob_end_clean(); // nur für Sicherheit, wenn Liste oben hinzugefügt wird
                            } else {
                                self::processNode($child, $rich, $para);
                            }
                        }

                        $list->addItem($para);

                        // Jetzt prüfen, ob Liste direktes Kind war → wurde oben in $rich eingefügt, also "ausschneiden" und wieder zuordnen
                        $lastElement = $rich->popLastElementIfList();
                        if ($lastElement instanceof ListElement) {
                            $list->addItem($lastElement); // nested
                        }
                    }
                }

                $rich->addElement($list);
                break;

            case 'blockquote':
                $para = new Paragraph();
                $para->setParagraphStyle('Quote');
                $para->addText(trim($node->textContent));
                $rich->addParagraph($para);
                break;

            case 'img':
                $src = $node->getAttribute('src');
                $path = null;

                // 📦 1. Base64 Data URL
                if (preg_match('#^data:image/(\w+);base64,#i', $src, $match)) {
                    $ext = strtolower($match[1]);
                    $data = substr($src, strpos($src, ',') + 1);
                    $binary = base64_decode($data);
                    $ext = in_array($ext, ['png', 'jpg', 'jpeg', 'gif']) ? $ext : 'png'; // convert if needed
                    $tempPath = sys_get_temp_dir() . '/odt_img_' . uniqid() . '.' . $ext;
                    file_put_contents($tempPath, $binary);
                    $path = $tempPath;
                }

                // 🌐 2. Remote URL
                elseif (preg_match('/^https?:\/\//i', $src)) {
                    $imgContent = @file_get_contents($src);
                    if ($imgContent !== false) {
                        $ext = pathinfo(parse_url($src, PHP_URL_PATH), PATHINFO_EXTENSION) ?: 'png';
                        if (!in_array(strtolower($ext), ['png', 'jpg', 'jpeg', 'gif', 'bmp'])) {
                            $ext = 'png'; // fallback
                        }
                        $tempPath = sys_get_temp_dir() . '/odt_img_' . uniqid() . '.' . $ext;
                        file_put_contents($tempPath, $imgContent);
                        $path = $tempPath;
                    }
                }

                // 📁 3. Local File
                elseif (file_exists($src)) {
                    $path = realpath($src);
                }

                if (!$path || !file_exists($path)) {
                    break;
                }

                // 📏 Maße (aus Attributen oder Styles)
                $width = $node->getAttribute('width') ?: '5cm';
                $height = $node->getAttribute('height') ?: '3cm';
                $imageOptions = array_merge([
                    'width' => $width,
                    'height' => $height,
                ], self::parseStyleAttribute($node));

                $image = new ImageElement($path, $imageOptions);
                $para = new Paragraph();
                $para->addElement($image);
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
            if (trim($text) !== '') {
                if (!$currentParagraph) {
                    $currentParagraph = new Paragraph();
                    $rich->addParagraph($currentParagraph);
                }
                $currentParagraph->addText($text, $style);
            }
        } elseif ($node instanceof DOMElement) {
            foreach ($node->childNodes as $child) {
                self::processStyledNode($child, $rich, $currentParagraph, $style);
            }
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

    protected static function parseInlineCss(DOMElement $node): array
    {
        $styleAttr = $node->getAttribute('style');
        $styles = [];

        foreach (explode(';', $styleAttr) as $item) {
            if (strpos($item, ':') !== false) {
                [$key, $value] = explode(':', $item, 2);
                $styles[trim(strtolower($key))] = trim($value);
            }
        }

        return $styles;
    }

    private static function getRawStyleForTag(string $tag): array
    {
        return match ($tag) {
            'b', 'strong' => ['bold' => true],
            'i', 'em' => ['italic' => true],
            'u' => ['underline' => true],
            'mark' => ['background-color' => '#ffff99'],
            'del' => ['text-line-through' => true], // Spezialfall
            'sub' => ['style:text-position' => 'sub'],
            'sup' => ['style:text-position' => 'super'],
            default => [],
        };
    }




}

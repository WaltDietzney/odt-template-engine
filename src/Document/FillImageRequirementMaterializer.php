<?php

declare(strict_types=1);

namespace OdtTemplateEngine\Document;

use DOMElement;
use DOMXPath;
use InvalidArgumentException;
use OdtTemplateEngine\OdtDocumentContext;

/**
 * Materializes one document-local ODF draw:fill-image declaration.
 *
 * Existing target-document declarations are authoritative. Physical bitmap
 * copying and manifest updates remain package/resource responsibilities.
 */
final class FillImageRequirementMaterializer
{
    private const OFFICE_NAMESPACE = 'urn:oasis:names:tc:opendocument:xmlns:office:1.0';
    private const DRAW_NAMESPACE = 'urn:oasis:names:tc:opendocument:xmlns:drawing:1.0';
    private const XLINK_NAMESPACE = 'http://www.w3.org/1999/xlink';

    public function materialize(OdtDocumentContext $context, FillImageRequirement $requirement): void
    {
        if ($requirement->documentPart() !== FillImageRequirement::PART_STYLES) {
            throw new InvalidArgumentException(sprintf(
                'Fill-image declaration "%s" requires styles.xml.',
                $requirement->name()
            ));
        }

        $dom = $context->stylesDom();
        $xpath = new DOMXPath($dom);
        $xpath->registerNamespace('office', self::OFFICE_NAMESPACE);
        $xpath->registerNamespace('draw', self::DRAW_NAMESPACE);

        foreach ($xpath->query('//draw:fill-image') ?: [] as $existing) {
            if (!$existing instanceof DOMElement) {
                continue;
            }
            if ($existing->getAttributeNS(self::DRAW_NAMESPACE, 'name') === $requirement->name()) {
                return;
            }
        }

        $officeStyles = $xpath->query('/office:document-styles/office:styles')->item(0);
        if (!$officeStyles instanceof DOMElement) {
            throw new InvalidArgumentException('Fill-image declaration materialization requires office:styles in styles.xml.');
        }

        $fillImage = $dom->createElementNS(self::DRAW_NAMESPACE, 'draw:fill-image');
        $fillImage->setAttributeNS(self::DRAW_NAMESPACE, 'draw:name', $requirement->name());
        $fillImage->setAttributeNS(self::XLINK_NAMESPACE, 'xlink:href', $requirement->href());
        $fillImage->setAttributeNS(self::XLINK_NAMESPACE, 'xlink:type', 'simple');
        $fillImage->setAttributeNS(self::XLINK_NAMESPACE, 'xlink:show', 'embed');
        $fillImage->setAttributeNS(self::XLINK_NAMESPACE, 'xlink:actuate', 'onLoad');

        $officeStyles->insertBefore($fillImage, $officeStyles->firstChild);
    }
}

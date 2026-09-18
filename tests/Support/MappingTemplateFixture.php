<?php

declare(strict_types=1);

namespace OdtTemplateEngine\Tests\Support;

use ZipArchive;

final class MappingTemplateFixture
{
    public static function create(
        string $additionalBody = '',
        ?string $experienceBody = null,
        string $stylesBody = '',
        string $contentDeclarations = ''
    ): string
    {
        $experienceBody ??= '<text:p>{{company}}</text:p>'
            . '<text:section text:name="#foreach:projects"><text:p>{{title}}</text:p></text:section>';
        $path = tempnam(sys_get_temp_dir(), 'mapping-template-e1-');
        if (!is_string($path)) {
            throw new \RuntimeException('Could not allocate a mapping fixture path.');
        }
        unlink($path);
        $path .= '.odt';

        $namespaces = ' xmlns:office="urn:oasis:names:tc:opendocument:xmlns:office:1.0"'
            . ' xmlns:text="urn:oasis:names:tc:opendocument:xmlns:text:1.0"'
            . ' xmlns:table="urn:oasis:names:tc:opendocument:xmlns:table:1.0"'
            . ' xmlns:draw="urn:oasis:names:tc:opendocument:xmlns:drawing:1.0"'
            . ' xmlns:style="urn:oasis:names:tc:opendocument:xmlns:style:1.0"'
            . ' xmlns:fo="urn:oasis:names:tc:opendocument:xmlns:xsl-fo-compatible:1.0"'
            . ' xmlns:loext="urn:org:documentfoundation:names:experimental:office:xmlns:loext:1.0"'
            . ' xmlns:xlink="http://www.w3.org/1999/xlink"';
        $zip = new ZipArchive();
        if ($zip->open($path, ZipArchive::CREATE | ZipArchive::OVERWRITE) !== true) {
            throw new \RuntimeException('Could not create a mapping fixture package.');
        }

        $zip->addFromString('mimetype', 'application/vnd.oasis.opendocument.text');
        $zip->addFromString(
            'content.xml',
            '<?xml version="1.0" encoding="UTF-8"?>'
            . '<office:document-content' . $namespaces . '><office:automatic-styles/>'
            . '<office:body><office:text>' . $contentDeclarations . '<text:p>{{name}}</text:p>'
            . '<text:section text:name="#foreach:experience">' . $experienceBody
            . '</text:section>'
            . '<text:section text:name="Profile"><text:p>Profile body</text:p></text:section>'
            . '<text:bookmark text:name="Signature"/>'
            . '<draw:frame draw:name="Portrait"><draw:image xlink:href="Pictures/portrait.png"/></draw:frame>'
            . '<table:table table:name="Skills"/>'
            . $additionalBody
            . '</office:text></office:body></office:document-content>'
        );
        $zip->addFromString(
            'styles.xml',
            '<?xml version="1.0" encoding="UTF-8"?>'
            . '<office:document-styles' . $namespaces . '><office:styles/><office:automatic-styles/>'
            . ($stylesBody === ''
                ? '<office:master-styles/>'
                : '<office:master-styles><style:master-page style:name="Standard">'
                    . $stylesBody
                    . '</style:master-page></office:master-styles>')
            . '</office:document-styles>'
        );
        $zip->addFromString(
            'meta.xml',
            '<?xml version="1.0" encoding="UTF-8"?>'
            . '<office:document-meta xmlns:office="urn:oasis:names:tc:opendocument:xmlns:office:1.0">'
            . '<office:meta/></office:document-meta>'
        );
        $zip->addFromString(
            'META-INF/manifest.xml',
            '<?xml version="1.0" encoding="UTF-8"?>'
            . '<manifest:manifest xmlns:manifest="urn:oasis:names:tc:opendocument:xmlns:manifest:1.0">'
            . '<manifest:file-entry manifest:full-path="/" manifest:media-type="application/vnd.oasis.opendocument.text"/>'
            . '<manifest:file-entry manifest:full-path="content.xml" manifest:media-type="text/xml"/>'
            . '<manifest:file-entry manifest:full-path="styles.xml" manifest:media-type="text/xml"/>'
            . '<manifest:file-entry manifest:full-path="meta.xml" manifest:media-type="text/xml"/>'
            . '</manifest:manifest>'
        );
        $zip->close();

        return $path;
    }
}

<?php

declare(strict_types=1);

namespace OdtTemplateEngine\Tests\Integration;

use DOMDocument;
use DOMElement;
use OdtTemplateEngine\Elements\Paragraph;
use OdtTemplateEngine\OdtTemplate;
use OdtTemplateEngine\Template\UserFieldBindingException;
use PHPUnit\Framework\TestCase;
use ZipArchive;

final class NativeUserFieldConstructionTest extends TestCase
{
    private const TEXT = 'urn:oasis:names:tc:opendocument:xmlns:text:1.0';
    private const OFFICE = 'urn:oasis:names:tc:opendocument:xmlns:office:1.0';
    private const STYLE = 'urn:oasis:names:tc:opendocument:xmlns:style:1.0';

    /** @var list<string> */
    private array $paths = [];

    protected function tearDown(): void
    {
        foreach ($this->paths as $path) {
            if (is_file($path)) {
                unlink($path);
            }
        }
    }

    public function testConstructBindRepeatSaveAndReloadWithNativeOrderedReferences(): void
    {
        $t = new ConstructionTemplate($this->fixture());
        $source = $t->inspectTemplate()->toArray();
        try {
            $name = 'Kontakt "E-Mail" & Ä';
            $value = "Änne & Co <test> 😀\n" . str_repeat('lange Adresse ', 25);
            $t->declareUserField($name, '');
            $first = $t->xml();
            $t->declareUserField($name, '');
            self::assertSame($first, $t->xml());
            $p = (new Paragraph())->addText('Vor ')
                ->addUserField($name, ['color' => '#245678', 'bold' => true])
                ->addText(' Mitte ')->addUserField($name)->addText(' Nach');
            $t->appendBody($p);
            $t->setUserField($name, $value);
            $t->render();
            $a = $this->path();
            $b = $this->path();
            $t->save($a);
            $t->save($b);
            foreach (['content.xml', 'styles.xml', 'meta.xml'] as $part) {
                self::assertSame($this->part($a, $part), $this->part($b, $part));
            }
            self::assertSame($source, $t->inspectTemplate()->toArray());
            $dom = new DOMDocument();
            $dom->loadXML($this->part($a, 'content.xml'));
            $decl = $dom->getElementsByTagNameNS(self::TEXT, 'user-field-decl');
            self::assertCount(1, $decl);
            self::assertSame($value, $decl->item(0)->getAttributeNS(self::OFFICE, 'string-value'));
            $refs = $dom->getElementsByTagNameNS(self::TEXT, 'user-field-get');
            self::assertCount(2, $refs);
            self::assertSame($name, $refs->item(0)->getAttributeNS(self::TEXT, 'name'));
            self::assertSame('', $refs->item(0)->textContent);
            self::assertSame('Vor  Mitte  Nach', $dom->getElementsByTagNameNS(self::TEXT, 'p')->item(0)->textContent);
            self::assertSame('span', $refs->item(0)->parentNode->localName);
            $reopened = new ConstructionTemplate($a);
            try {
                $reopened->declareUserField($name, $value);
                $reopened->setUserField($name, 'rebound');
                self::assertStringContainsString('rebound', $reopened->xml()['content.xml']);
            } finally {
                $reopened->cleanup();
            }
        } finally {
            $t->cleanup();
        }
    }

    public function testCrossPartIdenticalDeclarationsReuseAndBindingRemainAuthoritative(): void
    {
        $t = new ConstructionTemplate($this->fixture('same', 'same'));
        try {
            $before = $t->xml();
            $t->declareUserField('existing', 'same');
            self::assertSame($before, $t->xml());
            $t->setUserField('existing', 'changed');
            foreach ($t->xml() as $xml) {
                self::assertStringContainsString('office:string-value="changed"', $xml);
                self::assertStringContainsString('old-cache', $xml);
            }
        } finally {
            $t->cleanup();
        }
    }

    public function testConflictsRejectWithoutPartialMutationAcrossParts(): void
    {
        foreach ([['same', 'same', 'different'], ['one', 'two', 'one']] as [$body, $header, $requested]) {
            $t = new ConstructionTemplate($this->fixture($body, $header));
            try {
                $before = $t->xml();
                try {
                    $t->declareUserField('existing', $requested);
                    self::fail('Expected conflict');
                } catch (UserFieldBindingException $e) {
                    self::assertSame(UserFieldBindingException::AMBIGUOUS, $e->reason());
                    self::assertSame($before, $t->xml());
                }
            } finally {
                $t->cleanup();
            }
        }
    }

    public function testOrphanAndUnsupportedExistingFieldsAreNotSilentlyRepaired(): void
    {
        foreach (['orphan', 'float'] as $kind) {
            $path = $this->fixture('same');
            $zip = new ZipArchive();
            $zip->open($path);
            $dom = new DOMDocument();
            $dom->loadXML($zip->getFromName('content.xml'));
            $decl = $dom->getElementsByTagNameNS(self::TEXT, 'user-field-decl')->item(0);
            if ($kind === 'orphan') {
                $decl->parentNode->removeChild($decl);
            } else {
                $decl->setAttributeNS(self::OFFICE, 'office:value-type', 'float');
            }
            $zip->addFromString('content.xml', $dom->saveXML());
            $zip->close();
            $t = new ConstructionTemplate($path);
            try {
                $before = $t->xml();
                try {
                    $t->declareUserField('existing', 'same');
                    self::fail('Expected rejection');
                } catch (UserFieldBindingException $e) {
                    self::assertSame($kind === 'orphan' ? 'MALFORMED' : 'UNSUPPORTED_TYPE', $e->reason());
                    self::assertSame($before, $t->xml());
                }
            } finally {
                $t->cleanup();
            }
        }
    }

    public function testInvalidNamesAndXmlValuesFailBeforeMutation(): void
    {
        $t = new ConstructionTemplate($this->fixture());
        try {
            $before = $t->xml();
            foreach ([['', 'x'], ['  ', 'x'], ["bad\0", 'x'], ['valid', "bad\0"], ["\xFF", 'x']] as [$name, $value]) {
                try {
                    $t->declareUserField($name, $value);
                    self::fail('Expected invalid input');
                } catch (\InvalidArgumentException) {
                    self::assertSame($before, $t->xml());
                }
            }
            foreach (['', ' ', "bad\0", "\xFF"] as $name) {
                $p = (new Paragraph())->addText('keep');
                try {
                    $p->addUserField($name);
                    self::fail('Expected invalid name');
                } catch (\InvalidArgumentException) {
                    self::assertSame('keep', $p->toDomNode(new DOMDocument())->textContent);
                }
            }
        } finally {
            $t->cleanup();
        }
    }

    public function testFieldStyleApplicationAndTextOrderDoNotReorderLegacyEmbeddedElements(): void
    {
        $p = (new Paragraph())->addText('A')->addElement((new Paragraph())->addText('embedded'))
            ->addUserField('email')->addText('B');
        $p->applyTextStyle(['color' => '#123456']);
        $dom = new DOMDocument();
        $node = $p->toDomNode($dom);
        self::assertSame('ABembedded', $node->textContent);
        self::assertSame('text:p', $node->lastChild->nodeName);
        self::assertSame('user-field-get', $node->childNodes->item(1)->firstChild->localName);
        self::assertSame('text:span', $node->childNodes->item(1)->nodeName);
    }

    public function testPublicPageStyleAuthoringUsesTheSameFieldInBodyHeaderAndFooter(): void
    {
        $t = new ConstructionTemplate($this->fixture());
        try {
            $t->declareUserField('shared', 'initial');
            $t->definePageLayout('FieldLayout', ['page-width' => '21cm', 'page-height' => '29.7cm']);
            $t->definePageStyle('FieldPage', 'FieldLayout', [
                'header' => (new Paragraph())->addUserField('shared', ['bold' => true]),
                'footer' => (new Paragraph())->addUserField('shared'),
            ]);
            $t->assignPageStyle('FieldBody', 'FieldPage');
            $t->appendBody((new Paragraph('FieldBody'))->addUserField('shared'));
            $t->setUserField('shared', 'all regions');
            $out = $this->path();
            $t->save($out);
            foreach (['content.xml' => 1, 'styles.xml' => 2] as $part => $count) {
                $dom = new DOMDocument();
                $dom->loadXML($this->part($out, $part));
                self::assertCount($count, $dom->getElementsByTagNameNS(self::TEXT, 'user-field-get'));
            }
            $reopened = new ConstructionTemplate($out);
            try {
                $reopened->setUserField('shared', 'reloaded regions');
                self::assertStringContainsString('reloaded regions', $reopened->xml()['content.xml']);
            } finally {
                $reopened->cleanup();
            }
        } finally {
            $t->cleanup();
        }
    }

    public function testNewDeclarationsFollowVariableDeclarationsAndRejectAmbiguousContainers(): void
    {
        $path = $this->fixture();
        $zip = new ZipArchive();
        $zip->open($path);
        $dom = new DOMDocument();
        $dom->loadXML($zip->getFromName('content.xml'));
        $body = $dom->getElementsByTagNameNS(self::OFFICE, 'text')->item(0);
        $body->appendChild($dom->createElementNS(self::TEXT, 'text:variable-decls'));
        $zip->addFromString('content.xml', $dom->saveXML());
        $zip->close();
        $t = new ConstructionTemplate($path);
        try {
            $t->declareUserField('first', 'value');
            $t->declareUserField('second', '');
            $dom->loadXML($t->xml()['content.xml']);
            $body = $dom->getElementsByTagNameNS(self::OFFICE, 'text')->item(0);
            $children = array_values(array_filter(iterator_to_array($body->childNodes), static fn ($n) => $n instanceof DOMElement));
            self::assertSame('variable-decls', $children[0]->localName);
            self::assertSame('user-field-decls', $children[1]->localName);
            self::assertCount(2, $children[1]->getElementsByTagNameNS(self::TEXT, 'user-field-decl'));
        } finally {
            $t->cleanup();
        }
        $body->appendChild($dom->createElementNS(self::TEXT, 'text:user-field-decls'));
        $zip->open($path);
        $zip->addFromString('content.xml', $dom->saveXML());
        $zip->close();
        $t = new ConstructionTemplate($path);
        try {
            $before = $t->xml();
            try {
                $t->declareUserField('third', 'value');
                self::fail('Expected ambiguous container failure');
            } catch (UserFieldBindingException $e) {
                self::assertSame('AMBIGUOUS', $e->reason());
                self::assertSame($before, $t->xml());
            }
        } finally {
            $t->cleanup();
        }
    }

    public function testHeaderOnlyDeclarationIsReusedWithoutCreatingABodyDuplicate(): void
    {
        $t = new ConstructionTemplate($this->fixture(null, 'header only'));
        try {
            $before = $t->xml();
            $t->declareUserField('existing', 'header only');
            self::assertSame($before, $t->xml());
            $t->appendBody((new Paragraph())->addUserField('existing'));
            $t->setUserField('existing', 'shared value');
            self::assertStringNotContainsString('text:user-field-decl', $t->xml()['content.xml']);
            self::assertStringContainsString('shared value', $t->xml()['styles.xml']);
        } finally {
            $t->cleanup();
        }
    }

    public function testNumericStringNamesRetainTheirIdentityThroughAnalysisAndBinding(): void
    {
        $t = new ConstructionTemplate($this->fixture());
        try {
            foreach (['0', '123', '01'] as $name) {
                $t->declareUserField($name, 'initial');
                $before = $t->xml();
                $t->declareUserField($name, 'initial');
                self::assertSame($before, $t->xml());
                $t->appendBody((new Paragraph())->addUserField($name));
                $t->setUserField($name, 'value-' . $name);
            }
            $dom = new DOMDocument();
            $dom->loadXML($t->xml()['content.xml']);
            self::assertCount(3, $dom->getElementsByTagNameNS(self::TEXT, 'user-field-decl'));
            self::assertSame(
                ['0', '123', '01'],
                array_map(
                    static fn ($n) => $n->getAttributeNS(self::TEXT, 'name'),
                    iterator_to_array($dom->getElementsByTagNameNS(self::TEXT, 'user-field-decl'))
                )
            );
        } finally {
            $t->cleanup();
        }
    }

    private function fixture(?string $bodyValue = null, ?string $headerValue = null): string
    {
        $path = $this->path();
        copy(dirname(__DIR__) . '/Fixtures/LegacySamples/templates/template_15_simpleTableStyled.odt', $path);
        $zip = new ZipArchive();
        $zip->open($path);
        foreach (['content.xml', 'styles.xml'] as $part) {
            $dom = new DOMDocument();
            $dom->loadXML($zip->getFromName($part));
            if ($part === 'content.xml') {
                $container = $dom->getElementsByTagNameNS(self::OFFICE, 'text')->item(0);
                while ($container->firstChild) {
                    $container->removeChild($container->firstChild);
                }
                $value = $bodyValue;
            } else {
                $master = $dom->getElementsByTagNameNS(self::STYLE, 'master-page')->item(0);
                $container = $dom->createElementNS(self::STYLE, 'style:header');
                $master->appendChild($container);
                $value = $headerValue;
            }
            if ($value !== null) {
                $decls = $dom->createElementNS(self::TEXT, 'text:user-field-decls');
                $decl = $dom->createElementNS(self::TEXT, 'text:user-field-decl');
                $decl->setAttributeNS(self::TEXT, 'text:name', 'existing');
                $decl->setAttributeNS(self::OFFICE, 'office:value-type', 'string');
                $decl->setAttributeNS(self::OFFICE, 'office:string-value', $value);
                $decls->appendChild($decl);
                $container->appendChild($decls);
                $p = $dom->createElementNS(self::TEXT, 'text:p');
                $ref = $dom->createElementNS(self::TEXT, 'text:user-field-get');
                $ref->setAttributeNS(self::TEXT, 'text:name', 'existing');
                $ref->appendChild($dom->createTextNode('old-cache'));
                $p->appendChild($ref);
                $container->appendChild($p);
            }
            $zip->addFromString($part, $dom->saveXML());
        }
        $zip->close();
        return $path;
    }

    private function path(): string
    {
        $path = sys_get_temp_dir() . '/native-field-' . bin2hex(random_bytes(8)) . '.odt';
        $this->paths[] = $path;
        return $path;
    }

    private function part(string $path, string $part): string
    {
        $zip = new ZipArchive();
        $zip->open($path);
        $result = $zip->getFromName($part);
        $zip->close();
        return $result;
    }
}

final class ConstructionTemplate extends \OdtTemplateEngine\PageLayoutOdtTemplate
{
    /** @return array<string, string> */
    public function xml(): array
    {
        return [
            'content.xml' => $this->documentContext()->contentDom()->saveXML(),
            'styles.xml' => $this->documentContext()->stylesDom()->saveXML(),
        ];
    }
}

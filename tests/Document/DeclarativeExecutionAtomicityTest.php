<?php

declare(strict_types=1);

namespace OdtTemplateEngine\Tests\Document;

use DOMDocument;
use DOMElement;
use OdtTemplateEngine\Document\DeclarativeConditionExecutor;
use OdtTemplateEngine\Document\DeclarativeForeachExecutionException;
use OdtTemplateEngine\OdtDocumentContext;
use OdtTemplateEngine\Template\TemplateContract;
use OdtTemplateEngine\Template\TemplateContractInspector;
use PHPUnit\Framework\TestCase;

final class DeclarativeExecutionAtomicityTest extends TestCase
{
    private const OFFICE = 'urn:oasis:names:tc:opendocument:xmlns:office:1.0';
    private const STYLE = 'urn:oasis:names:tc:opendocument:xmlns:style:1.0';
    private const TEXT = 'urn:oasis:names:tc:opendocument:xmlns:text:1.0';

    public function testLaterForeachFailureRestoresEarlierClonesAndRetryReusesIdentity(): void
    {
        [$context, $contract] = $this->fixture([
            $this->definition('body', '#foreach:people', '{{name}}'),
        ]);
        $before = $this->xml($context->contentDom());

        try {
            (new DeclarativeConditionExecutor())->execute($context, $contract, [
                'people' => [['name' => 'One'], ['missing' => 'Two']],
            ]);
            self::fail('Expected the second item to fail.');
        } catch (\Throwable) {
            self::assertSame($before, $this->xml($context->contentDom()));
        }

        (new DeclarativeConditionExecutor())->execute($context, $contract, [
            'people' => [['name' => 'One'], ['name' => 'Two']],
        ]);

        self::assertStringContainsString('text:name="#foreach:people_1"', $this->xml($context->contentDom()));
        self::assertStringContainsString('text:name="#foreach:people_2"', $this->xml($context->contentDom()));
        self::assertStringContainsString('One', $this->xml($context->contentDom()));
        self::assertStringContainsString('Two', $this->xml($context->contentDom()));
    }

    public function testNestedFailureRestoresOuterMutation(): void
    {
        [$context, $contract] = $this->fixture([
            $this->definition('body', '#foreach:experiences', '{{company}}', null, [
                $this->definition('body', '#foreach:projects', '{{project}}'),
            ]),
        ]);
        $before = $this->xml($context->contentDom());

        $this->expectException(DeclarativeForeachExecutionException::class);
        try {
            (new DeclarativeConditionExecutor())->execute($context, $contract, [
                'experiences' => [[
                    'company' => 'A',
                    'projects' => null,
                ]],
            ]);
        } finally {
            self::assertSame($before, $this->xml($context->contentDom()));
        }
    }

    public function testLaterFailureRollsBackEarlierConditionAcrossTheExecutionUnit(): void
    {
        [$context, $contract] = $this->fixture([
            $this->definition('body', '#if:remove', 'removed'),
            $this->definition('body', '#foreach:people', '{{name}}'),
        ]);
        $before = $this->xml($context->contentDom());

        $this->expectException(DeclarativeForeachExecutionException::class);
        try {
            (new DeclarativeConditionExecutor())->execute($context, $contract, [
                'remove' => false,
                'people' => 'invalid',
            ]);
        } finally {
            self::assertSame($before, $this->xml($context->contentDom()));
        }
    }

    public function testCrossPartFailureRestoresContentAndStylesDocuments(): void
    {
        [$context, $contract] = $this->fixture([
            $this->definition('body', '#foreach:bodyItems', '{{name}}'),
            $this->definition('header', '#foreach:headerItems', '{{name}}', 'Standard'),
        ]);
        $contentBefore = $this->xml($context->contentDom());
        $stylesBefore = $this->xml($context->stylesDom());

        $this->expectException(DeclarativeForeachExecutionException::class);
        try {
            (new DeclarativeConditionExecutor())->execute($context, $contract, [
                'bodyItems' => [['name' => 'Body']],
                'headerItems' => null,
            ]);
        } finally {
            self::assertSame($contentBefore, $this->xml($context->contentDom()));
            self::assertSame($stylesBefore, $this->xml($context->stylesDom()));
        }
    }

    /** @return array{0: OdtDocumentContext, 1: TemplateContract} */
    private function fixture(array $definitions): array
    {
        $content = $this->document('office:document-content');
        $body = $content->createElementNS(self::OFFICE, 'office:body');
        $text = $content->createElementNS(self::OFFICE, 'office:text');
        foreach ($definitions as $definition) {
            if ($definition['region'] === 'body') {
                $text->appendChild($this->section($content, $definition));
            }
        }
        $body->appendChild($text);
        $content->documentElement->appendChild($body);

        $styles = $this->document('office:document-styles');
        $masters = [];
        foreach ($definitions as $definition) {
            if ($definition['region'] === 'body') {
                continue;
            }
            $owner = $definition['owner'];
            $masters[$owner] ??= $this->masterPage($styles, $owner);
            $carrier = $styles->createElementNS(
                self::STYLE,
                $definition['region'] === 'header' ? 'style:header' : 'style:footer'
            );
            $carrier->appendChild($this->section($styles, $definition));
            $masters[$owner]->appendChild($carrier);
        }
        foreach ($masters as $master) {
            $styles->documentElement->appendChild($master);
        }

        $contract = (new TemplateContractInspector())->inspect($content, $styles);
        return [
            new OdtDocumentContext(
                $this->copy($content),
                $this->copy($styles),
                $this->document('office:document-meta')
            ),
            $contract,
        ];
    }

    private function definition(
        string $region,
        string $name,
        string $text,
        ?string $owner = null,
        array $children = []
    ): array {
        return compact('region', 'name', 'text', 'owner', 'children');
    }

    private function section(DOMDocument $dom, array $definition): DOMElement
    {
        $section = $dom->createElementNS(self::TEXT, 'text:section');
        $section->setAttribute('text:name', $definition['name']);
        $paragraph = $dom->createElementNS(self::TEXT, 'text:p');
        $paragraph->appendChild($dom->createTextNode($definition['text']));
        $section->appendChild($paragraph);
        foreach ($definition['children'] as $child) {
            $section->appendChild($this->section($dom, $child));
        }

        return $section;
    }

    private function masterPage(DOMDocument $dom, string $name): DOMElement
    {
        $master = $dom->createElementNS(self::STYLE, 'style:master-page');
        $master->setAttribute('style:name', $name);

        return $master;
    }

    private function document(string $root): DOMDocument
    {
        $dom = new DOMDocument('1.0', 'UTF-8');
        $dom->loadXML(sprintf(
            '<%s xmlns:office="%s" xmlns:style="%s" xmlns:text="%s"/>',
            $root,
            self::OFFICE,
            self::STYLE,
            self::TEXT
        ));

        return $dom;
    }

    private function copy(DOMDocument $source): DOMDocument
    {
        $copy = new DOMDocument('1.0', 'UTF-8');
        $copy->loadXML($source->saveXML());

        return $copy;
    }

    private function xml(DOMDocument $document): string
    {
        return $document->saveXML() ?: '';
    }
}

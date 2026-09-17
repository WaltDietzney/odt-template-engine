<?php

declare(strict_types=1);

namespace OdtTemplateEngine\Tests\Mapping;

use OdtTemplateEngine\Mapping\ApplicationPath;
use OdtTemplateEngine\Mapping\DependencyMapping;
use OdtTemplateEngine\Mapping\DocumentCapabilityMapping;
use OdtTemplateEngine\Mapping\MappingDefinition;
use OdtTemplateEngine\Mapping\NativeObjectActionMapping;
use PHPUnit\Framework\TestCase;

final class MappingDefinitionTest extends TestCase
{
    public function testContainsExplicitMappingsForAllThreeTargetFamilies(): void
    {
        $definition = new MappingDefinition(
            [new DependencyMapping(ApplicationPath::parse('person.name'), 'name')],
            [new NativeObjectActionMapping(
                ApplicationPath::parse('person.photo'),
                'frame',
                'Portrait',
                'replace-image'
            )],
            [new DocumentCapabilityMapping(ApplicationPath::parse('person.author'), 'metadata', 'author')]
        );

        self::assertCount(1, $definition->dependencies());
        self::assertCount(1, $definition->nativeObjectActions());
        self::assertCount(1, $definition->documentCapabilities());
        self::assertSame('replace-image', $definition->nativeObjectActions()[0]->actionId());
        self::assertSame('frame', $definition->nativeObjectActions()[0]->targetKind());
    }

    public function testOneSourceCanFeedMultipleTargetsAndConflictsRemainRepresentable(): void
    {
        $sameSource = ApplicationPath::parse('person.name');
        $definition = new MappingDefinition(
            [
                new DependencyMapping($sameSource, 'name'),
                new DependencyMapping(ApplicationPath::parse('customer.name'), 'name'),
            ],
            [
                new NativeObjectActionMapping($sameSource, 'frame', 'Portrait', 'replace-image'),
                new NativeObjectActionMapping(ApplicationPath::parse('image.backup'), 'frame', 'Portrait', 'replace-image'),
            ],
            [
                new DocumentCapabilityMapping($sameSource, 'metadata', 'author'),
                new DocumentCapabilityMapping(ApplicationPath::parse('customer.author'), 'metadata', 'author'),
            ]
        );

        self::assertCount(2, $definition->dependencies());
        self::assertCount(2, $definition->nativeObjectActions());
        self::assertCount(2, $definition->documentCapabilities());
    }
}

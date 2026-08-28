<?php

declare(strict_types=1);

namespace OdtTemplateEngine\Tests\Integration;

use OdtTemplateEngine\Document\SectionInstantiationException;
use OdtTemplateEngine\Document\TargetNotFoundException;
use OdtTemplateEngine\OdtTemplate;
use PHPUnit\Framework\TestCase;

final class SectionCollectionInstantiationTest extends TestCase
{
    /** @var list<string> */
    private array $outputs = [];

    protected function tearDown(): void
    {
        foreach ($this->outputs as $output) {
            if (is_file($output)) {
                unlink($output);
            }
        }
    }

    public function testEmptyCollectionRemovesPrototypeAndReturnsNoInstances(): void
    {
        $template = new OdtTemplate($this->templatePath());
        $prototype = $template->section('ExperienceEntry');

        self::assertSame([], $prototype->instantiateMany([]));
        $this->expectException(TargetNotFoundException::class);
        $prototype->descriptor();
    }

    public function testSingleAndMultipleCollectionsFinalizeInInputOrder(): void
    {
        $template = new OdtTemplate($this->templatePath());
        $prototype = $template->section('ExperienceEntry');
        $instances = $prototype->instantiateMany([
            ['note' => 'A', 'position' => 'Rolle A'],
            ['note' => 'B', 'position' => 'Rolle B'],
            ['note' => 'C', 'position' => 'Rolle C'],
        ]);

        self::assertSame(['ExperienceEntry_1', 'ExperienceEntry_2', 'ExperienceEntry_3'], array_map(
            static fn ($instance): string => $instance->name(),
            $instances
        ));
        self::assertStringContainsString('Rolle A', $instances[0]->text());
        self::assertStringContainsString('Rolle B', $instances[1]->text());
        self::assertStringContainsString('Rolle C', $instances[2]->text());
        self::assertStringNotContainsString('{{position}}', implode('|', array_map(
            static fn ($instance): string => $instance->text(),
            $instances
        )));
        $this->expectException(TargetNotFoundException::class);
        $prototype->text();
    }

    public function testInstantiateRemainsPrototypePreserving(): void
    {
        $template = new OdtTemplate($this->templatePath());
        $prototype = $template->section('ExperienceEntry');
        $prototype->instantiate(['note' => 'A', 'position' => 'Rolle A']);

        self::assertStringContainsString('{{position}}', $prototype->text());
        self::assertSame(2, count(array_filter(
            $template->inspect()->sections(),
            static fn ($section): bool => str_starts_with($section->name(), 'ExperienceEntry')
        )));
    }

    public function testInvalidMiddleItemRollsBackEntireCollectionAndKeepsPrototypeUsable(): void
    {
        $template = new OdtTemplate($this->templatePath());
        $prototype = $template->section('ExperienceEntry');
        $before = $template->inspect()->toArray();

        try {
            $prototype->instantiateMany([
                ['note' => 'A', 'position' => 'Rolle A'],
                ['note' => 'Invalid', 'position' => ['not scalar']],
                ['note' => 'C', 'position' => 'Rolle C'],
            ]);
            self::fail('Expected invalid collection item to fail.');
        } catch (SectionInstantiationException $exception) {
            self::assertSame('invalid binding data', $exception->reason());
        }

        self::assertSame($before, $template->inspect()->toArray());
        self::assertStringContainsString('{{position}}', $prototype->text());
    }

    public function testNestedCollectionsFinalizeOnlyTheirLocalPrototypes(): void
    {
        $template = new OdtTemplate($this->templatePath());
        $experiences = $template->section('ExperienceEntry')->instantiateMany([
            ['note' => 'A', 'position' => 'Rolle A'],
            ['note' => 'B', 'position' => 'Rolle B'],
        ]);

        $experiences[0]->section('ActivityEntry')->instantiateMany([
            ['activity' => 'A1'], ['activity' => 'A2'], ['activity' => 'A3'],
        ]);
        $experiences[1]->section('ActivityEntry')->instantiateMany([
            ['activity' => 'B1'],
        ]);

        self::assertStringContainsString('A1', $experiences[0]->text());
        self::assertStringContainsString('A3', $experiences[0]->text());
        self::assertStringNotContainsString('B1', $experiences[0]->text());
        self::assertStringContainsString('B1', $experiences[1]->text());
        self::assertStringNotContainsString('{{activity_1}}', $experiences[0]->text());
        self::assertStringNotContainsString('{{activity_2}}', $experiences[1]->text());
        $this->expectException(TargetNotFoundException::class);
        $experiences[0]->section('ActivityEntry');
    }

    public function testNestedEmptyCollectionRemovesOnlyLocalPrototype(): void
    {
        $template = new OdtTemplate($this->templatePath());
        $experience = $template->section('ExperienceEntry')->instantiate(['note' => 'A', 'position' => 'Rolle A']);
        $activityPrototype = $experience->section('ActivityEntry');
        self::assertSame([], $activityPrototype->instantiateMany([]));

        self::assertStringContainsString('Rolle A', $experience->text());
        self::assertStringNotContainsString('{{activity_1}}', $experience->text());
        $this->expectException(TargetNotFoundException::class);
        $activityPrototype->descriptor();
    }

    public function testSixByNestedThreeOneFourZeroTwoFiveCollection(): void
    {
        $template = new OdtTemplate($this->templatePath());
        $activityCounts = [3, 1, 4, 0, 2, 5];
        $items = array_map(
            static fn (int $index): array => ['note' => 'N' . $index, 'position' => 'P' . $index],
            range(1, 6)
        );
        $experiences = $template->section('ExperienceEntry')->instantiateMany($items);

        foreach ($activityCounts as $index => $count) {
            $activities = [];
            for ($activity = 1; $activity <= $count; $activity++) {
                $activities[] = ['activity' => sprintf('E%d-A%d', $index + 1, $activity)];
            }
            $experiences[$index]->section('ActivityEntry')->instantiateMany($activities);
        }

        self::assertCount(6, $experiences);
        foreach ($activityCounts as $index => $count) {
            $text = $experiences[$index]->text();
            for ($activity = 1; $activity <= $count; $activity++) {
                self::assertStringContainsString(sprintf('E%d-A%d', $index + 1, $activity), $text);
            }
            self::assertStringNotContainsString('{{activity_' . ($index + 1) . '}}', $text);
            foreach ($activityCounts as $other => $_) {
                if ($other !== $index) {
                    self::assertStringNotContainsString('E' . ($other + 1) . '-A', $text);
                }
            }
        }

        $sections = $template->inspect()->sections();
        self::assertCount(6, array_filter($sections, static fn ($section): bool => str_starts_with($section->name(), 'ExperienceEntry_')));
        self::assertSame([], $template->inspect()->diagnostics());
    }

    public function testFinalizedCollectionSurvivesSaveAndReopen(): void
    {
        $template = new OdtTemplate($this->templatePath());
        $instances = $template->section('ExperienceEntry')->instantiateMany([
            ['note' => 'N', 'position' => 'Gespeichert'],
        ]);
        $instances[0]->section('ActivityEntry')->instantiateMany([
            ['activity' => 'Gespeicherte Aktivität'],
        ]);
        $output = sys_get_temp_dir() . '/odt-section-collection-' . uniqid('', true) . '.odt';
        $this->outputs[] = $output;
        $template->save($output);

        $reopened = new OdtTemplate($output);
        self::assertStringContainsString('Gespeichert', $reopened->section('ExperienceEntry_1')->text());
        self::assertStringContainsString('Gespeicherte Aktivität', $reopened->section('ExperienceEntry_1')->text());
        self::assertSame([], array_filter(
            $reopened->inspect()->sections(),
            static fn ($section): bool => in_array($section->name(), ['ExperienceEntry', 'ActivityEntry'], true)
        ));
    }

    private function templatePath(): string
    {
        return dirname(__DIR__, 2) . '/samples/templates/sample_25_sectionClone.odt';
    }
}

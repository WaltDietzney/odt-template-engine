<?php

declare(strict_types=1);

namespace OdtTemplateEngine\Tests\Integration;

use OdtTemplateEngine\Document\SectionInstantiationException;
use OdtTemplateEngine\OdtTemplate;
use PHPUnit\Framework\TestCase;

final class NestedSectionInstantiationTest extends TestCase
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

    public function testNestedPrototypeIsResolvedLocallyAndInstancesKeepCallerOrder(): void
    {
        $template = new OdtTemplate($this->templatePath());
        $experience = $template->section('ExperienceEntry')->instantiate([
            'note' => 'Aktuelle Position',
            'position' => 'Senior Projektmanager',
        ]);

        $activityPrototype = $experience->section('ActivityEntry');
        self::assertSame('ActivityEntry_1', $activityPrototype->name());
        $first = $activityPrototype->instantiate(['activity' => 'Aktivität A']);
        $second = $activityPrototype->instantiate(['activity' => 'Aktivität B']);

        self::assertSame('ActivityEntry_1_1', $first->name());
        self::assertSame('ActivityEntry_1_2', $second->name());
        self::assertStringContainsString('{{activity_1}}', $activityPrototype->text());
        self::assertStringContainsString('Aktivität A', $first->text());
        self::assertStringContainsString('Aktivität B', $second->text());
        self::assertLessThan(
            strpos($experience->text(), 'Aktivität B'),
            strpos($experience->text(), 'Aktivität A')
        );
    }

    public function testNestedCloneFamiliesAreIndependentAcrossOuterInstances(): void
    {
        $template = new OdtTemplate($this->templatePath());
        $one = $template->section('ExperienceEntry')->instantiate(['note' => 'Eins', 'position' => 'Rolle eins']);
        $two = $template->section('ExperienceEntry')->instantiate(['note' => 'Zwei', 'position' => 'Rolle zwei']);

        $onePrototype = $one->section('ActivityEntry');
        $twoPrototype = $two->section('ActivityEntry');
        self::assertSame('ActivityEntry_1', $onePrototype->name());
        self::assertSame('ActivityEntry_2', $twoPrototype->name());
        self::assertSame('ActivityEntry_1_1', $onePrototype->instantiate(['activity' => 'Nur eins'])->name());
        self::assertSame('ActivityEntry_2_1', $twoPrototype->instantiate(['activity' => 'Nur zwei'])->name());
        self::assertStringContainsString('Nur eins', $one->text());
        self::assertStringNotContainsString('Nur zwei', $one->text());
        self::assertStringContainsString('Nur zwei', $two->text());
        self::assertStringNotContainsString('Nur eins', $two->text());
    }

    public function testThreeByThreeTwoFourHierarchicalInstantiationIsScopedAndAddressable(): void
    {
        $template = new OdtTemplate($this->templatePath());
        $counts = [3, 2, 4];
        $experiences = [];
        foreach ($counts as $outerIndex => $count) {
            $experience = $template->section('ExperienceEntry')->instantiate([
                'note' => 'Position ' . ($outerIndex + 1),
                'position' => 'Rolle ' . ($outerIndex + 1),
            ]);
            $prototype = $experience->section('ActivityEntry');
            for ($activityIndex = 1; $activityIndex <= $count; $activityIndex++) {
                $prototype->instantiate(['activity' => sprintf('E%d-A%d', $outerIndex + 1, $activityIndex)]);
            }
            $experiences[] = $experience;
        }

        self::assertSame(['ExperienceEntry_1', 'ExperienceEntry_2', 'ExperienceEntry_3'], array_map(
            static fn ($target): string => $target->name(),
            $experiences
        ));
        foreach ($counts as $outerIndex => $count) {
            $experience = $experiences[$outerIndex];
            $prototype = $experience->section('ActivityEntry');
            self::assertSame('ActivityEntry_' . ($outerIndex + 1), $prototype->name());
            for ($activityIndex = 1; $activityIndex <= $count; $activityIndex++) {
                self::assertStringContainsString(sprintf('E%d-A%d', $outerIndex + 1, $activityIndex), $experience->text());
            }
            foreach ($experiences as $otherIndex => $other) {
                if ($otherIndex !== $outerIndex) {
                    self::assertStringNotContainsString('E' . ($otherIndex + 1) . '-A', $experience->text());
                }
            }
        }
    }

    public function testNestedMissingValueFailsWithoutInsertingAClone(): void
    {
        $template = new OdtTemplate($this->templatePath());
        $experience = $template->section('ExperienceEntry')->instantiate(['note' => 'N', 'position' => 'P']);
        $prototype = $experience->section('ActivityEntry');
        $before = $template->inspect()->toArray();

        try {
            $prototype->instantiate([]);
            self::fail('Expected missing nested activity to fail.');
        } catch (SectionInstantiationException $exception) {
            self::assertSame('missing required value', $exception->reason());
            self::assertSame('activity', $exception->variableName());
        }

        self::assertSame($before, $template->inspect()->toArray());
        self::assertStringContainsString('{{activity_1}}', $prototype->text());
    }

    public function testNestedResolutionAndValuesSurviveSaveAndReopen(): void
    {
        $template = new OdtTemplate($this->templatePath());
        $experience = $template->section('ExperienceEntry')->instantiate(['note' => 'N', 'position' => 'P']);
        $prototype = $experience->section('ActivityEntry');
        $prototype->instantiate(['activity' => 'Gespeichert eins']);
        $prototype->instantiate(['activity' => 'Gespeichert zwei']);
        $output = sys_get_temp_dir() . '/odt-nested-section-' . uniqid('', true) . '.odt';
        $this->outputs[] = $output;
        $template->save($output);

        $reopened = new OdtTemplate($output);
        $reopenedExperience = $reopened->section('ExperienceEntry_1');
        self::assertStringContainsString('Gespeichert eins', $reopenedExperience->text());
        self::assertSame('ActivityEntry_1', $reopenedExperience->section('ActivityEntry')->name());
        self::assertStringContainsString('{{activity_1}}', $reopenedExperience->section('ActivityEntry')->text());
        self::assertStringContainsString('Gespeichert zwei', $reopenedExperience->text());
    }

    private function templatePath(): string
    {
        return dirname(__DIR__, 2) . '/samples/templates/sample_25_sectionClone.odt';
    }
}

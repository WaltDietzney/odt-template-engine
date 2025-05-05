<?php

namespace OdtTemplateEngine\Elements;

use OdtTemplateEngine\Elements\Paragraph;
use OdtTemplateEngine\Elements\RichText;

class TableExampleGenerator
{
    private array $summaryKeywords = ['summe', 'total', 'gesamt'];

    public function createStatusBadge(string $status): Paragraph
    {
        $paragraph = new Paragraph();
        $color = match (strtolower($status)) {
            'in arbeit' => '#ffcc00',
            'abgeschlossen' => '#00cc66',
            'geplant' => '#3399ff',
            default => '#cccccc',
        };


        $paragraph->addText($status, [
            'background-color' => $color,
            'font-weight' => 'bold',
            'padding' => '2px 4px',
            'border-radius' => '4px',
        ]);
        return $paragraph;
    }

    public function createCourseParagraph(string $title, string $subtitle): Paragraph
    {
        $paragraph = new Paragraph();

        $paragraph->addText($title, ['font-weight' => 'bold']);
        $paragraph->addText(' - ' . $subtitle, ['font-style' => 'italic', 'font-size' => 'smaller']);

        return $paragraph;
    }

    public function generateSimpleTable(): array
    {
        return [
            ['Position', 'Beschreibung', 'Betrag'],
            [1, 'Einnahmen', '1000 €'],
            [2, 'Ausgaben', '600 €'],
            [3, 'Miete', '300 €'],
            [4, 'Sonstiges', '100 €'],
            ['Summe', '', '0 €'],
        ];
    }

    public function generateCourseTable(): array
    {
        return [
            ['Kurs', 'Dauer', 'Status'],
            [
                $this->createCourseParagraph('PHP Grundlagen', 'Einsteigerkurs'),
                '10 Wochen',
                $this->createStatusBadge('in Arbeit'),
            ],
            [
                $this->createCourseParagraph('Objektorientiertes PHP', 'Aufbaukurs'),
                '8 Wochen',
                $this->createStatusBadge('abgeschlossen'),
            ],
            [
                $this->createCourseParagraph('Symfony Framework', 'Fortgeschrittene'),
                '12 Wochen',
                $this->createStatusBadge('geplant'),
            ],
        ];
    }

    public function generateFinancialSummary(): array
    {
        return [
            ['Kategorie', 'Monat', 'Betrag'],
            ['Umsatz', 'April', '5000 €'],
            ['Kosten', 'April', '2000 €'],
            ['Gewinn', 'April', '3000 €'],
            ['Gesamt', '', '3000 €'],
        ];
    }
}

<?php

declare(strict_types=1);

require_once dirname(__DIR__) . '/vendor/autoload.php';

use OdtTemplateEngine\Elements\ImageElement;
use OdtTemplateEngine\Elements\Paragraph;
use OdtTemplateEngine\Elements\RichTable;
use OdtTemplateEngine\Elements\RichTableCell;
use OdtTemplateEngine\Elements\RichText;
use OdtTemplateEngine\OdtTemplate;

$template = new OdtTemplate(__DIR__ . '/templates/invoice-richtext-prototype.odt');

$orange = '#e17a19';
$ink = '#1f252b';
$muted = '#6f777d';
$lightGray = '#f1f1ef';

$left = new RichText();

$logoParagraph = new Paragraph('InvoiceBrand', [
    'margin-top' => '0cm',
    'margin-bottom' => '0cm',
]);
$logoParagraph->addElement(new ImageElement(__DIR__ . '/assets/northstar-mark.png', [
    'width' => '5.0cm',
    'anchor' => 'as-char',
]));
$left->addParagraph($logoParagraph);

$left->addParagraph(
    (new Paragraph('InvoiceTagline', [
        'margin-top' => '0cm',
        'margin-bottom' => '0cm',
    ]))->addText('Creative & Digital Services', [
        'color' => $muted,
        'font-size' => '9pt',
    ])
);

$left->addParagraphBreak(2);
$left->addParagraph(
    (new Paragraph('InvoiceBillToHeading', [
        'margin-top' => '0cm',
        'margin-bottom' => '0cm',
    ]))->addText('BILL TO', [
        'bold' => true,
        'color' => $orange,
        'font-size' => '8pt',
    ])
);
$left->addParagraph(
    (new Paragraph('InvoiceBillToName', [
        'margin-top' => '0cm',
        'margin-bottom' => '0cm',
    ]))->addText('Hamid Group', [
        'bold' => true,
        'color' => $ink,
        'font-size' => '10pt',
    ])
);
$left->addParagraph(
    (new Paragraph('InvoiceBillToAddress', [
        'margin-top' => '0cm',
        'margin-bottom' => '0cm',
    ]))->addText('123 Short Name Town/City', [
        'color' => $muted,
        'font-size' => '8pt',
    ])->addLineBreak()->addText('State, County 556', [
        'color' => $muted,
        'font-size' => '8pt',
    ])->addLineBreak()->addText('P: 00 999 123 456 789', [
        'color' => $muted,
        'font-size' => '8pt',
    ])->addLineBreak()->addText('M: info@hamidgroup.example', [
        'color' => $muted,
        'font-size' => '8pt',
    ])
);

$contactLine = static function (string $label, string $value) use ($orange, $muted): Paragraph {
    return (new Paragraph('InvoiceContact', [
        'text-align' => 'right',
        'margin-top' => '0cm',
        'margin-bottom' => '0cm',
    ]))
        ->addText($label . '  ', [
            'bold' => true,
            'color' => $orange,
            'font-size' => '7pt',
        ])
        ->addText($value, [
            'color' => $muted,
            'font-size' => '8pt',
        ]);
};

$right = new RichText();
$right->addParagraph($contactLine('LOC', '555 Market Street · Sydney NSW 2000'));
$right->addParagraph($contactLine('TEL', '+61 2 9999 4567'));
$right->addParagraph($contactLine('MAIL', 'info@northstar.example'));
$right->addParagraph($contactLine('WEB', 'www.northstar.example'));

$right->addParagraphBreak(2);
$right->addParagraph(
    (new Paragraph('InvoiceTitle', [
        'text-align' => 'right',
        'margin-top' => '0cm',
        'margin-bottom' => '0cm',
    ]))->addText('INVOICE', [
        'bold' => true,
        'color' => $ink,
        'font-size' => '25pt',
    ])
);

$metadata = new Paragraph('InvoiceMeta', [
    'background-color' => $lightGray,
    'border-left' => '0.12cm solid ' . $orange,
    'padding-left' => '0.28cm',
    'padding-right' => '0.28cm',
    'padding-top' => '0.16cm',
    'padding-bottom' => '0.16cm',
    'tab-stops' => [
        ['position' => 2.6, 'alignment' => 'left'],
    ],
]);
$metadata
    ->addText('Invoice No.', [
        'bold' => true,
        'color' => $ink,
        'font-size' => '8pt',
    ])
    ->addTab()
    ->addText('INV-2026-0142', [
        'color' => $ink,
        'font-size' => '8pt',
    ])
    ->addLineBreak()
    ->addText('Date', [
        'bold' => true,
        'color' => $ink,
        'font-size' => '8pt',
    ])
    ->addTab()
    ->addText('23 September 2026', [
        'color' => $ink,
        'font-size' => '8pt',
    ]);
$right->addParagraph($metadata);

$table = (new RichTable())->setTableStyle(['relative-width' => '100%']);
$table->setColumnWidthRatios([55, 45]);
$table->addRow([
    new RichTableCell($left, [
        'border' => 'none',
        'padding' => '0cm',
    ]),
    new RichTableCell($right, [
        'border' => 'none',
        'padding' => '0cm',
    ]),
]);

$tabStops = [
    ['position' => 10.2, 'alignment' => 'right'],
    ['position' => 13.1, 'alignment' => 'center'],
    ['position' => 17.0, 'alignment' => 'right'],
];

$body = new RichText();

// This salutation is the prototype for the later Writer-authored conditional
// salutation Sections in the final S02 template.
$body->addParagraph(
    (new Paragraph('InvoiceSalutation', [
        'margin-top' => '0.35cm',
        'margin-bottom' => '0.12cm',
    ]))->addText('Dear Mr Hamid,', [
        'bold' => true,
        'color' => $ink,
        'font-size' => '10pt',
    ])
);

$body->addParagraph(
    (new Paragraph('InvoiceBodyText', [
        'margin-top' => '0cm',
        'margin-bottom' => '0.35cm',
        'line-height' => '115%',
    ]))->addText(
        'Thank you for choosing Northstar Studio. Please find below the services provided for the current project.',
        [
            'color' => $muted,
            'font-size' => '9pt',
        ]
    )
);

$body->addParagraph(
    (new Paragraph('InvoiceItemsHeader', [
        'background-color' => $orange,
        'padding-left' => '0.18cm',
        'padding-right' => '0.18cm',
        'padding-top' => '0.14cm',
        'padding-bottom' => '0.14cm',
        'margin-top' => '0cm',
        'margin-bottom' => '0.16cm',
        'tab-stops' => $tabStops,
    ]))
        ->addText('ITEM DESCRIPTION', [
            'bold' => true,
            'color' => '#ffffff',
            'font-size' => '8pt',
        ])
        ->addTab()
        ->addText('PRICE', [
            'bold' => true,
            'color' => '#ffffff',
            'font-size' => '8pt',
        ])
        ->addTab()
        ->addText('QTY', [
            'bold' => true,
            'color' => '#ffffff',
            'font-size' => '8pt',
        ])
        ->addTab()
        ->addText('TOTAL', [
            'bold' => true,
            'color' => '#ffffff',
            'font-size' => '8pt',
        ])
);

$markerStyle = [
    'margin-top' => '0.02cm',
    'margin-bottom' => '0.02cm',
    'font-size' => '6pt',
    'color' => '#a3a3a3',
];

$body->addParagraph(
    (new Paragraph('InvoiceTemplateMarker', $markerStyle))->addText('{{#foreach:items}}')
);

$body->addParagraph(
    (new Paragraph('InvoiceItem', [
        'margin-top' => '0.12cm',
        'margin-bottom' => '0.04cm',
        'tab-stops' => $tabStops,
    ]))
        ->addText('{{name}}', [
            'bold' => true,
            'color' => $ink,
            'font-size' => '9pt',
        ])
        ->addTab()
        ->addText('{{price}}', [
            'color' => $ink,
            'font-size' => '9pt',
        ])
        ->addTab()
        ->addText('{{quantity}}', [
            'color' => $ink,
            'font-size' => '9pt',
        ])
        ->addTab()
        ->addText('{{total}}', [
            'bold' => true,
            'color' => $ink,
            'font-size' => '9pt',
        ])
);

$body->addParagraph(
    (new Paragraph('InvoiceItemDescription', [
        'margin-left' => '0.18cm',
        'margin-top' => '0cm',
        'margin-bottom' => '0.08cm',
        'line-height' => '110%',
    ]))->addText('{{description}}', [
        'color' => $muted,
        'font-size' => '8pt',
    ])
);

$body->addParagraph(
    (new Paragraph('InvoiceTemplateMarker', $markerStyle))->addText('{{#endforeach}}')
);

$lowerLeft = new RichText();
$lowerLeft->addParagraph(
    (new Paragraph('InvoicePaymentHeading', [
        'margin-top' => '0.35cm',
        'margin-bottom' => '0.12cm',
    ]))->addText('PAYMENT METHOD', [
        'bold' => true,
        'color' => $orange,
        'font-size' => '8pt',
    ])
);
$lowerLeft->addParagraph(
    (new Paragraph('InvoicePaymentText', [
        'margin-top' => '0cm',
        'margin-bottom' => '0.24cm',
        'line-height' => '112%',
    ]))->addText('Bank Account', [
        'bold' => true,
        'color' => $ink,
        'font-size' => '8pt',
    ])->addLineBreak()->addText('Bank Full Name', [
        'color' => $muted,
        'font-size' => '8pt',
    ])->addLineBreak()->addText('Bank Code: 110-245', [
        'color' => $muted,
        'font-size' => '8pt',
    ])->addLineBreak(2)->addText('PayPal', [
        'bold' => true,
        'color' => $ink,
        'font-size' => '8pt',
    ])->addLineBreak()->addText('info@northstar.example', [
        'color' => $muted,
        'font-size' => '8pt',
    ])
);
$lowerRight = new RichText();
$footerTabStops = [
    ['position' => 5.7, 'alignment' => 'right'],
];
$lowerRight->addParagraph(
    (new Paragraph('InvoiceSummary', [
        'margin-top' => '0.35cm',
        'margin-bottom' => '0.08cm',
        'tab-stops' => $footerTabStops,
    ]))
        ->addText('Sub Total', [
            'color' => $muted,
            'font-size' => '8pt',
        ])
        ->addTab()
        ->addText('{{subtotal}}', [
            'color' => $ink,
            'font-size' => '8pt',
        ])
        ->addLineBreak()
        ->addText('Tax Vat 18%', [
            'color' => $muted,
            'font-size' => '8pt',
        ])
        ->addTab()
        ->addText('{{tax}}', [
            'color' => $ink,
            'font-size' => '8pt',
        ])
);
$lowerRight->addParagraph(
    (new Paragraph('InvoiceGrandTotal', [
        'background-color' => $orange,
        'padding-left' => '0.18cm',
        'padding-right' => '0.18cm',
        'padding-top' => '0.14cm',
        'padding-bottom' => '0.14cm',
        'margin-top' => '0.08cm',
        'margin-bottom' => '0.28cm',
        'tab-stops' => $footerTabStops,
    ]))
        ->addText('GRAND TOTAL', [
            'bold' => true,
            'color' => '#ffffff',
            'font-size' => '8pt',
        ])
        ->addTab()
        ->addText('{{total}}', [
            'bold' => true,
            'color' => '#ffffff',
            'font-size' => '9pt',
        ])
);
$lowerRight->addParagraph(
    (new Paragraph('InvoiceSignature', [
        'text-align' => 'right',
        'margin-top' => '0.08cm',
        'margin-bottom' => '0.05cm',
    ]))->addText('/ / / / /', [
        'italic' => true,
        'color' => $ink,
        'font-size' => '11pt',
    ])
);
$lowerRight->addParagraph(
    (new Paragraph('InvoiceSignatureRole', [
        'text-align' => 'right',
        'margin-top' => '0cm',
        'margin-bottom' => '0.03cm',
    ]))->addText('Account Manager', [
        'color' => $muted,
        'font-size' => '7pt',
    ])
);
$lowerRight->addParagraph(
    (new Paragraph('InvoiceSignatureName', [
        'text-align' => 'right',
        'margin-top' => '0cm',
        'margin-bottom' => '0cm',
    ]))->addText('JONATHON DEO', [
        'bold' => true,
        'color' => $ink,
        'font-size' => '8pt',
    ])
);

$footer = new RichText();
$footer->addParagraph(
    (new Paragraph('InvoiceFooter', [
        'margin-top' => '0.3cm',
        'margin-bottom' => '0.08cm',
    ]))->addText('THANK YOU FOR YOUR BUSINESS', [
        'bold' => true,
        'color' => $ink,
        'font-size' => '8pt',
    ])
);
$footer->addParagraph(
    (new Paragraph('InvoiceFooterTerms', [
        'margin-top' => '0cm',
        'margin-bottom' => '0cm',
        'line-height' => '110%',
    ]))->addText('Terms: Payment is due within 14 days. Thank you for your continued partnership.', [
        'color' => $muted,
        'font-size' => '7pt',
    ])
);

$lowerTable = (new RichTable())->setTableStyle(['relative-width' => '100%']);
$lowerTable->setColumnWidthRatios([55, 45]);
$lowerTable->addRow([
    new RichTableCell($lowerLeft, [
        'border' => 'none',
        'padding' => '0cm',
    ]),
    new RichTableCell($lowerRight, [
        'border' => 'none',
        'padding' => '0cm',
    ]),
]);
$body->addTable($lowerTable);

$template->setElement('invoice_body', $body);
$template->setElement('invoice_header', $table);
$template->setElement('invoice_footer', $footer);
$template->save(__DIR__ . '/output/output_B01_invoice_template_builder.odt');

echo "Invoice RichText prototype generated successfully.\n";

# Native Section Authoring (H01)

**Recommended · PHP-owned Structured Content**

H01 uses the existing Section authoring and collection APIs. It did **not**
introduce a separate H01 class or a new declarative `foreach` language.

Create a named Section, add content, and optionally nest another Section:

```php
use OdtTemplateEngine\Elements\Paragraph;
use OdtTemplateEngine\Elements\SectionElement;
use OdtTemplateEngine\OdtTemplate;

$template = new OdtTemplate('blank.odt');
$stations = (new SectionElement('Stations'))
    ->addElement((new Paragraph())->addText('Experience'))
    ->addSection(
        (new SectionElement('Station'))
            ->addElement((new Paragraph())->addText('Project manager'))
    );

$template->appendSection($stations);
$template->save('sections.odt');
```

The public entry points are `OdtTemplate::appendBody()`,
`OdtTemplate::appendSection()`, `SectionElement::addElement()`,
`SectionElement::addSection()`, `SectionElement::setSectionStyle()`,
and `$template->styles()->defineSection()`. Use
[Named Sections](../../rich-documents/named-sections.md#create-native-sections-in-php)
for exact signatures, lifecycle, nested Sections and style options.

For repeated Writer-owned station prototypes, use the existing
[SectionTarget API](../writer-native/sections.md), including its bounded
`instantiate()` / `instantiateMany()` semantics. This is not the same
operation as appending a new PHP-owned Section.

H01 verified native nested Sections, document-local section styles,
multi-column layouts, Writer round-trips and the 0/1/n collection lifecycle.
Column balancing, pagination and visual layout remain Writer responsibilities.
See the [H01 evidence](../../architecture/H01_SECTION_AUTHORING_CLOSEOUT.md)
for test boundaries. No H01-specific convenience mapping or automatic
template-generation API is provided.

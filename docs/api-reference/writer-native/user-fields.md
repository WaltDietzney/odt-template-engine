# User Fields

Writer User Fields are native Writer data declarations that can be referenced in document content. The engine supports direct binding of the bounded **string User Field** contract through `setUserField()`.

## `setUserField()`

**Recommended · Writer-native binding**

```php
public function setUserField(string $name, string $value): void
```

```php
$template->setUserField('CustomerName', 'Maria Muster');
$template->save('output.odt');
```

The operation mutates the supported declaration values in the current Working Document immediately. It does not require `render()`.

The original authored source inspected by `inspectTemplate()` remains unchanged.

## Supported source regions

Binding analyzes:

- the Writer document body in `content.xml`;
- direct header/header-* and footer/footer-* content under Writer master pages in `styles.xml`.

A logical User Field can therefore have supported declarations across these source regions.

The binder updates the authoritative declarations for the logical field after the complete field has passed validation.

## Supported field type

The 1.0 direct binding contract supports Writer User Field declarations with:

```text
office:value-type="string"
```

Other Writer User Field value types are inspectable evidence but are not supported by `setUserField()`.

The supplied PHP value is already typed as `string`; the binder writes it to `office:string-value`.

## Validation before mutation

The complete logical field is analyzed before the first declaration is changed.

Binding fails when:

- the requested name is empty;
- the field does not exist;
- a reference has no authoritative declaration;
- the declaration type is unsupported;
- declarations have conflicting value types across source regions;
- declarations have conflicting values across source regions;
- conflicting duplicate declarations occur inside one source region;
- the analyzed field is otherwise malformed or ambiguous.

Compatible duplicate declarations across supported regions are updated together.

## Failure contract

Failures throw `UserFieldBindingException`.

```php
UserFieldBindingException::NOT_FOUND
UserFieldBindingException::UNSUPPORTED_TYPE
UserFieldBindingException::MALFORMED
UserFieldBindingException::AMBIGUOUS

fieldName(): string
reason(): string
```

Example:

```php
use OdtTemplateEngine\Template\UserFieldBindingException;

try {
    $template->setUserField('CustomerName', 'Maria Muster');
} catch (UserFieldBindingException $exception) {
    echo $exception->fieldName();
    echo $exception->reason();
}
```

Validation completes before the first declaration mutation, so a rejected binding does not intentionally leave a subset of declarations updated.

## Declarations and displayed references

`setUserField()` updates the supported Writer User Field **declarations**. It does not replace each `text:user-field-get` reference with literal text.

The references remain native Writer User Field references. This preserves the Writer-owned field model instead of flattening it into PHP-owned text.

## Relation to `inspectTemplate()`

`inspectTemplate()` analyzes User Field evidence as part of the authored semantic contract. That contract can classify a field as supported, unsupported, malformed, or ambiguous.

`setUserField()` performs its own validation against the **current Working Document** before mutation. A previously inspected source contract is therefore not a substitute for the binder's current-state validation.

Mapping & Automation can consume the source contract and route READY dependencies through this same public binding operation.

## Lifecycle

Binding mutates the current Working Document immediately:

```php
$template->setUserField('CustomerName', 'Maria Muster');
$template->save('output.odt');
```

No classic `render()` call is required solely for User Field binding.

Calling `load()` resets the Working Document to the original template state.

## Ownership

Writer owns the User Field declarations and references. PHP supplies a supported string value while preserving that native field structure.

This differs from classic visible placeholders, where PHP renders template-language expressions into document text.

## See also

- [Inspection](inspection.md)
- [Writer-native Objects](index.md)
- Mapping & Automation

# SR-06C.3 — ImageElement Semantic Graphic Producer

Status: implementation slice

Branch: `architecture/sr-06c3-image-element-semantic-producer`

Depends on:

- `SR-06C1_GRAPHIC_PRODUCER_SEMANTICS_CONTRACT.md`
- SR-06B graphic/drawing boundary characterization
- SR-06C.2 semantic graphic producer transition

## 1. Purpose

SR-06C.3 applies the approved semantic graphic producer contract to `ImageElement`.

The result is intentionally conservative: the currently supported normal-image API does not expose an independently classified graphic appearance definition after drawing structure, geometry, placement, convenience input, and resource concerns are removed.

Therefore `ImageElement` owns no semantic `graphic` `StyleRequirement` in this slice.

This is a semantic result, not an implementation omission.

## 2. Current legacy image-style boundary

`ImageElement::setStyle()` passes public image options through `StyleMapper::mapImageStyleOptions()` and derives the historical generated style name from that mapped array.

The current mapped image options include values such as:

- `svg:width`;
- `svg:height`;
- `style:wrap`;
- `text:anchor-type`;
- horizontal position/relation;
- vertical position/relation;
- the convenience value `align`.

SR-06B characterization already established that width, height, anchor, wrap, and placement values participate in current legacy image-style identity, while `align` is resolved later during DOM materialization.

Those facts characterize legacy behavior but do not define future semantic graphic ownership.

## 3. Semantic projection

Under the SR-06C.1 contract, the currently supported normal-image concerns classify as follows:

| Current ImageElement concern | SR-06C semantic ownership |
| --- | --- |
| width / height | drawing-object geometry |
| anchor | drawing structure / placement |
| wrap | layout / flow, excluded from current semantic graphic identity |
| horizontal position/relation | object placement |
| vertical position/relation | object placement |
| x / y | geometry / placement |
| align | convenience input |
| image href | resource reference |
| physical image file | package resource |

No independently classified appearance property remains.

Consequently, `ImageElement` must not synthesize a semantic graphic definition merely to mirror the legacy image-style registry.

## 4. Producer implementation

No production override is added to `ImageElement::getOwnStyleRequirements()`.

`OdtElement` already provides the correct semantic result for an element with no owned style requirement:

```php
public function getOwnStyleRequirements(): iterable
{
    return [];
}
```

Adding an `ImageElement` override that only returned the same empty array would duplicate framework behavior without adding semantics. SR-06C.3 therefore makes the null producer contract executable through dedicated tests rather than adding redundant production code.

This decision also keeps future appearance support explicit: when `ImageElement` gains a genuinely classified graphic appearance concern, that future change must introduce a real producer projection instead of accidentally inheriting legacy mapper identity.

## 5. Legacy compatibility

SR-06C.3 does not change the legacy image-style path.

The following remain unchanged:

- `StyleMapper::mapImageStyleOptions()`;
- `ImageElement::setStyle()`;
- legacy generated image style names;
- `getImageStyleRequirements()`;
- `getOwnImageStyleRequirements()`;
- `draw:style-name` generation/reference behavior;
- `toDomNode()` alignment resolution;
- the existing mutation of resolved placement values into `imageOptions`;
- image package resource handling.

Different geometry or placement values may therefore continue to create different legacy style identities even though they create no semantic graphic style identity.

## 6. Alignment lifecycle

`ImageElement::toDomNode()` currently resolves convenience `align` values into concrete wrapping and placement attributes and writes some of those resolved values back into `imageOptions`.

SR-06C.3 intentionally preserves that behavior.

The semantic producer result must remain empty both before and after this mutation. DOM-materialization state must not become an accidental semantic style input.

Changing this lifecycle belongs outside SR-06C.3.

## 7. Tests

`tests/Integration/ImageElementSemanticGraphicProducerTest.php` proves:

1. the complete currently supported placement/geometry/convenience option set produces no owned semantic graphic requirement;
2. legacy image-style identities remain available and may differ for geometry changes while semantic requirements remain empty;
3. alignment resolution may mutate legacy/materialization state without creating a semantic graphic requirement.

Existing SR-06B characterization remains the authority for the historical mixed image-style behavior.

## 8. Non-goals

SR-06C.3 does not:

- add a semantic graphic definition for normal images;
- change `StyleMapper`;
- change legacy image style generation;
- change DOM serialization or placement;
- change alignment resolution or state mutation;
- change resource collection;
- materialize semantic graphic styles;
- introduce fill-image dependencies;
- modify `CircularImageElement`;
- remove legacy image style registries or compatibility APIs.

## 9. Exit condition

SR-06C.3 is complete when:

1. the null semantic producer result is explicitly documented;
2. dedicated tests prove that current normal-image options do not create semantic graphic requirements;
3. legacy image-style behavior remains unchanged;
4. SR-06B characterization remains passing;
5. the full test suite remains green apart from previously known unrelated deprecations.

The next producer slice is SR-06C.4 for `CircularImageElement`, where a real semantic graphic definition is expected because bitmap fill and stroke semantics remain after geometry, placement, resource, and dependency concerns are separated.

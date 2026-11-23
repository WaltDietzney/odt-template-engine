# H03 Slice 2 — Rounded rectangle reference characterization (2026-11-23)

Reference: installed LibreOffice presentation template `/usr/lib/libreoffice/share/template/common/presnt/Portfolio.otp`, `styles.xml`, `draw:type=round-rectangle`. Native enhanced geometry contains enhanced-path, equations, handle, modifiers, text areas and stretch points. This is LibreOffice-supplied **Impress** evidence, not a Writer-authored GUI reference.

The exact reference default `draw:modifiers=10800` renders as a circle in a 4 cm square Engine shape. The H03 preset intentionally chooses `draw:modifiers=3600` while preserving the reference equations/handle, producing a visually rounded rectangle (confirmed with `tools/visual-regression/render.sh`). This difference from the shipped reference is explicit and requires manual Writer GUI editing verification. The initial Engine-generated round-rectangle with the original 10800 modifier was also rendered as a control.

Focused `H03NativeShapeTest` passed (9 tests, 43 assertions). Writer ODT roundtrip was performed with the 10800 default before modifier adjustment; the **3600 version still needs a new roundtrip**. No full merge or GUI editability claim.

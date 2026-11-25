# Verification boundaries

The initial comparison attempted to compare a Writer-roundtripped shape tree to
its source XML. Writer legitimately renames styles and rounds 12cm to 12.001cm.
The correct unchanged-geometry control compares source to accepted source and
Writer output to accepted Writer output, while independently checking pixels.
No package or shape was changed to make this check pass.

Visible-title checks compare complete extracted lines, rather than substring
counts: “Ebene 1” is also a substring of “Ebene 10”. PDF bookmark checks separately
compare the full ordered title list. These were verifier corrections, not engine
rendering failures. No accepted source or visual evidence was regenerated.

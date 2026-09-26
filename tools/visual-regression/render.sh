#!/usr/bin/env bash
set -euo pipefail

ROOT_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")/../.." && pwd)"
OUTPUT_DIR="$ROOT_DIR/samples/output"
WORK_DIR="$ROOT_DIR/tmp/visual-regression"
PDF_DIR="$WORK_DIR/pdf"
IMAGE_DIR="$WORK_DIR/images"
DPI=150

require_command() {
    if ! command -v "$1" >/dev/null 2>&1; then
        echo "Required command not found: $1" >&2
        exit 1
    fi
}

require_command libreoffice
require_command pdftoppm

mkdir -p "$PDF_DIR" "$IMAGE_DIR"

if (($# > 0)); then
    odt_files=("$@")
else
    shopt -s nullglob
    odt_files=("$OUTPUT_DIR"/output*.odt)
    shopt -u nullglob
fi

if ((${#odt_files[@]} == 0)); then
    echo "No ODT files found."
    echo "Render samples first or pass one or more .odt files explicitly." >&2
    exit 1
fi

for input in "${odt_files[@]}"; do
    if [[ ! -f "$input" ]]; then
        echo "ODT file not found: $input" >&2
        exit 1
    fi

    if [[ "${input##*.}" != "odt" ]]; then
        echo "Skipping non-ODT file: $input" >&2
        continue
    fi

    input_abs="$(cd "$(dirname "$input")" && pwd)/$(basename "$input")"
    stem="$(basename "$input_abs" .odt)"
    pdf="$PDF_DIR/$stem.pdf"
    image_prefix="$IMAGE_DIR/$stem"

    echo "Rendering: $input_abs"

    # Remove only candidate artifacts for this ODT. Baseline files are never touched.
    rm -f "$pdf"
    rm -f "$image_prefix"-*.png

    libreoffice \
        --headless \
        --convert-to pdf \
        --outdir "$PDF_DIR" \
        "$input_abs" >/dev/null

    if [[ ! -f "$pdf" ]]; then
        echo "LibreOffice did not create the expected PDF: $pdf" >&2
        exit 1
    fi

    pdftoppm \
        -png \
        -r "$DPI" \
        "$pdf" \
        "$image_prefix" >/dev/null

    echo "  PDF:    $pdf"
    echo "  Images: $image_prefix-*.png"
done

echo "Visual regression render complete."

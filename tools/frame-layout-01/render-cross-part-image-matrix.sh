#!/usr/bin/env bash
set -euo pipefail

ROOT_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")/../.." && pwd)"
BASE_FIXTURE="${1:-$ROOT_DIR/research/frame-layout-01-cross-part-base.odt}"
MATRIX_DIR="$ROOT_DIR/tmp/frame-layout-01-cross-part"

cd "$ROOT_DIR"

php tools/frame-layout-01/generate-cross-part-image-fixtures.php "$BASE_FIXTURE"

mapfile -t ODT_FILES < <(find "$MATRIX_DIR" -maxdepth 1 -type f -name 'H*.odt' | sort)

if [[ "${#ODT_FILES[@]}" -eq 0 ]]; then
    echo "No H1-H7 ODT files were generated." >&2
    exit 1
fi

tools/visual-regression/render.sh "${ODT_FILES[@]}"

echo
echo "Rendered H1-H7 via tools/visual-regression/render.sh"
echo "PDFs:   $ROOT_DIR/tmp/visual-regression/pdf/"
echo "Images: $ROOT_DIR/tmp/visual-regression/images/"
echo
echo "Review RESULTS.txt as well:"
echo "$MATRIX_DIR/RESULTS.txt"

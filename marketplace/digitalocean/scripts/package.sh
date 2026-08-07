#!/bin/sh
set -eu

output=${1:?"Lokasi archive wajib diisi"}
script_dir=$(CDPATH= cd -- "$(dirname -- "$0")" && pwd)
repo_dir=$(CDPATH= cd -- "$script_dir/../../.." && pwd)

if ! git -C "$repo_dir" rev-parse --verify HEAD >/dev/null 2>&1; then
    echo "Repository harus memiliki commit sebelum image Marketplace dibangun." >&2
    exit 1
fi

mkdir -p "$(dirname -- "$output")"
git -C "$repo_dir" archive --format=tar.gz --output="$output" HEAD
echo "Build context dibuat dari commit $(git -C "$repo_dir" rev-parse --short HEAD)."


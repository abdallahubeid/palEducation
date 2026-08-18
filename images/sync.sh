#!/usr/bin/env bash
# مزامنة صور الجذر إلى public/ ليخدمها Laravel
# المصدر: /images   ·   الوجهة: /public/images
cd "$(dirname "$0")/.." || exit 1
rm -rf public/images
cp -r images public/images
echo "✅ زُومنت $(find public/images -name '*.jpg' | wc -l) صورة إلى public/images"

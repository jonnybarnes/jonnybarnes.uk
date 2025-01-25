#!/usr/bin/env zsh

if ! (( $+commands[fd] )) &> /dev/null; then
    echo "fd not installed"
    exit 1
fi

if ! (( $+commands[brotli] )) &> /dev/null; then
    echo "brotli not installed"
    exit 1
fi

if ! (( $+commands[zstd] )) &> /dev/null; then
    echo "zstd not installed"
    exit 1
fi

# Make .br files
fd --extension css --extension js --search-path ./public/assets --type f --exec brotli --force --best --output={}.br {}
# Make .zst files
fd --extension css --extension js --search-path ./public/assets --type f --exec zstd --quiet --force --ultra -22 --exclude-compressed {} -o {}.zst

# Remove files that actually got bigger!
fd --extension br --extension zst --search-path ./public/assets --type f --exec sh -c '
for file; do
  src="${file%.br}"
  src="${src%.zst}"
  if [ -f "$src" ]; then
    # Get file sizes using stat with cross-platform compatibility
    if [ "$(uname)" = "Darwin" ]; then
      file_size=$(stat -f%z "$file")
      src_size=$(stat -f%z "$src")
    else
      file_size=$(stat -c%s "$file")
      src_size=$(stat -c%s "$src")
    fi
    # Compare sizes and rm compressed file if larger than the source
    [ "$file_size" -ge "$src_size" ] && rm "$file"
  fi
done
' _ {}

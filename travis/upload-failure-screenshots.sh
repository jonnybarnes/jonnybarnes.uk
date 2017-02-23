#!/bin/bash

for f in tests/Browser/screenshots/*.png
do
    curl -F "upload=@$f" -F "format=txt" http://uploads.im/api
done

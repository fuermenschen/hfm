# ImageMagick Debug Images

These images reproduce Story-image rendering across ImageMagick builds.

## Production Mirror

`Dockerfile` installs pinned Debian Bullseye packages matching production's problematic ImageMagick family:

```sh
docker build -t hfm-imagick-debug:production-mirror docker/debug-imagick
```

Use this image to reproduce black SVG outlines caused by Debian's patched MSVG renderer. Adding root `stroke="none"` removes the artifact.

## Clean Control

`Dockerfile.source-built` builds upstream ImageMagick `6.9.11-60` with PHP 8.4 and Imagick 3.8.1. Use it to compare clean source-built rendering against Debian's packaged build.

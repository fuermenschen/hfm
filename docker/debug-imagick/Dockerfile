FROM debian:bullseye-slim

ARG IMAGEMAGICK_PACKAGE_VERSION=8:6.9.11.60+dfsg-1.3+deb11u16
ARG PHP_IMAGICK_PACKAGE_VERSION=3.4.4+php8.0+3.4.4-2+deb11u2

RUN apt-get update && apt-get install -y --no-install-recommends \
        imagemagick=${IMAGEMAGICK_PACKAGE_VERSION} \
        libmagickcore-6.q16-6-extra=${IMAGEMAGICK_PACKAGE_VERSION} \
        php-cli \
        php-imagick=${PHP_IMAGICK_PACKAGE_VERSION} \
        librsvg2-bin \
    && rm -rf /var/lib/apt/lists/*

RUN test "$(dpkg-query -W -f='${Version}' imagemagick)" = "${IMAGEMAGICK_PACKAGE_VERSION}" \
    && test "$(dpkg-query -W -f='${Version}' php-imagick)" = "${PHP_IMAGICK_PACKAGE_VERSION}" \
    && convert -version \
    && php -r ' \
        if (PHP_VERSION_ID < 70400 || PHP_VERSION_ID >= 70500) exit(1); \
        if (phpversion("imagick") !== "3.4.4") exit(2); \
        $v = Imagick::getVersion()["versionString"]; \
        if (strpos($v, "ImageMagick 6.9.11-60 Q16") === false) exit(3); \
        echo PHP_VERSION, PHP_EOL; \
        echo "imagick ", phpversion("imagick"), PHP_EOL; \
        echo $v, PHP_EOL; \
    '

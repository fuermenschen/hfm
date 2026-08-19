FROM php:8.4.21-cli-bookworm

ARG IMAGEMAGICK_VERSION=6.9.11-60
ARG IMAGICK_VERSION=3.8.1

# Build dependencies + common ImageMagick delegates
RUN apt-get update && apt-get install -y --no-install-recommends \
        build-essential \
        pkg-config \
        curl \
        xz-utils \
        libjpeg62-turbo-dev \
        libpng-dev \
        libwebp-dev \
        libfreetype6-dev \
        libxml2-dev \
        libtiff-dev \
    && rm -rf /var/lib/apt/lists/*

# ImageMagick 6.9.11-60, Q16
RUN curl -fsSL \
        "https://download.imagemagick.org/archive/releases/ImageMagick-${IMAGEMAGICK_VERSION}.tar.xz" \
        -o /tmp/imagemagick.tar.xz \
    && mkdir /tmp/imagemagick \
    && tar -xJf /tmp/imagemagick.tar.xz \
        -C /tmp/imagemagick --strip-components=1 \
    && cd /tmp/imagemagick \
    && ./configure \
        --prefix=/usr/local \
        --with-quantum-depth=16 \
        --disable-hdri \
    && make -j"$(nproc)" \
    && make install \
    && ldconfig \
    && rm -rf /tmp/imagemagick /tmp/imagemagick.tar.xz

# PHP Imagick 3.8.1
RUN pecl install imagick-${IMAGICK_VERSION} \
    && docker-php-ext-enable imagick

# Fail build if versions differ
RUN php -r ' \
    if (PHP_VERSION !== "8.4.21") exit(1); \
    if (phpversion("imagick") !== "3.8.1") exit(2); \
    $v = Imagick::getVersion()["versionString"]; \
    if (strpos($v, "ImageMagick 6.9.11-60 Q16") === false) exit(3); \
    echo PHP_VERSION, PHP_EOL; \
    echo "imagick ", phpversion("imagick"), PHP_EOL; \
    echo $v, PHP_EOL; \
'

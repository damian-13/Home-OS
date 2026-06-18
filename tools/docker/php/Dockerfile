FROM php:8.5-cli-alpine

RUN apk add --no-cache \
    bash \
    curl \
    git \
    icu-dev \
    libpq-dev \
    linux-headers \
    unzip \
    zip

RUN docker-php-ext-install intl pdo_pgsql

COPY --from=composer:2 /usr/bin/composer /usr/bin/composer

RUN curl -1sLf 'https://dl.cloudsmith.io/public/symfony/stable/setup.alpine.sh' | bash \
    && apk add --no-cache symfony-cli

WORKDIR /app/backend

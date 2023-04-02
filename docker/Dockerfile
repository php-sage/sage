FROM node:latest AS node
FROM php:fpm

ARG DOCKER_UID

COPY --from=node /usr/local/lib/node_modules /usr/local/lib/node_modules
COPY --from=node /usr/local/bin/node /usr/local/bin/node
RUN ln -s /usr/local/lib/node_modules/npm/bin/npm-cli.js /usr/local/bin/npm

WORKDIR /var/www

# Install composer
ENV COMPOSER_HOME "/var/composer"
ENV PATH ./vendor/bin:/composer/vendor/bin:$PATH
ENV COMPOSER_ALLOW_SUPERUSER 1
ENV COMPOSER_PROCESS_TIMEOUT 600
RUN apt update && apt install -y unzip
RUN curl -s https://getcomposer.org/installer | php -- --install-dir=/usr/local/bin/ --filename=composer


# disable phar readonly
RUN echo "phar.readonly = Off" >> /usr/local/etc/php/conf.d/php.enable-phar.ini


RUN useradd -u ${DOCKER_UID} -m the-whale; \
    useradd -u ${DOCKER_UID} -m the-whale; \
    usermod -G www-data,the-whale,root,adm the-whale

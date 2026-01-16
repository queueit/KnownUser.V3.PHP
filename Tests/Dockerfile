FROM php:7.3-cli

RUN apt-get update && \
    apt-get install -y --no-install-recommends git zip
RUN curl --silent --show-error https://getcomposer.org/installer | php

RUN mkdir /app /app/Tests

COPY ./ /app
WORKDIR /app/Tests
RUN php /composer.phar install
WORKDIR /app
RUN php ./Tests/TestSuite.php
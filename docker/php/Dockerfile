FROM meysam1987/php

RUN apt-get update && apt-get install -y smbclient libsmbclient-dev
RUN pecl install smbclient
RUN docker-php-ext-enable smbclient
RUN docker-php-ext-configure pcntl --enable-pcntl \
  && docker-php-ext-install \
    pcntl
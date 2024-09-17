FROM meysam1987/php

RUN apt-get update --fix-missing && apt-get install -y smbclient libsmbclient-dev
RUN pecl install smbclient
RUN docker-php-ext-enable smbclient
RUN docker-php-ext-configure pcntl --enable-pcntl \
  && docker-php-ext-install \
    pcntl


RUN apt-get install -y gnupg2
RUN apt-get install -y unixodbc-dev
RUN curl -sL https://packages.microsoft.com/keys/microsoft.asc | apt-key add -
RUN echo "deb [arch=amd64,armhf,arm64] https://packages.microsoft.com/ubuntu/20.04/prod focal main" > /etc/apt/sources.list.d/mssql-release.list

RUN apt-get update
RUN ACCEPT_EULA=Y apt-get install -y msodbcsql17 \
    && pecl install sqlsrv \
    && docker-php-ext-enable sqlsrv \
    && pecl install pdo_sqlsrv \
    && docker-php-ext-enable pdo_sqlsrv

COPY memory.ini /usr/local/etc/php/conf.d/

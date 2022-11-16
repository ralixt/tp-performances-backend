FROM ubuntu:kinetic

ENV TZ="Europe/Paris"

# Install NGINX
RUN apt update && apt upgrade \
  && apt install -y tzdata \
  && apt install -y nginx;

# Install PHP
RUN apt update \
  && apt install -y php8.1-fpm php-dev pkg-php-tools \
  && apt install -y php8.1-cli php8.1-common php8.1-mysql php8.1-zip php8.1-gd php8.1-mbstring php8.1-curl php8.1-xml php8.1-bcmath \
  && pecl install redis \
  && echo "extension=redis.so" >> /etc/php/8.1/fpm/php.ini

# Install Git, Composer, Nano, ZIP \
RUN apt update \
    && apt install -y zip nano git curl sudo wget gpg \
    && curl -sS https://getcomposer.org/installer -o /tmp/composer-setup.php \
    && php /tmp/composer-setup.php --install-dir=/usr/local/bin --filename=composer;

# Install NewRelic
ARG NEW_RELIC_API_KEY
ENV NEW_RELIC_API_KEY=$NEW_RELIC_API_KEY

ARG NEW_RELIC_ACCOUNT_ID
ENV NEW_RELIC_ACCOUNT_ID=$NEW_RELIC_ACCOUNT_ID

ARG NEW_RELIC_REGION
ENV NEW_RELIC_REGION=$NEW_RELIC_REGION

ARG NEW_RELIC_APP_NAME
ENV NEW_RELIC_APP_NAME=$NEW_RELIC_APP_NAME

ARG NEW_RELIC_LICENSE_KEY
ENV NEW_RELIC_LICENSE_KEY=$NEW_RELIC_LICENSE_KEY
ENV NR_INSTALL_KEY=$NEW_RELIC_LICENSE_KEY

ENV NR_INSTALL_USE_CP_NOT_LN 1
ENV NR_INSTALL_SILENT 1
ENV PHP_VERSION 8.1

RUN echo newrelic-php5 newrelic-php5/application-name string $NEW_RELIC_APP_NAME | debconf-set-selections \
    && echo newrelic-php5 newrelic-php5/license-key string $NEW_RELIC_LICENSE_KEY | debconf-set-selections

RUN echo 'deb http://apt.newrelic.com/debian/ newrelic non-free' | sudo tee /etc/apt/sources.list.d/newrelic.list \
    && wget -O- https://download.newrelic.com/548C16BF.gpg | sudo apt-key add - \
    && sudo apt update && yes | sudo apt -y install newrelic-php5 \
    && sudo NR_INSTALL_SILENT=1 newrelic-install install \
    && sed -i -e "s/REPLACE_WITH_REAL_KEY/$NEW_RELIC_LICENSE_KEY/" \
         -e "s/newrelic.appname[[:space:]]=[[:space:]].*/newrelic.appname=\"$NEW_RELIC_APP_NAME\"/" \
         /etc/php/$PHP_VERSION/fpm/conf.d/newrelic.ini

# Setup websites folder
EXPOSE 80
EXPOSE 443

RUN mkdir /var/www/tp-performances \
    && touch /etc/nginx/sites-available/tp-performances \
    && ln -s /etc/nginx/sites-available/tp-performances /etc/nginx/sites-enabled \
    && unlink /etc/nginx/sites-enabled/default;

VOLUME /var/www/tp-performances
WORKDIR /var/www/tp-performances

STOPSIGNAL SIGQUIT

CMD ["/bin/bash", "-c", "php-fpm8.1 && chmod 777 /var/run/php/php8.1-fpm.sock && chmod 755 /usr/share/nginx/html/* && nginx -g 'daemon off;'"]



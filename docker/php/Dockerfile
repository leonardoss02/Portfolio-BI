# Imagem única usada pelos serviços "seed" (gera os dados) e "app" (Apache).
FROM php:8.3-apache

# mysqli para o banco; mod_rewrite para o .htaccess mandar tudo ao index.php.
RUN docker-php-ext-install mysqli \
    && a2enmod rewrite \
    && sed -ri 's/AllowOverride None/AllowOverride All/g' /etc/apache2/apache2.conf \
    && printf "date.timezone=America/Sao_Paulo\n" > /usr/local/etc/php/conf.d/timezone.ini

WORKDIR /var/www/html

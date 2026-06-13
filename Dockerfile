# RH Privus - imagem PHP + Apache para deploy (Coolify / Docker Compose)
FROM php:8.2-apache

# Dependências de sistema e extensões PHP necessárias
RUN apt-get update && apt-get install -y --no-install-recommends \
        libpng-dev \
        libjpeg62-turbo-dev \
        libfreetype6-dev \
        libwebp-dev \
        libzip-dev \
        libonig-dev \
        unzip \
        default-mysql-client \
    && docker-php-ext-configure gd --with-freetype --with-jpeg --with-webp \
    && docker-php-ext-install -j"$(nproc)" pdo_mysql gd mbstring zip \
    && apt-get clean && rm -rf /var/lib/apt/lists/*

# Habilita mod_rewrite (necessário para o .htaccess)
RUN a2enmod rewrite

# VirtualHost com DocumentRoot na raiz do projeto
COPY docker/apache-vhost.conf /etc/apache2/sites-available/000-default.conf

# Composer
COPY --from=composer:2 /usr/bin/composer /usr/bin/composer

WORKDIR /var/www/html

# Instala dependências PHP primeiro (cache de camada)
COPY composer.json ./
RUN composer install --no-dev --no-scripts --no-interaction --prefer-dist --optimize-autoloader || true

# Copia o restante do código
COPY . /var/www/html

# Reexecuta o autoloader já com todo o código presente
RUN composer install --no-dev --no-interaction --prefer-dist --optimize-autoloader \
    && chown -R www-data:www-data /var/www/html

# Entrypoint: espera o banco, instala/migra e sobe o Apache
RUN chmod +x /var/www/html/docker/entrypoint.sh

EXPOSE 80
ENTRYPOINT ["/var/www/html/docker/entrypoint.sh"]

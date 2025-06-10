FROM php:8.0-apache

# Installation des extensions PHP nécessaires
RUN apt-get update && apt-get install -y \
    libpng-dev \
    libjpeg-dev \
    libfreetype6-dev \
    libzip-dev \
    unzip \
    && docker-php-ext-install mysqli pdo pdo_mysql zip \
    && docker-php-ext-configure gd --with-freetype --with-jpeg \
    && docker-php-ext-install -j$(nproc) gd

# Installation de Composer
RUN curl -sS https://getcomposer.org/installer | php -- --install-dir=/usr/local/bin --filename=composer

# Configuration du serveur Apache
RUN a2enmod rewrite
COPY ./web /var/www/html

# Add custom site configuration with proper permissions
COPY ./apache-config.conf /etc/apache2/sites-available/000-default.conf

# Mise à jour des permissions Apache pour .htaccess
RUN sed -i 's/AllowOverride None/AllowOverride All/g' /etc/apache2/apache2.conf

# Permissions des fichiers
RUN chown -R www-data:www-data /var/www/html
RUN chmod -R 755 /var/www/html

# Debug: afficher les configurations
RUN echo "Apache config:" && cat /etc/apache2/apache2.conf | grep AllowOverride
RUN echo "VirtualHost config:" && cat /etc/apache2/sites-available/000-default.conf

# Exposer le port 80
EXPOSE 80

# Démarrer Apache en premier plan
CMD ["apache2-foreground"] 
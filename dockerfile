FROM php:8.2-apache

# Enable Apache rewrite module
RUN a2enmod rewrite

# Install cURL extension for Supabase API requests
RUN apt-get update && apt-get install -y libcurl4-openssl-dev \
    && docker-php-ext-install curl

# Copy project files into Apache document root
COPY . /var/www/html/

EXPOSE 80
FROM ubuntu:22.04

ENV DEBIAN_FRONTEND=noninteractive

# Install dependencies: Apache, PHP, and MySQL extensions
RUN apt-get update && apt-get install -y \
    apache2 \
    php \
    php-mysql \
    libapache2-mod-php \
    && apt-get clean \
    && rm -rf /var/lib/apt/lists/*

# Enable Apache rewrite module
RUN a2enmod rewrite

# Update Apache configuration to allow overrides (for .htaccess)
RUN sed -i 's/AllowOverride None/AllowOverride All/g' /etc/apache2/apache2.conf

# Set ServerName to avoid warning messages
RUN echo "ServerName localhost" >> /etc/apache2/apache2.conf

# Copy project files to the document root
COPY . /var/www/html/

# Ensure proper permissions
RUN chown -R www-data:www-data /var/www/html

# Expose port 80
EXPOSE 80

# Start Apache in the foreground
CMD ["apachectl", "-D", "FOREGROUND"]
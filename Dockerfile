FROM php:8.4-cli-bookworm

WORKDIR /var/www/html

# Install system dependencies, Node.js 20, and Chromium
RUN apt-get update && apt-get install -y --no-install-recommends \
    curl \
    git \
    unzip \
    libzip-dev \
    libsqlite3-dev \
    chromium \
    && curl -fsSL https://deb.nodesource.com/setup_20.x | bash - \
    && apt-get install -y nodejs \
    && docker-php-ext-install zip bcmath pcntl \
    && apt-get clean && rm -rf /var/lib/apt/lists/*

# Copy Composer binary
COPY --from=composer:2 /usr/bin/composer /usr/bin/composer

# Puppeteer & Chromium configuration
ENV PUPPETEER_SKIP_CHROMIUM_DOWNLOAD=true
ENV PUPPETEER_EXECUTABLE_PATH=/usr/bin/chromium
ENV WHATSAPP_WEB_CHROME_PATH=/usr/bin/chromium
ENV WHATSAPP_WEB_SIDECAR_PATH=/var/www/html/whatsapp-sidecar

# 1. Install PHP dependencies (Cached layer)
COPY composer.json composer.lock ./
RUN composer install --no-dev --no-scripts --no-autoloader --prefer-dist

# 2. Install WhatsApp Web sidecar dependencies early for Docker layer caching
COPY whatsapp-sidecar/package.json ./whatsapp-sidecar/
RUN cd whatsapp-sidecar && npm install && npm cache clean --force

# 3. Install App Node dependencies (Cached layer)
COPY package.json package-lock.json ./
RUN npm ci && npm cache clean --force

# 4. Copy application files (Only application code edits invalidate layers below)
COPY . .

# 5. Install WhatsApp Web sidecar dependencies
RUN cd whatsapp-sidecar && npm prune --omit=dev && npm cache clean --force

# Create necessary Laravel framework directories
RUN mkdir -p storage/framework/cache/data \
             storage/framework/sessions \
             storage/framework/views \
             storage/framework/testing \
             storage/logs \
             storage/app/whatsapp-sidecar/sessions \
             bootstrap/cache

# Optimize Composer Autoloader
RUN composer dump-autoload --no-dev --optimize

# Build production assets using Vite
RUN npm run build

# Fix permissions for writable directories only
RUN chown -R www-data:www-data storage bootstrap/cache \
    && chmod -R 775 storage bootstrap/cache

# Expose Laravel port
EXPOSE 3001

# Execute startup script
CMD ["./scripts/start-container.sh"]

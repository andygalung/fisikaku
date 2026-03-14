#!/bin/bash

# Aktifkan mode maintenance
php artisan down || true

# Update kode dari Git
git pull origin main

# Update dependensi Composer (tanpa dev)
composer install --no-dev --optimize-autoloader

# Jalankan migrasi database
php artisan migrate --force

# Optimasi konfigurasi, route, dan view
php artisan config:cache
php artisan route:cache
php artisan view:cache

# Bersihkan cache lainnya
php artisan cache:clear

# Matikan mode maintenance
php artisan up

echo "✅ Backend Fisikakuu berhasil diupdate ke Hostinger!"

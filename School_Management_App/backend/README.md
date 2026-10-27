# DAV School Management - REST API (Laravel)

This is the backend for the four Android apps. Full setup, deployment and API notes are in
[`../README.md`](../README.md).

Quick start:

```bash
composer install
cp .env.example .env && php artisan key:generate
touch database/database.sqlite
php artisan migrate --seed        # demo school + demo logins
php artisan serve --host=0.0.0.0 --port=8000
python3 tests/smoke_api.py        # 138 end-to-end API checks
```

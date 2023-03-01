## Installation

Clone the repo locally:

```sh
git clone https://github.com/pratamashooter/final-project-pemweb-api.git
cd final-project-pemweb-api.git
```

Install PHP dependencies:

```sh
composer install
```

Setup configuration:

```sh
cp .env.example .env
```

Generate application key:

```sh
php artisan key:generate
```

Run database migrations:

```sh
php artisan migrate
```

Run database seeder:

```sh
php artisan db:seed
```

Run artisan server:

```sh
php artisan serve
```

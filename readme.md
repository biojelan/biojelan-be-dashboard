# BioJelan API

## Run Locally

### Requirements

- PHP 8.3 or newer
- Composer
- Node.js and npm
- MySQL 8.0 or newer

### Setup

```bash
git clone <repository-url>
cd biojelan-be-dashboard
composer install
npm install
cp .env.example .env
php artisan key:generate
```

### Database Setup

Start your local MySQL server, then create a database:

```bash
mysql -u root -p -e "CREATE DATABASE biojelan;"
```

Update the database settings in `.env`:

```env
DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=biojelan
DB_USERNAME=root
DB_PASSWORD=
```

Use your own MySQL username and password when they differ from the example. Then run the migrations and seed the initial data:

```bash
php artisan migrate --seed
```

### Start the Project

For running the project in development mode, use:

```bash
composer run dev
```

### Test the API

Register a new user, then use the returned token to call protected endpoints:

```bash
curl -X POST http://127.0.0.1:8000/api/register \
  -H "Accept: application/json" \
  -H "Content-Type: application/json" \
  -d '{"name":"API Tester","email":"api.tester@example.com","password":"password","password_confirmation":"password"}'
```

Example protected request:

```bash
curl http://127.0.0.1:8000/api/user \
  -H "Accept: application/json" \
  -H "Authorization: Bearer <token>"
```

To view all available endpoints, run:

```bash
php artisan route:list --path=api
```

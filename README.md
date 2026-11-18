# EV Charge Point API

A lightweight REST API for managing electric vehicle charge points, built with vanilla PHP 8.3+, SQLite and PDO.

## Requirements

- PHP 8.3 or later
- Composer
- PDO SQLite extension

## Installation

Clone the repository:

```bash
git clone https://github.com/simonelee93/ecomovement-ev-charge-point-api-assessment.git
cd ecomovement-ev-charge-point-api-assessment
```

Install dependencies:

```bash
composer install
```

Create your local configuration:

```bash
cp .env.example .env
```

The default configuration is:

```dotenv
APP_ENV=local
APP_PORT=8000
DB_PATH=var/chargepoints.sqlite
```

You can change the server port and database path in `.env`.

## Running the application

Start the development server:

```bash
./bin/serve
```

The API will be available at:

```text
http://localhost:8000
```

The SQLite database is created automatically when the application starts. Database migrations are executed during application startup.

## API endpoints

### Health check

`GET /`

Returns the application status.

### Create a charge point

`POST /chargepoints`

Request body:

```json
{
    "name": "Amsterdam Central",
    "latitude": 52.378,
    "longitude": 4.9,
    "status": "AVAILABLE"
}
```

Supported statuses:

- `AVAILABLE`
- `OCCUPIED`
- `OFFLINE`

Returns the created charge point, including its database-generated ID, with HTTP `201 Created`.

### Retrieve a charge point

`GET /chargepoints/{id}`

Returns a charge point by its ID.

Returns HTTP `404 Not Found` if the charge point does not exist.

### Find nearby charge points

`GET /chargepoints/nearby?lat={latitude}&lon={longitude}&radius={radius}`

Returns charge points within the specified radius in kilometres.

Example:

```text
GET /chargepoints/nearby?lat=52.378&lon=4.9&radius=5
```

Latitude and longitude must be valid geographic coordinates. Radius must be non-negative.

## Running tests

Run the PHPUnit test suite:

```bash
./vendor/bin/phpunit
```

The test suite contains unit and integration tests, including tests for:

- Domain validation
- HTTP request and response handling
- Routing
- Database connections and migrations
- Charge point persistence
- Nearby geographical searches
- Controller behaviour and input validation

## Project structure

```text
config/
    routes.php

database/
    migrations/

public/
    index.php
    router.php

src/
    Controller/
    Database/
    Domain/
    Http/
    Repository/
    Service/

tests/
    Integration/
    Unit/

bin/
    serve

var/
    chargepoints.sqlite
```

## Design decisions

- **Vanilla PHP:** The application uses native PHP without a full-stack framework.
- **SQLite and PDO:** SQLite provides lightweight persistence, while PDO handles database access and prepared statements.
- **Repository pattern:** Database operations are separated from HTTP and domain logic.
- **Input parsing:** Request input is parsed and validated separately from controller responsibilities.
- **Presentation:** Charge point objects are converted into response arrays through a dedicated presenter.
- **Geographical calculations:** Nearby searches use a bounding-box query to narrow database candidates, followed by an exact distance calculation using the Haversine formula.
- **Database-generated IDs:** SQLite generates unique charge point IDs when records are inserted.

## Time spent
Approximately 4-6 hours, including implementation, testing, refinement, and documentation.

## Notes

The PHP built-in development server is intended for local development and assessment purposes, not production deployment.


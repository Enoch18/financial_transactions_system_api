# Microservices-Based Finance Management System API

## Overview

This project is a microservices-based financial transaction system built using **Laravel**. It consists of multiple services that handle different aspects of financial transactions securely and efficiently.

## Services

- **User Service** - Manages user accounts, authentication, and balances.
- **Transaction Service** - Handles deposits, withdrawals, and transfers.
- **Notification Service** - Sends email/SMS alerts for transactions.
- **Fraud Detection Service** - Detects suspicious transactions based on predefined rules.

## Technology Stack

- **Backend**: Laravel (PHP)
- **Authentication**: Laravel Sanctum (Bearer Token)
- **Messaging Queue**: RabbitMQ
- **Database**: MySQL (Each service has its own database)
- **Containerization**: Docker
- **API Communication**: RESTful APIs with secure authentication

---

## Setup Instructions

### Prerequisites

- PHP 8+
- Composer
- MySQL
- RabbitMQ
- Docker (optional for containerized setup)

### Installation Steps

1. **Clone the Repository:**

   ```sh
   git clone https://github.com/Enoch18/financial_transactions_system_api.git
   cd finance-microservices
   ```

2. **Install Dependencies:**

   ```sh
   composer install
   ```

3. **Setup Environment:** Copy the `.env.example` file for each service and configure it.

   ```sh
   cp .env.example .env
   ```

   Update database settings and RabbitMQ credentials.

4. **Run Migrations:**

   ```sh
   php artisan migrate --seed
   ```

5. **Start the Services:**

   ```sh
   php artisan serve
   ```

   Or using Docker:

   ```sh
   docker-compose up -d
   ```

---

## API Endpoints

### Authentication (User Service)

#### Register a New User

```http
POST /api/register
```

**Payload:**

```json
{
  "name": "John Doe",
  "email": "john@example.com",
  "password": "password123",
  "password_confirmation": "password123"
}
```

#### Login

```http
POST /api/login
```

**Payload:**

```json
{
  "email": "john@example.com",
  "password": "password123"
}
```

**Response:**

```json
{
  "token": "BearerTokenHere"
}
```

#### Forgot Password

```http
POST /api/forgot-password
```

**Payload:**

```json
{
  "email": "john@example.com"
}
```

#### Reset Password

```http
POST /api/reset-password
```

**Payload:**

```json
{
  "email": "john@example.com",
  "token": "reset_token_here",
  "password": "newpassword",
  "password_confirmation": "newpassword"
}
```

### Transactions (Transaction Service)

#### Initiate a Transfer

```http
POST /api/transfer
Authorization: Bearer {token}
```

**Payload:**

```json
{
  "from_user_id": 1,
  "to_user_id": 2,
  "amount": 500
}
```

#### Check User Balance

```http
GET /api/balance/{user_id}
Authorization: Bearer {token}
```

---

## Security

- All requests require a **Bearer Token** for authentication.
- Inter-service communication uses **signed JWT tokens**.
- API rate limiting is enforced using **Laravel Throttle Middleware**.
- RabbitMQ is used for **asynchronous processing** of notifications and fraud detection.

---

## Deployment

### With Docker

```sh
docker-compose up -d
```

### Without Docker

- Set up **Nginx/Apache**.
- Configure **MySQL databases**.
- Run migrations and **Laravel queue workers**.

```sh
php artisan migrate --seed
php artisan queue:work
```

---

## Contributing

Feel free to fork and submit pull requests.

---

## License

MIT License
# Investment Account API

Laravel backend API for managing investment accounts, cash balances, holdings, and transactions for clients.

## Requirements

- PHP 8.2+
- Composer
- SQLite

## Local Setup

### 1. Clone the repository

```bash
git clone https://github.com/MarijaBozinovska/investment-account-api.git
cd investment-account-api
```

### 2. Install dependencies

```bash
composer install
```

### 3. Configure the environment

Copy the example environment file:

```bash
cp .env.example .env
```

Generate the application key:

```bash
php artisan key:generate
```

The project is configured to use SQLite.

### 4. Create the SQLite database

If the SQLite database file does not exist:

```bash
touch database/database.sqlite
```

Make sure `.env` contains:

```env
DB_CONNECTION=sqlite
```

### 5. Run migrations and seed example data

```bash
php artisan migrate:fresh --seed
```

The seed data creates example clients, accounts, and transactions so the API can be used immediately.

### 6. Start the development server

```bash
php artisan serve
```

The API will be available at:

`http://127.0.0.1:8000`

---

## API Endpoints

### Create a client

**POST** `/api/clients`

Request:

```json
{
    "name": "Ana",
    "currency": "EUR"
}
```

Response:

```json
{
    "id": 1,
    "name": "Ana",
    "created_at": "...",
    "updated_at": "...",
    "account": {
        "id": 1,
        "client_id": 1,
        "currency": "EUR",
        "created_at": "...",
        "updated_at": "..."
    }
}
```

Each client has exactly one account and one currency.

### Create a deposit

**POST** `/api/clients/{client}/transactions`

Request:

```json
{
    "type": "deposit",
    "amount": 1000
}
```

Response:

```json
{
    "id": 1,
    "account_id": 1,
    "type": "deposit",
    "amount": 1000,
    "instrument": null,
    "quantity": null,
    "price": null
}
```

### Create a withdrawal

**POST** `/api/clients/{client}/transactions`

Request:

```json
{
    "type": "withdrawal",
    "amount": 300
}
```

A withdrawal is rejected if the requested amount is greater than the available cash balance.

Example error:

```json
{
    "message": "Insufficient cash balance for this withdrawal."
}
```

### Buy an instrument

**POST** `/api/clients/{client}/transactions`

Request:

```json
{
    "type": "buy",
    "instrument": "AAPL",
    "quantity": 5,
    "price": 100
}
```

The transaction amount is calculated as:

`quantity × price`

For this example:

`5 × 100 = 500`

The purchase is rejected if there is not enough available cash.

### Sell an instrument

**POST** `/api/clients/{client}/transactions`

Request:

```json
{
    "type": "sell",
    "instrument": "AAPL",
    "quantity": 3,
    "price": 120
}
```

The sale proceeds are calculated using the sale price:

`3 × 120 = 360`

A sale is rejected if the client does not own enough units of the instrument.

Example error:

```json
{
    "message": "Insufficient holdings for this sale."
}
```

### List client transactions

**GET** `/api/clients/{client}/transactions`

Transactions are returned newest first.

Transactions are append-only and are never edited or deleted.

### Get cash balance

**GET** `/api/clients/{client}/balance`

Example response:

```json
{
    "currency": "EUR",
    "balance": 860
}
```

Cash balance is derived from the transaction history:

- deposits increase cash
- withdrawals decrease cash
- buys decrease cash
- sells increase cash

### Get holdings

**GET** `/api/clients/{client}/holdings`

Example response:

```json
{
    "holdings": {
        "AAPL": 2
    }
}
```

Holdings are calculated from buys and sells.

---

## Validation

The API validates all incoming transaction data.

### Deposit / withdrawal

`amount` is required and must be a positive number.

### Buy / sell

The following fields are required:

- `instrument`
- `quantity`
- `price`

`quantity` must be a positive whole number.

`price` must be positive.

The transaction type must be one of:

- `deposit`
- `withdrawal`
- `buy`
- `sell`

Invalid input returns HTTP `422` with validation errors.

---

## Business Rules

- A client has one account and one currency.
- Transactions belong to a client's account.
- Clients are isolated from each other.
- Cash can never become negative.
- A client cannot withdraw more cash than available.
- A client cannot buy more than the available cash allows.
- A client cannot sell more units than currently owned.
- Buy and sell amounts are calculated from quantity and price.
- Sell price can be different from the original purchase price.
- Transactions are append-only.
- Invalid operations are rejected without creating a transaction.
- No external price source is used; prices are supplied with each transaction.
- Instrument names/tickers are accepted as supplied; there is no predefined instrument list.

---

## Testing

Run the full test suite with:

```bash
php artisan test
```

The tests cover:

- deposits
- insufficient withdrawals
- purchases
- purchases with insufficient cash
- sales at a different price
- overselling
- balance calculation
- holdings calculation
- client isolation
- input validation

---

## Why this way

The application uses a transaction ledger as the source of truth for account state. Cash balance and instrument holdings are calculated from the transaction history rather than stored as mutable values. This keeps the transaction history append-only and makes the account state reproducible from its transactions.

Business rules are kept in a dedicated `TransactionService`, while controllers handle the API layer, Form Requests handle input validation, and Eloquent models represent the database relationships.

Database transactions are used when creating financial transactions so that a rejected operation does not leave a partial state.

SQLite is used for the assignment because it keeps local setup simple and requires no separate database server.

The project includes seed data and automated tests so the reviewer can run the application and verify the main business rules without manually creating the initial state.

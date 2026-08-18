# Jimmy API

Base URL:

```text
/api/v1
```

Login endpoint haihitaji token. Endpoint nyingine zote zinahitaji token kwenye header:

```text
Authorization: Bearer YOUR_TOKEN
Accept: application/json
```

## 1. Login

```http
POST /api/v1/login
```

Payload:

```json
{
  "email": "jimmy@gpitg.com",
  "password": "password"
}
```

Response:

```json
{
  "success": true,
  "message": "Login successful.",
  "data": {
    "user": {
      "id": 1,
      "name": "Jimmy Mbapila",
      "email": "jimmy@gpitg.com"
    },
    "token": "1|your-token-here",
    "token_type": "Bearer"
  }
}
```

Wrong credentials:

```json
{
  "success": false,
  "message": "The email or password is incorrect."
}
```

## 2. List products

```http
GET /api/v1/products
```

Hakuna payload.

Response:

```json
{
  "success": true,
  "message": "Products retrieved successfully.",
  "data": [
    {
      "id": 1,
      "name": "Wireless Headphones",
      "description": "Comfortable wireless headphones with clear sound.",
      "price": "79.99",
      "ratings": 4.5,
      "user_rating": 4,
      "time_passed": 12,
      "time_passed_human": "12 minutes ago",
      "active_time": "inactive"
    }
  ]
}
```

`ratings` ni average ya ratings zote za product. `user_rating` ni rating ya user mwenye token.

`active_time` inakuwa `active` kama `time_passed` ni zaidi ya dakika 30, vinginevyo ni `inactive`.

## 3. Rate a product

```http
POST /api/v1/products/{productId}/rating
```

Example:

```http
POST /api/v1/products/1/rating
```

Payload:

```json
{
  "rating": 5
}
```

Rating inaruhusiwa kuanzia 1 mpaka 5. Kama user alisharate product hiyo, rating yake ya zamani ita-update.

Response ya rating mpya:

```json
{
  "success": true,
  "message": "Product rated successfully.",
  "data": {
    "id": 1,
    "user_id": 1,
    "product_id": 1,
    "rating": 5,
    "rating_datetime": "2026-08-18 11:30:00"
  }
}
```

Response kama rating ilikuwepo:

```json
{
  "success": true,
  "message": "Your existing rating was updated.",
  "data": {
    "id": 1,
    "user_id": 1,
    "product_id": 1,
    "rating": 5,
    "rating_datetime": "2026-08-18 11:30:00"
  }
}
```

## 4. Change a rating

```http
PUT /api/v1/products/{productId}/rating
```

Example:

```http
PUT /api/v1/products/1/rating
```

Payload:

```json
{
  "rating": 3
}
```

Response:

```json
{
  "success": true,
  "message": "Rating updated successfully.",
  "data": {
    "id": 1,
    "user_id": 1,
    "product_id": 1,
    "rating": 3,
    "rating_datetime": "2026-08-18 11:45:00"
  }
}
```

## 5. Remove a rating

```http
DELETE /api/v1/products/{productId}/rating
```

Example:

```http
DELETE /api/v1/products/1/rating
```

Hakuna payload.

Response:

```json
{
  "success": true,
  "message": "Rating removed successfully."
}
```

## 6. Register a patient

```http
POST /api/v1/patients/register
```

Payload:

```json
{
  "Sponsor_ID": "1",
  "Patient_Name": "ngenzi ngenzi",
  "Date_Of_Birth": "2022-07-02",
  "Gender": "Male",
  "Visit_Type_ID": "1",
  "Type_Of_Check_In": "1",
  "branchId": "1",
  "Employee_ID": "46",
  "pf3": null,
  "Diceased": "no",
  "Referral_Status": null
}
```

Request hii inatumwa kwenda Gpitg Hospital API.

Response:

```json
{
  "success": true,
  "message": "Patient registered successfully.",
  "data": {
    "Check_In_Date_And_Time": "2026-08-18 14:30:00"
  }
}
```

## Common errors

Token ikiwa haipo au sio sahihi:

```json
{
  "message": "Unauthenticated."
}
```

Rating ikiwa nje ya 1 mpaka 5:

```json
{
  "success": false,
  "message": "The rating must be a whole number between 1 and 5.",
  "errors": {
    "rating": [
      "The rating field must be between 1 and 5."
    ]
  }
}
```

Product ikiwa haipo:

```json
{
  "success": false,
  "message": "Product not found."
}
```

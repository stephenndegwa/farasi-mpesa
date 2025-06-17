# Farasi M-Pesa API Documentation

This API provides endpoints for integrating with M-Pesa payment services, including STK push, transaction status checking, and transaction retrieval.

## Base URL

All API endpoints are relative to: `/api/`

## Endpoints

### 1. STK Push Initiation

Initiates an STK push request to a customer's phone.

**URL:** `/api/stkpush`  
**Method:** `POST`  
**Content-Type:** `application/json`

**Request Body:**

```json
{
  "phone": "254712345678",
  "amount": 1000,
  "invoiceid": "INV-123456",
  "description": "Payment for Invoice #123456"
}
```

**Parameters:**

| Parameter    | Type   | Required | Description                                  |
|-------------|--------|----------|----------------------------------------------|
| phone       | String | Yes      | Customer phone number (format: 254XXXXXXXXX) |
| amount      | Number | Yes      | Amount to be paid (integer)                  |
| invoiceid   | String | Yes      | Invoice/account reference                    |
| description | String | No       | Payment description (default: STK Push Payment) |

**Success Response:**

```json
{
  "status": 200,
  "success": true,
  "message": "Payment request initiated successfully",
  "data": {
    "checkout_request_id": "ws_CO_01072023114505681712345678",
    "customer_message": "Payment request has been sent to 254712345678. Please check your phone and enter PIN.",
    "merchant_request_id": "92451-7854126-1",
    "response_code": "0",
    "response_description": "Success. Request accepted for processing"
  }
}
```

**Error Response:**

```json
{
  "status": 400,
  "success": false,
  "message": "Missing required fields: phone, amount",
  "data": {
    "error": "Missing required parameters"
  }
}
```

### 2. Check STK Push Status

Check the status of a previously initiated STK push request.

**URL:** `/api/stkpush/status/{checkoutRequestId}`  
**Method:** `GET`

**Parameters:**

| Parameter          | Type   | Location | Required | Description                           |
|-------------------|--------|----------|----------|---------------------------------------|
| checkoutRequestId | String | Path     | Yes      | CheckoutRequestID from STK push response |

**Success Response:**

```json
{
  "status": 200,
  "success": true,
  "message": "The service request is processed successfully.",
  "data": {
    "checkout_request_id": "ws_CO_01072023114505681712345678",
    "merchant_request_id": "92451-7854126-1",
    "result_code": 0,
    "result_description": "The service request is processed successfully.",
    "transaction_status": "COMPLETED",
    "transaction_date": "20250612140829",
    "phone_number": "2547XXXXXXXX",
    "amount": "1000.00"
  }
}
```

**Error Response:**

```json
{
  "status": 400,
  "success": false,
  "message": "Failed to check transaction status",
  "data": {
    "checkout_request_id": "ws_CO_01072023114505681712345678",
    "response_code": "1",
    "response_description": "Failed to process request"
  }
}
```

### 3. Transaction Retrieval

Retrieve transaction details by transaction reference or M-Pesa transaction ID.

**URL:** `/api/transactions`  
**Method:** `GET`

**Query Parameters:**

| Parameter      | Type   | Required | Description                                      |
|---------------|--------|----------|--------------------------------------------------|
| transactionRef | String | No*      | Transaction reference/BillRefNumber              |
| TransID        | String | No*      | M-Pesa transaction ID                           |
| page           | Number | No       | Page number for pagination (default: 1)         |
| limit          | Number | No       | Number of records per page (default: 20, max: 100) |

*At least one of `transactionRef` or `TransID` is required

**Success Response:**

```json
{
  "status": 200,
  "success": true,
  "message": "Transactions retrieved successfully.",
  "data": [
    {
      "id": "1",
      "TransactionType": "Pay Bill",
      "TransID": "TFC1KJ030J",
      "TransTime": "20250612140829",
      "TransAmount": "1.00",
      "BusinessShortCode": "6817245",
      "BillRefNumber": "1024",
      "InvoiceNumber": "",
      "OrgAccountBalance": "50.00",
      "ThirdPartyTransID": "",
      "MSISDN": "d90b9c0041f877d0735ef24449ff6554ea5f3ff4cbf5e16a0ec9b4b048e8611f",
      "FirstName": "STEPHEN",
      "created_at": "2025-06-12 14:08:30"
    }
  ],
  "meta": {
    "pagination": {
      "total": 1,
      "per_page": 20,
      "current_page": 1,
      "total_pages": 1,
      "has_more": false
    }
  }
}
```

**Error Response:**

```json
{
  "status": 400,
  "success": false,
  "message": "Either transactionRef or TransID is required."
}
```

## M-Pesa Confirmation Endpoint

This endpoint receives and processes M-Pesa payment confirmations.

**URL:** `/api/confirmation`  
**Method:** `POST`  
**Content-Type:** `application/json`

*Note: This endpoint is meant to be called by Safaricom M-Pesa API only, not by clients.*

**Expected Request Body:**
```json
{
  "TransactionType": "Pay Bill",
  "TransID": "TFC1KJ030J",
  "TransTime": "20250612140829",
  "TransAmount": "1.00",
  "BusinessShortCode": "6817245",
  "BillRefNumber": "1024",
  "InvoiceNumber": "",
  "OrgAccountBalance": "50.00",
  "ThirdPartyTransID": "",
  "MSISDN": "d90b9c0041f877d0735ef24449ff6554ea5f3ff4cbf5e16a0ec9b4b048e8611f",
  "FirstName": "STEPHEN"
}
```

**Response:**
```json
{
  "ResultCode": 0,
  "ResultDesc": "Confirmation received successfully"
}
```

## Error Codes

| HTTP Status Code | Description                                   |
|-----------------|-----------------------------------------------|
| 200             | Request processed successfully                |
| 400             | Bad request (missing or invalid parameters)   |
| 404             | Resource not found                           |
| 405             | Method not allowed                           |
| 500             | Internal server error                        |

## Notes

- All timestamps are in the format `YYYYMMDDHHmmss`
- Phone numbers should use the format `254XXXXXXXXX` (without leading '+' or '0')
- For security reasons, the MSISDN (phone number) is returned as a hashed value

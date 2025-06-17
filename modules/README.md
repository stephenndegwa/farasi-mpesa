# Farasi M-Pesa WHMCS Integration Module

## Updated June 2025

This WHMCS payment gateway module integrates with the Farasi M-Pesa API to enable mobile payments for your WHMCS invoices via M-Pesa.

## Features

- **STK Push**: Send payment requests directly to a customer's phone
- **Transaction Verification**: Verify payments using transaction references
- **Automatic Payment Validation**: Automatically check for payments for an invoice
- **Callback Processing**: Process M-Pesa callbacks for real-time payment updates
- **Comprehensive Logging**: All actions are logged for troubleshooting

## Installation

1. Copy the entire `farasi_mpesa` folder and its contents to the `modules/gateways/` directory of your WHMCS installation.
2. Copy the `callback/farasi_mpesa.php` file to `modules/gateways/callback/` directory.
3. Log into your WHMCS admin panel, go to Setup > Payment Gateways, and activate the "Lipa na MPESA" gateway.

## Configuration

In the WHMCS admin panel:

1. Navigate to Setup > Payment Gateways
2. Click on the "Manage Existing Gateways" tab
3. Configure the Lipa na MPESA gateway with the following information:

| Setting | Description |
|---------|-------------|
| Your License | Your license key from Farasi |
| API Base URL | The URL to your API (e.g., https://example.com) |
| Short Code Type | Choose Paybill or Till |
| Short Code | Your M-Pesa short code |
| Store Number | For Till numbers only |
| Consumer Key | Your M-Pesa API consumer key |
| Consumer Secret | Your M-Pesa API consumer secret |
| Mpesa Passkey | Your M-Pesa API passkey for STK Push |
| Mpesa Api Version | Choose v1 or v2 |
| Payment Description | Customize the payment instructions for your customers |
| Custom Mpesa Logo Link | URL to your custom M-Pesa logo (optional) |
| Auto Validate Payments | Toggle automatic payment validation |
| Enable M-Pesa Callback Processing | Toggle callback processing |

## API Integration

This module integrates with the Farasi M-Pesa API, which should be configured to accept the following endpoints:

- `/api/transactions` - For querying transactions
- `/api/stkpush` - For sending STK Push requests
- `/api/stkpush/status` - For checking STK Push status

## Features

### STK Push

Sends a payment request directly to the customer's phone, prompting them to enter their M-Pesa PIN.

### Payment Verification

Allows customers to verify payments by entering their transaction reference.

### Automatic Payment Validation

Periodically checks for payments for the current invoice without requiring customer interaction.

### Callback Handling

Processes M-Pesa callbacks for real-time payment updates.

## Troubleshooting

- Check the WHMCS Gateway Log for errors
- Verify your API credentials in the gateway settings
- Ensure your API server is accessible from your WHMCS server
- Check the logs in `modules/gateways/farasi_mpesa/logs/` for additional information

## Support

For technical support, please contact Farasi support at support@farasi.co.ke or visit https://farasi.co.ke/support

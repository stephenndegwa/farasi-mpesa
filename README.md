# Farasi M-Pesa WHMCS Gateway Integration (2025 Edition)

## Overview

This integration allows WHMCS users to accept payments through M-Pesa, the popular mobile money service. The integration supports:

1. Manual Payments - Where users enter M-Pesa transaction details
2. STK Push - Direct push to customer phones for payment
3. Transaction Validation - Automatic validation of payments
4. Payment Confirmation - Processing of M-Pesa confirmation callbacks

## Steps to Set Up the Farasi M-Pesa WHMCS Gateway

### 1. Upload the API Folder

- Copy the API project folder (`api`) to your server or hosting environment via cPanel or DirectAdmin.
- Ensure all files and subfolders are correctly uploaded.
- Set up the folder to run on a subdomain, such as `api.example.com`:
  - In cPanel: Navigate to **Domains > Subdomains**, create a subdomain pointing to the `api` folder.
  - In DirectAdmin: Navigate to **Account Manager > Subdomains**, create a subdomain with the document root as the `api` folder.

### 2. Create the Database

- Log in to your cPanel or DirectAdmin panel.
- Navigate to **Databases > MySQL Databases**.
- Create a new database with your preferred name.
- Create a new database user with a preferred username and password, then assign it to the database with full privileges.

### 3. Run the SQL File

- Open **phpMyAdmin** from your control panel.
- Select your newly created database.
- Click on the **Import** tab.
- Upload the `database.sql` file and click **Go** to execute the SQL commands.
- NOTE: If you're upgrading from a previous version, run the `migration_update_2025.sql` file as well.

### 4. Update the `.env` File

- Use the **File Manager** in your control panel to edit the `.env` file in the `api` folder.
- Update the values with your configuration details:

```
DB_HOST=your_db_host
DB_USER=your_db_user
DB_PASSWORD=your_db_password
DB_NAME=your_db_name

CONSUMER_KEY=your_mpesa_consumer_key
CONSUMER_SECRET=your_mpesa_consumer_secret
BUSINESS_SHORT_CODE=your_mpesa_shortcode
LIPA_NA_MPESA_PASSKEY=your_mpesa_passkey
CALLBACK_URL=https://your-domain.com/api/confirmation
DEFAULT_TRANSACTION_TYPE=CustomerBuyGoodsOnline
PARTY_B=your_mpesa_party_b
```

### 5. Register URLs with Safaricom

- Open a browser and navigate to `https://api.example.com/registerurls.php`.
- Enter the following details in the form:
  - **Short Code**: Your business's short code (e.g., PayBill or Till number).
  - **Confirmation URL**: The full URL for transaction confirmations (e.g., `https://api.example.com/confirmation`).
  - **Validation URL**: The full URL for transaction validations (e.g., `https://api.example.com/validation`).
  - **Consumer Key** and **Consumer Secret**: From your Safaricom developer portal.
- Click the **Register** button.

### 6. Upload the Modules Folder

- Copy the `modules` folder included in this project to your WHMCS root directory.
- Ensure the folder structure remains intact.
- The `modules` folder contains the necessary files for the WHMCS gateway integration.
- After uploading, navigate to your WHMCS admin panel to configure the payment gateway:
  1. Go to **Setup > Payments > Payment Gateways**.
  2. Activate the Farasi M-Pesa WHMCS Gateway.
  3. Configure the gateway with required credentials and settings:

### 7. Configure WHMCS Payment Gateway

In the WHMCS admin panel:
1. Navigate to **Setup > Payment Gateways**
2. Click on the "All Payment Gateways" tab
3. Find and activate "Lipa na MPESA"
4. Enter the following configuration:
   - **License**: Your license key (obtain from farasi.co.ke)
   - **Request URL**: URL to your API endpoint (e.g., `https://api.example.com`)
   - **STK Push URL**: URL to your API endpoint (e.g., `https://api.example.com`)
   - **Short Code Type**: Select Paybill or Till
   - **Short Code**: Your M-Pesa shortcode
   - **Store Number**: For till numbers only
   - **Consumer Key**: Your M-Pesa API consumer key
   - **Consumer Secret**: Your M-Pesa API consumer secret
   - **Mpesa Passkey**: Your M-Pesa API passkey for STK push
   - **Auto Validate Payments**: Enable for automatic payment validation

### 8. Test the Integration

Test your integration using these steps:
1. Create a test order or invoice in WHMCS
2. Attempt to pay using the M-Pesa payment option
3. Try both manual payment and STK Push options
4. Verify that transactions are recorded in your database
5. Check that payments are properly applied to invoices

## API Endpoints

The updated API provides RESTful endpoints:

- **STK Push**: `/api/stkpush` - Initiates M-Pesa STK push requests
- **STK Status**: `/api/stkpush/status/{checkoutRequestId}` - Checks status of STK push
- **Transactions**: `/api/transactions` - Fetches transaction data
- **Confirmation**: `/api/confirmation` - Receives M-Pesa payment confirmations

## Security Considerations

1. Ensure your API directory has proper access permissions (755 for directories, 644 for files)
2. Protect your `.env` file from public access (included in the .htaccess file)
3. Use HTTPS for all API endpoints to encrypt data in transit
4. Regularly check logs in the `api/logs` directory for suspicious activities

## Troubleshooting

Common issues and solutions:

- **Payments not showing in WHMCS**: Check logs in `api/logs` directory, verify callback URL configuration
- **STK Push errors**: Check phone number format (should be 254XXXXXXXXX), verify API keys and passkey
- **Connection errors**: Confirm API URL configuration, check server firewall settings
- **Database errors**: Verify database credentials in `.env` file, check database permissions

---


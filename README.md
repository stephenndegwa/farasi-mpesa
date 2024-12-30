# Farasi Mpesa WHMCS Gateway Setup Documentation

## Steps to Set Up the Farasi Mpesa WHMCS Gateway Project

### 1. Upload the API Folder

- Copy the API project folder (`api`) to your server or hosting environment via cPanel or DirectAdmin .
- Ensure all files and subfolders are correctly uploaded.
- Set up the folder to run on a subdomain, such as `api.example.com`:
  - In cPanel:
    1. Navigate to **Domains > Subdomains**.
    2. Create a subdomain pointing to the `api` folder.
  - In DirectAdmin:
    1. Navigate to **Account Manager > Subdomains**.
    2. Create a subdomain and specify the document root as the `api` folder.

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

### 4. Update the `.env` File

- Use the **File Manager** in your control panel to edit the `.env` file in the `api` folder.
- Update the values with your configuration details:

### 5. Register URLs

- Open a browser and navigate to `https://api.example.com/registerurls.php`.
- Enter the following details in the form:
  - **Short Code**: Your business's short code (e.g., PayBill or Till number).
  - **Confirmation URL**: The full URL for transaction confirmations (e.g., `https://api.example.com/confirmation.php`).
  - **Validation URL**: The full URL for transaction validations (e.g., `https://api.example.com/validation.php`).
  - **Consumer Key** and **Consumer Secret**: From your Safaricom developer portal.
- Click the **Register** button.

### 6. Transaction Confirmation (`confirmation.php`)

- The `confirmation.php` file handles transaction confirmations sent by Safaricom.
- This file is uploaded to the `api` folder and acts as the **Confirmation URL** for your integration.
- **Security Note**:
  - You can rename the `confirmation.php` file to a more obscure name and update your **Confirmation URL** in Safaricom settings to reduce exposure.
  - For example, rename it to `secure_confirm.php` and use `https://api.example.com/secure_confirm.php`.
  - Move the file to a non-public location if your setup allows, and configure the server to access it.

### 7. Upload the Modules Folder

- Copy the `modules` folder included in this project to your WHMCS root directory.
- Ensure the folder structure remains intact.
- The `modules` folder contains the necessary files for the WHMCS gateway integration.
- After uploading, navigate to your WHMCS admin panel to configure the payment gateway:
  1. Go to **Setup > Payments > Payment Gateways**.
  2. Activate the Farasi Mpesa WHMCS Gateway.
  3. Enter the required credentials (e.g., Consumer Key, Consumer Secret, and URLs).
  4. Update the **STK Push** and **Request URLs** in the following files:
     - `modules/gateways/farasi_mpesa.php`
     - `modules/gateways/farasi_mpesa/farasi_mpesa.php`
     - Use the full URLs pointing to your `api.example.com` endpoints, e.g., `https://api.example.com/stkpush.php` and `https://api.example.com/request.php`.

### 8. Test the Endpoints

- Use tools like Postman to test the following:
  - **Transaction Confirmation**: Ensure the `confirmation.php` endpoint saves transactions.
  - **Transaction Retrieval**: Use the `request.php` endpoint to fetch transactions by reference.
  - **STK Push**: Use the `stkpush.php` to initiate payment requests.

### 9. Additional Notes

- Ensure `mod_rewrite` is enabled if using Apache.
- Verify file permissions for uploaded files (e.g., 755 for directories and 644 for files).
- Use HTTPS to secure API calls and sensitive data.

---


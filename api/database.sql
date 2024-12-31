CREATE TABLE transactions (
    id INT AUTO_INCREMENT PRIMARY KEY,
    TransactionType VARCHAR(255) NOT NULL,
    TransID VARCHAR(255) NOT NULL,
    TransTime VARCHAR(255) NOT NULL,
    TransAmount VARCHAR(255) NOT NULL,
    BusinessShortCode VARCHAR(255) NOT NULL,
    BillRefNumber VARCHAR(255) NOT NULL,
    InvoiceNumber VARCHAR(255),
    OrgAccountBalance VARCHAR(255) NOT NULL,
    ThirdPartyTransID VARCHAR(255),
    MSISDN TEXT NOT NULL,
    FirstName VARCHAR(255) NOT NULL,
    MiddleName VARCHAR(255),
    LastName VARCHAR(255),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

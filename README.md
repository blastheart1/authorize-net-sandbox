# Authorize.Net Sandbox Integration (PHP)

This project is a simple sandbox implementation of **Authorize.Net** using PHP.  
It demonstrates secure handling of API credentials, basic form validation, and transaction requests.

---

## Features
- Securely loads API credentials from a `.env` file using `vlucas/phpdotenv`
- Credit card form with **brand logo detection**
- Input validation for numeric fields (card number, CVV, expiration)
- Example transaction request to Authorize.Net sandbox
- Clean structure for extension into production-ready apps

---

## Requirements
- PHP 7.4+  
- [Composer](https://getcomposer.org/)  
- Authorize.Net sandbox account  
- Git (for version control)

---

## Setup

1. Clone the repository:
   ```bash
   git clone https://github.com/blastheart1/authorize-net-sandbox.git
   cd authorize-net-sandbox

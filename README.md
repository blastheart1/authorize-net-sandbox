# 💳 Authorize.Net Sandbox Integration

This repository demonstrates how to integrate Authorize.Net payment gateway in PHP with sandbox credentials.  
It provides a simple form for capturing payment details and securely sending transactions using the Authorize.Net API.

---

## 🚀 Features
- 🔐 Securely load API keys from `.env`
- 🛠️ Sandbox-ready for safe testing
- 🖥️ Transaction modal for reviewing details
- ✅ Input validation (numbers only in card fields, no letters allowed)

---

## 📦 Installation
1. Clone the repository  
   ```bash
   git clone https://github.com/blastheart1/authorize-net-sandbox.git
   cd authorize-net-sandbox

## 🔑 Environment Variables

Make sure you set your credentials in .env (never commit these!):

AUTH_NET_API_LOGIN_ID=your_api_login_id
AUTH_NET_TRANSACTION_KEY=your_transaction_key

## 📝 Usage

- Open the form in your browser at `http://localhost:8000`  
- Enter card details (use Authorize.Net sandbox test cards)  
- Confirm transaction details in the modal before submitting 

## 🛡️ Security Notes

- ✅ API keys are hidden in `.env`  
- ✅ Client-side and server-side validation included  
- ✅ Numbers-only enforced on payment fields

📜 Changelog

- **v1.0.0** – Initial setup with Authorize.Net sandbox  
- **v1.1.0** – Added input validation to prevent letters in number fields ✨  

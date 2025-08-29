<?php
require 'vendor/autoload.php';

use Dotenv\Dotenv;
use net\authorize\api\contract\v1 as AnetAPI;
use net\authorize\api\controller as AnetController;

// Load .env
$dotenv = Dotenv::createImmutable(__DIR__);
$dotenv->safeLoad(); // use safeLoad() so it won't crash if .env is missing

$merchantAuthentication = new AnetAPI\MerchantAuthenticationType();
$merchantAuthentication->setName($_ENV['AUTH_NET_API_LOGIN_ID']);
$merchantAuthentication->setTransactionKey($_ENV['AUTH_NET_TRANSACTION_KEY']);


$responseMessage = "";

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $cardNumber = preg_replace('/\D/', '', $_POST['cardNumber']);
    $expDateInput = str_replace('/', '', $_POST['expDate']); // MMYY
    $cvv = $_POST['cvv'];
    $amount = str_replace([',','$'], '', $_POST['amount']); // remove formatting
    $description = $_POST['description'];

    $month = substr($expDateInput, 0, 2);
    $year = '20' . substr($expDateInput, 2, 2);
    $expDateFormatted = $year . '-' . $month;

    $refId = 'ref' . time();

    $creditCard = new AnetAPI\CreditCardType();
    $creditCard->setCardNumber($cardNumber);
    $creditCard->setExpirationDate($expDateFormatted);
    $creditCard->setCardCode($cvv);

    $payment = new AnetAPI\PaymentType();
    $payment->setCreditCard($creditCard);

    $order = new AnetAPI\OrderType();
    $order->setDescription($description);

    $transactionRequest = new AnetAPI\TransactionRequestType();
    $transactionRequest->setTransactionType("authCaptureTransaction");
    $transactionRequest->setAmount(floatval($amount));
    $transactionRequest->setPayment($payment);
    $transactionRequest->setOrder($order);

    $request = new AnetAPI\CreateTransactionRequest();
    $request->setMerchantAuthentication($merchantAuthentication);
    $request->setRefId($refId);
    $request->setTransactionRequest($transactionRequest);

    $controller = new AnetController\CreateTransactionController($request);
    $response = $controller->executeWithApiResponse(\net\authorize\api\constants\ANetEnvironment::SANDBOX);

    if ($response != null) {
        $tResponse = $response->getTransactionResponse();
        if ($tResponse != null && $tResponse->getResponseCode() == "1") {
            $responseMessage = "✅ Transaction Successful! Transaction ID: " . $tResponse->getTransId() . ", Auth Code: " . $tResponse->getAuthCode();
        } else {
            $responseMessage = "❌ Transaction Failed. ";
            if ($tResponse != null && $tResponse->getErrors() != null) {
                $responseMessage .= "Error: " . $tResponse->getErrors()[0]->getErrorText();
            }
        }
    } else {
        $responseMessage = "No response from Authorize.Net.";
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<title>Validated Sandbox Payment Form</title>
<style>
body { font-family: Arial, sans-serif; margin: 20px; display: flex; justify-content: center; }
.container { display: flex; max-width: 900px; width: 100%; gap: 40px; }
form { flex: 2; display: flex; flex-direction: column; gap: 8px; }
.label { font-weight: bold; margin-top: 10px; }
input, textarea, button { padding: 10px; font-size: 14px; width: 100%; box-sizing: border-box; }
input.invalid { border: 1px solid red; }
button { cursor: pointer; background-color: #4CAF50; color: white; border: none; margin-top: 15px; opacity: 0.6; }
button.enabled { opacity: 1; }
.error { color: red; font-size: 12px; margin-top: -5px; margin-bottom: 5px; }
.response { margin-top: 20px; font-weight: bold; text-align: center; }
.samples { flex: 1; border: 1px solid #ccc; padding: 10px; background: #f9f9f9; }
.samples h3 { margin-top: 0; }
.samples p { font-family: monospace; margin: 5px 0; }
</style>
<script>
let validFields = { cardNumber: false, expDate: false, cvv: false, amount: false };

function updateSubmitButton() {
    let btn = document.getElementById('submitBtn');
    btn.disabled = !Object.values(validFields).every(v => v);
    btn.className = Object.values(validFields).every(v => v) ? 'enabled' : '';
}

function detectCardType(input) {
    let val = input.value.replace(/\D/g,''); // strip spaces
    let type = 'Unknown';
    let maxLength = 16;

    if (val.length === 0) {
        type = 'Unknown';
    } else if (/^4/.test(val)) type='VISA';
    else if (/^5[1-5]/.test(val)) type='MasterCard';
    else if (/^3[47]/.test(val)) { type='AMEX'; maxLength=15; }
    else if (/^6(?:011|5)/.test(val)) type='Discover';

    document.getElementById('cardType').value = type;
    input.maxLength = maxLength;
    document.getElementById('cvv').maxLength = (type==='AMEX')?4:3;

    // Map type to icons
    const iconMap = {
        'VISA': 'icons/visa.png',
        'MasterCard': 'icons/mastercard.png',
        'AMEX': 'icons/amex.png',
        'Discover': 'icons/discover.png',
        'Unknown': 'icons/unknown.png'
    };

    // Always set an icon
    document.getElementById('cardIcon').src = iconMap[type] || iconMap['Unknown'];

    // Luhn check
    let sum = 0;
    let alt = false;
    for (let i = val.length - 1; i >= 0; i--){
        let n = parseInt(val[i]);
        if (alt) { n *= 2; if (n > 9) n -= 9; }
        sum += n;
        alt = !alt;
    }

    let isValid = (val.length === maxLength) && (sum % 10 === 0);
    validFields.cardNumber = isValid;
    document.getElementById('cardError').innerText = isValid ? '' : 'Invalid card number';
    input.className = isValid ? '' : 'invalid';
    updateSubmitButton();
}



function formatExpDate(input){
    let val=input.value.replace(/\D/g,'').slice(0,4);
    if(val.length>2){ input.value = val.slice(0,2)+'/'+val.slice(2); } else { input.value=val; }

    // Validate
    let parts = input.value.split('/');
    let isValid=false;
    if(parts.length===2){
        let mm=parseInt(parts[0],10), yy=parseInt(parts[1],10);
        let now=new Date(), year=now.getFullYear()%100, month=now.getMonth()+1;
        isValid = mm>=1 && mm<=12 && (yy>year || (yy===year && mm>=month));
    }
    validFields.expDate=isValid;
    document.getElementById('expError').innerText=isValid?'':'Invalid expiration';
    input.className=isValid?'':'invalid';
    updateSubmitButton();
}

function validateCVV(input){
    let val=input.value.replace(/\D/g,'');
    let type=document.getElementById('cardType').value;
    let max=(type==='AMEX')?4:3;
    let isValid=val.length===max;
    validFields.cvv=isValid;
    document.getElementById('cvvError').innerText=isValid?'':'Invalid CVV';
    input.className=isValid?'':'invalid';
    updateSubmitButton();
}

function formatAmount(input){
    let val = input.value.replace(/,/g,'').replace(/[^\d.]/g,'');
    if(val.includes('.')){
        let parts = val.split('.');
        parts[0]=parseInt(parts[0]).toLocaleString();
        val = parts.join('.');
    } else if(val.length>0){
        val=parseInt(val).toLocaleString();
    }
    input.value=val;
    validateAmount(input);
}

function validateAmount(input){
    let val=parseFloat(input.value.replace(/,/g,''));
    let isValid=!isNaN(val) && val>0;
    validFields.amount=isValid;
    document.getElementById('amtError').innerText=isValid?'':'Invalid amount';
    input.className=isValid?'':'invalid';
    updateSubmitButton();
}

window.addEventListener('DOMContentLoaded', () => {
    // Initialize validation for pre-filled sandbox values
    detectCardType(document.getElementById('cardNumber'));
    formatExpDate(document.getElementById('expDate'));
    validateCVV(document.getElementById('cvv'));
    formatAmount(document.getElementById('amount'));
});
function showModal(message){
    document.getElementById('modalMessage').innerText = message;
    document.getElementById('transactionModal').style.display = 'flex';
}

function closeModal(){
    document.getElementById('transactionModal').style.display = 'none';
}

</script>
</head>
<body>
<div class="container">
    <div class="samples">
        <h3>Sandbox Sample Cards</h3>
        <p>VISA: 4111111111111111</p>
        <p>MasterCard: 5500000000000004</p>
        <p>AMEX: 340000000000009</p>
        <p>Discover: 6011000000000012</p>
        <p>Expiration: Any future MM/YY</p>
        <p>CVV: 3 or 4 digits</p>
    </div>

    <form method="post">
        <label class="label">Card Number</label>
<div style="position: relative;">
    <input type="text" id="cardNumber" name="cardNumber" placeholder="4111111111111111" value="4111111111111111" oninput="detectCardType(this)">
    <img id="cardIcon" src="icons/unknown.png" alt="Card Type" style="position: absolute; right: 10px; top: 50%; transform: translateY(-50%); width: 40px; height: auto;">
</div>
<div id="cardError" class="error"></div>

        <label class="label">Card Type</label>
        <input type="text" id="cardType" disabled placeholder="Card Type">

        <label class="label">Expiration Date (MM/YY)</label>
        <input type="text" name="expDate" id="expDate" maxlength="5" value="12/26" oninput="formatExpDate(this)">
        <div id="expError" class="error"></div>

        <label class="label">CVV</label>
        <input type="text" id="cvv" name="cvv" maxlength="3" value="123" oninput="validateCVV(this)">
        <div id="cvvError" class="error"></div>

        <label class="label">Amount (USD)</label>
        <input type="text" name="amount" id="amount" value="5.00" oninput="formatAmount(this)">
        <div id="amtError" class="error"></div>

        <label class="label">Description</label>
        <textarea name="description">Sandbox Test</textarea>

        <button type="submit" id="submitBtn" disabled>Submit Payment</button>
    </form>
</div>

<!-- Transaction Modal -->
<div id="transactionModal" style="display:none; position:fixed; top:0; left:0; width:100%; height:100%; 
    background: rgba(0,0,0,0.5); justify-content:center; align-items:center; z-index:1000;">
    <div style="background:white; padding:20px; border-radius:8px; max-width:400px; text-align:center;">
        <p id="modalMessage"></p>
        <button onclick="closeModal()" style="
    padding:10px 20px; 
    margin-top:10px; 
    background-color:#4CAF50; 
    color:white; 
    border:none; 
    border-radius:5px; 
    cursor:pointer;
    opacity:1;
">
Okay
</button>
    </div>
</div>

<?php if($responseMessage !== ""): ?>
<script>
    // Ensure the functions exist before calling
    if(typeof showModal === 'function'){
        showModal("<?php echo addslashes($responseMessage); ?>");
    }
</script>
<?php endif; ?>

</body>

</html>

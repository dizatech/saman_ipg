## Payment Cycle
For a payment transaction we have to request a payment. If our request is successful the IPG will return a token with which we should guide the customer to the payment page via a POST request.
Customer will be redirected back to our desired URL(redirect address) from payment page via a POST request carrying data which may be used to check and verify the transaction.

### Instantiating an IPG object
for instantiating an IPG object we should call `Dizatech\SamanIpg\SamanIpg` constructor passing it an array of required arguments containing:
* terminal_id: your payment gateway terminal id
#### Code sample:
```php
$args = [
    'terminal_id'   => '123',
]; //Replace arguments with your gateway actual values 
$ipg = new Dizatech\SamanIpg\SamanIpg($args);
```
### Payment Request
For a payment transaction we should request a payment from IPG and acquire a token. This may be accomplished by calling `requestPayment` method. If the request is successful we can redirect our customer to the payment page with the acquired token.
#### Arguments:
* amount: payment amount in Rials
* order_id: unique order id
* redirect_url: URL to which customer may be redirected after payment
#### Returns:
An object with the following properties:
* status: `success` or `error`
* token: in case of a successful request contains a token with which we can redirect user to the payment page. This token may be used for further tracking the purchase request
* message: in case of an error contains a message describing the error
```php
$result = $ipg->requestPayment(
    order_id: 3,
    amount: 13000,
    redirect_url: 'http://myaddress.com/verify'
);
if ($result->status == 'success') {
    echo "<form method='post' action='https://sep.shaparak.ir/OnlinePG/OnlinePG' id='saman_redirect_form' style='display: none;'>
                <input type='text' name='token' value='{$result->token}'>
                <button type='submit'>Send</button>
            </form>
            <script>window.addEventListener('load', function () {document.getElementById('saman_redirect_form').submit()})</script>";
    exit;
}
```
## Payment verification
After payment the customer will be redirected back to the redirect address provided in payment request, via a POST request carrying all necessary data. Data fields sent by IPG are:
* State: a numeric code showing payment status. 0 means successful and 2 indicates a payment which has beeen verified before
* ResNum: the order id passed to IPG in first phase
* RefNum: Tracking number
* RRN: Reference number
If `ResCode` equals `0` or `ResCode` equals `2` and POST request contains a RefNum we can continue verifying the payment via calling the ipg `verify` method.
#### Arguments:
* amount: original payment amount sent to IPG in first phase
* ref_number: the RefNum returned by IPG
#### Returns:
* status: `success` or `error`
* ref_no: the RRN returned by IPG
* token: the RefNum returned by IPG. This RefNum (token) can be used later for interacting with the transcation. For example you may need it if you want to reverse the transaction.
* message: in case of an error contains an message describing the error
If `ResCode` equals `0` it means the transaction has been successful and verified. `ResCode = 2` means the transaction was successful but has already beeen verified. So in either case the transaction can be regarded as **successful**.
Other values for `ResCode` means the transaction has failed.
#### Code Sample:
```php
//Replace arguments with your gateway actual values 
$args = [
    'terminal_id'   => '123',
]; //Replace arguments with your gateway actual values 
$ipg = new Dizatech\SamanIpg\SamanIpg($args);

if (0 == $_REQUEST['ResCode'] && isset($_REQUEST['RefNum'])) {
    $result = $ipg->verify(13000, $_REQUEST['RefNum']);
    if ($result->status == 'success') {
        echo "Transaction sucessful! Reference Number: {$result->ref_no}";
    } else {
        echo "Transaction failed with error: {$result->error}";
    }
}
```
## Payment Reverse
In case we need to cancel customer's order immediately after payment (maximum 2 hours later) we can simply reverse the payment transaction which may result to full and instant refund to customer's bank account. For reversing transactions we can call `reverse` method.
#### Arguments:
* ref_number: the RefNum returned by IPG in case of successful payment verification
#### Returns:
* status: `success` or `error`
* ref_no: the RRN returned by IPG
* token: the RefNum returned by IPG
* message: in case of an error contains an message describing the error
#### Code Sample:
```php
//Replace arguments with your gateway actual values 
$args = [
    'terminal_id'   => '123',
]; //Replace arguments with your gateway actual values 
$ipg = new Dizatech\SamanIpg\SamanIpg($args);
$result = $ipg->reverse('123456789'); //Replace with atcual ref_num of the transaction
```

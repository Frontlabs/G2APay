# G2APay PHP Library

[![G2APay](https://www.g2a.co/build/img/logos/g2a-pay-f8d2f055c7.png)](https://pay.g2a.com)

Create and send payments using G2APay payment gateway.

### Installation

Run `composer require frontlabs/g2apay` to install through **Composer**.

### Usage

**NOTE:** If variables / parameters in following code examples looks confusing to you, you can take a look at official [G2APay Documentation](https://pay.g2a.com/documentation#method-2-variables).

#### Initialization

Initialize G2APay library and set required parameters:

```php
use G2APay\G2APay;

// Set required variables
$hash = 'your-api-hash'; // Get it from G2APay
$secret = 'your-api-secret'; // Get it from G2APay
$email = 'mail@example.com'; // Your G2APay store email
$success = 'http://example.com/success/'; // URL for successful callback;
$fail = 'http://example.com/failed/'; // URL for failed callback;
$order = 2234; // Choose your order id or invoice number, can be anything

// Optional
$currency = 'USD'; // Pass currency, if no given will use "USD"

// Create payment instance
$payment = new G2APay($hash, $secret, $email, $success, $fail, $order, $currency);
```

#### Adding Items

Add item to your payment:

```php
// Set item parameters
$sku = 1; // Item number (In most cases $sku can be same as $id)
$name = 'My Game';
$quantity = 1; // Must be integer
$id = 1; // Your items' identifier
$price = 9.95; // Must be float
$url = 'http://example.com/my-game/';

// Optional
$extra = '';
$type = '';

// Add item to payment
$payment->addItem($sku, $name, $quantity, $id, $price, $url, $extra, $type);

```

You can chain methods, and add multiple items:

```php
$payment->addItem(114, 'Game', 1, 114, 9.95, 'http://example.com/i/game/')
        ->addItem(115, 'Gift', 2, 115, 1.50, 'http://example.com/i/gift/')
        ->addItem(116, 'Key', 1, 116, 9.50, 'http://example.com/i/key/');
```

#### Finalizing Payment

Create payment and send it to G2APay:

```php
$orderId = 1; // Generate or save in your database
$extras = []; // Optional extras passed to order (Please refer G2APay docs)
// Create payment against G2APay
$response = $payment->createOrder($orderId, $extras);

// Or if you want to create sandbox payment (for testing only)
$response = $payment->test()->createOrder($orderId, $extras);
```

There can be two responses:
```
// Success
[
    'success' => true,
    'url' => 'https://checkout.pay.g2a.com/index/gateway?token=abc123'
]

// Fail
[
    'success' => false,
    'message' => 'Generic error message.',
]
```

#### Redirecting to G2APay site

After recieving `$response` you can now check if it was successful and redirect user to recieved G2APay website URL.

Simple code example:
```php
// Check if successful
if ($response['success']) {
    header('Location: '.$response['url']); // redirect
} else {
    echo $response['message']; // print out error message
}
```

#### Callback

**TODO:** When user successfuly pays or cancels, G2APay will redirect you back to your previously given callback urls.

### License

MIT

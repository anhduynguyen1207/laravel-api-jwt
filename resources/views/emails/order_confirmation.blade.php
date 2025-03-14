<!DOCTYPE html>
<html>
<head>
    <title>Order Confirmation</title>
</head>
<body>
    <h1>Thank you for your order, {{ $order['name'] }}!</h1>
    <p>Your order ID is: {{ $order['id'] }}</p>
    <p>Order Total: ${{ $order['total'] }}</p>
    <p>We will notify you once your order is shipped.</p>
</body>
</html>

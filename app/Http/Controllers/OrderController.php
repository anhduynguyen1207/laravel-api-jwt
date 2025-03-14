<?php

namespace App\Http\Controllers;

use App\Jobs\SendOrderConfirmationEmail;
use App\Mail\OrderConfirmationMail;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Mail;

class OrderController extends Controller
{
    public function placeOrder(Request $request)
    {
        // Giả sử đơn hàng được lưu vào database
        $order = [
            'id' => rand(1000, 9999),
            'name' => 'John Doe',
            'email' => 'nguyenanhduy.dev@gmail.com',
            'total' => 99.99
        ];

        // Gửi email xác nhận đơn hàng
        // Mail::to($order['email'])->send(new OrderConfirmationMail($order));

        SendOrderConfirmationEmail::dispatch($order);

        return response()->json([
            'message' => 'Order placed! Email will be sent shortly.',
            'order' => $order
        ], 201);
    }
}

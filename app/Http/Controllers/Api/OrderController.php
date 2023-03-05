<?php

namespace App\Http\Controllers\Api;

use App\Models\Api\Order;
use App\Models\Api\Product;
use Illuminate\Http\Request;
use App\Models\Api\OrderProduct;
use App\Helpers\ResponseFormatter;
use App\Http\Controllers\Controller;
use Illuminate\Database\QueryException;
use Illuminate\Support\Facades\Validator;

class OrderController extends Controller
{
    public function index()
    {
        try {
            $search = request()->search;
            $data = Order::when(request()->search !== null, function ($query) use ($search) {
                $query->where('code', 'LIKE', "%{$search}%");
            })->with('order_product.product')->get();

            // if data kosong
            if ($data->count() == 0) {
                $data = [];
            }

            return ResponseFormatter::success($data, "success");
        } catch (QueryException $th) {
            return ResponseFormatter::error($th, "Ups Something Wrong");
        }
    }

    public function store()
    {
        $validation = Validator::make(request()->all(), [
            'cashier_name' => 'required',
            'customer_name' => 'required',
            'total' => 'required',
            'pay' => 'required',
            'change' => 'required',
        ]);

        if ($validation->fails()) return ResponseFormatter::error($validation->errors(), "Error Validation", 422);

        try {

            $store = Order::create([
                'code' => 'OR' . time(),
                'cashier_name' => request()->cashier_name,
                'customer_name' => request()->customer_name,
                'total' => request()->total,
                'pay' => request()->pay,
                'change' => request()->change,
            ]);

            $product_id = request()->product_id;
            foreach ($product_id as $key => $value) {
                OrderProduct::create([
                    'order_id' => $store->id,
                    'product_id' => $value,
                    'qty' => request()->qty[$key],
                    'price' => request()->price[$key],
                    'total' => request()->total_price[$key],
                ]);

                // kurangi product
                $product = Product::find($value);
                $product->update([
                    'stock' => $product->stock - request()->qty[$key]
                ]);
            }

            return ResponseFormatter::success($store, "Store success");
        } catch (QueryException $error) {
            return ResponseFormatter::error($error, "Ups Something Wrong");
        }
    }

    public function update($id)
    {
        $validation = Validator::make(request()->all(), [
            'cashier_name' => 'required',
            'customer_name' => 'required',
            'total' => 'required',
            'pay' => 'required',
            'change' => 'required',
        ]);

        if ($validation->fails()) return ResponseFormatter::error($validation->errors(), "Error Validation", 422);

        try {
            $order = Order::find($id);
            if ($order == null) {
                return ResponseFormatter::error([], "Order not Found", 422);
            }

            $store = $order->update([
                'cashier_name' => request()->cashier_name,
                'customer_name' => request()->customer_name,
                'total' => request()->total,
                'pay' => request()->pay,
                'change' => request()->change,
            ]);


            OrderProduct::where('order_id', $id)->delete();
            $product_id = request()->product_id;
            foreach ($product_id as $key => $value) {
                OrderProduct::create([
                    'order_id' => $order->id,
                    'product_id' => $value,
                    'qty' => request()->qty[$key],
                    'price' => request()->price[$key],
                    'total' => request()->total[$key],
                ]);
            }

            return ResponseFormatter::success($order, "Update success");
        } catch (QueryException $error) {
            return ResponseFormatter::error($error, "Ups Something Wrong");
        }
    }

    public function delete($id)
    {
        try {
            $order = Order::find($id);
            if ($order == null) {
                return ResponseFormatter::error([], "Order not Found", 422);
            }

            OrderProduct::where('order_id', $id)->delete();
            $order->delete();

            return ResponseFormatter::success($order, "Delete success", 422);
        } catch (QueryException $error) {
            return ResponseFormatter::error($error, "Ups Something Wrong");
        }
    }
}

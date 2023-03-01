<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Database\QueryException;
use App\Helpers\ResponseFormatter;
use App\Models\Api\Product;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Validator;

class ProductController extends Controller
{
    public function list()
    {
        try {
            $data = Product::when(request()->search !== null, function ($query) {
                $query->where('name', request()->search);
            })->get();

            // if data kosong
            if ($data->count() == 0) {
                $data = [];
            }

            return ResponseFormatter::success($data, "success");
        } catch (QueryException $error) {
            return ResponseFormatter::error($error, "Ups Something Wrong");
        }
    }

    public function store()
    {
        $validation = Validator::make(request()->all(), [
            'name' => 'required',
            'type' => 'required',
            'brand' => 'required',
            'stock' => 'required|numeric',
            'price' => 'required|numeric',
        ]);

        if ($validation->fails()) return ResponseFormatter::error($validation->errors(), "Error Validation", 422);

        try {
            $image_name = null;
            if (request()->file('image') != null || request()->file('image') != "") {
                $image = request()->file('image');
                $image_name = $image->hashName();
                $image->storeAs('public/products', $image_name);
            }

            $store = Product::create([
                'name' => request()->name,
                'image' => $image_name,
                'type' => request()->type,
                'brand' => request()->brand,
                'stock' => request()->stock,
                'price' => request()->price,
            ]);

            return ResponseFormatter::success($store, "Store success");
        } catch (QueryException $error) {
            return ResponseFormatter::error($error, "Ups Something Wrong");
        }
    }

    public function update($id)
    {
        $validation = Validator::make(request()->all(), [
            'name' => 'required',
            'type' => 'required',
            'brand' => 'required',
            'stock' => 'required|numeric',
            'price' => 'required|numeric',
        ]);

        if ($validation->fails()) return ResponseFormatter::error($validation->errors(), "Error Validation", 422);

        try {
            $product = Product::find($id);
            if ($product == null) {
                return ResponseFormatter::error([], "Product not Found", 422);
            }

            if (request()->file('image') != null || request()->file('image') != "") {
                // hapus gambar
                if (file_exists(public_path('storage/products/' . $product->image)) == true) {
                    Storage::disk('local')->delete('public/products/' . $product->image);
                }

                $image = request()->file('image');
                $image_name = $image->hashName();
                $image->storeAs('public/products', $image_name);
            } else {
                $image_name = $product->image;
            }

            $store = $product->update([
                'name' => request()->name,
                'image' => $image_name,
                'type' => request()->type,
                'brand' => request()->brand,
                'stock' => request()->stock,
                'price' => request()->price,
            ]);

            return ResponseFormatter::success($product, "Update success");
        } catch (QueryException $error) {
            return ResponseFormatter::error($error, "Ups Something Wrong");
        }
    }

    public function delete($id)
    {
        try {
            $product = Product::find($id);
            if ($product == null) {
                return ResponseFormatter::error([], "Product not Found", 422);
            }

            // hapus gambar
            if (file_exists(public_path('storage/products/' . $product->image)) == true) {
                Storage::disk('local')->delete('public/products/' . $product->image);
            }

            $product->delete();

            return ResponseFormatter::error($product, "Delete success", 422);
        } catch (QueryException $error) {
            return ResponseFormatter::error($error, "Ups Something Wrong");
        }
    }
}

<?php

namespace App\Http\Controllers\Api;

use App\Models\Api\Product;
use Illuminate\Support\Str;
use App\Helpers\ResponseFormatter;
use App\Http\Controllers\Controller;
use Illuminate\Database\QueryException;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Validator;

class ProductController extends Controller
{
    public function list()
    {
        try {
            $search = request()->search;
            $data = Product::when($search != null, function ($query) use ($search) {
                $query->where('name', 'LIKE', "%{$search}%");
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
                $image_name = $this->uploadImage($image);
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
                // dapatkan nama image
                $image = explode('/', $product->image);
                $image_delete_str = $image[3] . '/' . $image[4];

                // hapus gambar
                if (file_exists($image_delete_str) == true) {
                    unlink($image_delete_str);
                }

                $image_file = request()->file('image');
                $image_name = $this->uploadImage($image_file);
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

            // dapatkan nama image
            $image = explode('/', $product->image);
            $image_delete_str = $image[3] . '/' . $image[4];

            // hapus gambar
            if (file_exists($image_delete_str) == true) {
                unlink($image_delete_str);
            }

            $product->delete();

            return ResponseFormatter::success($product, "Delete success");
        } catch (QueryException $error) {
            return ResponseFormatter::error($error, "Ups Something Wrong");
        }
    }

    public function uploadImage($gambar)
    {
        $photo = base64_encode(file_get_contents($gambar->path()));

        define("PRODUCT_PATH", 'products');
        $data = base64_decode($photo);
        $file = PRODUCT_PATH . '/' . 'product-' . uniqid() . '.png';

        if (is_dir(public_path('products')) == false) {
            mkdir(public_path('products'));
        }

        // simpan gambar
        file_put_contents($file, $data);

        $file_image_name = url('') . '/' . $file;

        return $file_image_name;
    }
}

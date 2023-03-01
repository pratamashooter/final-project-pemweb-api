<?php

namespace App\Http\Controllers;

use App\Helpers\ResponseFormatter;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Foundation\Bus\DispatchesJobs;
use Illuminate\Foundation\Validation\ValidatesRequests;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller as BaseController;

class Controller extends BaseController
{
    use AuthorizesRequests, DispatchesJobs, ValidatesRequests;

    protected $request;

    public function __construct(Request $request)
    {
        $this->request = $request;
    }

    public function respondSuccess($data = null, $message = null)
    {
        return ResponseFormatter::success($data, $message);
    }

    public function respondError($data = null, $message = null, $code = null)
    {
        return ResponseFormatter::error($data, $message, $code);
    }

    protected function perPage($default = 20)
    {
        $perPage = (int) $this->request->input('per_page');

        return $perPage > 0 && $perPage <= 100 ? $perPage : $default;
    }
}

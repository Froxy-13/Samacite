<?php

namespace App\Http\Controllers;

use App\Http\Responses\BaseResponse;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Foundation\Validation\ValidatesRequests;
use Illuminate\Routing\Controller as LaravelController;

class BaseController extends LaravelController
{
    use AuthorizesRequests, ValidatesRequests;

    /**
     * Success response
     */
    protected function success($data = null, $message = 'Success', $statusCode = 200)
    {
        return BaseResponse::success($data, $message, $statusCode);
    }

    /**
     * Error response
     */
    protected function error($message = 'Error', $statusCode = 400, $data = null)
    {
        return BaseResponse::error($message, $statusCode, $data);
    }

    /**
     * Validation error response
     */
    protected function validationError($errors, $message = 'Validation failed')
    {
        return BaseResponse::validationError($errors, $message);
    }

    /**
     * Unauthorized response
     */
    protected function unauthorized($message = 'Unauthorized')
    {
        return BaseResponse::unauthorized($message);
    }

    /**
     * Forbidden response
     */
    protected function forbidden($message = 'Forbidden')
    {
        return BaseResponse::forbidden($message);
    }

    /**
     * Not found response
     */
    protected function notFound($message = 'Not found')
    {
        return BaseResponse::notFound($message);
    }
}

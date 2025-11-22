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
    protected function success($data = null, $message = 'Succès', $statusCode = 200)
    {
        return BaseResponse::success($data, $message, $statusCode);
    }

    /**
     * Error response
     */
    protected function error($message = 'Erreur', $statusCode = 400, $data = null)
    {
        return BaseResponse::error($message, $statusCode, $data);
    }

    /**
     * Validation error response
     */
    protected function validationError($errors, $message = 'Erreur de validation')
    {
        return BaseResponse::validationError($errors, $message);
    }

    /**
     * Unauthorized response
     */
    protected function unauthorized($message = 'Non autorisé')
    {
        return BaseResponse::unauthorized($message);
    }

    /**
     * Forbidden response
     */
    protected function forbidden($message = 'Accès interdit')
    {
        return BaseResponse::forbidden($message);
    }

    /**
     * Not found response
     */
    protected function notFound($message = 'Non trouvé')
    {
        return BaseResponse::notFound($message);
    }
}

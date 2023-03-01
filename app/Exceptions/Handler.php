<?php

namespace App\Exceptions;

use App\Helpers\ResponseFormatter;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Auth\AuthenticationException;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Foundation\Exceptions\Handler as ExceptionHandler;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\ItemNotFoundException;
use Illuminate\Validation\ValidationException;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;
use Symfony\Component\HttpKernel\Exception\HttpException;
use Symfony\Component\HttpKernel\Exception\MethodNotAllowedHttpException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Throwable;

class Handler extends ExceptionHandler
{
    protected $dontReport = [
        AuthorizationException::class,
        AuthenticationException::class,
        HttpException::class,
        ModelNotFoundException::class,
        ValidationException::class,
    ];

    public function report(Throwable $exception)
    {
        parent::report($exception);
    }

    public function render($request, Throwable $e)
    {
        // 400
        if ($e instanceof BadRequestHttpException) {
            return $this->handle($e, $request, $this->renderBadRequestHttpException($e));
        }

        // 401
        if ($e instanceof AuthenticationException) {
            return $this->handle($e, $request, $this->renderAuthenticationException());
        }

        // 403
        if ($e instanceof AuthorizationException) {
            return $this->handle($e, $request, $this->renderAuthorizationException($e));
        }

        // 404
        if ($e instanceof NotFoundHttpException || $e instanceof ModelNotFoundException || $e instanceof ItemNotFoundException) {
            return $this->handle($e, $request, $this->renderNotFoundHttpException());
        }

        // 405
        if ($e instanceof MethodNotAllowedHttpException) {
            return $this->handle($e, $request, $this->renderMethodNotAllowedHttpException());
        }

        // 422
        if ($e instanceof ValidationException) {
            return $this->handle($e, $request, $this->renderValidationException($e));
        }

        return $this->handle($e, $request, $this->renderDefaultException($e));
    }

    private function renderDefaultException(Throwable $e)
    {
        // Symfony's HTTP exception have this method, we don't need to test it tho
        $statusCode = Response::HTTP_INTERNAL_SERVER_ERROR;

        if (method_exists($e, 'getStatusCode') && $e->getStatusCode() !== null) {
            // @codeCoverageIgnoreStart
            $statusCode = $e->getStatusCode();
            // @codeCoverageIgnoreEnd
        }

        return ResponseFormatter::error(null, $e->getMessage(), $statusCode);
    }

    private function renderBadRequestHttpException(BadRequestHttpException $e)
    {
        return ResponseFormatter::error(null, $e->getMessage() ?: 'It\'s not me, it\'s you!', Response::HTTP_BAD_REQUEST);
    }

    private function renderAuthenticationException()
    {
        return ResponseFormatter::error(null, 'I\'m sorry, but I don\'t know you.', Response::HTTP_UNAUTHORIZED);
    }

    private function renderAuthorizationException(AuthorizationException $e)
    {
        return ResponseFormatter::error(null, $e->getMessage(), Response::HTTP_FORBIDDEN);
    }

    private function renderMethodNotAllowedHttpException()
    {
        return ResponseFormatter::error(null, 'Hey! Watch your tone!', Response::HTTP_METHOD_NOT_ALLOWED);
    }

    private function renderNotFoundHttpException()
    {
        return ResponseFormatter::error(null, 'Are you lost?', Response::HTTP_NOT_FOUND);
    }

    private function renderValidationException(ValidationException $e)
    {
        $errors = [];

        foreach ($e->errors() as $key => $error) {
            foreach ($error as $err) {
                $errors[] = [
                    'code' => 0,
                    'message' => $err,
                    'field' => $key,
                ];
            }
        }

        return ResponseFormatter::error($errors, $e->getMessage(), Response::HTTP_UNPROCESSABLE_ENTITY);
    }

    /** @codeCoverageIgnore */
    private function handle(Throwable $e, Request $request, JsonResponse $response)
    {
        if (config('app.debug')) {
            $data = $response->getData();

            $data->request = [
                'url' => $request->fullUrl(),
                'headers' => $request->header(),
                'payload' => $request->json()->all(),
            ];
            $data->trace = explode(PHP_EOL, $e);

            $response->setData($data);
        }

        return $response;
    }
}

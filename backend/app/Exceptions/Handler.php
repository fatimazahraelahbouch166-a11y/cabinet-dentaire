<?php

namespace App\Exceptions;

use Illuminate\Auth\AuthenticationException;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Foundation\Exceptions\Handler as ExceptionHandler;
use Illuminate\Validation\ValidationException;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Throwable;

class Handler extends ExceptionHandler
{
    protected $dontFlash = ['current_password', 'password', 'password_confirmation'];

    public function register(): void {}

    public function render($request, Throwable $e)
    {
        if ($request->is('api/*') || $request->expectsJson()) {
            if ($e instanceof ValidationException) {
                return response()->json([
                    'success' => false,
                    'message' => 'Données invalides.',
                    'errors'  => $e->errors(),
                ], 422);
            }
            if ($e instanceof AuthenticationException) {
                return response()->json(['success' => false, 'message' => 'Non authentifié.'], 401);
            }
            if ($e instanceof AccessDeniedHttpException) {
                return response()->json(['success' => false, 'message' => 'Accès refusé.'], 403);
            }
            if ($e instanceof ModelNotFoundException || $e instanceof NotFoundHttpException) {
                return response()->json(['success' => false, 'message' => 'Ressource introuvable.'], 404);
            }
            return response()->json([
                'success' => false,
                'message' => config('app.debug') ? $e->getMessage() : 'Erreur serveur.',
            ], 500);
        }

        return parent::render($request, $e);
    }
}
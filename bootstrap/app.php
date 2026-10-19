<?php

use App\Http\Middleware\EnsureUserHasRole;
use App\Http\Middleware\EnsureUserIsNotArchived;
use App\Support\RoleHome;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Auth\AuthenticationException;
use Illuminate\Database\UniqueConstraintViolationException;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;
use Symfony\Component\HttpKernel\Exception\HttpExceptionInterface;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware): void {
        $middleware->trustProxies(at: '*');

        $middleware->redirectUsersTo(function (Request $request): string {
            $user = $request->user();

            return $user !== null
                ? RoleHome::path($user)
                : '/';
        });

        $middleware->alias([
            'role' => EnsureUserHasRole::class,
            'not_archived' => EnsureUserIsNotArchived::class,
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        $exceptions->shouldRenderJsonWhen(
            fn (Request $request) => $request->is('api/*') || $request->expectsJson(),
        );

        $exceptions->render(function (UniqueConstraintViolationException $exception, Request $request) {
            $message = 'This record could not be saved. Please try again.';

            if ($request->expectsJson() || $request->is('api/*')) {
                return response()->json(['message' => $message], 409);
            }

            if (in_array($request->method(), ['POST', 'PUT', 'PATCH', 'DELETE'], true)) {
                return back()
                    ->withInput()
                    ->withErrors(['form' => $message]);
            }

            return null;
        });

        $exceptions->render(function (Throwable $exception, Request $request) {
            if (! $request->expectsJson() && ! $request->is('api/*')) {
                return null;
            }

            if ($exception instanceof ValidationException
                || $exception instanceof AuthenticationException
                || $exception instanceof AuthorizationException
                || $exception instanceof HttpExceptionInterface) {
                return null;
            }

            $message = config('app.debug')
                ? ($exception->getMessage() !== '' ? $exception->getMessage() : 'Something went wrong. Please try again.')
                : 'Something went wrong. Please try again.';

            return response()->json(['message' => $message], 500);
        });
    })->create();

<?php

use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware): void {
        $middleware->alias([
            'role'=> \Spatie\Permission\Middleware\RoleMiddleWare::class,
            'permission'=> \Spatie\Permission\MiddleWare\PermissionMiddleWare::class,
            'role or permission'=> \Spatie\Permission\MiddleWare\RoleOrPermissionMiddleWare::class,
        ]);
    })    
    ->withExceptions(function (Exceptions $exceptions): void {
        //
    })->create();

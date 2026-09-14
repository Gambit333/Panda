<?php

if (! function_exists('active')) {
    function active(string $segment): string
    {
        $route = request()->route()?->getName() ?? '';

        return str_starts_with($route, $segment) ? 'active' : '';
    }
}

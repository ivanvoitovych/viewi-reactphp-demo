<?php

namespace App\Middleware;

use React\Http\Message\Response;

class StaticFilesMiddleware
{
    private string $directory;

    public function __construct(string $directory)
    {
        $this->directory = $directory;
    }

    public function __invoke(\Psr\Http\Message\ServerRequestInterface $request, callable $next)
    {
        $filePath = $request->getUri()->getPath();
        $file = $this->directory . $filePath;
        if (file_exists($file) && !is_dir($file)) {
            $fileExt = pathinfo($file, PATHINFO_EXTENSION);
            $contentType = 'text/text';
            switch ($fileExt) {
                case 'js': {
                        $contentType = 'application/javascript';
                        break;
                    }
                case 'json': {
                        $contentType = 'application/json';
                        break;
                    }
                case 'css': {
                        $contentType = 'text/css';
                        break;
                    }
                case 'ico': {
                        $contentType = 'image/x-icon';
                        break;
                    }
            }
            return new Response(200, ['Content-Type' => $contentType], file_get_contents($file));
        }
        return $next($request);
    }
}

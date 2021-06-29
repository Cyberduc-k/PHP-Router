<?php

/// Class which contains information about a request
class Request {
    private string $path;
    private string $method;

    public function __construct(?string $uri = null) {
        $parsed_url = parse_url($uri ?? $_SERVER['REQUEST_URI']);

        if (isset($parsed_url['path']))
            $this->path = $parsed_url['path'];
        else
            $this->path = "/";

        $this->method = $_SERVER['REQUEST_METHOD'];
    }

    public function method(): string {
        return $this->method;
    }

    public function path(): string {
        return $this->path;
    }
}

?>

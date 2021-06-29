<?php

/*
 * A trie data structure to more efficiently represent registered routes with common prefixes.
 * This also increases the effeciency of matching routes.
 * 
 * For example two routes like this:
 * /user/create
 * /user/edit
 * 
 * Are represented like this:
 * /user /create
 *       /edit
 *
 * Where /create and /edit are both children of the /user route.
 *
 * This is better because each segment only has to be registered and matched once
 * and parameters are extracted automatically.
 */
class Route {
    private RouteData $data;
    private array $children;
    private ?Closure $handler;

    private function __construct(RouteData $data) {
        $this->data = $data;
        $this->children = [];
        $this->handler = null;
    }

    public static function base(): self {
        return new Route(RouteData::Root());
    }

    public function add(array $parts, callable $handler, array &$params) {
        if (!is_null($part = array_shift($parts))) {
            if ($part instanceof RouteDataOpt) {
                $this->add($parts, $handler, $params);

                $params[$part->name] ??= [];
                $params[$part->name][] = &$part;
            }

            if ($part instanceof RouteDataParam) {
                $params[$part->name] ??= [];
                $params[$part->name][] = &$part;
            }

            if (!is_null($child = array_find($this->children, function($c) use($part) { return $c->data->equals($part); }))) {
                $child->add($parts, $handler, $params);
            } else {
                $this->children[] = new Route($part);
                $this->children[count($this->children) - 1]->add($parts, $handler, $params);
            }
        } else {
            $this->handler = $handler;
        }
    }

    public function matches(array $parts, array &$params): ?Closure {
        if (!is_null($part = array_shift($parts))) {
            if (!is_null($child = array_find($this->children, function($c) use($part, &$params) {
                return $c->data->matches($part, $params);
            }))) {
                return $child->matches($parts, $params);
            } else {
                return null;
            }
        } else {
            return $this->handler;
        }
    }
}

function array_find(array $array, callable $by): ?object {
    foreach ($array as $struct) {
        if ($by($struct)) {
            return $struct;
        }
    }

    return null;
}

function starts_with(string $whole, string $sub): bool {
    return substr($whole, 0, strlen($sub)) == $sub;
}

function ends_with(string $whole, string $sub): bool {
    return substr($whole, -strlen($sub)) == $sub;
}

/*
 * Enumeration type for different kinds of route segments
 * such as exact matches, parameters and optional parameters.
 */
abstract class RouteData {
    public static function Root(): self {
        return new RouteDataRoot();
    }

    public static function Exact(string $value): self {
        return new RouteDataExact($value);
    }

    public static function Param(string $name): self {
        return new RouteDataParam($name);
    }

    public static function Opt(string $name): self {
        return new RouteDataOpt($name);
    }

    public static function new(string $part): self {
        if ($part == "") {
            return RouteData::Exact("/");
        } else if (starts_with($part, "<") && ends_with($part, ">")) {
            $name = substr($part, 1, strlen($part) - 2);

            if (ends_with($name, "?")) {
                return RouteData::Opt(substr($name, 0, strlen($name) - 1));
            } else {
                return RouteData::Param($name);
            }
        } else {
            return RouteData::Exact($part);
        }
    }

    public abstract function matches(string $part, array &$params): bool;

    function equals($other): bool {
        return $this === $other;
    }
}

class RouteDataRoot extends RouteData {
    public function matches(string $part, array &$params): bool {
        die("This code should be unreachable");
    }
}

class RouteDataExact extends RouteData {
    public string $value;

    public function __construct(string $value) {
        $this->value = $value;
    }

    public function matches(string $part, array &$params): bool {
        if ($this->value == "/") {
            return $part == "/" || $part == "";
        } else {
            return $part == $this->value;
        }
    }

    function equals($other): bool {
        return get_class($this) == get_class($other) && $this->value == $other->value;
    }
}

class RouteDataParam extends RouteData {
    public string $name;
    public ?string $regex = null;

    public function __construct(string $name) {
        $this->name = $name;
    }

    public function matches(string $part, array &$params): bool {
        if (!is_null($this->regex) && !preg_match($this->regex, $part)) {
            return false;
        }

        $params[$this->name] = $part;
        return true;
    }

    function equals($other): bool {
        return get_class($this) == get_class($other) && $this->name == $other->name;
    }
}

class RouteDataOpt extends RouteData {
    public string $name;
    public ?string $regex = null;

    public function __construct(string $name) {
        $this->name = $name;
    }

    public function matches(string $part, array &$params): bool {
        if (!is_null($this->regex) && !preg_match($this->regex, $part)) {
            return false;
        }

        $params[$this->name] = $part;
        return true;
    }

    function equals($other): bool {
        return get_class($this) == get_class($other) && $this->name == $other->name;
    }
}

?>

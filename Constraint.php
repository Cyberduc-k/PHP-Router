<?php

/// Constrain routing parameters using regex
class RouteConstraint {
    private array $params;

    function __construct($params) {
        $this->params = $params;
    }

    /// Add a regex constraint to paramater with name $name
    public function where(string $name, string $regex): self {
        $regex = '/^'.str_replace('/', '\/', $regex).'$/';

        if (isset($this->params[$name])) {
            foreach ($this->params[$name] as &$param) {
                $param->regex = $regex;
            }
        } else {
            die("route does not contain parameter &lt;$name&gt;");
        }

        return $this;
    }

    /// Constrain the paramater to words
    public function word(string $name): self {
        return $this->where($name, '\w+');
    }

    /// Constrain the paramter to nubmers
    public function number(string $name): self {
        return $this->where($name, '\d+');
    }
}

?>

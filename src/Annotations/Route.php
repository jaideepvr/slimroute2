<?php

namespace Gvs\SlimRoute2\Annotations;

/**
 * @Annotation
 * @Target({"METHOD"})
 */
class Route {

    /** @Required */
    public $path;

    /** @var({"GET", "POST", "DELETE", "PUT"}) */
    public $method;
    
    public $name;

}

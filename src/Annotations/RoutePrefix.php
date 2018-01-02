<?php

namespace Gvs\SlimRoute2\Annotations;

/**
 * @Annotation
 * @Target({"CLASS"})
 */
class RoutePrefix {

    /** @Required */
    public $prefix;
    
    public $nameSpace;

}

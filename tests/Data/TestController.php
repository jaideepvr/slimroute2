<?php

use \Gvs\SlimRoute2\Annotations\RoutePrefix;
use \Gvs\SlimRoute2\Annotations\Route;

/**
 * @RoutePrefix(prefix="/api/test")
 */
class TestController {

    /**
     * @Route(path="route1",method="GET")
     */
    public function testFunction1() {
        $i = 1;
        return $i;
    }

}

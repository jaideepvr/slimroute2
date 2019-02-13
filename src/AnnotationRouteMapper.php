<?php

namespace Gvs\SlimRoute2;

use Doctrine\Common\Annotations as Annotations;    
use Doctrine\Common\Annotations\AnnotationReader;
use Phramz\Doctrine\Annotation\Scanner\ClassFileInfo;
use Phramz\Doctrine\Annotation\Scanner\Scanner;

use \Slim\Router;
use \Gvs\SlimRoute2\Annotations\Route as Route;
use \Gvs\SlimRoute2\Annotations\RoutePrefix as RoutePrefix;

require_once( __DIR__ . '/Annotations/Route.php');
require_once( __DIR__ . '/Annotations/RoutePrefix.php');

class AnnotationRouteMapper {

    /**
     * Primary static method to parse controller files in the given folders and
     * add them to the Slim application
     */
    public static function loadAnnotations($folderPaths, $app, $cacheFile = false) {
        $routeMapInfo = [];
        $cacheAnnotations = is_string($cacheFile);
        
        if ($cacheAnnotations & is_readable($cacheFile)) {
            $routeMapInfo = json_decode(file_get_contents($cacheFile));
        } else {
            if ($cacheAnnotations && !file_exists($cacheFile)) {
                if (!is_writable(dirname($cacheFile))) {
                    throw new \Exception("Insufficient permissions to save to the supplied cache file.");
                }
            }
            
            $routeMapInfo = AnnotationRouteMapper::parseAndGetAnnotations($folderPaths);
            if ($cacheAnnotations) {
                $myfile = fopen($cacheFile, "w") or die("Unable to open file!");
                fwrite($myfile, json_encode($routeMapInfo));
                fclose($myfile);
            }
        }

        for ($idx = 0; $idx < sizeof($routeMapInfo); $idx++) {
            $routeMap = $routeMapInfo[$idx];
            $route = $app->map([$routeMap->Method], $routeMap->Route, [$routeMap->NameSpace, $routeMap->MethodName]);
            if (isset($routeMap->Middleware) && ($routeMap->Middleware != "")) {
                $route->add($routeMap->Middleware);
            }
        }
    }
    
    /**
     * Scans the gievn file for the routing annotations, builds the map info, and returns
     * an array of all the route with their mapping
     */
    private static function scanFile($file) {
        $routePrefix = null;
        $className = $file->getClassName();
        $routeMapInfo = [];
        
        foreach ($file->getClassAnnotations() as $classAnnotation) {
            $routePrefix = $classAnnotation->prefix;
            $nameSpace = $classAnnotation->nameSpace;
        }
        
        foreach ($file->getMethodAnnotations() as $methodName => $methodAnnotations) {
            if (sizeof($methodAnnotations) == 0) {
                continue;
            }
            foreach ($methodAnnotations as $methodAnnotation) {
                $routeMap = new \stdClass;

                $routeMap->Method = $methodAnnotation->method;
                $routeMap->Route = AnnotationRouteMapper::prepareRoutePath($routePrefix, $methodAnnotation->path);
                $routeMap->NameSpace = $nameSpace;
                $routeMap->MethodName = isset($methodAnnotation->name) ? $methodAnnotation->name : $methodName;
                $routeMap->Middleware = $methodAnnotation->middleware;
                
                array_push($routeMapInfo, $routeMap);
            }
        }

        return $routeMapInfo;
    }
    
    /**
     * Prepares the route path given the prefix and the method level defined path
     */
    private static function prepareRoutePath($prefix, $givenPath) {
        if ($givenPath[0] == '/') {
            $path = $$givenPath;
        } else {
            $lastChar = substr($prefix, -1);
            
            if ($lastChar == '/') {
                $path = "{$prefix}{$givenPath}";    
            } else {
                $path = "{$prefix}/{$givenPath}";
            }
        }
        return $path;
    }
    
    /**
     * Parse and retrieve the route annotations from each file in the given folder path
     */
    private static function parseAndGetAnnotations($folderPaths) {
        $annotations = [
            Route::class,
            RoutePrefix::class
        ];
        $routeMapInfo = [];
        $parser = new Annotations\DocParser();
        $parser->setIgnoreNotImportedAnnotations(true);

        foreach($folderPaths as $folderPath) {
            $reader = new AnnotationReader($parser); // get an instance of the doctrine annotation reader
            $scanner = new Scanner($reader);

            $scanner->scan($annotations)->in($folderPath);

            foreach ($scanner as $file) {
                $mapInfo = AnnotationRouteMapper::scanFile($file);
                $routeMapInfo = array_merge($routeMapInfo, $mapInfo);
            }
        }
        
        return $routeMapInfo;
    }
}

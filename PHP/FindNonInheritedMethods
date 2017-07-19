<?php

/**
 * Undocumented interface
 */
interface MyStructure{
    public function wowza();
}

class Base
{
    protected function blurg(){}
    public function gold(){}
}

/**
 * Undocumented class
 */
class MyParent extends Base
{
    protected function blurg(){}
    public function blorg(){}
}

/**
 * Undocumented class
 */
class MyChild extends MyParent implements MyStructure
{
    protected function blurg(){}
    public function blorg(){}
    private function test(){}
    public function makeMePrivate(){}
    public function gold(){}

    public function wowza(){return false;}
}

/*****************************************************
 * The Logic: Find Non-Inherited Methods
 ****************************************************/

// Setup
$parentMethods = [];
$f = new ReflectionClass('MyChild');

// Obtain the list of methods used buy the current class
foreach($f->getMethods() as $reflectMethodObject){
    $classMethods[] = $reflectMethodObject->getName();
}

// Get the parent name of the current class, if any.
if(!$parent = $f->getParentClass()){
    echo 'No parent found for ' . $f->getName() . PHP_EOL;
    exit;
}

/**
 * @param $class string
 */
$findParentMethods = function(string $class) use (&$parentMethods, &$findParentMethods)
{
    $f = new ReflectionClass($class);
    foreach($f->getMethods() as $reflectMethodObject){
        $parentMethods[] = $reflectMethodObject->getName();
    }
    if($f->getParentClass()){
        $findParentMethods($f->getParentClass()->getName());
    }
};

// Build the array of parent methods
$findParentMethods($parent->getName());

// Obtain the methods that do not belong to the parent
$nonInheritedMethods = array_diff($classMethods, $parentMethods);

print_r($nonInheritedMethods);

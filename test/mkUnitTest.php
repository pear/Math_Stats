#! /usr/local/bin/php
<?php

$fname = $argv[1];

$source = @file_get_contents($fname, true);
if (!is_string($source)) {
    echo "Error reading $fname.\n";
    echo "  Check that you are using the correct full path,\n";
    echo "  or a valid relative path if the PHP file is in your include_path\n";
    exit(1);
}
//print_r(token_get_all($source));
$classdef = false;
$funcdef = false;
$private = array();
$public = array();
foreach (token_get_all($source) as $token) {
    if (!is_array($token)) {
        continue;
    } else {
        list($id, $val) = $token;
        if ($id == T_CLASS) {
            $classdef = true;
        }
            
        if ($classdef && $id == T_STRING) {
            $classname = $val;
            $classdef = false;
        }
        
        if ($id == T_FUNCTION) {
            $funcdef = true;
        }

        if ($funcdef && $id == T_STRING) {
            if (preg_match('/^_/', $val)) {
                $private[$classname][] = $val;
                $type='Private';
            } else {
                $public[$classname][] = $val;
                $type='Public';
            }
            //echo "$type name: $classname::$val\n";
            $funcdef = false;
        } else {
            continue;
        }
    }
}

foreach ($public as $className=>$methods) {
    createUnitTest($fname, $className, $methods);
}

function createUnitTest($fname, $className, $methods) {
    $class = "<?php\n\nrequire_once 'PHPUnit.php';\nrequire_once '$fname';\n\n";
    $class .= "class {$className}_UnitTest extends PHP_UnitTestCase { /*{{{*/\n\n";
    $class .= "    var \$o1 = null;\n\n";
    $class .= "    function {$className}_UnitTest(\$name) { /*{{{*/\n";
    $class .= "        \$this->PHPUnit_TestCase(\$name);\n    } /*}}}*/\n\n";
    $class .= "    function setUp() { /*{{{*/\n";
    $class .= "        // set up your test vars and data\n";
    $class .= "        \$this->o1 = new $className();\n";
    $class .= "    } /*}}}*/\n\n";
    $class .= "    function tearDown() { /*{{{*/\n";
    $class .= "        // clean up after yourself\n";
    $class .= "        unset(\$this->o1)\n";
    $class .= "    } /*}}}*/\n\n";
    foreach ($methods as $method) {
        // skip constructor
        if ($className == $method) {
            continue;
        }
        $name = 'test'.ucfirst($method);
        $class .= "    function $name() { /*{{{*/\n";
        $class .= "        // test of $className::$method\n";
        $class .= "        // echo serialize(\$this->o1->{$method}()).\"\\n\";\n";
        $class .= "        \$this->assertEquals('some_value', \$this->o1->{$method}());\n";
        $class .= "    } /*}}}*/\n\n";
    }
    $class .= "}/*}}}*/\n\n?>";
    $unitFname = "unitTest_{$className}.php";
    $fp = fopen($unitFname,'w');
    fwrite($fp, $class);
    fflush($fp);
    fclose($fp);
    echo "Created $unitFname to test $className\n";
}

?>

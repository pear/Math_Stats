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
    $out = "<?php\n\nrequire_once 'PHPUnit.php';\nrequire_once '$fname';\n\n";
    $out .= "/**\n * Unit test for {$className}\n *\n * @package {$className}\n";
    $out .= " * @author\n * @version\n */\n";
    $out .= "class {$className}_UnitTest extends PHP_UnitTestCase { /*{{{*/\n\n";
    $out .= "    var \$o1 = null;\n\n";
    $out .= "    function {$className}_UnitTest(\$name) { /*{{{*/\n";
    $out .= "        \$this->PHPUnit_TestCase(\$name);\n    } /*}}}*/\n\n";
    $out .= "    function setUp() { /*{{{*/\n";
    $out .= "        // set up your test vars and data\n";
    $out .= "        \$this->o1 = new $className();\n";
    $out .= "    } /*}}}*/\n\n";
    $out .= "    function tearDown() { /*{{{*/\n";
    $out .= "        // clean up after yourself\n";
    $out .= "        unset(\$this->o1)\n";
    $out .= "    } /*}}}*/\n\n";
    foreach ($methods as $method) {
        // skip constructor
        if ($outName == $method) {
            continue;
        }
        $name = 'test'.ucfirst($method);
        $out .= "    function $name() { /*{{{*/\n";
        $out .= "        // test of $className::$method\n";
        $out .= "        // echo serialize(\$this->o1->{$method}()).\"\\n\";\n";
        $out .= "        \$this->assertEquals(TODO, \$this->o1->{$method}());\n";
        $out .= "    } /*}}}*/\n\n";
    }
    $out .= "}/*}}}*/\n\n";
    $out .= "\$suite = new PHPUnit_TestSuite('{$className}_UnitTest')\n";
    $out .= "\$result = PHPUnit::run(\$suite);\n";
    $out .= "echo \$result->toString();\n\n?>";
    $unitFname = "unitTest_{$className}.php";
    $fp = fopen($unitFname,'w');
    fwrite($fp, $out);
    fflush($fp);
    fclose($fp);
    echo "Created $unitFname to test $className\n";
}

?>

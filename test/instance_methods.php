<?php
//
// +----------------------------------------------------------------------+
// | PHP version 4.0                                                      |
// +----------------------------------------------------------------------+
// | Copyright (c) 1997-2001 The PHP Group                                |
// +----------------------------------------------------------------------+
// | This source file is subject to version 2.02 of the PHP license,      |
// | that is bundled with this package in the file LICENSE, and is        |
// | available at through the world-wide-web at                           |
// | http://www.php.net/license/2_02.txt.                                 |
// | If you did not receive a copy of the PHP license and are unable to   |
// | obtain it through the world-wide-web, please send a note to          |
// | license@php.net so we can mail you a copy immediately.               |
// +----------------------------------------------------------------------+
// | Authors: Jesus M. Castagnetto <jmcastagnetto@php.net>                |
// +----------------------------------------------------------------------+
// 
// Matrix definition and manipulation package
// 
// $Id$
//

require_once 'PHPUnit.php';
require_once 'Math/Stats.php';
//require_once '../Stats.php';

class Math_Stats_Instance_Methods_Test extends PHPUnit_TestCase {/*{{{*/
    var $stats;
    var $data = array (2,2.3,4.5,2,2,3.2,5.3,3,4,5,1,6);
    var $datawithnulls = array (1.1650,null, "foo",0.6268, 0.6268, 0.0751, 0.3516, -0.6965);
    var $cummdata = array("3"=>4, "2.333"=>5, "1.22"=>6, "0.5"=>3, "0.9"=>2, "2.4"=>7);
    var $cummdatawithnulls = array("3"=>4, "plink"=>2, "bar is not foo"=>6, "0.5"=>3, "0.9"=>2, "2.4"=>7);

    function Math_Matrix_Instance_Methods_Test($name) {
        $this->PHPUnit_TestCase($name);
    }

    function setUp() {
        $this->stats = new Math_Stats();
    }

    }/*}}}*/

$suite = new PHPUnit_TestSuite('Math_Stats_Instance_Methods_Test');
$result = PHPUnit::run($suite);
echo $result->toString()."\n";

// vim: ts=4:sw=4:et:
// vim6: fdl=0: fdm=marker:
?>

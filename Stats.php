<?php
//
// +----------------------------------------------------------------------+
// | PHP Version 4                                                        |
// +----------------------------------------------------------------------+
// | Copyright (c) 1997-2002 The PHP Group                                |
// +----------------------------------------------------------------------+
// | This source file is subject to version 2.0 of the PHP license,       |
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
// $Id$
//

include_once "PEAR.php";

/**
 * @package	Math_Stats
 */

// Constants for defining the statistics to calculate
/**
 * STATS_BASIC to generate the basic descriptive statistics
 */
define("STATS_BASIC", 1);
/**
 * STATS_FULL to generate also higher moments, mode, median, etc.
 */
define("STATS_FULL", 2);


// Constants describing the data set format 
/**
 * STATS_DATA_SIMPLE for an array of numeric values
 * e.g. $data = array(2,3,4,5,1,1,6);
 */
define("STATS_DATA_SIMPLE", 0);
/**
 * STATS_DATA_CUMMULATIVE for an associative array of frequency values,
 * where in each array entry, the index is the data point and the
 * value the count (frequency):
 * e.g. $data = array(3=>4, 2.3=>5, 1.25=>6, 0.5=>3)
 */
define("STATS_DATA_CUMMULATIVE", 1);


// Constants defining how to handle nulls
/**
 * STATS_REJECT_NULL, reject data sets with null values.
 * Any non-numeric value is considered a null in this context.
 */
define("STATS_REJECT_NULL", -1);
/**
 * STATS_IGNORE_NULL, ignore null values and prune them from the data.
 * Any non-numeric value is considered a null in this context.
 */
define("STATS_IGNORE_NULL", -2);
/**
 * STATS_USE_NULL_AS_ZERO, assign the value of 0 (zero) to null values.
 * Any non-numeric value is considered a null in this context.
 */
define("STATS_USE_NULL_AS_ZERO", -3);

/**
 * A class to calculate descriptive statistics from a data set.
 * Data sets can be simple arrays of data, or a cummulative hash.
 * The second form is useful when passing large data set,
 * for example the data set:
 *
 * $data = array (1,2,1,1,1,1,3,3,4,4,3,2,2,1,1,2,3,3,2,2,1,1,2,2);
 *
 * can be epxressed more compactly as:
 *
 * $data = array(1=>9, 2=>8, 3=>5, 4=>2);
 *
 *
 * @author	Jesus M. Castagnetto <jmcastagnetto@php.net>
 * @version	0.8
 * @access	public
 */
class Math_Stats {/*{{{*/
	/**
	 * The simple or cummulative data set.
	 * Null by default.
	 *
	 * @access	private
	 * @var	array
	 */
	var $_data = null;

	/**
	 * Flag for data type, one of STATS_DATA_SIMPLE or
	 * STATS_DATA_CUMMULATIVE. Null by default.
	 *
	 * @access	private
	 * @var	int
	 */
	var $_dataOption = null;

	/**
	 * Flag for null handling options. One of STATS_REJECT_NULL,
	 * STATS_IGNORE_NULL or STATS_USE_NULL_AS_ZERO
	 *
	 * @access	private
	 * @var	int
	 */
	var $_nullOption;

	function Math_Stats($nullOption=STATS_REJECT_NULL) {/*{{{*/
		$this->_nullOption = $nullOption;
	}/*}}}*/

	function setData($arr, $opt=STATS_DATA_SIMPLE) {/*{{{*/
		if ($opt == STATS_DATA_SIMPLE) {
			$this->_dataOption = $opt;
			$this->_data = array_values($arr);
		} else if ($opt == STATS_DATA_CUMMULATIVE) {
			$this->_dataOption = $opt;
			$this->_data = $arr;
		} 
		return $this->_validate();
	}/*}}}*/

	function getData() {/*{{{*/
		return $this->_data;
	}/*}}}*/

	function setNullOption($nullOption) {/*{{{*/
		$this->_nullOption = $nullOption;
	}/*}}}*/

	function calc($mode) {/*{{{*/
		if ($this->_data == null)
			return PEAR::raiseError("data has not been set");
		if ($mode == STATS_BASIC) {
			return array (
				"min" => $this->min(),
				"max" => $this->max(),
				"sum" => $this->sum(),
				"sum2" => $this->sum2(),
				"count" => $this->count(),
				"mean" => $this->mean(),
				"stdev" => $this->stDev(),
				"variance" => $this->variance()
			);
		} else if ($mode == STATS_FULL) {
			return array (
				"min" => $this->min(),
				"max" => $this->max(),
				"sum" => $this->sum(),
				"sum2" => $this->sum2(),
				"count" => $this->count(),
				"mean" => $this->mean(),
				"stdev" => $this->stDev(),
				"variance" => $this->variance(),
				"absDev" => $this->absDev(),
				"median" => $this->median(),
				"mode" => $this->mode(),
				"skewness" => $this->skewness(),
				"kurtosis" => $this->kurtosis(),
				"coeff_of_variation" => $this->coeffOfVariation(),
				"frequency" => $this->frequency()
			);
		} else {
			return PEAR::raiseError("incorrect mode, expected STATS_BASIC or STATS_FULL");
		}
	}/*}}}*/

	function calcBasic() {/*{{{*/
		return $this->calc(STATS_BASIC);
	}/*}}}*/

	function calcFull() {/*{{{*/
		return $this->calc(STATS_FULL);
	}/*}}}*/

	function min() {/*{{{*/
		if ($this->_data == null)
			return PEAR::raiseError("data has not been set");
		if ($this->_dataOption == STATS_DATA_CUMMULATIVE)
			return min(array_keys($this->_data));
		else
			return min($this->_data);
	}/*}}}*/

	function max() {/*{{{*/
		if ($this->_data == null)
			return PEAR::raiseError("data has not been set");
		if ($this->_dataOption == STATS_DATA_CUMMULATIVE)
			return max(array_keys($this->_data));
		else
			return max($this->_data);
	}/*}}}*/

	function sum() {/*{{{*/
		return $this->sumN(1);
	}/*}}}*/

	function sum2() {/*{{{*/
		return $this->sumN(2);
	}/*}}}*/

	function sumN($n) {/*{{{*/
		if ($this->_data == null)
			return PEAR::raiseError("data has not been set");
		$sumN = 0;
		if ($this->_dataOption == STATS_DATA_CUMMULATIVE) {
			foreach($this->_data as $val=>$freq)
				$sumN += $freq * pow((float)$val, $n);
		} else {
			foreach($this->_data as $val)
				$sumN += pow($val, $n);
		}
		return $sumN;
	}/*}}}*/

	function count() {/*{{{*/
		if ($this->_data == null)
			return PEAR::raiseError("data has not been set");
		if ($this->_dataOption == STATS_DATA_CUMMULATIVE) {
			foreach($this->_data as $freq)
				$count += $freq;
		} else {
			$count = count($this->_data);
		}
		return $count;
	}/*}}}*/

	function mean() {/*{{{*/
		if ($this->_data == null)
			return PEAR::raiseError("data has not been set");
		return ($this->sum() / $this->count());
	}/*}}}*/

	function variance() {/*{{{*/
		if ($this->_data == null)
			return PEAR::raiseError("data has not been set");
		return $this->__sumdiff(2) / ($this->count() - 1);
	}/*}}}*/

	function stDev() {/*{{{*/
		if ($this->_data == null)
			return PEAR::raiseError("data has not been set");
		return sqrt($this->variance());
	}/*}}}*/

	function varianceWithMean($mean) {/*{{{*/
		if ($this->_data == null)
			return PEAR::raiseError("data has not been set");
		return $this->__sumdiff(2, $mean) / ($this->count() - 1);
	}/*}}}*/
	
	function stDevWithMean($mean) {/*{{{*/
		if ($this->_data == null)
			return PEAR::raiseError("data has not been set");
		return sqrt($this->variance($mean));
	}/*}}}*/

	function absDev() {/*{{{*/
		if ($this->_data == null)
			return PEAR::raiseError("data has not been set");
		return $this->__sumabsdev() / $this->count();
	}/*}}}*/

	function absDevWithMean($mean) {/*{{{*/
		if ($this->_data == null)
			return PEAR::raiseError("data has not been set");
		return $this->__sumabsdev($mean) / $this->count();
	}/*}}}*/

	function skewness() {/*{{{*/
		if ($this->_data == null)
			return PEAR::raiseError("data has not been set");
		$skew = ($this->__sumdiff(3) / ($this->count() * pow($this->stDev(), 3)));
		return $skew;
	}/*}}}*/

	function kurtosis() {/*{{{*/
		if ($this->_data == null)
			return PEAR::raiseError("data has not been set");
		$kurt = ($this->__sumdiff(4) / ($this->count() * pow($this->stDev(), 4))) - 3;
		return $kurt;
	}/*}}}*/

	function median() {/*{{{*/
		if ($this->_data == null)
			return PEAR::raiseError("data has not been set");
		$arr = array();
		if ($this->_dataOption == STATS_DATA_CUMMULATIVE)
			foreach ($this->_data as $val=>$freq)
				$arr = array_pad($arr, count($arr) + $freq, $val);
		else
			$arr = $this->_data;
		sort($arr);
		$n = count($arr);
		$h = intval($n / 2);
		if ($n % 2 == 0) {
			$median = ($arr[$h] + $arr[$h + 1]) / 2;
		} else {
			$median = $arr[$h + 1];
		}
		return $median;
	}/*}}}*/

	function mode() {/*{{{*/
		if ($this->_data == null)
			return PEAR::raiseError("data has not been set");
		if ($this->_dataOption == STATS_DATA_CUMMULATIVE)
			$arr = $this->_data;
		else
			$arr = $this->frequency();
		arsort($arr);
		$mcount = 1;
		foreach ($arr as $val=>$freq) {
			if ($mcount == 1) {
				$mode = array($val);
				$mfreq = $freq;
				$mcount++;
				continue;
			}
			if ($mfreq == $freq)
				$mode[] = $val;
			if ($mfreq > $freq)
				break;
		}
		return $mode;
	}/*}}}*/

	function frequency() {/*{{{*/
		if ($this->_data == null)
			return PEAR::raiseError("data has not been set");
		if ($this->_dataOption == STATS_DATA_CUMMULATIVE) {
			return $this->_data;
		} else {
			$freq = array();
			foreach ($this->_data as $val)
				$freq["$val"]++;
			return $freq;
		}
	}/*}}}*/

	function coeffOfVariation() {/*{{{*/
		if ($this->_data == null)
			return PEAR::raiseError("data has not been set");
		return $this->stDev() / $this->mean();
	}/*}}}*/

	/**
	 * Utility function to calculate: SUM { (xi - mean)^n }
	 * 
	 * @access private
	 * @param	numeric	$power	the exponent
	 * @param	optional	float	$mean	the data set mean value
	 * @return	mixed	the sum on success, a PEAR_Error object otherwise
	 *
	 * @see stDev()
	 * @see variaceWithMean();
	 * @see skewness();
	 * @see kurtosis();
	 */
	function __sumdiff($power, $mean=null) {/*{{{*/
		if ($this->_data == null)
			return PEAR::raiseError("data has not been set");
		if (is_null($mean))
			$mean = $this->mean();
		$sdiff = 0;
		if ($this->_dataOption == STATS_DATA_CUMMULATIVE) {
			foreach ($this->_data as $val=>$freq)
				$sdiff += $freq * pow(($val - $mean), $power);
		} else {
			foreach ($this->_data as $val=>$freq)
				$sdiff += pow(($val - $mean), $power);
		}
		return $sdiff;
	}/*}}}*/

	/**
	 * Used by absDev, absDevWithMean
	 */
	function __sumabsdev($mean=null) {/*{{{*/
		if ($this->_data == null)
			return PEAR::raiseError("data has not been set");
		if (is_null($mean))
			$mean = $this->mean();
		$sdev = 0;
		if ($this->_dataOption == STATS_DATA_CUMMULATIVE) {
			foreach ($this->_data as $val=>$freq)
				$sdev += $freq * abs($val - $mean);
		} else {
			foreach ($this->_data as $val=>$freq)
				$sdev += abs($val - $mean);
		}
		return $sdev;
	}/*}}}*/

	function _validate() {/*{{{*/
		$flag = ($this->_dataOption == STATS_DATA_CUMMULATIVE);
		$newdata = array();
		foreach ($this->_data as $key=>$value) {
			$d = ($flag) ? $key : $value;
			if (!is_numeric($d)) {
				switch ($this->_nullOption) {
					case STATS_IGNORE_NULL :
						unset($this->_data[$key]);
						break;
					case STATS_USE_NULL_AS_ZERO:
						$this->_data[$key] = 0;
						break;
					case STATS_REJECT_NULL :
					default:
						return PEAR::raiseError("data rejected, contains NULL values");
						break;
				}
			}
		}
		return true;
	}/*}}}*/

}/*}}}*/


?>

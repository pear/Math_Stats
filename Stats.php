<?php
//
// +----------------------------------------------------------------------+
// | PHP Version 4                                                        |
// +----------------------------------------------------------------------+
// | Copyright (c) 1997-2003 The PHP Group                                |
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
 * @package Math_Stats
 */

// Constants for defining the statistics to calculate /*{{{*/
/**
 * STATS_BASIC to generate the basic descriptive statistics
 */
define("STATS_BASIC", 1);
/**
 * STATS_FULL to generate also higher moments, mode, median, etc.
 */
define("STATS_FULL", 2);
/*}}}*/

// Constants describing the data set format /*{{{*/
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
/*}}}*/

// Constants defining how to handle nulls /*{{{*/
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
/*}}}*/

/**
 * A class to calculate descriptive statistics from a data set.
 * Data sets can be simple arrays of data, or a cummulative hash.
 * The second form is useful when passing large data set,
 * for example the data set:
 *
 * <pre>
 * $data1 = array (1,2,1,1,1,1,3,3,4.1,3,2,2,4.1,1,1,2,3,3,2,2,1,1,2,2);
 * </pre>
 *
 * can be epxressed more compactly as:
 *
 * <pre>
 * $data2 = array("1"=>9, "2"=>8, "3"=>5, "4.1"=>2);
 * </pre>
 *
 * Example of use:
 *
 * <pre>
 * include_once "Math/Stats.php";
 * $s = new Math_Stats();
 * $s->setData($data1);
 * // or
 * // $s->setData($data2, STATS_DATA_CUMMULATIVE);
 * $stats = $s->calcBasic();
 * echo "Mean: ".$stats["mean"]." StDev: ".$stats["stdev"]." <br />\n";
 * 
 * // using data with nulls
 * // first ignoring them:
 * $data3 = array(1.2, "foo", 2.4, 3.1, 4.2, 3.2, null, 5.1, 6.2);
 * $s->setNullOption(STATS_IGNORE_NULL);
 * $s->setData($data3);
 * $stats3 = $s->calcFull();
 *
 * // and then assuming nulls == 0
 * $s->setNullOption(STATS_USE_NULL_AS_ZERO);
 * $s->setData($data3);
 * $stats3 = $s->calcFull();
 * </pre>
 *
 * Originally this class was part of NumPHP (Numeric PHP package)
 *
 * @author  Jesus M. Castagnetto <jmcastagnetto@php.net>
 * @version 0.8
 * @access  public
 * @package Math_Stats
 */
class Math_Stats {/*{{{*/
    // properties /*{{{*/
    
    /**
     * The simple or cummulative data set.
     * Null by default.
     *
     * @access  private
     * @var array
     */
    var $_data = null;

    /**
     * Flag for data type, one of STATS_DATA_SIMPLE or
     * STATS_DATA_CUMMULATIVE. Null by default.
     *
     * @access  private
     * @var int
     */
    var $_dataOption = null;

    /**
     * Flag for null handling options. One of STATS_REJECT_NULL,
     * STATS_IGNORE_NULL or STATS_USE_NULL_AS_ZERO
     *
     * @access  private
     * @var int
     */
    var $_nullOption;

    /**
     * Array for caching result values, should be reset
     * when using setData()
     *
     * @access private
     * @var array
     */
    var $_calculatedValues = array();

    /*}}}*/
    
    /**
     * Constructor for the class
     *
     * @access  public
     * @param   optional    int $nullOption how to handle null values
     * @return  object  Math_Stats
     */
    function Math_Stats($nullOption=STATS_REJECT_NULL) {/*{{{*/
        $this->_nullOption = $nullOption;
    }/*}}}*/

    /**
     * Sets and verifies the data, checking for nulls and using
     * the current null handling option
     *
     * @access public
     * @param   array   $arr    the data set
     * @param   optional    int $opt    data format: STATS_DATA_CUMMULATIVE or STATS_DATA_SIMPLE (default)
     * @return  mixed   true on success, a PEAR_Error object otherwise
     */
    function setData($arr, $opt=STATS_DATA_SIMPLE) {/*{{{*/
        if (!is_array($arr)) {
            return PEAR::raiseError("invalid data, an array of numeric data was expected");
        }
        $this->_data = null;
        $this->_dataOption = null;
        $this->_calculatedValues = array();
        if ($opt == STATS_DATA_SIMPLE) {
            $this->_dataOption = $opt;
            $this->_data = array_values($arr);
        } else if ($opt == STATS_DATA_CUMMULATIVE) {
            $this->_dataOption = $opt;
            $this->_data = $arr;
        } 
        return $this->_validate();
    }/*}}}*/

    /**
     * Returns the data which might have been modified
     * according to the current null handling options.
     *
     * @access  public
     * @return  mixed   array of data on success, a PEAR_Error object otherwise
     * @see _validate()
     */
    function getData() {/*{{{*/
        if ($this->_data == null)
            return PEAR::raiseError("data has not been set");
        return $this->_data;
    }/*}}}*/

    /**
     * Sets the null handling option.
     * Must be called before assigning a new data set containing null values
     * 
     * @access  public
     * @return  mixed   true on success, a PEAR_Error object otherwise
     * @see _validate()
     */
    function setNullOption($nullOption) {/*{{{*/
        if ($nullOption == STATS_REJECT_NULL
            || $nullOption == STATS_IGNORE_NULL
            || $nullOption == STATS_USE_NULL_AS_ZERO) {
            $this->_nullOption = $nullOption;
            return true;
        } else {
            return PEAR::raiseError("invalid null handling option expecting: ".
                        "STATS_REJECT_NULL, STATS_IGNORE_NULL or STATS_USE_NULL_AS_ZERO");
        }
    }/*}}}*/

    /**
     * Calculates the basic or full statistics for the data set
     * 
     * @access  public
     * @param   int $mode   one of STATS_BASIC or STATS_FULL
     * @return  mixed   an associative array of statistics on success, a PEAR_Error object otherwise
     * @see calcBasic()
     * @see calcFull()
     */ 
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
                "median" => $this->median(),
                "mode" => $this->mode(),
                "midrange" => $this->midrange(),
                "stdev" => $this->stDev(),
                "absdev" => $this->absDev(),
                "variance" => $this->variance(),
                "std_error_of_mean" => $this->stdErrorOfMean(),
                "skewness" => $this->skewness(),
                "kurtosis" => $this->kurtosis(),
                "coeff_of_variation" => $this->coeffOfVariation(),
                "sample_central_moments" => array (
                            1 => $this->sampleCentralMoment(1),
                            2 => $this->sampleCentralMoment(2),
                            3 => $this->sampleCentralMoment(3),
                            4 => $this->sampleCentralMoment(4),
                            5 => $this->sampleCentralMoment(5)
                            ),
                "sample_raw_moments" => array (
                            1 => $this->sampleRawMoment(1),
                            2 => $this->sampleRawMoment(2),
                            3 => $this->sampleRawMoment(3),
                            4 => $this->sampleRawMoment(4),
                            5 => $this->sampleRawMoment(5)
                            ),
                "frequency" => $this->frequency()
            );
        } else {
            return PEAR::raiseError("incorrect mode, expected STATS_BASIC or STATS_FULL");
        }
    }/*}}}*/

    /**
     * Calculates a basic set of statistics
     *
     * @access  public
     * @return  mixed   an associative array of statistics on success, a PEAR_Error object otherwise
     * @see calc()
     * @see calcFull()
     */
    function calcBasic() {/*{{{*/
        return $this->calc(STATS_BASIC);
    }/*}}}*/

    /**
     * Calculates a full set of statistics
     *
     * @access  public
     * @return  mixed   an associative array of statistics on success, a PEAR_Error object otherwise
     * @see calc()
     * @see calcBasic()
     */
    function calcFull() {/*{{{*/
        return $this->calc(STATS_FULL);
    }/*}}}*/

    /**
     * Calculates the minimum of a data set.
     * Handles cummulative data sets correctly
     *
     * @access  public
     * @return  mixed   the minimum value on success, a PEAR_Error object otherwise
     * @see calc()
     * @see max()
     */
    function min() {/*{{{*/
        if ($this->_data == null) {
            return PEAR::raiseError("data has not been set");
        }
        if (!array_key_exists('min', $this->_calculatedValues)) {
            if ($this->_dataOption == STATS_DATA_CUMMULATIVE) {
                $min = min(array_keys($this->_data));
            } else {
                $min = min($this->_data);
            }
            $this->_calculatedValues['min'] = $min;
        }
        return $this->_calculatedValues['min'];
    }/*}}}*/

    /**
     * Calculates the maximum of a data set.
     * Handles cummulative data sets correctly
     *
     * @access  public
     * @return  mixed   the maximum value on success, a PEAR_Error object otherwise
     * @see calc()
     * @see min()
     */
    function max() {/*{{{*/
        if ($this->_data == null) {
            return PEAR::raiseError("data has not been set");
        }
        if (!array_key_exists('max', $this->_calculatedValues)) {
            if ($this->_dataOption == STATS_DATA_CUMMULATIVE) {
                $max = max(array_keys($this->_data));
            } else {
                $max = max($this->_data);
            }
            $this->_calculatedValues['max'] = $max;
        }
        return $this->_calculatedValues['max'];
    }/*}}}*/

    /**
     * Calculates SUM { xi }
     * Handles cummulative data sets correctly
     *
     * @access  public
     * @return  mixed   the sum on success, a PEAR_Error object otherwise   
     * @see calc()
     * @see sum2()
     * @see sumN()
     */
    function sum() {/*{{{*/
        if (!array_key_exists('sum', $this->_calculatedValues)) {
            $sum = $this->sumN(1);
            if (PEAR::isError($sum)) {
                return $sum;
            } else {
                $this->_calculatedValues['sum'] = $sum;
            }
        }
        return $this->_calculatedValues['sum'];
    }/*}}}*/

    /**
     * Calculates SUM { (xi)^2 }
     * Handles cummulative data sets correctly
     *
     * @access  public
     * @return  mixed   the sum on success, a PEAR_Error object otherwise   
     * @see calc()
     * @see sum()
     * @see sumN()
     */
    function sum2() {/*{{{*/
        if (!array_key_exists('sum2', $this->_calculatedValues)) {
            $sum2 = $this->sumN(1);
            if (PEAR::isError($sum2)) {
                return $sum2;
            } else {
                $this->_calculatedValues['sum2'] = $sum2;
            }
        }
        return $this->_calculatedValues['sum2'];
    }/*}}}*/

    /**
     * Calculates SUM { (xi)^n }
     * Handles cummulative data sets correctly
     *
     * @access  public
     * @param   numeric $n  the exponent
     * @return  mixed   the sum on success, a PEAR_Error object otherwise   
     * @see calc()
     * @see sum()
     * @see sum2()
     */
    function sumN($n) {/*{{{*/
        if ($this->_data == null) {
            return PEAR::raiseError("data has not been set");
        }
        $sumN = 0;
        if ($this->_dataOption == STATS_DATA_CUMMULATIVE) {
            foreach($this->_data as $val=>$freq) {
                $sumN += $freq * pow((double)$val, (double)$n);
            }
        } else {
            foreach($this->_data as $val) {
                $sumN += pow((double)$val, (double)$n);
            }
        }
        return $sumN;
    }/*}}}*/

    /**
     * Calculates PROD { (xi) }, (the product of all observations)
     * Handles cummulative data sets correctly
     *
     * @access  public
     * @return  mixed   the product on success, a PEAR_Error object otherwise   
     * @see productN()
     */
    function product() {/*{{{*/
        if (!array_key_exists('product', $this->_calculatedValues)) {
            $product = $this->productN(1);
            if (PEAR::isError($product)) {
                return $product;
            } else {
                $this->_calculatedValues['product'] = $product;
            }
        }
        return $this->_calculatedValues['product'];
    }/*}}}*/

    /**
     * Calculates PROD { (xi)^n }, which is the product of all observations
     * Handles cummulative data sets correctly
     *
     * @access  public
     * @param   numeric $n  the exponent
     * @return  mixed   the product on success, a PEAR_Error object otherwise   
     * @see product()
     */
    function productN($n) {/*{{{*/
        if ($this->_data == null) {
            return PEAR::raiseError("data has not been set");
        }
        $prodN = 1.0;
        if ($this->_dataOption == STATS_DATA_CUMMULATIVE) {
            foreach($this->_data as $val=>$freq) {
                if ($val == 0) {
                    return 0.0;
                }
                $prodN *= $freq * pow((double)$val, (double)$n);
            }
        } else {
            foreach($this->_data as $val) {
                if ($val == 0) {
                    return 0.0;
                }
                $prodN *= pow((double)$val, (double)$n);
            }
        }
        return $prodN;

    }/*}}}*/

    /**
     * Calculates the number of data points in the set
     * Handles cummulative data sets correctly
     *
     * @access  public
     * @return  mixed   the count on success, a PEAR_Error object otherwise 
     * @see calc()
     */
    function count() {/*{{{*/
        if ($this->_data == null) {
            return PEAR::raiseError("data has not been set");
        }
        if (!array_key_exists('count', $this->_calculatedValues)) {
            if ($this->_dataOption == STATS_DATA_CUMMULATIVE) {
                foreach($this->_data as $freq) {
                    $count += $freq;
                }
            } else {
                $count = count($this->_data);
            }
            $this->_calculatedValues['count'] = $count;
        }
        return $this->_calculatedValues['count'];
    }/*}}}*/

    /**
     * Calculates the mean (average) of the data points in the set
     * Handles cummulative data sets correctly
     *
     * @access  public
     * @return  mixed   the mean value on success, a PEAR_Error object otherwise    
     * @see calc()
     * @see sum()
     * @see count()
     */
    function mean() {/*{{{*/
        if (!array_key_exists('mean', $this->_calculatedValues)) {
            $sum = $this->sum();
            if (PEAR::isError($sum)) {
                return $sum;
            }
            $count = $this->count();
            if (PEAR::isError($count)) {
                return $count;
            }
            $this->_calculatedValues['mean'] = $sum / $count;
        }
        return $this->_calculatedValues['mean'];
    }/*}}}*/

    /**
     * Calculates the variance (unbiased) of the data points in the set
     * Handles cummulative data sets correctly
     *
     * @access  public
     * @return  mixed   the variance value on success, a PEAR_Error object otherwise    
     * @see calc()
     * @see __sumdiff()
     * @see count()
     */
    function variance() {/*{{{*/
        if (!array_key_exists('variance', $this->_calculatedValues)) {
            $variance = $this->__calcVariance();
            if (PEAR::isError($variance)) {
                return $variance;
            }
            $this->_calculatedValues['variance'] = $variance;
        }
        return $this->_calculatedValues['variance'];
    }/*}}}*/

    /**
     * Calculates the standard deviation (unbiased) of the data points in the set
     * Handles cummulative data sets correctly
     *
     * @access  public
     * @return  mixed   the standard deviation on success, a PEAR_Error object otherwise    
     * @see calc()
     * @see variance()
     */
    function stDev() {/*{{{*/
        if (!array_key_exists('stDev', $this->_calculatedValues)) {
            $variance = $this->variance();
            if (PEAR::isError($variance)) {
                return $variance;
            }
            $this->_calculatedValues['stDev'] = sqrt($variance);
        }
        return $this->_calculatedValues['stDev'];
    }/*}}}*/

    /**
     * Calculates the variance (unbiased) of the data points in the set
     * given a fixed mean (average) value. Not used in calcBasic(), calcFull()
     * or calc().
     * Handles cummulative data sets correctly
     *
     * @access  public
     * @param   numeric $mean   the fixed mean value
     * @return  mixed   the variance on success, a PEAR_Error object otherwise  
     * @see __sumdiff()
     * @see count()
     * @see variance()
     */
    function varianceWithMean($mean) {/*{{{*/
        return $this->__calcVariance($mean);
    }/*}}}*/
    
    /**
     * Calculates the standard deviation (unbiased) of the data points in the set
     * given a fixed mean (average) value. Not used in calcBasic(), calcFull()
     * or calc().
     * Handles cummulative data sets correctly
     *
     * @access  public
     * @param   numeric $mean   the fixed mean value
     * @return  mixed   the standard deviation on success, a PEAR_Error object otherwise    
     * @see varianceWithMean()
     * @see stDev()
     */
    function stDevWithMean($mean) {/*{{{*/
        $varianceWM = $this->varianceWithMean($mean);
        if (PEAR::isError($varianceWM)) {
            return $varianceWM;
        }
        return sqrt($varianceWM);
    }/*}}}*/

    /**
     * Calculates the absolute deviation of the data points in the set
     * Handles cummulative data sets correctly
     *
     * @access  public
     * @return  mixed   the absolute deviation on success, a PEAR_Error object otherwise    
     * @see calc()
     * @see __sumabsdev()
     * @see count()
     * @see absDevWithMean()
     */
    function absDev() {/*{{{*/
        if (!array_key_exists('absDev', $this->_calculatedValues)) {
            $absDev = $this->__calcAbsoluteDeviation();
            if (PEAR::isError($absdev)) {
                return $absdev;
            }
            $this->_calculatedValues['absDev'] = $absDev;
        }
        return $this->_calculatedValues['absDev'];
    }/*}}}*/

    /**
     * Calculates the absolute deviation of the data points in the set
     * given a fixed mean (average) value. Not used in calcBasic(), calcFull()
     * or calc().
     * Handles cummulative data sets correctly
     *
     * @access  public
     * @param   numeric $mean   the fixed mean value
     * @return  mixed   the absolute deviation on success, a PEAR_Error object otherwise    
     * @see __sumabsdev()
     * @see absDev()
     */
    function absDevWithMean($mean) {/*{{{*/
        return $this->__calcAbsoluteDeviation($mean);
    }/*}}}*/

    /**
     * Calculates the skewness of the data distribution in the set
     * The skewness measures the degree of asymmetry of a distribution,
     * and is related to the third central moment of a distribution.
     * A normal distribution has a skewness = 0
     * A distribution with a tail off towards the high end of the scale
     * (positive skew) has a skewness > 0
     * A distribution with a tail off towards the low end of the scale
     * (negative skew) has a skewness < 0
     * Handles cummulative data sets correctly
     *
     * @access  public
     * @return  mixed   the skewness value on success, a PEAR_Error object otherwise    
     * @see __sumdiff()
     * @see count()
     * @see stDev()
     * @see calc()
     */
    function skewness() {/*{{{*/
        if (!array_key_exists('skewness', $this->_calculatedValues)) {
            $count = $this->count();
            if (PEAR::isError($count)) {
                return $count;
            }
            $stDev = $this->stDev();
            if (PEAR::isError($stDev)) {
                return $stDev;
            }
            $sumdiff3 = $this->__sumdiff(3);
            if (PEAR::isError($sumdiff3)) {
                return $sumdiff3;
            }
            $this->_calculatedValues['skewness'] = ($sumdiff3 / ($count * pow($stDev, 3)));
        }
        return $this->_calculatedValues['skewness'];
    }/*}}}*/


    // TODO : finish error handling and calc caching code
    /**
     * Calculates the kurtosis of the data distribution in the set
     * The kurtosis measures the degrees of peakedness of a distribution.
     * It is also callesd the "excess" or "excess coefficient", and is
     * a normalized form of the fourth central moment of a distribution.
     * A normal distributions has kurtosis = 0
     * A narrow and peaked (leptokurtic) distribution has a
     * kurtosis > 0
     * A flat and wide (platykurtic) distribution has a kurtosis < 0
     * Handles cummulative data sets correctly
     *
     * @access  public
     * @return  mixed   the kurtosis value on success, a PEAR_Error object otherwise    
     * @see __sumdiff()
     * @see count()
     * @see stDev()
     * @see calc()
     */
    function kurtosis() {/*{{{*/
        if ($this->_data == null)
            return PEAR::raiseError("data has not been set");
        $kurt = ($this->__sumdiff(4) / ($this->count() * pow($this->stDev(), 4))) - 3;
        return $kurt;
    }/*}}}*/

    /**
     * Calculates the median of a data set.
     * The median is the value such that half of the points are below it
     * in a sorted data set.
     * If the number of values is odd, it is the middle item.
     * If the number of values is even, is the average of the two middle items.
     * Handles cummulative data sets correctly
     *
     * @access  public
     * @return  mixed   the median value on success, a PEAR_Error object otherwise  
     * @see count()
     * @see calc()
     */
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
            $median = ($arr[$h] + $arr[$h - 1]) / 2;
        } else {
            $median = $arr[$h + 1];
        }
        return $median;
    }/*}}}*/

    /**
     * Calculates the mode of a data set.
     * The mode is the value with the highest frequency in the data set.
     * There can be more than one mode.
     * Handles cummulative data sets correctly
     *
     * @access  public
     * @return  mixed   an array of mode value on success, a PEAR_Error object otherwise    
     * @see frequency()
     * @see calc()
     */
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

    /**
     * Calculates the midrange of a data set.
     * The midrange is the average of the minimum and maximum of the data set.
     * Handles cummulative data sets correctly
     *
     * @access  public
     * @return  mixed   the midrange value on success, a PEAR_Error object otherwise    
     * @see min()
     * @see max()
     * @see calc()
     */
    function midrange() {/*{{{*/
        if ($this->_data == null)
            return PEAR::raiseError("data has not been set");
        return (($this->max() + $this->min()) / 2);
    }/*}}}*/

    function geometricMean() {/*{{{*/
        if ($this->_data == null) {
            return PEAR::raiseError("data has not been set");
        }
        $prod = $this->product();
        if (PEAR::isError($product)) {
            return $product;
        }
        if ($product == 0.0) {
            return 0.0;
        }
        return power($prod , -1 * $this->count());
    }/*}}}*/
    
    /**
     * Calculates the nth central moment (m{n}) of a data set.
     *
     * The definition of a sample central moment is:
     *
     *     m{n} = 1/N * SUM { (xi - avg)^n }
     *
     * where: N = sample size, avg = sample mean.
     */
    function sampleCentralMoment($n) {/*{{{*/
        if ($n == 1) {
            return 0;
        }
        $count = $this->count();
        if (PEAR::isError($count)) {
            return $count;
        }
        if ($count == 0) {
            return PEAR::raiseError("Cannot calculate {$n}th sample moment, there are zero data entries.");
        }
        $sum = $this->__sumdiff($n);
        if (PEAR::isError($sum)) {
            return $sum;
        }
        return ($sum / $count);
    }/*}}}*/

    /**
     * Calculates the nth raw moment (m{n}) of a data set.
     *
     * The definition of a sample central moment is:
     *
     *     m{n} = 1/N * SUM { xi^n }
     *
     * where: N = sample size, avg = sample mean.
     */
    function sampleRawMoment($n) {/*{{{*/
        $count = $this->count();
        if (PEAR::isError($count)) {
            return $count;
        }
        if ($count == 0) {
            return PEAR::raiseError("Cannot calculate {$n}th raw moment, there are zero data entries.");
        }
        $sum = $this->sumN($n);
        if (PEAR::isError($sum)) {
            return $sum;
        }
        return ($sum / $count);
    }/*}}}*/

    /**
     * Calculates the value frequency table of a data set.
     * Handles cummulative data sets correctly
     *
     * @access  public
     * @return  mixed   an associative array of value=>frequency items on success, a PEAR_Error object otherwise    
     * @see min()
     * @see max()
     * @see calc()
     */
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

    /**
     * Calculates the coefficient of variation of a data set.
     * The coefficient of variation measures the spread of a set of data 
     * as a proportion of its mean. It is often expressed as a percentage.
     * Handles cummulative data sets correctly
     *
     * @access  public
     * @return  mixed   the coefficient of variation on success, a PEAR_Error object otherwise  
     * @see stDev()
     * @see mean()
     * @see calc()
     */
    function coeffOfVariation() {/*{{{*/
        if ($this->_data == null)
            return PEAR::raiseError("data has not been set");
        return $this->stDev() / $this->mean();
    }/*}}}*/

    /**
     * Calculates the standard error of the mean.
     * It is the standard deviation of the sampling distribution of
     * the mean. The formula is:
     *
     * S.E. Mean = SD / (N)^(1/2)
     *
     * This formula does not assume a normal distribution, and shows
     * that the size of the standard error of the mean is inversely
     * proportional to the square root of the sample size. 
     *
     * @access  public
     * @return  mixed   the standard error of the mean on success, a PEAR_Error object otherwise  
     * @see stDev()
     * @see count()
     * @see calc()
     */
    function stdErrorOfMean() {/*{{{*/
        if ($this->_data == null)
            return PEAR::raiseError("data has not been set");
        return $this->stDev() / sqrt($this->count());
    }/*}}}*/

    /**
     * Utility function to calculate: SUM { (xi - mean)^n }
     * 
     * @access private
     * @param   numeric $power  the exponent
     * @param   optional    double   $mean   the data set mean value
     * @return  mixed   the sum on success, a PEAR_Error object otherwise
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
                $sdiff += $freq * pow((double)($val - $mean), (double)$power);
        } else {
            foreach ($this->_data as $val)
                $sdiff += pow((double)($val - $mean), (double)$power);
        }
        return $sdiff;
    }/*}}}*/

    /**
     * Utility function to calculate the variance with or without
     * a fixed mean
     *
     * @acess private
     * @param $mean the fixed mean to use, null as default
     * @return mixed a numeric value on success, a PEAR_Error otherwise
     * @see variance()
     * @see varianceWithMean()
     */
    function __calcVariance($mean = null) {/*{{{*/
        if ($this->_data == null) {
            return PEAR::raiseError("data has not been set");
        }

        if (is_null($mean)) {
            $sumdiff2 = $this->__sumdiff(2);
        } else {
            $sumdiff2 = $this->__sumdiff(2, $mean);
        }
        if (PEAR::isError($sumdiff2)) {
            return $sumdiff2;
        }

        $count = $this->count();
        if (PEAR::isError($count)) {
            return $count;
        }
        if ($count == 1) {
            return PEAR::raiseError('cannot calculate variance of a singe data point');
        }
        return  ($sumdiff2 / ($count - 1));
    }/*}}}*/

    function __calcAbsoluteDeviation($mean = null) {
        if ($this->_data == null) {
            return PEAR::raiseError("data has not been set");
        }
        $count = $this->count();
        if (PEAR::isError($count)) {
            return $count;
        }
        if (is_null) {
            $sumabsdev = $this->__sumabsdev();
        } else {
            $sumabsdev = $this->__sumabsdev($mean);
        }
        if (PEAR::isError($sumabsdev)) {
            return $sumabsdev;
        }
        return sqrt($sumabsdev / $count);
    }

    /**
     * Utility function to calculate: SUM { | xi - mean | }
     *
     * @access  private
     * @param   optional    double   $mean   the mean value for the set or population
     * @return  mixed   the sum on success, a PEAR_Error object otherwise
     *
     * @see absDev()
     * @see absDevWithMean()
     */
    function __sumabsdev($mean=null) {/*{{{*/
        if ($this->_data == null) {
            return PEAR::raiseError("data has not been set");
        }
        if (is_null($mean)) {
            $mean = $this->mean();
        }
        $sdev = 0;
        if ($this->_dataOption == STATS_DATA_CUMMULATIVE) {
            foreach ($this->_data as $val=>$freq) {
                $sdev += $freq * abs($val - $mean);
            }
        } else {
            foreach ($this->_data as $val) {
                $sdev += abs($val - $mean);
            }
        }
        return $sdev;
    }/*}}}*/

    /**
     * Utility function to validate the data and modify it
     * according to the current null handling option
     *
     * @access  private
     * @return  mixed true on success, a PEAR_Error object otherwise
     * 
     * @see setData()
     */
    function _validate() {/*{{{*/
        $flag = ($this->_dataOption == STATS_DATA_CUMMULATIVE);
        foreach ($this->_data as $key=>$value) {
            $d = ($flag) ? $key : $value;
            $v = ($flag) ? $value : $key;
            if (!is_numeric($d)) {
                switch ($this->_nullOption) {
                    case STATS_IGNORE_NULL :
                        unset($this->_data["$key"]);
                        break;
                    case STATS_USE_NULL_AS_ZERO:
                        if ($flag) {
                            unset($this->_data["$key"]);
                            $this->_data[0] += $v;
                        } else {
                            $this->_data[$key] = 0;
                        }
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

// vim: ts=4:sw=4:et:
// vim6: fdl=1: fdm=marker:

?>

<?php
namespace Meldon\Random;
class Random {
    /**
     * @param $number Number of dice to roll
     * @param $sides Number of sides the dice should have
     * @param null|'ASC'|'DESC' $order  Whether to sort the rolls and if so to sort ASC or DESC
     * @return \stdClass
     */
    public static function dice( $number, $sides, $order = NULL ) {
        $roll = new \stdClass();
        $roll->total = 0;
        $roll->num_dice = $number;
        for ( $i = 1; $i <= $number; $i++ ) {
            $roll->total += $roll->results[$i] = self::getInteger( 1, $sides );
        }
        if ( $order === "ASC" ) {
            sort( $roll->results );
        } elseif ( $order === "DESC" ) {
            rsort( $roll->results );
        }
        return $roll;
    }

    /**
     * @param int $min
     * @param int $max
     * @return int
     * @throws Error
     * @throws Exception
     * @throws TypeError
     */
    public static function getInteger($min, $max)
    {
        /**
         * Type and input logic checks
         */
        if (!is_numeric($min)) {
            throw new TypeError(
                'Random::getInteger(): $min must be an integer'
            );
        }
        if (!is_numeric($max)) {
            throw new TypeError(
                'Random::getInteger(): $max must be an integer'
            );
        }
        $min = (int) $min;
        $max = (int) $max;
        if ($min > $max) {
            throw new Error(
                'Minimum value must be less than or equal to the maximum value'
            );
        }
        if ($max === $min) {
            return $min;
        }
        /**
         * Initialize variables to 0
         *
         * We want to store:
         * $bytes => the number of random bytes we need
         * $mask => an integer bitmask (for use with the &) operator
         *          so we can minimize the number of discards
         */
        $attempts = $bits = $bytes = $mask = $valueShift = 0;
        /**
         * At this point, $range is a positive number greater than 0. It might
         * overflow, however, if $max - $min > PHP_INT_MAX. PHP will cast it to
         * a float and we will lose some precision.
         */
        $range = $max - $min;
        /**
         * Test for integer overflow:
         */
        if (!is_int($range)) {
            /**
             * Still safely calculate wider ranges.
             * Provided by @CodesInChaos, @oittaa
             *
             * @ref https://gist.github.com/CodesInChaos/03f9ea0b58e8b2b8d435
             *
             * We use ~0 as a mask in this case because it generates all 1s
             *
             * @ref https://eval.in/400356 (32-bit)
             * @ref http://3v4l.org/XX9r5  (64-bit)
             */
            $bytes = PHP_INT_SIZE;
            $mask = ~0;
        } else {
            /**
             * $bits is effectively ceil(log($range, 2)) without dealing with
             * type juggling
             */
            while ($range > 0) {
                if ($bits % 8 === 0) {
                    ++$bytes;
                }
                ++$bits;
                $range >>= 1;
                $mask = $mask << 1 | 1;
            }
            $valueShift = $min;
        }
        /**
         * Now that we have our parameters set up, let's begin generating
         * random integers until one falls between $min and $max
         */
        do {
            /**
             * The rejection probability is at most 0.5, so this corresponds
             * to a failure probability of 2^-128 for a working RNG
             */
            if ($attempts > 128) {
                throw new Exception(
                    'Random::getInteger: RNG is broken - too many rejections'
                );
            }

            /**
             * Let's grab the necessary number of random bytes
             */
            $randomByteString = mcrypt_create_iv($bytes, MCRYPT_DEV_URANDOM);
            if ($randomByteString === false) {
                throw new Exception(
                    'Random number generator failure'
                );
            }
            /**
             * Let's turn $randomByteString into an integer
             *
             * This uses bitwise operators (<< and |) to build an integer
             * out of the values extracted from ord()
             *
             * Example: [9F] | [6D] | [32] | [0C] =>
             *   159 + 27904 + 3276800 + 201326592 =>
             *   204631455
             */
            $val = 0;
            for ($i = 0; $i < $bytes; ++$i) {
                $val |= ord($randomByteString[$i]) << ($i * 8);
            }
            /**
             * Apply mask
             */
            $val &= $mask;
            $val += $valueShift;
            ++$attempts;
            /**
             * If $val overflows to a floating point number,
             * ... or is larger than $max,
             * ... or smaller than $int,
             * then try again.
             */
        } while (!is_int($val) || $val > $max || $val < $min);
        return (int) $val;
    }
}
<?php
namespace Meldon\Random;
class Random {
    const SORT_ASC = 1;
    const SORT_DESC = 2;
    /**
     * @param $number Number of dice to roll
     * @param $sides Number of sides the dice should have
     * @param null|Random::SORT_ASC|Random::SORT_DESC $order  Whether to sort the rolls and if so to sort ASC or DESC
     * @return \stdClass
     */
    public static function dice( $number, $sides, $order = NULL ) {
        $roll = new \stdClass();
        $roll->total = 0;
        $roll->num_dice = $number;
        for ( $i = 1; $i <= $number; $i++ ) {
            $roll->total += $roll->results[$i] = random_int( 1, $sides );
        }
        if ( $order === Random::SORT_ASC ) {
            sort( $roll->results );
        } elseif ( $order === Random::SORT_DESC ) {
            rsort( $roll->results );
        }
        return $roll;
    }
}
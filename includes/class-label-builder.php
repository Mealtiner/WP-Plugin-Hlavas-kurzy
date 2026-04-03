<?php
/**
 * Label builder: generates human-readable Czech labels from dates.
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

class Hlavas_Terms_Label_Builder {

    /**
     * Czech month names (genitive case).
     */
    private const MONTHS = [
        1  => 'ledna',
        2  => 'února',
        3  => 'března',
        4  => 'dubna',
        5  => 'května',
        6  => 'června',
        7  => 'července',
        8  => 'srpna',
        9  => 'září',
        10 => 'října',
        11 => 'listopadu',
        12 => 'prosince',
    ];

    /**
     * Build a label for a term.
     *
     * kurz:    "kurz: 17. - 19. dubna 2026"
     * zkouška: "zkouška: 25. dubna 2026"
     */
    public static function build( string $term_type, string $date_start, ?string $date_end = null ): string {
        $prefix = $term_type === 'kurz' ? 'kurz' : 'zkouška';

        $start = new \DateTime( $date_start );

        if ( $term_type === 'kurz' && $date_end && $date_end !== $date_start ) {
            $end = new \DateTime( $date_end );
            return self::build_range_label( $prefix, $start, $end );
        }

        // Single-day
        $day   = (int) $start->format( 'j' );
        $month = self::MONTHS[ (int) $start->format( 'n' ) ];
        $year  = $start->format( 'Y' );

        return "{$prefix}: {$day}. {$month} {$year}";
    }

    /**
     * Build a range label like "kurz: 17. - 19. dubna 2026".
     */
    private static function build_range_label( string $prefix, \DateTime $start, \DateTime $end ): string {
        $day_s   = (int) $start->format( 'j' );
        $month_s = (int) $start->format( 'n' );
        $year_s  = $start->format( 'Y' );

        $day_e   = (int) $end->format( 'j' );
        $month_e = (int) $end->format( 'n' );
        $year_e  = $end->format( 'Y' );

        // Same month & year
        if ( $month_s === $month_e && $year_s === $year_e ) {
            $month_name = self::MONTHS[ $month_e ];
            return "{$prefix}: {$day_s}. - {$day_e}. {$month_name} {$year_e}";
        }

        // Different months, same year
        if ( $year_s === $year_e ) {
            $month_name_s = self::MONTHS[ $month_s ];
            $month_name_e = self::MONTHS[ $month_e ];
            return "{$prefix}: {$day_s}. {$month_name_s} - {$day_e}. {$month_name_e} {$year_e}";
        }

        // Different years (unlikely but safe)
        $month_name_s = self::MONTHS[ $month_s ];
        $month_name_e = self::MONTHS[ $month_e ];
        return "{$prefix}: {$day_s}. {$month_name_s} {$year_s} - {$day_e}. {$month_name_e} {$year_e}";
    }

    /**
     * Generate a term_key from type and dates.
     *
     * kurz:    "kurz_2026_04_17_19"
     * zkouška: "zkouska_2026_04_25"
     */
    public static function build_key( string $term_type, string $date_start, ?string $date_end = null ): string {
        $start = new \DateTime( $date_start );
        $base  = $term_type === 'kurz' ? 'kurz' : 'zkouska';
        $key   = $base . '_' . $start->format( 'Y_m_d' );

        if ( $term_type === 'kurz' && $date_end && $date_end !== $date_start ) {
            $end = new \DateTime( $date_end );
            $key .= '_' . $end->format( 'd' );
        }

        return $key;
    }
}

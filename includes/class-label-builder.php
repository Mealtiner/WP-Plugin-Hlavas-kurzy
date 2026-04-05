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
	 * kurz s kvalifikaci:
	 * "Kurz: Příprava na zkoušku 75-008-N (17. – 19. dubna 2026)"
	 *
	 * kurz bez kvalifikace:
	 * "Kurz: Administrativní název (17. – 19. dubna 2026)"
	 *
	 * zkouška:
	 * "Zkouška z profesní kvalifikace 75-008-N (22. dubna 2026)"
	 *
	 * fallback bez kódu:
	 * "zkouška: 22. dubna 2026"
	 *
	 * @param string      $term_type Kurz nebo zkouška.
	 * @param string      $date_start Datum od.
	 * @param string|null $date_end Datum do.
	 * @param string|null $qualification_code Kód kvalifikace.
	 * @param string|null $admin_title Administrativní název pro fallback kurzů.
	 * @return string
	 */
	public static function build(
		string $term_type,
		string $date_start,
		?string $date_end = null,
		?string $qualification_code = null,
		?string $admin_title = null
	): string {
		$start = new \DateTime( $date_start );

		if ( 'zkouska' === $term_type ) {
			return self::build_exam_label( $start, $qualification_code );
		}

		return self::build_course_label( $start, $date_start, $date_end, $qualification_code, $admin_title );
	}

	/**
	 * Build a range label like "kurz: 17. - 19. dubna 2026".
	 *
	 * @param string    $prefix Prefix label.
	 * @param \DateTime $start Start date.
	 * @param \DateTime $end End date.
	 * @return string
	 */
	private static function build_range_label( string $prefix, \DateTime $start, \DateTime $end ): string {
		$day_s   = (int) $start->format( 'j' );
		$month_s = (int) $start->format( 'n' );
		$year_s  = $start->format( 'Y' );

		$day_e   = (int) $end->format( 'j' );
		$month_e = (int) $end->format( 'n' );
		$year_e  = $end->format( 'Y' );

		if ( $month_s === $month_e && $year_s === $year_e ) {
			$month_name = self::MONTHS[ $month_e ];
			return "{$prefix}: {$day_s}. - {$day_e}. {$month_name} {$year_e}";
		}

		if ( $year_s === $year_e ) {
			$month_name_s = self::MONTHS[ $month_s ];
			$month_name_e = self::MONTHS[ $month_e ];
			return "{$prefix}: {$day_s}. {$month_name_s} - {$day_e}. {$month_name_e} {$year_e}";
		}

		$month_name_s = self::MONTHS[ $month_s ];
		$month_name_e = self::MONTHS[ $month_e ];
		return "{$prefix}: {$day_s}. {$month_name_s} {$year_s} - {$day_e}. {$month_name_e} {$year_e}";
	}

	/**
	 * Build a course label.
	 *
	 * @param \DateTime    $start Start date.
	 * @param string       $date_start Start date string.
	 * @param string|null  $date_end End date string.
	 * @param string|null  $qualification_code Qualification code.
	 * @param string|null  $admin_title Admin title fallback.
	 * @return string
	 */
	private static function build_course_label(
		\DateTime $start,
		string $date_start,
		?string $date_end = null,
		?string $qualification_code = null,
		?string $admin_title = null
	): string {
		$qualification_code = trim( (string) $qualification_code );
		$admin_title        = trim( (string) $admin_title );
		$prefix             = '' !== $qualification_code
			? 'Kurz: Příprava na zkoušku ' . $qualification_code
			: 'Kurz: ' . ( '' !== $admin_title ? $admin_title : 'Kurz' );

		if ( $date_end && $date_end !== $date_start ) {
			$end = new \DateTime( $date_end );

			return self::build_course_range_label( $prefix, $start, $end );
		}

		return $prefix . ' (' . self::format_single_date( $start ) . ')';
	}

	/**
	 * Build a course range label with parentheses.
	 *
	 * @param string    $prefix Prefix label.
	 * @param \DateTime $start Start date.
	 * @param \DateTime $end End date.
	 * @return string
	 */
	private static function build_course_range_label( string $prefix, \DateTime $start, \DateTime $end ): string {
		$day_s   = (int) $start->format( 'j' );
		$month_s = (int) $start->format( 'n' );
		$year_s  = $start->format( 'Y' );

		$day_e   = (int) $end->format( 'j' );
		$month_e = (int) $end->format( 'n' );
		$year_e  = $end->format( 'Y' );

		if ( $month_s === $month_e && $year_s === $year_e ) {
			$month_name = self::MONTHS[ $month_e ];
			return "{$prefix} ({$day_s}. – {$day_e}. {$month_name} {$year_e})";
		}

		if ( $year_s === $year_e ) {
			$month_name_s = self::MONTHS[ $month_s ];
			$month_name_e = self::MONTHS[ $month_e ];
			return "{$prefix} ({$day_s}. {$month_name_s} – {$day_e}. {$month_name_e} {$year_e})";
		}

		$month_name_s = self::MONTHS[ $month_s ];
		$month_name_e = self::MONTHS[ $month_e ];
		return "{$prefix} ({$day_s}. {$month_name_s} {$year_s} – {$day_e}. {$month_name_e} {$year_e})";
	}

	/**
	 * Build an exam label.
	 *
	 * @param \DateTime    $date Exam date.
	 * @param string|null  $qualification_code Qualification accreditation code.
	 * @return string
	 */
	private static function build_exam_label( \DateTime $date, ?string $qualification_code = null ): string {
		$qualification_code = trim( (string) $qualification_code );
		$formatted_date     = self::format_single_date( $date );

		if ( '' !== $qualification_code ) {
			return "Zkouška z profesní kvalifikace {$qualification_code} ({$formatted_date})";
		}

		return "zkouška: {$formatted_date}";
	}

	/**
	 * Format a single Czech date like "22. dubna 2026".
	 *
	 * @param \DateTime $date Date instance.
	 * @return string
	 */
	private static function format_single_date( \DateTime $date ): string {
		$day   = (int) $date->format( 'j' );
		$month = self::MONTHS[ (int) $date->format( 'n' ) ];
		$year  = $date->format( 'Y' );

		return "{$day}. {$month} {$year}";
	}

	/**
	 * Generate a term_key from type and dates.
	 *
	 * kurz: "kurz_2026_04_17_19"
	 * zkouška: "zkouska_2026_04_25"
	 *
	 * @param string      $term_type Kurz nebo zkouška.
	 * @param string      $date_start Datum od.
	 * @param string|null $date_end Datum do.
	 * @return string
	 */
	public static function build_key( string $term_type, string $date_start, ?string $date_end = null ): string {
		$start = new \DateTime( $date_start );
		$base  = 'kurz' === $term_type ? 'kurz' : 'zkouska';
		$key   = $base . '_' . $start->format( 'Y_m_d' );

		if ( 'kurz' === $term_type && $date_end && $date_end !== $date_start ) {
			$end = new \DateTime( $date_end );
			$key .= '_' . $end->format( 'd' );
		}

		return $key;
	}
}

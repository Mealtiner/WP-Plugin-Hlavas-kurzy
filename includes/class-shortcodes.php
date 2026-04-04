<?php
/**
 * Frontend shortcodes for HLAVAS Terms plugin.
 *
 * Provides GDPR-safe public output — term schedules, capacities and
 * waitlist registration. Never exposes personal data of participants.
 *
 * Registered shortcodes:
 *   [hlavas_terms]         — table of upcoming terms
 *   [hlavas_term_capacity] — capacity badge for one term
 *   [hlavas_waitlist]      — waitlist sign-up form for a full term
 *
 * Compatible with Elementor, Divi, Bricks, Gutenberg (shortcode block)
 * and any theme/builder that renders do_shortcode().
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( ! class_exists( 'Hlavas_Terms_Repository', false ) ) {
	require_once __DIR__ . '/class-repository.php';
}

if ( ! class_exists( 'Hlavas_Terms_Availability_Service', false ) ) {
	require_once __DIR__ . '/class-availability-service.php';
}

class Hlavas_Terms_Shortcodes {

	private Hlavas_Terms_Repository $repo;
	private Hlavas_Terms_Availability_Service $availability;

	public function __construct(
		?Hlavas_Terms_Repository $repo = null,
		?Hlavas_Terms_Availability_Service $availability = null
	) {
		$this->repo         = $repo ?? new Hlavas_Terms_Repository();
		$this->availability = $availability ?? new Hlavas_Terms_Availability_Service( $this->repo );
	}

	/**
	 * Register all shortcodes.
	 *
	 * @return void
	 */
	public function register(): void {
		add_shortcode( 'hlavas_terms',         [ $this, 'render_terms_table' ] );
		add_shortcode( 'hlavas_term_capacity', [ $this, 'render_capacity_badge' ] );
		add_shortcode( 'hlavas_waitlist',      [ $this, 'render_waitlist_form' ] );
		add_action( 'wp_enqueue_scripts',      [ $this, 'enqueue_frontend_styles' ] );
		add_action( 'wp_ajax_hlavas_waitlist_submit',        [ $this, 'handle_waitlist_submit' ] );
		add_action( 'wp_ajax_nopriv_hlavas_waitlist_submit', [ $this, 'handle_waitlist_submit' ] );
	}

	/* ---------------------------------------------------------------
	 * SHORTCODE: [hlavas_terms]
	 *
	 * Attributes:
	 *   type        = kurz | zkouska | all    (default: all)
	 *   show        = upcoming | all          (default: upcoming)
	 *   limit       = int                     (default: 0 = unlimited)
	 *   show_capacity = yes | no              (default: yes)
	 *   qualification = type_key or empty     (default: empty = all)
	 *   class       = extra CSS class
	 * ------------------------------------------------------------- */

	/**
	 * Render terms table.
	 *
	 * @param array<string, string>|string $atts Shortcode attributes.
	 * @return string HTML output.
	 */
	public function render_terms_table( array|string $atts ): string {
		$atts = shortcode_atts(
			[
				'type'          => 'all',
				'show'          => 'upcoming',
				'limit'         => '0',
				'show_capacity' => 'yes',
				'qualification' => '',
				'class'         => '',
			],
			(array) $atts,
			'hlavas_terms'
		);

		$args = [
			'is_visible'  => true,
			'is_active'   => true,
			'is_archived' => false,
		];

		if ( 'all' !== $atts['type'] ) {
			$args['term_type'] = sanitize_key( $atts['type'] );
		}

		if ( 'upcoming' === $atts['show'] ) {
			$args['future_only'] = true;
		}

		$terms = $this->repo->get_all( $args );

		// Filter by qualification type_key if requested.
		$qual_filter = sanitize_key( $atts['qualification'] );
		if ( '' !== $qual_filter ) {
			$terms = array_values(
				array_filter(
					$terms,
					static fn( object $t ): bool =>
						isset( $t->qualification_key ) && (string) $t->qualification_key === $qual_filter
				)
			);
		}

		$limit = (int) $atts['limit'];
		if ( $limit > 0 ) {
			$terms = array_slice( $terms, 0, $limit );
		}

		if ( empty( $terms ) ) {
			return '<p class="hlavas-sc-empty">' . esc_html__( 'Žádné termíny nejsou momentálně k dispozici.', 'hlavas-terms' ) . '</p>';
		}

		$show_capacity = 'yes' === $atts['show_capacity'];
		$extra_class   = '' !== $atts['class'] ? ' ' . sanitize_html_class( $atts['class'] ) : '';

		ob_start();
		?>
		<div class="hlavas-sc-wrap hlavas-sc-terms-wrap<?php echo esc_attr( $extra_class ); ?>">
			<table class="hlavas-sc-table hlavas-sc-terms-table">
				<thead>
					<tr>
						<th class="hlavas-sc-col-term"><?php esc_html_e( 'Termín', 'hlavas-terms' ); ?></th>
						<th class="hlavas-sc-col-type"><?php esc_html_e( 'Typ', 'hlavas-terms' ); ?></th>
						<th class="hlavas-sc-col-date"><?php esc_html_e( 'Datum', 'hlavas-terms' ); ?></th>
						<th class="hlavas-sc-col-deadline"><?php esc_html_e( 'Uzávěrka', 'hlavas-terms' ); ?></th>
						<?php if ( $show_capacity ) : ?>
							<th class="hlavas-sc-col-capacity"><?php esc_html_e( 'Volná místa', 'hlavas-terms' ); ?></th>
						<?php endif; ?>
					</tr>
				</thead>
				<tbody>
					<?php foreach ( $terms as $term ) : ?>
						<?php
						$remaining  = $show_capacity ? $this->availability->get_remaining( (string) $term->term_key ) : null;
						$full       = $show_capacity && 0 === $remaining;
						$label      = ! empty( $term->title ) ? (string) $term->title : (string) $term->label;
						$type_label = 'kurz' === $term->term_type ? __( 'Kurz', 'hlavas-terms' ) : __( 'Zkouška', 'hlavas-terms' );
						$date_str   = $this->format_date_range( (string) $term->date_start, (string) ( $term->date_end ?? '' ) );
						$deadline   = ! empty( $term->enrollment_deadline )
							? wp_date( 'j. n. Y', strtotime( (string) $term->enrollment_deadline ) )
							: '—';
						?>
						<tr class="hlavas-sc-term-row<?php echo $full ? ' hlavas-sc-term-full' : ''; ?>">
							<td class="hlavas-sc-col-term">
								<?php echo esc_html( $label ); ?>
								<?php if ( ! empty( $term->qualification_name ) ) : ?>
									<span class="hlavas-sc-qualification"><?php echo esc_html( (string) $term->qualification_name ); ?></span>
								<?php endif; ?>
							</td>
							<td class="hlavas-sc-col-type">
								<span class="hlavas-sc-type-badge hlavas-sc-type-<?php echo esc_attr( (string) $term->term_type ); ?>">
									<?php echo esc_html( $type_label ); ?>
								</span>
							</td>
							<td class="hlavas-sc-col-date"><?php echo esc_html( $date_str ); ?></td>
							<td class="hlavas-sc-col-deadline"><?php echo esc_html( $deadline ); ?></td>
							<?php if ( $show_capacity ) : ?>
								<td class="hlavas-sc-col-capacity">
									<?php if ( $full ) : ?>
										<span class="hlavas-sc-capacity-full"><?php esc_html_e( 'Obsazeno', 'hlavas-terms' ); ?></span>
									<?php else : ?>
										<span class="hlavas-sc-capacity-free"><?php echo esc_html( (string) $remaining ); ?></span>
									<?php endif; ?>
								</td>
							<?php endif; ?>
						</tr>
					<?php endforeach; ?>
				</tbody>
			</table>
		</div>
		<?php
		return ob_get_clean();
	}

	/* ---------------------------------------------------------------
	 * SHORTCODE: [hlavas_term_capacity term_key="kurz_2026_05_15_17"]
	 *
	 * Attributes:
	 *   term_key    = term_key (required)
	 *   format      = badge | text | number   (default: badge)
	 * ------------------------------------------------------------- */

	/**
	 * Render capacity badge for a single term.
	 *
	 * @param array<string, string>|string $atts Shortcode attributes.
	 * @return string HTML output.
	 */
	public function render_capacity_badge( array|string $atts ): string {
		$atts = shortcode_atts(
			[
				'term_key' => '',
				'format'   => 'badge',
			],
			(array) $atts,
			'hlavas_term_capacity'
		);

		$term_key = sanitize_key( $atts['term_key'] );

		if ( '' === $term_key ) {
			return '';
		}

		$term = $this->repo->find_by_key( $term_key );

		if ( ! $term ) {
			return '';
		}

		$remaining = $this->availability->get_remaining( $term_key );
		$capacity  = (int) $term->capacity;
		$full      = 0 === $remaining;

		if ( 'number' === $atts['format'] ) {
			return '<span class="hlavas-sc-capacity-number">' . esc_html( (string) $remaining ) . '</span>';
		}

		if ( 'text' === $atts['format'] ) {
			return $full
				? '<span class="hlavas-sc-capacity-text hlavas-sc-capacity-full">' . esc_html__( 'Termín je plný', 'hlavas-terms' ) . '</span>'
				: '<span class="hlavas-sc-capacity-text hlavas-sc-capacity-free">'
					. esc_html(
						sprintf(
							/* translators: %1$d = remaining spots, %2$d = total capacity */
							__( 'Volná místa: %1$d z %2$d', 'hlavas-terms' ),
							$remaining,
							$capacity
						)
					)
					. '</span>';
		}

		// Default: badge.
		$css_class = $full ? 'hlavas-sc-badge hlavas-sc-badge-full' : 'hlavas-sc-badge hlavas-sc-badge-free';
		$label     = $full
			? esc_html__( 'Obsazeno', 'hlavas-terms' )
			: esc_html( (string) $remaining ) . ' ' . esc_html__( 'volných míst', 'hlavas-terms' );

		return '<span class="' . esc_attr( $css_class ) . '">' . $label . '</span>';
	}

	/* ---------------------------------------------------------------
	 * SHORTCODE: [hlavas_waitlist term_key="kurz_2026_05_15_17"]
	 *
	 * Attributes:
	 *   term_key    = term_key (required)
	 *   show_always = yes | no  (default: no — show only when term is full)
	 * ------------------------------------------------------------- */

	/**
	 * Render waitlist sign-up form.
	 *
	 * @param array<string, string>|string $atts Shortcode attributes.
	 * @return string HTML output.
	 */
	public function render_waitlist_form( array|string $atts ): string {
		$atts = shortcode_atts(
			[
				'term_key'    => '',
				'show_always' => 'no',
			],
			(array) $atts,
			'hlavas_waitlist'
		);

		$term_key = sanitize_key( $atts['term_key'] );

		if ( '' === $term_key ) {
			return '';
		}

		$term = $this->repo->find_by_key( $term_key );

		if ( ! $term ) {
			return '';
		}

		$remaining   = $this->availability->get_remaining( $term_key );
		$show_always = 'yes' === $atts['show_always'];

		// Show only when full (unless show_always = yes).
		if ( ! $show_always && $remaining > 0 ) {
			return '';
		}

		$form_id     = 'hlavas-waitlist-' . esc_attr( $term_key );
		$term_label  = ! empty( $term->title ) ? (string) $term->title : (string) $term->label;
		$nonce       = wp_create_nonce( 'hlavas_waitlist_' . $term_key );

		ob_start();
		?>
		<div class="hlavas-sc-wrap hlavas-sc-waitlist-wrap" id="<?php echo esc_attr( $form_id . '-wrap' ); ?>">
			<h3 class="hlavas-sc-waitlist-title">
				<?php esc_html_e( 'Zapsat na čekací listinu', 'hlavas-terms' ); ?>
			</h3>
			<p class="hlavas-sc-waitlist-term">
				<?php echo esc_html( $term_label ); ?>
			</p>
			<?php if ( $remaining <= 0 ) : ?>
				<p class="hlavas-sc-waitlist-notice">
					<?php esc_html_e( 'Termín je momentálně plný. Zanechte nám kontakt a dáme vám vědět, jakmile se uvolní místo.', 'hlavas-terms' ); ?>
				</p>
			<?php endif; ?>
			<form
				class="hlavas-sc-waitlist-form"
				id="<?php echo esc_attr( $form_id ); ?>"
				data-term-key="<?php echo esc_attr( $term_key ); ?>"
				novalidate
			>
				<input type="hidden" name="action"    value="hlavas_waitlist_submit">
				<input type="hidden" name="term_key"  value="<?php echo esc_attr( $term_key ); ?>">
				<input type="hidden" name="_nonce"    value="<?php echo esc_attr( $nonce ); ?>">

				<div class="hlavas-sc-waitlist-field">
					<label for="<?php echo esc_attr( $form_id ); ?>-name">
						<?php esc_html_e( 'Jméno a příjmení', 'hlavas-terms' ); ?> <span aria-hidden="true">*</span>
					</label>
					<input
						type="text"
						id="<?php echo esc_attr( $form_id ); ?>-name"
						name="waitlist_name"
						required
						autocomplete="name"
						maxlength="120"
					>
				</div>

				<div class="hlavas-sc-waitlist-field">
					<label for="<?php echo esc_attr( $form_id ); ?>-email">
						<?php esc_html_e( 'E-mail', 'hlavas-terms' ); ?> <span aria-hidden="true">*</span>
					</label>
					<input
						type="email"
						id="<?php echo esc_attr( $form_id ); ?>-email"
						name="waitlist_email"
						required
						autocomplete="email"
						maxlength="254"
					>
				</div>

				<div class="hlavas-sc-waitlist-field hlavas-sc-waitlist-gdpr">
					<label>
						<input type="checkbox" name="waitlist_gdpr" value="1" required>
						<?php esc_html_e( 'Souhlasím se zpracováním svého jména a e-mailové adresy za účelem zařazení na čekací listinu kurzu. Data budou smazána po obsazení místa nebo odmítnutí nabídky.', 'hlavas-terms' ); ?>
						<span aria-hidden="true">*</span>
					</label>
				</div>

				<div class="hlavas-sc-waitlist-actions">
					<button type="submit" class="hlavas-sc-waitlist-submit">
						<?php esc_html_e( 'Zařadit na čekací listinu', 'hlavas-terms' ); ?>
					</button>
				</div>

				<div class="hlavas-sc-waitlist-response" aria-live="polite"></div>
			</form>
		</div>
		<script>
		(function(){
			var form = document.getElementById(<?php echo wp_json_encode( $form_id ); ?>);
			if (!form) return;
			form.addEventListener('submit', function(e){
				e.preventDefault();
				var btn      = form.querySelector('.hlavas-sc-waitlist-submit');
				var response = form.querySelector('.hlavas-sc-waitlist-response');
				btn.disabled = true;
				var data = new FormData(form);
				fetch(<?php echo wp_json_encode( admin_url( 'admin-ajax.php' ) ); ?>, {
					method: 'POST',
					body: data,
					credentials: 'same-origin'
				})
				.then(function(r){ return r.json(); })
				.then(function(json){
					response.className = 'hlavas-sc-waitlist-response ' +
						(json.success ? 'hlavas-sc-waitlist-ok' : 'hlavas-sc-waitlist-error');
					response.textContent = json.data || '';
					if (json.success) {
						form.querySelectorAll('input,button').forEach(function(el){ el.disabled = true; });
					} else {
						btn.disabled = false;
					}
				})
				.catch(function(){
					response.className = 'hlavas-sc-waitlist-response hlavas-sc-waitlist-error';
					response.textContent = <?php echo wp_json_encode( __( 'Chyba připojení, zkuste to prosím znovu.', 'hlavas-terms' ) ); ?>;
					btn.disabled = false;
				});
			});
		}());
		</script>
		<?php
		return ob_get_clean();
	}

	/* ---------------------------------------------------------------
	 * AJAX: waitlist submission
	 * ------------------------------------------------------------- */

	/**
	 * Handle waitlist AJAX submission.
	 *
	 * Stores name + email in wp_options as a simple JSON list.
	 * No personal data is shown on the frontend — admin only.
	 *
	 * @return void
	 */
	public function handle_waitlist_submit(): void {
		$term_key = sanitize_key( $_POST['term_key'] ?? '' );
		$nonce    = sanitize_text_field( wp_unslash( $_POST['_nonce'] ?? '' ) );

		if ( '' === $term_key || ! wp_verify_nonce( $nonce, 'hlavas_waitlist_' . $term_key ) ) {
			wp_send_json_error( __( 'Neplatný bezpečnostní token. Obnovte stránku a zkuste znovu.', 'hlavas-terms' ) );
		}

		$term = $this->repo->find_by_key( $term_key );

		if ( ! $term ) {
			wp_send_json_error( __( 'Termín nebyl nalezen.', 'hlavas-terms' ) );
		}

		$name  = sanitize_text_field( wp_unslash( $_POST['waitlist_name'] ?? '' ) );
		$email = sanitize_email( wp_unslash( $_POST['waitlist_email'] ?? '' ) );
		$gdpr  = ! empty( $_POST['waitlist_gdpr'] );

		if ( '' === $name || '' === $email ) {
			wp_send_json_error( __( 'Vyplňte prosím jméno a e-mail.', 'hlavas-terms' ) );
		}

		if ( ! is_email( $email ) ) {
			wp_send_json_error( __( 'Zadejte prosím platnou e-mailovou adresu.', 'hlavas-terms' ) );
		}

		if ( ! $gdpr ) {
			wp_send_json_error( __( 'Pro zařazení na čekací listinu je nutný souhlas se zpracováním dat.', 'hlavas-terms' ) );
		}

		$option_key = 'hlavas_waitlist_' . $term_key;
		$list       = (array) get_option( $option_key, [] );

		// Prevent duplicate email entries for same term.
		foreach ( $list as $entry ) {
			if ( isset( $entry['email'] ) && strtolower( $entry['email'] ) === strtolower( $email ) ) {
				wp_send_json_success( __( 'Jste již na čekací listině tohoto termínu.', 'hlavas-terms' ) );
			}
		}

		$list[] = [
			'name'       => $name,
			'email'      => $email,
			'term_key'   => $term_key,
			'registered' => current_time( 'mysql' ),
		];

		update_option( $option_key, $list, false );

		hlavas_terms_log_event(
			'waitlist_signup',
			'Novy zaznam na cekaci listine.',
			[
				'term_key'  => $term_key,
				'term_label' => (string) ( $term->label ?? '' ),
			]
		);

		wp_send_json_success( __( 'Byli jste úspěšně zařazeni na čekací listinu. Dáme vám vědět, jakmile se místo uvolní.', 'hlavas-terms' ) );
	}

	/* ---------------------------------------------------------------
	 * Frontend styles
	 * ------------------------------------------------------------- */

	/**
	 * Enqueue minimal frontend CSS only on pages that use our shortcodes.
	 *
	 * @return void
	 */
	public function enqueue_frontend_styles(): void {
		global $post;

		if (
			! is_a( $post, 'WP_Post' ) ||
			! (
				has_shortcode( $post->post_content, 'hlavas_terms' ) ||
				has_shortcode( $post->post_content, 'hlavas_term_capacity' ) ||
				has_shortcode( $post->post_content, 'hlavas_waitlist' )
			)
		) {
			return;
		}

		wp_enqueue_style(
			'hlavas-terms-frontend',
			HLAVAS_TERMS_URL . 'frontend/css/shortcodes.css',
			[],
			HLAVAS_TERMS_VERSION
		);
	}

	/* ---------------------------------------------------------------
	 * HELPERS
	 * ------------------------------------------------------------- */

	/**
	 * Format a date range for display.
	 *
	 * @param string $date_start Start date (Y-m-d).
	 * @param string $date_end   End date (Y-m-d) or empty.
	 * @return string
	 */
	private function format_date_range( string $date_start, string $date_end ): string {
		if ( '' === $date_start ) {
			return '—';
		}

		$start = wp_date( 'j. n. Y', strtotime( $date_start ) );

		if ( '' === $date_end || $date_end === $date_start ) {
			return (string) $start;
		}

		$end = wp_date( 'j. n. Y', strtotime( $date_end ) );

		return $start . ' – ' . $end;
	}
}

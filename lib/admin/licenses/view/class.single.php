<?php
/**
 * Single license view.
 *
 * @author Iron Bound Designs
 * @since  1.0
 */

/**
 * Class ITELIC_Admin_Licenses_View_Single
 */
class ITELIC_Admin_Licenses_View_Single extends ITELIC_Admin_Tab_View {

	/**
	 * @var ITELIC_Key
	 */
	protected $key;

	/**
	 * Constructor.
	 *
	 * @since 1.0
	 *
	 * @param ITELIC_Key $key
	 */
	public function __construct( ITELIC_Key $key ) {
		$this->key = $key;

		wp_enqueue_style( 'itelic-admin-license-detail', ITELIC::$url . 'assets/css/admin-license-detail.css' );
	}

	/**
	 * Render the view.
	 */
	public function render() {
		?>

		<div id="it-exchange-license-details">
			<div class="spacing-wrapper bottom-border header-block">

				<div class="status status-<?php echo esc_attr( $this->key->get_status() ); ?>">
					<span><?php echo $this->key->get_status( true ); ?></span>
				</div>

				<div class="name-block">
					<h2 class="customer-name"><?php echo $this->key->get_customer()->wp_user->display_name; ?></h2>

					<h2 class="product-name"><?php echo $this->key->get_product()->post_title; ?></h2>
				</div>
				<div class="key-block">
					<p>
						<label for="license-key" class="screen-reader-text"><?php _e( "License Key", ITELIC::SLUG ); ?></label>
						<input type="text" id="license-key" size="<?php echo esc_attr( strlen( $this->key->get_key() ) ); ?>"
						       readonly value="<?php echo $this->key->get_key(); ?>">
					</p>
				</div>
			</div>

			<div class="spacing-wrapper bottom-border third-row misc-block">
				<div class="third expires">
					<h4><?php _e( "Expires", ITELIC::SLUG ); ?></h4>

					<h3>
						<?php if ( null === ( $d = $this->key->get_expires() ) ) : ?>
							<?php _e( "Forever", ITELIC::SLUG ); ?>
						<?php else: ?>
							<?php echo $d->format( $this->get_short_df() ); ?>
						<?php endif; ?>
					</h3>
				</div>
				<div class="third transaction">
					<h4><?php _e( "Transaction", ITELIC::SLUG ); ?></h4>

					<h3>
						<a href="<?php echo esc_url( get_edit_post_link( $this->key->get_transaction()->ID ) ); ?>">
							<?php echo it_exchange_get_transaction_order_number( $this->key->get_transaction() ); ?>
						</a>
					</h3>
				</div>
				<div class="third max-activations">
					<h4><?php _e( "Max Activations", ITELIC::SLUG ); ?></h4>

					<h3><?php echo $this->key->get_max(); ?></h3>
				</div>
			</div>

			<div class="spacing-wrapper activations">
				<h3><?php _e( "Activations", ITELIC::SLUG ); ?></h3>

				<table class="widefat">
					<thead>
					<tr>
						<th><?php _e( "ID", ITELIC::SLUG ); ?></th>
						<th><?php _e( "Location", ITELIC::SLUG ); ?></th>
						<th><?php _e( "Status", ITELIC::SLUG ); ?></th>
						<th><?php _e( "Activation", ITELIC::SLUG ); ?></th>
						<th><?php _e( "Deactivation", ITELIC::SLUG ); ?></th>
						<th><span class="screen-reader-text"><?php _e( "Delete", ITELIC::SLUG ); ?></span></th>
					</tr>
					</thead>

					<tbody>

					<?php foreach ( $this->key->get_activations() as $activation ): ?>

						<tr>
							<td><?php echo $activation->get_id(); ?></td>
							<td><?php echo $activation->get_location(); ?></td>
							<td><?php echo $activation->get_status( true ); ?></td>
							<td><?php echo $activation->get_activation()->format( $this->get_short_df() ); ?></td>
							<td>
								<?php if ( null === ( $d = $activation->get_deactivation() ) ): ?>
									<a href="javascript:" data-id="<?php echo esc_attr(); ?>" class="deactivate"><?php _e( "Deactivate", ITELIC::SLUG ); ?></a>
								<?php else: ?>
									<?php echo $d->format( $this->get_short_df() ); ?>
								<?php endif; ?>
							</td>
							<td><button data-id="<?php echo esc_attr(); ?>" class="remove-item">x</button></td>
						</tr>

					<?php endforeach; ?>
					</tbody>
				</table>

				<h4><?php _e( "Remote Activate", ITELIC::SLUG ); ?></h4>

				<label for="remote-activate-name"><?php _e( "Install Location", ITELIC::SLUG ); ?></label>
				<input type="text" id="remote-activate-name" placeholder="<?php _e( "www.store.com", ITELIC::SLUG ); ?>">
				<input type="submit" id="remote-activate-submit" class="it-exchange-button" value="<?php esc_attr_e( "Activate", ITELIC::SLUG ); ?>">
			</div>
		</div>

	<?php
	}

	/**
	 * Get the date format. Replace full month name, with short month name if possible.
	 *
	 * @since 1.0
	 *
	 * @return string
	 */
	protected function get_short_df() {
		return str_replace( 'F', 'M', get_option( 'date_format' ) );
	}

	/**
	 * Get the title of this view.
	 *
	 * @return string
	 */
	protected function get_title() {
		return __( "Manage License", ITELIC::SLUG );
	}
}
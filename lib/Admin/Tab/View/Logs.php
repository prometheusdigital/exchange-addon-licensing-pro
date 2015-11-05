<?php
/**
 * Logs view.
 *
 * @author    Iron Bound Designs
 * @since     1.0
 * @license   AGPL
 * @copyright Iron Bound Designs, 2015.
 */

namespace ITELIC\Admin\Tab\View;

use IronBound\DBLogger\ListTable;
use ITELIC\Admin\Tab\View;
use ITELIC\Plugin;

/**
 * Class Logs
 * @package ITELIC\Admin\Tab\View
 */
class Logs extends View {

	/**
	 * @var ListTable
	 */
	private $table;

	/**
	 * Logs constructor.
	 *
	 * @param ListTable $table
	 */
	public function __construct( ListTable $table ) {
		$this->table = $table;
	}

	/**
	 * Render the view.
	 */
	public function render() {

		$this->table->prepare_items();

		?>

		<form method="GET">
			<input type="hidden" name="page" value="<?php echo esc_attr( $_GET['page'] ); ?>">
			<input type="hidden" name="tab" value="<?php echo esc_attr( $_GET['tab'] ); ?>">

			<?php $this->table->views(); ?>
			<?php $this->table->search_box( __( "Search", Plugin::SLUG ), 'itelic-search' ); ?>
			<?php $this->table->display(); ?>
		</form>

		<?php
	}

	/**
	 * Get the title of this view.
	 *
	 * @return string
	 */
	protected function get_title() {
		return __( "Logs", Plugin::SLUG );
	}
}
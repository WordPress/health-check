<?php
/**
 * Base class for the Tools tab to be extended.
 *
 * @package Health Check
 */

// Make sure the file is not directly accessible.
if ( ! defined( 'ABSPATH' ) ) {
	die( 'We\'re sorry, but you can not directly access this file.' );
}

/**
 * Class Health_Check_Tools
 */
abstract class Health_Check_Tool {
	protected $description;
	protected $label;

	public function __construct() {
		add_filter( 'health_check_tools_tab', array( $this, 'tab_setup' ) );
	}

	public function tab_setup( $tabs ) {
		if ( ! isset( $this->label ) || empty( $this->label ) ) {
			return $tabs;
		}

		ob_start();
		?>

		<div>
			<?php if ( $this->has_description() ) : ?>
			<p><?php echo $this->get_description(); ?></p>
			<?php endif; ?>

			<?php $this->tab_content(); ?>
		</div>

		<?php

		$tab_content = ob_get_clean();

		$tabs[] = array(
			'label'   => $this->label,
			'content' => $tab_content,
		);

		return $tabs;
	}

	public function tab_content() {}

	public function has_description() {
		return isset( $this->description ) && ! empty( $this->description );
	}

	public function get_description() {
		return $this->description;
	}
}

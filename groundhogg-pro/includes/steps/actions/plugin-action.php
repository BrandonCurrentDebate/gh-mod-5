<?php

namespace GroundhoggPro\Steps\Actions;

use Groundhogg\Step;
use function Groundhogg\html;

class Plugin_Action extends \Groundhogg\Steps\Premium\Actions\Plugin_Action {

	/**
	 * Display the settings based on the given ID
	 *
	 * @param $step Step
	 */
	public function settings( $step ) {

		$callname = $this->get_setting( 'call_name' );

		echo html()->e( 'p', [], html()->e( 'b', [], __( 'Action' ) ) );

		echo html()->input( [
			'id'    => $this->setting_id_prefix( 'call_name' ),
			'name'  => $this->setting_name_prefix( 'call_name' ),
			'value' => $this->get_setting( 'call_name' ),
			'class' => 'full-width code'
		] );
		echo html()->description( __( 'The plugin action to do.', 'groundhogg-pro' ) );

		echo html()->e( 'p', [], html()->e( 'b', [], __( 'Usage' ) ) );

		echo html()->textarea( [
			'class'    => 'code full-width copy-text',
			'value'    => "<?php

add_filter( '$callname', '$callname', 10, 2 );

/**
 * Your custom script to run
 * 
 * @param \$success bool|WP_Error 
 * @param \$contact \Groundhogg\Contact
 *                                     
 * @return true|false|WP_Error true if success, false to skip, WP_Error if failed
 */
function $callname( \$success, \$contact ){
	
    // Return out if the action previously failed
    if ( ! \$success || is_wp_error( \$success ) ){
    	return \$success;
    }
    
    // todo you code here
    \$contact->update_meta( 'some_field', 'your data' );
    
    return true;
}",
			'cols'     => '',
			'rows'     => 25,
			'wrap'     => 'off',
			'readonly' => true,
			'onfocus'  => "this.select()"
		] );

		echo html()->description( __( 'Copy and paste the above code into a custom plugin or your theme\'s functions.php file.', 'groundhogg-pro' ) );
	}

	/**
	 * @param \Groundhogg\Contact $contact
	 * @param \Groundhogg\Event   $event
	 *
	 * @return bool|mixed|void
	 */
	public function run( $contact, $event ) {
		return apply_filters( $this->get_setting( 'call_name' ), true, $contact );
	}

	public function get_settings_schema() {
		return [
			'call_name' => [
				'sanitize' => 'sanitize_key',
				'initial'  => uniqid( 'step_' ),
				'default'  => ''
			]
		];
	}

	public function generate_step_title( $step ) {
		return sprintf( 'Call <code>%s</code>', $this->get_setting( 'call_name' ) );
	}
}

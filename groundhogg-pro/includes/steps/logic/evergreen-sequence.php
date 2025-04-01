<?php

namespace GroundhoggPro\Steps\Logic;

use Groundhogg\Contact;
use function Groundhogg\get_contactdata;
use function Groundhogg\html;

class Evergreen_Sequence extends \Groundhogg\Steps\Premium\Logic\Evergreen_Sequence {

	public function generate_step_title( $step ) {
		return false;
	}

	public function settings( $step ) {

		$next = $this->get_logic_action( get_contactdata() ?: new Contact() );

		?>
        <p>The contact will jump to the timer within the sequence where the date is closest to the current date.</p>
		<?php

		$timers = $this->get_sub_timer_steps();

		if ( ! empty( $timers ) ) {
			echo html()->e( 'p', [], __( 'Optionally select specific timers within the sequence where contacts can be added.', 'groundhogg-pro' ) );

			$options = [];

			foreach ( $timers as $_s ) {
				$options[ $_s->get_id() ] = sprintf( '%d. %s', $_s->get_order(), sanitize_text_field( $_s->get_title() ) );
			}

			echo html()->e( 'div', [
				'class' => 'display-flex gap-5 align-center ignore-morph'
			], [
				html()->select2( [
					'id'          => $this->setting_id_prefix( 'timers' ),
					'name'        => $this->setting_name_prefix( 'timers' ) . '[]',
					'options'     => $options,
					'selected'    => $this->get_setting( 'timers' ),
					'multiple'    => true,
					'placeholder' => __( 'Any timer', 'groundhogg-pro' ),
				] ),
			] );
		}

		if ( $next ) {
			?>
            <p>If a contact were added to the sequence now, they'd start at...</p>
            <p><b><?php echo $next->get_title(); ?></b></p>
			<?php
		} else {
            ?><p></p><?php
		}
	}
}

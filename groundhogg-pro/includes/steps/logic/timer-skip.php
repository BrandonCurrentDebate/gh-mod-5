<?php

namespace GroundhoggPro\Steps\Logic;

use Groundhogg\Contact;
use Groundhogg\Step;
use Groundhogg\Steps\Actions\DelayDateTime;
use function Groundhogg\array_map_to_step;
use function Groundhogg\get_contactdata;
use function Groundhogg\html;

class Timer_Skip extends \Groundhogg\steps\premium\logic\Timer_Skip {

	public function validate_settings( Step $step ) {

		$timers = $this->get_setting( 'timers' );
		$timers = array_map_to_step( $timers );

		if ( empty( $timers ) ) {
			$step->add_error( 'no_timers', 'Please select a timer to skip to.' );

			return;
		}

		foreach ( $timers as $timer ) {

			if ( $timer->is_before( $step ) ) {
				$step->add_error( 'loop_broken', 'The target step can\'t come before the skip step.' );

				return;
			}

			if ( ! $timer->is_same_branch( $step ) ) {
				$step->add_error( 'loop_broken', 'The target step can\'t be in a different branch than the skip step.' );

				return;
			}
		}
	}

	/**
	 * @param Step $step
	 *
	 * @return void
	 */
	public function settings( $step ) {

		echo html()->e( 'p', [], __( 'Select timers to compare and possibly skip to. The contact will skip directly to the timer where the calculated date is closest to the current date. If the calculated date is in the past it will be ignored.', 'groundhogg-pro' ) );

		$steps = $step->get_funnel()->get_steps();

		$proceeding = array_filter( $steps, function ( $proceeding ) use ( $step ) {
			return $proceeding->is_after( $step ) && $proceeding->is_same_branch( $step ) && $proceeding->is_timer();
		} );

		$options = [];

		foreach ( $proceeding as $_s ) {
			$options[ $_s->get_id() ] = sprintf( '%d. %s', $_s->get_order(), sanitize_text_field( $_s->get_title() ) );
		}

		echo html()->e( 'div', [
			'class' => 'display-flex gap-5 align-center'
		], [
			html()->select2( [
				'id'       => $this->setting_id_prefix( 'timers' ),
				'name'     => $this->setting_name_prefix( 'timers' ) . '[]',
				'options'  => $options,
				'selected' => $this->get_setting( 'timers' ),
				'multiple' => true,
			] ),
		] );

		?><p></p><?php
	}

	public function get_settings_schema() {
		return [
			'timers' => [
				'sanitize' => function ( $value ) {
					$ids = wp_parse_id_list( $value );

					return array_filter( $ids, function ( $id ) {
						return ( new Step( $id ) )->exists();
					} );
				},
				'default'  => [],
				'initial'  => []
			]
		];
	}

	/**
	 * update the next if defined
	 *
	 * @param $step Step
	 *
	 * @return void
	 */
	public function post_import( $step ) {

		// This will be the ID of the imported step
		$old_next = absint( $step->get_meta( 'next' ) );

		if ( ! $old_next ) {
			return;
		}

		// the latest one will always be the most recently imported
		$meta = get_db( 'stepmeta' )->query( [
			'meta_key'   => 'imported_step_id',
			'meta_value' => $old_next,
			'limit'      => 1,
			'orderby'    => 'step_id',
			'order'      => 'desc'
		] );

		$step_id = $meta[0]->step_id;

		$step->update_meta( 'next', $step_id );

	}

	/**
	 * GEnerate a step title
	 *
	 * @param $step
	 *
	 * @return string|null
	 */
	public function generate_step_title( $step ) {

		$timers      = $this->get_setting( 'timers' );
		$timer_count = count( $timers );

		if ( $timer_count > 2 ) {

		}

		return 'Skip to the timer with the closest future date.';
	}

	/**
	 * Return the step to skip to
	 *
	 * @param Contact $contact
	 *
	 * @return false|Step
	 */
	public function get_logic_action( Contact $contact ) {

		$timers = $this->get_setting( 'timers' );

		if ( empty( $timers ) ) {
			return false;
		}

        $timers = array_map_to_step( $timers );

        $calculated = [];

        foreach ( $timers as $timer ) {

            if ( ! $timer->is_timer() || ! $timer->is_same_branch( $this->get_current_step() ) ) {
                continue;
            }

            if ( $this->get_current_step()->get_funnel()->is_active() && ! $timer->is_active() ){
                continue;
            }

            $diff = $timer->get_run_time() - time();

            if ( $diff < 0 ){
                continue;
            }

            $calculated[ $diff ] = $timer;
        }

        if ( empty( $calculated ) ) {
            return false;
        }

        $min = min( array_keys( $calculated ) );

        return $calculated[$min];
	}


	/**
	 * Show a preview of the run time
	 *
	 * @param Step $step
	 *
	 * @return void
	 */
	protected function before_step_notes( Step $step ) {

		$next = $this->get_logic_action( get_contactdata() ?: new Contact() );

        if ( $next === false ){
            return;
        }

		?>
        <div class="gh-panel">
            <div class="gh-panel-header">
                <h2><?php _e( 'Skip Preview' ) ?></h2>
            </div>
            <div class="inside">
                <p>If a contact were added to the funnel now they would skip to...</p>
				<?php
                echo $next->get_title()
				?>
            </div>
        </div>
		<?php
	}

}

<?php

namespace GroundhoggPro\Steps\Logic;

use function Groundhogg\_nf;
use function Groundhogg\dashicon_e;
use function Groundhogg\html;

class Split_Test extends \Groundhogg\Steps\Premium\Logic\Split_Test {

	protected function settings_should_ignore_morph() {
		return false;
	}

	protected function before_settings( \Groundhogg\Step $step ) {

		// don't show report if step is not active
		if ( ! $this->is_test_active() ) {
			return;
		}

		echo html()->e( 'div', [ 'class' => 'pill green' ], __( 'This test is in progress and cannot be modified until completed or a winner is declared.' ) );

		$report = $this->get_split_test_report();

		?>
        <div class="display-flex gap-20 align-center">
            <div class="gh-panel full-width">
                <div class="gh-panel-header">
                    <h2>Test Results</h2>
                </div>
                <style>
                    .groundhogg-report-table {
                        th:last-child,
                        td:last-child {
                            width: 60px !important;
                            box-sizing: border-box;
                        }
                    }
                </style>
                <table class="groundhogg-report-table" style="overflow: visible">
                    <thead>
                    <th>Branch</th>
                    <th>Contacts</th>
                    <th>Results</th>
                    <th></th>
                    </thead>
                    <tbody>
					<?php foreach ( $report as $branch => $cells ): ?>
                        <tr>
                            <td><?php _e( $this->get_branch_name( $branch ) ); ?></td>
                            <td><?php _e( _nf( $cells['contacts'] ) ); ?></td>
                            <td><?php _e( _nf( $cells['outcomes'] ) ); ?></td>
                            <td style="overflow: visible;">
                                <button type="button" data-branch="<?php esc_attr_e( $branch ); ?>" class="declare-winner gh-button text primary">
									<?php dashicon_e( 'thumbs-up' ); ?>
                                    <div class="gh-tooltip right">Declare winner</div>
                                </button>
                            </td>
                        </tr>
					<?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
		<?php

	}

	public function save( $step ) {

        // reset the test
		if ( $this->get_posted_data( 'start_new_test' ) ) {
			$this->save_setting( 'winner', '' );
			// set the status to inactive
			$step->update( [
				'step_status' => 'inactive'
			] );
		}
	}

	public function settings( $step ) {

		$winner = $this->get_setting( 'winner' );

		echo html()->e( 'p', [], __( 'Describe your test...' ) );

		echo html()->input( [
			'placeholder' => 'A/B Test',
			'value'       => $this->get_setting( 'test_name' ),
			'name'        => $this->setting_name_prefix( 'test_name' ),
		] );

		if ( $winner && $step->is_active() ) {
			echo html()->e( 'p', [], __( 'A winner was declared so all contacts will travel down the winning branch.' ) );
			echo html()->button( [
				'class' => 'gh-button primary start-new-test',
				'text'  => 'Start new test',
				'id'    => $this->setting_id_prefix( 'start_new_test' ),
			] );
			?><p></p><?php

			return;
		}

		echo html()->e( 'p', [], __( 'Contacts will be randomly sent down either branch <span class="pill purple">A</span> or branch <span class="pill purple">B</span>.' ) );
		echo html()->e( 'p', [], __( 'How should traffic be split between <span class="pill purple">A</span> / <span class="pill purple">B</span>?' ) );

		echo html()->e( 'div', [
			'class' => 'gh-input-group'
		], [
			html()->dropdown( [
				'selected' => absint( $this->get_setting( 'weight' ) ),
				'name'     => $this->setting_name_prefix( 'weight' ),
				'class'    => 'small',
				'options'  => [
					10 => '10% / 90%',
					20 => '20% / 80%',
					30 => '30% / 70%',
					40 => '40% / 60%',
					50 => '50% / 50%',
					60 => '60% / 40%',
					70 => '70% / 30%',
					80 => '80% / 20%',
					90 => '90% / 10%',
				],
				'disabled' => $this->is_test_active()
			] ),
		] );

		echo html()->e( 'p', [], __( 'Define the win condition.' ) );

		echo html()->dropdown( [
			'placeholder' => 'Path A weight',
			'selected'    => $this->get_setting( 'win_condition' ),
			'name'        => $this->setting_name_prefix( 'win_condition' ),
			'options'     => [
				'conversions' => 'Funnel conversions',
				'clicks'      => 'Email link clicks',
			],
			'disabled'    => $this->is_test_active()
		] );

		if ( ! $this->is_test_active() ) {

			if ( $step->get_funnel()->is_active() ) {
				echo html()->e( 'p', [], __( 'The test will start when your changes are saved.' ) );
			} else {
				echo html()->e( 'p', [], __( 'The test will start when the funnel is activated.' ) );
			}

			echo html()->e( 'p', [], __( 'Once the test becomes active you will be unable to make changes until you declare a winner or stop the test.' ) );

		}

		?><p></p><?php
	}

	/**
	 * Step title
	 *
	 * @param $step
	 *
	 * @return false|string
	 */
	public function generate_step_title( $step ) {
		return $this->get_setting( 'test_name' );
	}
}

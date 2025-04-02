<?php

namespace GroundhoggAdvancedPreferences\Admin;

use function Groundhogg\get_db;
use function Groundhogg\get_magic_tag_ids;
use function Groundhogg\get_request_var;
use function Groundhogg\html;
use Groundhogg\Tag;

class Admin
{
	public function __construct() {
		add_action( 'groundhogg/admin/tags/add/form', [ $this, 'tag_add' ] );
		add_action( 'groundhogg/admin/tags/edit/form', [ $this, 'tag_edit' ] );

		add_action( 'groundhogg/admin/tags/edit', [ $this, 'tag_edit_save' ] );
		add_action( 'groundhogg/admin/tags/add', [ $this, 'tag_add_save' ] );

		add_filter( 'groundhogg/admin/tags/table/get_columns', [ $this, 'add_column' ] );
		add_action( 'groundhogg/admin/tags/table/show_as_preference', [ $this, 'show_column' ] );
	}

	public function add_column( $columns ){

		$columns[ 'show_as_preference' ] = _x( 'Shown in preferences', 'Column label', 'groundhogg-ap' );

		return $columns;
	}

	/**
	 * @param $tag Tag
	 */
	public function show_column( $tag )
	{
		//$tag->is_preference_tag() ? _e( 'Yes' ) : _e( 'No' );
		
		// PB: Check for 1 of 3 states
		switch ($tag->show_as_preference) {
			case 0:
				_e( 'Never' );
				break;
			case 1:
				_e( 'Always' );
				break;
			case 2:
				_e( 'If tagged' );
				break;
			default:
				
		}
	}

	/**
	 * @param $id int the tag id
	 */
	public function tag_edit( $id )
	{
		$tag = new Tag( $id );

		$ignore = get_magic_tag_ids();

		if ( in_array( $id, $ignore ) ){
			return;
		}

		?>
		<tr class="form-field term-show-as-preference-wrap">
			<th scope="row"><label for="show-as-preference"><?php _e( 'Show as preference' ); ?></label></th>
			<td>
				<?php 
				
					/*echo html()->checkbox( [
					'label'     => __( 'Enable', 'groundhogg-ap' ),
					'name'      => 'show_as_preference',
					'id'        => 'show-as-preference',
					'checked'   => $tag->is_preference_tag()*/
					
					// PB: Switched to drop-down box with new option "If tagged"
					$show_options = [
						0 => __('Never show','groundhogg-ap'),
						2 => __('If tagged', 'groundhogg-ap'),
						1 => __('Always show','groundhogg-ap'),
					];
					echo html()->dropdown([
						'name' => 'show_as_preference',
						'id' => 'show-as-preference',
						'options' => $show_options,
						'multiple' => false,
						'selected' => $tag->show_as_preference,
						'class' => 'gh-input'
				] ) ?>
				<p class="description"><?php _e( 'This will allow contacts to opt in or out of receiving information related to this tag.' ); ?></p>
			</td>
		</tr>
		<?php
	}

	/**
	 * @param $id int the tag id
	 */
	public function tag_edit_save( $id )
	{
		$show = absint( get_request_var( 'show_as_preference', 0 ) );
		get_db( 'tags' )->update( $id, [ 'show_as_preference' => $show ] );
	}

	/**
	 * Show show as preference option
	 */
	public function tag_add()
	{
		?>
		<div class="form-field term-show-as-preference">
			<?php _e( 'Show as preference', 'groundhogg-ap' ) ?>
			<label for="show-as-preference"><select name="show_as_preference" id="show-as-preference" class="gh-input" ><option value=''>Please Select One</option><option value='0' selected>Never show</option><option value='2' >If tagged</option><option value='1' >Always show</option></select></label>
			<p class="description"><?php _e( 'This will allow contacts to opt in or out of receiving information related to this tag.' ); ?></p>
		</div>
		<?php
	}

	/**
	 * @param $id int the tag id
	 */
	public function tag_add_save( $id )
	{
		$show = absint( get_request_var( 'show_as_preference', 0 ) );

		get_db( 'tags' )->update( $id, [ 'show_as_preference' => $show ] );
	}

}

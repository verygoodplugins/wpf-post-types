<?php

/*
Plugin Name: WP Fusion - Post Types
Description: Allows protecting entire post types with CRM tags
Plugin URI: https://verygoodplugins.com/
Version: 1.0
Author: Very Good Plugins
Author URI: https://verygoodplugins.com/
*/

function wpf_post_types_admin_menu() {

	$id = add_submenu_page(
		'options-general.php',
		'WP Fusion - Post Types',
		'WPF Post Types',
		'manage_options',
		'wpf-post-types-settings',
		'wpf_post_types_render_admin_menu'
	);

	add_action( 'load-' . $id, 'wpf_post_types_enqueue_scripts' );

}

add_action( 'admin_menu', 'wpf_post_types_admin_menu' );

function wpf_post_types_enqueue_scripts() {

	wp_enqueue_style( 'bootstrap', WPF_DIR_URL . 'includes/admin/options/css/bootstrap.min.css' );
	wp_enqueue_style( 'options-css', WPF_DIR_URL . 'includes/admin/options/css/options.css' );
	wp_enqueue_style( 'wpf-options', WPF_DIR_URL . 'assets/css/wpf-options.css' );

}

function wpf_post_types_render_admin_menu() {

	// Save settings
	if ( isset( $_POST['wpf_post_types_settings_nonce'] ) && wp_verify_nonce( $_POST['wpf_post_types_settings_nonce'], 'wpf_post_types_settings' ) ) {

		$settings = $_POST['wpf_settings'];

		foreach ( $settings as $i => $setting ) {
			if ( ! isset( $setting['lock_content'] ) ) {
				unset( $settings[ $i ] );
			}
		}

		wp_fusion()->settings->set( 'post_type_rules', $settings );

		echo '<div id="message" class="updated fade"><p><strong>Settings saved.</strong></p></div>';
	}

	?>

	<div class="wrap">
		<h2>WP Fusion Post Types</h2>

		<form id="wpf-post-types-settings" action="" method="post">
			<?php wp_nonce_field( 'wpf_post_types_settings', 'wpf_post_types_settings_nonce' ); ?>
			<input type="hidden" name="action" value="update">

			<?php $all_settings = wp_fusion()->settings->get( 'post_type_rules', array() ); ?>

			<?php

			$post_types      = get_post_types();
			$available_posts = array();

			unset( $post_types['attachment'] );
			$post_types = apply_filters( 'wpf_redirect_post_types', $post_types );

			foreach ( $post_types as $post_type ) {

				$posts = get_posts( array(
					'post_type'      => $post_type,
					'posts_per_page' => 200,
					'orderby'        => 'post_title',
					'order'          => 'ASC'
				) );

				foreach ( $posts as $post ) {
					$available_posts[ $post_type ][ $post->ID ] = $post->post_title;
				}

			}

			?>

			<table class="table table-hover wpf-settings-table" style="max-width: 1200px;">
				<thead>
				<tr>
					<th>Post Type</th>
					<th>Restrict Access</th>
					<th>Required Tags</th>
					<th>Redirect To</th>
				</tr>
				</thead>
				<tbody>

				<?php

				foreach ( $post_types as $type ) :

					$defaults = array(
						'lock_content' => false,
						'allow_tags'   => array(),
						'redirect'     => false,
					);

					if ( ! isset( $all_settings[ $type ] ) ) {
						$all_settings[ $type ] = array();
					}

					$settings = array_merge( $defaults, $all_settings[ $type ] );

					?>

					<tr>
						<td><?php echo $type; ?></td>
						<td>
							<input class="checkbox wpf-restrict-access-checkbox" type="checkbox" data-unlock="wpf_settings-allow_tags wpf_settings-allow_tags_all wpf-redirect wpf-redirect-url" name="wpf_settings[<?php echo $type; ?>][lock_content]" value="1" <?php checked( $settings['lock_content'], 1 ) ?> />
							<label for="wpf-lock-content" class="wpf-restrict-access">Restrict access</label>
						</td>
						<td>
							<?php

							$args = array(
								'setting'   => $settings['allow_tags'],
								'meta_name' => 'wpf_settings[' . $type . ']',
								'field_id'  => 'allow_tags'
							);

							wpf_render_tag_multiselect( $args );

							?>
						</td>
						<td style="width: 300px;">

							<select class="select4-search" style="width: 100%; min-width: 300px;" data-placeholder="None" name="wpf_settings[<?php echo $type; ?>][redirect]">

								<option></option>

								<?php foreach ( $available_posts as $post_type => $data ) : ?>

									<optgroup label="<?php echo $post_type ?>">

									<?php foreach ( $available_posts[ $post_type ] as $id => $post_name ) : ?>
										<option value="<?php echo $id ?>" <?php selected( $id, $settings['redirect'] ) ?> > <?php echo $post_name; ?></option>
									<?php endforeach; ?>

									</optgroup>

								<?php endforeach; ?>

							</select>

						</td>
					</tr>

				<?php endforeach; ?>

				</tbody>

			</table>

			<p class="submit"><input name="Submit" type="submit" class="button-primary" value="Save Changes"/>
			</p>

		</form>

	</div>

	<?php

}

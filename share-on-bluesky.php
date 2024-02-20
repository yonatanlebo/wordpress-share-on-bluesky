<?php
/**
 * Plugin Name: Share On Bluesky
 * Plugin URI: https://github.com/pfefferle/wordpress-bluesky
 * Description: A simple Crossposter for Bluesky (AT Protocol)
 * Author: Matthias Pfefferle
 * Author URI: https://notiz.blog/
 * Version: 1.0.1
 * License: GPL-2.0
 * License URI: https://opensource.org/license/gpl-2-0/
 * Text Domain: share-on-bluesky
 * Domain Path: /languages
 */

namespace Share_On_Bluesky;

/**
 * On plugin activation, redirect to the profile page so folks can connect to their Bluesky profile.
 *
 * @param string $plugin        Path to the plugin file relative to the plugins directory.
 * @param bool   $network_wide  Whether to enable the plugin for all sites in the network.
 */
function redirect_to_settings( $plugin, $network_wide ) {
	// Bail if the plugin is not Share on Bluesky.
	if ( \plugin_basename( __FILE__ ) !== $plugin ) {
		return;
	}

	// Bail if we're on a multisite and the plugin is network activated.
	if ( $network_wide ) {
		return;
	}

	\wp_safe_redirect( admin_url( 'options-general.php?page=share-on-bluesky' ) );
}
\add_action( 'activated_plugin', __NAMESPACE__ . '\redirect_to_settings', 10, 2 );

/**
 * Add a settings page to the admin menu.
 *
 * @return void
 */
function admin_menu() {
	\add_options_page(
		\esc_html__( 'Bluesky', 'share-on-bluesky' ),
		\esc_html__( 'Bluesky', 'share-on-bluesky' ),
		'manage_options',
		'share-on-bluesky',
		__NAMESPACE__ . '\settings_page'
	);
}
\add_action( 'admin_menu', __NAMESPACE__ . '\admin_menu' );

/**
 * Add a link to the plugin's settings page
 * in the plugin's description in the admin.
 *
 * @param array $links Array of plugin action links.
 * @return array
 */
function add_settings_link( $links ) {
	$settings_link = \sprintf(
		'<a href="%1$s">%2$s</a>',
		\esc_url( \admin_url( 'options-general.php?page=share-on-bluesky' ) ),
		\esc_html__( 'Settings', 'share-on-bluesky' )
	);
	array_unshift( $links, $settings_link );

	return $links;
}
\add_filter( 'plugin_action_links_' . \plugin_basename( __FILE__ ), __NAMESPACE__ . '\add_settings_link' );

/**
 * Register ActivityPub settings
 */
function register_settings() {
	\register_setting(
		'share-on-bluesky',
		'bluesky_domain',
		array(
			'type'              => 'string',
			'description'       => \__( 'The domain of your Bluesky instance', 'share-on-bluesky' ),
			'default'           => 'https://bsky.social',
			'sanitize_callback' => 'esc_url_raw',
		)
	);

	\register_setting(
		'share-on-bluesky',
		'bluesky_did',
		array(
			'type'              => 'string',
			'description'       => \__( 'The DID of your Bluesky account', 'share-on-bluesky' ),
			'default'           => '',
			'sanitize_callback' => 'sanitize_text_field',
		)
	);

	\register_setting(
		'share-on-bluesky',
		'bluesky_password',
		array(
			'type'              => 'string',
			'description'       => \__( 'The password of your Bluesky account (will not be stored permanently)', 'share-on-bluesky' ),
			'default'           => '',
			'sanitize_callback' => 'sanitize_text_field',
		)
	);

	\register_setting(
		'share-on-bluesky',
		'bluesky_identifier',
		array(
			'type'              => 'string',
			'description'       => \__( 'The identifier of your Bluesky account', 'share-on-bluesky' ),
			'default'           => '',
			'sanitize_callback' => 'sanitize_text_field',
		)
	);

	\register_setting(
		'share-on-bluesky',
		'bluesky_access_jwt',
		array(
			'type'              => 'string',
			'description'       => \__( 'The access token of your Bluesky account', 'share-on-bluesky' ),
			'default'           => '',
			'sanitize_callback' => 'sanitize_text_field',
		)
	);

	\register_setting(
		'share-on-bluesky',
		'bluesky_refresh_jwt',
		array(
			'type'              => 'string',
			'description'       => \__( 'The refresh token of your Bluesky account', 'share-on-bluesky' ),
			'default'           => '',
			'sanitize_callback' => 'sanitize_text_field',
		)
	);
}
\add_action( 'admin_init', __NAMESPACE__ . '\register_settings' );

/**
 * Add a section to user's profile to add their Bluesky name and public key.
 *
 * @param WP_User $user User instance to output for.
 * @return void
 */
function settings_page( $user ) {
	printf(
		'<h2 id="bluesky">%1$s</h2>',
		\esc_html__( 'Share on Bluesky', 'share-on-bluesky' )
	);

	if ( \get_option( 'bluesky_identifier' ) && \get_option( 'bluesky_password' ) && ! \get_option( 'bluesky_access_jwt' ) ) {
		get_access_token();
	}
	?>
	<div class="activitypub-settings activitypub-settings-page hide-if-no-js">
		<form method="post" action="options.php">
			<?php \settings_fields( 'share-on-bluesky' ); ?>
			<table class="form-table" role="presentation">
				<tbody>
					<tr class="domain-wrap">
						<th>
							<label for="bluesky-domain"><?php \esc_html_e( 'Bluesky Domain', 'share-on-bluesky' ); ?></label>
						</th>
						<td>
							<input type="text" name="bluesky_domain" id="bluesky-domain" value="<?php echo \esc_attr( \get_option( 'bluesky_domain' ) ); ?>" placeholder="https://bsky.social" />
							<p class="description" id="bluesky-domain-description">
								<?php \esc_html_e( 'The domain of your Bluesky instance. (This has to be a valid URL including "http(s)")', 'share-on-bluesky' ); ?>
							</p>
						</td>
					</tr>

					<tr class="user-identifier-wrap">
						<th>
							<label for="bluesky-identifier"><?php \esc_html_e( 'Bluesky "Identifier"', 'share-on-bluesky' ); ?></label>
						</th>
						<td>
							<input type="text" name="bluesky_identifier" id="bluesky-identifier" aria-describedby="email-description" value="<?php echo \esc_attr( \get_option( 'bluesky_identifier' ) ); ?>">
							<p class="description" id="bluesky-identifier-description">
								<?php \esc_html_e( 'Your Bluesky identifier.', 'share-on-bluesky' ); ?>
							</p>
						</td>
					</tr>

					<tr class="user-password-wrap">
						<th>
							<label for="bluesky-password"><?php \esc_html_e( 'Password', 'share-on-bluesky' ); ?></label>
						</th>
						<td>
							<input type="text" name="bluesky_password" id="bluesky-password" class="regular-text code" value="<?php echo \esc_attr( \get_option( 'bluesky_password' ) ); ?>">
							<p class="description" id="bluesky-password-description">
								<?php \esc_html_e( 'Your Bluesky application password. It is needed to get an Access-Token and will not be stored anywhere.', 'share-on-bluesky' ); ?>
							</p>
						</td>
					</tr>

					<tr class="access-token-wrap">
						<th>
							<label for="bluesky-password"><?php \esc_html_e( 'Access Token', 'share-on-bluesky' ); ?></label>
						</th>
						<td>
							<input type="text" class="regular-text code" value="<?php echo \esc_attr( \get_option( 'bluesky_access_jwt' ) ); ?>" readonly>
							<p class="description" id="bluesky-password-description">
								<?php \esc_html_e( 'This is only to see if everything works as expected', 'share-on-bluesky' ); ?>
							</p>
						</td>
					</tr>
				</tbody>
			</table>
			<?php \do_settings_sections( 'share-on-bluesky' ); ?>

			<?php \submit_button(); ?>
		</form>
	</div>
	<?php
}

/**
 * Save Bluesky data when the user profile is updated.
 *
 * @param int $user_id User ID.
 * @return void
 */
function get_access_token() {
	$bluesky_identifier = \get_option( 'bluesky_identifier' );
	$bluesky_domain     = \get_option( 'bluesky_domain' );
	$bluesky_password   = \get_option( 'bluesky_password' );

	if (
		! empty( $bluesky_domain )
		&& ! empty( $bluesky_identifier )
		&& ! empty( $bluesky_password )
	) {
		$bluesky_domain = \trailingslashit( $bluesky_domain );
		$session_url    = $bluesky_domain . 'xrpc/com.atproto.server.createSession';
		$wp_version     = \get_bloginfo( 'version' );
		$user_agent     = \apply_filters( 'http_headers_useragent', 'WordPress/' . $wp_version . '; ' . \get_bloginfo( 'url' ) );

		$response = \wp_safe_remote_post(
			\esc_url_raw( $session_url ),
			array(
				'user-agent' => "$user_agent; ActivityPub",
				'headers'    => array(
					'Content-Type' => 'application/json',
				),
				'body'       => \wp_json_encode(
					array(
						'identifier' => $bluesky_identifier,
						'password'   => $bluesky_password,
					)
				),
			)
		);

		if (
			\is_wp_error( $response ) ||
			\wp_remote_retrieve_response_code( $response ) >= 300
		) {
			// save error
			return;
		}

		$data = json_decode( \wp_remote_retrieve_body( $response ), true );

		if (
			! empty( $data['accessJwt'] )
			&& ! empty( $data['refreshJwt'] )
			&& ! empty( $data['did'] )
		) {
			\update_option( 'bluesky_access_jwt', $data['accessJwt'] );
			\update_option( 'bluesky_refresh_jwt', $data['refreshJwt'] );
			\update_option( 'bluesky_did', $data['did'] );
			\update_option( 'bluesky_password', '' );
		} else {
			// save error
		}
	}
}

/**
 * Refresh the access token
 *
 * @return void
 */
function refresh_access_token() {
	$bluesky_domain = \get_option( 'bluesky_domain' );
	$bluesky_domain = \trailingslashit( $bluesky_domain );
	$session_url    = $bluesky_domain . 'xrpc/com.atproto.server.refreshSession';
	$wp_version     = \get_bloginfo( 'version' );
	$user_agent     = \apply_filters( 'http_headers_useragent', 'WordPress/' . $wp_version . '; ' . \get_bloginfo( 'url' ) );
	$access_token   = \get_option( 'bluesky_refresh_jwt' );

	$response = \wp_safe_remote_post(
		\esc_url_raw( $session_url ),
		array(
			'user-agent' => "$user_agent; ActivityPub",
			'headers'    => array(
				'Content-Type'  => 'application/json',
				'Authorization' => 'Bearer ' . $access_token,
			),
		)
	);

	if (
		\is_wp_error( $response ) ||
		\wp_remote_retrieve_response_code( $response ) >= 300
	) {
		// save error
		return;
	}

	$data = \json_decode( \wp_remote_retrieve_body( $response ), true );

	if (
		! empty( $data['accessJwt'] )
		&& ! empty( $data['refreshJwt'] )
	) {
		\update_option( 'bluesky_access_jwt', $data['accessJwt'] );
		\update_option( 'bluesky_refresh_jwt', $data['refreshJwt'] );
	} else {
		// save error
	}
}

/**
 * Schedule Cross-Posting-Event to not slow down publishing
 *
 * @param int     $post_id
 * @param WP_Post $post
 * @return void
 */
function publish_post( $post_id, $post ) {
	if ( \get_option( 'bluesky_access_jwt' ) ) {
		\wp_schedule_single_event( time(), 'bluesky_send_post', array( $post_id ) );
	}
}
\add_action( 'publish_post', __NAMESPACE__ . '\publish_post', 10, 2 );

/**
 * Undocumented function
 *
 * @param int $post_id
 * @return void
 */
function send_post( $post_id ) {
	$post = \get_post( $post_id );

	refresh_access_token();

	$access_token   = \get_option( 'bluesky_access_jwt' );
	$did            = \get_option( 'bluesky_did' );
	$bluesky_domain = \get_option( 'bluesky_domain' );
	$bluesky_domain = \trailingslashit( $bluesky_domain );

	if ( ! $access_token || ! $did || ! $bluesky_domain ) {
		return;
	}

	$wp_version = \get_bloginfo( 'version' );
	$user_agent = \apply_filters( 'http_headers_useragent', 'WordPress/' . $wp_version . '; ' . \get_bloginfo( 'url' ) );

	$response = \wp_safe_remote_post(
		$bluesky_domain . 'xrpc/com.atproto.repo.createRecord',
		array(
			'user-agent' => "$user_agent; Share on Bluesky",
			'headers'    => array(
				'Content-Type'  => 'application/json',
				'Authorization' => 'Bearer ' . $access_token,
			),
			'body'       => \wp_json_encode(
				array(
					'collection' => 'app.bsky.feed.post',
					'did'        => \esc_html( $did ),
					'repo'       => \esc_html( $did ),
					'record'     => array(
						'$type'     => 'app.bsky.feed.post',
						'text'      => \esc_html( \get_excerpt( $post, 400 ) ),
						'createdAt' => \gmdate( 'c', \strtotime( $post->post_date_gmt ) ),
						'embed'     => array(
							'$type'    => 'app.bsky.embed.external',
							'external' => array(
								'uri'         => \wp_get_shortlink( $post->ID ),
								'title'       => \esc_html( $post->post_title ),
								'description' => \esc_html( \get_excerpt( $post ) ),
							),
						),
					),
				)
			),
		)
	);

	if ( \is_wp_error( $response ) ) {
		// save_error
	}
}
\add_action( 'bluesky_send_post', __NAMESPACE__ . '\send_post' );

/**
 * Add a weekly event to refresh the access token.
 *
 * @return void
 */
function add_scheduler() {
	if ( ! \wp_next_scheduled( 'bluesky_refresh_token' ) ) {
		\wp_schedule_event( time(), 'weekly', 'bluesky_refresh_token' );
	}
}
\register_activation_hook( __FILE__, __NAMESPACE__ . '\add_scheduler' );

/**
 * Remove the weekly event to refresh the access token.
 *
 * @return void
 */
function remove_scheduler() {
	\wp_clear_scheduled_hook( 'bluesky_refresh_token' );
}
\register_deactivation_hook( __FILE__, __NAMESPACE__ . '\remove_scheduler' );

/**
 * Returns an excerpt
 *
 * @param WP_Post $post
 * @param int     $length
 * @return void
 */
function get_excerpt( $post, $length = 55 ) {
	$excerpt_length = \apply_filters( 'excerpt_length', $length );
	$excerpt_more   = \apply_filters( 'excerpt_more', ' [...]' );

	return \wp_trim_words( \get_the_excerpt( $post ), $excerpt_length, $excerpt_more );
}
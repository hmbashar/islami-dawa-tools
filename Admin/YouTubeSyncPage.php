<?php
/**
 * YouTubeSyncPage.php
 *
 * Registers and renders the "YouTube Sync" submenu page under Islami Dawa Tools
 * in the WordPress admin. Handles the settings form, "Sync All Videos" button,
 * "Run Latest Sync Now" button, and displays sync status info.
 *
 * @package IslamiDawaTools\Admin
 * @since   1.0.0
 */

namespace IslamiDawaTools\Admin;

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

use IslamiDawaTools\YouTubeSyncManager;

/**
 * Class YouTubeSyncPage
 *
 * Provides the admin UI for configuring YouTube sync and triggering manual syncs.
 *
 * @package IslamiDawaTools\Admin
 * @since   1.0.0
 */
class YouTubeSyncPage {

	/**
	 * Admin menu slug for this page.
	 *
	 * @since 1.0.0
	 * @var string
	 */
	const PAGE_SLUG = 'islami-dawa-tools-youtube-sync';

	/**
	 * Nonce action for the settings form.
	 *
	 * @since 1.0.0
	 * @var string
	 */
	const NONCE_SETTINGS = 'islami_dawa_tools_youtube_settings_nonce';

	/**
	 * Nonce action for the "Sync All Videos" action.
	 *
	 * @since 1.0.0
	 * @var string
	 */
	const NONCE_SYNC_ALL = 'islami_dawa_tools_youtube_sync_all_nonce';

	/**
	 * Nonce action for the "Run Latest Sync Now" action.
	 *
	 * @since 1.0.0
	 * @var string
	 */
	const NONCE_SYNC_LATEST = 'islami_dawa_tools_youtube_sync_latest_nonce';

	/**
	 * Constructor — registers WordPress hooks.
	 *
	 * @since 1.0.0
	 */
	public function __construct() {
		add_action( 'admin_menu', array( $this, 'register_menu' ) );
		add_action( 'admin_post_islami_dawa_tools_sync_all', array( $this, 'handle_sync_all' ) );
		add_action( 'admin_post_islami_dawa_tools_sync_latest', array( $this, 'handle_sync_latest' ) );
	}

	/**
	 * Register the "YouTube Sync" submenu page.
	 *
	 * Hooks into 'admin_menu' to add the submenu underneath the plugin's
	 * top-level menu. If the top-level menu does not yet exist, it is also created.
	 *
	 * @since 1.0.0
	 */
	public function register_menu() {
		// Add top-level menu if it does not exist yet.
		add_menu_page(
			__( 'Islami Dawa Tools', 'islami-dawa-tools' ),
			__( 'Islami Dawa Tools', 'islami-dawa-tools' ),
			'manage_options',
			'islami-dawa-tools',
			'__return_null',
			'dashicons-admin-tools',
			60
		);

		add_submenu_page(
			'islami-dawa-tools',
			__( 'YouTube Sync', 'islami-dawa-tools' ),
			__( 'YouTube Sync', 'islami-dawa-tools' ),
			'manage_options',
			self::PAGE_SLUG,
			array( $this, 'render_page' )
		);
	}

	/**
	 * Handle the "Sync All Videos" form submission.
	 *
	 * Validates nonce and capability, runs the full sync, then redirects back
	 * to the settings page with a query-string result indicator.
	 *
	 * @since 1.0.0
	 */
	public function handle_sync_all() {
		if ( ! current_user_can( 'manage_options' ) ) {
			wp_die( esc_html__( 'You do not have permission to perform this action.', 'islami-dawa-tools' ) );
		}

		check_admin_referer( self::NONCE_SYNC_ALL );

		$manager = new YouTubeSyncManager();
		$result  = $manager->sync_all_videos();

		$redirect_url = admin_url( 'admin.php?page=' . self::PAGE_SLUG );

		if ( is_wp_error( $result ) ) {
			$redirect_url = add_query_arg(
				array(
					'sync_status'  => 'error',
					'sync_message' => rawurlencode( $result->get_error_message() ),
				),
				$redirect_url
			);
		} else {
			$redirect_url = add_query_arg(
				array(
					'sync_status'   => 'success',
					'sync_found'    => absint( $result['found'] ),
					'sync_imported' => absint( $result['imported'] ),
					'sync_skipped'  => absint( $result['skipped'] ),
					'sync_failed'   => absint( $result['failed'] ),
				),
				$redirect_url
			);
		}

		wp_safe_redirect( $redirect_url );
		exit;
	}

	/**
	 * Handle the "Run Latest Sync Now" form submission.
	 *
	 * Same flow as handle_sync_all() but fetches only the most recent 10 videos.
	 *
	 * @since 1.0.0
	 */
	public function handle_sync_latest() {
		if ( ! current_user_can( 'manage_options' ) ) {
			wp_die( esc_html__( 'You do not have permission to perform this action.', 'islami-dawa-tools' ) );
		}

		check_admin_referer( self::NONCE_SYNC_LATEST );

		$manager = new YouTubeSyncManager();
		$result  = $manager->sync_latest_videos( 10 );

		$redirect_url = admin_url( 'admin.php?page=' . self::PAGE_SLUG );

		if ( is_wp_error( $result ) ) {
			$redirect_url = add_query_arg(
				array(
					'sync_status'  => 'error',
					'sync_message' => rawurlencode( $result->get_error_message() ),
				),
				$redirect_url
			);
		} else {
			$redirect_url = add_query_arg(
				array(
					'sync_status'   => 'success',
					'sync_found'    => absint( $result['found'] ),
					'sync_imported' => absint( $result['imported'] ),
					'sync_skipped'  => absint( $result['skipped'] ),
					'sync_failed'   => absint( $result['failed'] ),
				),
				$redirect_url
			);
		}

		wp_safe_redirect( $redirect_url );
		exit;
	}

	/**
	 * Render the YouTube Sync settings page markup.
	 *
	 * @since 1.0.0
	 */
	public function render_page() {
		if ( ! current_user_can( 'manage_options' ) ) {
			return;
		}

		$api_key    = get_option( 'islami_dawa_tools_youtube_api_key', '' );
		$channel_id = get_option( 'islami_dawa_tools_youtube_channel_id', '' );

		// Retrieve last sync info.
		$last_sync_time   = get_option( YouTubeSyncManager::OPTION_LAST_SYNC_TIME, '' );
		$last_sync_result = get_option( YouTubeSyncManager::OPTION_LAST_SYNC_RESULT, '' );

		// Cron next run.
		$next_cron = wp_next_scheduled( \IslamiDawaTools\CronManager::CRON_HOOK );
		?>
		<div class="wrap">
			<h1><?php esc_html_e( 'YouTube Sync — Islami Dawa Tools', 'islami-dawa-tools' ); ?></h1>

			<?php $this->render_notices(); ?>

			<!-- ============================================================
				 Settings Form
			============================================================ -->
			<div class="card" style="max-width:700px;padding:20px 24px;margin-top:20px;">
				<h2><?php esc_html_e( 'API Settings', 'islami-dawa-tools' ); ?></h2>

				<form method="post" action="<?php echo esc_url( admin_url( 'options.php' ) ); ?>">
					<?php settings_fields( YouTubeSyncSettings::SETTINGS_GROUP ); ?>

					<table class="form-table" role="presentation">
						<tbody>
							<tr>
								<th scope="row">
									<label for="islami_dawa_tools_youtube_api_key">
										<?php esc_html_e( 'YouTube API Key', 'islami-dawa-tools' ); ?>
									</label>
								</th>
								<td>
									<input
										type="text"
										name="islami_dawa_tools_youtube_api_key"
										id="islami_dawa_tools_youtube_api_key"
										value="<?php echo esc_attr( $api_key ); ?>"
										class="regular-text"
										autocomplete="off"
									/>
									<p class="description">
										<?php
										printf(
											/* translators: %s: link to Google Cloud console */
											esc_html__( 'Enter your YouTube Data API v3 key. You can create one in the %s.', 'islami-dawa-tools' ),
											'<a href="https://console.cloud.google.com/apis/credentials" target="_blank" rel="noopener noreferrer">' . esc_html__( 'Google Cloud Console', 'islami-dawa-tools' ) . '</a>'
										);
										?>
									</p>
								</td>
							</tr>

							<tr>
								<th scope="row">
									<label for="islami_dawa_tools_youtube_channel_id">
										<?php esc_html_e( 'YouTube Channel ID', 'islami-dawa-tools' ); ?>
									</label>
								</th>
								<td>
									<input
										type="text"
										name="islami_dawa_tools_youtube_channel_id"
										id="islami_dawa_tools_youtube_channel_id"
										value="<?php echo esc_attr( $channel_id ); ?>"
										class="regular-text"
									/>
									<p class="description">
										<?php esc_html_e( 'Enter your YouTube Channel ID (e.g. UCxxxxxxxxxxxxxxxx). Find it under YouTube Studio → Settings → Channel → Advanced settings.', 'islami-dawa-tools' ); ?>
									</p>
								</td>
							</tr>
						</tbody>
					</table>

					<?php submit_button( __( 'Save Settings', 'islami-dawa-tools' ) ); ?>
				</form>
			</div>

			<!-- ============================================================
				 Manual Sync Actions
			============================================================ -->
			<div class="card" style="max-width:700px;padding:20px 24px;margin-top:20px;">
				<h2><?php esc_html_e( 'Manual Sync', 'islami-dawa-tools' ); ?></h2>

				<p>
					<?php esc_html_e( 'Use "Sync All Videos" to import every video from your channel (may take several minutes for large channels). Use "Run Latest Sync Now" to quickly check for the 10 most recent uploads.', 'islami-dawa-tools' ); ?>
				</p>

				<form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>" style="display:inline;">
					<input type="hidden" name="action" value="islami_dawa_tools_sync_all" />
					<?php wp_nonce_field( self::NONCE_SYNC_ALL ); ?>
					<?php
					submit_button(
						__( 'Sync All Videos', 'islami-dawa-tools' ),
						'primary',
						'submit',
						false,
						array( 'onclick' => 'return confirm("' . esc_js( __( 'This may take a while for large channels. Continue?', 'islami-dawa-tools' ) ) . '")' )
					);
					?>
				</form>

				&nbsp;&nbsp;

				<form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>" style="display:inline;">
					<input type="hidden" name="action" value="islami_dawa_tools_sync_latest" />
					<?php wp_nonce_field( self::NONCE_SYNC_LATEST ); ?>
					<?php submit_button( __( 'Run Latest Sync Now', 'islami-dawa-tools' ), 'secondary', 'submit', false ); ?>
				</form>
			</div>

			<!-- ============================================================
				 Status Info
			============================================================ -->
			<div class="card" style="max-width:700px;padding:20px 24px;margin-top:20px;">
				<h2><?php esc_html_e( 'Sync Status', 'islami-dawa-tools' ); ?></h2>

				<table class="widefat striped" style="width:auto;">
					<tbody>
						<tr>
							<th><?php esc_html_e( 'Cron Enabled', 'islami-dawa-tools' ); ?></th>
							<td>
								<?php if ( $next_cron ) : ?>
									<span style="color:#28a745;">&#10003; <?php esc_html_e( 'Yes', 'islami-dawa-tools' ); ?></span>
								<?php else : ?>
									<span style="color:#dc3545;">&#10007; <?php esc_html_e( 'No (save valid API key & Channel ID first)', 'islami-dawa-tools' ); ?></span>
								<?php endif; ?>
							</td>
						</tr>

						<?php if ( $next_cron ) : ?>
						<tr>
							<th><?php esc_html_e( 'Next Cron Run', 'islami-dawa-tools' ); ?></th>
							<td><?php echo esc_html( date_i18n( get_option( 'date_format' ) . ' ' . get_option( 'time_format' ), $next_cron ) ); ?></td>
						</tr>
						<?php endif; ?>

						<tr>
							<th><?php esc_html_e( 'Last Sync Time', 'islami-dawa-tools' ); ?></th>
							<td><?php echo $last_sync_time ? esc_html( $last_sync_time ) : esc_html__( 'Never', 'islami-dawa-tools' ); ?></td>
						</tr>

						<tr>
							<th><?php esc_html_e( 'Last Sync Result', 'islami-dawa-tools' ); ?></th>
							<td><?php echo $last_sync_result ? esc_html( $last_sync_result ) : '&mdash;'; ?></td>
						</tr>
					</tbody>
				</table>
			</div>
		</div>
		<?php
	}

	/**
	 * Render admin notices based on redirect query parameters.
	 *
	 * Reads `sync_status` and associated parameters from `$_GET` after a
	 * POST/redirect/GET cycle. All values are sanitised before use.
	 *
	 * @since 1.0.0
	 */
	private function render_notices() {
		// WordPress Settings API notice (settings saved).
		settings_errors( 'general' );

		if ( empty( $_GET['sync_status'] ) ) { // phpcs:ignore WordPress.Security.NonceVerification.Recommended
			return;
		}

		// phpcs:disable WordPress.Security.NonceVerification.Recommended
		$status = sanitize_key( $_GET['sync_status'] );

		if ( 'error' === $status ) {
			$message = isset( $_GET['sync_message'] ) ? sanitize_text_field( rawurldecode( $_GET['sync_message'] ) ) : __( 'An unknown error occurred.', 'islami-dawa-tools' );
			echo '<div class="notice notice-error is-dismissible"><p>' . esc_html( $message ) . '</p></div>';
			return;
		}

		if ( 'success' === $status ) {
			$found    = isset( $_GET['sync_found'] ) ? absint( $_GET['sync_found'] ) : 0;
			$imported = isset( $_GET['sync_imported'] ) ? absint( $_GET['sync_imported'] ) : 0;
			$skipped  = isset( $_GET['sync_skipped'] ) ? absint( $_GET['sync_skipped'] ) : 0;
			$failed   = isset( $_GET['sync_failed'] ) ? absint( $_GET['sync_failed'] ) : 0;
			// phpcs:enable WordPress.Security.NonceVerification.Recommended

			printf(
				'<div class="notice notice-success is-dismissible"><p>%s</p></div>',
				esc_html(
					sprintf(
						/* translators: 1: found, 2: imported, 3: skipped, 4: failed */
						__( 'Sync complete — Found: %1$d | Imported: %2$d | Skipped: %3$d | Failed: %4$d', 'islami-dawa-tools' ),
						$found,
						$imported,
						$skipped,
						$failed
					)
				)
			);
		}
	}
}

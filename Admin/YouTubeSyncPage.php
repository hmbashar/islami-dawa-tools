<?php
/**
 * YouTubeSyncPage.php
 *
 * Registers and renders the professional "YouTube Sync" admin submenu page.
 * Uses SweetAlert2 for confirmations and result feedback.
 * Enqueues custom CSS (Admin/assets/css/youtube-sync.css) and JS (Admin/assets/js/youtube-sync.js).
 *
 * @package IslamiDawaTools\Admin
 * @since   1.0.0
 */

namespace IslamiDawaTools\Admin;

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

use IslamiDawaTools\Api\YouTube\YouTubeSyncManager;
use IslamiDawaTools\Cron\CronManager;

/**
 * Class YouTubeSyncPage
 *
 * Provides the professional admin UI for configuring YouTube sync settings
 * and triggering manual syncs with SweetAlert2 feedback dialogs.
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
	const PAGE_SLUG_MAIN = 'islami-dawa-tools';

	/**
	 * Nonce action for the "Sync All Videos" POST action.
	 *
	 * @since 1.0.0
	 * @var string
	 */
	const NONCE_SYNC_ALL = 'islami_dawa_tools_youtube_sync_all_nonce';

	/**
	 * Nonce action for the "Run Latest Sync Now" POST action.
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
		add_action( 'admin_enqueue_scripts', array( $this, 'enqueue_assets' ) );
		add_action( 'admin_post_islami_dawa_tools_sync_all', array( $this, 'handle_sync_all' ) );
		add_action( 'admin_post_islami_dawa_tools_sync_latest', array( $this, 'handle_sync_latest' ) );
		add_filter( 'parent_file', array( $this, 'highlight_main_menu' ) );
	}

	public function highlight_main_menu( $parent_file ) {
		global $plugin_page;
		if ( 'islami-dawa-tools' === $plugin_page ) {
			$parent_file = 'islami-dawa-tools';
		}
		return $parent_file;
	}

	/**
	 * Register the "YouTube Sync" submenu under the plugin's top-level menu.
	 *
	 * Creates the top-level "Islami Dawa Tools" menu if it does not yet exist.
	 *
	 * @since 1.0.0
	 */
	public function register_menu() {
		add_menu_page(
			__( 'Islami Dawa Tools', 'islami-dawa-tools' ),
			__( 'Islami Dawa Tools', 'islami-dawa-tools' ),
			'manage_options',
			'islami-dawa-tools',
			array( $this, 'render_main_page' ),
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
	 * Enqueue SweetAlert2 and plugin CSS/JS on the YouTube Sync admin page only.
	 *
	 * @since 1.0.0
	 *
	 * @param string $hook Current admin page hook.
	 */
	public function enqueue_assets( $hook ) {
		$is_main_page     = false !== strpos( $hook, self::PAGE_SLUG_MAIN );
		$is_youtube_page = false !== strpos( $hook, self::PAGE_SLUG );

		if ( ! $is_main_page && ! $is_youtube_page ) {
			return;
		}

		$plugin_url = ISLAMI_DAWA_TOOLS_URL;
		$version    = ISLAMI_DAWA_TOOLS_VERSION;

		// SweetAlert2 from CDN.
		wp_enqueue_style(
			'sweetalert2',
			'https://cdn.jsdelivr.net/npm/sweetalert2@11/dist/sweetalert2.min.css',
			array(),
			'11'
		);

		wp_enqueue_script(
			'sweetalert2',
			'https://cdn.jsdelivr.net/npm/sweetalert2@11/dist/sweetalert2.all.min.js',
			array(),
			'11',
			true
		);

		// Google Font: Inter.
		wp_enqueue_style(
			'google-font-inter',
			'https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap',
			array(),
			null
		);

		// Plugin page stylesheet (load on both main page and YouTube sync page).
		wp_enqueue_style(
			'idt-youtube-sync',
			$plugin_url . 'Admin/assets/css/youtube-sync.css',
			array(),
			$version
		);

		// Plugin page script (YouTube sync page only).
		if ( $is_youtube_page ) {
			wp_enqueue_script(
				'idt-youtube-sync',
				$plugin_url . 'Admin/assets/js/youtube-sync.js',
				array( 'sweetalert2' ),
				$version,
				true
			);
		}

		if ( ! $is_youtube_page ) {
			return;
		}

		// SweetAlert2 (YouTube sync page only).

		// Collect redirect query params for SweetAlert2 to display.
		// phpcs:disable WordPress.Security.NonceVerification.Recommended
		$sync_status   = isset( $_GET['sync_status'] )   ? sanitize_key( $_GET['sync_status'] ) : '';
		$sync_found    = isset( $_GET['sync_found'] )    ? absint( $_GET['sync_found'] )    : 0;
		$sync_imported = isset( $_GET['sync_imported'] ) ? absint( $_GET['sync_imported'] ) : 0;
		$sync_skipped  = isset( $_GET['sync_skipped'] )  ? absint( $_GET['sync_skipped'] )  : 0;
		$sync_failed   = isset( $_GET['sync_failed'] )   ? absint( $_GET['sync_failed'] )   : 0;
		$sync_message  = isset( $_GET['sync_message'] )  ? rawurldecode( sanitize_text_field( $_GET['sync_message'] ) ) : '';
		// phpcs:enable WordPress.Security.NonceVerification.Recommended

		// Pass localised data to JS.
		wp_localize_script(
			'idt-youtube-sync',
			'idtYtSync',
			array(
				'syncStatus'   => $sync_status,
				'syncFound'    => $sync_found,
				'syncImported' => $sync_imported,
				'syncSkipped'  => $sync_skipped,
				'syncFailed'   => $sync_failed,
				'syncMessage'  => esc_js( $sync_message ),
				'i18n'         => array(
					'syncComplete'    => esc_html__( 'Sync Complete!', 'islami-dawa-tools' ),
					'syncFailed'      => esc_html__( 'Sync Failed', 'islami-dawa-tools' ),
					'syncAllTitle'    => esc_html__( 'Sync All Videos?', 'islami-dawa-tools' ),
					'syncAllText'     => esc_html__( 'This will import all videos from your channel. It may take a while for large channels.', 'islami-dawa-tools' ),
					'syncAllConfirm'  => esc_html__( 'Yes, Start Sync', 'islami-dawa-tools' ),
					'syncLatestTitle' => esc_html__( 'Run Latest Sync?', 'islami-dawa-tools' ),
					'syncLatestText'  => esc_html__( 'This will check the 10 most recent uploads and import any that are missing.', 'islami-dawa-tools' ),
					'syncLatestConfirm' => esc_html__( 'Yes, Run Now', 'islami-dawa-tools' ),
					'cancel'          => esc_html__( 'Cancel', 'islami-dawa-tools' ),
					'ok'              => esc_html__( 'Got it!', 'islami-dawa-tools' ),
					'processing'      => esc_html__( 'Syncing videos…', 'islami-dawa-tools' ),
					'syncingAll'      => esc_html__( 'Syncing all videos — please wait…', 'islami-dawa-tools' ),
					'syncingLatest'   => esc_html__( 'Checking for latest videos…', 'islami-dawa-tools' ),
					'found'           => esc_html__( 'Found', 'islami-dawa-tools' ),
					'imported'        => esc_html__( 'Imported', 'islami-dawa-tools' ),
					'skipped'         => esc_html__( 'Skipped', 'islami-dawa-tools' ),
					'failed'          => esc_html__( 'Failed', 'islami-dawa-tools' ),
				),
			)
		);

		// Inline SweetAlert2 modal stats styling.
		$inline_css = '
			.idt-swal-popup { border-radius: 14px !important; font-family: Inter, sans-serif !important; }
			.idt-swal-stats { display: grid; grid-template-columns: repeat(2,1fr); gap: 12px; margin-top: 16px; }
			.idt-swal-stat  { background: #f8f9fa; border-radius: 10px; padding: 14px 10px; text-align: center; }
			.idt-swal-stat strong { display: block; font-size: 28px; font-weight: 700; color: #2c3e50; line-height:1; }
			.idt-swal-stat span   { font-size: 11px; font-weight: 600; text-transform: uppercase; letter-spacing:.5px; color: #718096; }
			.idt-swal-stat.imported strong { color: #27ae60; }
			.idt-swal-stat.skipped  strong { color: #f39c12; }
			.idt-swal-stat.failed   strong { color: #e74c3c; }
		';

		wp_add_inline_style( 'sweetalert2', $inline_css );
	}

	/**
	 * Handle the "Sync All Videos" POST submission.
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

		wp_safe_redirect( $this->build_redirect_url( $result ) );
		exit;
	}

	/**
	 * Handle the "Run Latest Sync Now" POST submission.
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

		wp_safe_redirect( $this->build_redirect_url( $result ) );
		exit;
	}

	/**
	 * Build the POST-redirect-GET URL with sync result query params.
	 *
	 * @since 1.0.0
	 *
	 * @param array|\WP_Error $result Sync result or WP_Error.
	 * @return string Redirect URL.
	 */
	private function build_redirect_url( $result ) {
		$base = admin_url( 'admin.php?page=' . self::PAGE_SLUG );

		if ( is_wp_error( $result ) ) {
			return add_query_arg(
				array(
					'sync_status'  => 'error',
					'sync_message' => rawurlencode( $result->get_error_message() ),
				),
				$base
			);
		}

		return add_query_arg(
			array(
				'sync_status'   => 'success',
				'sync_found'    => absint( $result['found'] ),
				'sync_imported' => absint( $result['imported'] ),
				'sync_skipped'  => absint( $result['skipped'] ),
				'sync_failed'   => absint( $result['failed'] ),
			),
			$base
		);
	}

	/**
	 * Render the YouTube Sync admin page.
	 *
	 * @since 1.0.0
	 */
	public function render_main_page() {
		if ( ! current_user_can( 'manage_options' ) ) {
			return;
		}
		?>
		<div class="wrap idt-main-wrap">
			<div class="idt-hero">
				<div class="idt-hero-icon">
					<svg viewBox="0 0 24 24" fill="currentColor"><path d="M19.35 10.04C18.67 6.59 15.64 4 12 4 9.11 4 6.6 5.64 5.35 8.04 2.34 8.36 0 10.91 0 14c0 3.31 2.69 6 6 6h13c2.76 0 5-2.24 5-5 0-2.64-2.05-4.78-4.65-4.96zM17 13l-5 5-5-5h3V9h4v4h3z"/></svg>
				</div>
				<div class="idt-hero-text">
					<h1><?php esc_html_e( 'Islami Dawa Tools', 'islami-dawa-tools' ); ?></h1>
					<p><?php esc_html_e( 'Powerful tools for Islamic content management and Dawa outreach.', 'islami-dawa-tools' ); ?></p>
				</div>
			</div>

			<div class="idt-grid">
				<?php
				$demo_modules = array(
					array(
						'id'        => 'youtube-sync',
						'title'     => 'YouTube Sync',
						'desc'      => 'Automatically import videos from your YouTube channel as WordPress posts.',
						'icon'      => '<path d="M23.498 6.186a3.016 3.016 0 00-2.122-2.136C19.505 3.545 12 3.545 12 3.545s-7.505 0-9.377.505A3.017 3.017 0 00.502 6.186C0 8.07 0 12 0 12s0 3.93.502 5.814a3.016 3.016 0 002.122 2.136c1.871.505 9.376.505 9.376.505s7.505 0 9.377-.505a3.015 3.015 0 002.122-2.136C24 15.93 24 12 24 12s0-3.93-.502-5.814zM9.545 15.568V8.432L15.818 12l-6.273 3.568z"/>',
						'color'     => '#FF0000',
						'link'      => admin_url( 'admin.php?page=islami-dawa-tools-youtube-sync' ),
						'stats'     => array( 'videos' => 142, 'posts' => 138 ),
					),
					array(
						'id'        => 'quran-reciters',
						'title'     => 'Quran Reciters',
						'desc'      => 'Manage and display Quran recitations from famous reciters worldwide.',
						'icon'      => '<path d="M12 3v10.55c-.59-.34-1.27-.55-2-.55-2.21 0-4 1.79-4 4s1.79 4 4 4 4-1.79 4-4V7h4V3h-6z"/>',
						'color'     => '#27ae60',
						'link'      => '#',
						'stats'     => array( 'reciters' => 25, 'surahs' => 114 ),
					),
					array(
						'id'        => 'duas-collection',
						'title'     => 'Duas Collection',
						'desc'      => 'Beautiful collection of authentic Islamic duas and prayers.',
						'icon'      => '<path d="M19 3h-4.18C14.4 1.84 13.3 1 12 1c-1.3 0-2.4.84-2.82 2H5c-1.1 0-2 .9-2 2v14c0 1.1.9 2 2 2h14c1.1 0 2-.9 2-2V5c0-1.1-.9-2-2-2zm-7 0c.55 0 1 .45 1 1s-.45 1-1 1-1-.45-1-1 .45-1 1-1zm2 14H7v-2h7v2zm3-4H7v-2h10v2zm0-4H7V7h10v2z"/>',
						'color'     => '#9b59b6',
						'link'      => '#',
						'stats'     => array( 'duas' => 80, 'categories' => 12 ),
					),
					array(
						'id'        => 'event-manager',
						'title'     => 'Event Manager',
						'desc'      => 'Organize Islamic events, conferences, and community gatherings.',
						'icon'      => '<path d="M17 12h-5v5h5v-5zM16 1v3H8V1H6v3H5c-1.11 0-1.99.9-1.99 2L3 19c0 1.1.89 2 2 2h14c1.1 0 2-.9 2-2V5c0-1.1-.9-2-2-2h-1V1h-2zm3 18H5V8h14v11z"/>',
						'color'     => '#e67e22',
						'link'      => '#',
						'stats'     => array( 'events' => 5, 'upcoming' => 2 ),
					),
					array(
						'id'        => 'donations',
						'title'     => 'Donations',
						'desc'      => 'Secure online donations and Zakat calculation tools.',
						'icon'      => '<path d="M11.8 10.9c-2.27-.59-3-1.2-3-2.15 0-1.09 1.01-1.85 2.75-1.85 1.78 0 2.44.85 2.5 2.1h2.21c-.07-1.72-1.12-3.3-3.21-3.81V3h-3v2.16c-1.94.42-3.5 1.68-3.5 3.61 0 2.31 1.91 3.46 4.7 4.13 2.5.6 3 1.48 3 2.41 0 .69-.49 1.79-2.7 1.79-2.06 0-2.87-.92-2.98-2.17h-2.2c.12 2.19 1.76 3.42 3.68 3.83V21h3v-2.15c1.95-.41 3.5-1.5 3.5-3.55 0-2.84-2.43-3.81-4.7-4.4z"/>',
						'color'     => '#3498db',
						'link'      => '#',
						'stats'     => array( 'raised' => '$12.5k', 'donors' => 234 ),
					),
					array(
						'id'        => 'analytics',
						'title'     => 'Analytics',
						'desc'      => 'Track engagement, views, and user activity across your content.',
						'icon'      => '<path d="M19 3H5c-1.1 0-2 .9-2 2v14c0 1.1.9 2 2 2h14c1.1 0 2-.9 2-2V5c0-1.1-.9-2-2-2zM9 17H7v-7h2v7zm4 0h-2V7h2v10zm4 0h-2v-4h2v4z"/>',
						'color'     => '#e74c3c',
						'link'      => '#',
						'stats'     => array( 'views' => '45.2k', 'users' => 1820 ),
					),
				);

				foreach ( $demo_modules as $module ) :
					?>
					<a href="<?php echo esc_url( $module['link'] ); ?>" class="idt-module-card" style="--module-color: <?php echo esc_attr( $module['color'] ); ?>;">
						<div class="idt-module-icon">
							<svg viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg"><?php echo $module['icon']; ?></svg>
						</div>
						<div class="idt-module-content">
							<h3><?php echo esc_html( $module['title'] ); ?></h3>
							<p><?php echo esc_html( $module['desc'] ); ?></p>
						</div>
						<div class="idt-module-stats">
							<?php foreach ( $module['stats'] as $label => $value ) : ?>
								<div class="idt-stat">
									<span class="idt-stat-value"><?php echo esc_html( $value ); ?></span>
									<span class="idt-stat-label"><?php echo esc_html( $label ); ?></span>
								</div>
							<?php endforeach; ?>
						</div>
						<div class="idt-module-arrow">
							<svg viewBox="0 0 24 24" fill="currentColor"><path d="M8.59 16.59L13.17 12 8.59 7.41 10 6l6 6-6 6z"/></svg>
						</div>
					</a>
					<?php
				endforeach;
				?>
			</div>
		</div>
		<?php
	}
	public function render_page() {
		if ( ! current_user_can( 'manage_options' ) ) {
			return;
		}

		$api_key    = get_option( 'islami_dawa_tools_youtube_api_key', '' );
		$channel_id = get_option( 'islami_dawa_tools_youtube_channel_id', '' );

		$last_sync_time   = get_option( YouTubeSyncManager::OPTION_LAST_SYNC_TIME, '' );
		$last_sync_result = get_option( YouTubeSyncManager::OPTION_LAST_SYNC_RESULT, '' );
		$next_cron        = wp_next_scheduled( CronManager::CRON_HOOK );
		$cron_active      = ! empty( $api_key ) && ! empty( $channel_id ) && $next_cron;
		?>
		<!-- Spinner overlay -->
		<div class="idt-spinner-overlay" role="status" aria-label="<?php esc_attr_e( 'Syncing, please wait…', 'islami-dawa-tools' ); ?>">
			<div class="idt-spinner"></div>
			<span class="idt-spinner-label"><?php esc_html_e( 'Syncing videos…', 'islami-dawa-tools' ); ?></span>
		</div>

		<div class="wrap idt-yt-wrap">

			<!-- ============================================================
				 Page Header
			============================================================ -->
			<div class="idt-page-header">
				<div class="idt-page-header-icon">
					<!-- YouTube icon -->
					<svg viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
						<path d="M23.498 6.186a3.016 3.016 0 00-2.122-2.136C19.505 3.545 12 3.545 12 3.545s-7.505 0-9.377.505A3.017 3.017 0 00.502 6.186C0 8.07 0 12 0 12s0 3.93.502 5.814a3.016 3.016 0 002.122 2.136c1.871.505 9.376.505 9.376.505s7.505 0 9.377-.505a3.015 3.015 0 002.122-2.136C24 15.93 24 12 24 12s0-3.93-.502-5.814zM9.545 15.568V8.432L15.818 12l-6.273 3.568z"/>
					</svg>
				</div>
				<div class="idt-page-header-text">
					<h1><?php esc_html_e( 'YouTube Channel Sync', 'islami-dawa-tools' ); ?></h1>
					<p><?php esc_html_e( 'Islami Dawa Tools — Automatically import YouTube videos as WordPress posts.', 'islami-dawa-tools' ); ?></p>
				</div>
			</div>

			<!-- ============================================================
				 API Settings Card
			============================================================ -->
			<div class="idt-card">
				<div class="idt-card-header">
					<div class="idt-card-header-icon icon-settings">
						<svg viewBox="0 0 24 24" fill="currentColor"><path d="M19.14 12.94c.04-.3.06-.61.06-.94 0-.32-.02-.64-.07-.94l2.03-1.58c.18-.14.23-.41.12-.61l-1.92-3.32c-.12-.22-.37-.29-.59-.22l-2.39.96c-.5-.38-1.03-.7-1.62-.94l-.36-2.54c-.04-.24-.24-.41-.48-.41h-3.84c-.24 0-.43.17-.47.41l-.36 2.54c-.59.24-1.13.57-1.62.94l-2.39-.96c-.22-.08-.47 0-.59.22L2.74 8.87c-.12.21-.08.47.12.61l2.03 1.58c-.05.3-.09.63-.09.94s.02.64.07.94l-2.03 1.58c-.18.14-.23.41-.12.61l1.92 3.32c.12.22.37.29.59.22l2.39-.96c.5.38 1.03.7 1.62.94l.36 2.54c.05.24.24.41.48.41h3.84c.24 0 .44-.17.47-.41l.36-2.54c.59-.24 1.13-.56 1.62-.94l2.39.96c.22.08.47 0 .59-.22l1.92-3.32c.12-.22.07-.47-.12-.61l-2.01-1.58zM12 15.6c-1.98 0-3.6-1.62-3.6-3.6s1.62-3.6 3.6-3.6 3.6 1.62 3.6 3.6-1.62 3.6-3.6 3.6z"/></svg>
					</div>
					<h2><?php esc_html_e( 'API Settings', 'islami-dawa-tools' ); ?></h2>
				</div>
				<div class="idt-card-body">
					<form method="post" action="<?php echo esc_url( admin_url( 'options.php' ) ); ?>">
						<?php settings_fields( YouTubeSyncSettings::SETTINGS_GROUP ); ?>

						<div class="idt-field-group">
							<label class="idt-label" for="idt_api_key">
								<?php esc_html_e( 'YouTube API Key', 'islami-dawa-tools' ); ?>
								<span class="required">*</span>
							</label>
							<input
								type="text"
								id="idt_api_key"
								name="islami_dawa_tools_youtube_api_key"
								class="idt-input"
								value="<?php echo esc_attr( $api_key ); ?>"
								placeholder="AIzaSy..."
								autocomplete="off"
								spellcheck="false"
							/>
							<p class="idt-hint">
								<?php
								printf(
									/* translators: %s: Google Cloud Console link */
									esc_html__( 'Generate a key in the %s with YouTube Data API v3 enabled.', 'islami-dawa-tools' ),
									'<a href="https://console.cloud.google.com/apis/credentials" target="_blank" rel="noopener noreferrer">' . esc_html__( 'Google Cloud Console', 'islami-dawa-tools' ) . '</a>'
								);
								?>
							</p>
						</div>

						<div class="idt-field-group">
							<label class="idt-label" for="idt_channel_id">
								<?php esc_html_e( 'YouTube Channel ID', 'islami-dawa-tools' ); ?>
								<span class="required">*</span>
							</label>
							<input
								type="text"
								id="idt_channel_id"
								name="islami_dawa_tools_youtube_channel_id"
								class="idt-input"
								value="<?php echo esc_attr( $channel_id ); ?>"
								placeholder="UCxxxxxxxxxxxxxxxx"
								spellcheck="false"
							/>
							<p class="idt-hint">
								<?php esc_html_e( 'Found in YouTube Studio → Settings → Channel → Advanced settings.', 'islami-dawa-tools' ); ?>
							</p>
						</div>

						<div class="idt-btn-row" style="margin-top:24px;">
							<button type="submit" class="idt-btn idt-btn-save">
								<svg viewBox="0 0 24 24" fill="currentColor"><path d="M17 3H5c-1.11 0-2 .9-2 2v14c0 1.1.89 2 2 2h14c1.1 0 2-.9 2-2V7l-4-4zm-5 16c-1.66 0-3-1.34-3-3s1.34-3 3-3 3 1.34 3 3-1.34 3-3 3zm3-10H5V5h10v4z"/></svg>
								<?php esc_html_e( 'Save Settings', 'islami-dawa-tools' ); ?>
							</button>
						</div>
					</form>
				</div>
			</div>

			<!-- ============================================================
				 Manual Sync Card
			============================================================ -->
			<div class="idt-card">
				<div class="idt-card-header">
					<div class="idt-card-header-icon icon-sync">
						<svg viewBox="0 0 24 24" fill="currentColor"><path d="M12 4V1L8 5l4 4V6c3.31 0 6 2.69 6 6 0 1.01-.25 1.97-.7 2.8l1.46 1.46C19.54 15.03 20 13.57 20 12c0-4.42-3.58-8-8-8zm0 14c-3.31 0-6-2.69-6-6 0-1.01.25-1.97.7-2.8L5.24 7.74C4.46 8.97 4 10.43 4 12c0 4.42 3.58 8 8 8v3l4-4-4-4v3z"/></svg>
					</div>
					<h2><?php esc_html_e( 'Manual Sync', 'islami-dawa-tools' ); ?></h2>
				</div>
				<div class="idt-card-body">
					<p style="margin: 0 0 20px; color: var(--idt-text-muted); font-size:13.5px; line-height:1.6;">
						<?php esc_html_e( '"Sync All Videos" imports every video from your channel (uses full API pagination). "Run Latest Sync Now" checks only the 10 most recent uploads — ideal for a quick catch-up.', 'islami-dawa-tools' ); ?>
					</p>

					<div class="idt-btn-row">
						<form id="idt-sync-all-form" method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>">
							<input type="hidden" name="action" value="islami_dawa_tools_sync_all" />
							<?php wp_nonce_field( self::NONCE_SYNC_ALL ); ?>
							<button type="submit" class="idt-btn idt-btn-primary">
								<svg viewBox="0 0 24 24" fill="currentColor"><path d="M19.35 10.04C18.67 6.59 15.64 4 12 4 9.11 4 6.6 5.64 5.35 8.04 2.34 8.36 0 10.91 0 14c0 3.31 2.69 6 6 6h13c2.76 0 5-2.24 5-5 0-2.64-2.05-4.78-4.65-4.96zM17 13l-5 5-5-5h3V9h4v4h3z"/></svg>
								<?php esc_html_e( 'Sync All Videos', 'islami-dawa-tools' ); ?>
							</button>
						</form>

						<form id="idt-sync-latest-form" method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>">
							<input type="hidden" name="action" value="islami_dawa_tools_sync_latest" />
							<?php wp_nonce_field( self::NONCE_SYNC_LATEST ); ?>
							<button type="submit" class="idt-btn idt-btn-secondary">
								<svg viewBox="0 0 24 24" fill="currentColor"><path d="M13 3c-4.97 0-9 4.03-9 9H1l3.89 3.89.07.14L9 12H6c0-3.87 3.13-7 7-7s7 3.13 7 7-3.13 7-7 7c-1.93 0-3.68-.79-4.94-2.06l-1.42 1.42C8.27 19.99 10.51 21 13 21c4.97 0 9-4.03 9-9s-4.03-9-9-9zm-1 5v5l4.28 2.54.72-1.21-3.5-2.08V8H12z"/></svg>
								<?php esc_html_e( 'Run Latest Sync Now', 'islami-dawa-tools' ); ?>
							</button>
						</form>
					</div>
				</div>
			</div>

			<!-- ============================================================
				 Status Card
			============================================================ -->
			<div class="idt-card">
				<div class="idt-card-header">
					<div class="idt-card-header-icon icon-status">
						<svg viewBox="0 0 24 24" fill="currentColor"><path d="M12 2C6.48 2 2 6.48 2 12s4.48 10 10 10 10-4.48 10-10S17.52 2 12 2zm-2 15l-5-5 1.41-1.41L10 14.17l7.59-7.59L19 8l-9 9z"/></svg>
					</div>
					<h2><?php esc_html_e( 'Sync Status', 'islami-dawa-tools' ); ?></h2>
				</div>
				<div class="idt-card-body">
					<table class="idt-status-table">
						<tbody>
							<tr>
								<th><?php esc_html_e( 'Auto-Sync (Cron)', 'islami-dawa-tools' ); ?></th>
								<td>
									<?php if ( $cron_active ) : ?>
										<span class="idt-badge idt-badge-success">
											<span class="idt-badge-dot"></span>
											<?php esc_html_e( 'Active', 'islami-dawa-tools' ); ?>
										</span>
									<?php else : ?>
										<span class="idt-badge idt-badge-danger">
											<span class="idt-badge-dot"></span>
											<?php esc_html_e( 'Inactive — save API key &amp; Channel ID first', 'islami-dawa-tools' ); ?>
										</span>
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
								<td><?php echo $last_sync_time ? esc_html( $last_sync_time ) : '<em style="color:var(--idt-text-muted)">' . esc_html__( 'Never synced', 'islami-dawa-tools' ) . '</em>'; ?></td>
							</tr>

							<tr>
								<th><?php esc_html_e( 'Last Sync Result', 'islami-dawa-tools' ); ?></th>
								<td><?php echo $last_sync_result ? esc_html( $last_sync_result ) : '<em style="color:var(--idt-text-muted)">&mdash;</em>'; ?></td>
							</tr>
						</tbody>
					</table>
				</div>
			</div>

		</div><!-- .idt-yt-wrap -->
		<?php
	}
}

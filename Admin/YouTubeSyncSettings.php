<?php
/**
 * YouTubeSyncSettings.php
 *
 * Registers WordPress Settings API options for the YouTube sync feature:
 * - islami_dawa_tools_youtube_api_key
 * - islami_dawa_tools_youtube_channel_id
 *
 * @package IslamiDawaTools\Admin
 * @since   1.0.0
 */

namespace IslamiDawaTools\Admin;

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

/**
 * Class YouTubeSyncSettings
 *
 * Registers and sanitizes the YouTube API key and Channel ID options
 * using the WordPress Settings API.
 *
 * @package IslamiDawaTools\Admin
 * @since   1.0.0
 */
class YouTubeSyncSettings {

	/**
	 * Settings group name.
	 *
	 * @since 1.0.0
	 * @var string
	 */
	const SETTINGS_GROUP = 'islami_dawa_tools_youtube_sync_settings';

	/**
	 * Option name for the YouTube API key.
	 *
	 * @since 1.0.0
	 * @var string
	 */
	const OPTION_API_KEY = 'islami_dawa_tools_youtube_api_key';

	/**
	 * Option name for the YouTube Channel ID.
	 *
	 * @since 1.0.0
	 * @var string
	 */
	const OPTION_CHANNEL_ID = 'islami_dawa_tools_youtube_channel_id';

	/**
	 * Constructor — registers WordPress hooks.
	 *
	 * @since 1.0.0
	 */
	public function __construct() {
		add_action( 'admin_init', array( $this, 'register_settings' ) );
	}

	/**
	 * Register settings, sections, and fields via the Settings API.
	 *
	 * @since 1.0.0
	 */
	public function register_settings() {
		// Register settings with sanitization callbacks.
		register_setting(
			self::SETTINGS_GROUP,
			self::OPTION_API_KEY,
			array(
				'type'              => 'string',
				'sanitize_callback' => 'sanitize_text_field',
				'default'           => '',
			)
		);

		register_setting(
			self::SETTINGS_GROUP,
			self::OPTION_CHANNEL_ID,
			array(
				'type'              => 'string',
				'sanitize_callback' => 'sanitize_text_field',
				'default'           => '',
			)
		);
	}
}

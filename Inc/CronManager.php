<?php
/**
 * CronManager.php
 *
 * Manages WP-Cron scheduling for the YouTube sync feature.
 * Registers a filterable cron interval and schedules/clears the recurring event.
 *
 * @package IslamiDawaTools
 * @since   1.0.0
 */

namespace IslamiDawaTools;

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

/**
 * Class CronManager
 *
 * Registers a custom WP-Cron schedule and hooks the recurring YouTube sync event
 * to YouTubeSyncManager::sync_latest_videos(). The cron interval is filterable.
 *
 * @package IslamiDawaTools
 * @since   1.0.0
 */
class CronManager {

	/**
	 * WP-Cron hook name used by the YouTube sync event.
	 *
	 * @since 1.0.0
	 * @var string
	 */
	const CRON_HOOK = 'islami_dawa_tools_youtube_sync';

	/**
	 * Custom cron schedule slug.
	 *
	 * @since 1.0.0
	 * @var string
	 */
	const SCHEDULE_SLUG = 'islami_dawa_tools_every_15_minutes';

	/**
	 * Constructor — registers WordPress hooks.
	 *
	 * @since 1.0.0
	 */
	public function __construct() {
		add_filter( 'cron_schedules', array( $this, 'add_cron_schedules' ) ); // phpcs:ignore WordPress.WP.CronInterval.ChangeDetected
		add_action( 'init', array( $this, 'schedule_event' ) );
		add_action( self::CRON_HOOK, array( $this, 'run_sync' ) );
	}

	/**
	 * Register a custom cron schedule (every 15 minutes).
	 *
	 * The interval is filterable via `islami_dawa_tools_cron_interval`.
	 * The filter receives the interval in seconds and the schedule slug.
	 *
	 * @since 1.0.0
	 *
	 * @param array $schedules Existing WP-Cron schedules.
	 * @return array Modified schedules.
	 */
	public function add_cron_schedules( $schedules ) {
		/**
		 * Filters the cron interval in seconds for the YouTube sync event.
		 *
		 * Use this filter to increase or decrease how often the plugin checks
		 * for newly uploaded videos. Default is 900 seconds (15 minutes).
		 *
		 * Example (hourly):
		 *   add_filter( 'islami_dawa_tools_cron_interval', function() { return 3600; } );
		 *
		 * @since 1.0.0
		 *
		 * @param int $interval Interval in seconds. Default 900.
		 */
		$interval = apply_filters( 'islami_dawa_tools_cron_interval', 900 );
		$interval = max( 300, absint( $interval ) ); // Enforce a 5-minute minimum.

		$schedules[ self::SCHEDULE_SLUG ] = array(
			'interval' => $interval,
			'display'  => __( 'Islami Dawa Tools: Every 15 minutes (configurable)', 'islami-dawa-tools' ),
		);

		return $schedules;
	}

	/**
	 * Schedule the recurring sync event if it is not already scheduled.
	 *
	 * Called on the 'init' action so access to the full WordPress environment
	 * is guaranteed.
	 *
	 * @since 1.0.0
	 */
	public function schedule_event() {
		// Only schedule if both API key and channel ID are configured.
		$api_key    = get_option( 'islami_dawa_tools_youtube_api_key', '' );
		$channel_id = get_option( 'islami_dawa_tools_youtube_channel_id', '' );

		if ( empty( $api_key ) || empty( $channel_id ) ) {
			return;
		}

		if ( ! wp_next_scheduled( self::CRON_HOOK ) ) {
			wp_schedule_event( time(), self::SCHEDULE_SLUG, self::CRON_HOOK );
		}
	}

	/**
	 * Execute the scheduled YouTube sync.
	 *
	 * Runs sync_latest_videos() via the YouTubeSyncManager.
	 * Errors are silently logged to avoid breaking the cron queue.
	 *
	 * @since 1.0.0
	 */
	public function run_sync() {
		$manager = new YouTubeSyncManager();
		$result  = $manager->sync_latest_videos( 10 );

		if ( is_wp_error( $result ) ) {
			error_log( // phpcs:ignore WordPress.PHP.DevelopmentFunctions.error_log_error_log
				'[IslamiDawaTools] Cron sync error: ' . $result->get_error_message()
			);
		}
	}

	/**
	 * Clear the scheduled cron event.
	 *
	 * Should be called on plugin deactivation.
	 *
	 * @since 1.0.0
	 */
	public static function clear_scheduled_event() {
		$timestamp = wp_next_scheduled( self::CRON_HOOK );

		if ( $timestamp ) {
			wp_unschedule_event( $timestamp, self::CRON_HOOK );
		}
	}
}

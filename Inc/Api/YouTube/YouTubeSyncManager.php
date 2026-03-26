<?php
/**
 * YouTubeSyncManager.php
 *
 * Orchestrates all sync operations (manual full sync and cron latest-video sync).
 * Located under Inc/Api/YouTube/ — tightly coupled to the YouTube API service layer.
 *
 * @package IslamiDawaTools\Api\YouTube
 * @since   1.0.0
 */

namespace IslamiDawaTools\Api\YouTube;

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

/**
 * Class YouTubeSyncManager
 *
 * Coordinates YouTubeApiService and YouTubeImporter to perform full-channel
 * sync (manual) and latest-video sync (cron). Persists timestamps and result
 * summaries to WordPress options.
 *
 * @package IslamiDawaTools\Api\YouTube
 * @since   1.0.0
 */
class YouTubeSyncManager {

	/**
	 * Option key for the last sync timestamp.
	 *
	 * @since 1.0.0
	 * @var string
	 */
	const OPTION_LAST_SYNC_TIME = 'islami_dawa_tools_youtube_last_sync_time';

	/**
	 * Option key for the last sync result summary string.
	 *
	 * @since 1.0.0
	 * @var string
	 */
	const OPTION_LAST_SYNC_RESULT = 'islami_dawa_tools_youtube_last_sync_result';

	/**
	 * YouTube API service instance.
	 *
	 * @since 1.0.0
	 * @var YouTubeApiService
	 */
	private $api;

	/**
	 * YouTube importer instance.
	 *
	 * @since 1.0.0
	 * @var YouTubeImporter
	 */
	private $importer;

	/**
	 * Constructor.
	 *
	 * Reads the stored API key to build the API service.
	 *
	 * @since 1.0.0
	 */
	public function __construct() {
		$api_key        = get_option( 'islami_dawa_tools_youtube_api_key', '' );
		$this->api      = new YouTubeApiService( $api_key );
		$this->importer = new YouTubeImporter();
	}

	/**
	 * Perform a full sync of all videos from the configured YouTube channel.
	 *
	 * Fetches every video via API pagination and imports missing ones.
	 * Intended for the manual "Sync All Videos" admin button.
	 *
	 * @since 1.0.0
	 *
	 * @return array|\WP_Error Summary array or WP_Error on catastrophic failure.
	 */
	public function sync_all_videos() {
		$channel_id = get_option( 'islami_dawa_tools_youtube_channel_id', '' );

		if ( empty( $channel_id ) ) {
			return new \WP_Error(
				'islami_dawa_tools_no_channel_id',
				__( 'YouTube Channel ID is not configured.', 'islami-dawa-tools' )
			);
		}

		$items = $this->api->get_channel_videos( $channel_id );

		if ( is_wp_error( $items ) ) {
			return $items;
		}

		return $this->process_items( $items );
	}

	/**
	 * Sync only the latest N videos from the channel.
	 *
	 * Intended for use by the WP-Cron recurring event.
	 *
	 * @since 1.0.0
	 *
	 * @param int $max_results Number of recent videos to fetch. Default 10.
	 * @return array|\WP_Error Summary array or WP_Error on catastrophic failure.
	 */
	public function sync_latest_videos( $max_results = 10 ) {
		$channel_id = get_option( 'islami_dawa_tools_youtube_channel_id', '' );

		if ( empty( $channel_id ) ) {
			return new \WP_Error(
				'islami_dawa_tools_no_channel_id',
				__( 'YouTube Channel ID is not configured.', 'islami-dawa-tools' )
			);
		}

		$items = $this->api->get_latest_videos( $channel_id, absint( $max_results ) );

		if ( is_wp_error( $items ) ) {
			return $items;
		}

		return $this->process_items( $items );
	}

	/**
	 * Process a raw list of YouTube playlistItems API items.
	 *
	 * Parses each item, imports via YouTubeImporter, accumulates the summary,
	 * and persists the result to WordPress options.
	 *
	 * @since 1.0.0
	 *
	 * @param array $items Raw items from YouTubeApiService.
	 * @return array {
	 *     Sync result summary.
	 *
	 *     @type int   $found    Total videos found in the API response.
	 *     @type int   $imported Number of newly imported posts.
	 *     @type int   $skipped  Number of already-imported videos skipped.
	 *     @type int   $failed   Number of videos that failed to import.
	 *     @type array $details  Per-video result arrays from YouTubeImporter::import().
	 * }
	 */
	private function process_items( array $items ) {
		$summary = array(
			'found'    => count( $items ),
			'imported' => 0,
			'skipped'  => 0,
			'failed'   => 0,
			'details'  => array(),
		);

		foreach ( $items as $raw_item ) {
			$video = $this->api->parse_video_item( $raw_item );

			if ( false === $video ) {
				++$summary['skipped']; // Private / deleted video.
				continue;
			}

			$result = $this->importer->import( $video );

			switch ( $result['status'] ) {
				case 'imported':
					++$summary['imported'];
					break;
				case 'skipped':
					++$summary['skipped'];
					break;
				default:
					++$summary['failed'];
					break;
			}

			$summary['details'][] = $result;
		}

		// Persist last sync metadata.
		update_option( self::OPTION_LAST_SYNC_TIME, current_time( 'mysql' ) );
		update_option(
			self::OPTION_LAST_SYNC_RESULT,
			sprintf(
				/* translators: 1: found, 2: imported, 3: skipped, 4: failed */
				__( 'Found: %1$d | Imported: %2$d | Skipped: %3$d | Failed: %4$d', 'islami-dawa-tools' ),
				$summary['found'],
				$summary['imported'],
				$summary['skipped'],
				$summary['failed']
			)
		);

		return $summary;
	}
}

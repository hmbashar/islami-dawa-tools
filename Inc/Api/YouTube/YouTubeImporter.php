<?php
/**
 * YouTubeImporter.php
 *
 * Imports a single YouTube video into a WordPress "video" custom post.
 * Located under Inc/Api/YouTube/ alongside the API service class.
 *
 * @package IslamiDawaTools\Api\YouTube
 * @since   1.0.0
 */

namespace IslamiDawaTools\Api\YouTube;

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

/**
 * Class YouTubeImporter
 *
 * Imports a single YouTube video into a WordPress custom post of type "video".
 * Used by both the manual sync and the automatic WP-Cron sync.
 *
 * @package IslamiDawaTools\Api\YouTube
 * @since   1.0.0
 */
class YouTubeImporter {

	/**
	 * Post meta key to store the YouTube video ID (used for duplicate prevention).
	 *
	 * @since 1.0.0
	 * @var string
	 */
	const META_VIDEO_ID = '_islami_dawa_tools_youtube_video_id';

	/**
	 * Post meta key to store the YouTube published-at ISO date.
	 *
	 * @since 1.0.0
	 * @var string
	 */
	const META_PUBLISHED_AT = '_islami_dawa_tools_youtube_published_at';

	/**
	 * Post meta key to store the remote thumbnail URL (avoids re-downloading).
	 *
	 * @since 1.0.0
	 * @var string
	 */
	const META_THUMBNAIL_URL = '_islami_dawa_tools_youtube_thumbnail_url';

	/**
	 * ACF field name for the video URL.
	 *
	 * @since 1.0.0
	 * @var string
	 */
	const ACF_FIELD_VIDEO_URL = 'isdc_video_url';

	/**
	 * Import a single video.
	 *
	 * @since 1.0.0
	 *
	 * @param array $video Normalised video data from YouTubeApiService::parse_video_item().
	 *                     Expected keys: video_id, title, url, thumbnail, published_at.
	 * @return array {
	 *     Result array.
	 *
	 *     @type string   $status   'imported' | 'skipped' | 'failed'
	 *     @type string   $message  Human-readable result message.
	 *     @type int|null $post_id  WordPress post ID if created, otherwise null.
	 *     @type string   $video_id YouTube video ID.
	 * }
	 */
	public function import( array $video ) {
		$video_id = isset( $video['video_id'] ) ? sanitize_text_field( $video['video_id'] ) : '';

		if ( empty( $video_id ) ) {
			return $this->result( 'failed', __( 'Video ID is missing.', 'islami-dawa-tools' ), null, '' );
		}

		// --- Duplicate check -------------------------------------------
		if ( $this->video_exists( $video_id ) ) {
			return $this->result(
				'skipped',
				/* translators: %s: YouTube video ID */
				sprintf( __( 'Video %s already imported.', 'islami-dawa-tools' ), $video_id ),
				null,
				$video_id
			);
		}

		/**
		 * Fires before a YouTube video is imported.
		 *
		 * @since 1.0.0
		 * @param array $video Normalised video data.
		 */
		do_action( 'islami_dawa_tools_before_video_import', $video );

		// --- Prepare post title ----------------------------------------
		$post_title = isset( $video['title'] ) ? sanitize_text_field( $video['title'] ) : '';

		/**
		 * Filters the post title before the video post is inserted.
		 *
		 * @since 1.0.0
		 * @param string $post_title The video title from YouTube.
		 * @param array  $video      Normalised video data.
		 */
		$post_title = apply_filters( 'islami_dawa_tools_video_post_title', $post_title, $video );

		/**
		 * Filters the post status for imported video posts.
		 *
		 * @since 1.0.0
		 * @param string $post_status Default 'publish'.
		 * @param array  $video       Normalised video data.
		 */
		$post_status = apply_filters( 'islami_dawa_tools_video_post_status', 'publish', $video );
		$post_status = sanitize_key( $post_status );

		// --- Insert post -----------------------------------------------
		$post_id = wp_insert_post(
			array(
				'post_title'  => $post_title,
				'post_status' => $post_status,
				'post_type'   => 'video',
			),
			true
		);

		if ( is_wp_error( $post_id ) ) {
			return $this->result(
				'failed',
				sprintf(
					/* translators: %s: error message */
					__( 'Failed to insert post: %s', 'islami-dawa-tools' ),
					$post_id->get_error_message()
				),
				null,
				$video_id
			);
		}

		// --- Save meta -------------------------------------------------
		update_post_meta( $post_id, self::META_VIDEO_ID, $video_id );

		if ( ! empty( $video['published_at'] ) ) {
			update_post_meta( $post_id, self::META_PUBLISHED_AT, sanitize_text_field( $video['published_at'] ) );
		}

		if ( ! empty( $video['thumbnail'] ) ) {
			update_post_meta( $post_id, self::META_THUMBNAIL_URL, esc_url_raw( $video['thumbnail'] ) );
		}

		// --- Save video URL via ACF or fallback to post meta -----------
		if ( ! empty( $video['url'] ) ) {
			$this->save_video_url( $post_id, esc_url_raw( $video['url'] ) );
		}

		// --- Sideload thumbnail ----------------------------------------
		if ( ! empty( $video['thumbnail'] ) ) {
			$this->sideload_thumbnail( $post_id, $video['thumbnail'], $post_title );
		}

		/**
		 * Fires after a YouTube video has been successfully imported.
		 *
		 * @since 1.0.0
		 * @param int   $post_id WordPress post ID.
		 * @param array $video   Normalised video data.
		 */
		do_action( 'islami_dawa_tools_after_video_import', $post_id, $video );

		return $this->result(
			'imported',
			/* translators: 1: video title, 2: YouTube video ID */
			sprintf( __( 'Imported "%1$s" (%2$s).', 'islami-dawa-tools' ), $post_title, $video_id ),
			$post_id,
			$video_id
		);
	}

	/**
	 * Check whether a video has already been imported by querying post meta.
	 *
	 * @since 1.0.0
	 *
	 * @param string $video_id YouTube video ID.
	 * @return bool True if already imported.
	 */
	public function video_exists( $video_id ) {
		$query = new \WP_Query(
			array(
				'post_type'      => 'video',
				'post_status'    => 'any',
				'posts_per_page' => 1,
				'fields'         => 'ids',
				'no_found_rows'  => true,
				'meta_query'     => array( // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_query
					array(
						'key'   => self::META_VIDEO_ID,
						'value' => sanitize_text_field( $video_id ),
					),
				),
			)
		);

		return $query->have_posts();
	}

	/**
	 * Save the YouTube video URL using ACF when available, with a post_meta fallback.
	 *
	 * @since 1.0.0
	 *
	 * @param int    $post_id   WordPress post ID.
	 * @param string $video_url YouTube watch URL.
	 */
	private function save_video_url( $post_id, $video_url ) {
		if ( function_exists( 'update_field' ) ) {
			update_field( self::ACF_FIELD_VIDEO_URL, $video_url, $post_id );
		} else {
			update_post_meta( $post_id, self::ACF_FIELD_VIDEO_URL, $video_url );
		}
	}

	/**
	 * Sideload a YouTube thumbnail and set it as the post's featured image.
	 *
	 * Skips re-download if the stored thumbnail URL matches the current one,
	 * preventing duplicate media library entries across sync runs.
	 *
	 * @since 1.0.0
	 *
	 * @param int    $post_id       WordPress post ID.
	 * @param string $thumbnail_url Remote thumbnail URL.
	 * @param string $post_title    Used as image alt text / description.
	 * @return int|false Attachment ID on success, false on failure.
	 */
	private function sideload_thumbnail( $post_id, $thumbnail_url, $post_title ) {
		$stored_url = get_post_meta( $post_id, self::META_THUMBNAIL_URL, true );
		$current_id = get_post_thumbnail_id( $post_id );

		// Already attached and URL unchanged — skip.
		if ( $current_id && $stored_url === $thumbnail_url ) {
			return $current_id;
		}

		if ( ! function_exists( 'media_sideload_image' ) ) {
			require_once ABSPATH . 'wp-admin/includes/media.php';
			require_once ABSPATH . 'wp-admin/includes/file.php';
			require_once ABSPATH . 'wp-admin/includes/image.php';
		}

		$attachment_id = media_sideload_image(
			esc_url_raw( $thumbnail_url ),
			$post_id,
			sanitize_text_field( $post_title ),
			'id'
		);

		if ( is_wp_error( $attachment_id ) ) {
			error_log( // phpcs:ignore WordPress.PHP.DevelopmentFunctions.error_log_error_log
				sprintf(
					'[IslamiDawaTools] Thumbnail sideload failed for post %d: %s',
					$post_id,
					$attachment_id->get_error_message()
				)
			);
			return false;
		}

		set_post_thumbnail( $post_id, $attachment_id );

		return $attachment_id;
	}

	/**
	 * Build a structured result array.
	 *
	 * @since 1.0.0
	 *
	 * @param string   $status   'imported' | 'skipped' | 'failed'.
	 * @param string   $message  Human-readable result message.
	 * @param int|null $post_id  WordPress post ID or null.
	 * @param string   $video_id YouTube video ID.
	 * @return array
	 */
	private function result( $status, $message, $post_id, $video_id ) {
		return array(
			'status'   => $status,
			'message'  => $message,
			'post_id'  => $post_id,
			'video_id' => $video_id,
		);
	}
}

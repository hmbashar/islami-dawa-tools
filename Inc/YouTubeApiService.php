<?php
/**
 * YouTubeApiService.php
 *
 * Wraps all YouTube Data API v3 HTTP requests. Returns structured PHP arrays.
 * No raw JSON is ever returned to callers.
 *
 * @package IslamiDawaTools
 * @since   1.0.0
 */

namespace IslamiDawaTools;

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

/**
 * Class YouTubeApiService
 *
 * Responsible for communicating with the YouTube Data API v3.
 * All public methods return WP_Error on failure or a structured array on success.
 *
 * @package IslamiDawaTools
 * @since   1.0.0
 */
class YouTubeApiService {

	/**
	 * YouTube Data API v3 base URL.
	 *
	 * @since 1.0.0
	 * @var string
	 */
	const API_BASE = 'https://www.googleapis.com/youtube/v3/';

	/**
	 * YouTube API key.
	 *
	 * @since 1.0.0
	 * @var string
	 */
	private $api_key;

	/**
	 * Constructor.
	 *
	 * @since 1.0.0
	 *
	 * @param string $api_key YouTube Data API v3 key.
	 */
	public function __construct( $api_key ) {
		$this->api_key = sanitize_text_field( $api_key );
	}

	/**
	 * Retrieve the "uploads" playlist ID for a given channel.
	 *
	 * @since 1.0.0
	 *
	 * @param string $channel_id YouTube Channel ID.
	 * @return string|\WP_Error Uploads playlist ID or WP_Error on failure.
	 */
	public function get_uploads_playlist_id( $channel_id ) {
		$channel_id = sanitize_text_field( $channel_id );

		if ( empty( $this->api_key ) ) {
			return new \WP_Error(
				'islami_dawa_tools_missing_api_key',
				__( 'YouTube API key is not configured.', 'islami-dawa-tools' )
			);
		}

		if ( empty( $channel_id ) ) {
			return new \WP_Error(
				'islami_dawa_tools_missing_channel_id',
				__( 'YouTube Channel ID is not configured.', 'islami-dawa-tools' )
			);
		}

		$url = add_query_arg(
			array(
				'part' => 'contentDetails',
				'id'   => $channel_id,
				'key'  => $this->api_key,
			),
			self::API_BASE . 'channels'
		);

		$response = $this->make_request( $url );

		if ( is_wp_error( $response ) ) {
			return $response;
		}

		if ( empty( $response['items'][0]['contentDetails']['relatedPlaylists']['uploads'] ) ) {
			return new \WP_Error(
				'islami_dawa_tools_no_uploads_playlist',
				__( 'Could not retrieve uploads playlist for the given channel.', 'islami-dawa-tools' )
			);
		}

		return $response['items'][0]['contentDetails']['relatedPlaylists']['uploads'];
	}

	/**
	 * Get all videos from a channel's uploads playlist with pagination support.
	 *
	 * Iterates through all pages and returns every video item.
	 *
	 * @since 1.0.0
	 *
	 * @param string $channel_id YouTube Channel ID.
	 * @return array|\WP_Error Array of video items on success, WP_Error on failure.
	 */
	public function get_channel_videos( $channel_id ) {
		$playlist_id = $this->get_uploads_playlist_id( $channel_id );

		if ( is_wp_error( $playlist_id ) ) {
			return $playlist_id;
		}

		$all_items  = array();
		$page_token = '';

		do {
			$args = array(
				'part'       => 'snippet',
				'playlistId' => $playlist_id,
				'maxResults' => 50, // maximum allowed by the API.
				'key'        => $this->api_key,
			);

			if ( ! empty( $page_token ) ) {
				$args['pageToken'] = $page_token;
			}

			$url      = add_query_arg( $args, self::API_BASE . 'playlistItems' );
			$response = $this->make_request( $url );

			if ( is_wp_error( $response ) ) {
				return $response;
			}

			if ( ! empty( $response['items'] ) ) {
				$all_items = array_merge( $all_items, $response['items'] );
			}

			$page_token = isset( $response['nextPageToken'] ) ? $response['nextPageToken'] : '';

		} while ( ! empty( $page_token ) );

		return $all_items;
	}

	/**
	 * Get the latest N videos from a channel's uploads playlist.
	 *
	 * Used by the cron sync to check for newly uploaded videos.
	 *
	 * @since 1.0.0
	 *
	 * @param string $channel_id  YouTube Channel ID.
	 * @param int    $max_results Number of videos to retrieve (1–50).
	 * @return array|\WP_Error Array of video items on success, WP_Error on failure.
	 */
	public function get_latest_videos( $channel_id, $max_results = 10 ) {
		$playlist_id = $this->get_uploads_playlist_id( $channel_id );

		if ( is_wp_error( $playlist_id ) ) {
			return $playlist_id;
		}

		$max_results = min( 50, max( 1, absint( $max_results ) ) );

		$args = array(
			'part'       => 'snippet',
			'playlistId' => $playlist_id,
			'maxResults' => $max_results,
			'key'        => $this->api_key,
		);

		$url      = add_query_arg( $args, self::API_BASE . 'playlistItems' );
		$response = $this->make_request( $url );

		if ( is_wp_error( $response ) ) {
			return $response;
		}

		return isset( $response['items'] ) ? $response['items'] : array();
	}

	/**
	 * Parse a raw YouTube playlistItems API item into a normalised array.
	 *
	 * @since 1.0.0
	 *
	 * @param array $item Raw item from YouTubeApiService::get_channel_videos().
	 * @return array|false Normalised video data or false if the item is invalid/private.
	 */
	public function parse_video_item( $item ) {
		if ( empty( $item['snippet'] ) ) {
			return false;
		}

		$snippet  = $item['snippet'];
		$video_id = isset( $snippet['resourceId']['videoId'] ) ? sanitize_text_field( $snippet['resourceId']['videoId'] ) : '';

		// Skip private / deleted videos.
		if ( empty( $video_id ) || 'private' === $snippet['title'] ) {
			return false;
		}

		// Determine the best available thumbnail.
		$thumbnail_url = $this->get_best_thumbnail( $snippet );

		return array(
			'video_id'     => $video_id,
			'title'        => sanitize_text_field( $snippet['title'] ),
			'url'          => 'https://www.youtube.com/watch?v=' . $video_id,
			'thumbnail'    => $thumbnail_url,
			'published_at' => isset( $snippet['publishedAt'] ) ? sanitize_text_field( $snippet['publishedAt'] ) : '',
		);
	}

	/**
	 * Select the highest-resolution thumbnail URL available.
	 *
	 * Priority: maxres → standard → high → medium → default.
	 *
	 * @since 1.0.0
	 *
	 * @param array $snippet YouTube snippet array containing thumbnails.
	 * @return string Thumbnail URL or empty string if none found.
	 */
	private function get_best_thumbnail( $snippet ) {
		if ( empty( $snippet['thumbnails'] ) ) {
			return '';
		}

		$priority = array( 'maxres', 'standard', 'high', 'medium', 'default' );

		foreach ( $priority as $size ) {
			if ( ! empty( $snippet['thumbnails'][ $size ]['url'] ) ) {
				return esc_url_raw( $snippet['thumbnails'][ $size ]['url'] );
			}
		}

		return '';
	}

	/**
	 * Make an HTTP GET request to the YouTube API.
	 *
	 * @since 1.0.0
	 *
	 * @param string $url Full request URL including query string.
	 * @return array|\WP_Error Decoded response body array or WP_Error on failure.
	 */
	private function make_request( $url ) {
		$response = wp_remote_get(
			$url,
			array(
				'timeout' => 30,
			)
		);

		if ( is_wp_error( $response ) ) {
			return new \WP_Error(
				'islami_dawa_tools_api_request_failed',
				sprintf(
					/* translators: %s: error message */
					__( 'YouTube API request failed: %s', 'islami-dawa-tools' ),
					$response->get_error_message()
				)
			);
		}

		$code = wp_remote_retrieve_response_code( $response );
		$body = wp_remote_retrieve_body( $response );
		$data = json_decode( $body, true );

		// Handle non-200 responses, including quota exceeded (403).
		if ( 200 !== $code ) {
			$message = isset( $data['error']['message'] )
				? sanitize_text_field( $data['error']['message'] )
				: __( 'Unknown API error.', 'islami-dawa-tools' );

			return new \WP_Error(
				'islami_dawa_tools_api_error',
				sprintf(
					/* translators: 1: HTTP code, 2: error message */
					__( 'YouTube API error %1$d: %2$s', 'islami-dawa-tools' ),
					$code,
					$message
				)
			);
		}

		if ( null === $data ) {
			return new \WP_Error(
				'islami_dawa_tools_invalid_api_response',
				__( 'YouTube API returned an invalid response.', 'islami-dawa-tools' )
			);
		}

		return $data;
	}
}

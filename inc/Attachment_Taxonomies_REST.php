<?php
/**
 * Attachment_Taxonomies_REST class
 *
 * @package AttachmentTaxonomies
 * @author Felix Arntz <hello@felix-arntz.me>
 * @since 1.2.0
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

if ( class_exists( 'Attachment_Taxonomies_REST' ) ) {
	return;
}

/**
 * Modifies the core media REST controller to support assigning terms on an attachment.
 *
 * @since 1.2.0
 */
final class Attachment_Taxonomies_REST {

	/**
	 * Plugin core instance.
	 *
	 * @since 1.2.0
	 * @var Attachment_Taxonomies_Core
	 */
	private $core;

	/**
	 * Constructor.
	 *
	 * @since 1.2.0
	 *
	 * @param Attachment_Taxonomies_Core $core Plugin core instance.
	 */
	public function __construct( Attachment_Taxonomies_Core $core ) {
		$this->core = $core;
	}

	/**
	 * Modifies the REST permission check for an attachment when assiging terms is attempted.
	 *
	 * @since 1.2.0
	 *
	 * @param WP_REST_Response|WP_HTTP_Response|WP_Error|mixed $response Result to send to the client.
	 *                                                                   Usually a WP_REST_Response or WP_Error.
	 * @param array                                            $handler  Route handler used for the request.
	 * @param WP_REST_Request                                  $request  Request used to generate the response.
	 * @return WP_REST_Response|WP_HTTP_Response|WP_Error|mixed Unmodified $response, or WP_Error to force failure.
	 */
	public function fail_permission_check_if_cannot_assign_attachment_terms( $response, $handler, $request ) {
		if ( ! isset( $handler['permission_callback'] ) || ! is_array( $handler['permission_callback'] ) ) {
			return $response;
		}

		if (
			! $handler['permission_callback'][0] instanceof WP_REST_Attachments_Controller ||
			! in_array( $handler['permission_callback'][1], array( 'create_item_permissions_check', 'update_item_permissions_check' ), true )
		) {
			return $response;
		}

		$assign_terms_check = $this->check_assign_terms_permission( $request );
		if ( is_wp_error( $assign_terms_check ) ) {
			return $assign_terms_check;
		}

		return $response;
	}

	/**
	 * Handles REST logic for assigning terms to an attachment.
	 *
	 * @since 1.2.0
	 *
	 * @param WP_Post         $attachment Inserted or updated attachment object.
	 * @param WP_REST_Request $request    Request object.
	 */
	public function handle_attachment_terms( $attachment, $request ) {
		$this->handle_terms( $attachment->ID, $request );
	}

	/**
	 * Checks whether current user can assign all terms sent with the current request.
	 *
	 * This is almost an exact copy of the {@see WP_REST_Posts_Controller::check_assign_terms_permission()} method.
	 *
	 * @since 1.2.0
	 *
	 * @param WP_REST_Request $request The request object with post and terms data.
	 * @return bool|WP_Error True if the current user can assign the provided terms, WP_Error otherwise.
	 */
	private function check_assign_terms_permission( $request ) {
		$taxonomies = wp_list_filter( $this->core->get_all_taxonomies(), array( 'show_in_rest' => true ) );
		foreach ( $taxonomies as $taxonomy ) {
			$base = ! empty( $taxonomy->rest_base ) ? $taxonomy->rest_base : $taxonomy->name;

			if ( ! isset( $request[ $base ] ) ) {
				continue;
			}

			foreach ( (array) $request[ $base ] as $term_id ) {
				// Invalid terms will be rejected later.
				if ( ! get_term( $term_id, $taxonomy->name ) ) {
					return new WP_Error(
						'rest_invalid_term_id',
						__( 'Invalid term ID.', 'default' ),
						array( 'status' => 400 )
					);
				}

				if ( ! current_user_can( 'assign_term', (int) $term_id ) ) {
					return new WP_Error(
						'rest_cannot_assign_term',
						__( 'Sorry, you are not allowed to assign the provided terms.', 'default' ),
						array( 'status' => rest_authorization_required_code() )
					);
				}
			}
		}

		return true;
	}

	/**
	 * Updates the attachment's terms from a REST request.
	 *
	 * This is almost an exact copy of the {@see WP_REST_Posts_Controller::handle_terms()} method.
	 *
	 * @since 1.2.0
	 *
	 * @param int             $attachment_id The attachment ID to update the terms form.
	 * @param WP_REST_Request $request       The request object with attachment and terms data.
	 * @return null|WP_Error WP_Error on an error assigning any of the terms, otherwise null.
	 */
	private function handle_terms( $attachment_id, $request ) {
		$taxonomies = wp_list_filter( $this->core->get_all_taxonomies(), array( 'show_in_rest' => true ) );

		foreach ( $taxonomies as $taxonomy ) {
			$base = ! empty( $taxonomy->rest_base ) ? $taxonomy->rest_base : $taxonomy->name;

			if ( ! isset( $request[ $base ] ) ) {
				continue;
			}

			$result = wp_set_object_terms( $attachment_id, (array) $request[ $base ], $taxonomy->name );

			if ( is_wp_error( $result ) ) {
				return $result;
			}
		}

		return null;
	}
}

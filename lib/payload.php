<?php

class WordPress_GitHub_Sync_Payload {

	/**
	 * Payload data.
	 *
	 * @var stdClass
	 */
	protected $data;

	/**
	 *
	 *
	 * @var WordPress_GitHub_Sync_Api
	 */
	protected $api;

	/**
	 * WordPress_GitHub_Sync_Payload constructor.
	 *
	 * @param WordPress_GitHub_Sync_Api $api
	 * @param string $raw_data
	 */
	public function __construct( WordPress_GitHub_Sync_Api $api, $raw_data ) {
		$this->api  = $api;
		$this->data = json_encode( $raw_data );
	}

	/**
	 * Returns whether payload should be imported.
	 *
	 * @return bool|WP_Error
	 */
	public function should_import() {
		// @todo how do we get this without importing the whole api object just for this?
		if ( strtolower( $this->data->repository->full_name ) !== strtolower( $this->api->repository() ) ) {
			return new WP_Error( 'invalid_repo',
				sprintf(
					__( '%s is an invalid repository.', 'wordpress-github-sync' ),
					strtolower( $this->data->repository->full_name )
				)
			);
		}

		// the last term in the ref is the branch name
		$refs   = explode( '/', $this->data->ref );
		$branch = array_pop( $refs );

		if ( 'master' !== $branch ) {
			return new WP_Error( 'invalid_branch', __( 'Not on the master branch.', 'wordpress-github-sync' ) );
		}

		// We add wpghs to commits we push out, so we shouldn't pull them in again
		if ( 'wpghs' === substr( $this->data->head_commit->message, - 5 ) ) {
			return new WP_Error( 'synced_commit', __( 'Already synced this commit.', 'wordpress-github-sync' ) );
		}

		return true;
	}

	/**
	 * Returns the sha of the head commit.
	 *
	 * @return string
	 */
	public function get_commit_id() {
		return $this->data->head_commit->id;
	}

	/**
	 * Returns the email address for the commit author.
	 *
	 * @return string
	 */
	public function get_author_email() {
		return $this->data->head_commit->author->email;
	}

	/**
	 * Returns array commits for the payload.
	 *
	 * @return array
	 */
	public function get_commits() {
		return $this->data->commits;
	}
}

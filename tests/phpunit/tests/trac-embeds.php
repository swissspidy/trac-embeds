<?php

class Test_Trac_Embeds extends WP_UnitTestCase {
	/**
	 **
	 * @var WP_REST_Server
	 */
	protected $server;

	public function setUp() {
		parent::setUp();

		/** @var WP_REST_Server $wp_rest_server */
		global $wp_rest_server;
		$this->server = $wp_rest_server = new Spy_REST_Server();

		do_action( 'rest_api_init', $this->server );
	}

	public function test_plugin_is_activated() {
		$this->assertTrue( function_exists( 'trac_embeds_get_sites' ) );
	}

	public function test_trac_embeds_create_dummy_post() {
		trac_embeds_create_dummy_post();

		$this->assertNotEquals( 0, get_option( 'trac_embeds_post_id' ) );
	}

	public function test_trac_embeds_uninstall(  ) {
		trac_embeds_create_dummy_post();
		trac_embeds_uninstall();

		$this->assertFalse( get_option( 'trac_embeds_post_id' ) );
	}

	function test_request_without_dummy_post() {
		$request = new WP_REST_Request( 'GET', '/oembed/1.0/embed' );
		$request->set_param( 'url', 'https://core.trac.wordpress.org/ticket/40000' );

		$response = $this->server->dispatch( $request );
		$data     = $response->get_data();

		$this->assertEquals( 'oembed_invalid_url', $data['code'] );
	}

	public function test_request() {
		trac_embeds_activation();

		$request = new WP_REST_Request( 'GET', '/oembed/1.0/embed' );
		$request->set_param( 'url', 'https://core.trac.wordpress.org/ticket/40000' );
		$request->set_param( 'maxwidth', 400 );

		$response = $this->server->dispatch( $request );
		$data     = $response->get_data();

		$this->assertInternalType( 'array', $data );
		$this->assertNotEmpty( $data );

		$this->assertEquals( 'WordPress Core Trac', $data['provider_name'] );
		$this->assertEquals( 'https://core.trac.wordpress.org', $data['provider_url'] );
	}
}

<?php
/**
 * This file is part of Media Credit.
 *
 * Copyright 2013-2016 Peter Putzer.
 * Copyright 2010-2011 Scott Bressler.
 *
 * This program is free software; you can redistribute it and/or
 * modify it under the terms of the GNU General Public License,
 * version 2 as published by the Free Software Foundation.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program; if not, write to the Free Software
 * Foundation, Inc., 51 Franklin Street, Fifth Floor, Boston,
 * MA 02110-1301, USA.
 *
 * @link       https://mundschenk.at
 * @since      3.0.0
 *
 * @package    Media_Credit
 * @subpackage Media_Credit/includes
 */

/**
 * The core plugin class.
 *
 * This is used to define internationalization, admin-specific hooks, and
 * public-facing site hooks.
 *
 * Also maintains the unique identifier of this plugin as well as the current
 * version of the plugin.
 *
 * @since      3.0.0
 * @package    Media_Credit
 * @subpackage Media_Credit/includes
 * @author     Peter Putzer <github@mundschenk.at>
 */
class Media_Credit implements Media_Credit_Base {

	/**
	 * The loader that's responsible for maintaining and registering all hooks that power
	 * the plugin.
	 *
	 * @since    3.0.0
	 * @access   protected
	 * @var      Media_Credit_Loader    $loader    Maintains and registers all hooks for the plugin.
	 */
	protected $loader;

	/**
	 * The unique identifier of this plugin.
	 *
	 * @since    3.0.0
	 * @access   protected
	 * @var      string    $plugin_name    The string used to uniquely identify this plugin.
	 */
	protected $plugin_name;

	/**
	 * The basename of the plugin (i.e. the ouptut plugin_basename(__FILE__) for the main plugin file).
	 *
	 * @since    3.0.0
	 * @access   protected
	 * @var      string    $plugin_name    The string used to uniquely identify this plugin.
	 */
	protected $plugin_basename;

	/**
	 * The current version of the plugin.
	 *
	 * @since    3.0.0
	 * @access   protected
	 * @var      string    $version    The current version of the plugin.
	 */
	protected $version;

	/**
	 * Define the core functionality of the plugin.
	 *
	 * Set the plugin name and the plugin version that can be used throughout the plugin.
	 * Load the dependencies, define the locale, and set the hooks for the admin area and
	 * the public-facing side of the site.
	 *
	 * @since    3.0.0
	 *
	 * @param string $slug     The plugin slug.
	 * @param string $version  The plugin version string.
	 * @param string $basename The plugin basename.
	 */
	public function __construct( $slug, $version, $basename ) {

		$this->plugin_name     = $slug;
		$this->plugin_basename = $basename;
		$this->version         = $version;
		$this->loader          = new Media_Credit_Loader();

		$this->set_locale();
		$this->define_admin_hooks();
		$this->define_public_hooks();

	}

	/**
	 * Define the locale for this plugin for internationalization.
	 *
	 * Uses the Media_Credit_I18n class in order to set the domain and to register the hook
	 * with WordPress.
	 *
	 * @since    3.0.0
	 * @access   private
	 */
	private function set_locale() {

		$plugin_i18n = new Media_Credit_I18n();
		$plugin_i18n->set_domain( $this->get_plugin_name() );

		$this->loader->add_action( 'plugins_loaded', $plugin_i18n, 'load_plugin_textdomain' );

	}

	/**
	 * Register all of the hooks related to the admin area functionality
	 * of the plugin.
	 *
	 * @since    3.0.0
	 * @access   private
	 */
	private function define_admin_hooks() {
		$plugin_admin = new Media_Credit_Admin( $this->get_plugin_name(), $this->get_version() );

		// Action hooks.
		$this->loader->add_action( 'admin_enqueue_scripts', $plugin_admin, 'enqueue_styles' );
		$this->loader->add_action( 'admin_enqueue_scripts', $plugin_admin, 'enqueue_scripts' );
		$this->loader->add_action( 'wp_enqueue_editor',     $plugin_admin, 'enqueue_editor', 10, 1 );
		$this->loader->add_action( 'print_media_templates', $plugin_admin, 'image_properties_template' );
		$this->loader->add_action( 'print_media_templates', $plugin_admin, 'attachment_details_template' );
		$this->loader->add_action( 'admin_menu',            $plugin_admin, 'display_settings' );
		$this->loader->add_action( 'admin_init',            $plugin_admin, 'admin_init' );

		// AJAX actions.
		$this->loader->add_action( 'wp_ajax_update-media-credit-in-post-content', $plugin_admin, 'ajax_filter_content' );
		$this->loader->add_action( 'wp_ajax_save-attachment-media-credit',        $plugin_admin, 'ajax_save_attachment_media_credit' );

		// Filter hooks.
		$this->loader->add_filter( 'wp_prepare_attachment_for_js',                  $plugin_admin, 'prepare_attachment_media_credit_for_js', 10, 3 );
		$this->loader->add_filter( 'attachment_fields_to_edit',                     $plugin_admin, 'add_media_credit_fields',                10, 2 );
		$this->loader->add_filter( 'attachment_fields_to_save',                     $plugin_admin, 'save_media_credit_fields',               10, 2 );
		$this->loader->add_filter( 'image_send_to_editor',                          $plugin_admin, 'image_send_to_editor',                   10, 8 );
		$this->loader->add_filter( 'plugin_action_links_' . $this->plugin_basename, $plugin_admin, 'add_action_links',                       10, 1 );
	}

	/**
	 * Register all of the hooks related to the public-facing functionality
	 * of the plugin.
	 *
	 * @since    3.0.0
	 * @access   private
	 */
	private function define_public_hooks() {

		$plugin_public = new Media_Credit_Public( $this->get_plugin_name(), $this->get_version() );

		$this->loader->add_action( 'wp_enqueue_scripts', $plugin_public, 'enqueue_styles' );
		$this->loader->add_action( 'wp_enqueue_scripts', $plugin_public, 'enqueue_scripts' );

		add_shortcode( 'wp_caption',   array( $plugin_public, 'caption_shortcode' ) );
		add_shortcode( 'caption',      array( $plugin_public, 'caption_shortcode' ) );
		add_shortcode( 'media-credit', array( $plugin_public, 'media_credit_shortcode' ) );

		$options = get_option( self::OPTION );
		if ( ! empty( $options['credit_at_end'] ) ) {
			$this->loader->add_filter( 'the_content', $plugin_public, 'add_media_credits_to_end', 10, 1 );
		}

		// Post thumbnail credits.
		if ( ! empty( $options['post_thumbnail_credit'] ) ) {
			$this->loader->add_filter( 'post_thumbnail_html', $plugin_public, 'add_media_credit_to_post_thumbnail', 10, 5 );
		}
	}

	/**
	 * Run the loader to execute all of the hooks with WordPress.
	 *
	 * @since    3.0.0
	 */
	public function run() {
		$this->loader->run();
	}

	/**
	 * The name of the plugin used to uniquely identify it within the context of
	 * WordPress and to define internationalization functionality.
	 *
	 * @since     3.0.0
	 * @return    string    The name of the plugin.
	 */
	public function get_plugin_name() {
		return $this->plugin_name;
	}

	/**
	 * The reference to the class that orchestrates the hooks with the plugin.
	 *
	 * @since     3.0.0
	 * @return    Media_Credit_Loader    Orchestrates the hooks of the plugin.
	 */
	public function get_loader() {
		return $this->loader;
	}

	/**
	 * Retrieve the version number of the plugin.
	 *
	 * @since     3.0.0
	 * @return    string    The version number of the plugin.
	 */
	public function get_version() {
		return $this->version;
	}
}

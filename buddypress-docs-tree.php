<?php
/*
Plugin Name: BuddyPress Docs Tree
Plugin URI: http://github.com/johappel/buddypress-docs-tree
Description: Adds a jstree widget to BuddyPress-Docs component
Version: 0.1
Author: Joachim Happel
Author URI: http://joachim-happel.de
Text Domain: bp_docs_tree
Domain Path: /languages/
Licence: GPLv3
*/

class RW_BuddyPress_Docs_Tree{

    /**
     * Plugin version
     *
     * @var     string
     * @since   0.1
     * @access  public
     */
    static public $version = "0.1";
    /**
     * Singleton object holder
     *
     * @var     mixed
     * @since   0.1
     * @access  private
     */
    static private $instance = NULL;
    /**
     * @var     mixed
     * @since   0.1
     * @access  public
     */
    static public $plugin_name = NULL;
    /**
     * @var     mixed
     * @since   0.1
     * @access  public
     */
    static public $textdomain = NULL;
    /**
     * @var     mixed
     * @since   0.1
     * @access  public
     */
    static public $plugin_base_name = NULL;
    /**
     * @var     mixed
     * @since   0.1
     * @access  public
     */
    static public $plugin_url = NULL;
    /**
     * @var     string
     * @since   0.1
     * @access  public
     */
    static public $plugin_filename = __FILE__;
    /**
     * @var     string
     * @since   0.1
     * @access  public
     */
    static public $plugin_version = '';
    /**
     * Plugin constructor.
     *
     * @since   0.1
     * @access  public
     * @uses    plugin_basename
     */
    public function __construct () {
        // set the textdomain variable
        self::$textdomain = self::get_textdomain();
        // The Plugins Name
        self::$plugin_name = $this->get_plugin_header( 'Name' );
        // The Plugins Basename
        self::$plugin_base_name = plugin_basename( __FILE__ );
        // The Plugins Version
        self::$plugin_version = $this->get_plugin_header( 'Version' );
        // Load the textdomain
        $this->load_plugin_textdomain();

        require_once 'inc/RW_BuddyPress_Docs_Tree_Autoloader.php';
        RW_BuddyPress_Docs_Tree_Autoloader::register();

        // Add Filter & Actions
        //add_action( '', array( '', '' ) );
        //add_filter( '', array( '', '' ), 10, 2 );
        add_action( 'widgets_init', array( $this, 'load_widget'));

    }

    /**
     * Register a new widget
     * and creates
     *
     * @since   0.1
     * @access  public
     * @uses    RW_BuddyPress_Docs_Tree_Widget
     */
    public function load_widget(){
        if ( is_plugin_active( 'buddypress-docs/loader.php' ) ){
            register_widget( 'RW_BuddyPress_Docs_Tree_Widget' );
            RW_BuddyPress_Docs_Tree_Widget::create_bp_docs_sitebar();
        }
    }


    /**
     * Returns the absolute url of the directory of this plugin
     *
     * @since   0.1
     * @static
     * @access  public
     *
     * @uses plugins_url()
     *
     * @return string
     */
    static public function get_plugin_directory_url(){
        return plugins_url(false,self::$plugin_base_name).'/';
    }


    /**
     * Creates an Instance of this Class
     *
     * @since   0.1
     * @access  public
     * @return  RW_BuddyPress_Docs_Tree
     */
    public static function get_instance() {
        if ( NULL === self::$instance )
            self::$instance = new self;
        return self::$instance;
    }
    /**
     * Load the localization
     *
     * @since	0.1
     * @access	public
     * @uses	load_plugin_textdomain, plugin_basename
     * @filters rw_buddypress_docs_tree_translationpath path to translations files
     * @return	void
     */
    public function load_plugin_textdomain() {
        load_plugin_textdomain( self::get_textdomain(), FALSE,false, apply_filters ( 'rw_buddypress_docs_tree_translationpath', dirname( plugin_basename( __FILE__ )) .  self::get_textdomain_path() ) );
    }
    /**
     * Get a value of the plugin header
     *
     * @since   0.1
     * @access	protected
     * @param	string $value
     * @uses	get_plugin_data, ABSPATH
     * @return	string The plugin header value
     */
    protected function get_plugin_header( $value = 'TextDomain' ) {
        if ( ! function_exists( 'get_plugin_data' ) ) {
            require_once( ABSPATH . '/wp-admin/includes/plugin.php');
        }
        $plugin_data = get_plugin_data( __FILE__ );
        $plugin_value = $plugin_data[ $value ];
        return $plugin_value;
    }
    /**
     * get the textdomain
     *
     * @since   0.1
     * @static
     * @access	public
     * @return	string textdomain
     */
    public static function get_textdomain() {
        if( is_null( self::$textdomain ) )
            self::$textdomain = self::get_plugin_data( 'TextDomain' );
        return self::$textdomain;
    }
    /**
     * get the textdomain path
     *
     * @since   0.1
     * @static
     * @access	public
     * @return	string Domain Path
     */
    public static function get_textdomain_path() {
        return self::get_plugin_data( 'DomainPath' );
    }
    /**
     * return plugin comment data
     *
     * @since   0.1
     * @uses    get_plugin_data
     * @access  public
     * @param   $value string, default = 'Version'
     *		Name, PluginURI, Version, Description, Author, AuthorURI, TextDomain, DomainPath, Network, Title
     * @return  string
     */
    public static function get_plugin_data( $value = 'Version' ) {
        if ( ! function_exists( 'get_plugin_data' ) )
            require_once( ABSPATH . '/wp-admin/includes/plugin.php' );
        $plugin_data  = get_plugin_data ( __FILE__ );
        $plugin_value = $plugin_data[ $value ];
        return $plugin_value;
    }
    
    
    /**
     * Check some thinks on plugin activation
     *
     * @since   0.1
     * @access  public
     * @static
     * @return  void
     */
    public static function on_activate() {
        // check WordPress version
        if ( ! version_compare( $GLOBALS[ 'wp_version' ], '3.0', '>=' ) ) {
            deactivate_plugins( self::$plugin_filename );
            die(
            wp_sprintf(
                '<strong>%s:</strong> ' .
                __( 'This plugin requires WordPress 3.0 or newer to work', self::get_textdomain() )
                , self::get_plugin_data( 'Name' )
            )
            );
        }
        // check php version
        if ( version_compare( PHP_VERSION, '5.2.0', '<' ) ) {
            deactivate_plugins( self::$plugin_filename );
            die(
            wp_sprintf(
                '<strong>%1s:</strong> ' .
                __( 'This plugin requires PHP 5.2 or newer to work. Your current PHP version is %1s, please update.', self::get_textdomain() )
                , self::get_plugin_data( 'Name' ), PHP_VERSION
            )
            );
        }
    }
    /**
     * Clean up after uninstall
     *
     * Clean up after uninstall the plugin.
     * Delete options and other stuff.
     *
     * @since   0.1
     * @access  public
     * @static
     * @return  void
     *
     */
    public static function on_uninstall() {
    }


}

if ( class_exists( 'RW_BuddyPress_Docs_Tree' ) ) {
    add_action( 'plugins_loaded', array( 'RW_BuddyPress_Docs_Tree', 'get_instance' ) );
    register_activation_hook( __FILE__, array( 'RW_BuddyPress_Docs_Tree', 'on_activate' ) );
    register_uninstall_hook(  __FILE__,	array( 'RW_BuddyPress_Docs_Tree', 'on_uninstall' ) );
}
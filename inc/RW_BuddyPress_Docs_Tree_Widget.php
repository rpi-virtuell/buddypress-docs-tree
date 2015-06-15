<?php
/**
* Buddypress Docs Widget
* Description: displays a tree-view of the buddpress docs tom the loggedin user
* @autor: Joachim Happel
*/
class RW_BuddyPress_Docs_Tree_Widget extends WP_Widget {

    /**
     * @var     array
     * @since   0.1
     * @access  public
     */
    public $posts;

    /**
     * @var     int
     * @since   0.1
     * @access  public
     */
	public $group_id;

    /**
     * @var     srting
     * @since   0.1
     * @access  public
     */
	public $plugin_dir;

    /**
     * Constructor
     *
     * @since   0.1
     * @access public
     */
	public function __construct() {

        $this->plugin_dir= RW_BuddyPress_Docs_Tree::get_plugin_directory_url();

        parent::__construct(
			'RW_BuddyPress_Docs_Tree_Widget',
			__('Buddypress Docs Tree View', 'bp-docs-tree'),
			array( 'description' => __( 'Tree View of the Buddypress Docs', 'bp-docs-tree' ), )
		);


        /* use jQuery extension jsTree to draw the Docs Tree */
		
		add_action( 'wp_enqueue_scripts', array($this, 'load_jsTree') );
		add_action( 'wp_enqueue_scripts', array($this, 'load_jsTree_css') );
	}

    /**
     * javascript loader
     *
     * @access public
     * @since   0.1
     * @uses wp_enqueue_script
     */
    public function load_jsTree() {
		wp_enqueue_script(
			'custom-script',
            $this->plugin_dir . 'js/jstree.min.js',
			array( 'jquery' )
		);
	}

    /**
     * stylesheet loader
     *
     * @access public
     * @since   0.1
     * @uses wp_enqueue_style
     */
    public function load_jsTree_css() {
		wp_enqueue_style( 'jsTreeStyle',$this->plugin_dir . 'js/themes/default/style.min.css' );
		wp_enqueue_style( 'jsTreeCustomStyle',$this->plugin_dir . 'css/style.css' );
	}


    /**
     * figure out the current used buddypress group_id
     *
     * @since   0.1
     * @access public
     * @returns int  $group_id
     */
	public function bd_docs_get_current_group_id(){
			 
		$group_id=false;
		
			
		if( bp_docs_is_bp_docs_page() && NULL !== bp_docs_get_current_doc() )
		{
			$group_id = bp_docs_get_associated_group_id(get_the_ID() );
		}
		else
			
		{
			
			$path = ( $_SERVER['REQUEST_URI'] );
			$p_arr = explode('/', $path );
			if( isset($p_arr[1]) && $p_arr[1] == bp_get_groups_root_slug() ){
				$slug = $p_arr[2];
				$group_id = BP_Groups_Group::get_id_from_slug( $slug ) ;
			}else{
				$u = parse_url( wp_get_referer() );
				$path = $u['path'];
				$p_arr = explode('/', $path );
			
				if( isset($p_arr[1]) && $p_arr[1] == bp_get_groups_root_slug() ){
					$slug = $p_arr[2];
					$group_id = BP_Groups_Group::get_id_from_slug( $slug ) ;
				}
			}
			
			
			
		}
		return $group_id;
		 
	}

    /**
     * Get Posts hierachical to prepare tree view
     *
     * @since   0.1
     * @access  public
     * @return array posts
     */
	function get_posts()
	{
		global $bp;
		
		$array=array();
		
		$this->group_id = $this->bd_docs_get_current_group_id();
		
		 $qargs = array(
			'posts_per_page'   => -1,
			'paged'           => 0,
			'orderby'          => 'post_name',
			'order'            => 'ASC',
			'post_type'        => 'bp_doc',
			'post_status'      => 'publish',
			'suppress_filters' => true
		);
		
		if($this->group_id){			
			$qargs["tax_query"]=array(
				array(
					"taxonomy"=>"bp_docs_associated_item",
					"field"=>"slug",
					'terms'=>array( 'bp_docs_associated_group_'.$this->group_id )
				)
			) ;
		}else{
			$qargs['author'] = $bp->loggedin_user->id;
		}
				
		$posts = get_posts( $qargs );
		
		$this->posts = array();
		
		foreach ($posts as $post){
			if( $post->post_type==='bp_doc'){
				$array[$post->ID]=$post->post_parent;
				$this->posts[$post->ID]=$post;
			}
		}
		
		foreach ($array as $c => $p)  {
			if(!array_key_exists($p,$array)){
				$array[$c]=0;
			}
		}
		$tree = $this->to_tree($array);
		
		return $tree;
	}

    /**
     * array helper function
     *
     * @since   0.1
     * @access  private
     *
     * @param $array $posts
     * @return array $tree
     */
	
	private function to_tree($array)
	{
		
		$flat = array();
		$tree = array();
		foreach ($array as $child => $parent) {
			if (!isset($flat[$child])) {
				$flat[$child] = array();
			}
			if (!empty($parent)) {
				$flat[$parent][$child] =& $flat[$child];
			} else {
				$tree[$child] =& $flat[$child];
			}
		
		}
		return $tree;
	}

    /**
     * Display the Doc tree
     *
     * @since   0.1
     * @access  public
     *
     * @param array $tree
     */

	public function print_tree($tree){
		echo '<ul>';
		foreach($tree as $parent=>$child){
			echo '<li class="jstree-ocl">';
			
			if($parent!='root'){
				$post=$this->posts[$parent];
				echo '<a title="'.$post->post_title.'" href="/'.bp_docs_get_slug().'/'.$post->post_name.'">'.$post->post_title.'</a>';
			}else{
				if($child!='root'){
					echo serialize($child);
				}
			}
			if(count($child)>0){
				$this->print_tree($child);
			}
			echo '</li>';
		}
		echo '</ul>';
	}

    /**
     * Displays the widget
     *
     * @since   0.1
     * @access  public
     *
     * @used by register_widget
     *
     * @param array $args
     * @param array $instance
     */
	public function widget( $args, $instance ) {
		
		$title = apply_filters( 'widget_title', $instance['title'] );
		// before and after widget arguments are defined by themes
		echo $args['before_widget'];
		if ( ! empty( $title ) )
			echo $args['before_title'] . $title . $args['after_title'];

		$tree = $this->get_posts() ;
		
		if($this->group_id){
			$group = groups_get_group( array( 'group_id' => $this->group_id ) );
			echo '<h4>in: '.$group->name.'</h4>';
		}else{
			echo '<h4>Meine Dokumente</h4>';
		}
		echo '<div id="docs-tree">';
		
		$this->print_tree($tree);
		
		echo '</div>';
	
		echo $args['after_widget'];
		
		?>
		<script>
			 jQuery(function () {
				//create an jsTree instance when the DOM is ready
				jQuery('#docs-tree').jstree();
				jQuery("#docs-tree").jstree(true).deselect_all();
				jQuery("#docs-tree").jstree(true).open_all();
				jQuery("#docs-tree").jstree(true).select_node(
					jQuery("#docs-tree a[href|='"+location.href+"']").parent()
				);
				
				jQuery('#docs-tree').on("select_node.jstree", function (e, data) {
					
					jQuery('a').removeClass('jstree-short-title');
					
					var href = $('#'+data.selected+' a')[0].href;
					
					jQuery('.show_bp_doc_link').remove();
					
					jQuery('#'+data.selected + ' > a').addClass('jstree-short-title');
					jQuery('#'+data.selected).append(' &nbsp; <a title="<?php _e('Display', 'bp-docs-tree');?>" class="show_bp_doc_link fa fa-external-link fa-2x" href="'+href+'"></a>');
					
				});
			});
			
		</script>
		<?php
	}
			
	/**
     * Widget Backend
     * Displays the Widget Config Form in Adminview
     *
     * @since   0.1
     * @access  public
     * @used by register_widget
     *
     */
	public function form( $instance ) {
		if ( isset( $instance[ 'title' ] ) ) {
			$title = $instance[ 'title' ];
		}
		else {
			$title = __( 'Docs', 'boss' );
		}
		// Widget admin form
		?>
		<p>
		<label for="<?php echo $this->get_field_id( 'title' ); ?>"><?php _e( 'Title:' ); ?></label> 
		<input class="widefat" id="<?php echo $this->get_field_id( 'title' ); ?>" name="<?php echo $this->get_field_name( 'title' ); ?>" type="text" value="<?php echo esc_attr( $title ); ?>" />
		</p>
		<?php 
	}

    /**
     * Widget Backend
     * Updating widget replacing old instances with new
     *
     * @since   0.1
     * @access  public
     * @used by register_widget
     *
     */
	public function update( $new_instance, $old_instance ) {
		$instance = array();
		$instance['title'] = ( ! empty( $new_instance['title'] ) ) ? strip_tags( $new_instance['title'] ) : '';
		return $instance;
	}

    /**
     * register a new sidebar for the Docs Template
     *
     * @static
     * @since   0.1
     * @access  public

     */
    static function create_bp_docs_sitebar(){


        register_sidebar( array(
            'name' => __( 'Docs', 'bp-docs' ),
            'id' => 'docs',
            'description' => __( 'Widgets in this area will be shown on buddypress docs pages.', 'bp-docs-tree' ),
            'before_widget' => '<li id="%1$s" class="widget %2$s">',
            'after_widget'  => '</li>',
            'before_title'  => '<h3 class="widgettitle">',
            'after_title'   => '</h3>',
            )
        );

    }

    /**
     * checks, whether the current page contains buddypress docs contents
     *
     * @static
     * @since   0.1
     * @access  public
     *
     * @returns bool
     */
	static function is_bp_doc_page(){
		
		$dirs = explode ('/', $_SERVER['REQUEST_URI'] );
		$slug = isset( $dirs[1] ) ? $dirs[1] : '';
		
		if( $slug == bp_docs_get_slug()){
			return true;
		}
		if(!bp_is_group() && !bp_is_my_profile() && get_post_type() == 'bp_doc') 
		{
			return true;
		}
		return false;
	}


    /**
     * checks, whether the current page is in desktop view by checking the $_COOKIE['switch_mode']
     *
     * @static
     * @since   0.1
     * @access  public
     *
     * @used by boss theme
     * @ToDo move functions to extended Class
     *
     * @returns bool
     */
	static function is_desktop(){
		return !self::is_mobile();
	}

    /**
     * checks, whether the current page is in mobile view by checking the $_COOKIE['switch_mode']
     *
     * @static
     * @since   0.1
     * @access  public
     *
     * @used by boss theme
     * @ToDo move functions to extended Class
     *
     * @returns bool
     */
	static function is_mobile(){
		 if(isset ($_COOKIE['switch_mode']) && $_COOKIE['switch_mode']=='mobile'){
			return true;
		 }elseif (wp_is_mobile() ){
			return true;
		}
		return false;
	}
} // Class RW_BuddyPress_Docs_Tree_Widget ends here

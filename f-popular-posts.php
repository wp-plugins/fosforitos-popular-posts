<?php
/*
Plugin Name: Fosforito's Popular Posts
Plugin URI: https://www.fosforito.net/fpp
Description: With this plugin you will be able to display your most popular posts based on comments or views in a widget.
Author: Fosforito Media
Version: 1.0.2
Author URI: https://www.fosforito.net/
License: GPLv3 or later
*/

/* Plugin initialization - add internationalization and size for image*/
if ( ! function_exists ( 'pplrpsts_init' ) ) {
	function pplrpsts_init() {
		global $pplrpsts_plugin_info;	
		/* Internationalization, first(!) */
		load_plugin_textdomain( 'popular_posts', false, dirname( plugin_basename( __FILE__ ) ) . '/languages/' ); 
		
		if ( empty( $pplrpsts_plugin_info ) ) {
			if ( ! function_exists( 'get_plugin_data' ) )
				require_once( ABSPATH . 'wp-admin/includes/plugin.php' );
			$pplrpsts_plugin_info = get_plugin_data( __FILE__ );
		}

		if ( ! session_id() )
			@session_start();
		
		add_image_size( 'popular-post-featured-image', 60, 60, true );
	}
}

/* Plugin initialization for admin page */
if ( ! function_exists ( 'pplrpsts_admin_init' ) ) {
	function pplrpsts_admin_init() {
		global $f_plugin_info, $pplrpsts_plugin_info, $pagenow;
		
		if ( ! isset( $f_plugin_info ) || empty( $f_plugin_info ) )
			$f_plugin_info = array( 'id' => '177', 'version' => $pplrpsts_plugin_info["Version"] );

		/* Call register settings function */
		if ( 'widgets.php' == $pagenow )
			pplrpsts_set_options();
	}
}

/* Setting options */
if ( ! function_exists( 'pplrpsts_set_options' ) ) {
	function pplrpsts_set_options() {
		global $pplrpsts_options, $pplrpsts_plugin_info;

		$pplrpsts_options_defaults	=	array(
			'plugin_option_version'	=>	$pplrpsts_plugin_info["Version"],			
			'widget_title'			=>	__( 'Popular Posts', 'popular_posts' ),
			'count'					=>	'5',
			'excerpt_length'		=>	'10',
			'excerpt_more'			=>	'...',
			'no_preview_img'		=>	plugins_url( 'images/default.png', __FILE__ ),
			'order_by'				=>	'views_count',
			'display_excerpt'		=>	'true'
		);

		if ( ! get_option( 'pplrpsts_options' ) )
			add_option( 'pplrpsts_options', $pplrpsts_options_defaults );

		$pplrpsts_options = get_option( 'pplrpsts_options' );

		/* Array merge incase this version has added new options */
		if ( ! isset( $pplrpsts_options['plugin_option_version'] ) || $pplrpsts_options['plugin_option_version'] != $pplrpsts_plugin_info["Version"] ) {
			$pplrpsts_options = array_merge( $pplrpsts_options_defaults, $pplrpsts_options );
			$pplrpsts_options['plugin_option_version'] = $pplrpsts_plugin_info["Version"];
			update_option( 'pplrpsts_options', $pplrpsts_options );
		}
	}
}

/* Create widget for plugin */
if ( ! class_exists( 'PopularPosts' ) ) {
	class PopularPosts extends WP_Widget {

		function PopularPosts() {
			/* Instantiate the parent object */
			parent::__construct( 
				'pplrpsts_popular_posts_widget', 
				__( 'Fosforito\'s Popular Posts', 'popular_posts' ),
				array( 'description' => __( 'Widget for displaying Popular Posts by comments or views count.', 'popular_posts' ) )
			);
		}
		
		/* Outputs the content of the widget */
		function widget( $args, $instance ) {
			global $post, $pplrpsts_excerpt_length, $pplrpsts_excerpt_more, $pplrpsts_options;
			if ( empty( $pplrpsts_options ) )
				$pplrpsts_options = get_option( 'pplrpsts_options' );
			$widget_title     	= isset( $instance['widget_title'] ) ? $instance['widget_title'] : $pplrpsts_options['widget_title'];
			$count            	= isset( $instance['count'] ) ? $instance['count'] : $pplrpsts_options['count'];
			$excerpt_length 	= $pplrpsts_excerpt_length = isset( $instance['excerpt_length'] ) ? $instance['excerpt_length'] : $pplrpsts_options['excerpt_length'];
			$excerpt_more 		= $pplrpsts_excerpt_more = isset( $instance['excerpt_more'] ) ? $instance['excerpt_more'] : $pplrpsts_options['excerpt_more']; 
			$no_preview_img		= isset( $instance['no_preview_img'] ) ? $instance['no_preview_img'] : $pplrpsts_options['no_preview_img'];
			$order_by			= isset( $instance['order_by'] ) ? $instance['order_by'] : $pplrpsts_options['order_by'];
			$display_excerpt	= isset( $instance['display_excerpt'] ) ? $instance['display_excerpt'] : $pplrpsts_options['display_excerpt'];
			echo $args['before_widget'];
			if ( ! empty( $widget_title ) ) { 
				echo $args['before_title'] . $widget_title . $args['after_title'];
			} ?>
			<div class="pplrpsts-popular-posts">
				<?php if ( 'comment_count' == $order_by )
					$query_args = array(
						'post_type'				=> 'post',
						'orderby'				=> 'comment_count',
						'order'					=> 'DESC',
						'posts_per_page'		=> $count,
						'ignore_sticky_posts' 	=> 1
					);
				else
					$query_args = array(
						'post_type'				=> 'post',
						'meta_key'				=> 'pplrpsts_post_views_count',
						'orderby'				=> 'meta_value_num',
						'order'					=> 'DESC',
						'posts_per_page'		=> $count,
						'ignore_sticky_posts' 	=> 1
					);

				if ( ! function_exists ( 'is_plugin_active' ) ) 
					include_once( ABSPATH . 'wp-admin/includes/plugin.php' );

				$the_query = new WP_Query( $query_args );
				/* The Loop */
				if ( $the_query->have_posts() ) { 
					add_filter( 'excerpt_length', 'pplrpsts_popular_posts_excerpt_length' );
					add_filter( 'excerpt_more', 'pplrpsts_popular_posts_excerpt_more' );
					$cnt_helper = 0;
					while ( $the_query->have_posts() ) {
						$cnt_helper++;
						$the_query->the_post(); ?>
						<article class="fpp-post">
							<div class="fpp-content <?php if ($cnt_helper < $count ) { echo 'dotted'; } ?> ">
								<a href="<?php the_permalink(); ?>" title="<?php the_title(); ?>">
									<?php if ( '' == get_the_post_thumbnail() ) { ?>
										<img width="70" height="70" class="fpp-thumbnail" src="<?php echo $no_preview_img; ?>" />
									<?php } else {
										$check_size = wp_get_attachment_image_src( get_post_thumbnail_id( $post->ID ), 'popular-post-featured-image' );
										if ( true === $check_size[3] )
											echo get_the_post_thumbnail( $post->ID, 'popular-post-featured-image' ); 
										else
											echo get_the_post_thumbnail( $post->ID, array( 70, 70 ) ); 
									} ?>
								</a>
								<h1><a href="<?php the_permalink(); ?>"><?php the_title(); ?></a></h1>
								<?php if ( $display_excerpt == "true" ) { the_excerpt(); } ?>
							</div><!-- .entry-content -->
						</article><!-- .post -->
					<?php }
					remove_filter( 'excerpt_length', 'pplrpsts_popular_posts_excerpt_length' );
					remove_filter( 'excerpt_more', 'pplrpsts_popular_posts_excerpt_more' );
				} else {
					/* no posts found */
				} ?>

			</div><!-- .pplrpsts-popular-posts -->
			<?php echo $args['after_widget'];
		}
		
		/* Outputs the options form on admin */
		function form( $instance ) {
			global $pplrpsts_excerpt_length, $pplrpsts_excerpt_more, $pplrpsts_options;
			if ( empty( $pplrpsts_options ) )
				$pplrpsts_options = get_option( 'pplrpsts_options' );
			$widget_title		= isset( $instance['widget_title'] ) ? $instance['widget_title'] : $pplrpsts_options['widget_title']; 
			$count				= isset( $instance['count'] ) ? $instance['count'] : $pplrpsts_options['count'];
			$excerpt_length 	= $pplrpsts_excerpt_length = isset( $instance['excerpt_length'] ) ? $instance['excerpt_length'] : $pplrpsts_options['excerpt_length'];
			$excerpt_more 		= $pplrpsts_excerpt_more = isset( $instance['excerpt_more'] ) ? $instance['excerpt_more'] : $pplrpsts_options['excerpt_more'];
			$no_preview_img 	= isset( $instance['no_preview_img'] ) ? $instance['no_preview_img'] : $pplrpsts_options['no_preview_img'];
			$order_by			= isset( $instance['order_by'] ) ? $instance['order_by'] : $pplrpsts_options['order_by'];
			$display_excerpt	= isset( $instance['display_excerpt'] ) ? $instance['display_excerpt'] : $pplrpsts_options['display_excerpt'];
			?>
			<p>
				<label for="<?php echo $this->get_field_id( 'widget_title' ); ?>"><?php _e( 'Widget title', 'popular_posts' ); ?>: </label>
				<input class="widefat" id="<?php echo $this->get_field_id( 'widget_title' ); ?>" name="<?php echo $this->get_field_name( 'widget_title' ); ?>" type="text" value="<?php echo esc_attr( $widget_title ); ?>"/>
			</p>
			<p>
				<label for="<?php echo $this->get_field_id( 'count' ); ?>"><?php _e( 'Number of posts', 'popular_posts' ); ?>: </label>
				<input class="widefat" id="<?php echo $this->get_field_id( 'count' ); ?>" name="<?php echo $this->get_field_name( 'count' ); ?>" type="text" value="<?php echo esc_attr( $count ); ?>"/>
			</p>
			<p>
				<label for="<?php echo $this->get_field_id( 'excerpt_length' ); ?>"><?php _e( 'Excerpt length', 'popular_posts' ); ?>: </label>
				<input class="widefat" id="<?php echo $this->get_field_id( 'excerpt_length' ); ?>" name="<?php echo $this->get_field_name( 'excerpt_length' ); ?>" type="text" value="<?php echo esc_attr( $excerpt_length ); ?>"/>
			</p>
			<p>
				<label for="<?php echo $this->get_field_id( 'excerpt_more' ); ?>"><?php _e( '"Read more" text', 'popular_posts' ); ?>: </label>
				<input class="widefat" id="<?php echo $this->get_field_id( 'excerpt_more' ); ?>" name="<?php echo $this->get_field_name( 'excerpt_more' ); ?>" type="text" value="<?php echo esc_attr( $excerpt_more ); ?>"/>
			</p>
			<p>
				<label for="<?php echo $this->get_field_id( 'no_preview_img' ); ?>"><?php _e( 'Default image (full URL), if no featured image is available', 'popular_posts' ); ?>: </label>
				<input class="widefat" id="<?php echo $this->get_field_id( 'no_preview_img' ); ?>" name="<?php echo $this->get_field_name( 'no_preview_img' ); ?>" type="text" value="<?php echo esc_attr( $no_preview_img ); ?>"/>
			</p>
			<p>
				<?php _e( 'Order by number of', 'popular_posts' ); ?>:<br />
				<label><input name="<?php echo $this->get_field_name( 'order_by' ); ?>" type="radio" value="comment_count" <?php if( 'comment_count' == esc_attr( $order_by ) ) echo 'checked="checked"'; ?> /> <?php _e( 'comments', 'popular_posts' ); ?></label><br />
				<label><input name="<?php echo $this->get_field_name( 'order_by' ); ?>" type="radio" value="views_count" <?php if( 'views_count' == esc_attr( $order_by ) ) echo 'checked="checked"'; ?> /> <?php _e( 'views', 'popular_posts' ); ?></label>
			</p>
			<p>
				<?php _e( 'Display Post Excerpts?', 'popular_posts' ); ?><br />
				<label><input name="<?php echo $this->get_field_name( 'display_excerpt' ); ?>" type="radio" value="true" <?php if( 'true' == esc_attr( $display_excerpt ) ) echo 'checked="checked"'; ?> /> <?php _e( 'Yes', 'popular_posts' ); ?></label><br />
				<label><input name="<?php echo $this->get_field_name( 'display_excerpt' ); ?>" type="radio" value="false" <?php if( 'false' == esc_attr( $display_excerpt ) ) echo 'checked="checked"'; ?> /> <?php _e( 'No', 'popular_posts' ); ?></label>
			</p>
		<?php }
		
		/* Processing widget options on save */
		function update( $new_instance, $old_instance ) {
			global $pplrpsts_options;
			if ( empty( $pplrpsts_options ) )
				$pplrpsts_options = get_option( 'pplrpsts_options' );
			$instance = array();
			$instance['widget_title']		= ( isset( $new_instance['widget_title'] ) ) ? stripslashes( esc_html( $new_instance['widget_title'] ) ) : $pplrpsts_options['widget_title'];
			$instance['count']				= ( ! empty( $new_instance['count'] ) ) ? intval( $new_instance['count'] ) : $pplrpsts_options['count'];
			$instance['excerpt_length']		= ( ! empty( $new_instance['excerpt_length'] ) ) ? stripslashes( esc_html( $new_instance['excerpt_length'] ) ) : $pplrpsts_options['excerpt_length'];
			$instance['excerpt_more']		= ( ! empty( $new_instance['excerpt_more'] ) ) ? stripslashes( esc_html( $new_instance['excerpt_more'] ) ) : $pplrpsts_options['excerpt_more'];
			if ( ! empty( $new_instance['no_preview_img'] ) && pplrpsts_is_200( $new_instance['no_preview_img'] ) && getimagesize( $new_instance['no_preview_img'] ) )
				$instance['no_preview_img'] = $new_instance['no_preview_img'];
			else
				$instance['no_preview_img'] = $pplrpsts_options['no_preview_img'];
			$instance['order_by'] 			= ( ! empty( $new_instance['order_by'] ) ) ? $new_instance['order_by'] : $pplrpsts_options['order_by'];
			$instance['display_excerpt']	= ( ! empty( $new_instance['display_excerpt'] ) ) ? $new_instance['display_excerpt'] : $pplrpsts_options['display_excerpt'];
			return $instance;
		}
	}
}

/* Filter the number of words in an excerpt */
if ( ! function_exists ( 'pplrpsts_popular_posts_excerpt_length' ) ) {
	function pplrpsts_popular_posts_excerpt_length( $length ) {
		global $pplrpsts_excerpt_length;
		return $pplrpsts_excerpt_length;
	}
}

/* Filter the string in the "more" link displayed after a trimmed excerpt */
if ( ! function_exists ( 'pplrpsts_popular_posts_excerpt_more' ) ) {
	function pplrpsts_popular_posts_excerpt_more( $more ) {
		global $pplrpsts_excerpt_more;
		return $pplrpsts_excerpt_more;
	}
}

/* Proper way to enqueue scripts and styles */
if ( ! function_exists ( 'pplrpsts_wp_head' ) ) {
	function pplrpsts_wp_head() {
		wp_enqueue_style( 'pplrpsts_stylesheet', plugins_url( 'css/style.css', __FILE__ ) );
	}
}

/* Add custom links for plugin in the Plugins list table */
if ( ! function_exists ( 'pplrpsts_register_plugin_links' ) ) {
	function pplrpsts_register_plugin_links( $links, $file ) {
		$base = plugin_basename(__FILE__);
		if ( $file == $base ) {
			if ( ! is_network_admin() )
			/* Reference: more Links can be added with "$links[] = ''" */
			$links[] = '<a href="http://support.bestwebsoft.com">' . __( 'Support','popular_posts' ) . '</a>';
		}
		return $links;
	}
}

/* Register a widget */
if ( ! function_exists ( 'pplrpsts_register_widgets' ) ) {
	function pplrpsts_register_widgets() {
		register_widget( 'PopularPosts' );
	}
}

/* Function to gather information about viewing posts */
if ( ! function_exists ( 'pplrpsts_set_post_views' ) ) {
	function pplrpsts_set_post_views( $pplrpsts_post_ID ) {
		global $post;

		if ( empty( $pplrpsts_post_ID ) && ! empty( $post ) ) {
			$pplrpsts_post_ID = $post->ID;
		}
		
		/* Check post type */
		if ( @get_post_type( $pplrpsts_post_ID ) != 'post' )
			return;

		$pplrpsts_count = get_post_meta( $pplrpsts_post_ID, 'pplrpsts_post_views_count', true );
		if ( $pplrpsts_count == '' ) {
			delete_post_meta( $pplrpsts_post_ID, 'pplrpsts_post_views_count' );
			add_post_meta( $pplrpsts_post_ID, 'pplrpsts_post_views_count', '1' );
		} else {
			$pplrpsts_count++;
			update_post_meta( $pplrpsts_post_ID, 'pplrpsts_post_views_count', $pplrpsts_count );
		}
	}
}

/* Check if image status = 200 */
if ( ! function_exists ( 'pplrpsts_is_200' ) ) {
	function pplrpsts_is_200( $url ) {
		if ( filter_var( $url, FILTER_VALIDATE_URL ) === FALSE )
			return false;

		$options['http'] = array(
				'method' => "HEAD",
				'ignore_errors' => 1,
				'max_redirects' => 0
		);
		$body = file_get_contents( $url, NULL, stream_context_create( $options ) );
		sscanf( $http_response_header[0], 'HTTP/%*d.%*d %d', $code );
		return $code === 200;
	}
}

/**
 * Delete plugin options
 */
if ( ! function_exists( 'pplrpsts_plugin_uninstall' ) ) {
	function pplrpsts_plugin_uninstall() {
		delete_option( 'pplrpsts_options' );
	}
}

/* Plugin initialization */
add_action( 'init', 'pplrpsts_init' );
/* Register a widget */
add_action( 'widgets_init', 'pplrpsts_register_widgets' );
/* Plugin initialization for admin page */
add_action( 'admin_init', 'pplrpsts_admin_init' );

add_action( 'wp_enqueue_scripts', 'pplrpsts_wp_head' );

/* Function to gather information about viewing posts */
add_action( 'wp_head', 'pplrpsts_set_post_views' );

register_uninstall_hook( __FILE__, 'pplrpsts_plugin_uninstall' );

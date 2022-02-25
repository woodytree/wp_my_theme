<?php
/*
Template Name: blackjack
*/
?>

<?php

if ( !is_user_logged_in() ) {
	auth_redirect();
}

// this is to load blackjack scripts
function blackjack_scripts() {
	
	wp_enqueue_script('jquery');
	
	wp_register_script( 'blackjack', get_stylesheet_directory_uri() .'/assets/js/blackjack.js', array('jquery') ); // register script but not enqueue it yet
	
	wp_localize_script( 'blackjack', 'blackjack_params', array( // pass php parameters to script
		'ajaxurl' => admin_url( 'admin-ajax.php' ) // wordpress ajax url
	) );
	
	wp_enqueue_script( 'blackjack' );
	
}
add_action( 'wp_enqueue_scripts', 'blackjack_scripts' );

?>

<?php get_header(); ?>

<style>

#blackjack h2,
#blackjack h3,
#blackjack table tr,
#blackjack table td {
	border: none;
    text-align: center;
}

#blackjack input {
    margin-bottom: 10px;
}

#blackjack img {
    display: inline;
}

#blackjack .img {
	line-height: 0;
}

#blackjack .btn {
	-moz-border-radius: 10px;
	-webkit-border-radius: 10px;
	background: #f6f6f6;
	border: 1px solid #ccc;
	border-radius: 10px;
	color: #1c94c4;
	cursor: pointer;
	font-weight: 700;
	padding: 3px 10px;
}

#blackjack .btn:hover {
	background: #fdf5ce;
	border: 1px solid orange;
	color: #c77405;
}

#blackjack .btn:active {
	background: #fff;
	border: 1px solid orange;
	color: orange;
}

</style>

<div class="main-content clear-fix<?php echo esc_attr(bard_options( 'general_content_width' )) === 'boxed' ? ' boxed-wrapper': ''; ?>" data-sidebar-sticky="<?php echo esc_attr( bard_options( 'general_sidebar_sticky' )  ); ?>">
	
	<?php
	
	// Sidebar Left
	get_template_part( 'templates/sidebars/sidebar', 'left' ); 

	?>

	<!-- Main Container -->
	<div class="main-container">
		
		<?php
		
		echo('<div id=\'blackjack\'>');
		
		do_action( 'blackjack_start' );
		
		echo('</div>');
		
		?>

	</div><!-- .main-container -->

	<?php
	
	// Sidebar Right
	get_template_part( 'templates/sidebars/sidebar', 'right' ); 

	?>

</div><!-- .page-content -->

<?php get_footer(); ?>
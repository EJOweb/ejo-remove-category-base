<?php

/**
 * General approach for removing `category_base` from permalink
 *
 * Does only work if category has `front`
 * Otherwise, use one of the 'remove category slug' plugins
 * They generate new rewrite rules for each category
 *
 * Bit complex solution because this reorganizes the rewrite rules
 * Maybe just add category-names to the rewrite rules. This is a 
 * bit ugly, but a lot more effective I guess.
 */
add_action( 'init', 'ejo_remove_category_base' );

function ejo_remove_category_base()
{
	global $wp_rewrite;

	/* Get `front` of permalink */
	$front = ejo_get_front();

    /* Abort if no `front` */
	if ( !$front )
		return;

	/* Remove `category` from permalink but keep `front` */
	$wp_rewrite->extra_permastructs['category']['struct'] = $front . '/%category%';
	
	/* Remove Category and Page rewrite rules */
	add_filter( 'category_rewrite_rules', 'ejo_remove_rewrite_rules' );
	add_filter( 'page_rewrite_rules',     'ejo_remove_rewrite_rules' );

	/* Add category-paging rewrite-rule to top of post-rewrite-rules */
	add_filter( 'post_rewrite_rules', 'ejo_add_post_rewrite_rules' );

	/* Add page rewrite-rules to bottom */
	add_filter( 'rewrite_rules_array', 'ejo_add_page_rewrite_rules_to_bottom' );
}

/**
 * Get `front` of permalink_structure 
 *
 * @return 	String `front` or bool False
 */
function ejo_get_front()
{
	$permalink_structure = get_option('permalink_structure');

	/**
	 * Get `front` of permalink_structure (everything up to the first rewrite tag).
	 * Example: Get `blog` from `/blog/%category%/%postname%/`
	 */
	$front = substr($permalink_structure, 0, strpos($permalink_structure, '%'));
	$front = (!empty($front)) ? trim($front, '/') : false;

	return (!empty($front)) ? $front : false;
}

/**
 * General function to remove rewrite-rules using a rewrite-filter
 */
function ejo_remove_rewrite_rules( $rewrite_rules ) 
{
	global $wp_rewrite;
	
	$rewrite_rules = array();

	return $rewrite_rules;
}

/**
 * Add Paging rewrite rule for post-categories and post-archive to post-rewrite-rules
 */
function ejo_add_post_rewrite_rules( $post_rewrite_rules )
{
	global $wp_rewrite;

	/* Get front */
	$front = ejo_get_front();

    /* Abort if no front */
	if ( !$front )
		return;

	/**
	 * Add rewrite rule for category paging to top of post-rewrite-rules to solve paging 404
	 *
	 * Possible conflict with multipage posts because it precedes:
	 *
	 * `front`/(.+?)/([^/]+)/page/?([0-9]{1,})/?$
	 * index.php?category_name=$matches[1]&name=$matches[2]&paged=$matches[3]
	 */
	$post_category_paging_rewrite_rule = array(
		"$front/(.+?)/page/?([0-9]{1,})/?$" => 'index.php?category_name=$matches[1]&paged=$matches[2]'
	);

	/**
	 * Fix post-archive paging
	 *
	 * Because 'post' is an opinionated build-in post type rewriting it to 
	 * match the post_type archive doesn't work well. Instead rewrite to 
	 * match the selected `front` page.
	 */
	$post_archive_paging_rewrite_rule = array(
		// "$front/page/?([0-9]{1,})/?$"] => 'index.php?post_type=post&paged=$matches[1]'
		"$front/page/?([0-9]{1,})/?$" => 'index.php?pagename='.$front.'&paged=$matches[1]'
	);

	/* Merge and return */
	return array_merge($post_category_paging_rewrite_rule, $post_archive_paging_rewrite_rule, $post_rewrite_rules);
}

/**
 * Add Page rewrite rules to bottom
 */
function ejo_add_page_rewrite_rules_to_bottom( $rewrite_rules ) 
{
	global $wp_rewrite;

	$front = ejo_get_front();

    /* Abort if no front */
	if ( !$front )
		return;

	$page_rewrite_rules = $wp_rewrite->page_rewrite_rules();

	return array_merge( $rewrite_rules, $page_rewrite_rules );
}









/**
 * Brainstorm / Old functions
 */

// function ejo_add_rewrite_rules_to_bottom( $rewrite_rules ) 
// {
// 	global $wp_rewrite;

// 	$front = ejo_get_front();

//     /* Abort if no front */
// 	if ( !$front )
// 		return;

// 	/**
// 	 * Get Category Rewrite Structure and mix with defaults 
// 	 *
// 	 * The defaults are from class-wp-rewrite.php: add_permastruct function
// 	 */
// 	$category_rewrite_struct = get_taxonomy( 'category' )->rewrite;

// 	$default_rewrite_struct = array(
// 		'with_front' => true,
// 		'ep_mask' => EP_NONE,
// 		'paged' => true,
// 		'feed' => true,
// 		'forcomments' => false,
// 		'walk_dirs' => true,
// 		'endpoints' => true,
// 	);

//     $category_struct = wp_parse_args( $category_rewrite_struct, $default_rewrite_struct );

//     /* Generate rewrite structure for category */
// 	$category_rewrite_rules = $wp_rewrite->generate_rewrite_rules( 
// 		"$front/%category%",
// 		$category_struct['ep_mask'], 
// 		$category_struct['paged'], 
// 		$category_struct['feed'], 
// 		$category_struct['forcomments'], 
// 		$category_struct['walk_dirs'], 
// 		$category_struct['endpoints'] 
// 	);

// 	$page_rewrite_rules = $wp_rewrite->page_rewrite_rules();

// 	$page_and_category_rewrite_rules = array_merge($category_rewrite_rules, $page_rewrite_rules);

// 	return array_merge( $rewrite_rules, $page_and_category_rewrite_rules );
// }

// function ejo_add_post_rewrite_rules2( $post_rewrite_rules )
// {
// 	global $wp_rewrite;

// 	/* Get front */
// 	$front = ejo_get_front();

//     /* Abort if no front */
// 	if ( !$front )
// 		return;

// 	/**
// 	 * Get Category Rewrite Structure and mix with defaults 
// 	 *
// 	 * The defaults are from class-wp-rewrite.php: add_permastruct function
// 	 */
// 	$category_rewrite_struct = get_taxonomy( 'category' )->rewrite;

// 	$default_rewrite_struct = array(
// 		'with_front' => true,
// 		'ep_mask' => EP_NONE,
// 		'paged' => true,
// 		'feed' => true,
// 		'forcomments' => false,
// 		'walk_dirs' => true,
// 		'endpoints' => true,
// 	);

//     $category_struct = wp_parse_args( $category_rewrite_struct, $default_rewrite_struct );

// 	/* Generate rewrite structure for category */
// 	$category_rewrite_rules = $wp_rewrite->generate_rewrite_rules( 
// 		"$front/%category%",
// 		$category_struct['ep_mask'], 
// 		$category_struct['paged'], 
// 		$category_struct['feed'], 
// 		$category_struct['forcomments'], 
// 		$category_struct['walk_dirs'], 
// 		$category_struct['endpoints'] 
// 	);

// 	return array_merge($category_rewrite_rules, $post_rewrite_rules);
// }

// add_filter( 'page_rewrite_rules', 'ejo_add_category_rewrite_rules_to_bottom' );

	// $category_rewrite_struct = get_taxonomy( 'category' )->rewrite;
	// $default_rewrite_struct = array(
	// 	'with_front' => true,
	// 	'ep_mask' => EP_NONE,
	// 	'paged' => true,
	// 	'feed' => true,
	// 	'forcomments' => false,
	// 	'walk_dirs' => true,
	// 	'endpoints' => true,
	// );

 //    $category_struct = wp_parse_args( $category_rewrite_struct, $default_rewrite_struct );

	// $category_rewrite_rules = $wp_rewrite->generate_rewrite_rules( 
	// 	"$front/%category%",
	// 	$category_struct['ep_mask'], 
	// 	$category_struct['paged'], 
	// 	$category_struct['feed'], 
	// 	$category_struct['forcomments'], 
	// 	$category_struct['walk_dirs'], 
	// 	$category_struct['endpoints'] 
	// );



	// add_rewrite_rule( 
	// 	"$front/(.+?)/page/?([0-9]+)/?$", 
 //    	'index.php?category_name=$matches[1]&paged=$matches[2]',  
 //    	'top'  
	// );
// 
	// /* Add rewrite rule for post category archive pagination */
	// add_rewrite_rule( 
	// 	"$front/(.+?)/([^/]+)(?:/([0-9]+))?/?$", 
	// 	'index.php?category_name=$matches[1]&name=$matches[2]&page=$matches[3]',  
	// 	'top'  
	// );

///////////////////////////

	// DEFAULT STRUCT
	// 'with_front' => true,
	// 'ep_mask' => EP_NONE,
	// 'paged' => true,
	// 'feed' => true,
	// 'forcomments' => false,
	// 'walk_dirs' => true,
	// 'endpoints' => true,

	// register_taxonomy( 'category', 'post', array(
	// 	'hierarchical' => true,
	// 	'query_var' => 'category_name',
	// 	'rewrite' => array(
	//		// CATEGORY STRUCT
	// 		'hierarchical' => true,
	// 		'slug' => get_option('category_base') ? get_option('category_base') : 'category',
	// 		'with_front' => ! get_option('category_base') || $wp_rewrite->using_index_permalinks(),
	// 		'ep_mask' => EP_CATEGORIES,
	// 	),
	// 	'public' => true,
	// 	'show_ui' => true,
	// 	'show_admin_column' => true,
	// 	'_builtin' => true,
	// ) );

	// COMBINED STRUCT
	// 'with_front' => true,
	// 'hierarchical' => true,
	// 'slug' => get_option('category_base') ? get_option('category_base') : 'category',
	// 'with_front' => ! get_option('category_base') || $wp_rewrite->using_index_permalinks(),
	// 'ep_mask' => EP_CATEGORIES,
	// 'paged' => true,
	// 'feed' => true,
	// 'forcomments' => false,
	// 'walk_dirs' => true,
	// 'endpoints' => true,



	// $rules = $wp_rewrite->generate_rewrite_rules( 
	// 	$struct['struct'], // "$front/$slug/%category%"
	// 	$struct['ep_mask'], 
	// 	$struct['paged'], 
	// 	$struct['feed'], 
	// 	$struct['forcomments'], 
	// 	$struct['walk_dirs'], 
	// 	$struct['endpoints'] 
	// );
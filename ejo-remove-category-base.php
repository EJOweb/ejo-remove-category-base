<?php
/**
 * Plugin Name: EJO Remove Category Base
 * Description: Removes the category base slug from the category archive permalinks.
 * Version:     0.9
 * Author:      Erik Joling
 * Author URI:  http://erik.joling.me
 *
 * Inspired by:
 * - WP Remove Category Base of Ezra Verheijen
 * - Remove Category URL of Valerio Souza
 * - No Category Base (WPML) of Marios Alexandrou
 * 
 * License:     GPL v3
 * 
 * Copyright (c) 2016, Erik Joling
 * 
 * EJO Remove Category Base is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation; either version 3 of the License, or
 * (at your option) any later version.
 * 
 * WP Remove Category Base is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
 * GNU General Public License for more details.
 * 
 * You should have recieved a copy of the GNU General Public License
 * along with this program. If not, see <http://www.gnu.org/licenses>.
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit; // exit if accessed directly
}

/**
 * Removes the category base slug from the category archive permalinks
 */
final class EJO_Remove_Category_Base 
{
	/* Holds the instance of this class. */
    protected static $_instance = null;

	/* Returns the instance. */
	public static function instantiate() 
	{
		if ( !self::$_instance )
			self::$_instance = new self;
		return self::$_instance;
	}

	/* Plugin setup. */
	protected function __construct() 
	{
		/* Remove category-base from permastruct */
		add_action( 'init', array( $this, 'remove_base_from_category_permastruct' ) );
		
		/* Empty existing category-rewrite-rules and add new ones based on category-names */
		add_filter( 'category_rewrite_rules', array( $this, 'manage_category_rewrite_rules' ) );

		/* Flush rewrite rules on category manipulations */
		foreach ( array( 'created_category', 'edited_category', 'delete_category' ) as $action ) {
			add_action( $action, 'flush_rewrite_rules' );
		};

		/* Flush rewrite rules on plugin-activation actions */
		register_activation_hook( __FILE__, 'flush_rewrite_rules' );
        register_deactivation_hook( __FILE__, 'flush_rewrite_rules' );
	}

	/**
	 * Remove the category-base from the category permastruct
	 * Add `front` if there is one
	 */
	public function remove_base_from_category_permastruct()
	{
		global $wp_rewrite;

		/* Get `front` of permalink */
		$front = $this->get_front();

		/* Create category permastruct */
		$category_permastruct = (!empty($front)) ? "$front/%category%" : '%category%';

		/* Remove `category` from permalink but keep `front` */
		$wp_rewrite->extra_permastructs['category']['struct'] = $category_permastruct;
	}

	/**
	 * Empty existing category-rewrite-rules and add new ones based on category-names
	 */
	public function manage_category_rewrite_rules( $category_rewrite_rules ) 
	{
		global $wp_rewrite;
		
		/* Empty rewrite rules */
		$category_rewrite_rules = array();
		
		/* Get front if there is one */
		$front = $this->get_front();
		$front = (!empty($front)) ? "$front/" : "";

		/* Get categories */
		$categories = get_categories( array( 'hide_empty' => false ) );

	    /* Add rewrite rules for each category */
		foreach ( $categories as $category ) {
			$category_nicename = "($category->slug)"; // Wrap in parentheses for rewriting
			
			/* If category has parent(s) include them in the nicename */
			if ( $category->parent != 0 && $category->parent != $category->cat_ID ) { 
				$category_nicename = get_category_parents( $category->parent, false, '/', true ) . $category_nicename;
			}			

			/* Create category rewrite rules */
			$this_category_rewrite_rules = array(
			    $front . $category_nicename . '/feed/(feed|rdf|rss|rss2|atom)/?$' => 'index.php?category_name=$matches[1]&feed=$matches[2]',
				$front . $category_nicename . '/(feed|rdf|rss|rss2|atom)/?$' 	  => 'index.php?category_name=$matches[1]&feed=$matches[2]',
				$front . $category_nicename . '/embed/?$' 						  => 'index.php?category_name=$matches[1]&embed=true',
				$front . $category_nicename . '/page/?([0-9]{1,})/?$' 			  => 'index.php?category_name=$matches[1]&paged=$matches[2]',
				$front . $category_nicename . '/?$' 							  => 'index.php?category_name=$matches[1]',
			);

			/* Merge with other category-rewrite-rules */
			$category_rewrite_rules = array_merge( $this_category_rewrite_rules, $category_rewrite_rules );
		}
	
		return $category_rewrite_rules;
	}


	/**
	 * Get `front` of permalink_structure 
	 *
	 * @return 	String `front` or empty string
	 */
	public function get_front()
	{
		$permalink_structure = get_option('permalink_structure');

		/**
		 * Get `front` of permalink_structure (everything up to the first rewrite tag).
		 * Example: Get `blog` from `/blog/%category%/%postname%/`
		 */
		$front = substr($permalink_structure, 0, strpos($permalink_structure, '%'));

		if ($front)
			return trim($front, '/');
		else 
			return '';
	}
}

/* Call class */
EJO_Remove_Category_Base::instantiate();
<?php
/**
 * GeoDirectory GeoDirectory Popular Post View Widget
 *
 * @since 1.0.0
 *
 * @package GeoDirectory
 */

/**
 * GeoDirectory listings widget class.
 *
 * @since 1.0.0
 */
class GeoDir_Widget_Listings extends WP_Super_Duper {


    /**
     * Register the popular posts widget.
     *
     * @since 1.0.0
     * @since 1.5.1 Changed from PHP4 style constructors to PHP5 __construct.
     */
    public function __construct() {


        $options = array(
            'textdomain'    => GEODIRECTORY_TEXTDOMAIN,
            'block-icon'    => 'admin-site',
            'block-category'=> 'widgets',
            'block-keywords'=> "['listings','posts','geo']",

            'class_name'    => __CLASS__,
            'base_id'       => 'gd_listings', // this us used as the widget id and the shortcode id.
            'name'          => __('GD > Listings','geodirectory'), // the name of the widget.
            'widget_ops'    => array(
                'classname'   => 'geodir-listings', // widget class
                'description' => esc_html__('Shows the GD listings filtered by your choices.','geodirectory'), // widget description
                'customize_selective_refresh' => true,
                'geodirectory' => true,
                'gd_show_pages' => array(),
            ),

        );

        


        parent::__construct( $options );

    }

    /**
     * Set the arguments later.
     *
     * @return array
     */
    public function set_arguments(){
        return array(
            'title'  => array(
                'title' => __('Title:', 'geodirectory'),
                'desc' => __('The widget title.', 'geodirectory'),
                'type' => 'text',
                //'placeholder' => __( 'My Dashboard', 'geodirectory' ),
                'default'  => '',
                'desc_tip' => true,
                'advanced' => false
            ),
            'post_type'  => array(
                'title' => __('Default Post Type:', 'geodirectory'),
                'desc' => __('The custom post types to show by default. Only used when there are multiple CPTs.', 'geodirectory'),
                'type' => 'select',
                'options'   =>  geodir_get_posttypes('options-plural'),
                'default'  => 'gd_place',
                'desc_tip' => true,
                'advanced' => true
            ),
            'category'  => array(
                'title' => __('Categories:', 'geodirectory'),
                'desc' => __('The categories to show.', 'geodirectory'),
                'type' => 'select',
                'multiple' => true,
                'options'   =>  $this->get_categories(),
                'default'  => '',
                'desc_tip' => true,
                'advanced' => true
            ),
            'related_to'  => array(
                'title' => __('Filter listings related to:', 'geodirectory'),
                'desc' => __('Select to filter the listings related to current listing categories/tags on detail page.', 'geodirectory'),
                'type' => 'select',
                'options'   =>  array(
                    '' => __('No filter', 'geodirectory'),
                    'category' => __('Categories', 'geodirectory'),
					'tags' => __('Tags', 'geodirectory')
                ),
                'default'  => '',
                'desc_tip' => true,
                'advanced' => true
            ),
            'sort_by'  => array(
                'title' => __('Sort by:', 'geodirectory'),
                'desc' => __('How the listings should be sorted.', 'geodirectory'),
                'type' => 'select',
                'options'   =>  $this->get_sort_options(),
                'default'  => '',
                'desc_tip' => true,
                'advanced' => true
            ),
            'title_tag'  => array(
                'title' => __('Title tag:', 'geodirectory'),
                'desc' => __('The title tag used for the listings.', 'geodirectory'),
                'type' => 'select',
                'options'   =>  array(
                    "h3"        =>  __('h3 (default)', 'geodirectory'),
                    "h2"        =>  __('h2 (if main content of page)', 'geodirectory'),
                ),
                'default'  => 'h3',
                'desc_tip' => true,
                'advanced' => true
            ),
            'layout'  => array(
                'title' => __('Sort by:', 'geodirectory'),
                'desc' => __('How the listings should be sorted.', 'geodirectory'),
                'type' => 'select',
                'options'   =>  array(
                    "gridview_onehalf"        =>  __('Grid View (Two Columns)', 'geodirectory'),
                    "gridview_onethird"        =>  __('Grid View (Three Columns)', 'geodirectory'),
                    "gridview_onefourth"        =>  __('Grid View (Four Columns)', 'geodirectory'),
                    "gridview_onefifth"        =>  __('Grid View (Five Columns)', 'geodirectory'),
                    "list"        =>  __('List view', 'geodirectory'),
                ),
                'default'  => 'h3',
                'desc_tip' => true,
                'advanced' => true
            ),
            'post_limit'  => array(
                'title' => __('Posts to show:', 'geodirectory'),
                'desc' => __('The number of posts to show by default.', 'geodirectory'),
                'type' => 'number',
                'default'  => '5',
                'desc_tip' => true,
                'advanced' => true
            ),
            'add_location_filter'  => array(
                'title' => __("Enable location filter?", 'geodirectory'),
                'type' => 'checkbox',
                'desc_tip' => true,
                'value'  => '1',
                'default'  => '1',
                'advanced' => true
            ),
            'show_featured_only'  => array(
                'title' => __("Show featured only?", 'geodirectory'),
                'type' => 'checkbox',
                'desc_tip' => true,
                'value'  => '1',
                'default'  => '0',
                'advanced' => true
            ),
            'with_pics_only'  => array(
                'title' => __("Only show listings with pictures?", 'geodirectory'),
                'type' => 'checkbox',
                'desc_tip' => true,
                'value'  => '1',
                'default'  => '0',
                'advanced' => true
            ),
            'with_videos_only'  => array(
                'title' => __("Only show listings with videos?", 'geodirectory'),
                'type' => 'checkbox',
                'desc_tip' => true,
                'value'  => '1',
                'default'  => '0',
                'advanced' => true
            ),
            'use_viewing_post_type'  => array(
                'title' => __("Use current viewing post type?", 'geodirectory'),
                'type' => 'checkbox',
                'desc_tip' => true,
                'value'  => '1',
                'default'  => '0',
                'advanced' => true
            ),

//                'show_featured_only'    => '',
//            'show_special_only'     => '',
//            'with_pics_only'        => '',
//            'with_videos_only'      => '',
//            'with_pagination'       => '1',
//            'top_pagination'        => '0',
//            'bottom_pagination'     => '1',
//            'without_no_results'    => 0,
//            'tags'                  => '',
//            'show_favorites_only'   => '',
//            'favorites_by_user'     => '',
        );
    }


    /**
     * The Super block output function.
     *
     * @param array $args
     * @param array $widget_args
     * @param string $content
     *
     * @return mixed|string|void
     */
    public function output($args = array(), $widget_args = array(),$content = ''){


        $args = wp_parse_args((array)$args,
            array('title' => '',
                  'post_type' => '',
                  'category' => array(),
				  'related_to' => '',
                  'category_title' => '',
                  'sort_by' => 'az',
                  'title_tag' => 'h3',
                  'list_order' => '',
                  'post_limit' => '5',
                  'layout' => 'gridview_onehalf',
                  'listing_width' => '',
                  'add_location_filter' => '1',
                  'character_count' => '20',
                  'show_featured_only' => '',
                  'show_special_only' => '',
                  'with_pics_only' => '',
                  'with_videos_only' => '',
                  'use_viewing_post_type' => '',
                  'hide_if_empty' => ''
            )
        );

        ob_start();

        $this->output_html($widget_args, $args);

        return ob_get_clean();
    }


    /**
     * Generates popular postview HTML.
     *
     * @since   1.0.0
     * @since   1.5.1 View all link fixed for location filter disabled.
     * @since   1.6.24 View all link should go to search page with near me selected.
     * @package GeoDirectory
     * @global object $post                    The current post object.
     * @global string $gridview_columns_widget The girdview style of the listings for widget.
     * @global bool $geodir_is_widget_listing  Is this a widget listing?. Default: false.
     * @global object $gd_session              GeoDirectory Session object.
     *
     * @param array|string $args               Display arguments including before_title, after_title, before_widget, and
     *                                         after_widget.
     * @param array|string $instance           The settings for the particular instance of the widget.
     */
    public function output_html( $args = '', $instance = '' ) {
        global $gd_session, $gd_post;


        // prints the widget
        extract( $args, EXTR_SKIP );

        /** This filter is documented in includes/widget/class-geodir-widget-advance-search.php.php */
        $title = empty( $instance['title'] ) ? geodir_ucwords( $instance['category_title'] ) : apply_filters( 'widget_title', __( $instance['title'], 'geodirectory' ) );
        /**
         * Filter the widget post type.
         *
         * @since 1.0.0
         *
         * @param string $instance ['post_type'] Post type of listing.
         */
        $post_type = empty( $instance['post_type'] ) ? 'gd_place' : apply_filters( 'widget_post_type', $instance['post_type'] );
        /**
         * Filter the widget's term.
         *
         * @since 1.0.0
         *
         * @param string $instance ['category'] Filter by term. Can be any valid term.
         */
        $category = empty( $instance['category'] ) ? '0' : apply_filters( 'widget_category', $instance['category'] );
		/**
         * Filter the widget related_to param.
         *
         * @since 2.0.0
         *
         * @param string $instance ['related_to'] Filter by related to categories/tags.
         */
        $related_to = empty( $instance['related_to'] ) ? '' : apply_filters( 'widget_related_to', $instance['related_to'], $instance, $this->id_base );
        /**
         * Filter the widget listings limit.
         *
         * @since 1.0.0
         *
         * @param string $instance ['post_number'] Number of listings to display.
         */
        $post_number = empty( $instance['post_limit'] ) ? '5' : apply_filters( 'widget_post_number', $instance['post_limit'] );
        /**
         * Filter widget's "layout" type.
         *
         * @since 1.0.0
         *
         * @param string $instance ['layout'] Widget layout type.
         */
        $layout = empty( $instance['layout'] ) ? 'gridview_onehalf' : apply_filters( 'widget_layout', $instance['layout'] );
        /**
         * Filter widget's "add_location_filter" value.
         *
         * @since 1.0.0
         *
         * @param string|bool $instance ['add_location_filter'] Do you want to add location filter? Can be 1 or 0.
         */
        $add_location_filter = empty( $instance['add_location_filter'] ) ? '0' : apply_filters( 'widget_add_location_filter', $instance['add_location_filter'] );
        /**
         * Filter widget's listing width.
         *
         * @since 1.0.0
         *
         * @param string $instance ['listing_width'] Listing width.
         */
        $listing_width = empty( $instance['listing_width'] ) ? '' : apply_filters( 'widget_listing_width', $instance['listing_width'] );
        /**
         * Filter widget's "list_sort" type.
         *
         * @since 1.0.0
         *
         * @param string $instance ['list_sort'] Listing sort by type.
         */
        $list_sort             = empty( $instance['list_sort'] ) ? 'latest' : apply_filters( 'widget_list_sort', $instance['list_sort'] );
        /**
         * Filter widget's "title_tag" type.
         *
         * @since 1.6.26
         *
         * @param string $instance ['title_tag'] Listing title tag.
         */
        $title_tag            = empty( $instance['title_tag'] ) ? 'h3' : apply_filters( 'widget_title_tag', $instance['title_tag'] );
        $use_viewing_post_type = ! empty( $instance['use_viewing_post_type'] ) ? true : false;

        // set post type to current viewing post type
        if ( $use_viewing_post_type ) {
            $current_post_type = geodir_get_current_posttype();
            if ( $current_post_type != '' && $current_post_type != $post_type ) {
                $post_type = $current_post_type;
                $category  = array(); // old post type category will not work for current changed post type
            }
        }
		if ( ( $related_to == 'category' || $related_to == 'tags' ) && ! empty( $gd_post->ID ) ) {
			if ( $post_type != $gd_post->post_type ) {
				$post_type = $gd_post->post_type;
				$category = array();
			}
		}
        // replace widget title dynamically
        $posttype_plural_label   = __( get_post_type_plural_label( $post_type ), 'geodirectory' );
        $posttype_singular_label = __( get_post_type_singular_label( $post_type ), 'geodirectory' );

        $title = str_replace( "%posttype_plural_label%", $posttype_plural_label, $title );
        $title = str_replace( "%posttype_singular_label%", $posttype_singular_label, $title );

        $categories = $category;
        if ( ! empty( $category ) && $category[0] != '0' ) {
            $category_taxonomy = geodir_get_taxonomies( $post_type );

            ######### WPML #########
            if ( geodir_wpml_is_taxonomy_translated( $category_taxonomy[0] ) ) {
                $category = geodir_lang_object_ids( $category, $category_taxonomy[0] );
            }
            ######### WPML #########
        }

        if ( isset( $instance['character_count'] ) ) {
            /**
             * Filter the widget's excerpt character count.
             *
             * @since 1.0.0
             *
             * @param int $instance ['character_count'] Excerpt character count.
             */
            $character_count = apply_filters( 'widget_list_character_count', $instance['character_count'] );
        } else {
            $character_count = '';
        }

        if ( empty( $title ) || $title == 'All' ) {
            $title .= ' ' . __( get_post_type_plural_label( $post_type ), 'geodirectory' );
        }

        $location_url = array();
        $city         = get_query_var( 'gd_city' );
        if ( ! empty( $city ) ) {
            $country = get_query_var( 'gd_country' );
            $region  = get_query_var( 'gd_region' );

            $geodir_show_location_url = geodir_get_option( 'geodir_show_location_url' );

            if ( $geodir_show_location_url == 'all' ) {
                if ( $country != '' ) {
                    $location_url[] = $country;
                }

                if ( $region != '' ) {
                    $location_url[] = $region;
                }
            } else if ( $geodir_show_location_url == 'country_city' ) {
                if ( $country != '' ) {
                    $location_url[] = $country;
                }
            } else if ( $geodir_show_location_url == 'region_city' ) {
                if ( $region != '' ) {
                    $location_url[] = $region;
                }
            }

            $location_url[] = $city;
        }

        $location_allowed = function_exists( 'geodir_cpt_no_location' ) && geodir_cpt_no_location( $post_type ) ? false : true;
        $location_url  = implode( '/', $location_url );
        $skip_location = false;
        if ( ! $add_location_filter && $gd_session->get( 'gd_multi_location' ) ) {
            $skip_location = true;
            $gd_session->un_set( 'gd_multi_location' );
        }

        if ( $location_allowed && $add_location_filter && $gd_session->get( 'all_near_me' ) && geodir_is_page( 'location' ) ) {
            $viewall_url = add_query_arg( array(
                'geodir_search' => 1,
                'stype' => $post_type,
                's' => '',
                'snear' => __( 'Near:', 'geodiradvancesearch' ) . ' ' . __( 'Me', 'geodiradvancesearch' ),
                'sgeo_lat' => $gd_session->get( 'user_lat' ),
                'sgeo_lon' => $gd_session->get( 'user_lon' )
            ), geodir_search_page_base_url() );

            if ( ! empty( $category ) && !in_array( '0', $category ) ) {
                $viewall_url = add_query_arg( array( 's' . $post_type . 'category' => $category ), $viewall_url );
            }
        } else {
            if ( get_option( 'permalink_structure' ) ) {
                $viewall_url = get_post_type_archive_link( $post_type );
            } else {
                $viewall_url = get_post_type_archive_link( $post_type );
            }

            if ( ! empty( $category ) && $category[0] != '0' ) {
                global $geodir_add_location_url;

                $geodir_add_location_url = '0';

                if ( $add_location_filter != '0' ) {
                    $geodir_add_location_url = '1';
                }

                $viewall_url = get_term_link( (int) $category[0], $post_type . 'category' );

                $geodir_add_location_url = null;
            }
        }

        if ( $skip_location ) {
            $gd_session->set( 'gd_multi_location', 1 );
        }

        if ( is_wp_error( $viewall_url ) ) {
            $viewall_url = '';
        }

        $query_args = array(
            'posts_per_page' => $post_number,
            'is_geodir_loop' => true,
            'gd_location'    => $add_location_filter ? true : false,
            'post_type'      => $post_type,
            'order_by'       => $list_sort
        );

        if ( $character_count ) {
            $query_args['excerpt_length'] = $character_count;
        }

        if ( ! empty( $instance['show_featured_only'] ) ) {
            $query_args['show_featured_only'] = 1;
        }

        if ( ! empty( $instance['show_special_only'] ) ) {
            $query_args['show_special_only'] = 1;
        }

        if ( ! empty( $instance['with_pics_only'] ) ) {
            $query_args['with_pics_only']      = 0;
            $query_args['featured_image_only'] = 1;
        }

        if ( ! empty( $instance['with_videos_only'] ) ) {
            $query_args['with_videos_only'] = 1;
        }
        $hide_if_empty = ! empty( $instance['hide_if_empty'] ) ? true : false;

        if ( ! empty( $categories ) && $categories[0] != '0' && !empty( $category_taxonomy ) ) {
            $tax_query = array(
                'taxonomy' => $category_taxonomy[0],
                'field'    => 'id',
                'terms'    => $category
            );

            $query_args['tax_query'] = array( $tax_query );
        }

		if ( ( $related_to == 'category' || $related_to == 'tags' ) && ! empty( $gd_post->ID ) ) {
			$terms = array();
			$term_field = 'id';
			$term_taxonomy = $post_type . 'category'; 
			if ( $related_to == 'category' && ! empty( $gd_post->post_category ) ) {
				$terms = explode( ',', trim( $gd_post->post_category, ',' ) );
			} else if ( $related_to == 'tags' && ! empty( $gd_post->post_tags ) ) {
				$term_taxonomy = $post_type . '_tags'; 
				$term_field = 'name';
				$terms = explode( ',', trim( $gd_post->post_tags, ',' ) );
			}
			$query_args['post__not_in'] = $gd_post->ID;

			$query_args['tax_query'] = array( 
				array(
					'taxonomy' => $term_taxonomy,
					'field'    => $term_field,
					'terms'    => $terms
				)
			);
		}

        global $gridview_columns_widget, $geodir_is_widget_listing;

        $widget_listings = geodir_get_widget_listings( $query_args );

        if ( $hide_if_empty && empty( $widget_listings ) ) {
            return;
        }

        ?>
        <div class="geodir_locations geodir_location_listing">

            <?php
            /**
             * Called before the div containing the title and view all link in popular post view widget.
             *
             * @since 1.0.0
             */
            do_action( 'geodir_before_view_all_link_in_widget' ); ?>
            <div class="geodir_list_heading clearfix">
                <?php echo $before_title . $title . $after_title; ?>
                <a href="<?php echo $viewall_url; ?>"
                   class="geodir-viewall"><?php _e( 'View all', 'geodirectory' ); ?></a>
            </div>
            <?php
            /**
             * Called after the div containing the title and view all link in popular post view widget.
             *
             * @since 1.0.0
             */
            do_action( 'geodir_after_view_all_link_in_widget' ); ?>
            <?php
            if ( strstr( $layout, 'gridview' ) ) {
                $listing_view_exp        = explode( '_', $layout );
                $gridview_columns_widget = $layout;
                $layout                  = $listing_view_exp[0];
            } else {
                $gridview_columns_widget = '';
            }

            if ( ! isset( $character_count ) ) {
                /**
                 * Filter the widget's excerpt character count.
                 *
                 * @since 1.0.0
                 *
                 * @param int $instance ['character_count'] Excerpt character count.
                 */
                $character_count = $character_count == '' ? 50 : apply_filters( 'widget_character_count', $character_count );
            }

            global $post, $map_jason, $map_canvas_arr;

            $current_post             = $post;
            $current_map_jason        = $map_jason;
            $current_map_canvas_arr   = $map_canvas_arr;
            $geodir_is_widget_listing = true;

            geodir_get_template( 'widget-listing-listview.php', array( 'title_tag'=>$title_tag, 'widget_listings' => $widget_listings, 'character_count' => $character_count, 'gridview_columns_widget' => $gridview_columns_widget, 'before_widget' => $before_widget ) );

            $geodir_is_widget_listing = false;

            $GLOBALS['post'] = $current_post;
            if ( ! empty( $current_post ) ) {
                setup_postdata( $current_post );
            }
            $map_jason      = $current_map_jason;
            $map_canvas_arr = $current_map_canvas_arr;
            ?>
        </div>
        <?php
    }



    public function get_categories($post_type = 'gd_place'){
        $options = array(
            '0'     => __('All', 'geodirectory')
        );
        $all_postypes = geodir_get_posttypes();

        if (!in_array($post_type, $all_postypes))
            $post_type = 'gd_place';


        $category_taxonomy = geodir_get_taxonomies($post_type);
        $categories = get_terms($category_taxonomy[0], array('orderby' => 'count', 'order' => 'DESC'));


        if(!is_wp_error( $categories )){
            foreach ($categories as $category_obj) {

                $options[$category_obj->term_id] = geodir_utf8_ucfirst($category_obj->name);
            }
        }


        return $options;
    }

    public function get_sort_options($post_type = 'gd_place'){
        $options = array(
            "az"        =>  __('A-Z', 'geodirectory'),
            "latest"        =>  __('Latest', 'geodirectory'),
            "featured"        =>  __('Featured', 'geodirectory'),
            "high_review"        =>  __('Most reviews', 'geodirectory'),
            "high_rating"        =>  __('Highest rating', 'geodirectory'),
            "random"        =>  __('Random', 'geodirectory'),
        );

        return $options;
    }





}

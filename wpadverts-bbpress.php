<?php
/*
Plugin Name: WPAdverts bbPress Integration
Plugin URI: https://github.com/elhardoum/wpadverts-bbpress
Description: Quick WPAdverts bbPress Integration
Author: Samuel Elh
Version: 0.1
Author URI: https://samelh.com
Donate link: https://paypal.me/samelh
*/

define('WABBP_DIR', plugin_dir_path(__FILE__));

function wpadvbbp() {
    return apply_filters(__FUNCTION__, array(
        'profile-tab' => array(
            'slug' => 'portfolio',
            'menu-item-text' => apply_filters('wpadvbbp_menu_item_text', 'Portfolio (%d)'),
            'menu-item-position' => 3
        )
    ));
}

if ( !class_exists('bbPressProfileTabs') ) {
    require ( WABBP_DIR . 'bbPress-Profile-Tabs/bbPressProfileTabs.php' );
}

add_action('plugins_loaded', 'wabbp_bbpress_profile_tab');

function wabbp_bbpress_profile_tab() {
    $args = wpadvbbp();

    if ( empty($args['profile-tab']) )
        return;

    return bbPressProfileTabs::create($args['profile-tab'])->init();
}

add_filter('BPT_tab_nav_item_display_portfolio', 'wpabbp_append_menu_item_adverts_count', 10, 2);

function wpabbp_append_menu_item_adverts_count($text, $user_id) {
    if ( false !== strpos($text, '%') && $user_id ) {
        $text = sprintf($text, wabbp_get_adverts_count($user_id));
    }

    $text = preg_replace(array(
        '/ \(0\)/s',
        '/ \(%d\)/s'
    ), '', $text);

    return $text;
}

function wabbp_get_adverts_count($user_id) {
    return apply_filters(__FUNCTION__, (int) count_user_posts($user_id, 'advert'), $user_id);
}

register_activation_hook(__FILE__, 'wabbp_flush_rules');
register_deactivation_hook(__FILE__, 'wabbp_flush_rules');

function wabbp_flush_rules() {
    return delete_option('rewrite_rules');
}

add_action('init', 'wabbp_bbpress_profile_tab_content');

function wabbp_bbpress_profile_tab_content() {
    $args = wpadvbbp();

    if ( empty($args['profile-tab']['slug']) )
        return;

    add_action('BPT_content-'. $args['profile-tab']['slug'], 'wabbp_bbpress_profile_tab_parse_content');
}

function wabbp_bbpress_profile_tab_parse_content() {
    $user_id = bbp_get_displayed_user_id();
    $shortcode = sprintf('[adverts_list columns=2 author=%d display=grid]', $user_id);
    $shortcode = apply_filters(__FUNCTION__, $shortcode, $user_id);
    echo do_shortcode($shortcode);
}

add_action('wp_head', 'wabbp_bpt_css_hack');

function wabbp_bpt_css_hack() {
    print '<style>#bbp-user-body .BPT-content { overflow: hidden; }</style>' . PHP_EOL;
}

// user forums
add_action('bbp_theme_after_reply_author_details', 'wabbp_forum_reply_portfolio_link');

function wabbp_forum_reply_portfolio_link() {
    $user_id = bbp_get_reply_author_id();

    if ( !$user_id )
        return;

    ob_start();

    print '<p>';
    printf ('<a href="%s">', wabbp_link_to_bbp_tab($user_id));
    print wpabbp_append_menu_item_adverts_count(apply_filters('wpadvbbp_menu_item_text', 'Portfolio (%d)'), $user_id);
    print '</a>';
    print '</p>';

    echo apply_filters(__FUNCTION__, ob_get_clean(), $user_id);
}

function wabbp_link_to_bbp_tab($user_id) {
    $args = wpadvbbp();

    if ( !empty($args['profile-tab']['slug']) ) {
        $slug = $args['profile-tab']['slug'];
    } else {
        $slug = 'portfolio';
    }

    $link = sprintf(
        '%s%s/',
        wabbp_bbp_get_user_profile_url($user_id),
        $slug
    );

    return apply_filters(__FUNCTION__, $link, $user_id, $slug);
}

function wabbp_bbp_get_user_profile_url( $user_id = 0, $user_nicename = '' ) {
    global $wp_rewrite;

    // Use displayed user ID if there is one, and one isn't requested
    $user_id = bbp_get_user_id( $user_id );
    if ( empty( $user_id ) )
        return false;

    // Allow early overriding of the profile URL to cut down on processing
    $early_profile_url = apply_filters( 'wabbp_bbp_pre_get_user_profile_url', (int) $user_id );
    if ( is_string( $early_profile_url ) )
        return $early_profile_url;

    // Pretty permalinks
    if ( $wp_rewrite->using_permalinks() ) {
        $url = $wp_rewrite->root . bbp_get_user_slug() . '/%' . bbp_get_user_rewrite_id() . '%';

        // Get username if not passed
        if ( empty( $user_nicename ) ) {
            $user_nicename = bbp_get_user_nicename( $user_id );
        }

        $url = str_replace( '%' . bbp_get_user_rewrite_id() . '%', $user_nicename, $url );
        $url = home_url( user_trailingslashit( $url ) );

    // Unpretty permalinks
    } else {
        $url = add_query_arg( array( bbp_get_user_rewrite_id() => $user_id ), home_url( '/' ) );
    }

    return apply_filters(__FUNCTION__, $url, $user_id, $user_nicename );
}

add_action('bbp_theme_after_reply_content', 'wabbp_parse_topic_adverts');

function wabbp_parse_topic_adverts() {
    $user_id = bbp_get_reply_author_id();
    $topic_id = bbp_get_topic_id();
    $reply_id = bbp_get_reply_id();

    if ( ($topic_id && $topic_id == $reply_id) || (!$reply_id && $topic_id) ) {
        wabbp_parse_topic_adverts_parse($topic_id, $user_id);
    }
}

function wabbp_parse_topic_adverts_parse($topic_id, $user_id) {
    $loop = new WP_Query(apply_filters('wabbp_parse_topic_adverts_parse_query', array( 
        'author' => $user_id,
        'post_type' => 'advert', 
        'post_status' => 'publish',
        'posts_per_page' => 1, 
        'orderby'   => 'rand'
    )));

    $columns = 1;

    if ( !$loop->have_posts() )
        return;

    wp_enqueue_style( 'adverts-frontend' );
    wp_enqueue_style( 'adverts-icons' );
    wp_enqueue_script( 'adverts-frontend' );

    ob_start();
    ?>
    <div class="adverts-list adverts-bg-hover">
        <?php if( $loop->have_posts() ): ?>
        <?php while ( $loop->have_posts() ) : $loop->the_post(); ?>
        <?php include apply_filters( "adverts_template_load", ADVERTS_PATH . 'templates/list-item.php' ) ?>
        <?php endwhile; ?>
        <?php else: ?>
        <div class="adverts-list-empty"><em><?php _e("There are no ads matching your search criteria.", "adverts") ?></em></div>
        <?php endif; ?>
        <?php wp_reset_query(); ?>
    </div>
    <?php

    echo apply_filters(__FUNCTION__, ob_get_clean(), $topic_id, $user_id);
}
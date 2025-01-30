<?php

/**
 * Plugin Name: Custom Wishlist for CPT
 * Description: A simple wishlist feature using AJAX.
 * Version: 1.0
 * Author: Szymon Mudrak
 */


function wishlist_enqueue_scripts()
{
    wp_enqueue_script('wishlist-script', plugin_dir_url(__FILE__) . 'wishlist.js', array('jquery'), null, true);
    wp_localize_script('wishlist-script', 'wishlist_ajax', array(
        'ajax_url' => admin_url('admin-ajax.php'),
        'nonce' => wp_create_nonce('wishlist_nonce')
    ));
    wp_enqueue_style('wishlist-style', plugin_dir_url(__FILE__) . 'wishlist.css');
}
add_action('wp_enqueue_scripts', 'wishlist_enqueue_scripts');



// Add wishlist button shortcode
function wishlist_button_shortcode()
{
    if (!is_singular('produkt')) return '';
    $post_id = get_the_ID();
    $wishlist = isset($_COOKIE['wishlist']) ? json_decode(stripslashes($_COOKIE['wishlist']), true) : [];
    $is_in_wishlist = in_array($post_id, $wishlist) ? 'added' : '';

    return "<button class='wishlist-button $is_in_wishlist' data-postid='$post_id'>" . ($is_in_wishlist ? "Usuń z listy -" : "Dodaj do listy +") . "</button>";
}
add_shortcode('wishlist_button', 'wishlist_button_shortcode');


// AJAX handler for adding/removing wishlist item
function wishlist_toggle_item()
{
    check_ajax_referer('wishlist_nonce', 'nonce');

    $post_id = intval($_POST['post_id']);
    if (!$post_id) wp_send_json_error('Invalid post ID');

    $wishlist = isset($_COOKIE['wishlist']) ? json_decode(stripslashes($_COOKIE['wishlist']), true) : [];

    if (in_array($post_id, $wishlist)) {
        $wishlist = array_diff($wishlist, [$post_id]);
    } else {
        $wishlist[] = $post_id;
    }

    setcookie('wishlist', json_encode($wishlist), time() + (30 * 24 * 60 * 60), '/'); // 30 days expiration
    wp_send_json_success(['wishlist' => $wishlist, 'count' => count($wishlist)]);
}

add_action('wp_ajax_wishlist_toggle', 'wishlist_toggle_item');
add_action('wp_ajax_nopriv_wishlist_toggle', 'wishlist_toggle_item');


// Shortcode for display current items on the /schowek page
function wishlist_display_shortcode()
{

    $wishlist = isset($_COOKIE['wishlist']) ? json_decode(stripslashes($_COOKIE['wishlist']), true) : [];

    if (empty($wishlist)) {
        return '<p>Twój schowek jest pusty.</p>';
    }

    $output = '<ul class="wishlist-items">';

    foreach ($wishlist as $post_id) {
        $post = get_post($post_id);

        $image_url = get_the_post_thumbnail_url($post->ID, 'full');
        $price = get_field('cena', $post->ID);
        $inventory = intval(get_field('stan_magazynowy', $post->ID));
        $categories = get_the_category($post->ID);
        $main_category = isset($categories[0]) ? $categories[0]->cat_name : '';

        $output .= "
        <li class='flex-row'>
            <a class='col-70 flex-row' href='" . get_permalink($post_id) . "'>
                <img class='product-img' src='" . esc_url($image_url) . "' alt='" . esc_attr(get_the_title($post->ID)) . "'>
                <div class='flex-col'>
                    <h3>" . esc_html($post->post_title) . "</h3>
                    <p>Cena jednostkowa: " . esc_html($price) . " złotych</p>
                </div>
            </a>
            <div class='col-30'>";

        if ($inventory >= 1 and $main_category == 'Dodatki do stroju' || $main_category == 'Inne') {
            $output .= "
            <div class='quantity-price flex-col'>
                <span class='flex-row'>
                    <span>Ilość: </span>
                    <input data-postid='" . esc_html($post_id) . "' data-price='" . esc_html($price) . "' type='number' min='0' value='1' max='" . esc_html($inventory) . "'/>      
                </span>
                <span class='flex-row'>
                    <span>Suma:</span>
                    <span data-postid='" . esc_html($post_id) . "' class='full-cost'>" . esc_html($price) . " złotych</span>  
                </span>
            </div>
            ";
        } elseif ($inventory == 0) {

            $output .= "<p>Brak produktu na magazynie.</p>";
        } else {
            $output .= "<p>Dla tego produktu nie można wybrać ilości.</p>";
        }

        $output .= "</div></li>";
    }

    $output .= '</ul>';
    return $output;
}
add_shortcode('wishlist_display', 'wishlist_display_shortcode');



// Code to display "bubble" with number of items currently in the wishlist.
function wishlist_get_count()
{
    $wishlist = isset($_COOKIE['wishlist']) ? json_decode(stripslashes($_COOKIE['wishlist']), true) : [];
    wp_send_json_success(['count' => count($wishlist)]);
}

add_action('wp_ajax_wishlist_get_count', 'wishlist_get_count');
add_action('wp_ajax_nopriv_wishlist_get_count', 'wishlist_get_count');

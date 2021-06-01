<?php
/*
Plugin Name: Hack Domain
Plugin URI: https://github.com/LoneDev6/yourls-hack-domain
Description: Hacky shit to change domain shown in admin gui, if you're doing hacky shit using Cloudflare like me.
Version: 1.0
Author: LoneDev6
Author URI: https://github.com/LoneDev6
*/


yourls_add_filter( 'table_add_row', 'do_replace_domain' );
yourls_add_filter( 'table_edit_row', 'do_replace_domain' );
yourls_add_filter( 'html_title', 'do_replace_domain' );
yourls_add_filter( 'html_link', 'do_replace_domain' );
yourls_add_filter( 'bookmarklet_jsonp', 'do_replace_domain' );


// based on original work from the PHP Laravel framework
if (!function_exists('str_contains')) {
    function str_contains($haystack, $needle) {
        return $needle !== '' && mb_strpos($haystack, $needle) !== false;
    }
}
function isHTML($string) {
	return $string != strip_tags($string) ? true:false;
}

function do_replace_domain($text) {
	
	$domain = yourls_get_option( 'hack_domain_option' );
	
	if(!isHTML($text)) {
		if(substr($text, -1) !== '+' && !str_contains($text, YOURLS_SITE . "/admin"))//avoid replacing ajax stuff
			return str_replace(YOURLS_SITE, $domain, $text);
	}
	
	$dom = new DOMDocument;
	$dom->loadHTML('<?xml encoding="utf-8" ?>' . $text);
	$xpath = new DOMXPath($dom);
	$nodes = $xpath->query("//a");
	
	foreach($nodes as $node) {
		$href = $node->getAttribute('href');
		if(substr($href, -1) !== '+' && !str_contains($href, YOURLS_SITE . "/admin"))//avoid replacing ajax stuff
			$node->setAttribute('href', str_replace(YOURLS_SITE, $domain, $href));
	}
	return $dom->saveHTML();
}


// Register our plugin admin page
yourls_add_action( 'plugins_loaded', 'hack_domain' );
function hack_domain() {
	yourls_register_plugin_page( 'hack_domain_page', 'Hack Domain Settings', 'hack_domain_do_page' );
}

// Display admin page
function hack_domain_do_page() {

	// Check if a form was submitted
	if( isset( $_POST['hack_domain_option'] ) ) {
		// Check nonce
		yourls_verify_nonce( 'hack_domain_page' );
		
		// Process form
		hack_domain_update_option();
	}

	// Get value from database
	$hack_domain_option = yourls_get_option( 'hack_domain_option' );

	
	// Create nonce
	$nonce = yourls_create_nonce( 'hack_domain_page' );

	echo <<<HTML
		<h2>Change Domain Settings</h2>
		<p>Custom Domain</p>
		<form method="post">
		<input type="hidden" name="nonce" value="$nonce" />
		<p><input name="hack_domain_option" value="$hack_domain_option"></p>
		<p><input type="submit" value="Update" /></p>
		</form>


HTML;
}

// Update option in database
function hack_domain_update_option() {
	$hack_domain = $_POST['hack_domain_option'];
	// Update value in database
	yourls_update_option( 'hack_domain_option', $hack_domain );
}

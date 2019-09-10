<?php
/*
Plugin Name: UNC Catalog
Plugin URI: http://digitalservices.unc.edu/plugins/plugin-unc-catalog/
Description: A plugin that allows you to pull in course information from catalog.unc.edu. Documentation and shortcodes can be found on the plugin website.
Version: 1.0
Author: ITS Digital Services
Author URI: http://digitalservices.unc.edu
*/

// Disable direct file access.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( !class_exists( 'Catalog' ) ) {

	class Catalog {

		public function __construct() {
			add_action( 'wp_enqueue_scripts', array($this,'catalog_assets') );
			add_shortcode( 'catalog_course', array($this,'catalog_course_shortcode') );
			add_shortcode( 'catalog_courselist', array($this,'catalog_courselist_shortcode') );
			add_shortcode( 'catalog', array($this,'catalog_page_shortcode') );
		}


		public function catalog_assets() {
			wp_enqueue_style('plugin_css', plugin_dir_url( __FILE__ ) . 'css/style.css');
		}


		// Single Course Information Shortcode
		public function catalog_course_shortcode( $atts ) {
			$a = shortcode_atts( array(
				'course' => '',
			), $atts );

			// Begin output
			$course_output = '<section class="unc-catalog catalog-course">';

			// If course isn't specified
			if ( empty($a['course']) ) {
				$course_output .= '<div class="catalog-course__error"><p><strong>Error:</strong> No course specified.</p></div>';
				$course_output .= '</section>';
				return $course_output;
			}

			// Get XML data
			$course = strtoupper($a['course']);
			$url = 'https://catalog.unc.edu/ribbit/?page=getcourse.rjs&code=' . urlencode($course);
			$xml = $this->catalog_get_xml($url);

			error_log( var_export( $xml, true ));
			
			// Finish output
			if( $xml ){
				if ($xml->course == "") {
					$course_output .= '<div class="catalog-course__error"><p><strong>Error:</strong> Course <strong>' . $a['course'] . '</strong> does not exist.</p></div>';
				} else {
					$course_output .= '<div class="catalog-course__info">' .
							$this->catalog_change_relative_links($xml->course) .
						'</div>';
				}
			} else {
				$course_output .= '<div class="catalog-course__error"><p><strong>Error:</strong> Could not get course data.</p></div>';
			}
		
			$course_output .= '</section>';

			return $course_output;
		}


		// Department Course List Shortcode
		public function catalog_courselist_shortcode( $atts ) {
			$a = shortcode_atts( array(
				'department' => '',
			), $atts );

			// Begin output
			$courselist_output = '<section class="unc-catalog catalog-courselist">';

			// If course isn't specified
			if ( empty($a['department']) ) {
				$courselist_output .= '<div class="catalog-courselist__error"><p><strong>Error:</strong> No department specified.</p></div>';
				$courselist_output .= '</section>';
				return $courselist_output;
			}

			// Get XML data
			$url = 'https://catalog.unc.edu/courses/' . strtolower($a['department']) . '/index.xml';
			$xml = $this->catalog_get_xml($url);
			
			// Finish output
			if ($xml === false) {
				$courselist_output .= '<div class="catalog-courselist__error"><p><strong>Error:</strong> Department <strong>' . $a['department'] . '</strong> does not exist. Make sure the four letter department code is correct.</p></div>';
			}
			else {
				$courselist_output .= '<h1 class="catalog-courselist__title">' .
						$xml->title .
					'</h1>' .
					'<div class="catalog-couselist__info">' .
						$this->catalog_change_relative_links($xml->text) .
					'</div>';
			}

			$courselist_output .= '</section>';

			return $courselist_output;
		}


		// Catalog Shortcode, can be used for any page on the catalog webite
		public function catalog_page_shortcode( $atts ) {
			$a = shortcode_atts( array(
				'url' => '',
				'title' => 'show',
				'overview' => 'show',
				'sections' => '',
			), $atts );

			// Begin output
			$page_output = '<section class="unc-catalog catalog-page">';

			// Make sure URL is specified
			if ( empty( $a['url'] ) ) {
				$page_output .= '<div class="catalog_page__section catalog-page__error"><p><strong>Error:</strong> Please enter a catalog page URL.</p></div>';
				$page_output .= '</section>';
				return $page_output;
			}

			// Get XML data
			$url = $this->catalog_remove_anchor_link($a['url']) . '/index.xml';
			$xml = $this->catalog_get_xml($url);

			// If Catalog page doesn't return XML or is 404
			if ( empty ( $xml ) ) {
				$page_output .= '<div class="catalog_page__section catalog-page__error"><p><strong>Error:</strong> The page <strong>' . $a['url'] . '</strong> either does not exist or cannot be used with this plugin.</p></div>';
				$page_output .= '</section>';
				return $page_output;
			}

			// Finish output
			if ($a['title'] == 'show') {
				$page_output .='<div class="catalog-page__title">
						<h1>' .
							$xml->title .
						'</h1>
					</div>';
			}

			if ($a['overview'] == 'show') {
				$page_output .= '<div class="catalog_page__section catalog-page__text">' .
						$this->catalog_change_relative_links($xml->text) .
					'</div>';
			}

			$sections = explode(',',$a['sections']);

			// If sections parameter isn't empty
			if ( $sections[0] !== ''  ) {

				foreach ($sections as $item_with_spaces) {
					$item_no_spaces = str_replace(' ', '', $item_with_spaces);
					$section_name = strtolower($item_no_spaces) . 'text';

					if ( $xml->$section_name == '' ) {
						$page_output .= '<div class="catalog_page__section catalog-page__error"><p><strong>Error:</strong> The section <strong>' . $item_with_spaces . '</strong> does not exist on this catalog page. For troubleshooting tips, see plugin documentation.</p></div>';
					}
					else {
						$page_output .= '<div class="catalog_page__section catalog-page__' . $item_no_spaces . '">' .
								$this->catalog_change_relative_links($xml->$section_name) .
							'</div>';
					}
				}
			}

			$page_output .= '</section>';

			return $page_output;
		}

		// Foward proxy for getting through firewall to grab XML data
		// To test locally, I had to comment out line 187 & 192 and uncomment line 196
		public function catalog_get_xml($url) {
			$proxy = 'tcp://fp.isis.unc.edu:80';

			$ch = curl_init();

			curl_setopt($ch, CURLOPT_URL, $url);
			curl_setopt($ch, CURLOPT_PROXY, $proxy);
			curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
			curl_setopt($ch, CURLOPT_FOLLOWLOCATION, 1);
			curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
			// curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);

			$xml = curl_exec($ch);

			curl_close($ch);
			
			$xml = simplexml_load_string($xml);

			return $xml;
		}

		// Function to change any relative links to to https://catalog.unc.edu
		public function catalog_change_relative_links($data) {
			$data = str_replace('<a href="/', '<a target="_blank" href="https://catalog.unc.edu/', $data);
			$data = str_replace('<img src="/', '<img src="https://catalog.unc.edu/', $data);
			return $data;
		}

		// Function to remove anchor link in URLs (i.e. .../#coursestext)
		public function catalog_remove_anchor_link($data) {
			if (strpos($data, '#')) {
				$data = substr($data, 0, strpos($data, '#'));
			}
			return $data;
		}

	}

}

$unc_catalog = new Catalog();
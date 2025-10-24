<?php
namespace ReyCore\Modules\DynamicTags\Tags\Woo;

use \ReyCore\Modules\DynamicTags\Base as TagDynamic;

if (!defined('ABSPATH')) exit; // Exit if accessed directly

class AttributeImage extends \ReyCore\Modules\DynamicTags\Tags\DataTag {

	public static function __config() {
		return [
			'id'         => 'product-attribute-image',
			'title'      => esc_html__( 'Product Attribute Image', 'rey-core' ),
			'categories' => [ 'image' ],
			'group'      => TagDynamic::GROUPS_WOO,
		];
	}

	protected function register_controls() {

		TagDynamic::woo_product_control($this);

		$this->add_control(
			'attribute',
			[
				'label'       => esc_html__( 'Attribute Item', 'rey-core' ),
				'default'     => '',
				'type'        => 'rey-query',
				'label_block' => true,
				'query_args'  => [
					'type'     => 'terms',
					'taxonomy' => 'product_taxonomies',
				],
			]
		);

		$this->add_control(
			'meta_key',
			[
				'label' => esc_html__( 'Meta Key', 'rey-core' ),
				'type' => \Elementor\Controls_Manager::TEXT,
				'default' => '',
			]
		);
	}

	public function get_value( $options = [] ) {

		if( ! ($product = TagDynamic::get_product($this)) ){
			return [];
		}

		$settings = $this->get_settings();

		if( ! ($term_id = $settings['attribute']) ){
			return [];
		}

		$term_obj = get_term_by( 'term_taxonomy_id', $term_id );

		if( ! isset($term_obj->name) ){
			return [];
		}

		// product must have this term
		if( ! has_term( $term_id, $term_obj->taxonomy, $product->get_id() ) ){
			return [];
		}

		if( ! (($meta_key = $settings['meta_key']) && ($meta = get_term_meta($term_id, $meta_key, true))) ){
			return [];
		}

		// likely an ID or URL
		if( is_string($meta) && ! empty($meta) ){
			if( is_numeric($meta) ){
				$att_id = absint($meta);
				return [
					'id' => $att_id,
					'url' => wp_get_attachment_image_src($att_id, 'full'),
				];
			}
			else {
				$att_url = esc_url($meta);
				return [
					'id'  => attachment_url_to_postid($att_url),
					'url' => $att_url,
				];
			}
		}
		else if( is_array($meta) ){
			if( isset($meta['id'], $meta['url']) ){
				return [
					'id'  => $meta['id'],
					'url' => $meta['url'],
				];
			}
			// likely an ID
			elseif( count($meta) === 1 ){
				return [
					'id'  => $meta[0],
					'url' => wp_get_attachment_image_src($meta[0], 'full'),
				];
			}
		}

		return [];
	}

}

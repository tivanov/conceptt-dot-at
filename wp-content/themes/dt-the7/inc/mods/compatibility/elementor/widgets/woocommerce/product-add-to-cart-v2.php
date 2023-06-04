<?php
/**
 * @package The7
 */

namespace The7\Mods\Compatibility\Elementor\Widgets\Woocommerce;

use Elementor\Controls_Manager;
use Elementor\Group_Control_Border;
use Elementor\Group_Control_Box_Shadow;
use Elementor\Group_Control_Typography;
use The7\Mods\Compatibility\Elementor\The7_Elementor_Widget_Base;
use The7\Mods\Compatibility\Elementor\Widget_Templates\Button;

defined( 'ABSPATH' ) || exit;

/**
 * Product_Add_To_Cart_V2 class.
 */
class Product_Add_To_Cart_V2 extends The7_Elementor_Widget_Base {

	/**
	 * @return string
	 */
	public function get_name() {
		return 'the7-woocommerce-product-add-to-cart-v2';
	}

	/**
	 * @return string
	 */
	public function the7_title() {
		return esc_html__( 'Add To Cart', 'the7mk2' );
	}

	/**
	 * @return string
	 */
	public function the7_icon() {
		return 'eicon-product-add-to-cart';
	}

	/**
	 * @return string[]
	 */
	public function the7_keywords() {
		return [ 'woocommerce', 'shop', 'store', 'cart', 'product', 'button', 'add to cart' ];
	}

	/**
	 * @return string[]
	 */
	public function get_style_depends() {
		return [ $this->get_name() ];
	}

	/**
	 * @return string[]
	 */
	public function get_script_depends() {
		return [ $this->get_name() ];
	}

	/**
	 * Register widget assets.
	 */
	protected function register_assets() {
		the7_register_style(
			$this->get_name(),
			THE7_ELEMENTOR_CSS_URI . '/the7-woocommerce-add-to-cart'
		);

		the7_register_script_in_footer(
			$this->get_name(),
			THE7_ELEMENTOR_JS_URI . '/the7-woocommerce-add-to-cart.js',
			[ 'jquery', 'wc-add-to-cart' ]
		);
	}

	/**
	 * @return void
	 */
	protected function render() {
		global $product;
		$settings = $this->get_settings();

		$product = wc_get_product();

		if ( empty( $product ) ) {
			return;
		}

		echo '<div class="the7-add-to-cart the7-product-' . esc_attr( $product->get_type() ) . '">';

		add_filter( 'woocommerce_dropdown_variation_attribute_options_html', [ $this, 'show_stock_status_in_dropdown' ], 10, 2 );
		add_filter( 'woocommerce_after_add_to_cart_button', [ $this, 'render_submit_button' ] );

		woocommerce_template_single_add_to_cart();

		remove_filter( 'woocommerce_dropdown_variation_attribute_options_html', [ $this, 'show_stock_status_in_dropdown' ], 10 );
		remove_filter( 'woocommerce_after_add_to_cart_button', [ $this, 'render_submit_button' ] );

		echo '</div>';
	}

	/**
	 * @param string $html HTML.
	 * @param array  $args Arguments.
	 *
	 * @return string
	 */
	public function show_stock_status_in_dropdown( $html, $args ) {
		global $wp;

		$settings = $this->get_settings();
		$options  = $args['options'];

		/**
		 * @var \WC_Product $product
		 */
		$product = $args['product'];

		$attribute             = sanitize_key( $args['attribute'] );
		$name                  = $args['name'] ? $args['name'] : 'attribute_' . sanitize_title( $attribute );
		$id                    = $args['id'] ? $args['id'] : sanitize_title( $attribute );
		$class                 = $args['class'];
		$show_option_none      = $args['show_option_none'] ? true : false;
		$show_option_none_text = $args['show_option_none'] ? $args['show_option_none'] : __( 'Choose an option', 'woocommerce' );
		$icon                  = '';
		if ( $settings['icon'] !== '' && $settings['layout'] === 'dropdown' ) {
			$icon = $this->get_elementor_icon_html( $settings['icon'] );
		}

		if ( empty( $options ) && ! empty( $product ) && ! empty( $attribute ) ) {
			$attributes = $product->get_variation_attributes();
			$options    = $attributes[ $attribute ];
		}

		if ( $settings['layout'] === 'dropdown' ) {

			$html  = '<div class="the7-wc-variation-select">';
			$html .= '<select id="' . esc_attr( $id ) . '" class="' . esc_attr( $class ) . '" name="' . esc_attr( $name ) . '" data-attribute_name="attribute_' . esc_attr( sanitize_title( $attribute ) ) . '" data-show_option_none="' . ( $show_option_none ? 'yes' : 'no' ) . '">';
			$html .= '<option value="">' . esc_html( $show_option_none_text ) . '</option>';

			if ( ! empty( $options ) ) {
				if ( $product && taxonomy_exists( $attribute ) ) {
					// Get terms if this is a taxonomy - ordered. We need the names too.
					$terms = wc_get_product_terms( $product->get_id(), $attribute, array( 'fields' => 'all' ) );

					foreach ( $terms as $term ) {
						if ( in_array( $term->slug, $options, true ) ) {
							$html .= '<option value="' . esc_attr( $term->slug ) . '" ' . selected( sanitize_title( $args['selected'] ), $term->slug, false ) . '>' . esc_html( apply_filters( 'woocommerce_variation_option_name', $term->name ) ) . '</option>';
						}
					}
				} else {
					foreach ( $options as $option ) {
						// This handles < 2.4.0 bw compatibility where text attributes were not sanitized.
						$selected = sanitize_title( $args['selected'] ) === $args['selected'] ? selected( $args['selected'], sanitize_title( $option ), false ) : selected( $args['selected'], $option, false );

						$html .= '<option value="' . esc_attr( $option ) . '" ' . $selected . '>' . esc_html( $option ) . '</option>';

					}
				}
			}

			$html .= '</select>';
			$html .= $icon;
			$html .= '</div>';
		} else {
			$html .= '<ul class="the7-vr-options ' . esc_attr( $class ) . '" data-atr="' . esc_attr( $id ) . '">';

			if ( $options && $product ) {
				$variations = $this->get_product_variations( $product, $attribute );

				foreach ( $options as $option ) {
					if ( ! isset( $variations[ $option ] ) ) {
						continue;
					}

					$variation = $variations[ $option ];
					$name      = $variation->get_attribute( $attribute );

					$href  = add_query_arg( $attribute, $option, $product->get_permalink() );
					$class = '';
					if ( ! $variation->is_in_stock() ) {
						$class = 'class="out-of-stock"';
					}

					$html .= '<li><a href="' . esc_url( $href ) . '" data-id="' . esc_attr( $option ) . '" ' . $class . '>' . esc_html( apply_filters( 'woocommerce_variation_option_name', $name ) ) . '</a></li>';
				}
			}

			$html .= '</ul>';
		}

		return $html;
	}

	/**
	 * @return void
	 */
	public function render_submit_button() {
		global $product;

		$text = $this->get_settings_for_display( 'button_text' );

		if ( ! $text ) {
			$text = $product->single_add_to_cart_text();
		}

		$this->add_render_attribute(
			'button',
			[
				'type'  => 'submit',
				'class' => [ 'single_add_to_cart_button', 'button', 'alt' ],
			]
		);

		if ( $product->get_type() === 'simple' ) {
			$this->add_render_attribute(
				'button',
				[
					'name'  => 'add-to-cart',
					'value' => $product->get_id(),
				]
			);
		}

		$this->template( Button::class )->render_button( 'button', $text, 'button' );
	}

	/**
	 * @param string $var Product add to cart text.
	 *
	 * @return string
	 */
	public function filter_woocommerce_product_single_add_to_cart_text( $var ) {
		return $this->get_settings_for_display( 'button_text' );
	}

	/**
	 * @param \WC_Product $product Product object.
	 * @param string      $attribute Product attribute.
	 *
	 * @return array
	 */
	protected function get_product_variations( $product, $attribute ) {
		if ( ! $product ) {
			return [];
		}

		/**
		 * @var array[]|\WC_Product_Variation[] $available_variations
		 */
		$available_variations = $product->get_available_variations( 'objects' );
		$variations           = [];

		foreach ( $available_variations as $variation ) {
			$attributes = $variation->get_variation_attributes( false );
			if ( ! isset( $attributes[ $attribute ] ) ) {
				continue;
			}

			$slug = $attributes[ $attribute ];
			if ( empty( $variations[ $slug ] ) || $variation->is_in_stock() ) {
				$variations[ $slug ] = $variation;
			}
		}

		return $variations;
	}

	/**
	 * @return void
	 */
	protected function register_controls() {
		// Content.
		$this->add_layout_content_controls();

		// Style.
		$this->add_variation_grid_styles_controls();
		$this->add_variation_dropdown_styles_controls();
		$this->add_variation_style_controls();
		$this->add_variation_label_style_controls();
		$this->add_quantity_style_controls();
		$this->template( Button::class )->add_style_controls(
			Button::ICON_MANAGER,
			[],
			[
				'gap_above_button' => null,
				'button_size'      => [
					'default' => 'm',
				],
			]
		);
	}

	/**
	 * @return void
	 */
	protected function add_variation_grid_styles_controls() {
		$this->start_controls_section(
			'item_count_section',
			[
				'label'     => __( 'Grid & Inline Variations', 'the7mk2' ),
				'tab'       => Controls_Manager::TAB_STYLE,
				'condition' => [
					'layout!' => 'dropdown',
				],
			]
		);

		$selector = '{{WRAPPER}} .the7-vr-options a';

		$this->add_group_control(
			Group_Control_Typography::get_type(),
			[
				'name'     => 'variations_typography',
				'selector' => $selector,
			]
		);

		$this->add_control(
			'variations_min_width',
			[
				'label'      => __( 'Min Width', 'the7mk2' ),
				'type'       => Controls_Manager::SLIDER,
				'size_units' => [ 'px' ],
				'range'      => [
					'px' => [
						'min' => 0,
						'max' => 200,
					],
				],
				'selectors'  => [
					$selector => 'min-width: {{SIZE}}{{UNIT}};',
				],
				'condition'  => [
					'layout!' => 'columns',
				],
			]
		);

		$this->add_control(
			'variations_min_height',
			[
				'label'      => __( 'Min Height', 'the7mk2' ),
				'type'       => Controls_Manager::SLIDER,
				'size_units' => [ 'px' ],
				'range'      => [
					'px' => [
						'min' => 0,
						'max' => 200,
					],
				],
				'selectors'  => [
					$selector => 'min-height: {{SIZE}}{{UNIT}};',
				],
			]
		);

		$this->add_control(
			'variations_border_width',
			[
				'label'      => __( 'Border Width', 'the7mk2' ),
				'type'       => Controls_Manager::SLIDER,
				'size_units' => [ 'px' ],
				'range'      => [
					'px' => [
						'min' => 0,
						'max' => 25,
					],
				],
				'separator'  => 'before',
				'selectors'  => [
					'{{WRAPPER}}' => '--variations-border-width: {{SIZE}}{{UNIT}};',
					$selector     => 'border-style: solid; border-width: {{SIZE}}{{UNIT}};',
				],
			]
		);

		$this->add_control(
			'variations_border_radius',
			[
				'label'      => __( 'Border Radius', 'the7mk2' ),
				'type'       => Controls_Manager::SLIDER,
				'size_units' => [ 'px' ],
				'range'      => [
					'px' => [
						'min' => 0,
						'max' => 100,
					],
				],
				'selectors'  => [
					$selector => 'border-radius: {{SIZE}}{{UNIT}}',
				],
			]
		);

		$this->add_responsive_control(
			'variations_padding',
			[
				'label'      => __( 'Padding', 'the7mk2' ),
				'type'       => Controls_Manager::DIMENSIONS,
				'size_units' => [ 'px', '%' ],
				'range'      => [
					'px' => [
						'min' => 0,
						'max' => 50,
					],
					'%'  => [
						'min' => 0,
						'max' => 100,
					],
				],
				'selectors'  => [
					$selector => 'padding: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}}',
				],
			]
		);

		$this->start_controls_tabs( 'variations_tabs_style' );
		$this->add_variations_tab_controls( 'normal_', __( 'Normal', 'the7mk2' ) );
		$this->add_variations_tab_controls( 'hover_', __( 'Hover', 'the7mk2' ) );
		$this->add_variations_tab_controls( 'active_', __( 'Selected', 'the7mk2' ) );
		$this->add_variations_tab_controls( 'of_stock_', __( 'Out Of Stock', 'the7mk2' ) );
		$this->end_controls_tabs();

		$this->end_controls_section();
	}

	/**
	 * @return void
	 */
	protected function add_variation_dropdown_styles_controls() {
		$this->start_controls_section(
			'item_dropdown_section',
			[
				'label'     => __( 'Dropdown variations', 'the7mk2' ),
				'tab'       => Controls_Manager::TAB_STYLE,
				'condition' => [
					'layout' => 'dropdown',
				],
			]
		);

		$selector = '{{WRAPPER}} .variations select';

		$this->add_group_control(
			Group_Control_Typography::get_type(),
			[
				'name'     => 'dropdown_variations_typography',
				'selector' => $selector,
			]
		);

		$this->add_control(
			'icon',
			[
				'label'       => __( 'Icon', 'the7mk2' ),
				'type'        => Controls_Manager::ICONS,
				'default'     => [
					'value'   => 'fas fa-caret-down',
					'library' => 'fa-solid',
				],
				'skin'        => 'inline',
				'label_block' => false,
				'condition'   => [
					'layout' => 'dropdown',
				],
			]
		);

		$this->add_responsive_control(
			'dropdown_variations_icon_size',
			[
				'label'     => __( 'Icon Size', 'the7mk2' ),
				'type'      => Controls_Manager::SLIDER,
				'range'     => [
					'px' => [
						'min' => 0,
						'max' => 100,
					],
				],
				'selectors' => [
					'{{WRAPPER}}' => '--icon-size: {{SIZE}}{{UNIT}};',
					'{{WRAPPER}} .the7-wc-variation-select i' => 'font-size: {{SIZE}}{{UNIT}};',
					'{{WRAPPER}} .the7-wc-variation-select svg' => 'width: {{SIZE}}{{UNIT}};',
				],
				'condition' => [
					'layout' => 'dropdown',
				],
			]
		);

		$this->add_control( 'min_width_top_divider', [ 'type' => Controls_Manager::DIVIDER ] );

		$this->add_responsive_control(
			'dropdown_variations_min_width',
			[
				'label'      => __( 'Min Width', 'the7mk2' ),
				'type'       => Controls_Manager::SLIDER,
				'size_units' => [ 'px' ],
				'range'      => [
					'px' => [
						'min' => 0,
						'max' => 900,
					],
				],
				'selectors'  => [
					$selector => 'min-width: {{SIZE}}{{UNIT}};',
				],
			]
		);

		$this->add_responsive_control(
			'dropdown_variations_min_height',
			[
				'label'      => __( 'Min Height', 'the7mk2' ),
				'type'       => Controls_Manager::SLIDER,
				'size_units' => [ 'px' ],
				'range'      => [
					'px' => [
						'min' => 0,
						'max' => 200,
					],
				],
				'selectors'  => [
					$selector => 'min-height: {{SIZE}}{{UNIT}};',
				],
			]
		);

		$this->add_control(
			'dropdown_variations_border_width',
			[
				'label'      => __( 'Border Width', 'the7mk2' ),
				'type'       => Controls_Manager::SLIDER,
				'size_units' => [ 'px' ],
				'range'      => [
					'px' => [
						'min' => 0,
						'max' => 25,
					],
				],
				'separator'  => 'before',
				'selectors'  => [
					$selector => 'border-style: solid; border-width: {{SIZE}}{{UNIT}};',
				],
			]
		);

		$this->add_control(
			'dropdown_variations_border_radius',
			[
				'label'      => __( 'Border Radius', 'the7mk2' ),
				'type'       => Controls_Manager::SLIDER,
				'size_units' => [ 'px' ],
				'range'      => [
					'px' => [
						'min' => 0,
						'max' => 100,
					],
				],
				'selectors'  => [
					$selector => 'border-radius: {{SIZE}}{{UNIT}}',
				],
			]
		);

		$this->add_responsive_control(
			'dropdown_variations_padding',
			[
				'label'      => __( 'Padding', 'the7mk2' ),
				'type'       => Controls_Manager::DIMENSIONS,
				'size_units' => [ 'px', '%' ],
				'range'      => [
					'px' => [
						'min' => 0,
						'max' => 50,
					],
					'%'  => [
						'min' => 0,
						'max' => 100,
					],
				],
				'selectors'  => [
					'{{WRAPPER}}' => '--the7-dropdown-padding-top: {{TOP}}{{UNIT}}; --the7-dropdown-padding-right: {{RIGHT}}{{UNIT}}; --the7-dropdown-padding-bottom: {{BOTTOM}}{{UNIT}}; --the7-dropdown-padding-left: {{LEFT}}{{UNIT}}',
					$selector     => 'padding: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}}',
				],
			]
		);

		$this->add_control(
			'dropdown_divider',
			[
				'type' => Controls_Manager::DIVIDER,
			]
		);

		$this->start_controls_tabs( 'dropdown_variations_tabs_style' );
		$this->add_dropdown_variations_tab_controls( 'normal_', __( 'Normal', 'the7mk2' ) );
		$this->add_dropdown_variations_tab_controls( 'hover_', __( 'Hover', 'the7mk2' ) );
		$this->end_controls_tabs();

		$this->end_controls_section();
	}

	/**
	 * @param string $prefix_name Prefix.
	 * @param string $box_name Tab label.
	 *
	 * @return void
	 */
	protected function add_variations_tab_controls( $prefix_name, $box_name ) {
		$extra_class = '';
		if ( $prefix_name === 'active_' ) {
			$extra_class .= '.active';
		}

		$extra_link_class = '';
		if ( $prefix_name === 'of_stock_' ) {
			$extra_link_class .= '.out-of-stock';
		}

		$is_hover = '';
		if ( $prefix_name === 'hover_' ) {
			$is_hover = ':hover';
		}
		$selector = '{{WRAPPER}} .the7-vr-options li' . $extra_class . ' a' . $extra_link_class . $is_hover;

		$this->start_controls_tab(
			$prefix_name . 'item_count_style',
			[
				'label' => $box_name,
			]
		);

		$this->add_control(
			$prefix_name . 'item_count_color',
			[
				'label'     => __( 'Text  Color', 'the7mk2' ),
				'type'      => Controls_Manager::COLOR,
				'alpha'     => true,
				'default'   => '',
				'selectors' => [
					$selector => 'color: {{VALUE}};',
				],
			]
		);

		$this->add_control(
			$prefix_name . 'item_count_background_color',
			[
				'label'     => __( 'Background Color', 'the7mk2' ),
				'type'      => Controls_Manager::COLOR,
				'selectors' => [
					$selector => 'background-color: {{VALUE}};',
				],
			]
		);

		$item_count_border_color_selectors = [
			$selector => 'border-color: {{VALUE}};',
		];

		if ( ! in_array( $prefix_name, [ 'hover_', 'active_' ], true ) ) {
			$item_count_border_color_selectors['{{WRAPPER}} .the7-vr-options'] = '--variations-border-color: {{VALUE}};';
		}

		$this->add_control(
			$prefix_name . 'item_count_border_color',
			[
				'label'     => __( 'Border Color', 'the7mk2' ),
				'type'      => Controls_Manager::COLOR,
				'selectors' => $item_count_border_color_selectors,
			]
		);

		$this->end_controls_tab();
	}

	/**
	 * @param string $prefix_name Prefix.
	 * @param string $box_name Tab label.
	 *
	 * @return void
	 */
	protected function add_dropdown_variations_tab_controls( $prefix_name, $box_name ) {
		$is_hover = '';
		if ( $prefix_name === 'hover_' ) {
			$is_hover = ':hover';
		}
		$selector      = '{{WRAPPER}} select' . $is_hover;
		$selector_icon = '{{WRAPPER}} .the7-wc-variation-select' . $is_hover . ' i';
		$selector_svg  = '{{WRAPPER}} .the7-wc-variation-select' . $is_hover . ' svg';

		$this->start_controls_tab(
			$prefix_name . 'dropdown_item_count_style',
			[
				'label' => $box_name,
			]
		);

		$this->add_control(
			$prefix_name . 'dropdown_item_count_color',
			[
				'label'     => __( 'Text  Color', 'the7mk2' ),
				'type'      => Controls_Manager::COLOR,
				'alpha'     => true,
				'default'   => '',
				'selectors' => [
					$selector => 'color: {{VALUE}};',
				],
			]
		);

		$this->add_control(
			$prefix_name . 'dropdown_variations_icon_color',
			[
				'label'     => __( 'Icon Color', 'the7mk2' ),
				'type'      => Controls_Manager::COLOR,
				'selectors' => [
					$selector_icon => 'color: {{VALUE}};',
					$selector_svg  => 'fill: {{VALUE}};',
				],
			]
		);

		$this->add_control(
			$prefix_name . 'dropdown_item_count_background_color',
			[
				'label'     => __( 'Background Color', 'the7mk2' ),
				'type'      => Controls_Manager::COLOR,
				'selectors' => [
					$selector => 'background-color: {{VALUE}};',
				],
			]
		);

		$this->add_control(
			$prefix_name . 'dropdown_item_count_border_color',
			[
				'label'     => __( 'Border Color', 'the7mk2' ),
				'type'      => Controls_Manager::COLOR,
				'selectors' => [
					$selector => 'border-color: {{VALUE}};',
				],
			]
		);

		$this->end_controls_tab();
	}

	/**
	 * @return void
	 */
	protected function add_variation_style_controls() {
		$this->start_controls_section(
			'variation_content_style',
			[
				'label' => __( 'Variation Content', 'the7mk2' ),
				'tab'   => Controls_Manager::TAB_STYLE,
			]
		);

		$this->add_control(
			'desc_heading',
			[
				'type'  => Controls_Manager::HEADING,
				'label' => __( 'Description', 'the7mk2' ),
			]
		);

		$this->add_group_control(
			Group_Control_Typography::get_type(),
			[
				'name'     => 'desc_typography',
				'label'    => __( 'Description Typography', 'the7mk2' ),
				'selector' => '{{WRAPPER}} .woocommerce-variation-description',
			]
		);

		$this->add_control(
			'desc_text_color',
			[
				'label'     => __( 'Description Color', 'the7mk2' ),
				'type'      => Controls_Manager::COLOR,
				'selectors' => [
					'{{WRAPPER}} .woocommerce-variation-description' => 'color: {{VALUE}};',
				],
			]
		);

		$this->add_control(
			'normal_price_heading',
			[
				'type'      => Controls_Manager::HEADING,
				'label'     => __( 'Normal Price', 'the7mk2' ),
				'separator' => 'before',
			]
		);

		$this->add_group_control(
			Group_Control_Typography::get_type(),
			[
				'name'     => 'price_typography',
				'label'    => __( 'Normal Price Typography', 'the7mk2' ),
				'selector' => '{{WRAPPER}} .price',
			]
		);

		$this->add_control(
			'normal_price_text_color',
			[
				'label'     => __( 'Normal Price Color', 'the7mk2' ),
				'type'      => Controls_Manager::COLOR,
				'selectors' => [
					'{{WRAPPER}} .price > span.woocommerce-Price-amount.amount, {{WRAPPER}} .price > span.woocommerce-Price-amount span, {{WRAPPER}} .price, {{WRAPPER}} .price ins span' => 'color: {{VALUE}};',
				],
			]
		);

		$this->add_control(
			'sale_price_heading',
			[
				'type'      => Controls_Manager::HEADING,
				'label'     => __( 'Sale Price', 'the7mk2' ),
				'separator' => 'before',
			]
		);

		$this->add_group_control(
			Group_Control_Typography::get_type(),
			[
				'name'     => 'sale_price_typography',
				'label'    => __( 'Old Price Typography', 'the7mk2' ),
				'selector' => '{{WRAPPER}} .price del span',
			]
		);

		$this->add_control(
			'sale_price_text_color',
			[
				'label'     => __( 'Old Price Color', 'the7mk2' ),
				'type'      => Controls_Manager::COLOR,
				'selectors' => [
					'{{WRAPPER}} .price del span' => 'color: {{VALUE}};',
				],
			]
		);

		$this->add_control(
			'old_price_line_color',
			[
				'label'     => __( 'Old Price Line Color', 'the7mk2' ),
				'type'      => Controls_Manager::COLOR,
				'selectors' => [
					'{{WRAPPER}} .price del' => 'color: {{VALUE}};',
				],
			]
		);

		$this->add_group_control(
			Group_Control_Typography::get_type(),
			[
				'name'     => 'sale_new_price_typography',
				'label'    => __( 'New Price Typography', 'the7mk2' ),
				'selector' => '{{WRAPPER}} .price ins span',
			]
		);

		$this->add_control(
			'sale_new_price_text_color',
			[
				'label'     => __( 'New Price Color', 'the7mk2' ),
				'type'      => Controls_Manager::COLOR,
				'selectors' => [
					'{{WRAPPER}} .price ins span' => 'color: {{VALUE}};',
				],
			]
		);

		$this->add_control(
			'outofstock_heading',
			[
				'type'      => Controls_Manager::HEADING,
				'label'     => __( 'Out Of Stock', 'the7mk2' ),
				'separator' => 'before',
			]
		);

		$this->add_group_control(
			Group_Control_Typography::get_type(),
			[
				'name'     => 'outofstock_typography',
				'label'    => __( 'Out Of Stock Typography', 'the7mk2' ),
				'selector' => '{{WRAPPER}} .stock.out-of-stock',
			]
		);

		$this->add_control(
			'outofstock_text_color',
			[
				'label'     => __( 'Out Of Stock Color', 'the7mk2' ),
				'type'      => Controls_Manager::COLOR,
				'selectors' => [
					'{{WRAPPER}} .stock.out-of-stock' => 'color: {{VALUE}};',
				],
			]
		);

		$this->add_control(
			'instock_heading',
			[
				'type'      => Controls_Manager::HEADING,
				'label'     => __( 'In Stock', 'the7mk2' ),
				'separator' => 'before',
			]
		);

		$this->add_group_control(
			Group_Control_Typography::get_type(),
			[
				'name'     => 'instock_typography',
				'label'    => __( 'In Stock Typography', 'the7mk2' ),
				'selector' => '{{WRAPPER}} .stock.in-stock',
			]
		);

		$this->add_control(
			'instock_text_color',
			[
				'label'     => __( 'In Stock Color', 'the7mk2' ),
				'type'      => Controls_Manager::COLOR,
				'selectors' => [
					'{{WRAPPER}} .stock.in-stock' => 'color: {{VALUE}};',
				],
			]
		);

		$this->end_controls_section();
	}

	/**
	 * @return void
	 */
	protected function add_variation_label_style_controls() {
		$this->start_controls_section(
			'section_label_style',
			[
				'label'     => esc_html__( 'Variation Label', 'the7mk2' ),
				'tab'       => Controls_Manager::TAB_STYLE,
				'condition' => [
					'show_labels' => 'yes',
				],
			]
		);

		$this->add_group_control(
			Group_Control_Typography::get_type(),
			[
				'name'     => 'variations_label_typography',
				'selector' => '{{WRAPPER}} form.cart table.variations label',
			]
		);

		$this->add_responsive_control(
			'label_min_width',
			[
				'label'      => __( 'Min Width', 'the7mk2' ),
				'type'       => Controls_Manager::SLIDER,
				'size_units' => [ 'px' ],
				'range'      => [
					'px' => [
						'min' => 0,
						'max' => 200,
					],
				],
				'selectors'  => [
					'{{WRAPPER}} .variations label' => 'min-width: {{SIZE}}{{UNIT}};',
				],
			]
		);

		$this->add_control(
			'label_spacing',
			[
				'label'     => esc_html__( 'Spacing', 'the7mk2' ),
				'type'      => Controls_Manager::SLIDER,
				'selectors' => [
					'{{WRAPPER}}' => '--label-spacing: {{SIZE}}{{UNIT}}',
				],
			]
		);

		$this->add_control(
			'variations_label_color',
			[
				'label'     => esc_html__( 'Color', 'the7mk2' ),
				'type'      => Controls_Manager::COLOR,
				'selectors' => [
					'{{WRAPPER}} form.cart table.variations label' => 'color: {{VALUE}}',
				],
			]
		);

		$this->end_controls_section();
	}

	/**
	 * @return void
	 */
	protected function add_layout_content_controls() {
		$this->start_controls_section(
			'section_layout',
			[
				'label' => __( 'General', 'the7mk2' ),
			]
		);

		$this->add_responsive_control(
			'alignment',
			[
				'label'                => __( 'Alignment', 'the7mk2' ),
				'type'                 => Controls_Manager::CHOOSE,
				'default'              => 'left',
				'options'              => [
					'left'   => [
						'title' => __( 'Left', 'the7mk2' ),
						'icon'  => 'eicon-text-align-left',
					],
					'center' => [
						'title' => __( 'Center', 'the7mk2' ),
						'icon'  => 'eicon-text-align-center',
					],
				],
				'toggle'               => false,
				'device_args'          => [
					'tablet' => [
						'toggle' => true,
					],
					'mobile' => [
						'toggle' => true,
					],
				],
				'selectors_dictionary' => [
					'left'   => 'text-align: left; justify-content: flex-start; --content-align: flex-start;',
					'center' => 'text-align: center; justify-content: center; --content-align: center;',
				],
				'selectors'            => [
					'{{WRAPPER}} .the7-add-to-cart, {{WRAPPER}} .woocommerce-variation-add-to-cart' => '{{VALUE}}',
				],
			]
		);

		$this->add_responsive_control(
			'variation_gap',
			[
				'label'      => __( 'Vertical Spacing', 'the7mk2' ),
				'type'       => Controls_Manager::SLIDER,
				'size_units' => [ 'px' ],
				'range'      => [
					'px' => [
						'min' => 0,
						'max' => 100,
					],
				],
				'selectors'  => [
					'{{WRAPPER}} .variations tr, {{WRAPPER}} .woocommerce-variation > .last, {{WRAPPER}} .the7-add-to-cart > .in-stock' => 'margin-bottom: {{SIZE}}{{UNIT}};',
				],
			]
		);

		$this->add_control(
			'button_title',
			[
				'label'     => __( 'Button', 'the7mk2' ),
				'type'      => Controls_Manager::HEADING,
				'separator' => 'before',
			]
		);

		$this->add_control(
			'button_text',
			[
				'label'       => __( 'Text', 'the7mk2' ),
				'type'        => Controls_Manager::TEXT,
				'default'     => __( 'Add to cart', 'the7mk2' ),
				'placeholder' => __( 'Add to cart', 'the7mk2' ),
			]
		);

		$button_width_options            = [
			'inline'  => __( 'Default', 'the7mk2' ),
			'stretch' => __( 'Stretch', 'the7mk2' ),
		];
		$button_width_options_on_devices = [ '' => __( 'No change', 'the7mk2' ) ] + $button_width_options;

		$this->add_responsive_control(
			'button_width',
			[
				'label'                => __( 'Width', 'the7mk2' ),
				'type'                 => Controls_Manager::SELECT,
				'options'              => $button_width_options,
				'device_args'          => [
					'tablet' => [
						'default' => '',
						'options' => $button_width_options_on_devices,
					],
					'mobile' => [
						'default' => '',
						'options' => $button_width_options_on_devices,
					],
				],
				'default'              => 'inline',
				'selectors_dictionary' => [
					'inline'  => 'width: auto;',
					'stretch' => 'width: 100%;',
				],
				'selectors'            => [
					'{{WRAPPER}} .box-button' => ' {{VALUE}};',
				],
			]
		);

		$this->add_control(
			'quantity_title',
			[
				'label'     => __( 'Quantity', 'the7mk2' ),
				'type'      => Controls_Manager::HEADING,
				'separator' => 'before',
			]
		);

		$this->add_control(
			'show_quantity',
			[
				'label'                => __( 'Visibility', 'the7mk2' ),
				'type'                 => Controls_Manager::SWITCHER,
				'label_on'             => __( 'Show', 'the7mk2' ),
				'label_off'            => __( 'Hide', 'the7mk2' ),
				'return_value'         => 'yes',
				'default'              => 'yes',
				'selectors_dictionary' => [
					'yes' => 'display: inline-flex;',
					''    => 'display: none !important;',
				],
				'selectors'            => [
					'{{WRAPPER}} .quantity, {{WRAPPER}} .woocommerce-variation-add-to-cart .quantity' => '{{VALUE}}',
				],
			]
		);

		$this->add_responsive_control(
			'quantity_position',
			[
				'label'                => __( 'Position', 'the7mk2' ),
				'type'                 => Controls_Manager::CHOOSE,
				'options'              => [
					'inline'  => [
						'title' => __( 'Inline', 'the7mk2' ),
						'icon'  => 'eicon-h-align-left',
					],
					'stacked' => [
						'title' => __( 'Stacked', 'the7mk2' ),
						'icon'  => 'eicon-v-align-top',
					],
				],
				'default'              => 'inline',
				'selectors_dictionary' => [
					'stacked' => 'flex-flow: column; --quantity-gap: 0 0 var(--quantity-spacing, 30px) 0; align-items: var(--content-align);',
					'inline'  => 'flex-flow: row nowrap; align-items: center; --quantity-gap: 0 var(--quantity-spacing, 30px) 0 0; justify-content: var(--content-align);',
				],
				'selectors'            => [
					'{{WRAPPER}} form.cart.variations_form .woocommerce-variation-add-to-cart, {{WRAPPER}} form.cart:not(.grouped_form):not(.variations_form)' => '{{VALUE}}',
				],
				'condition'            => [
					'show_quantity' => 'yes',
				],
			]
		);

		$quantity_width_options            = [
			'inline'  => __( 'Default', 'the7mk2' ),
			'stretch' => __( 'Stretch', 'the7mk2' ),
		];
		$quantity_width_options_on_devices = [ '' => __( 'No change', 'the7mk2' ) ] + $quantity_width_options;

		$this->add_responsive_control(
			'quantity_width',
			[
				'label'                => __( 'Width', 'the7mk2' ),
				'type'                 => Controls_Manager::SELECT,
				'options'              => $quantity_width_options,
				'device_args'          => [
					'tablet' => [
						'default' => '',
						'options' => $quantity_width_options_on_devices,
					],
					'mobile' => [
						'default' => '',
						'options' => $quantity_width_options_on_devices,
					],
				],
				'default'              => 'inline',
				'selectors_dictionary' => [
					'inline'  => ' width: auto;',
					'stretch' => ' width: 100%;',
				],
				'selectors'            => [
					'{{WRAPPER}} .quantity, {{WRAPPER}} .woocommerce-variation-add-to-cart .quantity' => ' {{VALUE}};',
				],
				'condition'            => [
					'quantity_position' => 'stacked',
					'show_quantity'     => 'yes',
				],
			]
		);

		$this->add_control(
			'variation_title',
			[
				'label'     => __( 'Variations', 'the7mk2' ),
				'type'      => Controls_Manager::HEADING,
				'separator' => 'before',
			]
		);

		$this->add_control(
			'show_labels',
			[
				'label'                => __( 'Label', 'the7mk2' ),
				'type'                 => Controls_Manager::SWITCHER,
				'label_on'             => __( 'Show', 'the7mk2' ),
				'label_off'            => __( 'Hide', 'the7mk2' ),
				'return_value'         => 'yes',
				'default'              => 'yes',
				'selectors_dictionary' => [
					'yes' => '',
					''    => 'display: none;',
				],
				'selectors'            => [
					'{{WRAPPER}} .variations th' => '{{VALUE}}',
				],
			]
		);

		$this->add_responsive_control(
			'label_position',
			[
				'label'                => __( 'Label Position', 'the7mk2' ),
				'type'                 => Controls_Manager::CHOOSE,
				'options'              => [
					'start' => [
						'title' => __( 'Left', 'the7mk2' ),
						'icon'  => 'eicon-h-align-left',
					],
					'top'   => [
						'title' => __( 'Top', 'the7mk2' ),
						'icon'  => 'eicon-v-align-top',
					],
				],
				'selectors_dictionary' => [
					'top'   => 'display: flex; flex-flow: column wrap; justify-content: var(--content-align); align-items: var(--content-align); --label-margin: 0 0 var(--label-spacing, 10px) 0;',
					'start' => 'display: flex;  flex-flow: row nowrap; justify-content: var(--content-align); align-items: center; --label-margin: 0 var(--label-spacing, 10px) 0 0;',
				],
				'selectors'            => [
					'{{WRAPPER}} .variations tr' => '{{VALUE}}',
				],
				'condition'            => [
					'show_labels' => 'yes',
				],
				'default'              => 'top',
			]
		);

		$this->add_control(
			'layout',
			[
				'label'                => __( 'Layout', 'the7mk2' ),
				'type'                 => Controls_Manager::SELECT,
				'default'              => 'dropdown',
				'options'              => [
					'dropdown' => __( 'Dropdown', 'the7mk2' ),
					'columns'  => __( 'Columns', 'the7mk2' ),
					'inline'   => __( 'Inline', 'the7mk2' ),
				],
				'selectors_dictionary' => [
					'dropdown' => $this->combine_to_css_vars_definition_string(
						[
							'display' => 'none',
						]
					),
					'columns'  => $this->combine_to_css_vars_definition_string(
						[
							'display'      => 'grid',
							'item-display' => 'flex',
							'list-width'   => '100%',
							'td-width'     => '100%',
						]
					),
					'inline'   => $this->combine_to_css_vars_definition_string(
						[
							'display'      => 'flex',
							'item-display' => 'inline-flex',
							'list-width'   => '100%',
						]
					),
				],
				'selectors'            => [
					'{{WRAPPER}}' => '{{VALUE}}',
				],
				'render_type'          => 'template',
				'prefix_class'         => 'variations-layout-',
			]
		);

		$this->add_responsive_control(
			'columns',
			[
				'label'          => __( 'Columns', 'the7mk2' ),
				'type'           => Controls_Manager::NUMBER,
				'default'        => 1,
				'tablet_default' => 1,
				'mobile_default' => 1,
				'min'            => 1,
				'max'            => 12,
				'selectors'      => [
					'{{WRAPPER}} .the7-vr-options' => 'grid-template-columns: repeat({{SIZE}},1fr)',
					'{{WRAPPER}}'                  => '--wide-desktop-columns: {{SIZE}}',
				],
				'render_type'    => 'template',
				'condition'      => [
					'layout' => 'columns',
				],
			]
		);

		$this->add_responsive_control(
			'columns_gap',
			[
				'label'      => __( 'Columns Gap', 'the7mk2' ),
				'type'       => Controls_Manager::SLIDER,
				'size_units' => [ 'px' ],
				'default'    => [
					'size' => '10',
				],
				'range'      => [
					'px' => [
						'max' => 100,
					],
				],
				'selectors'  => [
					'{{WRAPPER}} .the7-vr-options' => ' column-gap: {{SIZE}}{{UNIT}};',
				],
				'condition'  => [
					'layout!' => 'dropdown',
				],
			]
		);

		$this->add_responsive_control(
			'rows_gap',
			[
				'label'      => __( 'Rows Gap', 'the7mk2' ),
				'type'       => Controls_Manager::SLIDER,
				'size_units' => [ 'px' ],
				'default'    => [
					'size' => '10',
				],
				'range'      => [
					'px' => [
						'max' => 100,
					],
				],
				'selectors'  => [
					'{{WRAPPER}} .the7-vr-options' => 'row-gap: {{SIZE}}{{UNIT}}; --grid-row-gap: {{SIZE}}{{UNIT}}',
				],
				'condition'  => [
					'layout!' => 'dropdown',
				],
			]
		);

		$variation_width_options            = [
			'inline'  => __( 'Default', 'the7mk2' ),
			'stretch' => __( 'Stretch', 'the7mk2' ),
		];
		$variation_width_options_on_devices = [ '' => __( 'No change', 'the7mk2' ) ] + $variation_width_options;

		$this->add_responsive_control(
			'variation_width',
			[
				'label'                => __( 'Width', 'the7mk2' ),
				'type'                 => Controls_Manager::SELECT,
				'options'              => $variation_width_options,
				'device_args'          => [
					'tablet' => [
						'default' => '',
						'options' => $variation_width_options_on_devices,
					],
					'mobile' => [
						'inline'  => '',
						'options' => $variation_width_options_on_devices,
					],
				],
				'default'              => 'inline',
				'selectors_dictionary' => [
					'inline'  => 'width: auto;',
					'stretch' => 'width: 100%;',
				],
				'selectors'            => [
					'{{WRAPPER}} .the7-wc-variation-select, {{WRAPPER}} .variations td.value, {{WRAPPER}} .the7-wc-variation-select select' => ' {{VALUE}};',
				],
				'condition'            => [
					'layout' => 'dropdown',
				],
			]
		);

		$this->end_controls_section();
	}

	/**
	 * @return void
	 */
	protected function add_quantity_style_controls() {
		$this->start_controls_section(
			'section_atc_quantity_style',
			[
				'label' => esc_html__( 'Quantity', 'the7mk2' ),
				'tab'   => Controls_Manager::TAB_STYLE,
			]
		);

		$this->add_responsive_control(
			'quantity_spacing',
			[
				'label'      => esc_html__( 'Spacing', 'the7mk2' ),
				'type'       => Controls_Manager::SLIDER,
				'size_units' => [ 'px', 'em' ],
				'selectors'  => [
					'{{WRAPPER}}' => '--quantity-spacing: {{SIZE}}{{UNIT}}',
				],
				'separator'  => 'after',
			]
		);

		$this->add_control(
			'quantity_heading',
			[
				'type'  => Controls_Manager::HEADING,
				'label' => __( 'Box & Number', 'the7mk2' ),
			]
		);

		$this->add_group_control(
			Group_Control_Typography::get_type(),
			[
				'name'     => 'quantity_typography',
				'selector' => '{{WRAPPER}} .quantity .qty',
			]
		);

		$this->add_responsive_control(
			'quantity_min_width',
			[
				'label'      => __( 'Min Width', 'the7mk2' ),
				'type'       => Controls_Manager::SLIDER,
				'size_units' => [ 'px' ],
				'range'      => [
					'px' => [
						'min' => 0,
						'max' => 200,
					],
				],
				'selectors'  => [
					'{{WRAPPER}} .quantity' => '--quantity-width: {{SIZE}}{{UNIT}}; min-width: {{SIZE}}{{UNIT}};',
				],
				'conditions' => [
					'relation' => 'or',
					'terms'    => [
						[
							'name'  => 'quantity_width',
							'value' => 'inline',
						],
						[
							'name'  => 'quantity_width_tablet',
							'value' => 'inline',
						],
						[
							'name'  => 'quantity_width_mobile',
							'value' => 'inline',
						],
						[
							'name'  => 'quantity_position',
							'value' => 'inline',
						],
						[
							'name'  => 'quantity_position_tablet',
							'value' => 'inline',
						],
						[
							'name'  => 'quantity_position_mobile',
							'value' => 'inline',
						],
					],
				],
			]
		);

		$this->add_responsive_control(
			'quantity_min_height',
			[
				'label'      => __( 'Min Height', 'the7mk2' ),
				'type'       => Controls_Manager::SLIDER,
				'size_units' => [ 'px' ],
				'range'      => [
					'px' => [
						'min' => 0,
						'max' => 200,
					],
				],
				'selectors'  => [
					'{{WRAPPER}} .quantity' => 'min-height: {{SIZE}}{{UNIT}};',
				],
			]
		);

		$this->add_control(
			'quantity_paddings',
			[
				'label'     => esc_html__( 'Padding', 'the7mk2' ),
				'type'      => Controls_Manager::DIMENSIONS,
				'selectors' => [
					'{{WRAPPER}} .quantity' => 'padding: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};',
				],
			]
		);

		$this->add_group_control(
			Group_Control_Border::get_type(),
			[
				'name'           => 'quantity_border',
				'fields_options' => [
					'width' => [
						'selectors' => [
							'{{SELECTOR}}' => '--the7-top-input-border-width: {{TOP}}{{UNIT}}; --the7-right-input-border-width: {{RIGHT}}{{UNIT}}; --the7-bottom-input-border-width: {{BOTTOM}}{{UNIT}}; --the7-left-input-border-width: {{LEFT}}{{UNIT}}; border-width: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};',
						],
					],
				],
				'selector'       => '{{WRAPPER}} .quantity',
				'exclude'        => [ 'color' ],
			]
		);

		$this->add_responsive_control(
			'quantity_border_radius',
			[
				'label'      => __( 'Border Radius', 'the7mk2' ),
				'type'       => Controls_Manager::SLIDER,
				'size_units' => [ 'px' ],
				'range'      => [
					'px' => [
						'min' => 0,
						'max' => 200,
					],
				],
				'selectors'  => [
					'{{WRAPPER}} .quantity' => 'border-radius: {{SIZE}}{{UNIT}};',
				],
			]
		);

		$this->add_control(
			'quantity_text_color',
			[
				'label'     => esc_html__( 'Number Color', 'the7mk2' ),
				'type'      => Controls_Manager::COLOR,
				'selectors' => [
					'{{WRAPPER}} .quantity .qty' => 'color: {{VALUE}}',
				],
			]
		);

		$this->add_control(
			'quantity_bg_color',
			[
				'label'     => esc_html__( 'Background', 'the7mk2' ),
				'type'      => Controls_Manager::COLOR,
				'selectors' => [
					'{{WRAPPER}} .quantity' => 'background: {{VALUE}}',
				],
			]
		);

		$this->add_control(
			'quantity_border_color',
			[
				'label'     => esc_html__( 'Border Color', 'the7mk2' ),
				'type'      => Controls_Manager::COLOR,
				'selectors' => [
					'{{WRAPPER}} .quantity' => 'border-color: {{VALUE}}',
				],
			]
		);

		$this->add_group_control(
			Group_Control_Box_Shadow::get_type(),
			[
				'name'     => 'quantity_box_shadow',
				'label'    => __( 'Box Shadow', 'the7mk2' ),
				'selector' => '{{WRAPPER}} .quantity',
			]
		);

		$this->add_control(
			'quantity_button_heading',
			[
				'type'      => Controls_Manager::HEADING,
				'label'     => __( '+/- Settings', 'the7mk2' ),
				'separator' => 'before',
			]
		);

		$this->add_responsive_control(
			'quantity_button_icon_width',
			[
				'label'      => __( 'Icon Size', 'the7mk2' ),
				'type'       => Controls_Manager::SLIDER,
				'size_units' => [ 'px' ],
				'range'      => [
					'px' => [
						'min' => 0,
						'max' => 200,
					],
				],
				'selectors'  => [
					'{{WRAPPER}} .quantity .button' => 'font-size: {{SIZE}}{{UNIT}};',
				],
			]
		);

		$this->add_responsive_control(
			'quantity_button_width',
			[
				'label'      => __( 'Background Width', 'the7mk2' ),
				'type'       => Controls_Manager::SLIDER,
				'size_units' => [ 'px' ],
				'range'      => [
					'px' => [
						'min' => 0,
						'max' => 200,
					],
				],
				'selectors'  => [
					'{{WRAPPER}}'                   => '--quantity-btn-width: {{SIZE}}{{UNIT}};',
					'{{WRAPPER}} .quantity .button' => 'width: {{SIZE}}{{UNIT}};',
				],
			]
		);

		$this->add_responsive_control(
			'quantity_button_height',
			[
				'label'      => __( 'Background Height', 'the7mk2' ),
				'type'       => Controls_Manager::SLIDER,
				'size_units' => [ 'px' ],
				'range'      => [
					'px' => [
						'min' => 0,
						'max' => 200,
					],
				],
				'selectors'  => [
					'{{WRAPPER}}'                   => '--quantity-btn-height: {{SIZE}}{{UNIT}};',
					'{{WRAPPER}} .quantity .button' => 'height: {{SIZE}}{{UNIT}} !important;',
				],
			]
		);

		$this->add_responsive_control(
			'quantity_button_border',
			[
				'label'      => __( 'Border Width', 'the7mk2' ),
				'type'       => Controls_Manager::SLIDER,
				'size_units' => [ 'px' ],
				'range'      => [
					'px' => [
						'min' => 0,
						'max' => 200,
					],
				],
				'selectors'  => [
					'{{WRAPPER}}' => '--quantity-btn-border-width: {{SIZE}}{{UNIT}};',
				],
			]
		);

		$this->add_responsive_control(
			'quantity_button_border_radius',
			[
				'label'      => __( 'Border Radius', 'the7mk2' ),
				'type'       => Controls_Manager::SLIDER,
				'size_units' => [ 'px' ],
				'range'      => [
					'px' => [
						'min' => 0,
						'max' => 200,
					],
				],
				'selectors'  => [
					'{{WRAPPER}} .quantity .button' => 'border-radius: {{SIZE}}{{UNIT}} !important;',
				],
			]
		);

		$this->start_controls_tabs( 'quantity_style_tabs' );

		$this->start_controls_tab(
			'quantity_style_normal',
			[
				'label' => esc_html__( 'Normal', 'the7mk2' ),
			]
		);

		$this->add_control(
			'quantity_btn_color',
			[
				'label'     => esc_html__( '+/- Color', 'the7mk2' ),
				'type'      => Controls_Manager::COLOR,
				'selectors' => [
					'{{WRAPPER}} .quantity .button' => 'color: {{VALUE}}',
				],
			]
		);

		$this->add_control(
			'quantity_bg_btn',
			[
				'label'     => esc_html__( '+/- Background', 'the7mk2' ),
				'type'      => Controls_Manager::COLOR,
				'selectors' => [
					'#page {{WRAPPER}} .woocommerce-variation-add-to-cart .quantity input[type="button"].is-form, #page {{WRAPPER}} .the7-add-to-cart .quantity input[type="button"].is-form' => 'background: {{VALUE}}',
				],
			]
		);

		$this->add_control(
			'quantity_btn_border_color',
			[
				'label'     => esc_html__( '+/- Border Color', 'the7mk2' ),
				'type'      => Controls_Manager::COLOR,
				'selectors' => [
					'{{WRAPPER}}' => '--quantity-btn-border-color: {{VALUE}}',
				],
			]
		);

		$this->add_group_control(
			Group_Control_Box_Shadow::get_type(),
			[
				'name'           => 'quantity_btn_box_shadow',
				'label'          => __( '+/- Box Shadow', 'the7mk2' ),
				'selector'       => '{{WRAPPER}} .quantity .button',
				'fields_options' => [
					'box_shadow' => [
						'selectors' => [
							'{{SELECTOR}}' => 'box-shadow: {{HORIZONTAL}}px {{VERTICAL}}px {{BLUR}}px {{SPREAD}}px {{COLOR}} {{box_shadow_position.VALUE}} !important;',
						],
					],
				],
			]
		);

		$this->end_controls_tab();

		$this->start_controls_tab(
			'quantity_style_hover',
			[
				'label' => esc_html__( 'Hover', 'the7mk2' ),
			]
		);

		$this->add_control(
			'quantity_btn_color_focus',
			[
				'label'     => esc_html__( '+/- Color', 'the7mk2' ),
				'type'      => Controls_Manager::COLOR,
				'selectors' => [
					'{{WRAPPER}} .quantity .button:hover' => 'color: {{VALUE}}',
				],
			]
		);

		$this->add_control(
			'quantity_bg_btn_focus',
			[
				'label'     => esc_html__( '+/- Background', 'the7mk2' ),
				'type'      => Controls_Manager::COLOR,
				'selectors' => [
					'#page {{WRAPPER}} .woocommerce-variation-add-to-cart .quantity input[type="button"].is-form:hover, #page {{WRAPPER}} .the7-add-to-cart .quantity input[type="button"].is-form:hover' => 'background: {{VALUE}}',
				],
			]
		);

		$this->add_control(
			'quantity_border_color_focus',
			[
				'label'     => esc_html__( '+/- Border Color', 'the7mk2' ),
				'type'      => Controls_Manager::COLOR,
				'selectors' => [
					'{{WRAPPER}}' => '--quantity-btn-border-hover-color: {{VALUE}}',
				],
			]
		);

		$this->add_group_control(
			Group_Control_Box_Shadow::get_type(),
			[
				'name'           => 'quantity_box_shadow_focus',
				'label'          => __( '+/- Box Shadow', 'the7mk2' ),
				'selector'       => '{{WRAPPER}} .quantity .button:hover',
				'fields_options' => [
					'box_shadow' => [
						'selectors' => [
							'{{SELECTOR}}' => 'box-shadow: {{HORIZONTAL}}px {{VERTICAL}}px {{BLUR}}px {{SPREAD}}px {{COLOR}} {{box_shadow_position.VALUE}} !important;',
						],
					],
				],
			]
		);

		$this->end_controls_tab();

		$this->end_controls_tabs();

		$this->end_controls_section();
	}
}

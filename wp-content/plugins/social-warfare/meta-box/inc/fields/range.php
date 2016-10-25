<?php
/**
 * HTML5 range field class.
 */
class SWPMB_Range_Field extends SWPMB_Number_Field
{
	/**
	 * Get field HTML
	 *
	 * @param mixed $meta
	 * @param array $field
	 * @return string
	 */
	static function html( $meta, $field )
	{
		$output  = parent::html( $meta, $field );
		$output .= sprintf( '<span class="swpmb-output">%s</span>', $meta );
		return $output;
	}

	/**
	 * Enqueue styles
	 */
	static function admin_enqueue_scripts()
	{
		wp_enqueue_style( 'swpmb-range', SWPMB_CSS_URL . 'range.css', array(), SWPMB_VER );
		wp_enqueue_script( 'swpmb-range', SWPMB_JS_URL . 'range.js', array(), SWPMB_VER, true );
	}

	/**
	 * Normalize parameters for field.
	 *
	 * @param array $field
	 *
	 * @return array
	 */
	static function normalize( $field )
	{
		$field = wp_parse_args( $field, array(
			'min'  => 0,
			'max'  => 10,
			'step' => 1,
		) );

		$field = parent::normalize( $field );

		return $field;
	}

	/**
	 * Get the attributes for a field
	 *
	 * @param array $field
	 * @param mixed $value
	 *
	 * @return array
	 */
	static function get_attributes( $field, $value = null )
	{
		$attributes = parent::get_attributes( $field, $value );
		$attributes['type'] = 'range';

		return $attributes;
	}

	/**
	 * Ensure number in range.
	 *
	 * @param mixed $new
	 * @param mixed $old
	 * @param int   $post_id
	 * @param array $field
	 *
	 * @return int
	 */
	static function value( $new, $old, $post_id, $field )
	{
		$new = intval( $new );
		$min = intval( $field['min'] );
		$max = intval( $field['max'] );

		if ( $new < $min )
		{
			return $min;
		}
		elseif ( $new > $max )
		{
			return $max;
		}

		return $new;
	}
}

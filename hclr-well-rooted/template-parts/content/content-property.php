<?php
/**
 * Property card partial.
 * Usage: get_template_part( 'template-parts/content/content', 'property' );
 *
 * @package HCLR\WellRooted
 */

echo hclr_render_property_card( get_the_ID() ); // phpcs:ignore WordPress.Security.EscapeOutput

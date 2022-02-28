<?php
/**
 * Block class.
 *
 * @package SiteCounts
 */

namespace XWP\SiteCounts;

use WP_Block;
use WP_Query;

/**
 * The Site Counts dynamic block.
 *
 * Registers and renders the dynamic block.
 */
class Block {

	/**
	 * The Plugin instance.
	 *
	 * @var Plugin
	 */
	protected $plugin;

	/**
	 * Instantiates the class.
	 *
	 * @param Plugin $plugin The plugin object.
	 */
	public function __construct( $plugin ) {
		$this->plugin = $plugin;
	}

	/**
	 * Adds the action to register the block.
	 *
	 * @return void
	 */
	public function init() {
		add_action( 'init', [ $this, 'register_block' ] );
	}

	/**
	 * Registers the block.
	 */
	public function register_block() {
		register_block_type_from_metadata(
			$this->plugin->dir(),
			[
				'render_callback' => [ $this, 'render_callback' ],
			]
		);
	}

	/**
	 * Renders the block.
	 *
	 * @param array    $attributes The attributes for the block.
	 * @param string   $content    The block content, if any.
	 * @param WP_Block $block      The instance of this block.
	 * @return string The markup of the block.
	 */
	public function render_callback( $attributes, $content, $block ) {
		$post_types = get_post_types( array( 'public' => true ) );

		// Return if there are no post types available.
		if ( empty( $post_types ) || ! is_array( $post_types ) ) {
			return;
		}

		$class_name = ( ! empty( $attributes['className'] ) ) ? $attributes['className'] : '';
		$post_id    = filter_input( INPUT_GET, 'post_id', FILTER_INPUT_SANITIZE_NUMBER_INT );
		ob_start();
		?>
		<div class="<?php echo esc_attr( $class_name ); ?>">
			<h2><?php esc_html_e( 'Post Counts', 'site-counts' ); ?></h2>
			<ul>
				<?php
				// Iterate to the posttypes.
				foreach ( $post_types as $post_type_slug ) :
					$post_type_object = get_post_type_object( $post_type_slug );
					$post_count       = count(
						get_posts(
							array(
								'post_type'      => $post_type_slug,
								'posts_per_page' => get_option( 'post_per_page' ),
							)
						)
					);
					?>
					<li>
						<?php
						/* translators: 1: %d: count of posts, 2: %s: post type label */
						echo esc_html( sprintf( __( 'There are %1$d %2$s.', 'site-counts' ), $post_count, $post_type_object->labels->name ) );
						?>
					</li>
				<?php endforeach; ?>
			</ul>
			<p>
				<?php
				/* translators: 1: %d: count of post ID. */
				echo esc_html( sprintf( __( 'The current post ID is %1$d.', 'site-counts' ), $post_id ) );
				?>
			</p>

			<?php
			$this_query = new WP_Query(
				array(
					'post_type'     => array( 'post', 'page' ),
					'post_status'   => 'any',
					'date_query'    => array(
						array(
							'hour'    => 9,
							'compare' => '>=',
						),
						array(
							'hour'    => 17,
							'compare' => '<=',
						),
					),
					'tag'           => 'foo',
					'category_name' => 'baz',
					'post__not_in'  => array( get_the_ID() ),
				)
			);

			// If posts are available.
			if ( $this_query->have_posts() ) :
				?>
				<h2><?php esc_html_e( '5 posts with the tag of foo and the category of baz', 'site-counts' ); ?></h2>
				<ul>
					<?php
					// Iterate to the post.
					foreach ( array_slice( $this_query->posts, 0, 5 ) as $post ) :
						?>
						<li><?php echo esc_html( $post->post_title ); ?></li>
						<?php
					endforeach;
					?>
				</ul>
				<?php
			endif;
			?>
		</div>
		<?php

		return ob_get_clean();
	}
}

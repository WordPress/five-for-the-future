<?php

namespace WordPressDotOrg\FiveForTheFuture\View;
use WP_Post;

defined( 'WPINC' ) || die();

/**
 * @var WP_Post[] $stat_values
 */

?>

<p style="color: red; font-weight: bold; border: 2px solid red; padding: 6px 5px 3px 5px;">
	This is deprecated, and stats are currently stored in MC. This only remains as a historical record.
</p>

<p>
	This is just rough text-based output to check that it's working in a way that will be friendly for the vizualization that will be added in #38 (and a11y fallbacks, if any are needed).
</p>

<p>
	When that is implemented, the controller can add the data to a JSON array with `date` => `value` entries, or whatever the visualization library wants, rather than looping through it below.
</p>

<?php

/*
Label the h3s w/ full text descriptions instead of slugs. rough ones:
	number of total pledged hours across all companies/teams/etc
	number of people contributing (regardless of how many hours
	number of companies contributing (regardless of how many hours
	# of contributors sponsored for each team

how to visualize teams? maybe a dropdown w/ each team, so not a huge long list of chart for each
*/

?>

<ul>
	<?php foreach ( $stat_values as $label => $values ) : ?>
		<h3>
			<?php echo esc_html( $label ); ?>
		</h3>

		<?php foreach ( $values as $timestamp => $value ) : ?>
			<li>
				<?php echo esc_html( date( 'Y-m-d', $timestamp ) ); ?> -

				<?php if ( is_array( $value ) ) : ?>
					<?php echo esc_html( print_r( $value, true ) ); ?>
				<?php else : ?>
					<?php echo esc_html( $value ); ?>
				<?php endif; ?>
			</li>
		<?php endforeach; ?>
	<?php endforeach; ?>
</ul>

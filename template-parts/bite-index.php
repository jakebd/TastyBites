<?php
/**
 * "How I rate": the Bite Rating index, written as a note from Shawn. The opening line is his own
 * (About page, "Today"); the bands come from tb_rating_bands().
 */
?>
<aside class="bite-index" id="how-i-rate" aria-labelledby="bite-index-heading">
	<p class="bite-index__stamp stamp stamp--tilt">The Bite Rating</p>
	<h2 id="bite-index-heading" class="bite-index__title">How I rate</h2>
	<p class="bite-index__intro">I rate restaurants on taste, presentation, value, how the food is cooked, and whether it's homemade. Here's what the numbers mean:</p>
	<dl class="bite-index__scale">
		<?php foreach ( tb_rating_bands() as $tb_band ) : ?>
			<div class="bite-index__band">
				<dt><span class="bite-index__range"><?php echo esc_html( $tb_band['range'] ); ?></span> <?php echo esc_html( $tb_band['verdict'] ); ?></dt>
				<dd><?php echo esc_html( $tb_band['note'] ); ?></dd>
			</div>
		<?php endforeach; ?>
	</dl>
	<p class="bite-index__sign">— Shawn</p>
</aside>

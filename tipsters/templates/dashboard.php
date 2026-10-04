<?php
/** Paginated owner-only tip dashboard. @package Cammino */
defined( 'ABSPATH' ) || exit;
$page = max( 1, absint( get_query_var( 'cammino_tips_page', 1 ) ) );
$tips = cammino_tipsters_own_tips( $page );
$statuses = cammino_tipsters_status_labels();
?>
<section class="cammino-tipsters__card" aria-labelledby="tipster-tips-title">
	<div class="cammino-tipsters__section-heading">
		<h2 id="tipster-tips-title"><?php esc_html_e( 'Moje tipy', 'cammino' ); ?></h2>
		<a class="button button--coral" href="<?php echo esc_url( cammino_tipsters_url( 'new' ) ); ?>"><?php esc_html_e( 'Nový tip', 'cammino' ); ?></a>
	</div>
	<?php if ( ! $tips->posts ) : ?>
		<p><?php echo esc_html( $page > 1 ? __( 'Na tejto strane nie sú žiadne tipy.', 'cammino' ) : __( 'Zatiaľ nemáte žiadne tipy. Začnite odoslaním svojho prvého tipu.', 'cammino' ) ); ?></p>
	<?php else : ?>
		<ul class="cammino-tipsters__list">
			<?php foreach ( $tips->posts as $tip ) : ?>
				<?php if ( ! cammino_tipsters_can_read_tip( $tip->ID ) ) { continue; } $record = cammino_tipsters_tip_record( $tip->ID ); ?>
				<li class="cammino-tipsters__list-item">
					<div>
						<h3><a href="<?php echo esc_url( cammino_tipsters_url( 'tip', $tip->ID ) ); ?>"><?php echo esc_html( $record['title'] ); ?></a></h3>
						<p class="cammino-tipsters__dates"><?php echo esc_html( sprintf( __( 'Odoslané: %1$s · Aktualizované: %2$s', 'cammino' ), get_post_time( get_option( 'date_format' ) . ' H:i', false, $tip, true ), get_date_from_gmt( $record['updated_at'], get_option( 'date_format' ) . ' H:i' ) ) ); ?></p>
					</div>
					<span class="cammino-tipsters__status"><?php echo esc_html( $statuses[ $record['status'] ] ); ?></span>
				</li>
			<?php endforeach; ?>
		</ul>
	<?php endif; ?>
	<?php if ( $tips->max_num_pages > 1 || $page > 1 ) : ?>
		<nav class="cammino-tipsters__actions" aria-label="<?php esc_attr_e( 'Stránkovanie tipov', 'cammino' ); ?>">
			<?php if ( $page > 1 ) : ?><a href="<?php echo esc_url( add_query_arg( 'cammino_tips_page', $page - 1, cammino_tipsters_url() ) ); ?>"><?php esc_html_e( 'Predchádzajúca strana', 'cammino' ); ?></a><?php endif; ?>
			<span><?php echo esc_html( sprintf( __( 'Strana %1$d z %2$d', 'cammino' ), $page, max( 1, $tips->max_num_pages ) ) ); ?></span>
			<?php if ( $page < $tips->max_num_pages ) : ?><a href="<?php echo esc_url( add_query_arg( 'cammino_tips_page', $page + 1, cammino_tipsters_url() ) ); ?>"><?php esc_html_e( 'Ďalšia strana', 'cammino' ); ?></a><?php endif; ?>
		</nav>
	<?php endif; ?>
</section>

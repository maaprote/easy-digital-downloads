<?php
/**
 * View Order Details
 *
 * @package     EDD
 * @subpackage  Admin/Payments
 * @copyright   Copyright (c) 2013, Pippin Williamson
 * @license     http://opensource.org/licenses/gpl-2.0.php GNU Public License
 * @since       1.6
*/

// Exit if accessed directly
if ( ! defined( 'ABSPATH' ) ) exit;

/**
 * View Order Details Page
 *
 * @since 1.6
 * @return void
*/
function edd_view_order_details_screen() {
	if ( ! isset( $_GET['id'] ) || ! is_numeric( $_GET['id'] ) ) {
		wp_die( __( 'Payment ID not supplied. Please try again', 'edd' ), __( 'Error', 'edd' ) );
	}

	// Setup the variables
	$payment_id = absint( $_GET['id'] );
	$item = get_post( $payment_id );
	$payment_meta = edd_get_payment_meta( $payment_id );
	$cart_items   = isset( $payment_meta['cart_details'] ) ? maybe_unserialize( $payment_meta['cart_details'] ) : false;
	if ( empty( $cart_items ) || ! $cart_items ) {
		$cart_items = maybe_unserialize( $payment_meta['downloads'] );
	}
	$user_info = edd_get_payment_meta_user_info( $payment_id );
	$payment_date = strtotime( $item->post_date );
	?>
	<div class="wrap">
		<h2><?php _e( 'View Order Details', 'edd' ); ?> <a class="add-new-h2" href="<?php echo admin_url( 'edit.php?post_type=download&amp;page=edd-payment-history&amp;edd-action=edit-payment&amp;purchase_id=' . $payment_id ); ?>"><?php _e( 'Edit Order', 'edd' ); ?></a></h2>
		<?php do_action( 'edd_view_order_details_before' ); ?>
		<div id="poststuff">
			<div id="post-body" class="metabox-holder columns-2">
				<div id="postbox-container-1" class="postbox-container">
					<div id="side-sortables" class="meta-box-sortables ui-sortable">
						<?php do_action( 'edd_view_order_details_sidebar_before' ); ?>
						<div id="edd-order-totals" class="postbox">
							<h3 class="hndle"><span>Order Totals</span></h3>
							<div class="inside">
								<div class="edd-order-totals-box edd-admin-box">
									<div class="edd-order-discounts edd-admin-box-inside">
										<p><span class="label"><?php _e( 'Discount Code', 'edd' ); ?></span> <span class="right"><?php if ( isset( $user_info['discount'] ) && $user_info['discount'] !== 'none' ) { echo '<code>' . $user_info['discount'] . '</code>'; } else { _e( 'None', 'edd' ); } ?></span></p>
									</div>
									<?php
									$taxes = edd_use_taxes();
									if ( $taxes ) : ?>
									<div class="edd-order-taxes edd-admin-box-inside">
										<p><span class="label"><?php _e( 'Tax', 'edd' ); ?></span> <span class="right"><?php echo edd_currency_filter( edd_format_amount( edd_get_payment_tax( $payment_id ) ) ); ?></span></p>
									</div>
									<?php endif; ?>
									<?php
									$fees = edd_get_payment_fees( $payment_id );
									if ( ! empty( $fees ) ) : ?>
									<div class="edd-order-fees edd-admin-box-inside">
										<p class="strong"><?php _e( 'Fees', 'edd' ); ?></p>
										<ul class="edd-payment-fees">
											<?php foreach( $fees as $fee ) : ?>
											<li><span class="fee-label"><?php echo $fee['label'] . ':</span> ' . '<span class="right">' . edd_currency_filter( $fee['amount'] ); ?></span></li>
											<?php endforeach; ?>
										</ul>
									</div>
									<?php endif; ?>
									<div class="edd-order-payment edd-admin-box-inside">
										<p><span class="label"><?php _e( 'Total Price', 'edd' ); ?></span> <span class="right"><?php echo edd_currency_filter( edd_format_amount( edd_get_payment_amount( $payment_id ) ) ); ?></span></p>
									</div>
								</div><!-- /.edd-order-totals-box -->
							</div><!-- /.inside -->
						</div><!-- /#edd-order-totals -->

						<div id="edd-payment-notes" class="postbox">
							<h3 class="hndle"><span>Payment Notes</span></h3>
							<div class="inside">
								<?php
								$notes = edd_get_payment_notes( $payment_id );
								if ( ! empty( $notes ) ) :
									foreach ( $notes as $note ) :
										if ( ! empty( $note->user_id ) ) {
											$user = get_userdata( $note->user_id );
											$user = $user->display_name;
										} else {
											$user = __( 'EDD Bot', 'edd' );
										}
										?>
										<div class="edd-payment-note">
											<p><?php echo $note->comment_content; ?></p>
											<p><strong><?php echo $user; ?></strong> <em><?php echo $note->comment_date; ?></em></p>
										</div>
										<?php
									endforeach;
								else :
									echo '<p>'. __( 'No payment notes', 'edd' ) . '</p>';
								endif;
								?>
							</div><!-- /.inside -->
						</div><!-- /#edd-payment-notes -->
						<?php do_action( 'edd_view_order_details_sidebar_after' ); ?>
					</div><!-- /#side-sortables -->
				</div><!-- /#postbox-container-1 -->

				<div id="postbox-container-2" class="postbox-container">
					<div id="normal-sortables" class="meta-box-sortables ui-sortable">
						<?php do_action( 'edd_view_order_details_main_before' ); ?>
						<div id="edd-order-data" class="postbox">
							<h3 class="hndle"><?php printf( __( 'Order Details - Payment #%d', 'edd' ), $payment_id ); ?></h3>
							<div class="inside">
								<div class="column-container">
									<div class="order-data-column">
										<h4><?php _e( 'General Details' ); ?></h4>
										<p class="data"><span><?php _e( 'Status:', 'edd' ); ?></span> <?php echo edd_get_payment_status( get_post( $payment_id ), true ) ?></p>
										<p class="data"><span><?php _e( 'Date:', 'edd' ); ?></span> <?php echo date_i18n( get_option( 'date_format' ), $payment_date ) ?></p>
										<p class="data"><span><?php _e( 'Time:', 'edd' ); ?></span> <?php echo date_i18n( get_option( 'time_format' ), $payment_date ); ?></p>
									</div>

									<div class="order-data-column">
										<h4><?php _e( 'Buyer\'s Personal Details', 'edd' ); ?></h4>
										<p class="data"><span><?php _e( 'Name:', 'edd' ); ?></span> <?php echo $user_info['first_name'] . ' ' . $user_info['last_name']; ?></p>
										<p class="data"><span><?php _e( 'Email:', 'edd' ); ?></span> <a href="mailto:<?php echo $payment_meta['email']; ?>"><?php echo $payment_meta['email']; ?></a></p>
									</div>

									<div class="order-data-column">
										<h4><?php _e( 'Payment Details', 'edd' ); ?></h4>
										<?php 
										$gateway = edd_get_payment_gateway( $payment_id );
										if ( $gateway ) {
										?>
										<p class="data"><span><?php _e( 'Gateway:', 'edd' ); ?></span> <?php echo edd_get_gateway_admin_label( $gateway ); ?></p>
										<?php } ?>
										<p class="data"><span><?php _e( 'Key:', 'edd' ); ?></span> <?php echo $payment_meta['key']; ?></p>
									</div>
								</div><!-- /.column-container -->
							</div><!-- /.inside -->
						</div><!-- /#edd-order-data -->

						<div id="edd-purchased-files" class="postbox">
							<h3 class="hndle"><?php _e( 'Purchased Files', 'edd' ); ?></h3>
							<div class="inside">
								<table class="wp-list-table widefat fixed" cellspacing="0">
									<tbody id="the-list">
										<?php
										if ( $cart_items ) :
											$i = 0;
											foreach ( $cart_items as $key => $cart_item ) :
												$id = isset( $payment_meta['cart_details'] ) ? $cart_item['id'] : $cart_item;
												$price_override = isset( $payment_meta['cart_details'] ) ? $cart_item['price'] : null;
												$price = edd_get_download_final_price( $id, $user_info, $price_override );
												?>
												<tr class="<?php if ( $i % 2 == 0 ) { echo 'alternate'; } ?>">
													<td class="name column-name">
														<?php
														echo '<a href="' . admin_url( 'post.php?post=' . $id . '&action=edit' ) . '">' . get_the_title( $id ) . '</a>';

														if ( isset( $cart_items[ $key ]['item_number'] ) ) {
															$price_options = $cart_items[ $key ]['item_number']['options'];

															if ( isset( $price_options['price_id'] ) ) {
																echo ' - ' . edd_get_price_option_name( $id, $price_options['price_id'], $payment_id );
															}
														}
														?>
													</td>
													<td class="price column-price">
														<?php echo edd_currency_filter( edd_format_amount( $price ) ); ?>
													</td>
												</tr>
												<?php
												$i++;
											endforeach;
										endif;
										?>
									</tbody>
								</table>
							</div><!-- /.inside -->
						</div><!-- /#edd-purchased-files -->
						<?php do_action( 'edd_view_order_details_main_after' ); ?>
					</div><!-- /#normal-sortables -->
				</div><!-- #postbox-container-2 -->
			</div><!-- /#post-body -->
		</div><!-- /#post-stuff -->
		<?php do_action( 'edd_view_order_details_after' ); ?>
	</div><!-- /.wrap -->
	<?php	
}

/**
 * View Order Details Page Title
 *
 * @since 1.6
 * @return void
 */
function edd_view_order_details_title( $admin_title, $title ) {
	if ( 'download_page_edd-view-order-details' == get_current_screen()->base )
		return __( 'View Order Details', 'edd' ) . ' ' . $admin_title;

	return $admin_title;
}
add_filter( 'admin_title', 'edd_view_order_details_title', 10, 2 );

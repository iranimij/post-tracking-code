<?php
/**
 *
 * @link              http://www.iranimij.com
 * @since             1.0.1
 * @package           post-tracking-code
 *
 * @wordpress-plugin
 * Plugin Name:       Post tracking code
 * Plugin URI:        http://www.iranimij.com
 * Description:       Post tracking code.
 * Version:           1.0.0
 * Author:            Iman Heydari
 * License:           GPL-2.0+
 * License URI:       http://www.gnu.org/licenses/gpl-2.0.txt
 * Text Domain:       ptc
 * Domain Path:       /languages
 */

add_action( 'add_meta_boxes', 'ptc_mv_add_meta_boxes' );

add_action( 'save_post', 'ptc_mv_save_wc_order_other_fields', 10, 1 );

add_shortcode( 'ptc_tracking_code', 'ptc_tracking_code_callback' );

function ptc_mv_add_meta_boxes() {
    add_meta_box( 'mv_other_fields', esc_html__( 'Tracking code', 'ptc' ), 'ptc_mv_add_other_fields_for_packaging', 'shop_order', 'side', 'core' );
}

function ptc_mv_add_other_fields_for_packaging() {
    global $post;

    $meta_field_data = get_post_meta( $post->ID, 'ptc_tracking_code', true ) ? get_post_meta( $post->ID, 'ptc_tracking_code', true ) : '';

    echo '<input type="hidden" name="ptc_tracking_code_nonce" value="' . wp_create_nonce() . '">
        <p style="border-bottom:solid 1px #eee;padding-bottom:13px;">
            <input type="text" style="width:250px;";" name="ptc_tracking_code" value="' . esc_attr( $meta_field_data ) . '"></p>';
}

function ptc_mv_save_wc_order_other_fields( $post_id ) {
    $post_tracking_code = filter_input( INPUT_POST, 'ptc_tracking_code', FILTER_SANITIZE_STRING );
    // We need to verify this with the proper authorization (security stuff).

    // Check if our nonce is set.
    if ( !isset( $_POST[ 'ptc_tracking_code_nonce' ] ) ) {
        return;
    }

    $nonce = $_REQUEST[ 'ptc_tracking_code_nonce' ];

    //Verify that the nonce is valid.
    if ( !wp_verify_nonce( $nonce ) ) {
        return;
    }

    // If this is an autosave, our form has not been submitted, so we don't want to do anything.
    if ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) {
        return;
    }
    // --- Its safe for us to save the data ! --- //

    $order = new \WC_Order( $post_id );

    if ( $order->get_meta( 'ptc_tracking_code' ) === $post_tracking_code ) {
        return;
    }

    if ( empty( $post_tracking_code ) ) {
        return;
    }

    update_post_meta( $post_id, 'ptc_tracking_code', $post_tracking_code );

    // The text for the note
    $note = sprintf( esc_html__( "It's your tracking code %s"), $post_tracking_code );

    // Add the note
    $order->add_order_note( $note, 1 );
    $order->save();
}

function ptc_tracking_code_callback() {
    $code = filter_input( INPUT_GET, 'code', FILTER_SANITIZE_STRING );
    $nonce = filter_input( INPUT_GET, '_wpnonce', FILTER_SANITIZE_STRING );

    $order = wc_get_order( $code );

    if ( !empty( $code ) && ( !isset( $nonce ) || !wp_verify_nonce( $nonce, 'tracking_code' ) ) ) {
        print esc_html__( 'Something went-wrong', 'ptc');
        exit;
    }

    if ( !empty( $order ) ) {
        $tracking_code = $order->get_meta( 'ptc_tracking_code' );
        $shipping = $order->get_shipping_method();
    }

    ob_start();
    ?>
    <style>
        .tracking_code_wrapper {
            display: flex;
            justify-content: center;
            align-items: center;
            width: 100%;
            max-width: 500px;
        }

        .tracking_code_body {
            width: 100%;
            text-align: center;
        }

        .tracking_code_wrappr form {
            text-align: center;
        }

        .tracking_code_wrappr .tracking_code_body form button {
            margin-top: 10px !important;
            margin: 0 auto;
            display: inline-block;
        }

        .tracking_code_result {
            width: 100%;
            background: #fff;
            min-height: 100px;
            margin-top: 10px;
            border-radius: 10px;
            padding: 10px;
        }
    </style>
    <div class="tracking_code_wrapper">
        <div class="tracking_code_body">
            <form action="">
                <?php wp_nonce_field( 'tracking_code', '_wpnonce', false ); ?>
                <input type="text" placeholder="<?php _e( 'Insert your order number', 'ptc' );?>" name="code" value="<?= $code ?>">
                <button type="submit" style="margin-t"><?= esc_html__( 'Search', 'ptc' ); ?></button>
            </form>
            <?php if ( !empty( $tracking_code ) ) : ?>
                <div class="tracking_code_result">
                    <div><?php echo esc_html__('Tracking code','ptc'); ?> :<?php echo esc_html( $tracking_code ); ?> </div>
                    <?php if ( !empty( $shipping ) ) : ?>
                        <div style="direction: rtl"><?php echo esc_html__('Shipping type', 'ptc' ); ?> :<?php echo esc_html( $shipping ) ?> </div>
                    <?php endif; ?>
                </div>
            <?php endif; ?>
            <?php if ( empty( $tracking_code ) && empty( $order ) && !empty( $code ) ) : ?>
                <div class="tracking_code_result">
                    <div><?php echo esc_html__('Something went wrong', 'ptc' ); ?></div>
                </div>
            <?php endif; ?>
            <?php if ( !empty( $order ) && empty( $tracking_code ) ) : ?>
                <div class="tracking_code_result">
                    <div style="direction: rtl"><?php echo esc_html__('There is no tracking code', 'ptc' ); ?></div>
                </div>
            <?php endif; ?>
        </div>
    </div>
    <?php
    return ob_get_clean();
}
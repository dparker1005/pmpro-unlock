<?php
// All functions relating to checkout.

/**
 * Adds functionality to level page to show connect wallet button.
 */
function pmpro_up_add_wallet_to_checkout() {
    global $pmpro_level;
    $level_id = $pmpro_level->id;

    $wallet = pmpro_up_check_save_wallet(); // Get and save the wallet possibly.

    // Check if we have a wallet ID or not.
    if ( is_wp_error( $wallet ) || ! $wallet ) {
        echo pmpro_up_connect_wallet_button();
    } else {
        echo "<p>We've got a wallet address" . $wallet . "</p>"; /// Remove this later.

        $level_lock_options = get_option( 'pmpro_unlock_protocol_' . $level_id, true );
        // Let's check the validate lock status.
        $check_lock = pmpro_up_has_lock_access( $level_lock_options['network'], $level_lock_options['lock_address'], $wallet );

        if ( $check_lock ) {
            echo pmpro_setMessage( 'ALL GOOD', 'pmpro_success'); ///Change this later on.
        } else { ///Look at this too.
            $redirect_uri = get_permalink() . '?level=' . $level_id;
            $checkout_url = pmpro_up_get_checkout_url( $level_lock_options['lock_address'], $redirect_uri );
            echo "You can purchase this NFT <a href='" . $checkout_url . "'>Click here to buy the NFT</a>"; /// Build Checkout URL here, maybe check level options if NFT is totally required.
        }
    }
}
add_action( 'pmpro_checkout_after_pricing_fields', 'pmpro_up_add_wallet_to_checkout' );

/**
 * Save/update wallet after level's changed. If no $code parameter is found it will just retrieve their wallet. ///Todo, make this smarter when we call it.
 *
 */
function pmpro_up_save_wallet_after_level_change( $level_id, $user_id, $cancel_level ) {
    // Try to save user's wallet after they're given a level.
    pmpro_up_check_save_wallet( $user_id );
    pmpro_unset_session_var( 'code' ); // Remove any session VAR that may be there.
}
add_action( 'pmpro_after_change_membership_level', 'pmpro_up_save_wallet_after_level_change', 10, 3 );

/**
 * Bypass level pricing if they have an NFT.
 *
 * @param object $checkout_level The membership level the user is about to purchase.
 */
function pmpro_up_checkout_level( $checkout_level ) {
    $level_id = $checkout_level->id;

    $level_lock_options = get_option( 'pmpro_unlock_protocol_' . $level_id, true );

    // Level doesn't have any Unlock Protocol Settings, just bail.
    if ( ! $level_lock_options || ! is_array( $level_lock_options ) ) {
        return $checkout_level;
    }

    $wallet = pmpro_up_try_to_get_wallet();
    // Figure out how to get the wallet address.
    if ( is_wp_error( $wallet ) || ! $wallet ) {
        return $checkout_level; /// Unable to authenticate wallet whatsoever - just bail.
    }
       
    // Let's see if they have access to the lock now.
    $check_lock = pmpro_up_has_lock_access( $level_lock_options['network'], $level_lock_options['lock_address'], $wallet );

    if ( $check_lock ) {
        $checkout_level->initial_payment = '0';
        $checkout_level->billing_amount = '0';
        $checkout_level->cycle_number = '0';
        $checkout_level->cycle_period = '';
        $checkout_level->billing_limit = '0';
        $checkout_level->trial_amount = '0';
        $checkout_level->trial_limit = '0';
    }

    return $checkout_level;

}
add_filter( 'pmpro_checkout_level', 'pmpro_up_checkout_level', 10, 1 );

/**
 * Check if user has relevant lock access or not during checkout and based on settings.
 *
 * @param bool $continue Variable to continue or stop registration for checkout.
 * @return bool $continue Continue with Paid Memberships Pro checkout or not - based on NFT Status.
 */
function pmpro_up_registration_checks( $continue ) {
    global $pmpro_level;

    $level_id = $pmpro_level->id;

    if ( ! $continue ) {
        return $continue;
    }

    $level_lock_options = get_option( 'pmpro_unlock_protocol_' . $level_id, true );

    // Level doesn't require NFT, network not selected, just bail quietly.
    if ( empty( $level_lock_options ) || $level_lock_options['network'] === '' || $level_lock_options['nft_required'] === 'No' ) {
        return $continue;
    }

    $wallet = pmpro_up_try_to_get_wallet();
    
    // Let's see if they have access to the lock now.
    $continue = pmpro_up_has_lock_access( $level_lock_options['network'], $level_lock_options['lock_address'], $wallet );
    
    if ( ! $continue ) {
        pmpro_setMessage( 'You need an NFT bro!', 'pmpro_error' ); // Change this.
    }

    return $continue;
}
add_filter( 'pmpro_registration_checks', 'pmpro_up_registration_checks' );
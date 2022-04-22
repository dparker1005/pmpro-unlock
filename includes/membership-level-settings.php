<?php
/**
 * Code to add settings to the edit membership level page and save those settings.
*/

/**
 * Display Settings for Unlock Protocol Integration.
 */
function pmpro_up_level_settings() {

    // Unlock Protocol settings.
    $networks = pmpro_up_networks_list();
    array_unshift( $networks, array( 'network_name' => '' ) ); // Insert first option as "-" to array.
    
    if ( isset( $_REQUEST['edit'] ) ) {
		$level_id = intval( $_REQUEST['edit'] );
    }

    // Get level settings and configure variables to be used.
    $pmpro_unlock_settings = get_option( 'pmpro_unlock_protocol_' . $level_id );
    if ( is_array( $pmpro_unlock_settings ) ) {
        $network_value = $pmpro_unlock_settings['network'];
        $lock_address_value = $pmpro_unlock_settings['lock_address'];
        $nft_required = $pmpro_unlock_settings['nft_required'];
    } else {
        $network_value = '';
        $lock_address_value = '';
        $nft_required = 'No';
    }

    ?>
    <hr/>
    <h2><?php esc_html_e( 'Unlock Protocol Settings', 'pmpro-unlock' ); ?></h2>
    <table class="form-table">
        <tbody>
            <tr>
                <p>Configure settings below if an NFT is required to purchase this membership level. If a valid NFT is detected for the member, they will be able to claim this membership for free.</p>
                <th scope="row" valign="top">   
                    <label for="pmpro-unlock-network"><?php esc_html_e( 'Choose a Network:', 'pmpro-unlock' ); ?></label>
                </th>   
                <td>
                    <select name="pmpro-unlock-network" id="pmpro-unlock-network">
                    <?php
                        foreach ( $networks as $network ) {
                            echo "<option value='" . esc_attr( $network['network_rpc_endpoint'] ) . "' " . selected( $network_value, $network['network_rpc_endpoint'], false ) . ">" . esc_html( $network['network_name'] ) . "</option>";
                        }
                    ?>
                    </select>
                </td>
            </tr>
            <tr>
                <th scope="row" valign="top"><?php esc_html_e( 'Lock Address:', 'pmpro-unlock' ); ?></th>
                <td>
                    <input type="text" name="pmpro-unlock-lock" id="pmpro-unlock-lock" class="regular-text" value="<?php echo esc_attr( $lock_address_value ); ?>"/>
                    <p class="description"><a href="https://app.unlock-protocol.com/dashboard" target="_blank" rel="noopener noreferrer"><?php esc_html_e( 'Deploy a lock', 'pmpro-unlock' ); ?></p>
                </td>
            </tr>
            <tr>
                <th scope="row" valign="top"><?php esc_html_e( 'Require NFT to checkout?', 'pmpro-unlock' ); ?></th>
                <td>
                    <select name="pmpro-unlock-nft-required" id="pmpro-unlock-nft-required">
                        <option value="No" <?php selected( $nft_required, "No", true ); ?>>No. People who don't have an NFT may checkout for this level.</option>
                        <option value="Yes" <?php selected( $nft_required, "Yes", true ); ?> >Yes. People are required to have an NFT to checkout for this level.</option>
                    </select>
                </td>
        </tbody>
    </table>
    <?php
}
add_action( 'pmpro_membership_level_after_other_settings', 'pmpro_up_level_settings' );

/**
 * Save settings for Unlock Protocol.
 */
function pmpro_up_save_membership_level( $level_id ) {
    
    if ( $level_id <= 0 ) {
		return;
	}

    $network = sanitize_text_field( $_REQUEST['pmpro-unlock-network'] );
    $lock_address = sanitize_text_field( $_REQUEST['pmpro-unlock-lock' ] );
    $nft_required = sanitize_text_field( $_REQUEST['pmpro-unlock-nft-required'] );

    $pmpro_unlock_settings = array( 'network' => $network, 'lock_address' => $lock_address, 'nft_required' => $nft_required );

    // Save or delete options during level save.
    if ( $network ) {
        update_option( 'pmpro_unlock_protocol_' . $level_id, $pmpro_unlock_settings, 'no' );
    } else {
        delete_option( 'pmpro_unlock_protocol_' . $level_id );
    }

}
add_action( 'pmpro_save_membership_level', 'pmpro_up_save_membership_level', 10, 1 );

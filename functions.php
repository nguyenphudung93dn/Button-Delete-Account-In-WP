<?php

function wp_delete_user_account_delete_button( $button_text = '' ) {

	// Bail if user is logged out
	if ( ! is_user_logged_in() ) {
		return;	
	}
	// Bail to prevent administrators from deleting their own accounts
	if ( current_user_can( 'manage_options' ) ) {
		return;
	}
	// Defauly button text
	if ( $button_text == '' ) {
		$button_text = __( 'Delete My Account', 'wp-delete-user-accounts' );
	}
	// Button
	printf( '<button id="delete-my-account">%s</button>', $button_text );
}

/**
 *	Render [wp_delete_user_accounts] shortcode
 */
function wp_delete_user_accounts() {

	// Show nothing if user is logged out
	if ( ! is_user_logged_in() ) {
		return '';
	}

	// Bail to prevent administrators from deleting their own accounts
	if ( current_user_can( 'manage_options' ) ) {
		return '';
	}

	ob_start();
	
    ?>
        <div class="delete-user-account-container">
        
            <p>Điều này sẽ khiến tài khoản của bạn bị xóa vĩnh viễn. Bạn sẽ không thể khôi phục tài khoản của mình.</p>

            <?php wp_delete_user_account_delete_button( 'Xóa Tài Khoản' ); ?>

        </div>
    <?php
    
	return ob_get_clean();
}

add_shortcode( 'wp_delete_user_accounts','wp_delete_user_accounts' );

/**
 *	Process the request
 *	@todo Setting for reassigning user's posts
 */
function process_delete_account() {

	// Verify the security nonce and die if it fails
	if ( ! isset( $_POST['wp_delete_user_accounts_nonce'] ) || ! wp_verify_nonce( $_POST['wp_delete_user_accounts_nonce'], 'wp_delete_user_accounts_nonce' ) ) {
		wp_send_json( array(
			'status' => 'fail',
			'title' => __( 'Lỗi!', 'wp-delete-user-accounts' ),
			'message' => __( 'Yêu cầu kiểm tra bảo mật không thành công.', 'wp-delete-user-accounts' )
		) );
	}

	// Don't permit admins to delete their own accounts
	if ( current_user_can( 'manage_options' ) ) {
		wp_send_json( array(
			'status' => 'fail',
			'title' => __( 'Lỗi!', 'wp-delete-user-accounts' ),
			'message' => __( 'Quản trị viên không thể xóa tài khoản của chính họ.', 'wp-delete-user-accounts' )
		) );
	}

	// Get the current user
	$user_id = get_current_user_id();

	// Get user meta
	$meta = get_user_meta( $user_id );

	// Delete user's meta
	foreach ( $meta as $key => $val ) {
		delete_user_meta( $user_id, $key );
	}

	// Destroy user's session
	wp_logout();

	// Delete the user's account
	$deleted = wp_delete_user( $user_id );

	if ( $deleted ) {

		// Send success message
		wp_send_json( array(
			'status' => 'success',
			'title' => __( 'Thành Công!', 'wp-delete-user-accounts' ),
			'message' => __( 'Tài khoản của bạn đã được xóa thành công', 'wp-delete-user-accounts' )
		) );
	
	} else {

		wp_send_json( array(
			'status' => 'fail',
			'title' => __( 'Lỗi!', 'wp-delete-user-accounts' ),
			'message' => __( 'Yêu cầu không thành công.', 'wp-delete-user-accounts' )
		) );
	}
}

add_action( 'wp_ajax_wp_delete_user_account',  'process_delete_account' );


function add_theme_scripts_delete_account() {

    // Bail if user is logged out
    if ( ! is_user_logged_in() ) {
        return;
    }

    // Bail to prevent administrators from deleting their own accounts
    if ( current_user_can( 'manage_options' ) ) {
        return;
    }

    global $post;

    $confirm_text = apply_filters( 'wp_delete_user_account_confirm_delete_text', __( 'DELETE', 'wp-delete-user-accounts' ) );

    $vars = apply_filters( 'wp_delete_user_accounts_localize_script_vars', array(
        'alert_title' => __( 'Xin chào!', 'wp-delete-user-accounts' ),
        'alert_text' => __( 'Sau khi bạn xóa tài khoản của mình, sẽ không lấy lại được. Hãy chắc chắn rằng bạn muốn làm điều này.', 'wp-delete-user-accounts' ),
        'confirm_text' => $confirm_text,
        'button_confirm_text' => __( 'Đúng, Vui lòng xóa nó', 'wp-delete-user-accounts' ),
        'button_cancel_text' => __( 'Hủy', 'wp-delete-user-accounts' ),
        'incorrect_prompt_title' => __( 'Lỗi', 'wp-delete-user-accounts' ),
        'incorrect_prompt_text' => __( 'Xác nhận của bạn không chính xác.', 'wp-delete-user-accounts' ),
        'general_error_title' => __( 'Lỗi', 'wp-delete-user-accounts' ),
        'general_error_text' => __( 'Đã xảy ra sự cố.', 'wp-delete-user-accounts' ),
        'processing_title' => __( 'Đang thực thi...', 'wp-delete-user-accounts' ),
        'processing_text' => __( 'Chờ một chút trong khi chúng tôi xử lý yêu cầu của bạn.', 'wp-delete-user-accounts' ),
        'input_placeholder' => sprintf( '%s %s', __( 'Xác nhận bằng cách gõ', 'wp-delete-user-accounts' ), $confirm_text ),
        'redirect_url' => home_url()
    ) );

    $vars['nonce'] = wp_create_nonce( 'wp_delete_user_accounts_nonce' );

    if ( is_admin() && get_current_screen()->base == 'profile' ) {

        wp_enqueue_style( 'wp-delete-user-accounts-css', CHILD_THEME_URL . '/assets/css/wp-delete-user-accounts.css', '', '1.0.0' );
        wp_enqueue_script( 'sweetalert-js', CHILD_THEME_URL . '/assets/js/sweetalert.min.js', array( 'jquery' ), '1.0.0', true );
        wp_enqueue_script( 'wp-delete-user-accounts-js', CHILD_THEME_URL . '/assets/js/wp-delete-user-accounts.js', array( 'jquery', 'sweetalert-js' ), '1.0.0', true );
        wp_localize_script( 'wp-delete-user-accounts-js', 'wp_delete_user_accounts_js', array_merge( $vars, array( 'is_admin' => 'true' ) ) );

    } elseif ( apply_filters( 'wp_delete_user_accounts_load_assets_on_frontend', ( is_object( $post ) && has_shortcode( $post->post_content, 'wp_delete_user_accounts' ) ) ) ) {

        wp_enqueue_style( 'wp-delete-user-accounts-css', CHILD_THEME_URL . '/assets/css/wp-delete-user-accounts.css', '', '1.0.0' );
        wp_enqueue_script( 'sweetalert-js', CHILD_THEME_URL . '/assets/js/sweetalert.min.js', array( 'jquery' ), '1.0.0', true );
        wp_enqueue_script( 'wp-delete-user-accounts-js', CHILD_THEME_URL . '/assets/js/wp-delete-user-accounts.js', array( 'jquery', 'sweetalert-js' ), '1.0.0', true );
        wp_localize_script( 'wp-delete-user-accounts-js', 'wp_delete_user_accounts_js', array_merge( $vars, array( 'ajaxurl' => admin_url( 'admin-ajax.php' ) ) ) );
    }
}
add_action( 'wp_enqueue_scripts', 'add_theme_scripts_delete_account' );
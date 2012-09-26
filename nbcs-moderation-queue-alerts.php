<?php
/*
Moderation Queue Alerts
Plugin to send emails when the comment moderation queue grows too large
by James Dowd
*/

function nbcs_moderation_queue_alerts_check_queue() {
	$queue_minimum = get_option( 'nbcs-moderation-queue-minimum' );
	$queue_email = nbcs_moderation_queue_check_email( get_option( 'nbcs-moderation-queue-email' ) );
	$queue_frequency = get_option( 'nbcs-moderation-queue-frequency' );
	
	if ( false !== get_transient( 'nbcs-moderation-queue-delay' ) || false === $queue_minimum || false === $queue_frequency || empty( $queue_email ) ) {
		return; // Don't do anything if the settings have not been set
	}

	$comment_count = get_comment_count();
	if ( $comment_count['awaiting_moderation'] >= intval( $queue_minimum ) ) {
		if ( intval( $queue_frequency ) > 0 ) {
			set_transient( 'nbcs-moderation-queue-delay', true, 60 * intval( $queue_frequency ) );
		}

		$blog_name = get_bloginfo( 'name' );
		$subject = $blog_name . ' Comment Alert - ' . $comment_count['awaiting_moderation'] . ' in queue';
		$message = '<p>There are currently ' . $comment_count['awaiting_moderation'] . ' comments in the ' . $blog_name . ' moderation queue.';
		if ( $queue_frequency > 0 ) {
			$message .= ' You will not receive another alert for ' . $queue_frequency . ' minute' . ( $queue_frequency == 1 ? '' : 's' ) . '.';
		}
		$message .= '</p><p><a href="' . site_url( '/wp-admin/edit-comments.php' ) . '">Go to comments page</a></p>';

		wp_mail( $queue_email, $subject, $message );
	}
}
add_action( 'wp_insert_comment', 'nbcs_moderation_queue_alerts_check_queue' );

function nbcs_moderation_queue_alerts_settings_api_init() {
	add_settings_field( 'nbcs-moderation-queue-minimum', 'Moderation Queue Alerts', 'nbcs_moderation_queue_minimum_settings_field', 'discussion', 'default' );
	
	register_setting( 'discussion', 'nbcs-moderation-queue-minimum', 'intval' );
	register_setting( 'discussion', 'nbcs-moderation-queue-email', 'nbcs_moderation_queue_check_email' );
	register_setting( 'discussion', 'nbcs-moderation-queue-frequency', 'intval' );
}
add_action( 'admin_init', 'nbcs_moderation_queue_alerts_settings_api_init' );

function nbcs_moderation_queue_check_email( $email ) {
	$addresses = preg_split( '/[,;]/', $email );
	$final = array();
	foreach ( $addresses as $address ) {
		$address = trim( $address );
		if ( is_email( $address ) ) {
			$final[] = $address;
		}
	}
	
	return implode( ', ', $final );
}

function nbcs_moderation_queue_minimum_settings_field() {
	$queue_minimum = get_option( 'nbcs-moderation-queue-minimum' );
	if ( false === $queue_minimum ) {
		$queue_minimum = 100;
	}

	$queue_email = get_option( 'nbcs-moderation-queue-email' );

	$queue_frequency = get_option( 'nbcs-moderation-queue-frequency' );
	if ( false === $queue_frequency ) {
		$queue_frequency = 15;
	}

?>
	<label for="nbcs-moderation-queue-minimum">Send an alert email if the comment moderation queue has at least <input type="text" name="nbcs-moderation-queue-minimum" id="nbcs-moderation-queue-minimum" size="2" value="<?php echo esc_attr( $queue_minimum ); ?>" /> comments in it</label>
	<br />
	<label for="nbcs-moderation-queue-email">Send moderation queue alert emails to the following email address(es): <input type="text" name="nbcs-moderation-queue-email" id="nbcs-moderation-queue-email" size="45" value="<?php echo esc_attr( $queue_email ); ?>" /></label>
	<br />
	<label for="nbcs-moderation-queue-frequency">Do not send another moderation queue alert email until <input type="text" name="nbcs-moderation-queue-frequency" id="nbcs-moderation-queue-frequency" size="2" value="<?php echo esc_attr( $queue_frequency ); ?>" /> minutes have passed</label>
<?php
}

?>

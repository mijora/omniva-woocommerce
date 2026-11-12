<?php
class OmnivaLt_Emails
{
	protected $WC_mailer;
	protected $emails_dir;

	public function __construct() {
		$this->WC_mailer = WC()->mailer();
		$this->emails_dir = OMNIVALT_DIR . '/templates/emails/';
	}

	public function send_label( $order, $recipient, $params=array() ) {
		$variables['tracking_code'] = (isset($params['tracking_code'])) ? $params['tracking_code'] : '';
		$variables['tracking_link'] = (isset($params['tracking_link'])) ? $params['tracking_link'] : '';
		$variables['tracking_codes'] = (isset($params['tracking_codes'])) ? $params['tracking_codes'] : '';
		$variables['name'] = OmnivaLt_Order::get_customer_name($order);
		$variables['fullname'] = OmnivaLt_Order::get_customer_fullname($order);
		$variables['company'] = OmnivaLt_Order::get_customer_company($order);

		$subject = (isset($params['subject']) && !empty($params['subject'])) ? $params['subject'] : __('Your order shipment has been registered', 'omnivalt');
		$content = $this->email_createdlabel( $order->id, $subject, $variables );
		$headers = "Content-Type: text/html\r\n";

		$this->WC_mailer->send( $recipient, $subject, $content, $headers );
	}

	private function email_createdlabel( $order_id, $heading = false, $variables = array() ) {
		$template = 'customer-created_label.php';

		return wc_get_template_html( $template, array_merge(array(
			'order'         => OmnivaLt_Wc_Order::get_order($order_id),
			'email_heading' => $heading,
			'sent_to_admin' => false,
			'plain_text'    => false,
			'email'         => $this->WC_mailer
		), $variables) , '', $this->get_file_template_dir($template) );
	}

	private function get_file_template_dir( $file ) {
		$dir = 'emails/';
		if ( OmnivaLt_Core::get_override_file_path($dir . $file) ) {
			return OmnivaLt_Core::get_override_file_path($dir);
		}
		return $this->emails_dir;
	}

	/**
	 * Attach the generated Omniva label PDF to the WooCommerce "new order" admin email.
	 * Hooked to "woocommerce_email_attachments" (only when enabled in settings).
	 */
	public static function attach_label_to_new_order_email( $attachments, $email_id, $order ) {
		if ( $email_id !== 'new_order' || ! $order ) {
			return $attachments;
		}

		$settings = OmnivaLt_Core::get_settings();
		if ( empty($settings['email_labels_to_admin']) || $settings['email_labels_to_admin'] !== 'yes' ) {
			return $attachments;
		}

		$order_id = $order->get_id();

		if ( ! OmnivaLt_Omniva_Order::have_omniva_shipping($order_id) ) {
			return $attachments;
		}

		$barcodes = OmnivaLt_Omniva_Order::get_barcodes($order_id);
		if ( empty($barcodes) ) {
			OmnivaLt_Debug::log('order', 'Email address card: no barcodes found for order #' . $order_id);
			return $attachments;
		}

		try {
			$api = new OmnivaLt_Api();
			$labels_result = $api->get_shipment_labels($barcodes);

			if ( empty($labels_result['status']) || empty($labels_result['labels']) ) {
				OmnivaLt_Debug::log('error', 'Email address card: failed to get labels for order #' . $order_id . (isset($labels_result['msg']) ? ' (' . $labels_result['msg'] . ')' : ''));
				return $attachments;
			}

			$pdf_content = null;
			foreach ( $labels_result['labels'] as $barcode => $base64_pdf ) {
				if ( in_array( $barcode, (array) $barcodes, true ) ) {
					// phpcs:ignore WordPress.PHP.DiscouragedPHPFunctions.obfuscation_base64_decode -- The Omniva API returns the address card PDF as base64 data.
					$pdf_content = base64_decode( $base64_pdf, true );

					if ( false === $pdf_content ) {
						$pdf_content = null;
					}

					break;
				}
			}

			if ( ! $pdf_content ) {
				OmnivaLt_Debug::log('error', 'Email label: no PDF found for barcodes ' . implode(', ', (array) $barcodes));
				return $attachments;
			}

			OmnivaLt_Core::add_required_directories();
			$pdf_path = OMNIVALT_DIR . 'var/pdf/label_order_' . $order_id . '_' . time() . '.pdf';
			// phpcs:ignore WordPress.WP.AlternativeFunctions.file_system_operations_file_put_contents -- A temporary attachment must be written to the plugin's protected var directory.
			if ( false === file_put_contents( $pdf_path, $pdf_content ) ) {
				OmnivaLt_Debug::log('error', 'Email label: failed to save PDF for order #' . $order_id);
				return $attachments;
			}

			$attachments[] = $pdf_path;
			OmnivaLt_Debug::log('order', 'Email label: attached PDF to new order email for order #' . $order_id);

			// Schedule temporary file cleanup after the email is sent
			wp_schedule_single_event(time() + 300, 'omnivalt_cleanup_temp_label', array($pdf_path));
		} catch ( \Exception $e ) {
			OmnivaLt_Debug::log('error', 'Email label: error processing order #' . $order_id . ': ' . $e->getMessage());
		}

		return $attachments;
	}

	/**
	 * Remove a temporary label PDF created for an admin email attachment.
	 * Hooked to the scheduled "omnivalt_cleanup_temp_label" event.
	 */
	public static function cleanup_temp_label( $file_path ) {
		if ( file_exists($file_path) && strpos($file_path, 'var/pdf') !== false ) {
			wp_delete_file( $file_path );
			OmnivaLt_Debug::log('order', 'Email label: cleaned up temporary file ' . $file_path);
		}
	}
}

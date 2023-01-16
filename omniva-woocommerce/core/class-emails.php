<?php
class OmnivaLt_Emails
{
	protected $WC_mailer;
	protected $emails_dir;
	protected $overrides_dir;

	public function __construct() {
		$this->WC_mailer = WC()->mailer();
		$this->emails_dir = OMNIVALT_DIR . '/templates/emails/';
		$this->overrides_dir = OmnivaLt_Core::get_overrides_dir() . 'emails/';
	}

	public function send_label($order, $recipient, $params=array()) {
		$variables['tracking_code'] = (isset($params['tracking_code'])) ? $params['tracking_code'] : '';
		$variables['tracking_link'] = (isset($params['tracking_link'])) ? $params['tracking_link'] : '';
		$variables['name'] = OmnivaLt_Order::get_customer_name($order);
		$variables['fullname'] = OmnivaLt_Order::get_customer_fullname($order);
		$variables['company'] = OmnivaLt_Order::get_customer_company($order);

		$subject = (isset($params['subject']) && !empty($params['subject'])) ? $params['subject'] : __('Your order shipment has been registered', 'omnivalt');
		$content = $this->email_createdlabel( $order, $subject, $variables );
		$headers = "Content-Type: text/html\r\n";

		$this->WC_mailer->send( $recipient, $subject, $content, $headers );
	}

	private function email_createdlabel($order, $heading = false, $variables = array()) {
		$template = 'customer-created_label.php';

		return wc_get_template_html( $template, array_merge(array(
			'order'         => $order,
			'email_heading' => $heading,
			'sent_to_admin' => false,
			'plain_text'    => false,
			'email'         => $this->WC_mailer
		), $variables) , '', $this->get_file_template_dir($template) );
	}

	private function get_file_template_dir($file) {
		if (file_exists($this->overrides_dir . $file)) {
			return $this->overrides_dir;
		} else {
			return $this->emails_dir;
		}
	}
}
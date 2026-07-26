<?php
/**
 * My Login Form - Resend Mail Delivery
 *
 * Intercepts wp_mail() site-wide via the 'pre_wp_mail' filter (WP 5.7+)
 * and sends through the Resend API instead, when a Resend API key is
 * configured in Settings → Email Settings. On any failure (network error,
 * non-2xx response, or no API key set) this returns null so WordPress
 * falls through to its normal mail path — Resend is additive, never a
 * hard dependency.
 *
 * @package MyLoginForm\Emails
 */

namespace MyLoginForm\Emails;

// Prevent Direct Access
defined('ABSPATH') || exit;

class Emails {

    private static $instance = null;

    public static function get_instance(): self {
        if (null === self::$instance) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    public function init(): void {
        add_filter('pre_wp_mail', [$this, 'maybe_send_via_resend'], 10, 2);
    }

    /**
     * @param mixed $short_circuit Always null coming in — required by the filter signature.
     * @param array $atts wp_mail()'s to/subject/message/headers/attachments, already
     *                     passed through the 'wp_mail' filter.
     * @return true|null True if Resend accepted the email, null to let WordPress send it normally.
     */
    public function maybe_send_via_resend($short_circuit, array $atts) {
        $api_key = trim((string) get_option('my_login_form_resend_api_key', ''));
        if ($api_key === '') {
            return null;
        }

        $parsed = $this->parse_headers($atts['headers'] ?? '');

        $from_email = get_option('my_login_form_resend_from_email', '') ?: ($parsed['from_email'] ?: get_option('admin_email'));
        $from_name  = get_option('my_login_form_resend_from_name', '') ?: ($parsed['from_name'] ?: get_bloginfo('name'));

        $body = [
            'from'    => $from_name !== '' ? "{$from_name} <{$from_email}>" : $from_email,
            'to'      => $this->normalize_recipients($atts['to'] ?? ''),
            'subject' => (string) ($atts['subject'] ?? ''),
        ];

        if ($parsed['content_type'] === 'text/html') {
            $body['html'] = (string) ($atts['message'] ?? '');
        } else {
            $body['text'] = (string) ($atts['message'] ?? '');
        }

        if (!empty($parsed['cc']))       $body['cc']       = $parsed['cc'];
        if (!empty($parsed['bcc']))      $body['bcc']      = $parsed['bcc'];
        if (!empty($parsed['reply_to'])) $body['reply_to'] = $parsed['reply_to'];

        $attachments = $this->normalize_attachments($atts['attachments'] ?? []);
        if (!empty($attachments)) {
            $body['attachments'] = $attachments;
        }

        $response = wp_remote_post('https://api.resend.com/emails', [
            'headers' => [
                'Authorization' => 'Bearer ' . $api_key,
                'Content-Type'  => 'application/json',
            ],
            'body'    => wp_json_encode($body),
            'timeout' => 15,
        ]);

        if (is_wp_error($response)) {
            $this->log_failure($response->get_error_message());
            return null;
        }

        $code = (int) wp_remote_retrieve_response_code($response);
        if ($code >= 200 && $code < 300) {
            return true;
        }

        $this->log_failure('HTTP ' . $code . ': ' . wp_remote_retrieve_body($response));
        return null;
    }

    /**
     * @param string|array $to
     * @return array<int, string>
     */
    private function normalize_recipients($to): array {
        if (is_array($to)) {
            return array_values(array_filter(array_map('trim', $to)));
        }
        return array_values(array_filter(array_map('trim', explode(',', (string) $to))));
    }

    /**
     * WP core attachments are plain file paths. Resend wants base64 content.
     *
     * @param string|array $attachments
     * @return array<int, array{filename: string, content: string}>
     */
    private function normalize_attachments($attachments): array {
        $result = [];
        foreach ((array) $attachments as $file) {
            if (is_string($file) && $file !== '' && is_readable($file)) {
                $result[] = [
                    'filename' => basename($file),
                    'content'  => base64_encode((string) file_get_contents($file)),
                ];
            }
        }
        return $result;
    }

    /**
     * @param string|array $headers
     * @return array{content_type: string, from_email: string, from_name: string, cc: array, bcc: array, reply_to: array}
     */
    private function parse_headers($headers): array {
        $result = [
            'content_type' => 'text/plain',
            'from_email'   => '',
            'from_name'    => '',
            'cc'           => [],
            'bcc'          => [],
            'reply_to'     => [],
        ];

        if (empty($headers)) {
            return $result;
        }

        $lines = is_array($headers) ? $headers : preg_split('/\r\n|\r|\n/', (string) $headers);

        foreach ($lines as $line) {
            if (strpos($line, ':') === false) {
                continue;
            }
            [$name, $value] = array_map('trim', explode(':', $line, 2));

            switch (strtolower($name)) {
                case 'content-type':
                    if (stripos($value, 'text/html') !== false) {
                        $result['content_type'] = 'text/html';
                    }
                    break;
                case 'from':
                    if (preg_match('/^(.*)<(.+)>$/', $value, $m)) {
                        $result['from_name']  = trim($m[1], " \t\"");
                        $result['from_email'] = trim($m[2]);
                    } else {
                        $result['from_email'] = $value;
                    }
                    break;
                case 'cc':
                    $result['cc'] = array_merge($result['cc'], array_map('trim', explode(',', $value)));
                    break;
                case 'bcc':
                    $result['bcc'] = array_merge($result['bcc'], array_map('trim', explode(',', $value)));
                    break;
                case 'reply-to':
                    $result['reply_to'] = array_merge($result['reply_to'], array_map('trim', explode(',', $value)));
                    break;
            }
        }

        return $result;
    }

    private function log_failure(string $message): void {
        if (function_exists('my_login_form_log')) {
            my_login_form_log('Resend send failed, falling back to default mail: ' . $message, 'error');
        }
    }
}

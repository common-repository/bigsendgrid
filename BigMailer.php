<?php

/*
 * Plugin Name: Big Mailer - SendGrid
 * Plugin URI: http://bigemployee.com/projects/big-mailer-wordpress-plugin/
 * Description:
 * Author: Arian Khosravi, Norik Davtian
 * Author URI: http://bigemployee.com
 * Version: 1.1
 * License: GPL3
 * License URI: http://www.gnu.org/licenses/gpl.html
 */

class BigMailer {

    public function __construct() {
        add_action('admin_menu', array(&$this, 'bigMailer_admin_menu'));
        add_action('admin_enqueue_scripts', array(&$this, 'bigMailer_scripts'));
    }

    public function bigMailer_admin_menu() {
        add_options_page('Big Mailer - SendGrid Settings', 'Big Mailer/SendGrid', 'manage_options', 'bigmailer', array(&$this, 'bigMailer_options_page'));
    }

    public function bigMailer_scripts() {
        wp_enqueue_script('bigmailerjs', plugin_dir_url(__FILE__) . 'view/js/bigmailer.js', array('jquery'), '1.0', true);
    }

    public function bigMailer_options_page() {
        include plugin_dir_path(__FILE__) . 'view/options.php';
    }

}

$bigmailer = new BigMailer();

if (!function_exists('default_sendgrid_options')) {

    function default_sendgrid_options() {
        $default_sg_options = array(
            'sg_uname' => '',
            'sg_pass' => '',
            'sg_api' => 'SMTP',
            'sg_port' => 'TLS',
        );
        return apply_filters('default_sendgrid_options', $default_sg_options);
    }

}

if (!function_exists('get_sendgrid_options')) {

    function get_sendgrid_options() {
        return get_site_option('sendgrid_options', default_sendgrid_options());
    }

}

if (!function_exists('wp_mail')) {

    function wp_mail($to, $subject, $message, $headers = '', $attachments = array()) {
        $options = get_sendgrid_options();

        extract(apply_filters('wp_mail', compact('to', 'subject', 'message', 'headers', 'attachments')));

        if (!is_array($attachments))
            $attachments = explode("\n", str_replace("\r\n", "\n", $attachments));

        if (!function_exists('sendGridLoader'))
            include plugin_dir_path(__FILE__) . 'sendgrid-php/SendGrid_loader.php';
        $sendgrid = new SendGrid($options['sg_uname'], $options['sg_pass']);
        $mail = new SendGrid\Mail();

// Headers
        if (empty($headers)) {
            $headers = array();
        } else {
            if (!is_array($headers)) {
// Explode the headers out, so this function can take both
// string headers and an array of headers.
                $tempheaders = explode("\n", str_replace("\r\n", "\n", $headers));
            } else {
                $tempheaders = $headers;
            }
            $headers = array();
            $cc = array();
            $bcc = array();
// If it's actually got contents
            if (!empty($tempheaders)) {
// Iterate through the raw headers
                foreach ((array) $tempheaders as $header) {
                    if (strpos($header, ':') === false) {
                        if (false !== stripos($header, 'boundary=')) {
                            $parts = preg_split('/boundary=/i', trim($header));
                            $boundary = trim(str_replace(array("'", '"'), '', $parts[1]));
                        }
                        continue;
                    }
// Explode them out
                    list( $name, $content ) = explode(':', trim($header), 2);

// Cleanup crew
                    $name = trim($name);
                    $content = trim($content);
                    switch (strtolower($name)) {
// Mainly for legacy -- process a From: header if it's there
                        case 'from':
                            if (strpos($content, '<') !== false) {
// So... making my life hard again?
                                $from_name = substr($content, 0, strpos($content, '<') - 1);
                                $from_name = str_replace('"', '', $from_name);
                                $from_name = trim($from_name);

                                $from_email = substr($content, strpos($content, '<') + 1);
                                $from_email = str_replace('>', '', $from_email);
                                $from_email = trim($from_email);
                            } else {
                                $from_email = trim($content);
                            }
                            break;
                        case 'content-type':
                            if (strpos($content, ';') !== false) {
                                list( $type, $charset ) = explode(';', $content);
                                if (!isset($charset))
                                    $charset = get_bloginfo('charset');
                                $content_type = trim($type);
                                if (false !== stripos($charset, 'charset=')) {
                                    $charset = trim(str_replace(array('charset=', '"'), '', $charset));
                                } elseif (false !== stripos($charset, 'boundary=')) {
                                    $boundary = trim(str_replace(array('BOUNDARY=', 'boundary=', '"'), '', $charset));
                                    $charset = '';
                                }
                            } else {
                                $content_type = trim($content);
                            }
                            break;
                        case 'cc':
                            $cc = array_merge((array) $cc, explode(',', $content));
                            break;
                        case 'bcc':
                            $bcc = array_merge((array) $bcc, explode(',', $content));
                            break;
                        case 'reply-to':
                            if (strpos($content, '<') !== false) {
                                $reply_name = substr($content, 0, strpos($content, '<') - 1);
                                $reply_name = str_replace('"', '', $from_name);
                                $reply_name = trim($from_name);

                                $reply_email = substr($content, strpos($content, '<') + 1);
                                $reply_email = str_replace('>', '', $from_email);
                                $reply_email = trim($from_email);
                            } else {
                                $reply_email = trim($content);
                            }
                            $mail->setReplyTo($reply_email);
                            break;
                        default:
                            // Add it to our grand headers array
                            $headers[trim($name)] = trim($content);
                            break;
                    }
                }
            }
        }

// From email and name
// If we don't have a name from the input headers
        if (!isset($from_name))
            $from_name = 'WordPress';

        /* If we don't have an email from the input headers default to wordpress@$sitename
         * Some hosts will block outgoing mail from this address if it doesn't exist but
         * there's no easy alternative. Defaulting to admin_email might appear to be another
         * option but some hosts may refuse to relay mail from an unknown domain. See
         * http://trac.wordpress.org/ticket/5007.
         */

        if (!isset($from_email)) {
// Get the site domain and get rid of www.
            $sitename = strtolower($_SERVER['SERVER_NAME']);
            if (substr($sitename, 0, 4) == 'www.') {
                $sitename = substr($sitename, 4);
            }

            $from_email = 'wordpress@' . $sitename;
        }

// Plugin authors can override the potentially troublesome default
        $mail->setFrom(apply_filters('wp_mail_from', $from_email));
        $mail->setFromName(apply_filters('wp_mail_from_name', $from_name));

// Set destination addresses
        if (!is_array($to))
            $to = explode(',', $to);

        foreach ((array) $to as $recipient) {

// Break $recipient into name and address parts if in the format "Foo <bar@baz.com>"
            $recipient_name = '';
            if (preg_match('/(.*)<(.+)>/', $recipient, $matches)) {
                if (count($matches) == 3) {
                    $recipient_name = $matches[1];
                    $recipient = $matches[2];
                }
            }
            $mail->addTo($recipient, $recipient_name);
        }


// Set mail's subject and body
        $mail->setSubject($subject);


// Add any CC and BCC recipients
        if (!empty($cc)) {
            foreach ((array) $cc as $recipient) {

// Break $recipient into name and address parts if in the format "Foo <bar@baz.com>"
                $recipient_name = '';
                if (preg_match('/(.*)<(.+)>/', $recipient, $matches)) {
                    if (count($matches) == 3) {
                        $recipient_name = $matches[1];
                        $recipient = $matches[2];
                    }
                }
                $mail->addCc($recipient); // no recipient name yet
            }
        }

        if (!empty($bcc)) {
            foreach ((array) $bcc as $recipient) {

// Break $recipient into name and address parts if in the format "Foo <bar@baz.com>"
                $recipient_name = '';
                if (preg_match('/(.*)<(.+)>/', $recipient, $matches)) {
                    if (count($matches) == 3) {
                        $recipient_name = $matches[1];
                        $recipient = $matches[2];
                    }
                }
                $mail->addBcc($recipient); // no recipient name yet
            }
        }

// If we don't have a content-type from the input headers
        if (!isset($content_type))
            $content_type = 'text/plain';

        $content_type = apply_filters('wp_mail_content_type', $content_type);


// Set whether it's plaintext, depending on $content_type
        if ('text/html' == $content_type)
            $mail->setHtml($message);
        else
            $mail->setText($message);


// Set custom headers
        if (!empty($headers)) {
            foreach ((array) $headers as $name => $content) {
                $mail->addHeader($name, $content);
            }
        }

        if (!empty($attachments)) {
            foreach ($attachments as $attachment) {
                $mail->addAttachment($attachment);
            }
        }

// Send!
        if ($options['sg_api'] == 'SMTP') {
            switch ($options['sg_port']) {
                case 'TLS_ALTERNATIVE':
                    $sendgrid->smtp->setPort(\SendGrid\Smtp::TLS_ALTERNATIVE);
                    break;
                case 'SSL':
                    $sendgrid->smtp->setPort(\SendGrid\Smtp::SSL);
                    break;
                default:
                    $sendgrid->smtp->setPort(\SendGrid\Smtp::TLS);
                    break;
            }
            $sendgrid->smtp->send($mail);
        } else {
            $sendgrid->web->send($mail);
        }
        return true;
    }

}

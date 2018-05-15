<?php

  /**
    This is the MailContact plugin.

    This file contains the MailContact plugin. It provides a mail submission feature and a shortcode for the display
    of a contact form.

    @package urlaube\mailcontact
    @version 0.1a2
    @author  Yahe <hello@yahe.sh>
    @since   0.1a0
  */

  // ===== DO NOT EDIT HERE =====

  // prevent script from getting called directly
  if (!defined("URLAUBE")) { die(""); }

  // use PHPMailer classes
  use PHPMailer\PHPMailer\Exception;
  use PHPMailer\PHPMailer\PHPMailer;
  use PHPMailer\PHPMailer\SMTP;

  if (!class_exists("MailContact")) {
    class MailContact extends Translatable {

      // HELPER FUNCTIONS

      protected function configure() {
        // captcha configuration
        Plugins::preset("mailcontact_question", gl("Wähle den Begriff, der nicht passt: Freund, Feind, Nudelsuppe"));
        Plugins::preset("mailcontact_answer",   gl("Nudelsuppe"));

        // SMTP configuration
        Plugins::preset("mailcontact_host",      "localhost");
        Plugins::preset("mailcontact_password",  "");
        Plugins::preset("mailcontact_port",      587);
        Plugins::preset("mailcontact_recipient", "root@localhost");
        Plugins::preset("mailcontact_sender",    "urlaube@localhost");
        Plugins::preset("mailcontact_subject",   gl("Nachricht gesendet über MailContact"));
        Plugins::preset("mailcontact_username",  "anonymous");
      }

      protected function getForm($content) {
        $result = $content;

        if (is_string($result)) {
          // generate form source code
          $form = "<link href=\"".html(path2uri(__DIR__."/css/style.css"))."\" rel=\"stylesheet\">".NL.
                  "<p id=\"mailcontact-failure\"></p>".NL.
                  "<p id=\"mailcontact-success\"></p>".NL.
                  "<form action=\"".html(Main::ROOTURI()."mailcontact/")."\" id=\"mailcontact\" method=\"post\">".NL.
                  "  <p class=\"mailcontact-author\">".NL.
                  "    <label for=\"mailcontact-author\">".html(gl("Name"))."*</label><br>".NL.
                  "    <input id=\"mailcontact-author\" name=\"author\" required=\"required\" type=\"text\">".NL.
                  "  </p>".NL.
                  "  <p class=\"mailcontact-email\">".NL.
                  "    <label for=\"mailcontact-email\">".html(gl("E-Mail"))."*</label><br>".NL.
                  "    <input id=\"mailcontact-email\" name=\"email\" required=\"required\" type=\"email\">".NL.
                  "  </p>".NL.
                  "  <p class=\"mailcontact-message\">".NL.
                  "    <label for=\"mailcontact-message\">".html(gl("Nachricht"))."*</label><br>".NL.
                  "    <textarea autocomplete=\"nope\" id=\"mailcontact-message\" name=\"message\"".
                  " required=\"required\"></textarea>".NL.
                  "  </p>".NL.
                  "  <p class=\"mailcontact-captcha\">".NL.
                  "    <label for=\"mailcontact-captcha\">(".html(gl("Captcha"))."*) ".
                  html(Plugins::get("mailcontact_question"))."</label><br>".NL.
                  "    <input autocomplete=\"nope\" id=\"mailcontact-captcha\" name=\"captcha\"".
                  " required=\"required\" type=\"text\">".NL.
                  "  </p>".NL.
                  "  <p class=\"mailcontact-gdpr\">".NL.
                  "    <span class=\"mailcontact-gdpr-label\">".html(gl("Datenschutzerklärung"))."</span><br>".NL.
                  "    ".html(gl("Für die korrekte Funktionsweise dieses Kontaktformulars müssen die von Ihnen eingegebenen personenbezogenen Daten an den Betreiber dieser Webseite übermittelt werden. Durch Verwendung des Kontaktformulars stimmen Sie der Übermittlung und Speicherung der von Ihnen eingegebenen personenbezogenen Daten zu. Die Daten werden verwendet, um auf Ihre Kontaktanfrage reagieren zu können.")).NL.
                  "  </p>".NL.
                  "  <div class=\"alert alert-danger\" id=\"mailcontact-failure-alert\">".
                  html(gl("Der Versand ist fehlgeschlagen!"))."</div>".NL.
                  "  <div class=\"alert alert-success\" id=\"mailcontact-success-alert\">".
                  html(gl("Der Versand war erfolgreich!"))."</div>".NL.
                  "  <p class=\"mailcontact-submit\">".NL.
                  "    <input id=\"mailcontact-submit\" name=\"submit\" type=\"submit\" value=\"".
                  html(gl("Anfrage absenden"))."\">".NL.
                  "    <input name=\"referer\" type=\"hidden\" value=\"".html(Main::URI())."\">".NL.
                  "  </p>".NL.
                  "  <p class=\"mailcontact-info\">".gl("Pflichtfelder sind mit * markiert.")."</p>".NL.
                  "</form>".NL.
                  "<script src=\"".html(path2uri(__DIR__."/js/script.js"))."\"></script>";

          // replace shortcode with form
          $result = str_ireplace("[mailcontact]", $form, $result);
        }

        return $result;
      }

      protected static function sendMail($author, $email, $message, $via) {
        $phpmailer = new PHPMailer();

        // configure mail server
        $phpmailer->isSMTP();
        $phpmailer->Host = Plugins::get("mailcontact_host");
        $phpmailer->Port = Plugins::get("mailcontact_port");

        // configure authentication
        $phpmailer->SMTPAuth = true;
        $phpmailer->Username = Plugins::get("mailcontact_username");
        $phpmailer->Password = Plugins::get("mailcontact_password");

        // configure sender and receiver
        $phpmailer->setFrom(Plugins::get("mailcontact_sender"));
        $phpmailer->addAddress(Plugins::get("mailcontact_recipient"));

        // configure content
        $phpmailer->isHTML(false);
        $phpmailer->Body    = $author." <".$email."> ".gl("gesendet über")." ".$via.":".NL.NL.$message;
        $phpmailer->CharSet = Main::CHARSET();
        $phpmailer->Subject = Plugins::get("mailcontact_subject");

        return ($phpmailer->send());
      }

      // RUNTIME FUNCTIONS

      public function handler() {
        $result = null;

        // preset plugin configuration
        $this->configure();

        // check if the request comes from the website itself
        if (isset($_POST["referer"]) &&
            isset($_SERVER["HTTP_REFERER"])) {
          if (0 === strcmp(Main::PROTOCOL().Main::HOSTNAME().$_POST["referer"], $_SERVER["HTTP_REFERER"])) {
            // check if the captcha is correct
            if (isset($_POST["captcha"])) {
              if (0 === strcasecmp(Plugins::get("mailcontact_answer"), trim($_POST["captcha"]))) {
                // check if all mandatory fields are given
                if (isset($_POST["author"]) &&
                    isset($_POST["email"]) &&
                    isset($_POST["message"])) {
                  // handle message
                  $result = $this->sendMail($_POST["author"],
                                            $_POST["email"],
                                            $_POST["message"],
                                            $_SERVER["HTTP_REFERER"]);

                  // redirect to previous page
                  if ($result) {
                    redirect($_POST["referer"]."#mailcontact-success", true);
                  } else {
                    redirect($_POST["referer"]."#mailcontact-failure", true);
                  }
                }
              }
            }
          }
        }

        return $result;
      }

      public function plugin() {
        $result = false;

        // preset plugin configuration
        $this->configure();

        if (Main::CONTENT() instanceof Content) {
          if (Main::CONTENT()->isset(CONTENT)) {
            Main::CONTENT()->set(CONTENT, $this->getForm(Main::CONTENT()->get(CONTENT)));

            $result = true;
          }
        } else {
          if (is_array(Main::CONTENT())) {
            // iterate through all content items
            foreach (Main::CONTENT() as $content_item) {
              if ($content_item->isset(CONTENT)) {
                $content_item->set(CONTENT, $this->getForm($content_item->get(CONTENT)));
              }
            }

            $result = true;
          }
        }

        return $result;
      }

    }

    // include PHPMailer
    require_once(__DIR__."/vendors/phpmailer/Exception.php");
    require_once(__DIR__."/vendors/phpmailer/PHPMailer.php");
    require_once(__DIR__."/vendors/phpmailer/SMTP.php");

    // instantiate translatable handler
    $plugin = new MailContact();
    $plugin->setTranslationsPath(__DIR__.DS."lang".DS);

    // register handler
    Handlers::register($plugin, "handler", "@\/mailcontact\/@", [POST]);

    // register plugin
    Plugins::register($plugin, "plugin", BEFORE_THEME);
  }


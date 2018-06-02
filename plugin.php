<?php

  /**
    This is the MailContact plugin.

    This file contains the MailContact plugin. It provides a mail submission feature and a shortcode for the display
    of a contact form.

    @package urlaube\mailcontact
    @version 0.1a6
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
    class MailContact extends Base implements Handler, Plugin {

      // INTERFACE FUNCTIONS

      public static function getContent($info) {
        return null;
      }

      public static function getUri($info) {
        return Main::ROOTURI()."mailcontact".US;
      }

      public static function parseUri($uri) {
        $result = null;

        if (is_string($uri)) {
          if (1 === preg_match("@\/mailcontact\/@",
                               $uri, $matches)) {
            $result = array();
          }
        }

        return $result;
      }

      // HELPER FUNCTIONS

      protected static function configure() {
        // captcha configuration
        Plugins::preset("mailcontact_question", t("Wähle den Begriff, der nicht passt: Freund, Feind, Nudelsuppe", "MailContact"));
        Plugins::preset("mailcontact_answer",   t("Nudelsuppe", "MailContact"));

        // SMTP configuration
        Plugins::preset("mailcontact_host",      "localhost");
        Plugins::preset("mailcontact_password",  "");
        Plugins::preset("mailcontact_port",      587);
        Plugins::preset("mailcontact_recipient", "root@localhost");
        Plugins::preset("mailcontact_sender",    "urlaube@localhost");
        Plugins::preset("mailcontact_subject",   t("Nachricht gesendet über MailContact", "MailContact"));
        Plugins::preset("mailcontact_username",  "anonymous");
      }

      protected static function getForm($content) {
        $result = $content;

        if (is_string($result)) {
          // generate form source code
          $form = tfhtml("<link href=\"%s\" rel=\"stylesheet\">".NL.
                         "<p id=\"mailcontact-failure\"></p>".NL.
                         "<p id=\"mailcontact-success\"></p>".NL.
                         "<form action=\"%s\" id=\"mailcontact\" method=\"post\">".NL.
                         "  <p class=\"mailcontact-author\">".NL.
                         "    <label for=\"mailcontact-author\">%s*</label><br>".NL.
                         "    <input id=\"mailcontact-author\" name=\"author\" required=\"required\" type=\"text\">".NL.
                         "  </p>".NL.
                         "  <p class=\"mailcontact-email\">".NL.
                         "    <label for=\"mailcontact-email\">%s*</label><br>".NL.
                         "    <input id=\"mailcontact-email\" name=\"email\" required=\"required\" type=\"email\">".NL.
                         "  </p>".NL.
                         "  <p class=\"mailcontact-message\">".NL.
                         "    <label for=\"mailcontact-message\">%s*</label><br>".NL.
                         "    <textarea autocomplete=\"nope\" id=\"mailcontact-message\" name=\"message\" required=\"required\"></textarea>".NL.
                         "  </p>".NL.
                         "  <p class=\"mailcontact-captcha\">".NL.
                         "    <label for=\"mailcontact-captcha\">(%s*) %s</label><br>".NL.
                         "    <input autocomplete=\"nope\" id=\"mailcontact-captcha\" name=\"captcha\" required=\"required\" type=\"text\">".NL.
                         "  </p>".NL.
                         "  <p class=\"mailcontact-gdpr\">".NL.
                         "    <span class=\"mailcontact-gdpr-label\">%s</span><br>".NL.
                         "    %s".NL.
                         "  </p>".NL.
                         "  <div class=\"alert alert-danger\" id=\"mailcontact-failure-alert\">%s</div>".NL.
                         "  <div class=\"alert alert-success\" id=\"mailcontact-success-alert\">%s</div>".NL.
                         "  <p class=\"mailcontact-submit\">".NL.
                         "    <input name=\"referer\" type=\"hidden\" value=\"%s\">".NL.
                         "    <input id=\"mailcontact-submit\" name=\"submit\" type=\"submit\" value=\"%s\">".NL.
                         "  </p>".NL.
                         "  <p class=\"mailcontact-info\">%s</p>".NL.
                         "</form>".NL.
                         "<script src=\"%s\"></script>",
                         "MailContact",
                         path2uri(__DIR__."/css/style.css"),
                         Main::ROOTURI()."mailcontact/",
                         "Name",
                         "E-Mail",
                         "Nachricht",
                         "Captcha",
                         Plugins::get("mailcontact_question"),
                         "Datenschutzerklärung",
                         "[DATENSCHUTZERKLÄRUNG]",
                         "Der Versand ist fehlgeschlagen!",
                         "Der Versand war erfolgreich!",
                         Main::URI(),
                         "Anfrage absenden",
                         "Pflichtfelder sind mit * markiert.",
                         path2uri(__DIR__."/js/script.js"));

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
        $phpmailer->Body    = sprintf("%s <%s> %s %s".NL.
                                      NL.
                                      "%s",
                                      $author,
                                      $email,
                                      t("gesendet über", "MailContact")),
                                      $via,
                                      $message;
        $phpmailer->CharSet = Main::CHARSET();
        $phpmailer->Subject = Plugins::get("mailcontact_subject");

        return ($phpmailer->send());
      }

      // RUNTIME FUNCTIONS

      public static function handler() {
        $result = false;

        // preset plugin configuration
        static::configure();

        $info = static::parseUri(Main::RELATIVEURI());
        if (null !== $info) {
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
                    $result = static::sendMail($_POST["author"],
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
        }

        return $result;
      }

      public static function plugin($content) {
        $result = $content;

        // preset plugin configuration
        static::configure();

        if ($result instanceof Content) {
          if ($result->isset(CONTENT)) {
            $result->set(CONTENT, static::getForm($result->get(CONTENT)));
          }
        } else {
          if (is_array($result)) {
            // iterate through all content items
            foreach ($result as $result_item) {
              if ($result_item instanceof Content) {
                if ($result_item->isset(CONTENT)) {
                  $result_item->set(CONTENT, static::getForm($result_item->get(CONTENT)));
                }
              }
            }
          }
        }

        return $result;
      }

    }

    // include PHPMailer
    require_once(__DIR__."/vendors/phpmailer/Exception.php");
    require_once(__DIR__."/vendors/phpmailer/PHPMailer.php");
    require_once(__DIR__."/vendors/phpmailer/SMTP.php");

    // register handler
    Handlers::register("MailContact", "handler",
                       "@\/mailcontact\/@",
                       [POST], USER);

    // register plugin
    Plugins::register("MailContact", "plugin", FILTER_CONTENT);

    // register translation
    Translate::register(__DIR__.DS."lang".DS, "MailContact");
  }


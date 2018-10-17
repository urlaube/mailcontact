<?php

  /**
    This is the MailContact plugin.

    This file contains the MailContact plugin. It provides a mail submission
    feature and a shortcode for the display of a contact form.

    @package urlaube\mailcontact
    @version 0.1a9
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

  class MailContact extends BaseSingleton implements Handler, Plugin {

    // CONSTANTS

    const REGEX = "~\/mailcontact\/~";

    // INTERFACE FUNCTIONS

    public static function getContent($metadata, &$pagecount) {
      return null;
    }

    public static function getUri($metadata) {
      return value(Main::class, ROOTURI)."mailcontact".US;
    }

    public static function parseUri($uri) {
      $result = null;

      $metadata = preparecontent(parseuri($uri, static::REGEX));
      if ($metadata instanceof Content) {
        $result = $metadata;
      }

      return $result;
    }

    // HELPER FUNCTIONS

    protected static function configure() {
      // captcha configuration
      Plugins::preset("mailcontact_question", t("Wähle den Begriff, der nicht passt: Freund, Feind, Nudelsuppe", MailContact::class));
      Plugins::preset("mailcontact_answer",   t("Nudelsuppe", MailContact::class));

      // SMTP configuration
      Plugins::preset("mailcontact_host",      "localhost");
      Plugins::preset("mailcontact_password",  "");
      Plugins::preset("mailcontact_port",      587);
      Plugins::preset("mailcontact_recipient", "root@localhost");
      Plugins::preset("mailcontact_sender",    "urlaube@localhost");
      Plugins::preset("mailcontact_subject",   t("Nachricht gesendet über MailContact", MailContact::class));
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
                       MailContact::class,
                       path2uri(__DIR__."/css/style.css"),
                       static::getUri(new Content()),
                       "Name",
                       "E-Mail",
                       "Nachricht",
                       "Captcha",
                       value(Plugins::class, "mailcontact_question"),
                       "Datenschutzerklärung",
                       "[DATENSCHUTZERKLÄRUNG]",
                       "Der Versand ist fehlgeschlagen!",
                       "Der Versand war erfolgreich!",
                       value(Main::class, URI),
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
      $phpmailer->Host = value(Plugins::class, "mailcontact_host");
      $phpmailer->Port = value(Plugins::class, "mailcontact_port");

      // configure authentication
      $phpmailer->SMTPAuth = true;
      $phpmailer->Username = value(Plugins::class, "mailcontact_username");
      $phpmailer->Password = value(Plugins::class, "mailcontact_password");

      // configure sender and receiver
      $phpmailer->setFrom(value(Plugins::class, "mailcontact_sender"));
      $phpmailer->addAddress(value(Plugins::class, "mailcontact_recipient"));

      // configure content
      $phpmailer->isHTML(false);
      $phpmailer->Body    = sprintf("%s <%s> %s %s".NL.
                                    NL.
                                    "%s",
                                    $author,
                                    $email,
                                    t("gesendet über", MailContact::class),
                                    $via,
                                    $message);
      $phpmailer->CharSet = value(Main::class, CHARSET);
      $phpmailer->Subject = value(Plugins::class, "mailcontact_subject");

      return ($phpmailer->send());
    }

    // RUNTIME FUNCTIONS

    public static function handler() {
      $result = false;

      // preset plugin configuration
      static::configure();

      $metadata = static::parseUri(relativeuri());
      if (null !== $metadata) {
        // check if the URI is correct
        $fixed = static::getUri($metadata);
        if (0 !== strcmp(value(Main::class, URI), $fixed)) {
          relocate($fixed, false, true);

          // we handled this page
          $result = true;
        } else {
          // check if the request comes from the website itself
          if (isset($_POST["referer"]) && isset($_SERVER["HTTP_REFERER"])) {
            if (0 === strcmp(absoluteurl($_POST["referer"]), $_SERVER["HTTP_REFERER"])) {
              // at least we can handle this request
              $success = false;

              // check if the captcha is correct
              if (isset($_POST["captcha"])) {
                if (0 === strcasecmp(value(Plugins::class, "mailcontact_answer"), trim($_POST["captcha"]))) {
                  // check if all mandatory fields are given
                  if (isset($_POST["author"]) && isset($_POST["email"]) && isset($_POST["message"])) {
                    // handle message
                    $success = static::sendMail($_POST["author"], $_POST["email"], $_POST["message"], $_SERVER["HTTP_REFERER"]);
                  }
                }
              }

              // redirect to previous page
              if ($success) {
                relocate($_POST["referer"]."#mailcontact-success", false, false);
              } else {
                relocate($_POST["referer"]."#mailcontact-failure", false, false);
              }

              // we handled this page
              $result = true;
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
          $result->set(CONTENT, static::getForm(value($result, CONTENT)));
        }
      } else {
        if (is_array($result)) {
          // iterate through all content items
          foreach ($result as $result_item) {
            if ($result_item instanceof Content) {
              if ($result_item->isset(CONTENT)) {
                $result_item->set(CONTENT, static::getForm(value($result_item, CONTENT)));
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
  Handlers::register(MailContact::class, "handler", MailContact::REGEX, [POST], USER);

  // register plugin
  Plugins::register(MailContact::class, "plugin", FILTER_CONTENT);

  // register translation
  Translate::register(__DIR__.DS."lang".DS, MailContact::class);

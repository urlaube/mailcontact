# MailContact plugin
The MailContact plugin is a contact form plugin for [Urlaub.be](https://github.com/urlaube/urlaube) that is based on the [PHPMailer](https://github.com/PHPMailer/PHPMailer) library.

## Installation
Place the folder containing the plugin into your plugins directory located at `./user/plugins/`.

## Configuration
To configure the plugin you can change the corresponding settings in your configuration file located at `./user/config/config.php`.

### CAPTCHA
You can set the question and answer of the CAPTCHA:
```
Plugins::set("mailcontact_question", t("Wähle den Begriff, der nicht passt: Freund, Feind, Nudelsuppe", MailContact::class));
Plugins::set("mailcontact_answer",   t("Nudelsuppe", MailContact::class));
```

### SMTP mail server configuration
You can set the SMTP mail server configuration:
```
Plugins::set("mailcontact_host",      "localhost");
Plugins::set("mailcontact_password",  "");
Plugins::set("mailcontact_port",      587);
Plugins::set("mailcontact_recipient", "root@localhost");
Plugins::set("mailcontact_sender",    "urlaube@localhost");
Plugins::set("mailcontact_username",  "anonymous");
```

### Mail subject text
You can overwrite subject text of the sent mail:
```
Plugins::set("mailcontact_subject", t("Nachricht gesendet über MailContact", MailContact::class));
```

# MailContact plugin
The MailContact plugin is a contact form theme for [Urlaub.be](https://github.com/urlaube/base) that is based on the [PHPMailer](https://github.com/PHPMailer/PHPMailer) library.

## Installation
Place the folder containing the plugin into your plugins directory located at `./user/plugins/`.

## Configuration
To configure the plugin you can change the corresponding settings in your configuration file located at `./user/config/config.php`.

### CAPTCHA
You can set the question and answer of the CAPTCHA:
```
Config::PLUGIN("mailcontact_question", gl("Wähle den Begriff, der nicht passt: Freund, Feind, Nudelsuppe"));
Config::PLUGIN("mailcontact_answer",   gl("Nudelsuppe"));
```

### SMTP mail server configuration
You can set the SMTP mail server configuration:
```
Config::PLUGIN("mailcontact_host",      "localhost");
Config::PLUGIN("mailcontact_password",  "");
Config::PLUGIN("mailcontact_port",      587);
Config::PLUGIN("mailcontact_recipient", "root@localhost");
Config::PLUGIN("mailcontact_sender",    "urlaube@localhost");
Config::PLUGIN("mailcontact_username",  "anonymous");
```

### Mail subject text
You can overwrite subject text of the sent mail:
```
Config::PLUGIN("mailcontact_subject", gl("Nachricht gesendet über MailContact"));
```


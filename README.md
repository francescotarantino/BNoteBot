# BNoteBot
Telegram Bot for save notes callable through inline mode and set reminders!

![Logo BNoteBot](https://raw.githubusercontent.com/franci22/BNoteBot/master/Logo.png)

Telegram Username: [@BNoteBot](https://telegram.me/BNoteBot)

## How to contribute
Fork this project, create a new branch, commit all changes and create a pull request!

If you want to help translate this project create a new file into lang/ directory named: __message.__*languagecode*__.php__. Use the file __message.en.php__ as a guide. If you've seen a wrong translation send an issue.

## How to clone
[Download](https://github.com/franci22/BNoteBot/releases) a release, unzip, execute __create_table.SQL__ to create tables in your MySQL Database, setup __config.php__ and set [Telegram webhook](https://core.telegram.org/bots/api#setwebhook) to __bot.php__ file.
After that you should execute `cronjob.php` every minute, just setup a cron-job.

#### How to setup the webhook
You can simply run `setWebhook.php`.

If you want to do it manually use this URL: `https://api.telegram.org/botTOKEN/setWebhook?url=BOTURL`

For example: `https://api.telegram.org/bot123456:ABC-DEF1234ghIkl-zyx57W2v1u123ew11/setWebhook?url=https://domain.com/bot.php`

> Remember that the TOKEN must start with 'bot' and the BOTURL with 'https://'.

## Make a donation
If you like my work, you could make a donation: [PayPal](https://PayPal.me/franci22/2) or [BitCoin](https://paste.ubuntu.com/24299810/).
**Thank you!**

## Channel
Follow me on my Channel: [@franci22channel](https://telegram.me/franci22channel) (Italian Version) and [@franci22channel_en](https://telegram.me/franci22channel_en) (English Version).

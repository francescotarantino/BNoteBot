# BNoteBot [![Bot](https://img.shields.io/badge/Telegram-%40BNoteBot-blue.svg)](https://telegram.me/BNoteBot)  [![License: GPL v3](https://img.shields.io/badge/License-GPL%20v3-brightgreen.svg)](https://www.gnu.org/licenses/gpl-3.0) ![Maintenance](https://img.shields.io/maintenance/no/2023)

Telegram Bot for save notes callable through inline mode and set reminders!

![Logo BNoteBot](https://raw.githubusercontent.com/franci22/BNoteBot/master/Logo.png)

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

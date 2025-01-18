# BNoteBot [![Bot](https://img.shields.io/badge/Telegram-%40BNoteBot-blue.svg)](https://telegram.me/BNoteBot) [![License: GPL v3](https://img.shields.io/badge/License-GPL%20v3-brightgreen.svg)](https://www.gnu.org/licenses/gpl-3.0) ![Maintenance](https://img.shields.io/maintenance/no/2023)

Save notes, call them through inline mode, and set reminders!

This project is no longer maintained.

![Logo BNoteBot](https://raw.githubusercontent.com/francescotarantino/BNoteBot/master/Logo.png)

## How to clone

[Download](https://github.com/francescotarantino/BNoteBot/releases) a release, unzip, execute **create_table.SQL** to create tables in your MySQL Database, setup **config.php** and set [Telegram webhook](https://core.telegram.org/bots/api#setwebhook) to **bot.php** file.
After that you should execute `cronjob.php` every minute, just setup a cron-job.

#### How to setup the webhook

You can simply run `setWebhook.php`.

If you want to do it manually use this URL: `https://api.telegram.org/botTOKEN/setWebhook?url=BOTURL`

For example: `https://api.telegram.org/bot123456:ABC-DEF1234ghIkl-zyx57W2v1u123ew11/setWebhook?url=https://domain.com/bot.php`

> Remember that the TOKEN must start with 'bot' and the BOTURL with 'https://'.

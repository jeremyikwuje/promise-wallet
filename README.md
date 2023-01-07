## Promise Wallet
Promise a self hosted bitcoin wallet for sending and recieving bitcoins. If you want to build a custodial bitcoin wallet for sending and recieving bitcoin, you can fork this code and self host.

## 🎨 Features
1. Account creation (name and email)
2. Passwordless login (email)
3. Wallet creation
4. Send / Recieve (onchain and between internal ledgers)
5. Transaction history
6. Account updates
7. Self Hosted
8. No Bitcoin Node required
9. Native APIs

## Requirements
The following softwares and services are required to run this projects.

* PHP 8.0 and above
* MySQL or MariaDb database
* Tatum API Keys
* MailGun API Keys

## How to run
1. Download or clone this repo
2. Open your terminal and run `composer install`
3. Create the neccessary database tables by executing the file `database/tables.sql`
4. Rename the `.env-example` file to `.env` and input your database info and other config details 
4. Within your terminal, navigate to `/app`
5. Start up a PHP server `php -S localhost:5000`
6. Test run on POSTMAN

## Passwordless login
We don't like to remember passwords. Passwords are predicatable. This wallet provides an endpoint to authenticate users via email. When a you input an email to the `/auth/new-token` endpoint, a code will be sent to your email to verify. Once your verification is successfull, you are granted access. No need to remember or save passwords and someone has to gain access to your email account to have access to your funds.

Emails are sent via MailGun email service, so you will need to create a MailGun account and add your keys to the configuration file.

## Powered by Tatum
This wallet is powered by Tatum API. [Tatum API](https://tatum.io) is a blockchain infastructure service that provide API for building Bitcoin wallets. You will need to create a Tatum account, get your API keys, and input them in the configuration file.

## Need support
If you run into an issue, kindly open an issue. If you need custom or extensive support self hosting this project you can message me on [Twitter](https://twitter.com/jeremyikwuje) or [Telegram](https://t.me/ikwuje).

This project is currently on Beta and not yet recommended for production.

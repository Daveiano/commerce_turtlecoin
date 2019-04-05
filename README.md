[<img align="right" alt="TurtleCoin" width="440" height="440" src="https://raw.githubusercontent.com/turtlecoin/brand/master/logo/web/stacked/turtlecoin_stacked_color%402x.png">](https://turtlecoin.lol)

# Commerce TurtleCoin
[<img src="https://badge.turtlepay.io/">](https://turtlepay.io)

Payment Gateway for Drupal Commerce to allow payments with [TurtleCoin](https://turtlecoin.lol).

**Work in progress. Under active development.**

Issue Handling is done on Drupal.org: [Drupal Project Page](https://www.drupal.org/sandbox/daveiano/3029539)

## What this Modules does

Commerce TurtleCoin is providing two (currently one, second is in development) 
Payment Gateways for paying with [TurtleCoin](https://turtlecoin.lol) in 
[Drupal Commerce](https://www.drupal.org/project/commerce). 

The first is an 
implementation with the [Wallet RPC API](https://api-docs.turtlecoin.lol/?php#wallet-rpc-api)
to generate an integrated address on checkout and track the transaction
to void it after a given time or complete it if the payment arrives.

The second payment gateway uses [TurtlePay™](https://turtlepay.io/) to process the payment.

<img align="center" alt="TurtlePay Payment Instructions" src="https://www.drupal.org/files/project-images/turtlepay-themed-payment-instructions.png">

## Installation

The gateway implemented with Wallet RPC API needs a running and connected
TurtleCoind and turtle-service service.

See here: [https://github.com/turtlecoin/turtlecoin](https://github.com/turtlecoin/turtlecoin)

For installation and setting up a wallet see here: [https://github.com/turtlecoin/turtlecoin/wiki/Using-zedwallet](https://github.com/turtlecoin/turtlecoin/wiki/Using-zedwallet)

This module includes two very basic config files you could use for the
two services as a basis. You can use them via

`./TurtleCoind -c path/to/config/TurtleCoind-config.json`

and 

`./turtle-service -c path/to/config/turtle-service-config.json`. 

**Warning:** Change the passwords for production.

Donate to support my work: 
`TRTLv211SzUJigmnbqM5mYbv8asQvJEzBBWUdBNw2GSXMpDu3m2Csf63j2dHRSkCbDGMb24a4wTjc82JofqjgTao9zjd7ZZnhA1`

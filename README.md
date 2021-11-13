[<img align="right" alt="TurtleCoin" src="https://raw.githubusercontent.com/turtlecoin/brand/master/logo/web/stacked/turtlecoin_stacked_color%402x.png">](https://turtlecoin.lol)

# Commerce TurtleCoin
[<img src="https://badge.turtlepay.io/">](https://turtlepay.io)

Payment Gateway for Drupal Commerce to allow payments with
[TurtleCoin](https://turtlecoin.lol).

**Work in progress. Under active development.**

Issue Handling is done on Drupal.org:
[Drupal Project Page](https://www.drupal.org/sandbox/daveiano/3029539)

## What this Modules does

Commerce TurtleCoin is providing two Payment Gateways for paying with
[TurtleCoin](https://turtlecoin.lol) in
[Drupal Commerce](https://www.drupal.org/project/commerce).

The first is an implementation with the
[Wallet API](https://turtlecoin.github.io/wallet-api-docs/)
to generate an integrated address on checkout and track the transaction
to void it after a given time or complete it if the payment arrives.

The second payment gateway uses [TurtlePay™](https://turtlepay.io/)
to process the payment.

<img align="center" alt="TurtlePay Payment Instructions" src="https://www.drupal.org/files/project-images/turtlepay-themed-payment-instructions.png">

You can enter the product prices in Euro or Dollar, an Exchange rate service for Commerce Currency Resolver is included which uses the <a href="https://min-api.cryptocompare.com/">CryptoCompare API</a> to calculate the TRTL price if necessary.

## Installation

The gateway implemented with Wallet API needs a running and connected
TurtleCoind and wallet-api service running.

See here:
[https://github.com/turtlecoin/turtlecoin](https://github.com/turtlecoin/turtlecoin)

For installation and setting up a wallet see here:
[https://docs.turtlecoin.lol/guides/wallets/using-zedwallet](https://docs.turtlecoin.lol/guides/wallets/using-zedwallet)

The instructions from the
<a href="https://github.com/turtlecoin/turtlecoin-woocommerce-gateway#set-up-turtlecoin-daemon-and-turtle-service">
turtlecoin-woocommerce-gateway</a> are also very useful
([Link](https://github.com/turtlecoin/turtlecoin-woocommerce-gateway#set-up-turtlecoin-daemon-and-turtle-service)):

<blockquote>After downloading (or compiling) the TurtleCoin binaries on your
server, run <code>TurtleCoind</code> and <code>turtle-service</code>.
You can skip running <code>TurtleCoind</code> by using a remote node
with <code>turtle-service</code> by adding <code>--daemon-address</code>
and the address of a public node.

Note on security: using this option, while the most secure, requires you to run
the Turtle-Service program on your server. Best practice for this is to use a
view-only wallet since otherwise your server would be running a hot-wallet and
a security breach could allow hackers to empty your funds.
</blockquote>

This module includes a very basic config file for the TurtleCoin daemon,
you can use it via

`./TurtleCoind -c path/to/config/TurtleCoind-config.json`

You need also to start the wallet-api:

`./wallet-api --port 8070 --rpc-password password`

The payment gateway needs to be able to open a wallet for accessing the
wallet-api. Please specify your wallet along with the damon settings in the
setting.php like this:

```
$settings['turtlecoin_wallet_config'] = [
  "daemonHost" => "127.0.0.1",
  "daemonPort" => 11898,
  "filename" => "mywallet.wallet",
  "password" => "password",
];
```

**Warning:** Change the passwords for production.

Donate to support my work:
`TRTLv211SzUJigmnbqM5mYbv8asQvJEzBBWUdBNw2GSXMpDu3m2Csf63j2dHRSkCbDGMb24a4wTjc82JofqjgTao9zjd7ZZnhA1`

<?php

namespace Drupal\commerce_turtlecoin;

/**
 * Class TurtleCoinService.
 */
class TurtleCoinService {

  /**
   * Gateways which should use TRTL currency.
   */
  const TURTLE_PAYMENT_GATEWAYS = [
    'turtlepay_payment_gateway',
    'turtlecoin_payment_gateway',
  ];

  const TURTLE_CURRENCY_CODE_PSEUDO = 'TRT';

  const TURTLE_CURRENCY_CODE = 'TRTL';

  /**
   * Validate a given TurtleCoin address.
   *
   * @param string $turtlecoin_address
   *   The address to validate.
   *
   * @return bool
   *   Valid or Invalid.
   */
  public static function validate($turtlecoin_address) {
    if (strlen($turtlecoin_address) == 99 && substr($turtlecoin_address, 0, 4) === 'TRTL') {
      return TRUE;
    }

    return FALSE;
  }

  /**
   * Creates a turtle payment ID.
   *
   * @return string
   *   A valid paymentId (64char hex string).
   */
  public static function createPaymentId() {
    return bin2hex(openssl_random_pseudo_bytes(32));
  }

}

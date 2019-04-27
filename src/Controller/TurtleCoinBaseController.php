<?php

namespace Drupal\commerce_turtlecoin\Controller;

use Drupal\Core\Controller\ControllerBase;

/**
 * Defines TurtleCoinBaseController class.
 */
class TurtleCoinBaseController extends ControllerBase {

  /**
   * Gateways which should use TRTL currency.
   */
  const TURTLE_PAYMENT_GATEWAYS = [
    'turtlepay_payment_gateway',
    'turtlecoin_payment_gateway',
  ];

  const TURTLE_CURRENCY_CODE = 'XTR';

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
    if (strlen($turtlecoin_address) == 99 && substr($turtlecoin_address, 4)) {
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

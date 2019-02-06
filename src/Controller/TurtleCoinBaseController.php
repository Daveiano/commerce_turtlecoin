<?php

namespace Drupal\commerce_turtlecoin\Controller;

use Drupal\Core\Controller\ControllerBase;

/**
 * Defines TurtleCoinBaseController class.
 */
class TurtleCoinBaseController extends ControllerBase {

  /**
   * Validate a given TurtleCoin address.
   *
   * @param string $turtlecoin_address
   *   The address to validate.
   *
   * @return bool
   *   Valid or Invalid.
   */
  public function validate($turtlecoin_address) {
    if (strlen($turtlecoin_address) == 99 && substr($turtlecoin_address, 4)) {
      return TRUE;
    }

    return FALSE;
  }

}

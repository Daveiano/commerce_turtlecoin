<?php

namespace Drupal\Tests\commerce_turtlecoin\Unit;

use Drupal\Tests\UnitTestCase;
use Drupal\commerce_turtlecoin\TurtleCoinService;

/**
 * Tests the TurtleCoinService class.
 *
 * @group commerce_turtlecoin
 * @coversDefaultClass \Drupal\commerce_turtlecoin\TurtleCoinService
 */
class TurtleCoinServiceTest extends UnitTestCase {

  /**
   * Tests the TRTL address validation.
   */
  public function testAddressValidation() {
    $this->assertEquals(TRUE, TurtleCoinService::validate('TRTLv211SzUJigmnbqM5mYbv8asQvJEzBBWUdBNw2GSXMpDu3m2Csf63j2dHRSkCbDGMb24a4wTjc82JofqjgTao9zjd7ZZnhA1'));
    $this->assertEquals(FALSE, TurtleCoinService::validate('TRTLv211SzUJigmnbqM5mYbv8asQvJEzBBWUdBNw2GSXMpDu3m2Csf63j2dHRSkCbDGMb24a4wTjc82JofqjgTao9zjd7ZZnhA'));
    $this->assertEquals(FALSE, TurtleCoinService::validate('TTTLv211SzUJigmnbqM5mYbv8asQvJEzBBWUdBNw2GSXMpDu3m2Csf63j2dHRSkCbDGMb24a4wTjc82JofqjgTao9zjd7ZZnhA1'));
  }

  /**
   * Test the payment id generation.
   */
  public function testPaymentIdGeneration() {
    $paymentId = TurtleCoinService::createPaymentId();

    $this->assertEquals(64, strlen($paymentId));
  }

}

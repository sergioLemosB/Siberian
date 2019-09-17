<?php

namespace PaymentMethod\Controller;

use \Application_Controller_Mobile_Default;

/**
 * Class AbstractMobilePaymentController
 * @package PaymentMethod\Controller
 */
abstract class AbstractMobilePaymentController extends Application_Controller_Mobile_Default
{
    /**
     * Test endpoint for payment_method!
     */
    public function testAction()
    {
        $this->_sendJson([
            "success" => true,
            "message" => "Success",
        ]);
    }
}
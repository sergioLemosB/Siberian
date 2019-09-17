<?php

namespace PaymentCash\Model;

use PaymentMethod\Model\GatewayAbstract;

/**
 * Class Cash
 * @package PaymentStripe\Model
 */
class Cash extends GatewayAbstract
{
    /**
     * @var array
     */
    public static $paymentMethod = "cash";
}
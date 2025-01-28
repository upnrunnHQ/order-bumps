<?php
if (!defined('ABSPATH')) {
    exit;
}

class CartTotalCondition implements ProductDisplayCondition {
    private $minimumCartTotal;

    public function __construct($minimumCartTotal) {
        $this->minimumCartTotal = $minimumCartTotal;
    }

    public function isSatisfied(): bool {
        $cartTotal = WC()->cart->get_cart_contents_total();
        return $cartTotal >= $this->minimumCartTotal;
    }
}

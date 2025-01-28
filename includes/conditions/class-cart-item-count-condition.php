<?php
if (!defined('ABSPATH')) {
    exit;
}

class CartItemCountCondition implements ProductDisplayCondition {
    private $minimumItems;

    public function __construct($minimumItems) {
        $this->minimumItems = $minimumItems;
    }

    public function isSatisfied(): bool {
        $cartItemCount = WC()->cart->get_cart_contents_count();
        return $cartItemCount >= $this->minimumItems;
    }
}


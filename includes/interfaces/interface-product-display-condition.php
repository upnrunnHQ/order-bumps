<?php
if (!defined('ABSPATH')) {
    exit; // Exit if accessed directly
}

// Define the interface for product display conditions
interface ProductDisplayCondition {
    /**
     * Check if the condition is satisfied.
     * 
     * @return bool
     */
    public function isSatisfied(): bool;
}

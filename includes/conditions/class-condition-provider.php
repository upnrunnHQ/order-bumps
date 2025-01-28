<?php
if (!defined('ABSPATH')) {
    exit;
}

class ConditionProvider {
    protected $conditions = [];

    public function register_condition($condition) {
        $this->conditions[] = $condition;
        return $this->conditions;
    }

    public function get_conditions() {
        return $this->conditions;
    }

    public function evaluate_conditions() {
        foreach ($this->conditions as $condition) {
            if (!$condition->isSatisfied()) {
                return false;
            }
        }
        return true;
    }
}

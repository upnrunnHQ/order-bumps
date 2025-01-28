<?php
if (!defined('ABSPATH')) {
    exit;
}

class CompositeCondition implements ProductDisplayCondition {
    /**
     * @var ProductDisplayCondition[] Array of conditions to evaluate.
     */
    private $conditions = [];

    /**
     * @var string Logic to use for evaluation ('AND' or 'OR').
     */
    private $logic = 'AND';

    /**
     * Set the logical operator for evaluating conditions.
     *
     * @param string $logic Either 'AND' or 'OR'.
     */
    public function setLogic(string $logic): void {
        $this->logic = strtoupper($logic) === 'OR' ? 'OR' : 'AND';
    }

    /**
     * Add a condition to the composite.
     *
     * @param ProductDisplayCondition $condition The condition to add.
     */
    public function addCondition(ProductDisplayCondition $condition): void {
        $this->conditions[] = $condition;
    }

    /**
     * Add multiple conditions to the composite.
     *
     * @param ProductDisplayCondition[] $conditions The conditions to add.
     */
    public function addConditions(array $conditions): void {
        foreach ($conditions as $condition) {
            if ($condition instanceof ProductDisplayCondition) {
                $this->addCondition($condition);
            }
        }
    }

    /**
     * Check if the composite condition is satisfied based on the specified logic.
     *
     * @return bool True if the composite condition is satisfied, otherwise false.
     */
    public function isSatisfied(): bool {
        if (empty($this->conditions)) {
            return false; // No conditions to evaluate.
        }

        switch ($this->logic) {
            case 'OR':
                // OR Logic: Return true if any condition is satisfied.
                foreach ($this->conditions as $condition) {
                    if ($condition->isSatisfied()) {
                        return true;
                    }
                }
                return false;

            case 'AND':
            default:
                // AND Logic: Return true only if all conditions are satisfied.
                foreach ($this->conditions as $condition) {
                    if (!$condition->isSatisfied()) {
                        return false;
                    }
                }
                return true;
        }
    }

    /**
     * Get the list of conditions (read-only).
     *
     * @return ProductDisplayCondition[] Array of conditions.
     */
    public function getConditions(): array {
        return $this->conditions;
    }
}
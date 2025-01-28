<?php
if (!defined('ABSPATH')) {
    exit;
}

class User_Logged_In_Condition {
    public function is_met() {
        return is_user_logged_in();
    }
}

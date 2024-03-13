<?php

namespace App\Controllers;

class Test extends BaseController {
    
    public function customer() {
        $disabledCustomers = 0;
        $date = "2024-01-14";
        $allCustomers = $this->db->query("SELECT * FROM `customer`")->getResultArray();
        for ($i=0; $i<sizeof($allCustomers); $i++) {
            $customer = $allCustomers[$i];
            $subscriptionID = intval($customer['current_subscription_id']);
            if ($subscriptionID != 0) {
                $subscription = $this->db->query("SELECT * FROM `subscription` WHERE `id`=".$subscriptionID)->getRowArray();
                if ($subscription == NULL) continue;
                $expiry = $subscription['expiry'];
                $overdue = $this->db->query("SELECT * FROM `overdue` WHERE `customer_id`=".$customer['id']." AND `subscription_id`=".$subscriptionID)->getRowArray();
                if ($overdue != NULL) {
                    if (intval($overdue['paid']) < intval($overdue['overdue'])) {
                        $distanceDays = getDistanceDays($expiry, $date);
                        if ($distanceDays>1 && $distanceDays<=60) {
                        $disabledCustomers++;
                        }
                    }
                }
            }
        }
        return "".$disabledCustomers;
    }
}
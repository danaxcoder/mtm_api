<?php

namespace App\Controllers;

class Setup extends BaseController {
    
    public function populate_area_ids() {
        $customers = $this->db->query("SELECT * FROM `customer`")->getResultArray();
        $areas = $this->db->query("SELECT * FROM `area`")->getResultArray();
        for ($i=0; $i<sizeof($customers); $i++) {
            $customer = $customers[$i];
            $areaID = intval($customer['area_id']);
            if ($areaID == 0) {
                $address = strtolower($customer['address']);
                for ($j=0; $j<sizeof($areas); $j++) {
                    $area = $areas[$j];
                    if (strpos($address, strtolower($area['name'])) !== false) {
                        $areaID = intval($area['id']);
                        break;
                    }
                }
                $this->db->query("UPDATE `customer` SET `area_id`=".$areaID." WHERE `id`=".$customer['id']);
            }
        }
    }
}
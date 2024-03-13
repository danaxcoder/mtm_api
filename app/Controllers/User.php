<?php

namespace App\Controllers;

class User extends BaseController
{
    public function index(): string
    {
        return view('welcome_message');
    }
    
    public function test() {
    }
    
    public function login() {
        $username = $this->request->getVar('username');
        $password = $this->request->getVar('password');
        $users = $this->db->query("SELECT * FROM `user` WHERE `username`='".$username."' AND `password`='".$password."'")->getResultArray();
        if (sizeof($users) > 0) {
            return json_encode(array(
                'response_code' => 0,
                'data' => array(
                    'user' => $users[0]
                )
            ));
        } else {
            return json_encode(array(
                'response_code' => -1
            ));
        }
    }
    
    public function import_customers() {
        $customers = json_decode($this->request->getVar('customers'), true);
        //return json_encode($customers[618]);
        //$customers = json_decode('[{"name":"Halimatus Sa\'diyah","address":"UBD, Timur Sumur Bor","pkg":"RAKYAT 2Mb","price":"100000","phone":"085856863477","latitude":-8.2783859999999994,"longitude":113.559972,"expiry":"2024-01-10"}]', true);
        for ($i=0; $i<sizeof($customers); $i++) {
            $customer = $customers[$i];
            //echo "INSERT INTO customer (name, address, package, price, phone, latitude, longitude, expiry) VALUES ('Halimatus Sadiyah','".$customer['address']."', '".$customer['pkg']."', ".$customer['price'].", '".$customer['phone']."', ".$customer['latitude'].", ".$customer['longitude'].", '".$customer['expiry']."')<br/>";
            if ($customer==null || $customer['name']==null || $customer['address']==null ||$customer['pkg']==null || $customer['price']==null || $customer['phone']==null || $customer['latitude']==null || $customer['longitude']==null || $customer['expiry']==null) {
                continue;
            }
            $this->db->query("INSERT INTO customer (name, address, package, price, phone, latitude, longitude, expiry) VALUES ('".$this->fmt($customer['name'])."', '".$this->fmt($customer['address'])."', '".$this->fmt($customer['pkg'])."', ".$this->fmt($customer['price']).", '".$this->fmt($customer['phone'])."', ".$this->fmt($customer['latitude']).", ".$this->fmt($customer['longitude']).", '".$this->fmt($customer['expiry'])."')");
        }
    }
    
    private function fmt($value) {
        return str_replace("'", "\'", $value);
    }
    
    private function chknll($object) {
        if ($object==null) {
            return true;
        }
        return false;
    }
    
    public function get_customer_data() {
        $userID = intval($this->request->getVar('user_id'));
        $year = date("Y");
        $month = date("m");
        $day = date("d");
        $fullDate = $year."-".str_pad($month, 2, "0", STR_PAD_LEFT)."-".str_pad($day, 2, "0", STR_PAD_LEFT);
        $date = $year."-".$month."-".$day;
        $allCustomers = $this->db->query("SELECT * FROM `customer` WHERE `user_id`=".$userID)->getResultArray();
        $customers = $this->db->query("SELECT * FROM `customer` WHERE `user_id`=".$userID)->getResultArray();
        $customersBalance = doubleval(array_shift(array_values($this->db->query("SELECT (SUM(`price`)+SUM(`additional_fee_1`)+SUM(`additional_fee_2`)) FROM `payment` WHERE `user_id`=".$userID." AND MONTH(`date`)=".$month." AND YEAR(`date`)=".$year)->getRowArray())));
        $newCustomers = $this->db->query("SELECT * FROM `customer` WHERE `user_id`=".$userID." AND DAY(`created_at`)=".$day." AND MONTH(`created_at`)=".$month." AND YEAR(`created_at`)=".$year)->getResultArray();
        $newCustomersBalance = 0;
        for ($i=0; $i<sizeof($newCustomers); $i++) {
            $customer = $newCustomers[$i];
            $balance = doubleval(array_shift(array_values($this->db->query("SELECT (SUM(`price`)+SUM(`additional_fee_1`)+SUM(`additional_fee_2`)) FROM `payment` WHERE `customer_id`=".$customer['id']." AND MONTH(`date`)=".$month." AND YEAR(`date`)=".$year)->getRowArray())));
            $newCustomersBalance += $balance;
        }
        $unpayingCustomers = [];
        $unpaidAmount = 0;
        for ($i=0; $i<sizeof($customers); $i++) {
            $customer = $customers[$i];
            $payment = $this->db->query("SELECT * FROM `payment` WHERE `customer_id`=".$customer['id']." AND MONTH(`date`)=".$month." AND YEAR(`date`)=".$year." AND `status`='completed'")->getRowArray();
            if ($payment==null || $payment=="null") {
                array_push($unpayingCustomers, $customer);
                $package = $this->db->query("SELECT * FROM `package` WHERE `id`=".$customer['package_id'])->getRowArray();
                if ($package != NULL) {
                    $price = doubleval($package['price']);
                    $ppn = doubleval($package['ppn']);
                    $unpaidAmount += ($price+($ppn*$price/100.0));
                }
            }
        }
        $payingCustomers = $this->db->query("SELECT * FROM `customer` WHERE `id` IN (SELECT `customer_id` FROM `payment` WHERE `user_id`=".$userID." AND MONTH(`date`)=".$month." AND YEAR(`date`)=".$year." AND `status`='completed')")->getResultArray();
        $paidAmount = doubleval(array_shift(array_values($this->db->query("SELECT (SUM(`price`)+SUM(`additional_fee_1`)+SUM(`additional_fee_2`)) FROM `payment` WHERE `user_id`=".$userID." AND MONTH(`date`)=".$month." AND YEAR(`date`)=".$year)->getRowArray())));
        $cashTransactions = $this->db->query("SELECT * FROM `payment` WHERE `user_id`=".$userID." AND MONTH(`date`)=".$month." AND YEAR(`date`)=".$year." AND `method`='cash'")->getResultArray();
        $cashTransactionsAmount = doubleval(array_shift(array_values($this->db->query("SELECT (SUM(`price`)+SUM(`additional_fee_1`)+SUM(`additional_fee_2`)) FROM `payment` WHERE `user_id`=".$userID." AND MONTH(`date`)=".$month." AND YEAR(`date`)=".$year." AND `method`='cash'")->getRowArray())));
        $onlineTransactions = $this->db->query("SELECT * FROM `payment` WHERE `user_id`=".$userID." AND MONTH(`date`)=".$month." AND YEAR(`date`)=".$year." AND `method`!='cash'")->getResultArray();
        $onlineTransactionsAmount = doubleval(array_shift(array_values($this->db->query("SELECT (SUM(`price`)+SUM(`additional_fee_1`)+SUM(`additional_fee_2`)) FROM `payment` WHERE `user_id`=".$userID." AND MONTH(`date`)=".$month." AND YEAR(`date`)=".$year." AND `method`!='cash'")->getRowArray())));
        $disabledCustomers = sizeof($this->db->query("SELECT * FROM `customer` WHERE `expiry`<='".$fullDate."' AND `status`!='blocked'")->getResultArray());
        $stoppedCustomers = sizeof($this->db->query("SELECT * FROM `customer` WHERE `status`='blocked'")->getResultArray());
        return json_encode(array(
            'all_customers' => sizeof($allCustomers),
            'total' => sizeof($customers),
            'total_amount' => $customersBalance,
            'new' => sizeof($newCustomers),
            'new_amount' => $newCustomersBalance,
            'unpaying' => sizeof($unpayingCustomers),
            'unpaid_amount' => $unpaidAmount,
            'paying' => sizeof($payingCustomers),
            'paid_amount' => $paidAmount,
            'cash_transactions' => sizeof($cashTransactions),
            'cash_transactions_amount' => $cashTransactionsAmount,
            'online_transactions' => sizeof($onlineTransactions),
            'online_transactions_amount' => $onlineTransactionsAmount,
            'disabled_customers' => $disabledCustomers,
            'stopped_customers' => $stoppedCustomers
        ));
    }
    
    public function get_unpaying_customers() {
        $year = date("Y");
        $month = date("m");
        $day = date("d");
        $date = $year."-".$month."-".$day;
        $unpayingCustomers = $this->db->query("SELECT * FROM `overdue` WHERE `paid`=0 AND MONTH(`updated_at`)=".$month." AND YEAR(`updated_at`)=".$year)->getResultArray();
        return json_encode(array(
            'total' => sizeof($unpayingCustomers)
        ));
    }
    
    public function get_packages() {
        $packages = $this->db->query("SELECT * FROM `packages` ORDER BY `price`")->getResultArray();
        return json_encode($packages);
    }
    
    public function get_areas() {
        $areas = $this->db->query("SELECT * FROM `area`")->getResultArray();
        for ($i=0; $i<sizeof($areas); $i++) {
            $area = $areas[$i];
            $customers = $this->db->query("SELECT * FROM `customer` WHERE `area_id`=".$area['id'])->getResultArray();
            $areas[$i]['total_customers'] = sizeof($customers);
        }
        return json_encode($areas);
    }
    
    public function get_mikrotiks() {
        $mikrotik = $this->db->query("SELECT * FROM `mikrotik` ORDER BY `name`")->getResultArray();
        return json_encode($mikrotik);
    }
    
    public function add_customer() {
        $name = $this->request->getVar('name');
        $address = $this->request->getVar('address');
        $areaID = intval($this->request->getVar('area_id'));
        $packageID = intval($this->request->getVar('package_id'));
        $package = $this->request->getVar('package');
        $price = doubleval($this->request->getVar('price'));
        $phone = $this->request->getVar('phone');
        $latitude = doubleval($this->request->getVar('latitude'));
        $longitude = doubleval($this->request->getVar('longitude'));
        $regDate = $this->request->getVar('created_at');
        $billDate = $this->request->getVar('bill_date');
        $disableDate = $this->request->getVar('disable_date');
        $additionalPricing1Name = $this->request->getVar('additional_pricing_1_name');
        $additionalPricing1Price = doubleval($this->request->getVar('additional_pricing_1_price'));
        $additionalPricing2Name = $this->request->getVar('additional_pricing_2_name');
        $additionalPricing2Price = doubleval($this->request->getVar('additional_pricing_2_price'));
        $discount = doubleval($this->request->getVar('discount'));
        $mikrotikID = intval($this->request->getVar('mikrotik_id'));
        $customerSystemTypes = json_decode($this->request->getVar('customer_system_types'), true);
        $staticIP = $this->request->getVar('static_ip');
        $modemInfo = $this->request->getVar('modem_info');
        $customerODP = $this->request->getVar('customer_odp');
        $currentSubscriptionID = intval($this->request->getVar('current_subscription_id'));
        $expiry = $this->request->getVar('expiry');
        $date = date('Y-m-d');
        
        /*$name = "User One";
        $address = "Masjid Baitul Maqdis, Jalan Sultan Muhammad Salahudin, Tanjung Kel., Rasanae Barat, Bima 84118 Indonesia";
        $areaID=-1;
        $packageID=-1;
        $package="";
        $price=0;
        $phone="081123456789";
        $latitude=0;
        $longitude=0;
        $regDate="2024-01-02";
        $billDate="2024-01-02";
        $disableDate="2024-01-02";
        $additionalPricing1Name="";
        $additionalPricing1Price=0;
        $additionalPricing2Name="";
        $additionalPricing2Price=0;
        $discount=0;
        $mikrotikID=-1;
        $customerSystemTypes="[]";
        $staticIP="";
        $modemInfo="";
        $customerODP="";
        $currentSubscriptionID=0;
        $expiry="2024-03-14";
        $date = date('Y-m-d');*/
        
        $this->db->query("INSERT INTO `customer` (name, address, area_id, package_id, package, price, phone, latitude, longitude, created_at, bill_date, disable_date, additional_pricing_1_name, additional_pricing_1_price, additional_pricing_2_name, additional_pricing_2_price, discount, mikrotik_id, customer_system_types, static_ip, modem_info, customer_odp, current_subscription_id, expiry, created_at, updated_at) VALUES (\"".$name."\", \"".$address."\", ".$areaID.", ".$packageID.", \"".$package."\", ".$price.", \"".$phone."\", ".$latitude.", ".$longitude.", \"".$regDate."\", \"".$billDate."\", \"".$disableDate."\", \"".$additionalPricing1Name."\", ".$additionalPricing1Price.", \"".$additionalPricing2Name."\", ".$additionalPricing2Price.", ".$discount.", ".$mikrotikID.", \"".json_encode($customerSystemTypes)."\", \"".$staticIP."\", \"".$modemInfo."\", \"".$customerODP."\", 0, \"".$expiry."\", \"".$date."\", \"".$date."\")");
    }
    
    public function get_new_customers() {
        $userID = intval($this->request->getVar('user_id'));
        $year = date("Y");
        $month = date("m");
        $day = date("d");
        $customers = $this->db->query("SELECT * FROM `customer` WHERE `user_id`=".$userID." AND DAY(`created_at`)=".$day." AND MONTH(`created_at`)=".$month." AND YEAR(`created_at`)=".$year)->getResultArray();
        return json_encode($customers);
    }
    
    public function get_all_customers() {
        $userID = intval($this->request->getVar('user_id'));
        $customers = $this->db->query("SELECT * FROM `customer` WHERE `user_id`=".$userID." ORDER BY `name`")->getResultArray();
        return json_encode($customers);
    }
    
    public function get_billed_customers() {
        $userID = intval($this->request->getVar('user_id'));
        $year = date("Y");
        $month = date("m");
        $day = date("d");
        $customers = $this->db->query("SELECT * FROM `customer` WHERE `user_id`=".$userID." AND `expiry`<='".$year."-".str_pad("".$month, 2, '0')."-".str_pad("".$day, 2, '0')."' AND `status`!='blocked' ORDER BY `name`")->getResultArray();
        $unpayingCustomers = [];
        for ($i=0; $i<sizeof($customers); $i++) {
            $customer = $customers[$i];
            $payment = $this->db->query("SELECT * FROM `payment` WHERE `customer_id`=".$customer['id']." AND MONTH(`date`)=".$month." AND YEAR(`date`)=".$year." AND `status`='completed'")->getRowArray();
            if ($payment==NULL || $payment=="null") {
                array_push($unpayingCustomers, $customer);
            }
        }
        return json_encode($unpayingCustomers);
    }
    
    public function get_paying_customers() {
        $userID = intval($this->request->getVar('user_id'));
        $year = date("Y");
        $month = date("m");
        $day = date("d");
        $payments = $this->db->query("SELECT * FROM `payment` WHERE `user_id`=".$userID." MONTH(`date`)=".$month." AND YEAR(`date`)=".$year." AND `status`='completed'")->getResultArray();
        $payingCustomers = [];
        for ($i=0; $i<sizeof($payments); $i++) {
            $payment = $payments[$i];
            $customer = $this->db->query("SELECT * FROM `customer` WHERE `id`=".$payment['customer_id'])->getRowArray();
            array_push($payingCustomers, $customer);
        }
        return json_encode($payingCustomers);
    }
    
    public function get_customer_payments() {
        $customerID = intval($this->request->getVar('customer_id'));
        $customers = $this->db->query("SELECT * FROM `payment` WHERE `customer_id`=".$customerID." ORDER BY `created_at` DESC")->getResultArray();
        return json_encode($customers);
    }
    
    public function get_payment_statuses() {
        $customerID = intval($this->request->getVar('customer_id'));
        $year = intval($this->request->getVar('year'));
        $statuses = array_fill(0, 12, 'unpaid');
        for ($i=0; $i<12; $i++) {
            $statuses[$i] = $this->get_payment_status($customerID, $i+1, $year);
        }
        return json_encode($statuses);
    }
    
    private function get_payment_status($customerID, $month, $year) {
        $payments = $this->db->query("SELECT * FROM `payment` WHERE `customer_id`=".$customerID." AND MONTH(`date`)=".str_pad(''.$month, 2, "0", STR_PAD_LEFT)." AND YEAR(`date`)=".$year)->getResultArray();
        if (sizeof($payments) > 0) {
            $payment = $payments[0];
            return $payment['status'];
        }
        return 'unpaid';
    }
    
    public function get_payment_methods() {
        $paymentMethods = $this->db->query("SELECT * FROM `payment_method`")->getResultArray();
        return json_encode($paymentMethods);
    }
    
    public function get_customer() {
        $id = intval($this->request->getVar('id'));
        $customer = $this->db->query("SELECT * FROM `customer` WHERE `id`=".$id)->getRowArray();
        $mikrotikID = intval($customer['mikrotik_id']);
        if ($mikrotikID > 0) {
            $customer['mikrotik'] = $this->db->query("SELECT * FROM `mikrotik` WHERE `id`=".$mikrotikID)->getRowArray();
        }
        $packageID = intval($customer['package_id']);
        if ($packageID > 0) {
            $customer['package'] = $this->db->query("SELECT * FROM `package` WHERE `id`=".$packageID)->getRowArray();
        }
        $customer['wifi_data'] = $this->db->query("SELECT * FROM `wifi_data` WHERE `customer_id`=".$id)->getRowArray();
        $year = date("Y");
        $month = date("m");
        $day = date("d");
        $paymentStatus = 'unpaid';
        $payment = $this->db->query("SELECT * FROM `payment` WHERE `customer_id`=".$id." AND MONTH(`date`)=".$month." AND YEAR(`date`)=".$year)->getRowArray();
        if ($payment != NULL) {
            $paymentStatus = $payment['status'];
        }
        $customer['last_month_payment_status'] = $paymentStatus;
        return json_encode($customer);
    }
    
    public function get_network() {
        $customerID = intval($this->request->getVar('customer_id'));
        $network = $this->db->query("SELECT * FROM `network` WHERE `customer_id`=".$customerID)->getRowArray();
        $source = intval($network['source']);
        $network['source'] = $this->db->query("SELECT * FROM `customer` WHERE `id`=".$source)->getRowArray();
        $branches = [];
        $branchIDs = json_decode($network['branch'], true);
        for ($i=0; $i<sizeof($branchIDs); $i++) {
            $branchID = $branchIDs[$i];
            array_push($branches, $this->db->query("SELECT * FROM `customer` WHERE `id`=".$branchID)->getRowArray());
        }
        $network['branches'] = $branches;
        return json_encode($network);
    }
    
    public function get_customers() {
        $customers = $this->db->query("SELECT * FROM `customer` ORDER BY `name`")->getResultArray();
        return json_encode($customers);
    }
    
    public function get_customers_by_location() {
        $latitude = doubleval($this->request->getVar('latitude'));
        $longitude = doubleval($this->request->getVar('longitude'));
        $customers = $this->db->query("SELECT *, SQRT(POW(69.1 * (latitude - ".$latitude."), 2) + POW(69.1 * (".$longitude." - longitude) * COS(latitude / 57.3), 2)) AS distance FROM `customer` HAVING distance < 10 ORDER BY distance")->getResultArray();
        return json_encode($customers);
    }
    
    public function update_network() {
        $customerID = intval($this->request->getVar('customer_id'));
        $source = intval($this->request->getVar('source'));
        $branches = json_decode($this->request->getVar('branch'), true);
        $this->db->query("UPDATE `network` SET `source`=".$source.", `branch`='".json_encode($branches)."' WHERE `customer_id`=".$customerID);
    }
    
    public function add_branch() {
        $customerID = intval($this->request->getVar('customer_id'));
        $branch = intval($this->request->getVar('branch'));
        $network = $this->db->query("SELECT * FROM `network` WHERE `customer_id`=".$customerID)->getRowArray();
        $branchIDs = json_decode($network['branch'], true);
        if (!in_array($branch, $branchIDs)) {
            array_push($branchIDs, $branch);
        }
        $this->db->query("UPDATE `network` SET `branch`='".json_encode($branchIDs)."' WHERE `customer_id`=".$customerID);
    }
    
    public function get_payment() {
        $id = intval($this->request->getVar('id'));
        $payment = $this->db->query("SELECT * FROM `payment` WHERE `id`=".$id)->getRowArray();
        $payment['package'] = $this->db->query("SELECT * FROM `package` WHERE `id`=".$payment['package_id'])->getRowArray();
        $payment['customer'] = $this->db->query("SELECT * FROM `customer` WHERE `id`=".$payment['customer_id'])->getRowArray();
        return json_encode($payment);
    }
    
    public function get_payment_by_month() {
        $customerID = intval($this->request->getVar('customer_id'));
        $month = intval($this->request->getVar('month'));
        $customer = $this->db->query("SELECT * FROM `customer` WHERE `id`=".$customerID)->getRowArray();
        $paymentInfo = $this->db->query("SELECT * FROM `payment` WHERE `customer_id`=".$customerID." AND MONTH(`date`)=".$month)->getRowArray();
        $payment = array();
        if ($paymentInfo != NULL) {
            $payment['payment'] = $paymentInfo;
        }
        $payment['package'] = $this->db->query("SELECT * FROM `package` WHERE `id`=".$customer['package_id'])->getRowArray();
        $payment['customer'] = $customer;
        return json_encode($payment);
    }
    
    public function update_payment_details() {
        $id = intval($this->request->getVar('id'));
        $type = $this->request->getVar('type');
        $name = $this->request->getVar('name');
        if ($type == "text") {
            $value = $this->request->getVar('value');
            $this->db->query("UPDATE `payment` SET `".$name."`='".$value."' WHERE `id`=".$id);
        } else if ($type == "double") {
            $value = doubleval($this->request->getVar('value'));
            $this->db->query("UPDATE `payment` SET `".$name."`=".$value." WHERE `id`=".$id);
        }
    }
    
    public function get_settings() {
        $settings = $this->db->query("SELECT * FROM `setting`")->getRowArray();
        return json_encode($settings);
    }
    
    public function update_photo() {
        $photo = $this->request->getFile('file');
        $customerID = intval($this->request->getVar('customer_id'));
        $fileName = generateUUID();
        $photo->move('userdata', $fileName);
        $this->db->query("UPDATE `customer` SET `photo`='".$fileName."' WHERE `id`=".$customerID);
        return $fileName;
    }
    
    public function get_cash_payments() {
        $userID = intval($this->request->getVar('user_id'));
        $payments = $this->db->query("SELECT * FROM `payment` WHERE `user_id`=".$userID." AND `method`='cash' ORDER BY `date` DESC")->getResultArray();
        for ($i=0; $i<sizeof($payments); $i++) {
            $payment = $payments[$i];
            $customer = $this->db->query("SELECT * FROM `customer` WHERE `id`=".$payment['customer_id'])->getRowArray();
            $payments[$i]['customer'] = $customer;
        }
        return json_encode($payments);
    }
    
    public function get_online_payments() {
        $userID = intval($this->request->getVar('user_id'));
        $payments = $this->db->query("SELECT * FROM `payment` WHERE `user_id`=".$userID." AND `method`!='cash' ORDER BY `date` DESC")->getResultArray();
        for ($i=0; $i<sizeof($payments); $i++) {
            $payment = $payments[$i];
            $customer = $this->db->query("SELECT * FROM `customer` WHERE `id`=".$payment['customer_id'])->getRowArray();
            $payments[$i]['customer'] = $customer;
        }
        return json_encode($payments);
    }
    
    public function update_expiry_date() {
        $customerID = intval($this->request->getVar('customer_id'));
        $expiry = $this->request->getVar('expiry');
        $this->db->query("UPDATE `customer` SET `expiry`='".$expiry."' WHERE `id`=".$customerID);
    }
    
    public function block_customer() {
        $customerID = intval($this->request->getVar('id'));
        $this->db->query("UPDATE `customer` SET `status`='blocked' WHERE `id`=".$customerID);
    }
	
	public function get_technicians() {
		$technicians = $this->db->query("SELECT * FROM `technician` ORDER BY `name`")->getResultArray();
		return json_encode($technicians);
	}
	
	public function get_tasks() {
		$userID = intval($this->request->getVar('user_id'));
		$status = $this->request->getVar('status');
		$tasks = $this->db->query("SELECT * FROM `task` WHERE `user_id`=".$userID." AND `status`='".$status."' ORDER BY `date` DESC")->getResultArray();
		for ($i=0; $i<sizeof($tasks); $i++) {
			$task = $tasks[$i];
			$customerID = intval($task['customer_id']);
			$customer = $this->db->query("SELECT * FROM `customer` WHERE `id`=".$customerID)->getRowArray();
			$technicians = array();
			$technicianIDs = json_decode($task['technicians'], true);
			for ($j=0; $j<sizeof($technicianIDs); $j++) {
				$technicianID = $technicianIDs[$j];
				$technician = $this->db->query("SELECT * FROM `technician` WHERE `id`=".$technicianID)->getRowArray();
				array_push($technicians, $technician);
			}
			$tasks[$i]['customer'] = $customer;
			$tasks[$i]['technicians'] = $technicians;
		}
		return json_encode($tasks);
	}
	
	public function get_task_details() {
		$id = intval($this->request->getVar('id'));
		$task = $this->db->query("SELECT * FROM `task` WHERE `id`=".$id)->getRowArray();
		$customerID = intval($task['customer_id']);
		$customer = $this->db->query("SELECT * FROM `customer` WHERE `id`=".$customerID)->getRowArray();
		$technicians = array();
		$technicianIDs = json_decode($task['technicians'], true);
		for ($j=0; $j<sizeof($technicianIDs); $j++) {
			$technicianID = $technicianIDs[$j];
			$technician = $this->db->query("SELECT * FROM `technician` WHERE `id`=".$technicianID)->getRowArray();
			array_push($technicians, $technician);
		}
		$task['customer'] = $customer;
		$task['technicians'] = $technicians;
		return json_encode($task);
	}
	
	public function update_task_status() {
		$id = intval($this->request->getVar('id'));
		$status = $this->request->getVar('status');
		$this->db->query("UPDATE `task` SET `status`='".$status."' WHERE `id`=".$id);
	}
	
	public function get_payments() {
		$userID = intval($this->request->getVar('user_id'));
		$date = $this->request->getVar('date');
		$payments = $this->db->query("SELECT * FROM `payment` WHERE `user_id`=".$userID." AND `date`='".$date."'")->getResultArray();
		for ($i=0; $i<sizeof($payments); $i++) {
			$payment = $payments[$i];
			$userID = intval($payment['user_id']);
			$user = $this->db->query("SELECT * FROM `user` WHERE `id`=".$userID)->getRowArray();
			$customerID = intval($payment['customer_id']);
			$customer = $this->db->query("SELECT * FROM `customer` WHERE `id`=".$customerID)->getRowArray();
			$packageID = intval($payment['package_id']);
			$package = $this->db->query("SELECT * FROM `package` WHERE `id`=".$packageID)->getRowArray();
			$areaID = intval($customer['area_id']);
			$area = $this->db->query("SELECT * FROM `area` WHERE `id`=".$areaID)->getRowArray();
			$payments[$i]['user'] = $user;
			$payments[$i]['package'] = $package;
			$payments[$i]['customer'] = $customer;
			$payments[$i]['customer']['area'] = $area;
		}
		return json_encode($payments);
	}
	
	public function get_user() {
		$id = intval($this->request->getVar('id'));
		$user = $this->db->query("SELECT * FROM `user` WHERE `id`=".$id)->getRowArray();
		return json_encode($user);
	}
	
	public function update_user_password() {
		$id = intval($this->request->getVar('id'));
		$password = $this->request->getVar('password');
		$this->db->query("UPDATE `user` SET `password`='".$password."' WHERE `id`=".$id);
	}
}

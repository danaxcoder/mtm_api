<?php

use CodeIgniter\Router\RouteCollection;

/**
 * @var RouteCollection $routes
 */
$routes->get('/', 'Home::index');
$routes->get('/test', 'User::test');
$routes->post('/login', 'User::login');
$routes->post('/add_customer', 'User::add_customer');
$routes->post('/import_customers', 'User::import_customers');
$routes->post('/get_customer_data', 'User::get_customer_data');
$routes->get('/get_unpaying_customers', 'User::get_unpaying_customers');
$routes->get('/test/customer', 'Test::customer');
$routes->get('/get_packages', 'User::get_packages');
$routes->get('/populate_area_ids', 'Setup::populate_area_ids');
$routes->get('/get_areas', 'User::get_areas');
$routes->get('/get_mikrotiks', 'User::get_mikrotiks');
$routes->post('/add_customer', 'User::add_customer');
$routes->post('/get_new_customers', 'User::get_new_customers');
$routes->post('/get_customer_payments', 'User::get_customer_payments');
$routes->post('/get_payment_statuses', 'User::get_payment_statuses');
$routes->get('/get_payment_methods', 'User::get_payment_methods');
$routes->post('/get_customer', 'User::get_customer');
$routes->post('/get_network', 'User::get_network');
$routes->get('/get_customers', 'User::get_customers');
$routes->post('/update_network', 'User::update_network');
$routes->post('/add_branch', 'User::add_branch');
$routes->post('/get_payment', 'User::get_payment');
$routes->post('/get_payment_by_month', 'User::get_payment_by_month');
$routes->post('/update_payment_details', 'User::update_payment_details');
$routes->get('/get_settings', 'User::get_settings');
$routes->post('/update_photo', 'User::update_photo');
$routes->post('/get_all_customers', 'User::get_all_customers');
$routes->post('/get_billed_customers', 'User::get_billed_customers');
$routes->post('/get_paying_customers', 'User::get_paying_customers');
$routes->post('/get_cash_payments', 'User::get_cash_payments');
$routes->post('/get_online_payments', 'User::get_online_payments');
$routes->post('/get_customers_by_location', 'User::get_customers_by_location');
$routes->post('/update_expiry_date', 'User::update_expiry_date');
$routes->post('/block_customer', 'User::block_customer');
$routes->get('/get_technicians', 'User::get_technicians');
$routes->post('/get_tasks', 'User::get_tasks');
$routes->post('/get_task_details', 'User::get_task_details');
$routes->post('/update_task_status', 'User::update_task_status');
$routes->post('/get_payments', 'User::get_payments');
$routes->post('/get_user', 'User::get_user');
$routes->post('/update_user_password', 'User::update_user_password');
$routes->get('/get_odps', 'User::get_odps');
$routes->get('/get_odcs', 'User::get_odcs');
$routes->post('/get_odp', 'User::get_odp');
$routes->post('/get_odc', 'User::get_odc');
$routes->post('/update_odc', 'User::update_odc');
$routes->post('/update_odp', 'User::update_odp');
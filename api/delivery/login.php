<?php

/** 
 * login.php
 * error code from 1000
 **/

error_reporting(E_ALL);
ini_set('display_errors', '1');

include_once('common.php');
include_once('database.php');
// include_once('write_log.php');

require '../vendor/autoload.php';

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Headers: Content-Type');

$time_zone = new DateTimeZone('Australia/Melbourne');
date_default_timezone_set('Australia/Melbourne');

use \Firebase\JWT\JWT;

class Login {

    private $common;
    private $db;
    // private $log;

    public function __construct() {
        $this->common = new Common();
        $this->db = new Database();
        // $this->log = new Log();

        $this->handleRequest();
    }

    private function handleRequest() {
        $data = json_decode(file_get_contents('php://input'), true);

        if ($data) {
            $action = isset($data['action']) ? $data['action'] : '';
            switch ($action) {
                case 'login':
                    $this->handleLogin($data);
                    break;

                case 'signup':
                    $this->handleSignup($data);
                    break;

                case 'forget':
                    $this->handleForget($data);
                    break;
            }
        }
    }

    private function handleLogin($data) {
        if ($data['email'] == '' || $data['password'] == '') {
            echo json_encode(array('status' => 2000));
            exit;
        }

        $sql = "SELECT *
            FROM suppliers
            WHERE email = :email
            LIMIT 1";
        $supplier = $this->db->selectFetchOne(false, false, $sql, 
            array('email' => $data['email']));

        if ($supplier) {

            if ($this->common->verifyPassword($data['password'], $supplier['password'])) {
                $secretKey = 'P1yxtvnJ9a';

                $user = [
                    'id' => $supplier['id'],
                    'expired' => date('Y-m-d H:i', strtotime('+1 day'))
                ];

                $token = JWT::encode($user, $secretKey, 'HS256');

                echo json_encode(array('status' => 200, 'token' => $token));
                $isSuccess = 1;
                $sid = $supplier['id'];
            } else {
                echo json_encode(array('status' => 1000));
                $isSuccess = 0;
                $sid = 0;
            }
        } else {
            echo json_encode(array('status' => 1000));
            $isSuccess = 0;
            $sid = 0;
        }

        // $this->log->writeSupplierLog('login', $data['action'], null, $isSuccess, $sid);
        exit;
    }

    private function handleSignup($data) {
        if ($data['company'] == '' || $data['contact'] == '' || $data['email'] == '') {
            echo json_encode(array('status' => 1001));
            exit;
        }
        
        $password = $this->common->generateRandomPassword();
        $password_encrpt = $this->common->hashPassword($password);

        $params = array(
            'name' => $data['company'],
            'contact' => $data['contact'],
            'email' => $data['email'],
            'phone' => null,
            'tenant' => 'AU',
            'password' => $password_encrpt,
            'password_temp' => $password,
            'isActive' => 1
        );

        $this->db->insert('suppliers', $params);

        if ($this->db->lastInsertId) {
            // send mail to user with login details
            // $subject = 'Scheduling Delivery Bookings Credentials';

            // $message = '** DO NOT REPLY **'.PHP_EOL.PHP_EOL;
            // $message .= 'Dear ' . $data['contact'] . PHP_EOL;
            // $message .= 'Thank you for registering at our website.' . PHP_EOL;
            // $message .= 'Username: ' . $data['email'] . PHP_EOL;
            // $message .= 'Password: ' . $password . PHP_EOL;
            // $message.= 'Thank you!'.PHP_EOL.'Warehouse Team'.PHP_EOL.'Toys R Us'.PHP_EOL.'www.toyrus.com.au';

            // $headers = "From: no-reply@toyrus.com.au";

            // mail($data['email'], $subject, $message, $headers);

            echo json_encode(array('status' => 200));
            $isSuccess = 1;
        } else {
            echo json_encode(array('status' => 1001));
            $isSuccess = 0;
        }
        // $this->log->writeSupplierLog('signup', $data['action'], $data['email'], $isSuccess);
        exit;
    }

    private function handleForget($data) {
        if ($data['email'] == '') {
            echo json_encode(array('status' => 1002));
            exit;
        }
        
        $password = $this->common->generateRandomPassword();
        $password_encrpt = $this->common->hashPassword($password);

        // reset password
        $this->db->setTable('suppliers');
        $params = array(
            'password' => $password_encrpt,
            'password_temp' => $password
        );
        $updateParams = $params;
        $updateParams['email'] = $data['email'];
        $this->db->update($params, 'email = :email', false, $updateParams);

        if ($this->db->rowsAffected > 0) {
            // send mail to user with login details
            // $subject = 'Scheduling Delivery Bookings Credentials';

            // $message = '** DO NOT REPLY **'.PHP_EOL.PHP_EOL;
            // $message .= 'Dear ' . $data['contact'] . PHP_EOL;
            // $message .= 'Thank you for registering at our website.' . PHP_EOL;
            // $message .= 'Username: ' . $data['email'] . PHP_EOL;
            // $message .= 'Password: ' . $password . PHP_EOL;
            // $message.= 'Thank you!'.PHP_EOL.'Warehouse Team'.PHP_EOL.'Toys R Us'.PHP_EOL.'www.toyrus.com.au';

            // $headers = "From: no-reply@toyrus.com.au";

            // mail($data['email'], $subject, $message, $headers);

            echo json_encode(array('status' => 200));
            $isSuccess = 1;
        } else {
            echo json_encode(array('status' => 1002));
            $isSuccess = 0;
        }

        // $this->log->writeSupplierLog('forgot', $data['action'], $data['email'], $isSuccess);

        exit;
    }

}

$login = new Login();

?>
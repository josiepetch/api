<?php

class Common {

    public function generateRandomPassword($length = 12) {
        $chars = "abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ";
        $number = "0123456789";
        $symbol = "!@#$%^&*()-_+=";

        $charsSetSize = strlen($chars);
        $numberSetSize = strlen($number);
        $symbolSetSize = strlen($symbol);

        $password = '';
        $length_char = (int)ceil($length / 2);
        $length_number = (int)floor($length / 4);
        $length_symbol = $length - $length_char - $length_number;

        // Generate characters
        for ($i = 0; $i < $length_char; $i++) {
            $randomIndex = mt_rand(0, $charsSetSize - 1);
            $password .= $chars[$randomIndex];
        }

        // Generate numbers
        for ($i = 0; $i < $length_number; $i++) {
            $randomIndex = mt_rand(0, $numberSetSize - 1);
            $password .= $number[$randomIndex];
        }

        // Generate symbols
        for ($i = 0; $i < $length_symbol; $i++) {
            $randomIndex = mt_rand(0, $symbolSetSize - 1);
            $password .= $symbol[$randomIndex];
        }

        $password = str_shuffle($password);

        return $password;
    }

    public function hashPassword($password) {    
        $hashedPassword = password_hash($password, PASSWORD_DEFAULT);
    
        return $hashedPassword;
    }

    public function verifyPassword($inputPassword, $hashedPassword) {
        return password_verify($inputPassword, $hashedPassword);
    }

    public static function updateJsonHoliday($db) {
        // update json file
        $file_path = '../public/holiday.json';
        $file_event = fopen($file_path, 'w');
        if ($file_event === false) {
            return 0;
        } else {
            $data_to_write = array();
            $sql = "SELECT date, title FROM holiday WHERE tenant = :tenant ORDER BY date ASC";
            $db->select(false, false, $sql, array('tenant' => 'AU'));
            while ($row = $db->getRow(true)) {
                array_push($data_to_write, array('title' => $row['title'], 'start' => $row['date']));
            }
            fwrite($file_event, json_encode($data_to_write, true));
            fclose($file_event);
            return 1;
        }
    }

}

?>
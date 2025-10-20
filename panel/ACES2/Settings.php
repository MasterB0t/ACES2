<?php

namespace ACES2;

class Settings {

    public static function get(string $setting_name) {

        $db= new DB;
        $name = $db->escString(strtolower($setting_name));
        $r = $db->query("SELECT value,type FROM settings WHERE name='$name'");
        if($row=$r->fetch_assoc()) {

            return match ($row['type']) {
                'int' => (int)$row['value'],
                'bool' => (bool)$row['value'],
                'float' => (float)$row['value'],
                'json' => json_decode($row['value'], true),
                'serialize' => unserialize($row['value'], true),
                default => (string)$row['value'],
            };

        } else {
            //GETTING DEFAULT VALUES.
            foreach (glob(DOC_ROOT."includes/settings/*.php") as $filename) {
                include $filename;
            }
            if(isset($__SETTINGS[$setting_name]))
                return $__SETTINGS[$setting_name];
        }

        return null;

    }

    public static function set(string $name, string $value, string $type = 'string') {

        $db= new DB;
        $name = $db->escString(strtolower($name));
        $value = $db->escString($value);

        if(!in_array($type, ['string', 'int', 'integer','float','bool', 'serialize', 'json']))
            $type = 'string';

        $r = $db->query("SELECT id FROM settings WHERE name='$name'");
        if($r->num_rows > 0) {
            $db->query("UPDATE settings SET value='$value', type='$type' WHERE name='$name'");
        } else
            $db->query("INSERT INTO settings (name, value, type) 
                VALUES ('$name', '$value', '$type')");

    }

}
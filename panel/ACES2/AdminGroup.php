<?php

namespace ACES2;

class AdminGroup {

    public $id = 0;
    public $name = '';
    public $permissions = array();

    public function __construct(int $id = null) {

        if(!is_null($id)) {

            $db = new \ACES2\DB();
            $r=$db->query("SELECT * FROM `admin_groups` WHERE `id`='$id'");
            if(!$row=$r->fetch_assoc())
                throw new \Exception("Unable to get AdminGroup #$id from database.");

            $this->id = $id;
            $this->name = $row['name'];
            $this->permissions = json_decode($row['permissions'], 1);
        }

    }

    private function set($name, $permissions) {

        $sql_id = '';
        if($this->id)
            $sql_id = " AND `id`!='$this->id'";

        $db = new \ACES2\DB();

        if(empty($name))
            throw new \Exception("Name cannot be empty.");

        $r=$db->query("SELECT id FROM `admin_groups` WHERE lower(`name`) = lower('$name') $sql_id");
        if($r->num_rows > 0 )
            throw new \Exception("Group with this #$name already exists.");
        $this->name = $name;

        foreach($permissions as $name => $value) {
            $Permission[$name] = (bool)$value;
        }
        $this->permissions = $Permission;

    }

    public function update(string $name, array $permissions):bool {

        $this->set($name, $permissions);
        $json_permissions = json_encode($this->permissions);
        $db = new \ACES2\DB();
        $db->query("UPDATE `admin_groups` SET `name`='$name' , permissions='$json_permissions'  
                      WHERE `id`='$this->id'");

        $r=$db->query("SELECT id FROM `admins` WHERE group_id = $this->id ");
        while($admin_id = $r->fetch_assoc()['id']) {
            try {
                $Admin = new Admin($admin_id);
                $Admin->setGroup($this->id);
            } catch(\Exception $e) {
                $igrnore = true;
            }
        }

        return true;

    }

    public function remove():bool {
        $db = new \ACES2\DB();
        $r=$db->query("SELECT id FROM admins WHERE group_id = '$this->id'");
        if($r->num_rows > 0 )
            throw new \Exception("Unable to delete group #$this->id there are admins that belong to this group");

        $db->query("DELETE FROM admin_groups WHERE id = '$this->id'");

        return true;
    }

    public static function add(string $name, array $permissions):self {

        $AdminGroup = new AdminGroup(null);
        $AdminGroup->set($name, $permissions);

        $db = new \ACES2\DB();

        $json_perms = json_encode($AdminGroup->permissions);

        $db->query("INSERT INTO admin_groups ( name, permissions) 
                VALUES('$AdminGroup->name', '$json_perms') ");

        return new self($db->insert_id);

    }

}
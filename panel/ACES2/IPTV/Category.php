<?php

namespace ACES2\IPTV;


use ACES2\DB;

class Category {

    public $id = 0;
    public $name = "";
    public $is_adult = false;

    public function __construct(int $id = null) {

        $db = new DB;
        if(!$id)
            throw new \Exception("Unable to get category #$id from database.");

        $r = $db->query("SELECT * FROM iptv_stream_categories WHERE id = $id");
        if(!$row = $r->fetch_assoc())
            throw new \Exception("Unable to get category #$id from database.");

        $this->id = $id;
        $this->name = $row['name'];
        $this->is_adult = (bool)$row['adults'];

    }

    public function remove() : bool {
        $db = new DB;

        $r= $db->query("SELECT id FROM iptv_channels WHERE category_id = '$this->id' ");
        if($r->fetch_assoc())
            throw new \Exception("Unable to delete category #$this->id streams are using this category");

        $db->query("DELETE FROM iptv_stream_categories WHERE id = $this->id");

        return true;

    }

    public function update(String $name, bool $is_adult) : bool {
        $db = new DB;
        $name = $db->escString($name);
        $is_adult = $is_adult ? 1 : 0;

        $db->query("UPDATE iptv_stream_categories SET name = '$name', adults = '$is_adult' 
                              WHERE id = $this->id ");
        return true;
    }

    public static function add(String $category_name, bool $is_adult = false ): self {
        $db = new DB;

        $category_name = $db->escString($category_name);
        $is_adult = $is_adult ? 1 : 0;

        $r=$db->query("SELECT id FROM iptv_stream_categories WHERE lower(name) = lower('$category_name')");
        if($cat_id = $r->fetch_assoc()['id']) {
            logd("There is a category with this name '$category_name'");
            return new self($cat_id);
        }

        $r_order = $db->query("SELECT ordering from iptv_stream_categories order by ordering desc limit 1");
        $ordering = (int)$r_order->fetch_assoc()['ordering'];

        $r_order_movies = $db->query("SELECT m_ordering from iptv_stream_categories order by m_ordering desc limit 1");
        $m_ordering = (int)$r_order_movies->fetch_assoc()['m_ordering'];

        $r_order_series = $db->query("SELECT s_ordering from iptv_stream_categories order by s_ordering desc limit 1");
        $s_ordering = (int)$r_order_series->fetch_assoc()['s_ordering'];

        $db->query("INSERT INTO iptv_stream_categories(name,adults,ordering,m_ordering,s_ordering) 
            VALUES('$category_name', $is_adult, $ordering, $m_ordering, $s_ordering) ");

        return new self($db->insert_id);
    }

    public static function getCategoryByName(string $name ) {
        $db = new DB;
        $name = $db->escString($name);
        $r = $db->query("SELECT id FROM iptv_stream_categories WHERE lower(name) = lower('$name')");
        if(!$row = $r->fetch_assoc())
            return false;

        return new self($row['id']);
    }

}
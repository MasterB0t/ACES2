<?php

use ACES2\IPTV\Category;

$ADMIN = new \ACES2\Admin();
if(!$ADMIN->isLogged()){
    setAjaxError(\ACES2\ERRORS::NOT_LOGGED, 401);
    exit;
}

if(!$ADMIN->hasPermission(\ACES2\IPTV\AdminPermissions::IPTV_CATEGORIES_FULL)) {
    setAjaxError(\ACES2\Errors::NO_PERMISSIONS);
}

$DB = new \ACES2\DB;

if(!\ACES2\Armor\Armor::isToken('iptv.category',$_REQUEST['token']))
    setAjaxError(\ACES2\Errors::SESSION_EXPIRED);

try {

    switch($_REQUEST['action']) {
        case 'add_category':
            \ACES2\IPTV\Category::add($_REQUEST['name'], (bool)$_REQUEST['is_adult'] );

            break;

        case 'edit_category':
            $Category = new Category($_REQUEST['id']);
            $Category->update($_REQUEST['name'], (bool)$_REQUEST['is_adult']);
            break;

        case 'remove_category':
            $Category = new \ACES2\IPTV\Category($_REQUEST['id']);
            $Category->remove();

            break;

        case 'category_order':

            if(is_file("/home/aces/run/aces_category_order"))
                setAjaxError("Category being sort. Wait until it finish.");

            set_time_limit(0);
            session_write_close();
            echo json_encode(array('status'=> 1 , 'complete' => 1));
            fastcgi_finish_request();

            touch("/home/aces/run/aces_category_order");

            foreach($_POST['categories'] as $c )  {
                $c=(int)$c;
                $DB->query("UPDATE iptv_stream_categories SET ordering = '$o' WHERE id = '$c' ");
                $o++;
            }

            foreach($_POST['movies_categories'] as $c )  {
                $c=(int)$c;
                $DB->query("UPDATE iptv_stream_categories SET m_ordering = '$o' WHERE id = '$c' ");
                $o++;
            }

            foreach($_POST['series_categories'] as $c )  {
                $c=(int)$c;
                $DB->query("UPDATE iptv_stream_categories SET s_ordering = '$o' WHERE id = '$c' ");
                $o++;
            }

            unlink("/home/aces/run/aces_category_order");

            exit;

        default:
            LogD("Unknown Action");
            setAjaxError(\ACES2\ERRORS::SYSTEM_ERROR);
    }

} catch(\Exception $e) {
    LogD($e->getMessage());
    setAjaxError($e->getMessage());
}



setAjaxComplete();
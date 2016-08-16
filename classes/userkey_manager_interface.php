<?php
/**
 * Created by PhpStorm.
 * User: dmitriim
 * Date: 16/08/2016
 * Time: 15:23
 */

namespace auth_userkey;


interface userkey_manager_interface {
    /**
     * Create a user key.
     *
     * @return string Generated key.
     */
    public function create_key();

    /**
     * Delete a user key.
     */
    public function delete_key();

}
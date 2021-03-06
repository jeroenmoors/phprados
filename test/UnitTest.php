/*
 * phprados - A PHP5 extension for using librados
 *
 * Copyright (C) 2013 Wido den Hollander <wido@widodh.nl>
 *
 * This is free software; you can redistribute it and/or
 * modify it under the terms of the GNU Lesser General Public
 * License version 2.1, as published by the Free Software
 * Foundation.  See file COPYING.
 *
 */

<?php

class RadosTest extends PHPUnit_Framework_TestCase {

    public function testRadosConfSetGet() {
        $r = rados_create();
        $this->assertNotNull($r);

        $confOption = "mon_host";
        $confValue = "127.0.0.1";

        rados_conf_set($r, $confOption, $confValue);
        $value = rados_conf_get($r, $confOption);
        $this->assertEquals($confValue, $value);
    }

    public function testRadosConnect() {
        $cluster = rados_create(getenv('id'));
        $this->assertNotNull($cluster);

        rados_conf_set($cluster, "mon_host", getenv('mon_host'));
        rados_conf_set($cluster, "key", getenv('key'));

        $this->assertTrue(rados_connect($cluster));

        return $cluster;
    }

    /**
      * This test will create the pool specified in the environment variable 'pool'
      *
      * After a succesfull creation it will remove the pool again
      *
      * @depends testRadosConnect
     */
    public function testRadosCreatePool($cluster) {
        $this->assertTrue(rados_pool_create($cluster, getenv('pool')));
        return $cluster;
    }

    /**
     * @depends testRadosCreatePool
     */
    public function testRadosCreateIoCTX($cluster) {
        $ioctx = rados_ioctx_create($cluster, getenv('pool'));
        $this->assertNotNull($ioctx);
        return $ioctx;
    }

    /**
     * @depends testRadosCreateIoCTX
     */
    public function testRadosWrite($ioctx) {
        $oid = md5(rand(0,9999999999));
        $buf = sha1($oid);
        $r = rados_write($ioctx, $oid, $buf, 0);
        $this->assertTrue($r);
        $info = array();
        $info['oid'] = $oid;
        $info['buf'] = $buf;
        $info['ioctx'] = $ioctx;
        return $info;
    }

    /**
     * @depends testRadosWrite
     */
    public function testRadosRead($info) {
        $buf = rados_read($info['ioctx'], $info['oid'], strlen($info['buf']));
        $this->assertEquals($buf, $info['buf']);
        return $info;
    }

    /**
     * @depends testRadosRead
     */
    public function testRadosRemove($info) {
        $r = rados_remove($info['ioctx'], $info['oid']);
        $this->assertTrue($r);
    }

    /**
     * @depends testRadosCreatePool
     */
    public function testRadosDeletePool($cluster) {
        $this->assertTrue(rados_pool_delete($cluster, getenv('pool')));
    }

    /**
     * @depends testRadosConnect
     */
    public function testRadosPoolList($cluster) {
        $this->assertNotNull(rados_pool_list($cluster));
    }

    /**
     * @depends testRadosConnect
     */
    public function testRadosShutDown($cluster) {
        $this->assertTrue(rados_shutdown($cluster));
    }
}

?>
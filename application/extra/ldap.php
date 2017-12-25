<?php
// +----------------------------------------------------------------------
// | ThinkPHP [ WE CAN DO IT JUST THINK ]
// +----------------------------------------------------------------------
// | Copyright (c) 2006~2016 http://thinkphp.cn All rights reserved.
// +----------------------------------------------------------------------
// | Licensed ( http://www.apache.org/licenses/LICENSE-2.0 )
// +----------------------------------------------------------------------
// | Author: liu21st <liu21st@gmail.com>
// +----------------------------------------------------------------------

define('LDAP_IP','192.168.251.17');
//define('LDAP_IP','172.16.10.197:801');
return [
    'appNo'                 =>  '14',
    'appKey'                =>  '8VwT1oedhBPnyLhk',
//    'appNo'                 =>	'5',
//    'appKey'                =>	'1peOZV1CDZM0ZAYA',
    'getWUnitUrl'           =>	'http://'.LDAP_IP.'/homev1/api/getWDept',
    'getWPersonUrl'         =>  'http://'.LDAP_IP.'/homev1/api/getWPerson',
    'getUnitUrl'            =>	'http://'.LDAP_IP.'/homev1/api/getSubChildDept',
    'getPersonUrl'          =>  'http://'.LDAP_IP.'/homev1/api/getPersonBydept',
    'loginUrl'              =>  'http://'.LDAP_IP.'/homev1/api/ucUserLogin',
    'updatePasswordUrl'     =>  'http://'.LDAP_IP.'/homev1/Platform/updatePassword',
    'resetPasswordUrl'      =>  'http://'.LDAP_IP.'/homev1/Platform/resetPassword',
    'findPasswordUrl'       =>  'http://'.LDAP_IP.'/homev1/Platform/findPassword',
    'apiurl'                =>  'http://'.LDAP_IP.'/',

];

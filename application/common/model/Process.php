<?php
namespace app\common\model;
use think\Model;

class Process extends Model
{
	protected $createTime = 'createTime';
    protected $updateTime = 'updateTime';

    protected $auto = [];
    protected $insert = [
        'status'	=>	1
    ];
    protected $update = [];
}
<?php

namespace App\Model;

use App\Base\Model;

class UserModel extends Model
{
    /**
     * 与模型关联的表名
     *
     * @var string
     */
    protected $table = 'users';

    /**
     * 重定义主键，默认是id
     *
     * @var string
     */
    protected $primaryKey = 'id';

    /**
     * 允许批量赋值的字段白名单。
     *
     * Eloquent 默认 $guarded = ['*']（totallyGuarded() 为 true），未声明 $fillable 时
     * create()/fill() 会抛 MassAssignmentException。按需在此追加业务字段。
     *
     * @var array<int, string>
     */
    protected $fillable = ['name', 'email'];

    /**
     * 指示是否自动维护时间戳
     *
     * @var bool
     */
    public $timestamps = false;
}
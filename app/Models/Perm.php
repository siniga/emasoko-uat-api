<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

use Illuminate\Support\Facades\DB;

class Perm extends Model
{
    //get all perms
    public static function get_perms_all()
    {
        $classes = array();
        $perm_classes = PermClass::all();

        foreach ($perm_classes as $key => $value) {
            $methods = PermMethod::where(['class_id' => $value->id])->get();

            foreach ($methods as $k => $v) {
                $classes[$value->name][$v->name] = array($value->id, $v->id, $v->name);
            }
        }
        return $classes;
    }
}

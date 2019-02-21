<?php

namespace App\Models;

use Illuminate\Support\Facades\DB;

class GenericModel {

    function insertGeneric($tableName, $data) {
        $result = DB::table($tableName)->insert($data);

        if(count($result) > 0)
            return true;
        else
            return false;

    }

    function insertGenericAndReturnID($tableName, $data){
        $result = DB::table($tableName)->insertGetId($data);
        return $result;
    }

    function updateGeneric($table, $whereField, $whereFieldValue, $data) {
        $result = DB::table($table) -> where ($whereField , '=', $whereFieldValue) -> update( $data );
        if( count($result) > 0)
            return true;
        else
            return false;
    }

    function deleteGeneric($table, $whereField, $whereFieldValue) {
        $result = DB::table($table) -> where( $whereField, '=', $whereFieldValue )->delete();
        if(count($result) > 0)
            return true;
        else
            return false;
    }

    static public function simpleFetchGenericByWhere($tableName, $operator, $columnName, $data, $orderby){
        return DB::table($tableName)
            -> select('*')
            -> where($columnName, $operator ,$data)
            ->orderBy($orderby, 'ASC')
            ->get();
    }

    static public function simpleFetchGenericWithPaginationByWhereWithSortOrder($tableName, $operator, $columnName, $data, $offset, $limit, $orderby){
        return DB::table($tableName)
            -> select('*')
            -> where($columnName, $operator ,$data)
            ->orWhere('like', '%' . Input::get('name') . '%')
            ->offset($offset)->limit($limit)
            ->orderBy($orderby, 'ASC')
            ->get();
    }

    static public function simpleFetchGenericCount($tableName, $operator, $columnName, $data){
        return DB::table($tableName)
            -> where($columnName, $operator ,$data)
            -> count();
    }

}

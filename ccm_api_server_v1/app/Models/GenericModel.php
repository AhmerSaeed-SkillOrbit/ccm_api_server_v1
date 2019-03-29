<?php

namespace App\Models;

use Illuminate\Support\Facades\DB;

class GenericModel
{

    static public function insertGeneric($tableName, $data)
    {
        $result = DB::table($tableName)->insert($data);
        return $result;
    }

    static public function insertGenericAndReturnID($tableName, $data)
    {
        $result = DB::table($tableName)->insertGetId($data);
        return $result;
    }

    static public function updateGeneric($table, $whereField, $whereFieldValue, $data)
    {
        $result = DB::table($table)->where($whereField, '=', $whereFieldValue)->update($data);
        return $result;
    }

    static public function deleteGeneric($table, $whereField, $whereFieldValue)
    {
        $result = DB::table($table)->where($whereField, '=', $whereFieldValue)->delete();
        return $result;
    }

    static public function simpleFetchGenericByWhere($tableName, $operator, $columnName, $data, $orderby = "Id")
    {
        return DB::table($tableName)
            ->select('*')
            ->where($columnName, $operator, $data)
            ->orderBy($orderby, 'ASC')
            ->get();
    }

    static public function simpleFetchGenericWithPaginationByWhereWithSortOrderAndSearchKeyword
    ($tableName, $operator, $columnName, $data, $offset, $limit, $orderBy, $keyword, $searchColumnName)
    {

        if ($keyword != null && $keyword != "null") {

            return DB::table($tableName)
                ->select('*')
                ->where($columnName, $operator, $data)
                ->Where($searchColumnName, 'like', '%' . $keyword . '%')
//                ->offset($offset)->limit($limit)
                ->skip($offset * $limit)->take($limit)
                ->orderBy($orderBy, 'ASC')
                ->get();
        } else {
            return DB::table($tableName)
                ->select('*')
                ->where($columnName, $operator, $data)
//                ->offset($offset)->limit($limit)
                ->skip($offset * $limit)->take($limit)
                ->orderBy($orderBy, 'ASC')
                ->get();
        }
    }

    static public function simpleFetchGenericWithPaginationByWhereWithSortOrder
    ($tableName, $operator, $columnName, $data, $offset, $limit, $orderby)
    {

        return DB::table($tableName)
            ->select('*')
            ->where($columnName, $operator, $data)
//            ->offset($offset)->limit($limit)
            ->skip($offset * $limit)->take($limit)
            ->orderBy($orderby, 'ASC')
            ->get();
    }

    static public function simpleFetchGenericCountWIthKeyword($tableName, $operator, $columnName, $data, $searchColumnName, $keyword)
    {
        if ($keyword != null && $keyword != "null") {

            return DB::table($tableName)
                ->where($columnName, $operator, $data)
                ->Where($searchColumnName, 'like', '%' . $keyword . '%')
                ->count();
        } else {

            return DB::table($tableName)
                ->where($columnName, $operator, $data)
                ->count();
        }
    }
}

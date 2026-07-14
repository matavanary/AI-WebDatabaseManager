<?php

namespace App\Core;

class SchemaBuilder
{
    /**
     * Get the query to list all databases
     */
    public static function getDatabasesQuery($driver)
    {
        if ($driver === 'sqlsrv') {
            return "SELECT name AS [Database] FROM sys.databases WHERE name NOT IN ('master', 'tempdb', 'model', 'msdb')";
        }
        return "SHOW DATABASES";
    }

    /**
     * Get the query to list all tables in a specific database
     */
    public static function getTablesQuery($driver, $dbName)
    {
        if ($driver === 'sqlsrv') {
            return "SELECT TABLE_NAME AS Tables_in_$dbName FROM [$dbName].INFORMATION_SCHEMA.TABLES WHERE TABLE_TYPE = 'BASE TABLE' ORDER BY TABLE_NAME ASC";
        }
        return "SHOW TABLES FROM `$dbName`";
    }

    /**
     * Get the query to describe columns of a table
     */
    public static function getColumnsQuery($driver, $dbName, $tableName)
    {
        if ($driver === 'sqlsrv') {
            // Note: Does not parameterize $tableName yet due to PDO limitations on table names, so we just return the query string and the controller binds it, OR we inject it.
            return "
            SELECT 
                c.COLUMN_NAME AS Field, 
                c.DATA_TYPE AS Type, 
                c.IS_NULLABLE AS [Null], 
                c.COLUMN_DEFAULT AS [Default],
                ISNULL((
                    SELECT 'PRI' 
                    FROM [$dbName].INFORMATION_SCHEMA.KEY_COLUMN_USAGE kcu 
                    INNER JOIN [$dbName].INFORMATION_SCHEMA.TABLE_CONSTRAINTS tc ON kcu.CONSTRAINT_NAME = tc.CONSTRAINT_NAME 
                    WHERE kcu.TABLE_NAME = c.TABLE_NAME AND kcu.COLUMN_NAME = c.COLUMN_NAME AND tc.CONSTRAINT_TYPE = 'PRIMARY KEY'
                ), '') AS [Key]
            FROM [$dbName].INFORMATION_SCHEMA.COLUMNS c 
            WHERE c.TABLE_NAME = :table";
        }
        return "SHOW COLUMNS FROM `$tableName`"; // Assumes "USE $dbName" is called before
    }

    /**
     * Generate Pagination clause (LIMIT/OFFSET)
     */
    public static function paginate($driver, $sql, $limit, $offset, $orderBy = null)
    {
        if ($driver === 'sqlsrv') {
            // SQL Server 2012+ requires ORDER BY for OFFSET
            if (stripos($sql, 'ORDER BY') === false) {
                if ($orderBy) {
                    $sql .= " ORDER BY $orderBy";
                } else {
                    $sql .= " ORDER BY (SELECT NULL)"; // Dummy order by if none provided
                }
            }
            return "$sql OFFSET $offset ROWS FETCH NEXT $limit ROWS ONLY";
        }
        return "$sql LIMIT $limit OFFSET $offset";
    }
    
    /**
     * Get table foreign keys
     */
    public static function getForeignKeysQuery($driver, $dbName)
    {
        if ($driver === 'sqlsrv') {
            return "
                SELECT 
                    fk.name AS fk_name,
                    tp.name AS TABLE_NAME,
                    cp.name AS COLUMN_NAME,
                    tr.name AS REFERENCED_TABLE_NAME,
                    cr.name AS REFERENCED_COLUMN_NAME
                FROM [$dbName].sys.foreign_keys fk
                INNER JOIN [$dbName].sys.tables tp ON fk.parent_object_id = tp.object_id
                INNER JOIN [$dbName].sys.tables tr ON fk.referenced_object_id = tr.object_id
                INNER JOIN [$dbName].sys.foreign_key_columns fkc ON fkc.constraint_object_id = fk.object_id
                INNER JOIN [$dbName].sys.columns cp ON fkc.parent_column_id = cp.column_id AND fkc.parent_object_id = cp.object_id
                INNER JOIN [$dbName].sys.columns cr ON fkc.referenced_column_id = cr.column_id AND fkc.referenced_object_id = cr.object_id
            ";
        }
        
        return "
            SELECT 
                TABLE_NAME, COLUMN_NAME, REFERENCED_TABLE_NAME, REFERENCED_COLUMN_NAME
            FROM 
                INFORMATION_SCHEMA.KEY_COLUMN_USAGE
            WHERE 
                REFERENCED_TABLE_SCHEMA = '$dbName'
                AND REFERENCED_TABLE_NAME IS NOT NULL
        ";
    }

    /**
     * Process list query for Monitor
     */
    public static function getProcessListQuery($driver)
    {
        if ($driver === 'sqlsrv') {
            return "
                SELECT 
                    req.session_id AS Id,
                    sess.login_name AS [User],
                    sess.host_name AS Host,
                    DB_NAME(req.database_id) AS db,
                    req.command AS Command,
                    req.total_elapsed_time / 1000 AS Time,
                    req.status AS State,
                    txt.text AS Info
                FROM sys.dm_exec_requests req
                CROSS APPLY sys.dm_exec_sql_text(req.sql_handle) txt
                JOIN sys.dm_exec_sessions sess ON req.session_id = sess.session_id
                WHERE req.session_id <> @@SPID
            ";
        }
        return "SHOW FULL PROCESSLIST";
    }

    /**
     * Query to kill a process
     */
    public static function getKillProcessQuery($driver, $id)
    {
        if ($driver === 'sqlsrv') {
            return "KILL " . (int)$id;
        }
        return "KILL " . (int)$id;
    }
}

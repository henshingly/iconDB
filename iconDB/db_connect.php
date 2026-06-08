<?php

$pdo = NULL;

// MySQL database functions
function dbquery($query, $params = [])
{
    global $pdo;
    $result = $pdo->prepare($query);
    if (!$result) {
        print_r($result->errorInfo());
        return false;
    } else {
        $result->execute($params);
        return $result;
    }
}

function dbquery_exec($query)
{
    global $pdo;
    $result = $pdo->exec($query);
    return $result;
}

function dbcount($field, $table, $conditions = '')
{
    global $pdo;
    $cond = ($conditions ? ' WHERE ' . $conditions : '');
    $result = $pdo->prepare('SELECT COUNT' . $field . ' FROM ' . DB_PREFIX . $table . $cond);
    if (!$result) {
        print_r($result->errorInfo());
        return false;
    } else {
        $result->execute();
        return $result->fetchColumn();
    }
}

function dbresult($query, $row)
{
    global $pdo;
    $data = $query->fetchAll();
    if (!$query) {
        print_r($query->errorInfo());
        return FALSE;
    } else {
        $result = $query->getColumnMeta(0);
        return $data[$row][$result['name']];
    }
}

function dbrows($query)
{
    return $query->rowCount();
}

function dbarray($query)
{
    global $pdo;
    $query->setFetchMode(PDO::FETCH_ASSOC);
    return $query->fetch();
}

function dbarraynum($query)
{
    global $pdo;
    $query->setFetchMode(PDO::FETCH_NUM);
    return $query->fetch();
}

function dbconnect($db_host, $db_user, $db_pass, $db_name)
{
    global $pdo;
    try {
        $pdo = new PDO('mysql:host=' . $db_host . ';dbname=' . $db_name . ';encoding=utf8', $db_user, $db_pass);
        $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    } catch (PDOException $error) {
        die('<strong>Unable to select MySQL database</strong><br />' . $error->getMessage());
    }
}

$link = dbconnect(LMOID_DB_HOST, LMOID_DB_USER, LMOID_DB_PASS, LMOID_DB);

?>

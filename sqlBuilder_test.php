<?php
/**
 * Created by PhpStorm.
 * User: Mson
 * Date: 01-05-2019
 * Time: 14:47
 */

include_once "utils.php";
include_once "sqlBuilder.php";

try {
    $queries = array();
    $results = array();

    $sqlBuilder = new sqlBuilder();

    $columns = array("first", "second");
    $values = array(array("11", "12"), array("21", "22"));
    $addValue = array(array("31", "32"), array("41", "42"));
    $setValue = array("1", "2");
    $addSetValue = array("3", "4");
    $addColumns = array("third", "fourth");

    /*
     * Insert with prepared statements for 5 objects.
     * Expected result: "INSERT INTO `table` (`asd`, `qwe`) VALUES (:asd_0, :qwe_0), (:asd_1, :qwe_1), (:asd_2, :qwe_2), (:asd_3, :qwe_3), (:asd_4, :qwe_4)"
     */
    $sqlBuilder->createInsert("table", array("asd", "qwe"), 5);
    array_push($queries, $sqlBuilder->getSql());
    array_push($results, $sqlBuilder->getSql() == "INSERT INTO `table` (`asd`, `qwe`) VALUES (:asd_0, :qwe_0), (:asd_1, :qwe_1), (:asd_2, :qwe_2), (:asd_3, :qwe_3), (:asd_4, :qwe_4)");

    /*
     * Insert with prepared statements with question marks.
     * Expected result: "INSERT INTO `table` (`qaz`, `wsx`) VALUES (?, ?)"
     */
    $sqlBuilder->resetSqlBuilder();
    $sqlBuilder->createInsert("table", array("qaz", "wsx"));
    array_push($queries, $sqlBuilder->getSql());
    array_push($results, $sqlBuilder->getSql() == "INSERT INTO `table` (`qaz`, `wsx`) VALUES (?, ?)");

    /*
     * Insert with values of multiple objects.
     * Expected result: "INSERT INTO `table` (`first`, `second`) VALUES ('11', '12'), ('21', '22')"
     */
    $sqlBuilder->resetSqlBuilder();
    $sqlBuilder->createInsert("table", $columns, $values);
    array_push($queries, $sqlBuilder->getSql());
    array_push($results, $sqlBuilder->getSql() == "INSERT INTO `table` (`first`, `second`) VALUES ('11', '12'), ('21', '22')");

    /*
     * Insert where extra values are added.
     * Expected result: "INSERT INTO `table` (`first`, `second`) VALUES ('11', '12'), ('21', '22'), ('31', '32'), ('41', '42')"
     */
    $sqlBuilder->setPartValuesCustom($addValue);
    array_push($queries, $sqlBuilder->getSql());
    array_push($results, $sqlBuilder->getSql() == "INSERT INTO `table` (`first`, `second`) VALUES ('11', '12'), ('21', '22'), ('31', '32'), ('41', '42')");

    /*
     * Update with prepared statements with question marks.
     * Expected result: "UPDATE `table` SET `first` = ?, `second` = ?"
     */
    $sqlBuilder->resetSqlBuilder();
    $sqlBuilder->createUpdate("table", $columns);
    array_push($queries, $sqlBuilder->getSql());
    array_push($results, $sqlBuilder->getSql() == "UPDATE `table` SET `first` = ?, `second` = ?");

    /*
     * Update with values of multiple fields.
     * Expected result: "UPDATE `table` SET `first` = '1', `second` = '2'"
     */
    $sqlBuilder->resetSqlBuilder();
    $sqlBuilder->createUpdate("table", $columns, $setValue);
    array_push($queries, $sqlBuilder->getSql());
    array_push($results, $sqlBuilder->getSql() == "UPDATE `table` SET `first` = '1', `second` = '2'");

    /*
     * Updates where extra fields are added.
     * Expected result: "UPDATE `table` SET `first` = '1', `second` = '2', `third` = '3', `fourth` = '4'"
    */
    $sqlBuilder->setPartSetCustom($addColumns, $addSetValue);
    array_push($queries, $sqlBuilder->getSql());
    array_push($results, $sqlBuilder->getSql() == 'UPDATE `table` SET `first` = \'1\', `second` = \'2\', `third` = \'3\', `fourth` = \'4\'');
} catch (Exception $e) {
    echo "<b>Fatal exception: </b>" . $e->getMessage() . "<br>";
    echo "<b>Exception occurred at: </b>" . $e->getFile() . "#" . $e->getLine() . "<br>";
//    echo "<b>Stackflow: </b>" . $e->getTraceAsString();
}

echo "<table style='border:1px black solid;border-collapse:collapse;'>";
for ($i = 0; $i < count($queries); $i++) {
    echo "<tr style='border:1px black solid;border-collapse:collapse;'>";
    $color = $results[$i] ? "green" : "red";
    echo "<td style='border:1px black solid;border-collapse:collapse;background:{$color};' width='20'></td>";
    echo "<td style='border:1px black solid;border-collapse:collapse;'>" . $queries[$i] . "</td>";
    echo "</tr>";
}
echo "</table>";
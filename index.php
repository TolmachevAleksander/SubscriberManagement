<?php

  ######################
  ## Подключение к бд ##
  ######################

require_once 'connection.php'; // подключение к скрипту 
$db = new mysqli($host, $user, $password, $database);
// проверка на успешное подключение и вывод ошибки, если оно не выполнено
if ($db->connect_error) {
    echo "Нет подключения к БД. Ошибка:".mysqli_connect_error();
    exit;
}

  ####################
  ## Создание формы ##
  ####################

?>
<form  action="" method="POST" align="center"><fieldset style="width: 400px;">
IP-адрес: &nbsp<input type="text" name="IP">
<br><br>Скорость исходящего трафика:&nbsp<input type="text" name="src" style="width: 30px;">Mb/s
<br><br>Скорость входящего трафика:&nbsp&nbsp&nbsp<input type="text" name="dst" style="width: 30px;">Mb/s
<br><br><input type="submit" value="Добавить" name="submit">&nbsp&nbsp&nbsp<input type="submit" value="Удалить" name="submit2"><br>
</fieldset></form>
<?php

  #######################
  ## Скрипты для формы ##
  #######################


if(isset($_POST['submit']) && isset($_POST['IP']) && isset($_POST['dst']) && isset($_POST['src'])
&& $_POST['submit'] !== "" && $_POST['IP'] !== "" && $_POST['dst'] !== "" && $_POST['src'] !== "") { // условие для кнопки "Добавить"
    $ip = $_POST['IP'];
    $src = $_POST['src'];
    $dst = $_POST['dst'];
    exec('sudo ipset -A addresses '.$ip); // добавляет ip в список ipset
    exec('sudo echo @+'.$ip.' '.$src.'000000 > /proc/net/ipt_ratelimit/name0'); // добавляет правило в ipt_ratelimit на исходящий трафик
    exec('sudo echo @+'.$ip.' '.$dst.'000000 > /proc/net/ipt_ratelimit/name1'); // добавляет правило в ipt_ratelimit на входящий трафик
    exec('sudo iptables-save > /etc/iptables.up.rules'); //сохраняет правила IPtables
    exec('sudo ipset save addresses'); // сохраняет правила IPset
    $query ="INSERT ip(address, src, dst) VALUES ('$ip', '$src', '$dst')"; // создание строки запроса
    $result = mysqli_query($db, $query) or die("Ошибка " . mysqli_error($db)); 
    if($result) {
        echo "<span style='color:blue;'>Данные добавлены</span>";
    }
}
    else if(isset($_POST['submit']) && $_POST['IP'] == "" && $_POST['dst'] == "" && $_POST['src'] == "") {
	echo "<span style='color:red;'>ОШИБКА! УКАЖИТЕ ДАННЫЕ</span>";
    }
    else if(isset($_POST['submit']) && isset($_POST['IP']) && $_POST['dst'] == "" && $_POST['src'] == "") {
	echo "<span style='color:red;'>ОШИБКА! УКАЖИТЕ СКОРОСТЬ ВХОДЯЩЕГО И ИСХОДЯЩЕГО ТРАФИКА</span>";
    }
    else if(isset($_POST['submit']) && isset($_POST['dst']) && $_POST['IP'] == "" && $_POST['src'] == "") {
	echo "<span style='color:red;'>ОШИБКА! УКАЖИТЕ IP-АДРЕС И СКОРОСТЬ ИСХОДЯЩЕГО ТРАФИКА</span>";
    }
    else if(isset($_POST['submit']) && isset($_POST['src']) && $_POST['IP'] == "" && $_POST['dst'] == "") {
	echo "<span style='color:red;'>ОШИБКА! УКАЖИТЕ IP-АДРЕС И СКОРОСТЬ ВХОДЯЩЕГО ТРАФИКА</span>";
    }
    else if(isset($_POST['submit']) && isset($_POST['IP']) && isset($_POST['dst']) && $_POST['src'] == "") {
	echo "<span style='color:red;'>ОШИБКА! УКАЖИТЕ СКОРОСТЬ ИСХОДЯЩЕГО ТРАФИКА</span>";
    }
    else if(isset($_POST['submit']) && isset($_POST['IP']) && isset($_POST['src']) && $_POST['dst'] == "") {
	echo "<span style='color:red;'>ОШИБКА! УКАЖИТЕ СКОРОСТЬ ВХОДЯЩЕГО ТРАФИКА</span>";
    }
    else if(isset($_POST['submit']) && isset($_POST['dst']) && isset($_POST['src']) && $_POST['IP'] == "") {
	echo "<span style='color:red;'>ОШИБКА! УКАЖИТЕ IP-АДРЕС</span>";
    }
if(isset($_POST['submit2']) && isset($_POST['IP'])) { // скрипт для кнопки "Удалить"
    $ip = $_POST['IP'];
    $src = $_POST['src'];
    $dst = $_POST['dst'];
    exec('sudo ipset -D addresses '.$ip.''); // удаляет ip из списка ipset
    exec('sudo echo -'.$ip.' > /proc/net/ipt_ratelimit/name0'); // удаляет правило в ipt_ratelimit на исходящий трафик
    exec('sudo echo -'.$ip.' > /proc/net/ipt_ratelimit/name1'); // удаляет правило в ipt_ratelimit на входящий трафик
    $query3 ="DELETE from `ip` where `address` = '$ip';"; // создание строки запроса
    $result3 = mysqli_query($db, $query3) or die("Ошибка " . mysqli_error($db));
    if($result3) {
        echo "<span style='color:blue;'>Данные удалены</span>";
    }
}

  ##########################################
  ## Скрипт для отображения таблицы из бд ##
  ##########################################

$query2 = "SELECT * FROM ip";
$result2 = mysqli_query($db, $query2) or die("Ошибка " . mysqli_error($db)); 
if($result2) {
    $rows = mysqli_num_rows($result2); // количество полученных строк
    echo "<table border='2'><tr><th>Номер</th><th>IP-address</th><th>Скорость<br>исходящего трафика</th><th>Скорость<br> входящего трафика</th></tr>";
    for ($i = 0 ; $i < $rows ; ++$i) {
        $row = mysqli_fetch_row($result2);
        echo "<tr>";
            for ($j = 0 ; $j < 4 ; ++$j) echo "<td align='center'>$row[$j]</td>";
        echo "</tr>";
    }
    echo "</table>";
    mysqli_free_result($result2); // освобождает память, занятую результатами запроса
}
?>

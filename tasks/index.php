<?
require_once('config.php');

$collector = function (\Task $task) {
	$log =  $task["dt"] . "\n" . print_r($task['value'] , true);
	file_put_contents(__DIR__ . "\log.txt", $log . PHP_EOL, FILE_APPEND);
	return true;
};

class Task extends Threaded
{
    private $num;
	private $value;
	private $dt;

    public function __construct($i,$arr)
    {
        $this->num = $i;
		$this->value = $arr;
    }

    public function run()
    {
		$this->dt = date('Y-m-d H:i:s');
											// echo "Task: {$this->num}\n";						
    }
}

$tasks = file_get_contents("tasks.json");
$tasks = json_decode($tasks, true);

$attr = array();
$pool = new Pool($tasck_count);

/** ПРИМЕР С ЗАПРОСОМ ВЫПОЛНЕННЫХ ЗАДАЧ ХРАНЯЩИХСЯ В ОБЪЕКТЕ $pool **/
for ($i = 0; $i < count($tasks); ++$i) {

		$completed = false;
		
		foreach($attr as $val){	
			if (in_array($tasks[$i], (array)$val, true)) {
				echo "Эта задача уже выполнялась! \n";
				var_dump($tasks[$i]);
				$completed = true;
			}
		}
	
		if(!$completed){
			$pool->submit(new Task($i,$tasks[$i]));
			$attr = (array)$pool;;
			$attr = $attr["\0*\0work"]; 
											//var_dump($attr[0]["value"]);
		}
	$parr[] = $tasks[$i];
}
/**/

/** ПРИМЕР С ЗАПОМЕНАНИЕМ ВЫПОЛНЕННЫХ ЗАДАЧ В РУЧНУЮ
for ($i = 0; $i < count($tasks); ++$i) {
	if (!in_array($tasks[$i], $attr, true)) {
		$pool->submit(new Task($i,$tasks[$i]));
	}else{ 
		echo "Эта задача уже выполнялась! \n";
		var_dump($tasks[$i]); 
	}
	$attr[] = $tasks[$i];
}
**/


while ($pool->collect($collector));

$pool->shutdown();
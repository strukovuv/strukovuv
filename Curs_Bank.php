<?php
/*
 *   Получение курса валют. 
 *      Предполагается, что считывание курса валют будет 
 *      происходить из многих программ и из разных подразделений
 * -----------------
 * Для получения курса надо вызвать функцию  get_curs($Valute) 
 *       * Вход: 
 *       * $Valute - валюта, например 'USD', или 'RUB'
 *       * Выход:
 *       * -значение курса
 * -----------------
 * 
 *      Краткое описани алгоритма 
 *      Функция get_curs инициализирует создание класса kursValutes
 *      Алгоритм работы класса
 *      1. Если в локальном кэше есть значение курса, то сразу дается ответ - курс из кэша.
 *      2. Если в кэш устарел(по умолчанию 1 час. см. настройки в классе),
 *      то курс ищется в базе, содержащей таблицу курсов(см. ниже - Параметры подлючения к базе). 
 *      Если данные актуальны- то выход- значение курса из таблицы
 *      3. Если данные в кэше и базе не актуальны, устарели, , то послыется запрос в центробанк на скачивание
 *      xml файла, производится парсинг файла, обновление кэш, обновление таблицы курсов.
 *      Возврат - значение  курс из хмl файла
 * 
 */




//Класс чтениея курса валюты
class kursValutes
{
    public $list_curs = [];// Массив курсов в формате ИмяВалюты(ключ)=значение
    
    public $time_cache = "3600"; // Время жизни кеша в секундах. Как часто обновлять $list_curs
    
    public $file__cache  = __DIR__."/XML_daily.asp";//Файл кэша
  
    //адрес откуда скачать  курсы в формат xml
    public $url = "http://www.cbr.ru/scripts/XML_daily.asp?date_req=";// . date('d.m.Y');
    
    //Параметры подлючения к базе
     public $server_db= 'localhost';
     public $username=  'root';
     public $password = 'xxxxxx';
     public $database_name = 'db_XXX';    
     public $table_curs='kurs';//имя таблицы в базе database_name  с курсами валют
     
    
//--------------------------------------------------------------------------------------------------------------------------------------------------------------------------
    function __construct()
    {
        /* Конструктор класса kursValutes
         * 
         * 
         * Инициализация класса 
         * 
         * 
         * $time_cache = "3600"; // Время жизни кеша в секундах. Как часто обновлять $list_curs
         * $file__cache  = __DIR__."/XML_daily.asp";//Файл кэша
         * $url = "http://www.cbr.ru/scripts/XML_daily.asp?date_req=";//адрес откуда скачать  курсы в формат xml
         *
         * *Параметры подлючения к базе
         *$server_db= 'localhost';
         *$username=  'root';
         *$password = 'xxxxxx';
         *$database_name = 'db_XXX';    
         *$table_curs='kurs';//имя таблицы в базе database_name  с курсами валют
        
         * Вход: 
         * Нет
         * Выход: Нет
         */
        
        /*
         * Функция не прописана, поэтому  будут барться дефолтные значения 
         * 
         */
        
    }
    
//-------------------------------------------------------------------------------------------------------------------------------------------------------------------------- 
   public function load_list_cursFromHttp()
   {
       /* Загрузка массива курсов (валюта=значение) из удаленного источника см.$url
        * 
        * Вход: нет
        * 
        * Выход:
        * true-заполнен массив  $list_curs
        *         обновлена база данных
        *         обновлен кэш   
        * 
        * false-ошибка
        */
        $xml = new DOMDocument();
        $url1 = $this->$url. date('d.m.Y');//Курс на тек дату
        if (!$xml->load($url1))return false;
        
            $this->list_curs = []; 
            $root = $xml->documentElement;
            $items = $root->getElementsByTagName('Valute');
         
            if(count($items)==0)return false;
            
         //Вставка в  базу новых курсов
         //А есть ли таблиц  а
         
                 
         $db =     mysqli_connect($this->server_db,$this->username,$this->password,$this->database_name);   
         $this->check_table_curs($db);   
                 
             
           

         
            foreach ($items as $item)
            {
                $code = $item->getElementsByTagName('CharCode')->item(0)->nodeValue;
                $curs = $item->getElementsByTagName('Value')->item(0)->nodeValue;
                $this->list_curs[$code] = floatval(str_replace(',', '.', $curs));
                if($db)$this->update_table_curs($db,$code,$curs);    //сохраняю в базе
            }
            
            //сохраняю данные в файле-кэше
            $doc = new DOMDocument();
            $doc->loadXML($this->list_curs);
            echo $doc->saveXML($this->file__cach);
            
 
            return true;
    }   
  //--------------------------------------------------------------------------------------------------------------------------------------------------------------------------              
    
public function update_table_curs($db,$code,$curs)    
  {
       /*Обновление или вставка записей в табл curs
        * 
        * Вход: $db,
        * $code- код валюты
        * $curs- курс
        * Выход: нет
        */
  if($db==false)return;
  $result=mysqli_query($db,"select * from ".$this->table_curs." where dt= CURDATE() and code ='".$code."' " );
  if($result==false)
            mysqli_query($db,"INSERT INTO ".$this->table_curs." (code,curs,dt) VALUES ('".$code."',". $curs.", CURDATE())");
  else        
      mysqli_query($db,"UPDTARE  ".$this->table_curs
              ."SET "
              ." code = '".$code."', "
              ." curs =".$curs.", "
              ." dt = CURDATE()"
              ." where dt= CURDATE() and code ='".$code."' " );
      
             
      
  } 
  //----------------------------------------------------------------------------------------------------------------------------------------------------------------------------      
  public function check_table_curs($db)    
  {
       /*Проверка, есть ли таблиица курсов.
        * Если нет то создать ее
        * Вход: $db
        * Выход: нет
        */
$query =  mysqli_query("SHOW TABLES FROM ".$yhis->database_name."  LIKE '".$this->table_curs."';");
        $result = mysql_fetch_array($query);
           if($result===false)
            {
            //Таблица не найдена- создадим ее
                 mysqli_query($db,"CREATE TABLE ".$this->table_curs
                                        ."("           
                                        ."id INT NOT NULL AUTO_INCREMENT PRIMARY KEY,"
                                        ."code VARCHAR(200) NOT NULL,"
                                        ."curs decimal(8,4)NOT NULL,"
                                        ."dt datetime NOT NULL"
                                        . "   )");
            }
             
      
  } 
  //----------------------------------------------------------------------------------------------------------------------------------------------------------------------------  
   public function load_list_cursFromDB()
   {
       /* Загрузка массива курсов (валюта=значение) из базы см. tbale_curs
        *
        * 
        * Вход: $url
        * Выход:
        * true-заполнен массив  $list_curs
        * false-ошибка
        */
       
       $db =     mysqli_connect($this->server_db,$this->username,$this->password,$this->database_name);
        if ($db == false)return false;//Не удаось подключится к БД
       
         $result=mysqli_query($db,"select * from ".$this->table_curs." where dt= CURDATE()");
         if( $result==false)return false;
         
         $this->list_curs=[];
            foreach ($items as $result)
            {
                $this->list_curs[$items['code']] = floatval(str_replace(',', '.', $items['curs']));
            }
       
        
            return true;
    }   
  //--------------------------------------------------------------------------------------------------------------------------------------------------------------------------                  
    public function load_list_curs()
    {
 
        /*  
         * 
         * Проверка на заполнение массива       $this->list_curs['валюта'=>'значение']
         * 
         * 1.Если массив актуален то ответ из массива
         * Если массив не актуален, то массив перезагружается из кэша, время жизни которого равно time_cache сек
         * 2. Если кэш не актуален, , то массив перезагружается из базы
         * 3. Если кэш и таблица курсов не актуальны,  то считывается из xml по запросу HTTP
         * Вход:Нет
         * Выход: false - ошибка, массив не заполнен 
         * true- массив заполнен и актуален
         */

            if( !is_file($this->$file__cache) ||(filemtime($this->$file__cache)) < (time() - $this->$time_cache)) 
            {//В кэше нет ничего   в течении $this->$time_cache секунд

                // В базе есть данные?
                if(! $this->load_list_cursFromDB()) {
                                                                    //В базе данные старые,(иначе-true), полэтому  загрузим курсы из HTTP
                                                                      if(!$this->load_list_cursFromHttp()) return false; //Ошибка загрузки
                                                                    //true- Массив list_curs- заполнен, кэш файл заполнен , база заполнена
                                                                    }


          }
  return true;
    }

}
    
    
//---------------------------------------------------------------------------    

    function get_curs($Valute)
     {
       /*
        * Вход: 
        * Valute - валюта, например 'USD', или 'RUB'
        * Выход:
        * -значение курса
        */ 
        $curs=0;
        $banks = new kursValutes();
        if ($banks->load_list_curs())return $banks->list_curs[$valute];
        return isset($this->list_curs[$cur]) ? $this->list_curs[$cur] : 0;
    }
   //---------------------------------------------------------------------------     


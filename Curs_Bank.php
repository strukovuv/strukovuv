<?php
/*
 *   ��������� ����� �����. 
 *      ��������������, ��� ���������� ����� ����� ����� 
 *      ����������� �� ������ �������� � �� ������ �������������
 * -----------------
 * ��� ��������� ����� ���� ������� �������  get_curs($Valute) 
 *       * ����: 
 *       * $Valute - ������, �������� 'USD', ��� 'RUB'
 *       * �����:
 *       * -�������� �����
 * -----------------
 * 
 *      ������� ������� ��������� 
 *      ������� get_curs �������������� �������� ������ kursValutes
 *      �������� ������ ������
 *      1. ���� � ��������� ���� ���� �������� �����, �� ����� ������ ����� - ���� �� ����.
 *      2. ���� � ��� �������(�� ��������� 1 ���. ��. ��������� � ������),
 *      �� ���� ������ � ����, ���������� ������� ������(��. ���� - ��������� ���������� � ����). 
 *      ���� ������ ���������- �� �����- �������� ����� �� �������
 *      3. ���� ������ � ���� � ���� �� ���������, ��������, , �� ��������� ������ � ���������� �� ����������
 *      xml �����, ������������ ������� �����, ���������� ���, ���������� ������� ������.
 *      ������� - ��������  ���� �� ��l �����
 * 
 */




//����� ������� ����� ������
class kursValutes
{
    public $list_curs = [];// ������ ������ � ������� ���������(����)=��������
    
    public $time_cache = "3600"; // ����� ����� ���� � ��������. ��� ����� ��������� $list_curs
    
    public $file__cache  = __DIR__."/XML_daily.asp";//���� ����
  
    //����� ������ �������  ����� � ������ xml
    public $url = "http://www.cbr.ru/scripts/XML_daily.asp?date_req=";// . date('d.m.Y');
    
    //��������� ���������� � ����
     public $server_db= 'localhost';
     public $username=  'root';
     public $password = 'xxxxxx';
     public $database_name = 'db_XXX';    
     public $table_curs='kurs';//��� ������� � ���� database_name  � ������� �����
     
    
//--------------------------------------------------------------------------------------------------------------------------------------------------------------------------
    function __construct()
    {
        /* ����������� ������ kursValutes
         * 
         * 
         * ������������� ������ 
         * 
         * 
         * $time_cache = "3600"; // ����� ����� ���� � ��������. ��� ����� ��������� $list_curs
         * $file__cache  = __DIR__."/XML_daily.asp";//���� ����
         * $url = "http://www.cbr.ru/scripts/XML_daily.asp?date_req=";//����� ������ �������  ����� � ������ xml
         *
         * *��������� ���������� � ����
         *$server_db= 'localhost';
         *$username=  'root';
         *$password = 'xxxxxx';
         *$database_name = 'db_XXX';    
         *$table_curs='kurs';//��� ������� � ���� database_name  � ������� �����
        
         * ����: 
         * ���
         * �����: ���
         */
        
        /*
         * ������� �� ���������, �������  ����� ������� ��������� �������� 
         * 
         */
        
    }
    
//-------------------------------------------------------------------------------------------------------------------------------------------------------------------------- 
   public function load_list_cursFromHttp()
   {
       /* �������� ������� ������ (������=��������) �� ���������� ��������� ��.$url
        * 
        * ����: ���
        * 
        * �����:
        * true-�������� ������  $list_curs
        *         ��������� ���� ������
        *         �������� ���   
        * 
        * false-������
        */
        $xml = new DOMDocument();
        $url1 = $this->$url. date('d.m.Y');//���� �� ��� ����
        if (!$xml->load($url1))return false;
        
            $this->list_curs = []; 
            $root = $xml->documentElement;
            $items = $root->getElementsByTagName('Valute');
         
            if(count($items)==0)return false;
            
         //������� �  ���� ����� ������
         //� ���� �� ������  �
         
                 
         $db =     mysqli_connect($this->server_db,$this->username,$this->password,$this->database_name);   
         $this->check_table_curs($db);   
                 
             
           

         
            foreach ($items as $item)
            {
                $code = $item->getElementsByTagName('CharCode')->item(0)->nodeValue;
                $curs = $item->getElementsByTagName('Value')->item(0)->nodeValue;
                $this->list_curs[$code] = floatval(str_replace(',', '.', $curs));
                if($db)$this->update_table_curs($db,$code,$curs);    //�������� � ����
            }
            
            //�������� ������ � �����-����
            $doc = new DOMDocument();
            $doc->loadXML($this->list_curs);
            echo $doc->saveXML($this->file__cach);
            
 
            return true;
    }   
  //--------------------------------------------------------------------------------------------------------------------------------------------------------------------------              
    
public function update_table_curs($db,$code,$curs)    
  {
       /*���������� ��� ������� ������� � ���� curs
        * 
        * ����: $db,
        * $code- ��� ������
        * $curs- ����
        * �����: ���
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
       /*��������, ���� �� �������� ������.
        * ���� ��� �� ������� ��
        * ����: $db
        * �����: ���
        */
$query =  mysqli_query("SHOW TABLES FROM ".$yhis->database_name."  LIKE '".$this->table_curs."';");
        $result = mysql_fetch_array($query);
           if($result===false)
            {
            //������� �� �������- �������� ��
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
       /* �������� ������� ������ (������=��������) �� ���� ��. tbale_curs
        *
        * 
        * ����: $url
        * �����:
        * true-�������� ������  $list_curs
        * false-������
        */
       
       $db =     mysqli_connect($this->server_db,$this->username,$this->password,$this->database_name);
        if ($db == false)return false;//�� ������ ����������� � ��
       
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
         * �������� �� ���������� �������       $this->list_curs['������'=>'��������']
         * 
         * 1.���� ������ �������� �� ����� �� �������
         * ���� ������ �� ��������, �� ������ ��������������� �� ����, ����� ����� �������� ����� time_cache ���
         * 2. ���� ��� �� ��������, , �� ������ ��������������� �� ����
         * 3. ���� ��� � ������� ������ �� ���������,  �� ����������� �� xml �� ������� HTTP
         * ����:���
         * �����: false - ������, ������ �� �������� 
         * true- ������ �������� � ��������
         */

            if( !is_file($this->$file__cache) ||(filemtime($this->$file__cache)) < (time() - $this->$time_cache)) 
            {//� ���� ��� ������   � ������� $this->$time_cache ������

                // � ���� ���� ������?
                if(! $this->load_list_cursFromDB()) {
                                                                    //� ���� ������ ������,(�����-true), ��������  �������� ����� �� HTTP
                                                                      if(!$this->load_list_cursFromHttp()) return false; //������ ��������
                                                                    //true- ������ list_curs- ��������, ��� ���� �������� , ���� ���������
                                                                    }


          }
  return true;
    }

}
    
    
//---------------------------------------------------------------------------    

    function get_curs($Valute)
     {
       /*
        * ����: 
        * Valute - ������, �������� 'USD', ��� 'RUB'
        * �����:
        * -�������� �����
        */ 
        $curs=0;
        $banks = new kursValutes();
        if ($banks->load_list_curs())return $banks->list_curs[$valute];
        return isset($this->list_curs[$cur]) ? $this->list_curs[$cur] : 0;
    }
   //---------------------------------------------------------------------------     


<?php
namespace app\modules;

use php\lib\str;
use php\gui\framework\DataUtils;
use php\lib\arr;
use php\sql\SqlResult;
use php\sql\SqlStatement;
use php\gui\framework\AbstractModule;
use php\gui\framework\ScriptEvent; 
use php\lib\bin; 
use php\lib\char; 
use php\lib\fs; 
use php\lib\num; 
use php\lib\reflect; 
use php\io\Stream; 
use php\io\File; 
use php\io\IOException; 
use php\io\FileStream; 
use php\io\MemoryStream; 
use php\io\ResourceStream; 
use php\net\NetStream; 
use php\util\Flow; 
use php\util\Locale; 
use php\util\Regex; 
use php\util\Configuration; 
use php\time\Time; 
use php\time\TimeZone; 
use php\time\TimeFormat; 
use php\net\URL; 
use php\net\Socket; 
use php\net\SocketException; 
use php\net\ServerSocket; 
use php\net\Proxy; 
use php\lang\Thread; 
use php\lang\Environment; 
use php\lang\Process; 
use php\lang\System; 
use php\lang\ThreadGroup; 
use php\lang\ThreadPool; 
use php\format\JsonProcessor; 
use facade\Json; 
use php\gui\UXNode; 
use php\gui\event\UXEvent; 
use php\gui\UXApplication; 
use php\gui\animation\UXAnimationTimer; 
use php\gui\layout\UXHBox; 
use php\gui\layout\UXAnchorPane; 
use php\gui\UXClipboard; 
use php\gui\paint\UXColor; 
use php\gui\event\UXContextMenuEvent; 
use php\gui\UXDialog; 
use php\gui\text\UXFont; 
use php\gui\UXGeometry; 
use php\gui\UXImage; 
use php\gui\UXMedia; 
use php\gui\UXMenu; 
use php\gui\UXMenuItem; 
use php\gui\UXButton; 
use php\gui\UXTooltip; 
use php\gui\UXToggleButton; 
use php\gui\UXToggleGroup; 
use php\gui\UXImageView; 
use php\gui\UXImageArea; 
use php\gui\UXSlider; 
use php\gui\UXSpinner; 
use php\gui\layout\UXVBox; 
use php\gui\UXTitledPane; 
use php\gui\layout\UXPanel; 
use php\gui\layout\UXFlowPane; 
use php\gui\UXForm; 
use php\gui\UXWindow; 
use ide\bundle\std\UXAlert; 
use php\gui\UXContextMenu; 
use php\gui\UXControl; 
use php\gui\UXDirectoryChooser; 
use php\gui\UXFileChooser; 
use php\gui\UXFlatButton; 
use php\gui\UXHyperlink; 
use php\gui\UXList; 
use php\gui\UXListView; 
use php\gui\UXComboBox; 
use php\gui\UXChoiceBox; 
use php\gui\UXLabel; 
use php\gui\UXLabelEx; 
use php\gui\UXLabeled; 
use php\gui\UXListCell; 
use php\gui\UXMediaPlayer; 
use php\gui\UXParent; 
use php\gui\UXPopupWindow; 
use php\gui\UXPasswordField; 
use php\gui\UXProgressIndicator; 
use php\gui\UXProgressBar; 
use php\gui\UXTab; 
use php\gui\UXTabPane; 
use php\gui\UXTreeView; 
use php\gui\UXTrayNotification; 
use php\gui\UXWebEngine; 
use php\gui\UXWebView; 
use php\gui\UXCell; 
use php\gui\UXColorPicker; 
use php\gui\UXCanvas; 
use php\gui\layout\UXStackPane; 
use php\gui\layout\UXPane; 
use php\gui\layout\UXScrollPane; 
use php\gui\event\UXKeyEvent; 
use php\gui\event\UXDragEvent; 
use php\gui\event\UXMouseEvent; 
use php\gui\event\UXWebEvent; 
use php\gui\event\UXWindowEvent; 
use php\gui\framework\AbstractForm; 
use action\Animation; 
use action\Element; 
use action\Geometry; 
use action\Media; 
use action\Score; 
use ide\Logger; 


/**
 * Модуль для работы с базой данных.
 */
class DatabaseModule extends AbstractModule
{
    /**
     * @event action 
     */
    function doAction(ScriptEvent $event = null)
    {   
    
    /* 
        // создаем таблицы данных, если их еще нет.
        $this->database->query(
            'create table if not exists users (
            id integer primary key, 
            fio text, 
            groop integer
            )'
            )->update();
            
        // ----
        
        $this->database->query(
            'create table if not exists days (
            id integer primary key, 
            money integer, 
            date text,
            debt integer
            )'
            )->update();
       */
    }
    
    
    // --------------------------------------------------
    
    
        function getSearch()
    {
       $val = $this->form('MainForm')->search_edit->text;
       return $this->database->query("select * from users where fio LIKE '%".$val."%' OR search LIKE '%". $this->translit($val) ."%'"); 
    }
    
    function translit($s) 
    {        
        $s = strtolower($s);
        $replaces = ['а'=>'a','б'=>'b','в'=>'v','г'=>'g','д'=>'d','е'=>'e','ё'=>'e','ж'=>'j',
'з'=>'z','и'=>'i','й'=>'y','к'=>'k','л'=>'l','м'=>'m','н'=>'n','о'=>'o',
'п'=>'p','р'=>'r','с'=>'s','т'=>'t','у'=>'u','ф'=>'f','х'=>'h','ц'=>'c',
'ч'=>'ch','ш'=>'sh','щ'=>'shch','ы'=>'y','э'=>'e','ю'=>'yu','я'=>'ya',
'ъ'=>'','ь'=>'',
'А'=>'a','Б'=>'b','В'=>'v','Г'=>'g','Д'=>'d','Е'=>'e','Ё'=>'e','Ж'=>'j',
'З'=>'z','И'=>'i','Й'=>'y','К'=>'k','Л'=>'l','М'=>'m','Н'=>'n','О'=>'o',
'П'=>'p','Р'=>'r','С'=>'s','Т'=>'t','У'=>'u','Ф'=>'f','Х'=>'h','Й'=>'c',
'Ч'=>'ch','Ш'=>'sh','Щ'=>'shch','Ы'=>'y','Э'=>'e','Ю'=>'yu','Я'=>'ya',
'Ъ'=>'','Ь'=>''];
            
        foreach ($replaces as $what => $to) {
            $s = str::replace($s, $what, $to);
        }
     
      return $s;
    }
    
    // --------------------------------------------------
    
    
    
    /**
     * Возвращает список пользователей.
     * @return SqlStatement
     */
    function getUsers()
    {
        return $this->database->query('select * from users order by id desc');
    }
    
    /**
     * Возвращает список дней.
     * @return SqlStatement
     */
    
    function getDays()
    {
        return $this->database->query('select * from days order by id desc');
    }
    
    function getDaysAndMoney()
    {
        return $this->database->query('select * from days order by id desc');
    }
    
    /**
     * Возвращает кол-во учащихся
     * @return int
     */
    function getUserCount()
    {
        return (int) $this->database->query('select count(*) from users')->fetch()->get('count(*)');
    }
    
      /**
       * Возвращает кол-во дней
     * @return int
     */
    function getDayCount()
    {
        return (int) $this->database->query('select count(*) from days')->fetch()->get('count(*)');
    }
    
     /**
       * Возвращает кол-во дней для одного всего
     * @return int
     */
    function getDayCountPersonal($user)
    {
        return (int) $this->database->query('select count(*) from days where user = ?', [$user])->fetch()->get('count(*)');
    }
    
    // Месяц
    function getDayCountPersonalMonth($user, $month)
    {
        return (int) $this->database->query('select count(*) from days where user = ? and month = ?', [$user, $month])->fetch()->get('count(*)');
    }
    
    // Год
    function getDayCountPersonalYear($user, $year)
    {
        return (int) $this->database->query('select count(*) from days where user = ? and year = ?', [$user, $year])->fetch()->get('count(*)');
    }

    /**
     * Запрос пользователя по id
     * @return SqlResult|null
     */
    function getUser($id)
    {
        return arr::first($this->database->query('select * from users where id = ?', [$id]));
    }
    
    /**
     * Запрос дня по id
     * @return SqlResult|null
     */
    function getDay($id, array $data)
    {
        return arr::first($this->database->query('select * from days where user = ? and date = ?', [$id, $data['date']]));
    }
    
    /**
     * Добавляет нового пользователя и возвращает его id.
     * @return int
     */
    function addUser(array $data)
    {   
        $statement = $this->database->query('insert into users values(null, ?, ?, ?)', [$data['fio'], $data['groop'], $data['search']]);
        $statement->update();
        
        return $statement->getLastInsertId();
    }
    
    /**
     * Добавляет новый день и возвращает его id.
     * @return int
     */
    function addDay(array $data)
    {   
        $statement = $this->database->query('insert into days values(null, ?, ?, ?, ?, ?, ?, ?, ?)', 
        [
        $data['user'], 
        $data['money'], 
        $data['date'], 
        $data['visit'], 
        $data['debt'], 
        $data['day'], 
        $data['month'], 
        $data['year']
        ]);
        $statement->update();
        
        return $statement->getLastInsertId();
    }
    
    /**
     * Сохраняет пользователя по id.
     */
    function saveUser($id, array $data)
    {
        $this->database->query('update users set fio = ?, groop = ?, search = ? where id = ?', [
            $data['fio'], $data['groop'], $data['search'],
            $id
        ])->update();
    }
    
    /**
     * Сохраняет день по id.
     */
    function saveDay($id, array $data)
    {
        $this->database->query('update days set user = ?, money = ?, date = ?, visit = ?, debt = ?, day = ?, month = ?, year = ? where id = ?', [
            $data['user'], $data['money'], $data['date'], $data['visit'], $data['debt'], $data['day'], $data['month'], $data['year'],
            $id
        ])->update();
    }
    
    /**
     * Удаляет пользователя по id.
     * @return int
     */
    function deleteUser($id)
    {
        return $this->database->query('delete from users where id = ?', [$id])->update();
    }
    
    function deleteDay($id)
    {
        return $this->database->query('delete from days where id = ?', [$id])->update();
    }
    
    /**
     * Удаляет всех пользователей и возвращает количество удаленных пользователей.
     * @return int 
     */
    function deleteAllUsers()
    {
        return $this->database->query('delete from users where 1 = 1')->update();
    }
}

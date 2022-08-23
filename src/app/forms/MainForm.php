<?php
namespace app\forms;

use php\gui\UXTrayNotification;
use php\time\TimeFormat;
use php\gui\UXDialog;
use php\sql\SqlStatement;
use php\sql\SqlResult;
use php\gui\framework\AbstractForm;
use php\gui\event\UXEvent; 
use php\gui\event\UXMouseEvent; 
use php\time\Time;
use php\gui\event\UXKeyEvent; 
use php\gui\event\UXWindowEvent; 


class MainForm extends AbstractForm
{

    public $id;
    public $user;
    public $money;
    public $date;
    public $visit;
    public $debt;
    
    public $day;
    public $month;
    public $year;

    /**
     * @event showing 
     */
    function doShowing(UXEvent $event = null)
    {    
        $this->reloadUsers();
                
        $GLOBALS['money'] = $this->ini->get('money', 'setting', 'none');
    }

    /**
     * @event addButton.action 
     */
    function doAddButtonAction(UXEvent $event = null)
    {    
        $userForm = app()->getNewForm('UserForm');
        $userForm->showAndWait();
        $this->reloadUsers();
        $this->table->selectedIndex = 0;
    }

    /**
     * @event editButton.action 
     */
    function doEditButtonAction(UXEvent $event = null)
    {    
        $userForm = app()->getNewForm('UserForm');
        $userForm->id = $this->table->selectedItem['id'];
        
        $userForm->showAndWait();
        
        $index = $this->table->selectedIndex;
        $this->reloadUsers();
        $this->table->selectedIndex = $index;
    }
    
    /**
     * @event deleteButton.action 
     */
    function doDeleteButtonAction(UXEvent $event = null)
    {    
        if (UXDialog::confirm('Вы уверены, что хотите удалить ученика?')) {
            $this->deleteUser($this->table->selectedItem['id']);
            
            $index = $this->table->selectedIndex;
            
            $this->reloadUsers();
            
            $this->table->selectedIndex = $index;
            
            if ($this->table->selectedIndex == -1) {
                $this->table->selectedIndex = $index - 1;
            }
        }
    }

    /**
     * @event table.click 
     */
    function doTableClick(UXMouseEvent $event = null)
    {    
        $this->deleteButton->enabled = $this->table->selectedIndex != -1;
        
        // alert($this->table->selectedIndex != -1);
        // alert($this->table->selectedItem['fio']);
        
        $this->editButton->enabled = $this->deleteButton->enabled;
        
        // Клик по строке таблицы
        // $this->label_name->text = $this->table->selectedItem['fio'];
        
        // $user = $this->table->selectedIndex != -1;
        $user = $this->table->selectedItem['id'];
        
        // alert($this->table->selectedItem['id']);
        
        $this->tablesClear();
        $this->getWeekday($user);
        $this->getVisitOrMoney($user);
        
        $this->user_id->text = $user;
    }

    /**
     * @event deleteAllButton.action 
     */
    function doDeleteAllButtonAction(UXEvent $event = null)
    {    
        if (UXDialog::confirm('Вы уверены, что хотите удалить всех учеников?')) {
            $this->deleteAllUsers();
            $this->reloadUsers();
        }
    }

    /**
     * @event table.mouseDown-2x 
     */
    function doTableMouseDown2x(UXMouseEvent $event = null)
    {    
        $this->doEditButtonAction();
    }

    /**
     * @event search_edit.keyUp 
     */
    function doSearch_editKeyUp(UXKeyEvent $e = null)
    {
        $this->table->items->clear();
        
        $users = $this->getSearch();
        
        $this->addUsersToTable($users);
    }

    /**
     * @event clear.action 
     */
    function doClearAction(UXEvent $e = null)
    {
        $this->search_edit->clear();
        
        $this->reloadUsers();
    }

    /**
     * @event show 
     */
    function doShow(UXWindowEvent $e = null)
    {    
       $now = Time::now(); // получаем текущую дату
       $now_date = $now->toString('dd.MM.yyyy');
       $this->dateEdit_picker->value = $now_date;
       $this->dateEdit_transfer->value = $now_date;
       
       $this->Copyright->text = 'Copyright © '. $now->year() .' Переверзев С.Н. Все права защищены.';
       $this->label_price_money->text = 'Стоимость одного занятия : '. $GLOBALS['money'] . ' руб.';
       
       $this->numberField_charge_custom->step = $GLOBALS['money'];
       
       $this->dateEdit_picker->editor->observer('text')->addListener(function () {
           $user = $this->table->selectedItem['id'];
           
           if ($user !== false) {
               $this->tablesClear();
               $this->getWeekday($user);
               $this->getVisitOrMoney($user);
           }
       });
    }

    /**
     * @event button.action 
     */
    function doButtonAction(UXEvent $e = null)
    {    
        $now = Time::now(); // получаем текущую дату
        $now_date = $now->toString('dd.MM.yyyy');
        $this->dateEdit_picker->value = $now_date;
    }

    /**
     * @event keyDown-Shift+F1 
     */
    function doKeyDownShiftF1(UXKeyEvent $e = null)
    {    
         $this->deleteAllButton->visible = true;
    }

    /**
     * @event keyDown-Shift+F2 
     */
    function doKeyDownShiftF2(UXKeyEvent $e = null)
    {    
        $this->deleteAllButton->visible = false;
    }

    /**
     * @event button_update.action 
     */
    function doButton_updateAction(UXEvent $e = null)
    {   
         // $user = $this->table->selectedIndex != -1;
         $user = $this->table->selectedItem['id'];
         $this->tablesClear();
         $this->getWeekday($user);
         $this->getVisitOrMoney($user);
    }

    /**
     * @event button_get_full_state.action 
     */
    function doButton_get_full_stateAction(UXEvent $e = null)
    {    
        $DaysAndMoney = $this->getDaysAndMoney(); 
        $this->getFullDaysAndMoney($DaysAndMoney);
    }

    /**
     * @event button_charge_custom.action 
     */
    function doButton_charge_customAction(UXEvent $e = null)
    {   
        $user = $this->table->selectedItem['id'];
        
        // Если необходимо проверерять долги перед оплатой
        // $this->getPayDebt($user);
        
        // Провести оплату
        $this->getPayMoney($user);
    }

    /**
     * @event button_check_debt.action 
     */
    function doButton_check_debtAction(UXEvent $e = null)
    {    
        $user = $this->table->selectedItem['id'];
        $this->getPayDebt($user);
    }

    /**
     * @event numberField_day.click 
     */
    function doNumberField_dayClick(UXMouseEvent $e = null)
    {    
        $this->numberField_charge_custom->value = 0;
    }

    /**
     * @event numberField_charge_custom.click 
     */
    function doNumberField_charge_customClick(UXMouseEvent $e = null)
    {    
        $this->numberField_day->value = 0;
    }

    /**
     * @event button_visit.action 
     */
    function doButton_visitAction(UXEvent $e = null)
    {    
        $user = $this->table->selectedItem['id'];
        $this->getVisit($user);
    }

    /**
     * @event button_charge_del.action 
     */
    function doButton_charge_delAction(UXEvent $e = null)
    {   
        $day_id = $this->day_id->text;
        if ($day_id !== '') {
        $fio = $this->table->selectedItem['fio'];
        $date = $this->dateEdit_picker->value;
        
            if (UXDialog::confirm('Удалить оплату '. $fio .' за '. $date .' ?') ) {
                $this->deleteDay($day_id);
                $this->day_id->text = '';
                
                $user = $this->table->selectedItem['id'];
                $this->tablesClear();
                $this->getWeekday($user);
                $this->getVisitOrMoney($user);
            }
        }
    }

    /**
     * @event button_transfer.action 
     */
    function doButton_transferAction(UXEvent $e = null)
    {    
        $user = $this->table->selectedItem['id'];
        $this->getTransfer($user);
    }
    
    /**
     * Добавляет пользователя из базы в ui таблицу.
     */
    public function addUserToTable(SqlResult $record, $key)
    {
        $id = $record->toArray()['id'];
        $fio = $record->toArray()['fio'];
        $groop = $record->toArray()['groop'];
        $search = $record->toArray()['search'];
        $num = $record->toArray()['num'] = $key;
        
        $data = [
        "id" => $id,
        "fio" => $fio,
        "groop" => $groop,
        "search" => $search,
        "num" => $num,
        ];
        
        $this->table->items->add($data);
    }
    
    /**
     * Добавляет сразу всех пользователей в таблицу.
     */
    public function addUsersToTable(SqlStatement $records)
    {
        foreach ($records as $key => $record) {
            $this->addUserToTable($record, $key + 1);
        }
    }
    
    
    /**
     *  Складываем все посещения и деньги в БД #2
     */
    public function getFullDayAndMoney(SqlResult $record)
    {
        $visit = $record->toArray()['visit'];
        $money = $record->toArray()['money'];
        
        $oldMoney = $this->numberField_full_state_money->text;
        $this->numberField_full_state_money->text = $oldMoney + $money;
        
        $oldDay = $this->numberField_full_state_days->text;
        $this->numberField_full_state_days->text = $oldDay + $visit;
    }
    
    /**
     * Складываем все посещения и деньги в БД #1
     */
    public function getFullDaysAndMoney(SqlStatement $records)
    {
        foreach ($records as $record) {
            $this->getFullDayAndMoney($record);
        }
    }
    
    /**
     * Перезагружает пользователей в таблицу.
     */
    public function reloadUsers()
    {
        $this->table->items->clear();
        
        $users = $this->getUsers();
                
        $this->addUsersToTable($users);
        
        $count = $this->getUserCount();
        $this->countLabel->text = $count . " - Количество учеников";
        
        $countDay = $this->getDayCount();
        $this->countDaysLabel->text = $countDay . " - Количество занятий";
        
        $this->deleteAllButton->enabled = $count > 0;
        
        if ($count == 0) {
            $this->deleteButton->enabled = false;
            $this->editButton->enabled = false;
        }
    }
    
    // Расставить дни недели в таблицах
    public function getWeekday ($user) {
    
        $varDate = $this->dateEdit_picker->value;
        
        $varDate = explode('.', $varDate);
        
        $day = $varDate[0];
        $mount = $varDate[1];
        $year = $varDate[2];
        
        $now = Time::now();
        $format = new TimeFormat('d.MM.yyyy');
        $str_Key = 'day_';
        
        $arr_15 = array();
        
        // $arr_mon = ['', 'ВС', 'ПН' , 'ВТ' , 'СР' , 'ЧТ' , 'ПТ' , 'СБ'];
        $arr_mon = ['', 'SUN', 'MON' , 'TUES' , 'WED' , 'THUR' , 'FRI' , 'SAT'];
        
        for($i=1; $i <= 31; $i++){
                
                $date = $format->parse(''.$i.'.'.$mount.'.'.$year.'');
                
                $arr_15[$str_Key.$i] = $arr_mon[$date->dayOfWeek()];
                
                $dayOfMonth = $date->dayOfMonth();
                if($dayOfMonth == $i) { 
                    $arr_15[$str_Key.$i] = $arr_mon[$date->dayOfWeek()];
                } else {
                    $arr_15[$str_Key.$i] = $arr_mon[0]; 
                } 
        }
        
        // Вывод в таблицу
        $arr_15['info'] = 'WEEKDAY';
        $this->table_1_15->items->add($arr_15);
        $this->table_16_31->items->add($arr_15);
    }

    // Очистка таблиц
    public function tablesClear () {
        $this->table_1_15->items->clear();
        $this->table_16_31->items->clear();
    }
    
    // Загрузка данных в таблицы календаря оплата дни и долги
    public function getVisitOrMoney ($user) {
        
        $now = Time::now();
        $format = new TimeFormat('d.MM.yyyy');
        $str_Key = 'day_';
        
        $arr_dayMoney = array();
        $arr_dayVisit = array();
        $arr_dayDebt = array();
        
        $paid_per_month = 0;
        $debt_days = 0;
        $paid_days = 0;
        
        $date  = $this->dateEdit_picker->value;
        
        // Запрос 
        $dataMain = ['date' => $date ];
        $varDayMain = $this->getDay($user, $dataMain);
        
        if ($varDayMain !== null) {
            $varIdMain = (int) $varDayMain->get('id');
            $this->button_charge_del->enabled = true;
        } else {
            $varIdMain = '';
            $this->button_charge_del->enabled = false;       
        }
        
        $date = explode('.', $date);
        
        $varDayCel = (int) $date[0];
        $varMonthCel = (int) $date[1];
        $varYearCel = (int) $date[2];
        
        for($i=1; $i <= 31; $i++){
        
        $data = [
            'date' => $i.'.'.$date[1].'.'.$date[2]
        ];
        
         $varDay = $this->getDay($user, $data);
         
         
         if ($varDay !== null) {
             
             $varId = (int) $varDay->get('id');
             
             $varUser = (int) $varDay->get('user');
             $varMoney = (int) $varDay->get('money');
             $varDate = $varDay->get('date');
             $varDebt = (int) $varDay->get('debt');
             $varVisit = (int) $varDay->get('visit');
                          
             $paid_per_month = $paid_per_month + $varMoney;
             
             if ($varDebt == 0) {
              $debt_days++;   
             }
             
             $paid_days = $paid_days + $varVisit;
             
             // $varDayDB = (int) $varDay->get('day');
             // $varMonthDB = (int) $varDay->get('month');
             // $varYearDB = (int) $varDay->get('year');
             
            if ($varVisit !== 0) {
                $varVisit = 'VISIT';
            } else {
                $varVisit = '';
            }
            
            
            if ($varDebt == 0) { 
                $varDebt = 'DEBT';
                $varAvans = '';
            } 
            if ($varDebt == 1) { 
                $varDebt = 'PAYD';
                $varAvans = $data['date'];
            }
            
            /*
            if ($varDebt == 2) { 
                $varDebt = 'AVANS';
                // $varAvans = $data['date'];
            }
            */
            
            if ($varDayCel == $i) {
                
                if ($varVisit == 'VISIT') {
                    $this->button_visit->enabled = false;
                } else {
                    $this->button_visit->enabled = true;
                }
            }
            
             $arr_dayMoney[$str_Key.$i] = $varMoney;
             
             $arr_dayVisit[$str_Key.$i] = $varVisit;
             $arr_dayDebt[$str_Key.$i] = $varDebt;
             
         } else {
                      
             if ($varDayCel == $i) {
                 $this->button_visit->enabled = true;
             }             
             
         }
         
        }
        
        
        $arr_dayMoney['info'] = 'MONEY';
        $this->table_1_15->items->add($arr_dayMoney);
        $this->table_16_31->items->add($arr_dayMoney);
        
        $arr_dayVisit['info'] = 'VISIT';
        $this->table_1_15->items->add($arr_dayVisit);
        $this->table_16_31->items->add($arr_dayVisit);
        
        $arr_dayDebt['info'] = 'DEBT';
        $this->table_1_15->items->add($arr_dayDebt);
        $this->table_16_31->items->add($arr_dayDebt);
        
        
        
        // Вывод в панели ---------------------------------
        $this->label_name->text = $this->table->selectedItem['fio'];
        $this->day_id->text = $varIdMain; 
        
        $months = ['', 'Январь' , 'Февраль' , 'Март' , 'Апрель' , 'Май' , 'Июнь' , 'Июль' , 'Август' , 'Сентябрь' , 'Октябрь' , 'Ноябрь' , 'Декабрь'];
        $this->label_visit_days_mount->text = 'Посещено дней за ' . $months[$varMonthCel] . ' : ' . $this->getDayCountPersonalMonth($user, $varMonthCel);
        $this->label_visit_days_year->text = 'Посещено дней за ' . $varYearCel. ' год : ' . $this->getDayCountPersonalYear($user, $varYearCel);
        
        $this->label_paid_per_month->text = 'Оплачено за ' . $months[$varMonthCel] . ' : '. $paid_per_month . ' руб.';
        $this->label_paid_days->text = 'Посещено занятий за месяц : '. $paid_days;
        $this->label_debt_days->textColor = '#333333';
        
        if ($debt_days !== 0) {
            $this->label_debt_days->textColor = '#b31a1a';
        }
        
        $this->label_debt_days->text = 'Оплатить дней : '. $debt_days;
        $this->label_pay_to->text = 'Оплачено дней до : '. $varAvans;
        $this->label_day_date->text = $this->dateEdit_picker->value;

        $this->button_charge_custom->enabled = true;
        $this->numberField_charge_custom->enabled = true;
        $this->numberField_day->enabled = true;
        
        $this->button_check_debt->enabled = true;
        // $this->button_visit->enabled = true;
        
        $this->button_update->enabled = true;
        

        
        // $this->notice_SUCCESS('Готово', 'Информация загружена');
    }
    
    
    // Искать долги
    public function getPayDebt ($user) {
        $date  = $this->dateEdit_picker->value;
        $date = explode('.', $date);
        
        $varDayCel = (int) $date[0];
        $varMonthCel = (int) $date[1];
        $varYearCel = (int) $date[2];
        
        for($i=1; $i <= 31; $i++) {
            
            $data = [
            'date' => $i.'.'.$varMonthCel.'.'.$varYearCel
            ];
        
         $varDay = $this->getDay($user, $data);
         
         if ($varDay !== null) {
             
             $varId = (int) $varDay->get('id');
             
             $varUser = (int) $varDay->get('user');
             $varMoney = (int) $varDay->get('money');
             $varDate = $varDay->get('date');
             $varVisit = (int) $varDay->get('visit');
             $varDebt = (int) $varDay->get('debt');
             
             
             // $paid_per_month = $paid_per_month + $varMoney;
             // $paid_days = $paid_days + $varVisit;
             
             $varDayDB = (int) $varDay->get('day');
             $varMonthDB = (int) $varDay->get('month');
             $varYearDB = (int) $varDay->get('year');
             
            
            if ($varDebt == 0) { 
            
                // alert('Долг ' . $varDate);
                if (UXDialog::confirm('У ученика имеется задолженность ' . $varDate . '                     Оплатить?') ) {
                    
                // $this->numberField_charge_custom->value = $GLOBALS['money'];
                // $money = $this->numberField_charge_custom->value;
                $money = $GLOBALS['money'];             
                $data = [
                    'user' => $varUser,
                    'money' => $money, // Нужно получить
                    'date' => $varDate,
                    'visit' => $varVisit,
                    'debt' => 1,
                    'day' => $varDayDB,
                    'month' => $varMonthDB,
                    'year' => $varYearDB
                    ];
                    
                     $this->saveDay($varId, $data);
                     
                     $this->tablesClear();
                     $this->getWeekday($user);
                     $this->getVisitOrMoney($user);
                     $this->notice_SUCCESS('Готово', 'Долг за '. $varDate . ' погашен');
                }
                
            }
                 
            if ($varDebt == 1) {}
            // if ($varDebt == 2) {}
            
            if ($varDayCel == $i) {}
            
         } else {}
         
       }
    }
    
    // Провести оплату
    public function getPayMoney ($user) {
    
        $date  = $this->dateEdit_picker->value;
        
        // $data = ['date' => $date ];
        // $varDay = $this->getDay($user, $data);
        
        // if ($varDay == null) {}

        // $this->numberField_charge_custom->value = $GLOBALS['money'];
        // $money = $this->numberField_charge_custom->value;
                
        $numberField_day = $this->numberField_day->value;
        
        $numberField_charge_custom = $this->numberField_charge_custom->value;
            
        $format = new TimeFormat('dd.MM.yyyy');
        $date = $format->parse($date);
            
        // Левое поле
        if ($numberField_charge_custom > 0) {
            
            $this->numberField_charge_custom_count->value = $this->numberField_charge_custom->value;
                
                $count = 0;
                
                for($i = 0; $i < 1000; $i++) {
                
                $numberField_charge_custom_count = $this->numberField_charge_custom_count->value;
                
                    if ($numberField_charge_custom_count !== 0) {
                        $this->numberField_charge_custom_count->value = $numberField_charge_custom_count - $GLOBALS['money'];
                        $count++;
                    }
                }
                
                // alert($count);
                
                for($i = 0; $i <= $count-1; $i++) {
            
                    $newDate = $date->add(['day' => $i]);
                    $data = ['date' => $newDate->toString('dd').'.'.$newDate->toString('MM').'.'.$newDate->toString('yyyy') ];
                    $varDay = $this->getDay($user, $data);
                              
               if ($varDay == null) {
               
                $data = [
                    'user' => $user,
                    'money' => $GLOBALS['money'],
                    'date' => $newDate->toString('dd').'.'.$newDate->toString('MM').'.'.$newDate->toString('yyyy'),
                    'visit' => 1,
                    'debt' => 1,
                    'day' => $newDate->toString('dd'),
                    'month' => $newDate->toString('MM'),
                    'year' => $newDate->toString('yyyy')
                    ];
                    
                 if (UXDialog::confirm('Произвести оплату за '.$newDate->toString('dd.MM.yyyy').' ?') ) {
                    // alert('right');
                    // pre($data);
                    $this->addDay($data);
                 } else { break; }
               
                 // alert('null');
               } else {
               
                   $varId = (int) $varDay->get('id');
             
                   $varUser = (int) $varDay->get('user');
                   $varMoney = (int) $varDay->get('money');
                   $varDate = $varDay->get('date');
                   $varVisit = (int) $varDay->get('visit');
                   $varDebt = (int) $varDay->get('debt');

                   $varDayDB = (int) $varDay->get('day');
                   $varMonthDB = (int) $varDay->get('month');
                   $varYearDB = (int) $varDay->get('year');
             
                   $money = $GLOBALS['money'];
               
                    $data = [
                        'user' => $varUser,
                        'money' => $money, // Нужно получить
                        'date' => $varDate,
                        'visit' => $varVisit,
                        'debt' => 1,
                        'day' => $varDayDB,
                        'month' => $varMonthDB,
                        'year' => $varYearDB
                    ];
                    $this->saveDay($varId, $data);
                    
                    // pre($data);
                    // alert('not null');
                
               }
               
                 // alert('цыфра');    
        }
    }
        
        
        // Право поле    
        if ($numberField_day > 0) {
                
            for($i = 0; $i <= $numberField_day-1; $i++) {
            
               $newDate = $date->add(['day' => $i]);
               $data = ['date' => $newDate->toString('dd').'.'.$newDate->toString('MM').'.'.$newDate->toString('yyyy') ];
               $varDay = $this->getDay($user, $data);
                    
                 if ($i >= 1) {
                   
                   $data = [
                    'user' => $user,
                    'money' => $GLOBALS['money'],
                    'date' => $newDate->toString('dd').'.'.$newDate->toString('MM').'.'.$newDate->toString('yyyy'),
                    'visit' => 0,
                    'debt' => 1,
                    'day' => $newDate->toString('dd'),
                    'month' => $newDate->toString('MM'),
                    'year' => $newDate->toString('yyyy')
                    ];
                    
               } else {
               
                   $data = [
                    'user' => $user,
                    'money' => $GLOBALS['money'],
                    'date' => $newDate->toString('dd').'.'.$newDate->toString('MM').'.'.$newDate->toString('yyyy'),
                    'visit' => 1,
                    'debt' => 1,
                    'day' => $newDate->toString('dd'),
                    'month' => $newDate->toString('MM'),
                    'year' => $newDate->toString('yyyy')
                    ];
                    
               }
                              
               if ($varDay == null) {
                   
                 if (UXDialog::confirm('Произвести оплату за '.$newDate->toString('dd.MM.yyyy').' ?') ) {
                    // alert('right');
                    // pre($data);
                    $this->addDay($data);
                 } else { break; }
               
                 // alert('null');
                 
               } else {
               
                   $varId = (int) $varDay->get('id');
             
                   $varUser = (int) $varDay->get('user');
                   $varMoney = (int) $varDay->get('money');
                   $varDate = $varDay->get('date');
                   $varVisit = (int) $varDay->get('visit');
                   $varDebt = (int) $varDay->get('debt');

                   $varDayDB = (int) $varDay->get('day');
                   $varMonthDB = (int) $varDay->get('month');
                   $varYearDB = (int) $varDay->get('year');
             
                   $money = $GLOBALS['money'];
               
                    $data = [
                        'user' => $varUser,
                        'money' => $money, // Нужно получить
                        'date' => $varDate,
                        'visit' => $varVisit,
                        'debt' => 1,
                        'day' => $varDayDB,
                        'month' => $varMonthDB,
                        'year' => $varYearDB
                    ];
                    $this->saveDay($varId, $data);
                    
                    // pre($data);
                    // alert('not null');
               }
            }
            
            // alert('день');     
        }
            
            // 
        if ($numberField_charge_custom == 0 && $numberField_day == 0) {
            alert('Для оплаты нужно указать сумму или количество дней');
        } 

                
        $this->tablesClear();
        $this->getWeekday($user);
        $this->getVisitOrMoney($user);
        // $this->notice_SUCCESS('Готово', 'Оплата за '. $varDate .' добавлена');
        
        $this->numberField_day->value = 0;
        $this->numberField_charge_custom->value = 0;
        $this->numberField_charge_custom_count->value = 0;
        
    }
    
    
    public function getVisit ($user) {
        $date  = $this->dateEdit_picker->value;
        
        $data = ['date' => $date ];
        $varDay = $this->getDay($user, $data);
        
            if ($varDay == null) {
                if (UXDialog::confirm('Поставить посещение занятия ' . $date . ' без оплаты?') ) {
                  
                    $date = explode('.', $date);
            
                    $varDayCel = (int) $date[0];
                    $varMonthCel = (int) $date[1];
                    $varYearCel = (int) $date[2];

                    $data = [
                        'user' => $user,
                        'money' => 0,
                        'date' => $varDayCel . '.' . $varMonthCel . '.' . $varYearCel,
                        'visit' => 1,
                        'debt' => 0,
                        'day' => $varDayCel,
                        'month' => $varMonthCel,
                        'year' => $varYearCel
                    ];
            
                    $this->addDay($data);
                    // pre($data);
                    // alert('нет в БД');
               }
           } else {
                 
                 // alert('Ошибка!!! Запись есть в БД!');
                
                   $varId = (int) $varDay->get('id');
             
                   $varUser = (int) $varDay->get('user');
                   $varMoney = (int) $varDay->get('money');
                   $varDate = $varDay->get('date');
                   $varVisit = (int) $varDay->get('visit');
                   $varDebt = (int) $varDay->get('debt');

                   $varDayDB = (int) $varDay->get('day');
                   $varMonthDB = (int) $varDay->get('month');
                   $varYearDB = (int) $varDay->get('year');
             
                   $money = $GLOBALS['money'];
               
                    $data = [
                        'user' => $varUser,
                        'money' => $money, // Нужно получить
                        'date' => $varDate,
                        'visit' => 1,
                        'debt' => 1,
                        'day' => $varDayDB,
                        'month' => $varMonthDB,
                        'year' => $varYearDB
                    ];
                    $this->saveDay($varId, $data);
                    
                    // pre($data);
                    
           }
       $this->tablesClear();
       $this->getWeekday($user);
       $this->getVisitOrMoney($user);
    }
    
    
    public function getTransfer ($user) {
        $date  = $this->dateEdit_picker->value;
        $date_transfer  = $this->dateEdit_transfer->value;
        
        $now = Time::now(); // получаем текущую дату
        $now_date = $now->toString('dd.MM.yyyy');
        
        if ($date_transfer == $date) {
        
            $format = new TimeFormat('dd.MM.yyyy');
            $date = $format->parse($date);
            $newDate = $date->add(['day' => 1]);
            $data = ['date' => $newDate->toString('dd').'.'.$newDate->toString('MM').'.'.$newDate->toString('yyyy') ];
            
            $varDay = $this->getDay($user, $data);
            
        
            if ($varDay == null) {
                if (UXDialog::confirm('Перенести на ' . $newDate->toString('dd').'.'.$newDate->toString('MM').'.'.$newDate->toString('yyyy') . ' ?') ) {
                  
                   $data = ['date' => $now_date];
                   
                   $varDay = $this->getDay($user, $data);
                   
                   $varId = (int) $varDay->get('id');
             
                   $varUser = (int) $varDay->get('user');
                   $varMoney = (int) $varDay->get('money');
                   $varDate = $varDay->get('date');
                   $varVisit = (int) $varDay->get('visit');
                   $varDebt = (int) $varDay->get('debt');

                   $varDayDB = (int) $varDay->get('day');
                   $varMonthDB = (int) $varDay->get('month');
                   $varYearDB = (int) $varDay->get('year');
             
                   $money = $GLOBALS['money'];
               
                    $data = [
                        'user' => $varUser,
                        'money' => $money, // Нужно получить
                        'date' => $newDate->toString('dd').'.'.$newDate->toString('MM').'.'.$newDate->toString('yyyy'),
                        'visit' => $varVisit,
                        'debt' => $varDebt,
                        'day' => $varDayDB,
                        'month' => $varMonthDB,
                        'year' => $varYearDB
                    ];
                    $this->addDay($data);
                    // pre($data);
                   $day_id = $this->day_id->text;
                   if ($day_id !== '') {
                       $this->deleteDay($day_id);
                       $this->day_id->text = '';
                       // alert('Удалить : ' . $day_id);
                   }
               }
           } else {
               // for($i = 1; $i <= 2; $i++) {
                   // alert('цыкл');
               // }
               
               alert('Не чего переносить');
           }
           
        } else { 
            // alert('даты отличаются'); 
        
            if (UXDialog::confirm('Перенести на ' . $date_transfer . ' ?') ) {
                  
                   $data = ['date' => $date_transfer];
                   
                   $varDay = $this->getDay($user, $data);
                   
                   $varId = (int) $varDay->get('id');
             
                   $varUser = (int) $varDay->get('user');
                   $varMoney = (int) $varDay->get('money');
                   $varDate = $varDay->get('date');
                   $varVisit = (int) $varDay->get('visit');
                   $varDebt = (int) $varDay->get('debt');

                   $varDayDB = (int) $varDay->get('day');
                   $varMonthDB = (int) $varDay->get('month');
                   $varYearDB = (int) $varDay->get('year');
             
                   $money = $GLOBALS['money'];
               
                    $data = [
                        'user' => $varUser,
                        'money' => $money, // Нужно получить
                        'date' => $newDate->toString('dd').'.'.$newDate->toString('MM').'.'.$newDate->toString('yyyy'),
                        'visit' => $varVisit,
                        'debt' => $varDebt,
                        'day' => $varDayDB,
                        'month' => $varMonthDB,
                        'year' => $varYearDB
                    ];
                    $this->addDay($data);
                    // pre($data);
                   
                   $day_id = $this->day_id->text;
                   if ($day_id !== '') {
                       $this->deleteDay($day_id);
                       $this->day_id->text = '';
                       // alert('Удалить : ' . $day_id);
                   }
           }
        }
       
       $this->tablesClear();
       $this->getWeekday($user);
       $this->getVisitOrMoney($user);
        
    }
    
    // Подсказки (функции)
    function notice_WARNING($notice_head, $notice_text)
    {
        $this->notice = new UXTrayNotification(''.$notice_head.''?''.$notice_head.'':'Внимание', ''.$notice_text.'','WARNING');
        $this->notice->animationType = 'POPUP';
        $this->notice->location = 'BOTTOM_RIGHT';
        $this->notice->show();
    }

    function notice_INFORMATION($notice_head, $notice_text)
    {
        $this->notice = new UXTrayNotification(''.$notice_head.''?''.$notice_head.'':'Внимание', ''.$notice_text.'','INFORMATION');
        $this->notice->animationType = 'POPUP';
        $this->notice->location = 'BOTTOM_RIGHT';
        $this->notice->show();
    }

    function notice_NOTICE($notice_head, $notice_text)
    {
        $this->notice = new UXTrayNotification(''.$notice_head.''?''.$notice_head.'':'Внимание', ''.$notice_text.'','NOTICE');
        $this->notice->animationType = 'POPUP';
        $this->notice->location = 'BOTTOM_RIGHT';
        $this->notice->show();
    }

    function notice_SUCCESS($notice_head, $notice_text)
    {
        $this->notice = new UXTrayNotification(''.$notice_head.''?''.$notice_head.'':'Внимание', ''.$notice_text.'','SUCCESS');
        $this->notice->animationType = 'POPUP';
        $this->notice->location = 'BOTTOM_RIGHT';
        $this->notice->show();
    }

    function notice_ERROR($notice_head, $notice_text)
    {
        $this->notice = new UXTrayNotification(''.$notice_head.''?''.$notice_head.'':'Внимание', ''.$notice_text.'','ERROR');
        $this->notice->animationType = 'POPUP';
        $this->notice->location = 'BOTTOM_RIGHT';
        $this->notice->show();
    }
       
}

<?php
namespace app\forms;

use php\gui\framework\AbstractForm;
use php\gui\event\UXEvent; 
use php\gui\event\UXWindowEvent; 


class UserForm extends AbstractForm
{
    /**
     * @var int
     */
    public $id;
    
    /**
     * @var string
     */
    public $fio;
    
    /**
     * @var string
     */
    
    /**
     * @var int
     */
    public $groop;
    
    public $search;                // Поле формируемое для поиска

    /**
     * @event groopSelect.construct 
     */
    function doGroopSelectConstruct(UXEvent $event = null)
    {    
        for ($i = 1; $i < 6; $i++) {
            $this->groopSelect->items->add($i);
        }
        
        $this->groopSelect->value = 1;
    }

    /**
     * @event button.action 
     */
    function doButtonAction(UXEvent $event = null)
    {    
    
            $fio = $this->fioEdit->text; 
            $groop  = $this->groopSelect->value;
            
        $data = [
            'fio' => $fio,
            'groop'  => $groop,
            'search' => ''
            .$this->translit($fio).'~'
            .$this->translit($groop).''
        ];
        
        if (!$this->id) {
            $id = $this->addUser($data);
            
            app()->getMainForm()->toast("Создан новый пользователь с id = $id");
        } else {
            $this->saveUser($this->id, $data);
            
            app()->getMainForm()->toast("Данные успешно сохранены.");
        }
        
        
        $this->hide();
    }

    /**
     * @event showing 
     */
    function doShowing(UXWindowEvent $event = null)
    {    
        if ($this->id) {
            $user = $this->getUser($this->id);
            
            if ($user) {
                $this->fio = $user->get('fio');
                $this->groop  = (int) $user->get('groop');
                $this->search = $user->get('search');
            }
        }
    
        $this->fioEdit->text = $this->fio;
        $this->groopSelect->value = $this->groop;
        
        $this->textArea_search->text = $this->search;
    }

}

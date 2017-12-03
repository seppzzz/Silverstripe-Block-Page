<?php

namespace CyberDuck\BlockPage\Model;

use Page;
use SilverStripe\Control\Controller;
use SilverStripe\Core\Config\Config;
use SilverStripe\Forms\FieldList;
use SilverStripe\Forms\GridField\GridField;
use SilverStripe\Forms\GridField\GridFieldAddNewButton;
use SilverStripe\Forms\GridField\GridFieldConfig_RelationEditor;
use SilverStripe\Forms\GridField\GridFieldDetailForm;
use SilverStripe\Forms\GridField\GridFieldVersionedState;
use SilverStripe\Forms\HiddenField;
use SilverStripe\Forms\OptionsetField;
use SilverStripe\Forms\Tab;
use SilverStripe\Forms\TabSet;
use SilverStripe\ORM\DataObject;
use SilverStripe\ORM\FieldType\DBField;
use SilverStripe\Versioned\Versioned;
use SilverStripe\Versioned\VersionedGridFieldItemRequest;

class ContentBlock extends DataObject
{
    private static $db = [
        'Title' => 'Varchar(512)',
        'Sort'  => 'Int'
    ];

    private static $belongs_many_many = [
        'Pages' => 'Page.ContentBlocks',
    ];

    private static $owned_by = [
        'Pages'
    ];

    private static $extensions = [
        Versioned::class
    ];

    private static $default_sort = 'Sort';

    private static $table_name = 'ContentBlock';

    private static $versioned_gridfield_extensions = true;

    private static $summary_fields = [
        'ID'          => 'ID',
        'ClassName'   => 'ClassName',
        'Title'       => 'Title',
        'Pages.Count' => 'Pages'
    ];

    public function getCMSFields()
    {
        $fields = parent::getCMSFields();
        $fields->removeByName('Pages');
        $fields->push(HiddenField::create('Sort'));
        
        if($this->getAction() == 'new') {
            return $this->getCMSSelectionFields($fields);
        }
        return $fields;
    }
    
    public function getTemplateHolder()
    {
        return $this->renderWith(['Block/ContentBlock_holder']);
    }
    
    public function getTemplate()
    {   
        return $this->renderWith('Block/'.$this->ClassName);
    }
    
    public function getAction()
    {
        $path = explode('/', Controller::curr()->getRequest()->getURL());
        return array_pop($path);
    }

    private function getCMSSelectionFields(FieldList $fields)
    {
        $fields->removeByName('Root');
        // fields used in the inital selection request
        $session = Controller::curr()->getRequest()->getSession();
        $fields->push(HiddenField::create('BlockRelationID')->setValue($session->get('BlockRelationID')));
        $fields->push(HiddenField::create('BlockRelationClass')->setValue($session->get('BlockRelationClass')));

        // create the selection tab and options
        $fields->push(TabSet::create('Root', Tab::create('Main')));

        $rules = (array) Config::inst()->get(ContentBlock::class, 'restrict');
        
        if(array_key_exists($this->BlockRelationClass, $rules)) {
            $classes = $rules[$this->BlockRelationClass];
        } else {
            $classes = (array) Config::inst()->get(ContentBlock::class, 'blocks');
        }
        $options = [];
        foreach($classes as $class) {
            $options[$class] = DBField::create_field('HTMLText', Controller::curr()
                ->customise([
                    'Preview'     => $class::config()->get('preview'),
                    'Title'       => $class::config()->get('title'),
                    'Description' => $class::config()->get('description')
                ])
                ->renderWith('/Includes/ContentBlockOption')
            );
        }
        $checked = key(array_slice($options, 0, 1, true));

        $fields->addFieldToTab('Root.Main', OptionsetField::create('ContentBlock', false, $options, $checked));

        return $fields;
    }
}
<?php
class DMSDocumentExtension extends DataExtension
{
    
    public static $db = array(
        'ShowCategoryFrontend' => 'Boolean(1)',
    );

    public function updateCMSFields(FieldList $fields)
    {
        if ($this->owner->ID) {
            $srcTags = function () {
                $tags = array();
                foreach (DMSTag::get() as $t) {
                    $tags[$t->ID] = $t->Category;
                }
                return $tags;
            };

            $selectTags = DropdownField::create(
                'DocumentCategory',
                _t('DMSDocumentExtension.Category', 'Category'),
                $srcTags()
            )->useAddNew(
                'DMSTag', 
                $srcTags, 
                FieldList::create(
                    TextField::create('Category', _t('DMSDocumentExtension.Category', 'Category *')),
                    HiddenField::create('MultiValue', null, 0)
                )
            );

            $fields->insertAfter($selectTags, 'Description');

            $fields->insertAfter(CheckboxField::create('ShowCategoryFrontend', _t('DMSDocumentExtension.ShowCategoryFrontend', 'Show document category in frontend?')), 'DocumentCategory');
        }
    }

    public function onBeforeWrite()
    {
        $changedFields = $this->owner->getChangedFields(false, 1);

        if (array_key_exists("DocumentCategory", $changedFields)) {
            $currentTags = explode(',', $this->owner->getField('DocumentCategory'));
            $oldTags = DMSTag::get()
                ->innerJoin("DMSDocument_Tags", "\"DMSDocument_Tags\".\"DMSTagID\" = \"DMSTag\".\"ID\" AND \"DMSDocument_Tags\".\"DMSDocumentID\" = " . $this->owner->ID)->column();
            
            // delete the tags
            foreach (array_diff($oldTags, $currentTags) as $idTag) {
                $tag = DMSTag::get()->byID($idTag);
                if ($tag) {
                    $this->owner->removeTag($tag->Category, $tag->Value ? $tag->Value : null);
                }
            }
            // add the tags
            foreach (array_diff($currentTags, $oldTags) as $idTag) {
                $tag = DMSTag::get()->byID($idTag);
                if ($tag) {
                    $tag->Documents()->add($this->owner);
                }
            }
        }
    }

    public function onBeforeDelete()
    {
        $this->owner->removeAllTags();
    }
}

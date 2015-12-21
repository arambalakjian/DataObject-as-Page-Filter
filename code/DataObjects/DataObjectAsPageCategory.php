<?php

class DataObjectAsPageCategory extends DataObject
{
    //Set the listing page for the link builder
    private static $listing_page_class = 'FilteredListPage';
    
    //Set the GET var name for use in the filter
    private static $listing_page_filter_var = 'category';
    
    private static $db = array(
        'Title' => 'Varchar(140)'
    );

    public function canView($member = null)
    {
        return true;
    }
    
    public function getCMSFields()
    {
        $fields = parent::getCMSFields();
        
        $fields->addFieldToTab('Root.Main', new TextField('Title'));

        return $fields;
    }
        
    /*
     * Builds a link to this item in the filter (so you can link directly to a list filtered by this item)
     */
    public function Link()
    {
        if ($ListingPage = $this->getListingPage()) {
            return $ListingPage->Link('?' . $this->stat('listing_page_filter_var') . '=' . $this->ID);
        }
    }

    //Get the listing page to view this Event on (used in Link functions below)
    public function getListingPage()
    {
        $Class = $this->stat('listing_page_class');
        
        if (Controller::curr() instanceof $Class) {
            $ListingPage = Controller::curr();
        } else {
            $ListingPage = DataObject::get_one($Class);
        }
        
        return $ListingPage;
    }
}

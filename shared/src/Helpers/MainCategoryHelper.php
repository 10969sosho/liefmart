<?php

namespace Shared\Helpers;

class MainCategoryHelper
{
    public static function belongsToSelectedCategory($mainCategoryId): bool
    {
        if (!session()->has('main_category_id')) {
            return false;
        }

        $selectedMainCategoryId = session('main_category_id');
        
        return $mainCategoryId == $selectedMainCategoryId;
    }
    
    public static function getSelectedMainCategoryId()
    {
        return session('main_category_id');
    }
    
    public static function getSelectedMainCategoryName()
    {
        return session('main_category_name');
    }
} 

<?php

namespace App\Helpers;

class MainCategoryHelper
{
    /**
     * Check if a given main category ID matches the one in the session
     *
     * @param int|null $mainCategoryId
     * @return bool
     */
    public static function belongsToSelectedCategory($mainCategoryId): bool
    {
        if (!session()->has('main_category_id')) {
            return false;
        }

        $selectedMainCategoryId = session('main_category_id');
        
        return $mainCategoryId == $selectedMainCategoryId;
    }
    
    /**
     * Get the selected main category ID from session
     *
     * @return int|null
     */
    public static function getSelectedMainCategoryId()
    {
        return session('main_category_id');
    }
    
    /**
     * Get the selected main category name from session
     *
     * @return string|null
     */
    public static function getSelectedMainCategoryName()
    {
        return session('main_category_name');
    }
} 
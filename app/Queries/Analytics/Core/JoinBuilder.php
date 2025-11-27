<?php

namespace App\Queries\Analytics\Core;

/**
 * JoinBuilder - Reusable SQL join builder
 * 
 * Membantu membangun JOIN clause yang konsisten untuk semua analytics
 */
class JoinBuilder
{
    /**
     * Build join with orders table
     * 
     * @param string $alias Alias untuk orders table
     * @param string $onColumn Column untuk join condition
     * @param string $joinType 'INNER' or 'LEFT'
     * @return string SQL JOIN clause
     */
    public static function joinOrders(string $alias = 'o', string $onColumn = 'order_id', string $joinType = 'INNER'): string
    {
        return " {$joinType} JOIN orders {$alias} ON {$alias}.id = {$onColumn}";
    }
    
    /**
     * Build join with platforms table
     * 
     * @param string $ordersAlias Alias untuk orders table
     * @param string $platformAlias Alias untuk platforms table
     * @param string $joinType 'INNER' or 'LEFT'
     * @return string SQL JOIN clause
     */
    public static function joinPlatforms(string $ordersAlias = 'o', string $platformAlias = 'p', string $joinType = 'LEFT'): string
    {
        return " {$joinType} JOIN platforms {$platformAlias} ON {$platformAlias}.id = {$ordersAlias}.platform_id";
    }
    
    /**
     * Build join with order_items
     * 
     * @param string $ordersAlias Alias untuk orders table
     * @param string $itemsAlias Alias untuk order_items table
     * @param string $joinType 'INNER' or 'LEFT'
     * @return string SQL JOIN clause
     */
    public static function joinOrderItems(string $ordersAlias = 'o', string $itemsAlias = 'oi', string $joinType = 'LEFT'): string
    {
        return " {$joinType} JOIN order_items {$itemsAlias} ON {$itemsAlias}.order_id = {$ordersAlias}.id";
    }
    
    /**
     * Build join chain untuk HPP calculation
     * order_items -> warehouse_stock -> penerimaan_detail
     * 
     * @param string $itemsAlias Alias untuk order_items
     * @param string $stockAlias Alias untuk warehouse_stock
     * @param string $detailAlias Alias untuk penerimaan_detail
     * @return string SQL JOIN clause
     */
    public static function joinHppChain(
        string $itemsAlias = 'oi',
        string $stockAlias = 'ws',
        string $detailAlias = 'pd'
    ): string {
        return "
            LEFT JOIN warehouse_stock {$stockAlias} ON {$itemsAlias}.warehouse_stock_id = {$stockAlias}.id
            LEFT JOIN penerimaan_detail {$detailAlias} ON {$stockAlias}.penerimaan_detail_id = {$detailAlias}.id";
    }
}


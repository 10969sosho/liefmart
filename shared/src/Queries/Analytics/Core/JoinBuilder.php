<?php

namespace Shared\Queries\Analytics\Core;

/**
 * JoinBuilder - Reusable SQL join builder
 * 
 * Membantu membangun JOIN clause yang konsisten untuk semua analytics
 */
class JoinBuilder
{
    public static function joinOrders(string $alias = 'o', string $onColumn = 'order_id', string $joinType = 'INNER'): string
    {
        return " {$joinType} JOIN orders {$alias} ON {$alias}.id = {$onColumn}";
    }
    
    public static function joinPlatforms(string $ordersAlias = 'o', string $platformAlias = 'p', string $joinType = 'LEFT'): string
    {
        return " {$joinType} JOIN platforms {$platformAlias} ON {$platformAlias}.id = {$ordersAlias}.platform_id";
    }
    
    public static function joinOrderItems(string $ordersAlias = 'o', string $itemsAlias = 'oi', string $joinType = 'LEFT'): string
    {
        return " {$joinType} JOIN order_items {$itemsAlias} ON {$itemsAlias}.order_id = {$ordersAlias}.id";
    }
    
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

<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Parameter extends Model
{
    use HasFactory;

    protected $table = 'parameters';

    protected $fillable = [
        'param_name',
        'param_value',
        'param_group',
        'description',
        'is_active',
    ];

    protected $casts = [
        'is_active' => 'boolean',
    ];

    /**
     * Get parameter value by name and group
     */
    public static function getValue($paramName, $paramGroup = null, $default = null)
    {
        $query = self::where('param_name', $paramName)
                    ->where('is_active', true);
        
        if ($paramGroup !== null) {
            $query->where('param_group', $paramGroup);
        }
        
        $parameter = $query->first();
        
        return $parameter ? $parameter->param_value : $default;
    }

    /**
     * Get invoice format configuration
     */
    public static function getInvoiceFormatConfig()
    {
        return [
            'suffix' => self::getValue('invoice_format_suffix', 'invoice_format', 'AMP'),
            'year_month' => self::getValue('invoice_format_year_month', 'invoice_format', date('ym')),
            'counter_length' => (int) self::getValue('invoice_format_counter_length', 'invoice_format', 4),
            'pkp_tax_code' => self::getValue('pkp_tax_code', 'invoice_format', '01'),
            'non_pkp_tax_code' => self::getValue('non_pkp_tax_code', 'invoice_format', '02'),
        ];
    }

    /**
     * Update parameter value
     */
    public static function setValue($paramName, $paramValue, $paramGroup = null)
    {
        $query = self::where('param_name', $paramName);
        
        if ($paramGroup !== null) {
            $query->where('param_group', $paramGroup);
        }
        
        $parameter = $query->first();
        
        if ($parameter) {
            $parameter->update([
                'param_value' => $paramValue,
                'updated_at' => now()
            ]);
        } else {
            self::create([
                'param_name' => $paramName,
                'param_value' => $paramValue,
                'param_group' => $paramGroup,
                'created_at' => now(),
                'updated_at' => now()
            ]);
        }
        
        return true;
    }
}
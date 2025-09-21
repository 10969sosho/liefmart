<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;
use App\Models\FinanceOffline;

class CheckPrintPermission
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        $invoiceNumber = $request->route('invoiceNumber');
        
        if ($invoiceNumber) {
            $invoice = FinanceOffline::where('invoice_number', $invoiceNumber)->first();
            
            if ($invoice) {
                $user = $request->user();
                
                if (!$user || !$invoice->canBePrinted($user)) {
                    if ($invoice->print_count > 0 && !$invoice->reprint_requested) {
                        // Request reprint if not already requested
                        $invoice->requestReprint();
                        return redirect()->route('finance.offline.invoices')
                            ->with('error', 'Anda telah mencapai batas cetak. Permohonan cetak ulang telah dikirim ke Super Admin untuk persetujuan.');
                    } elseif ($invoice->reprint_requested && !$invoice->reprint_approved) {
                        return redirect()->route('finance.offline.invoices')
                            ->with('error', 'Permohonan cetak ulang sedang menunggu persetujuan dari Super Admin.');
                    }
                    
                    return redirect()->route('finance.offline.invoices')
                        ->with('error', 'Anda tidak memiliki izin untuk mencetak invoice ini.');
                }
            }
        }
        
        return $next($request);
    }
}

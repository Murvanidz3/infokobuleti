<?php

namespace App\Http\Controllers\Billing;

use App\Http\Controllers\Controller;
use App\Models\Order;
use App\Services\Billing\InvoiceDocument;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Invoice download for the order's owner (PDF, or a printable page when dompdf is missing).
 */
class InvoiceController extends Controller
{
    public function __invoke(Request $request, int $order, InvoiceDocument $invoices): Response
    {
        $order = Order::query()
            ->where('user_id', $request->user()?->getKey())
            ->whereNotNull('number')
            ->findOrFail($order);

        $file = $invoices->file($order, app()->getLocale());

        return response($file['content'], 200, [
            'Content-Type' => $file['mime'].($file['mime'] === 'text/html' ? '; charset=UTF-8' : ''),
            'Content-Disposition' => ($file['mime'] === 'application/pdf' ? 'attachment' : 'inline').'; filename="'.$file['name'].'"',
        ]);
    }
}

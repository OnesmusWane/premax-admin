<?php

namespace App\Http\Controllers;

use App\Models\{Invoice, VehicleChecklist};
use Illuminate\Http\Request;

/**
 * Returns printable HTML views for invoice and checklist.
 * The frontend opens these in a new tab and triggers window.print().
 * No PDF library needed — the browser handles printing to PDF.
 */
class PrintController extends Controller
{
    /**
     * GET /print/invoice/{invoice}
     * Printable invoice view.
     */
    public function invoice(Invoice $invoice)
    {
        $invoice->load(['customer', 'vehicle', 'items', 'creator']);
        return view('print.invoice', compact('invoice'));
    }

    /**
     * GET /print/checklist/{checklist}
     * Printable check-in / check-out form.
     */
    public function checklist(VehicleChecklist $checklist)
    {
        $checklist->load(['vehicle', 'customer', 'jobCard', 'checkedInBy', 'checkedOutBy']);
        return view('print.checklist', compact('checklist'));
    }
}
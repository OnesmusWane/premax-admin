<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Invoice {{ $invoice->invoice_number }} — Premax Autocare</title>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { font-family: Arial, sans-serif; font-size: 11px; color: #111; background: #fff; }

        /* ── Force single page, remove ALL browser chrome ── */
        @page {
            size: A4 portrait;
            margin: 0;                    /* removes header/footer space entirely */
        }

        /* Screen: show centred with padding so it looks good */
        .page {
            width: 210mm;
            margin: 0 auto;
            padding: 12mm 14mm 10mm;
        }

        /* Print: fill the page exactly */
        @media print {
            html, body { width: 210mm; height: 297mm; }
            body { -webkit-print-color-adjust: exact; print-color-adjust: exact; margin: 0; }
            .no-print { display: none !important; }
            .page {
                width: 210mm;
                min-height: 0;
                margin: 0;
                padding: 10mm 12mm 8mm;
                page-break-after: avoid;
            }
        }

        /* ── Layout ── */
        .header {
            display: flex;
            justify-content: space-between;
            align-items: flex-start;
            border-bottom: 2.5px solid #DC2626;
            padding-bottom: 10px;
            margin-bottom: 10px;
        }
        .logo-area { display: flex; align-items: center; gap: 10px; }
        .logo-area img { width: 48px; height: 48px; object-fit: contain; }
        .company-name { font-size: 15px; font-weight: 900; color: #DC2626; }
        .company-sub { font-size: 9px; color: #666; margin-top: 1px; }
        .company-contact { text-align: right; font-size: 9px; color: #555; line-height: 1.6; }
        .company-contact strong { font-size: 10px; color: #DC2626; }

        .invoice-title { display: flex; justify-content: space-between; align-items: center; margin-bottom: 12px; }
        .invoice-title h1 { font-size: 20px; font-weight: 900; color: #111; letter-spacing: -0.5px; }
        .invoice-meta { text-align: right; }
        .inv-num { font-size: 14px; font-weight: 700; color: #DC2626; }
        .inv-date { font-size: 9px; color: #888; margin-top: 1px; }

        .bill-section { display: grid; grid-template-columns: 1fr 1fr; gap: 12px; margin-bottom: 12px; }
        .bill-box { background: #f9fafb; border: 1px solid #e5e7eb; border-radius: 6px; padding: 9px 12px; }
        .bill-box h3 { font-size: 8px; text-transform: uppercase; letter-spacing: 1px; color: #999; margin-bottom: 5px; }
        .bill-box p { font-size: 11px; color: #111; line-height: 1.55; }
        .bill-box strong { font-weight: 700; font-size: 12px; }

        .items-table { width: 100%; border-collapse: collapse; margin-bottom: 12px; }
        .items-table thead { background: #DC2626; color: white; }
        .items-table thead th { padding: 6px 9px; font-size: 9px; text-transform: uppercase; letter-spacing: 0.4px; text-align: left; font-weight: 600; }
        .items-table thead th:last-child { text-align: right; }
        .items-table tbody tr { border-bottom: 1px solid #f3f4f6; }
        .items-table tbody tr:nth-child(even) { background: #fafafa; }
        .items-table tbody td { padding: 7px 9px; font-size: 11px; color: #333; }
        .items-table tbody td:last-child { text-align: right; font-weight: 600; }
        .items-table tfoot td { padding: 5px 9px; font-size: 11px; }
        .items-table tfoot .label { color: #666; text-align: right; padding-right: 14px; }
        .items-table tfoot .value { font-weight: 600; text-align: right; min-width: 90px; }
        .items-table tfoot .total-row td { border-top: 2px solid #DC2626; padding-top: 7px; }
        .items-table tfoot .total-row .label { color: #111; font-weight: 700; font-size: 12px; }
        .items-table tfoot .total-row .value { color: #DC2626; font-weight: 900; font-size: 13px; }

        .payment-info { display: grid; grid-template-columns: 1fr 1fr; gap: 12px; margin-bottom: 12px; }
        .payment-box { background: #f9fafb; border: 1px solid #e5e7eb; border-radius: 6px; padding: 9px 12px; }
        .payment-box h3 { font-size: 8px; text-transform: uppercase; letter-spacing: 1px; color: #999; margin-bottom: 5px; }
        .method-badge { display: inline-flex; background: #dcfce7; color: #166534; font-weight: 700; font-size: 10px; padding: 2px 8px; border-radius: 20px; }
        .status-badge { display: inline-flex; font-weight: 700; font-size: 10px; padding: 2px 8px; border-radius: 20px; }
        .status-paid    { background: #dcfce7; color: #166534; }
        .status-pending { background: #fef9c3; color: #854d0e; }

        .thank-you { text-align: center; margin: 10px 0; padding: 10px; background: #fff5f5; border-radius: 6px; border: 1px solid #fecaca; }
        .thank-you p { font-size: 12px; font-weight: 700; color: #DC2626; }
        .thank-you small { font-size: 9px; color: #888; }

        .footer { border-top: 1px solid #e5e7eb; padding-top: 8px; display: flex; justify-content: space-between; align-items: center; margin-top: 8px; }
        .footer-note { font-size: 9px; color: #aaa; }
        .footer-brand { font-size: 10px; font-weight: 700; color: #DC2626; }
    </style>

    <script>
        window.addEventListener('load', function () {
            setTimeout(function () {
                if (window.self !== window.top) {
                    window.print()
                }
            }, 350)
        })
    </script>
</head>
<body>

<div class="no-print" style="position:fixed;top:12px;right:12px;z-index:999;display:flex;gap:6px;">
    <button onclick="window.print()"
        style="background:#DC2626;color:white;border:none;padding:7px 18px;border-radius:8px;font-weight:700;cursor:pointer;font-size:12px;">
        🖨 Print / Save PDF
    </button>
    <button onclick="window.close()"
        style="background:#f3f4f6;color:#333;border:none;padding:7px 14px;border-radius:8px;font-weight:600;cursor:pointer;font-size:12px;">
        ✕ Close
    </button>
</div>

<div class="page">

    <!-- Header -->
    <div class="header">
        <div class="logo-area">
            <img src="/assets/images/logos/logo.png" alt="Premax">
            <div>
                <div class="company-name">PREMAX AUTOCARE</div>
                <div class="company-sub">& Diagnostic Services</div>
            </div>
        </div>
        <div class="company-contact">
            <strong>Premax Autocare & Diagnostic Services</strong><br>
            Kiambu Road / Northern Bypass Junction<br>
            Next to the Glee Hotel, Nairobi<br>
            Tel: +254 742 091 794 / +254 722 219 396<br>
            Email: premaxautocare@gmail.com
        </div>
    </div>

    <!-- Invoice title -->
    <div class="invoice-title">
        <h1>INVOICE</h1>
        <div class="invoice-meta">
            <div class="inv-num">{{ $invoice->invoice_number }}</div>
            <div class="inv-date">Date: {{ now()->format('d M Y') }}</div>
            @if($invoice->paid_at)
            <div class="inv-date">Paid: {{ $invoice->paid_at->format('d M Y, h:i A') }}</div>
            @endif
        </div>
    </div>

    <!-- Bill To / Vehicle -->
    <div class="bill-section">
        <div class="bill-box">
            <h3>Invoice To</h3>
            <p>
                <strong>{{ $invoice->customer?->name ?? 'Walk-in Customer' }}</strong><br>
                @if($invoice->customer?->phone) {{ $invoice->customer->phone }}<br> @endif
                @if($invoice->customer?->email) {{ $invoice->customer->email }} @endif
            </p>
        </div>
        <div class="bill-box">
            <h3>Vehicle</h3>
            <p>
                @if($invoice->vehicle)
                    <strong>{{ $invoice->vehicle->registration }}</strong><br>
                    {{ $invoice->vehicle->make }} {{ $invoice->vehicle->model }}
                    @if($invoice->vehicle->year) · {{ $invoice->vehicle->year }} @endif
                @else
                    <strong>—</strong>
                @endif
                @if($invoice->booking)
                    <br><span style="font-size:9px;color:#999;">Booking: {{ $invoice->booking->reference }}</span>
                @endif
            </p>
        </div>
    </div>

    <!-- Items -->
    <table class="items-table">
        <thead>
            <tr>
                <th style="width:30px;">#</th>
                <th>Description</th>
                <th style="width:40px;">Qty</th>
                <th style="width:100px;">Unit Price</th>
                <th style="width:100px;">Total</th>
            </tr>
        </thead>
        <tbody>
            @forelse($invoice->items as $i => $item)
            <tr>
                <td>{{ $i + 1 }}</td>
                <td>{{ $item->description }}</td>
                <td>{{ $item->quantity }}</td>
                <td>KES {{ number_format($item->unit_price) }}</td>
                <td>KES {{ number_format($item->total) }}</td>
            </tr>
            @empty
            <tr><td colspan="5" style="text-align:center;color:#888;padding:14px;">No items</td></tr>
            @endforelse
        </tbody>
        <tfoot>
            <tr>
                <td colspan="3"></td>
                <td class="label">Subtotal</td>
                <td class="value">KES {{ number_format($invoice->subtotal) }}</td>
            </tr>
            @if($invoice->discount > 0)
            <tr>
                <td colspan="3"></td>
                <td class="label">Discount</td>
                <td class="value" style="color:#16a34a;">− KES {{ number_format($invoice->discount) }}</td>
            </tr>
            @endif
            <tr>
                <td colspan="3"></td>
                <td class="label">VAT ({{ $invoice->vat_percent }}%)</td>
                <td class="value">KES {{ number_format($invoice->vat_amount) }}</td>
            </tr>
            <tr class="total-row">
                <td colspan="3"></td>
                <td class="label">TOTAL DUE</td>
                <td class="value">KES {{ number_format($invoice->total) }}</td>
            </tr>
        </tfoot>
    </table>

    <!-- Payment Info -->
    <div class="payment-info">
        <div class="payment-box">
            <h3>Payment Method</h3>
            <div class="method-badge" style="margin-top:4px;">
                {{ strtoupper($invoice->payment_method ?? 'N/A') }}
            </div>
            @if($invoice->mpesa_reference)
            <p style="margin-top:5px;font-size:10px;color:#444;">M-Pesa Ref: <strong>{{ $invoice->mpesa_reference }}</strong></p>
            @endif
            @if($invoice->card_reference)
            <p style="margin-top:5px;font-size:10px;color:#444;">Card Ref: <strong>{{ $invoice->card_reference }}</strong></p>
            @endif
        </div>
        <div class="payment-box">
            <h3>Payment Status</h3>
            <div class="status-badge {{ $invoice->status === 'paid' ? 'status-paid' : 'status-pending' }}" style="margin-top:4px;">
                {{ strtoupper($invoice->status) }}
            </div>
            @if($invoice->creator)
            <p style="margin-top:5px;font-size:9px;color:#888;">Processed by: {{ $invoice->creator->name }}</p>
            @endif
            @if($invoice->paid_at)
            <p style="font-size:9px;color:#aaa;margin-top:2px;">{{ $invoice->paid_at->format('d M Y, h:i A') }}</p>
            @endif
        </div>
    </div>

    <!-- Thank you -->
    <div class="thank-you">
        <p>Thank you for choosing Premax Autocare!</p>
        <small>We treat every vehicle with the utmost care and professionalism.</small>
    </div>

    <!-- Footer -->
    <div class="footer">
        <div class="footer-note">Printed: {{ now()->format('d M Y, h:i A') }} · Computer-generated invoice, no signature required.</div>
        <div class="footer-brand">PREMAX AUTOCARE & DIAGNOSTIC SERVICES</div>
    </div>

</div>
</body>
</html>
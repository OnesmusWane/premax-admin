<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Check {{ ucfirst(str_replace('_', '-', $checklist->status)) }} — {{ $checklist->reg_no }}</title>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { font-family: Arial, sans-serif; font-size: 10px; color: #111; background: #fff; }
        .page { width: 210mm; margin: 0 auto; padding: 8mm 10mm; }

        /* ── Remove all browser print chrome ── */
        @page {
            size: A4 portrait;
            margin: 8mm 10mm;
        }

        .header { display: flex; justify-content: space-between; align-items: flex-start; border-bottom: 3px solid #DC2626; padding-bottom: 8px; margin-bottom: 8px; }
        .logo-wrap { display: flex; align-items: center; gap: 8px; }
        .logo-wrap img { width: 48px; height: 48px; object-fit: contain; }
        .brand { font-size: 14px; font-weight: 900; color: #DC2626; }
        .brand-sub { font-size: 9px; color: #555; }
        .contact-info { text-align: right; font-size: 9px; color: #555; line-height: 1.5; }

        .title-bar { background: #DC2626; color: white; text-align: center; padding: 5px; font-size: 13px; font-weight: 900; letter-spacing: 1px; margin-bottom: 6px; border-radius: 4px; }

        .sn-row { display: flex; justify-content: space-between; align-items: center; margin-bottom: 4px; font-size: 9px; color: #666; }
        .sn-row strong { color: #DC2626; font-size: 10px; }

        .vehicle-grid { display: grid; grid-template-columns: auto 1fr auto 1fr auto 1fr auto 1fr; gap: 4px 6px; align-items: center; border: 1px solid #ddd; border-radius: 4px; padding: 6px 8px; margin-bottom: 6px; background: #fafafa; }
        .vlabel { font-weight: 700; font-size: 9px; color: #555; white-space: nowrap; }
        .vval { border-bottom: 1px solid #999; min-width: 60px; font-size: 10px; padding: 1px 2px; font-weight: 600; }

        .fuel-row { display: flex; align-items: center; gap: 8px; margin-bottom: 4px; font-size: 9px; }
        .fuel-gauge { display: flex; gap: 4px; }
        .fg { border: 1px solid #999; padding: 2px 6px; border-radius: 3px; font-size: 9px; font-weight: 700; }
        .fg.active { background: #DC2626; color: white; border-color: #DC2626; }

        .payment-row { display: flex; align-items: center; gap: 10px; margin-bottom: 6px; }
        .popt { display: flex; align-items: center; gap: 3px; font-size: 9px; }
        .box { width: 10px; height: 10px; border: 1px solid #999; border-radius: 2px; display: inline-flex; align-items: center; justify-center; font-size: 7px; }
        .box.selected { background: #DC2626; border-color: #DC2626; color: white; }

        .section-head { background: #1f2937; color: white; padding: 3px 8px; font-size: 9px; font-weight: 700; letter-spacing: 0.5px; text-transform: uppercase; margin-bottom: 2px; border-radius: 3px; display: flex; justify-content: space-between; align-items: center; }
        .section-head .counts { font-size: 8px; font-weight: normal; display: flex; gap: 6px; }
        .count-badge { padding: 1px 5px; border-radius: 10px; font-weight: bold; }
        .count-missing { background: #EAB308; color: white; }
        .count-damaged { background: #EF4444; color: white; }

        .three-col { display: grid; grid-template-columns: 1fr 1fr 1fr; gap: 8px; margin-bottom: 6px; }

        /* Each checklist item */
        .check-item { padding: 2px 0; border-bottom: 1px dotted #e5e7eb; }
        .check-item-row { display: flex; align-items: center; gap: 4px; }
        .item-label { flex: 1; font-size: 9px; color: #333; }
        .tick { width: 11px; height: 11px; border: 1px solid #999; border-radius: 2px; display: inline-flex; align-items: center; justify-content: center; font-size: 8px; shrink: 0; }
        .tick.ok      { background: #dcfce7; border-color: #16a34a; color: #16a34a; }
        .tick.missing { background: #fef9c3; border-color: #ca8a04; color: #ca8a04; }
        .tick.damaged { background: #fee2e2; border-color: #dc2626; color: #dc2626; }
        /* Note line below item */
        .item-note { font-size: 8px; color: #555; font-style: italic; margin-top: 1px; padding-left: 15px; border-left: 2px solid #e5e7eb; }
        .item-note.damaged-note { border-left-color: #dc2626; color: #dc2626; }
        .item-note.missing-note { border-left-color: #ca8a04; color: #92400e; }

        .remarks-label { font-size: 9px; font-weight: 700; color: #555; margin-bottom: 2px; margin-top: 4px; }
        .remarks-box { border: 1px solid #ddd; border-radius: 4px; padding: 4px 6px; min-height: 28px; font-size: 9px; color: #333; margin-bottom: 4px; }

        .release-grid { display: grid; grid-template-columns: 1fr 1fr; gap: 8px; border: 1px solid #ddd; border-radius: 4px; padding: 6px 8px; background: #fafafa; }
        .release-field label { font-size: 8px; font-weight: 700; color: #888; text-transform: uppercase; }
        .release-field .rval { border-bottom: 1px solid #999; min-height: 14px; font-size: 10px; font-weight: 600; }

        .print-footer { border-top: 1px solid #ddd; padding-top: 5px; margin-top: 6px; display: flex; justify-content: space-between; font-size: 8px; color: #888; }

        @media print {
            body { -webkit-print-color-adjust: exact; print-color-adjust: exact; }
            .no-print { display: none !important; }
            .page { width: 100%; margin: 0; padding: 0; }
        }
    </style>
    <script>
        // Auto-print when loaded via iframe (no browser headers/footers)
        window.addEventListener('load', function () {
            // Small delay to ensure styles are applied
            setTimeout(function () {
                if (window.self !== window.top) {
                    // Inside iframe — print silently
                    window.print()
                }
            }, 400)
        })
    </script>
    </style>
</head>
<body>

<div class="no-print" style="position:fixed;top:12px;right:12px;z-index:999;display:flex;gap:6px;">
    <button onclick="window.print()" style="background:#DC2626;color:white;border:none;padding:7px 18px;border-radius:8px;font-weight:700;cursor:pointer;font-size:12px;">🖨 Print</button>
    <button onclick="window.close()" style="background:#f3f4f6;color:#333;border:none;padding:7px 14px;border-radius:8px;font-weight:600;cursor:pointer;font-size:12px;">✕ Close</button>
</div>

<div class="page">

    <!-- Header -->
    <div class="header">
        <div class="logo-wrap">
            <img src="/assets/images/logos/logo.png" alt="Premax">
            <div>
                <div class="brand">PREMAX AUTOCARE</div>
                <div class="brand-sub">& Diagnostic Services</div>
            </div>
        </div>
        <div class="contact-info">
            Kiambu Road/Northern Bypass Junction<br>
            Next to The Glee Hotel<br>
            Tel: +254 742 091 794 / +254 722 219 396<br>
            Email: <a href="/cdn-cgi/l/email-protection" class="__cf_email__" data-cfemail="0a7a786f676b726b7f7e65696b786f4a6d676b636624696567">[email&#160;protected]</a>
        </div>
    </div>

    <div class="title-bar">MOTOR VEHICLE CHECK IN / CHECK OUT LIST</div>

    <!-- SN + date -->
    <div class="sn-row">
        <span>SN: <strong>{{ $checklist->sn }}</strong></span>
        <span>Date: <strong>{{ $checklist->checked_in_at?->format('d/m/Y') ?? now()->format('d/m/Y') }}</strong></span>
        <span>Status: <strong>{{ strtoupper(str_replace('_', ' ', $checklist->status)) }}</strong></span>
    </div>

    <!-- Vehicle details -->
    <div class="vehicle-grid">
        <span class="vlabel">REG NO:</span><span class="vval">{{ $checklist->reg_no }}</span>
        <span class="vlabel">MAKE:</span><span class="vval">{{ $checklist->make }}</span>
        <span class="vlabel">MODEL:</span><span class="vval">{{ $checklist->model }}</span>
        <span class="vlabel">COLOUR:</span><span class="vval">{{ $checklist->colour }}</span>
    </div>

    <!-- Fuel + odometer -->
    <div class="fuel-row">
        <span style="font-weight:700;">FUEL GAUGE:</span>
        <div class="fuel-gauge">
            @foreach(['F','3/4','1/2','1/4','E'] as $level)
            <span class="fg {{ $checklist->fuel_level === $level ? 'active' : '' }}">{{ $level }}</span>
            @endforeach
        </div>
        <span style="margin-left:12px;font-weight:700;">ODOMETER:</span>
        <span style="border-bottom:1px solid #999;min-width:60px;padding:0 4px;font-weight:600;">
            {{ $checklist->odometer ? number_format($checklist->odometer) . ' km' : '' }}
        </span>
    </div>

    <!-- Payment options -->
    <div class="payment-row">
        <span style="font-weight:700;font-size:9px;">PAYMENT OPTIONS:</span>
        @foreach(['mpesa'=>'MPESA','cash'=>'CASH','insurance'=>'INSURANCE','cheque'=>'CHEQUE','other'=>'OTHERS'] as $key=>$label)
        <span class="popt">
            <span class="box {{ $checklist->payment_option === $key ? 'selected' : '' }}">
                {{ $checklist->payment_option === $key ? '✓' : '' }}
            </span>
            {{ $label }}
        </span>
        @endforeach
    </div>

    @php
    // Helper: count items by status
    function countStatus($items, $status) {
        return collect($items)->filter(fn($i) => ($i['status'] ?? 'ok') === $status)->count();
    }

    $extLabels = [
        'front_windscreen'=>'Front Wind Screen','rear_windscreen'=>'Rear Wind Screen',
        'insurance_sticker'=>'Insurance Sticker','front_number_plate'=>'Front Number Plate',
        'headlights'=>'Headlights','tail_lights'=>'Tail Lights',
        'front_bumper'=>'Front Bumper','rear_bumper'=>'Rear Bumper',
        'grille'=>'Grille','grille_badge'=>'Grille Badge',
        'front_wiper'=>'Front Wiper','rear_wiper'=>'Rear Wiper',
        'side_mirror'=>'Side Mirror','door_glasses'=>'Door Glasses',
        'fuel_tank_cap'=>'Fuel Tank Cap','front_tyres'=>'Front Tyres',
        'rear_tyres'=>'Rear Tyres','front_rims'=>'Front Rims',
        'rear_rims'=>'Rear Rims','hub_wheel_caps'=>'Hub/Wheel Caps',
        'roof_rails'=>'Roof Rails','body_moulding'=>'Body Moulding',
        'emblems'=>'Emblems','weather_stripes'=>'Weather Stripes','mud_guard'=>'Mud Guard',
    ];
    $intLabels = [
        'rear_view_mirror'=>'Rear View Mirror','radio'=>'Radio','radio_face'=>'Radio Face',
        'equalizer'=>'Equalizer','amplifier'=>'Amplifier','tuner'=>'Tuner','speaker'=>'Speaker',
        'cigar_lighter'=>'Cigar Lighter','door_switches'=>'Door Switches',
        'rubber_mats'=>'Rubber Mats','carpets'=>'Carpets','seat_covers'=>'Seat Covers',
        'boot_mat'=>'Boot Mat','boot_board'=>'Boot Board','aircon_knobs'=>'Air Con Knobs',
        'keys_remotes'=>'No. of Keys/Remotes','seat_belts'=>'Seat Belts',
    ];
    $engLabels = [
        'battery'=>'Battery','computer_control_box'=>'Computer/Control Box',
        'ignition_coils'=>'Ignition Coils','wiper_panel_finisher_covers'=>'Wiper Panel Covers',
        'horn'=>'Horn','engine_caps'=>'Engine Caps','dip_sticks'=>'Dip Sticks',
        'starter'=>'Starter','alternator'=>'Alternator','fog_lights'=>'Fog Lights',
        'reverse_camera'=>'Reverse Camera','relays'=>'Relays','radiator'=>'Radiator',
    ];
    $extrasLabels = [
        'jack_handle'=>'Jack & Handle','wheel_spanner'=>'Wheel Spanner',
        'towing_pin'=>'Towing Pin','towing_cable_rope'=>'Towing Cable/Rope',
        'first_aid_kit'=>'First Aid Kit','fire_extinguisher'=>'Fire Extinguisher',
        'spare_wheel'=>'Spare Wheel','life_savers'=>'Life Savers',
    ];

    $exterior = $checklist->exterior ?? [];
    $interior = $checklist->interior ?? [];
    $engine   = $checklist->engine_compartment ?? [];
    $extras   = $checklist->extras ?? [];
    @endphp

    <!-- Three column checklist -->
    <div class="three-col">

        <!-- EXTERIOR -->
        <div>
            @php
            $extMissing = countStatus($exterior, 'missing');
            $extDamaged = countStatus($exterior, 'damaged');
            @endphp
            <div class="section-head">
                Exterior
                <div class="counts">
                    @if($extMissing) <span class="count-badge count-missing">{{ $extMissing }} missing</span> @endif
                    @if($extDamaged) <span class="count-badge count-damaged">{{ $extDamaged }} damaged</span> @endif
                </div>
            </div>
            @foreach($extLabels as $key => $label)
            @php
                $item   = $exterior[$key] ?? ['status'=>'ok','note'=>''];
                $status = $item['status'] ?? 'ok';
                $note   = $item['note']   ?? '';
            @endphp
            <div class="check-item">
                <div class="check-item-row">
                    <span class="item-label">{{ $label }}</span>
                    <span class="tick {{ $status }}">{{ $status==='ok'?'✓':($status==='missing'?'−':'!') }}</span>
                </div>
                @if($note)
                <div class="item-note {{ $status !== 'ok' ? $status.'-note' : '' }}">{{ $note }}</div>
                @endif
            </div>
            @endforeach
        </div>

        <!-- INTERIOR -->
        <div>
            @php
            $intMissing = countStatus($interior, 'missing');
            $intDamaged = countStatus($interior, 'damaged');
            @endphp
            <div class="section-head">
                Interior
                <div class="counts">
                    @if($intMissing) <span class="count-badge count-missing">{{ $intMissing }} missing</span> @endif
                    @if($intDamaged) <span class="count-badge count-damaged">{{ $intDamaged }} damaged</span> @endif
                </div>
            </div>
            @foreach($intLabels as $key => $label)
            @php
                $item   = $interior[$key] ?? ['status'=>'ok','note'=>''];
                $status = $item['status'] ?? 'ok';
                $note   = $item['note']   ?? '';
            @endphp
            <div class="check-item">
                <div class="check-item-row">
                    <span class="item-label">{{ $label }}</span>
                    <span class="tick {{ $status }}">{{ $status==='ok'?'✓':($status==='missing'?'−':'!') }}</span>
                </div>
                @if($note)
                <div class="item-note {{ $status !== 'ok' ? $status.'-note' : '' }}">{{ $note }}</div>
                @endif
            </div>
            @endforeach
        </div>

        <!-- ENGINE + EXTRAS -->
        <div>
            @php
            $engMissing = countStatus($engine, 'missing');
            $engDamaged = countStatus($engine, 'damaged');
            @endphp
            <div class="section-head">
                Engine Compartment
                <div class="counts">
                    @if($engMissing) <span class="count-badge count-missing">{{ $engMissing }} missing</span> @endif
                    @if($engDamaged) <span class="count-badge count-damaged">{{ $engDamaged }} damaged</span> @endif
                </div>
            </div>
            @foreach($engLabels as $key => $label)
            @php
                $item   = $engine[$key] ?? ['status'=>'ok','note'=>''];
                $status = $item['status'] ?? 'ok';
                $note   = $item['note']   ?? '';
            @endphp
            <div class="check-item">
                <div class="check-item-row">
                    <span class="item-label">{{ $label }}</span>
                    <span class="tick {{ $status }}">{{ $status==='ok'?'✓':($status==='missing'?'−':'!') }}</span>
                </div>
                @if($note)
                <div class="item-note {{ $status !== 'ok' ? $status.'-note' : '' }}">{{ $note }}</div>
                @endif
            </div>
            @endforeach

            <!-- Extras -->
            @php
            $exMissing = countStatus($extras, 'missing');
            $exDamaged = countStatus($extras, 'damaged');
            @endphp
            <div class="section-head" style="margin-top:6px;">
                Extras
                <div class="counts">
                    @if($exMissing) <span class="count-badge count-missing">{{ $exMissing }} missing</span> @endif
                    @if($exDamaged) <span class="count-badge count-damaged">{{ $exDamaged }} damaged</span> @endif
                </div>
            </div>
            @foreach($extrasLabels as $key => $label)
            @php
                $item   = $extras[$key] ?? ['status'=>'ok','note'=>''];
                $status = $item['status'] ?? 'ok';
                $note   = $item['note']   ?? '';
            @endphp
            <div class="check-item">
                <div class="check-item-row">
                    <span class="item-label">{{ $label }}</span>
                    <span class="tick {{ $status }}">{{ $status==='ok'?'✓':($status==='missing'?'−':'!') }}</span>
                </div>
                @if($note)
                <div class="item-note {{ $status !== 'ok' ? $status.'-note' : '' }}">{{ $note }}</div>
                @endif
            </div>
            @endforeach
        </div>

    </div>

    <!-- Remarks -->
    @if($checklist->exterior_remarks || $checklist->interior_remarks)
    <div class="remarks-label">Remarks</div>
    @if($checklist->exterior_remarks)
    <div style="font-size:9px;font-weight:700;color:#555;margin-bottom:2px;">Exterior:</div>
    <div class="remarks-box">{{ $checklist->exterior_remarks }}</div>
    @endif
    @if($checklist->interior_remarks)
    <div style="font-size:9px;font-weight:700;color:#555;margin-bottom:2px;">Interior:</div>
    <div class="remarks-box">{{ $checklist->interior_remarks }}</div>
    @endif
    @endif

    <!-- Release section -->
    <div style="font-weight:700;font-size:9px;margin-bottom:4px;color:#555;">
        RELEASED FROM:
        <span style="border-bottom:1px solid #999;display:inline-block;min-width:80px;font-weight:400;">
            {{ $checklist->released_from }}
        </span>
        &nbsp;&nbsp; TO:
        <span style="border-bottom:1px solid #999;display:inline-block;min-width:80px;font-weight:400;">
            {{ $checklist->released_to }}
        </span>
    </div>

    <div class="release-grid">
        <div>
            <div class="section-head" style="margin-bottom:6px;">Released By</div>
            <div style="display:grid;grid-template-columns:1fr 1fr;gap:6px 8px;">
                <div class="release-field"><label>Full Name</label><div class="rval">{{ $checklist->released_by }}</div></div>
                <div class="release-field"><label>Tel No.</label><div class="rval">{{ $checklist->released_by_tel }}</div></div>
                <div class="release-field"><label>Date</label><div class="rval">{{ $checklist->checked_out_at?->format('d/m/Y') }}</div></div>
                <div class="release-field"><label>Signature</label><div class="rval" style="min-height:30px;">
                    @if($checklist->release_signature)
                        <img src="{{ $checklist->release_signature }}" style="max-height:28px;">
                    @endif
                </div></div>
            </div>
        </div>
        <div>
            <div class="section-head" style="margin-bottom:6px;">Received By</div>
            <div style="display:grid;grid-template-columns:1fr 1fr;gap:6px 8px;">
                <div class="release-field"><label>Full Name</label><div class="rval">{{ $checklist->received_by }}</div></div>
                <div class="release-field"><label>Tel No.</label><div class="rval">{{ $checklist->received_by_tel }}</div></div>
                <div class="release-field"><label>ID No.</label><div class="rval">{{ $checklist->received_by_id }}</div></div>
                <div class="release-field"><label>Signature</label><div class="rval" style="min-height:30px;">
                    @if($checklist->receive_signature)
                        <img src="{{ $checklist->receive_signature }}" style="max-height:28px;">
                    @endif
                </div></div>
            </div>
        </div>
    </div>

    <div class="print-footer">
        <span>Printed: {{ now()->format('d M Y, h:i A') }}</span>
        <span style="color:#DC2626;font-weight:7
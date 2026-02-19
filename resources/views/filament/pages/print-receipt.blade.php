<x-filament-panels::page>
    <div class="invoice-toolbar print:hidden">
        <div class="title-wrap">
            <h1 class="invoice-title">Invoice</h1>
            <div class="print-actions">
                <button onclick="setPrint('a4')" class="btn-print">🖨️ Print(A4)</button>
                <button onclick="setPrint('pos')" class="btn-print">🧾 Print</button>
            </div>
        </div>
    </div>

    <div id="invoice-root" data-print="a4">
        <div id="invoice-content" class="invoice">

            <!-- HEADER -->
            <div class="header">
                <div class="logo-wrap">
                    <div class="logo-box">
                        <img src="{{ asset('images/aaliyaa_logo.png') }}" alt="Aaliyaa Clothing">
                    </div>

                    <div class="line-wrap">
                        <div class="ref-no">
                            REF NO: {{ $order->payment->reference_number ?? 'INV-0001' }}
                        </div>
                        <hr>
                        <div class="logo-line"></div>
                    </div>
                </div>
            </div>

            <!-- TITLE -->
            <h2 class="invoice-table-title">INVOICE</h2>

            <!-- TABLE -->
            <table class="invoice-table">
                <tbody>
                    <tr>
                        <th>Description</th>
                        <th>Amount (LKR)</th>
                    </tr>

                    <tr>
                        <td>Item</td>
                        <td>
                            @foreach ($sales as $sale)
                                {{ $sale->product->name }} (x{{ $sale->quantity }})<br>
                            @endforeach
                        </td>
                    </tr>

                    <tr>
                        <td>Unit Price</td>
                        <td>
                            @foreach ($sales as $sale)
                                {{ number_format($sale->unit_price, 2) }}<br>
                            @endforeach
                        </td>
                    </tr>

                    <tr>
                        <td>Discount</td>
                        <td>
                            @if($order->discount_total > 0 && $order->subtotal > 0)
                                {{ number_format(($order->discount_total / $order->subtotal) * 100, 2) }}%
                            @else
                                0.00
                            @endif
                        </td>
                    </tr>

                    <tr>
                        <td>Discount Amount</td>
                        <td>{{ number_format($order->discount_total ?? 0, 2) }}</td>
                    </tr>

                    <tr>
                        <td>Subtotal</td>
                        <td>
                            {{ number_format(($order->subtotal ?? 0) - ($order->discount_total ?? 0), 2) }}
                        </td>
                    </tr>

                    <tr>
                        <td>Delivery Charges</td>
                        <td>{{ number_format($order->shipping_total, 2) }}</td>
                    </tr>

                    <tr class="grand-total">
                        <td>Total Payable</td>
                        <td>{{ number_format($order->grand_total, 2) }}</td>
                    </tr>
                </tbody>
            </table>

            <!-- FOOTER -->
            <div class="footer image-footer">

                <!-- SIGNATURES -->
                <div class="centered-signatures">
                    <div>
                        <div class="line"></div>
                        <span class="sign">Date</span>
                    </div>
                    <div>
                        <div class="line"></div>
                        <span class="sign">Received</span>
                    </div>
                </div>

                <!-- CONTACT -->
                <div class="footer-bottom">
                    <div class="footer-left qr-row">
                        <img src="{{ asset('images/insta.png') }}">
                        <img src="{{ asset('images/tiktok.png') }}">
                    </div>
                    <div class="footer-right">
                        📞 +94 703 363 363<br>
                        🌐 www.aaliyaa.com<br>
                        📍 210, Stanley Thilakarathna Mawatha, Nugegoda
                    </div>
                </div>
            </div>

        </div>
    </div>

    <script>
        function setPrint(type) {
            document.getElementById('invoice-root').setAttribute('data-print', type);
            window.print();
        }
    </script>

<style>
body {
  font-family: Arial, sans-serif;
}

/* Toolbar */
.invoice-toolbar {
  margin-bottom: 10px;
}

.title-wrap {
  display: flex;
  justify-content: space-between;
  align-items: center;
}

.invoice-title {
  font-size: 20px;
  font-weight: bold;
}

.btn-print {
  background: #2563eb;
  color: #fff;
  padding: 6px 12px;
  border-radius: 4px;
  margin-left: 6px;
  border: none;
  cursor: pointer;
}

/* Invoice Box */
.invoice {
  max-width: 800px;
  margin: auto;
  border: 1px solid #ddd;
  padding: 40px;
}

/* Header */
.logo-wrap {
  display: flex;
  align-items: center;
  width: 100%;
}

.logo-box {
  width: 55%;
}

.logo-box img {
  max-width: 90%;
}

.line-wrap {
  flex: 1;
  margin-left: 15px;
}

.ref-no {
  text-align: right;
  font-size: 12px;
  font-weight: bold;
  margin-bottom: 6px;
}

.logo-line {
  display: block; /* ensures it's a block element */
  width: 100%; /* full width */
  height: 2px; /* line thickness */
  background: #000; /* black line */
}

.invoice-table-title {
  text-align: center;
  font-size: 42px;
  font-weight: bold;
  margin: 10px 0 10px 0;
}

.invoice-table {
  width: 100%;
  border-collapse: collapse;
  margin-bottom: 30px;
  font-size: 20px;
}

.invoice-table td {
  border: 1px solid #ddd;
  padding: 8px;
}

.invoice-table th {
  border: 1px solid #ddd;
  padding: 8px;
}

.invoice-table td:first-child {
  font-weight: bold;
  width: 40%;
  text-align: left;
}

.invoice-table td:last-child {
  text-align: right;
}

.invoice-table .grand-total td {
  font-weight: bold;
  border-top: 2px solid #000;
}

/* Footer */
.image-footer {
  margin-top: 50px;
  font-size: 11px;
}

/* Centered Signature Row */
.centered-signatures {
  display: flex;
  justify-content: center; /* center horizontally */
  gap: 330px; /* space between Date and Received */
  margin-bottom: 25px;
}

.centered-signatures div {
  text-align: center; /* center text and line inside each block */
}

.centered-signatures .line {
  width: 180px;
  border-bottom: 1px dotted #999;
  margin: 0 auto 4px auto; /* auto margins center the line */
}

.centered-signatures .sign {
  font-size: 16px;
  display: block;
}

.footer-bottom {
  display: flex;
  gap:20px;
  align-items: center;
}

.footer-left img {
  height: 90px;
}

.qr-row {
  display: flex;
  align-items: center;
  gap: 10px;
}

.footer-right {
  fill: #000;
  text-align: left;
  line-height: 2.5;
  font-size: 14px;
}

@media (max-width: 768px) {

    .invoice {
        padding: 16px;
        border: none;
    }

    /* Toolbar */
    .title-wrap {
        flex-direction: column;
        align-items: flex-start;
        gap: 10px;
    }

    .btn-print {
        padding: 5px 10px;
        font-size: 12px;
    }

    /* Header */
    .logo-wrap {
        flex-direction: row;
        align-items: center;
    }

    .logo-box {
        width: 50%;
        margin-bottom: 10px;
    }

    .logo-box img {
        max-width: 180px;
    }

    .line-wrap {
        width: 100%;
        margin-left: 0;
    }

    .ref-no {
        text-align: right;
        font-size: 11px;
    }

    /* Title */
    .invoice-table-title {
        font-size: 26px;
        margin: 20px 0;
    }

    /* Table */
    .invoice-table {
        font-size: 11px;
    }

    .invoice-table th,
    .invoice-table td {
        padding: 4px;
    }

    .invoice-table td:first-child {
        width: 50%;
    }

    /* Signatures */
    .centered-signatures {
        flex-direction: row;
        gap: 20px;
        align-items: center;
    }

    .centered-signatures .line {
        width: 140px;
    }

    .centered-signatures .sign {
        font-size: 13px;
    }

    /* Footer */
    .footer-bottom {
        flex-direction: row;
        align-items: center;
        text-align: center;
        gap: 12px;
    }

    .footer-left img {
        height: 60px;
    }

    .footer-right {
        padding-left: 8px;
        font-size: 10px;
        line-height: 1.6;
    }
}

/* Print */
@media print {
  body * {
    visibility: hidden;
  }

  #invoice-content,
  #invoice-content * {
    visibility: visible;
  }

  #invoice-content {
    position: absolute;
    left: 0;
    top: 0;
  }

  [data-print="a4"] #invoice-content {
    width: 210mm;
    padding: 20mm;
    font-size: 12px;
    border: none;
  }

  [data-print="a4"] #invoice-content {
    width: 210mm;
    padding: 20mm;
    font-size: 12px;
    border: none;
    box-sizing: border-box;
  }

  [data-print="a4"] .header {
    position: fixed;
    top: 0;
    left: 0;
    right: 0;
    background: #fff;
    padding-bottom: 10px;
  }

  [data-print="a4"] .image-footer {
    position: fixed;
    bottom: 0;
    left: 0;
    right: 0;
    background: #fff;
    padding-top: 10px;
  }

  /* Table fits between header and footer */
  [data-print="a4"] .invoice-table {
    width: 100%; /* full width of container */
    position: fixed;
    bottom: 120px;
    left: 0;
    right: 0;
    border-collapse: collapse;
    margin-bottom: 140px; /* space above footer */
    font-size: 14px;
  }

  [data-print="a4"] .invoice-table td {
    padding: 10px 10px; /* increase row height */
  }

  [data-print="a4"] .invoice-table td:first-child {
    width: 45%; /* first column slightly bigger */
  }

  [data-print="a4"] .invoice-table-title {
    margin-top: 260px; /* adjust title position if needed */
    font-size: 42px;
  }

  [data-print="pos"] #invoice-content {
    width: 80mm;
    padding: 5mm;
    font-size: 9px;
    border: none;
  }

  [data-print="pos"] .invoice-table {
    font-size: 9px;
    margin: 0;
  }

  [data-print="pos"] .image-footer {
        position: static; /* keep it in normal flow */
        margin-top: 15px;
        text-align: center;
        padding: 5px 0;
    }

    [data-print="pos"] .centered-signatures {
        display: flex;
        justify-content: space-around; /* spread Date & Received evenly */
        margin-bottom: 10px;
        flex-wrap: wrap; /* ensures it doesn't break on narrow POS width */
        gap: 20px; /* space between lines */
    }

    [data-print="pos"] .centered-signatures div {
        text-align: center;
    }

    [data-print="pos"] .centered-signatures .line {
        width: 80px; /* smaller line for narrow POS paper */
        border-bottom: 1px dotted #999;
        margin: 0 auto 4px auto;
    }

    [data-print="pos"] .centered-signatures .sign {
        font-size: 9px; /* smaller font for POS */
        display: block;
    }

    [data-print="pos"] .footer-bottom {
        display: flex;
        flex-direction: column;
        gap: 8px;
        align-items: center;
    }

    [data-print="pos"] .qr-row {
        justify-content: center;
        gap: 6px;
    }

    [data-print="pos"] .footer-left img {
        height: 45px;
    }

    [data-print="pos"] .footer-right {
        text-align: center;
        font-size: 9px;
        line-height: 1.5;
    }

  .print\:hidden {
    display: none !important;
  }
}
</style>
</x-filament-panels::page>

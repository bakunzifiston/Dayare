<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <title>{{ __('Finance report') }}</title>
    <style>
        body { font-family: DejaVu Sans, sans-serif; color: #0f172a; font-size: 12px; }
        .header { border-bottom: 2px solid #7f1d1d; padding-bottom: 10px; margin-bottom: 16px; }
        .brand { font-size: 18px; font-weight: bold; color: #7f1d1d; }
        table { width: 100%; border-collapse: collapse; margin-top: 12px; }
        th, td { border: 1px solid #e2e8f0; padding: 6px 8px; text-align: left; }
        .right { text-align: right; }
    </style>
</head>
<body>
    <div class="header">
        <div class="brand">{{ $business->business_name ?: config('app.name') }}</div>
        <div>{{ __('Butcher finance report') }} — {{ strtoupper($type) }}</div>
        <div>{{ $from }} — {{ $to }}</div>
    </div>

    @if ($type === 'pl')
        <table>
            <tr><th>{{ __('Line') }}</th><th class="right">{{ __('Amount (RWF)') }}</th></tr>
            <tr><td>{{ __('Revenue') }}</td><td class="right">{{ number_format((float) $data['revenue'], 0) }}</td></tr>
            <tr><td>{{ __('COGS') }}</td><td class="right">{{ number_format((float) $data['cogs'], 0) }}</td></tr>
            <tr><td><strong>{{ __('Gross profit') }}</strong></td><td class="right"><strong>{{ number_format((float) $data['gross_profit'], 0) }}</strong></td></tr>
            <tr><td>{{ __('Operating expenses') }}</td><td class="right">{{ number_format((float) $data['operating_expenses'], 0) }}</td></tr>
            <tr><td><strong>{{ __('Net profit') }}</strong></td><td class="right"><strong>{{ number_format((float) $data['net_profit'], 0) }}</strong></td></tr>
            <tr><td>{{ __('Net margin %') }}</td><td class="right">{{ $data['net_margin_pct'] }}%</td></tr>
        </table>
    @elseif ($type === 'sales')
        <table>
            <tr><th>{{ __('Group') }}</th><th>{{ __('Sales') }}</th><th class="right">{{ __('Revenue') }}</th></tr>
            @foreach ($data['groups'] as $group)
                <tr>
                    <td>{{ $group['label'] }}</td>
                    <td>{{ $group['sales_count'] ?? '—' }}</td>
                    <td class="right">{{ number_format((float) $group['revenue'], 0) }}</td>
                </tr>
            @endforeach
        </table>
    @elseif ($type === 'cashflow')
        <table>
            <tr><th>{{ __('Date') }}</th><th class="right">{{ __('In') }}</th><th class="right">{{ __('Out') }}</th><th class="right">{{ __('Net') }}</th></tr>
            @foreach ($data['days'] as $day)
                <tr>
                    <td>{{ $day['date'] }}</td>
                    <td class="right">{{ number_format((float) $day['cash_in'], 0) }}</td>
                    <td class="right">{{ number_format((float) $day['cash_out'], 0) }}</td>
                    <td class="right">{{ number_format((float) $day['net'], 0) }}</td>
                </tr>
            @endforeach
        </table>
    @elseif ($type === 'expenses')
        <table>
            <tr><th>{{ __('Date') }}</th><th>{{ __('Category') }}</th><th>{{ __('Description') }}</th><th class="right">{{ __('Amount') }}</th></tr>
            @foreach ($data['expenses'] as $expense)
                <tr>
                    <td>{{ $expense->expense_date?->toDateString() }}</td>
                    <td>{{ $expense->category }}</td>
                    <td>{{ $expense->description }}</td>
                    <td class="right">{{ number_format((float) $expense->amount, 0) }}</td>
                </tr>
            @endforeach
        </table>
    @endif
</body>
</html>

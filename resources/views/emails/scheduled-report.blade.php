<h1>{{ $report->name }}</h1>

<p>{{ __('Generated at') }}: {{ now()->format('Y-m-d H:i') }}</p>

<table cellpadding="8" cellspacing="0" border="1">
    @foreach ($summary as $key => $value)
        @continue(is_array($value))
        <tr>
            <th align="left">{{ __(str_replace('_', ' ', $key)) }}</th>
            <td>{{ is_numeric($value) ? round((float) $value, 2) : $value }}</td>
        </tr>
    @endforeach
</table>

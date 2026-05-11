<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>{{ $report->name }}</title>
</head>
<body style="font-family: 'Inter', system-ui, -apple-system, sans-serif; background-color: #f8fafc; margin: 0; padding: 0; -webkit-font-smoothing: antialiased;">
  <table align="center" border="0" cellpadding="0" cellspacing="0" width="100%" style="background-color: #f8fafc; padding: 40px 20px;">
    <tr>
      <td align="center">
        <table border="0" cellpadding="0" cellspacing="0" width="600" style="background-color: #ffffff; border-radius: 16px; overflow: hidden; box-shadow: 0 10px 15px -3px rgba(0, 0, 0, 0.1);">
          <!-- Header -->
          <tr>
            <td style="background-color: #0f172a; padding: 48px 40px; text-align: left;">
              <h1 style="color: #ffffff; margin: 0; font-size: 24px; font-weight: 800; letter-spacing: -0.025em; line-height: 1.2;">{{ $report->name }}</h1>
              <p style="color: #94a3b8; margin: 12px 0 0; font-size: 14px; font-weight: 500;">{{ __('Operational Intelligence Briefing') }}</p>
            </td>
          </tr>
          
          <!-- Content -->
          <tr>
            <td style="padding: 40px;">
              <p style="color: #1e293b; font-size: 16px; line-height: 1.6; margin: 0;">{{ __('Hello') }},</p>
              <p style="color: #475569; font-size: 15px; line-height: 1.6; margin: 16px 0 32px;">{{ __('Your scheduled performance summary is ready. This data reflects operational metrics captured on') }} <span style="color: #0f172a; font-weight: 700;">{{ now()->format('F d, Y • H:i') }}</span>.</p>
              
              <!-- Metrics Grid -->
              <table width="100%" cellpadding="0" cellspacing="0" style="border-collapse: separate; border-spacing: 0;">
                <thead>
                  <tr>
                    <th align="left" style="padding: 12px 0; border-bottom: 2px solid #f1f5f9; color: #64748b; font-size: 11px; text-transform: uppercase; font-weight: 700; letter-spacing: 0.1em;">{{ __('Operational Metric') }}</th>
                    <th align="right" style="padding: 12px 0; border-bottom: 2px solid #f1f5f9; color: #64748b; font-size: 11px; text-transform: uppercase; font-weight: 700; letter-spacing: 0.1em;">{{ __('Value') }}</th>
                  </tr>
                </thead>
                <tbody>
                  @php $count = 0; @endphp
                  @foreach ($summary as $key => $value)
                    @continue(is_array($value))
                    <tr>
                      <td style="padding: 16px 0; border-bottom: 1px solid #f1f5f9; color: #334155; font-size: 14px; font-weight: 500;">{{ __(str_replace('_', ' ', $key)) }}</td>
                      <td align="right" style="padding: 16px 0; border-bottom: 1px solid #f1f5f9; color: #0f172a; font-size: 14px; font-weight: 800;">{{ is_numeric($value) ? (round((float)$value) == $value ? $value : number_format((float)$value, 2)) : $value }}</td>
                    </tr>
                    @php $count++; @endphp
                  @endforeach
                </tbody>
              </table>

              <!-- Call to Action -->
              <div style="margin-top: 48px; text-align: center;">
                <a href="{{ url('/') }}" style="background-color: #2563eb; color: #ffffff; padding: 14px 32px; border-radius: 8px; text-decoration: none; font-weight: 700; font-size: 14px; display: inline-block; box-shadow: 0 4px 6px -1px rgba(37, 99, 235, 0.2);">{{ __('Access Control Center') }}</a>
              </div>
            </td>
          </tr>
          
          <!-- Footer -->
          <tr>
            <td style="background-color: #f8fafc; padding: 32px 40px; text-align: center; border-top: 1px solid #f1f5f9;">
              <p style="color: #64748b; font-size: 12px; line-height: 1.5; margin: 0;">{{ __('This intelligence briefing was automatically generated based on your subscription preferences.') }}</p>
              <p style="color: #94a3b8; font-size: 11px; margin: 12px 0 0;">&copy; {{ date('Y') }} {{ config('app.name') }} • {{ __('Secure Support Ecosystem') }}</p>
            </td>
          </tr>
        </table>
        
        <!-- Unsubscribe/Notice -->
        <table border="0" cellpadding="0" cellspacing="0" width="600" style="margin-top: 24px;">
          <tr>
            <td align="center" style="padding: 0 40px;">
              <p style="color: #94a3b8; font-size: 11px; margin: 0;">{{ __('Confidentiality Notice: This email and any attachments are intended only for the specified recipients. If you are not the intended recipient, please notify the sender and delete this message.') }}</p>
            </td>
          </tr>
        </table>
      </td>
    </tr>
  </table>
</body>
</html>

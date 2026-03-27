<!DOCTYPE html>
<html lang="vi">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>{{ $subjectText }}</title>
</head>
<body style="margin:0;padding:0;background:#f3f6fb;font-family:Arial,Helvetica,sans-serif;color:#0f172a;">
  <div style="display:none;max-height:0;overflow:hidden;opacity:0;">{{ $previewText }}</div>
  <table role="presentation" width="100%" cellspacing="0" cellpadding="0" style="background:#f3f6fb;padding:24px 12px;">
    <tr>
      <td align="center">
        <table role="presentation" width="100%" cellspacing="0" cellpadding="0" style="max-width:680px;background:#ffffff;border-radius:24px;overflow:hidden;border:1px solid #dbe4f0;">
          <tr>
            <td style="padding:28px 32px;background:linear-gradient(135deg,#102144 0%,#2463eb 100%);color:#ffffff;">
              <div style="font-size:13px;letter-spacing:0.28em;text-transform:uppercase;opacity:0.82;font-weight:700;">AIRecruitment</div>
              <div style="margin-top:18px;display:inline-block;padding:8px 14px;border-radius:999px;font-size:13px;font-weight:700;{{ $isAccepted ? 'background:#dcfce7;color:#166534;' : 'background:#fee2e2;color:#b91c1c;' }}">
                {{ $isAccepted ? 'Đã được chấp nhận' : 'Kết quả ứng tuyển' }}
              </div>
              <h1 style="margin:18px 0 0;font-size:30px;line-height:1.25;font-weight:800;">
                {{ $isAccepted ? 'Chúc mừng bạn đã vượt qua vòng hồ sơ' : 'Cập nhật kết quả ứng tuyển của bạn' }}
              </h1>
              <p style="margin:14px 0 0;font-size:15px;line-height:1.7;opacity:0.92;">
                {{ $isAccepted ? 'Nhà tuyển dụng đã xác nhận hồ sơ của bạn phù hợp và bạn đã vượt qua vòng xét duyệt hiện tại.' : 'Nhà tuyển dụng đã hoàn tất đánh giá hồ sơ của bạn cho đợt tuyển dụng này.' }}
              </p>
            </td>
          </tr>

          <tr>
            <td style="padding:32px;">
              <p style="margin:0 0 18px;font-size:16px;line-height:1.7;">Xin chào <strong>{{ $candidateName }}</strong>,</p>

              <table role="presentation" width="100%" cellspacing="0" cellpadding="0" style="border-collapse:separate;border-spacing:0 12px;">
                <tr>
                  <td style="width:180px;padding:16px 18px;background:#f8fafc;border-radius:16px;font-size:13px;font-weight:700;letter-spacing:0.18em;text-transform:uppercase;color:#64748b;">Vị trí ứng tuyển</td>
                  <td style="padding:16px 18px;background:#f8fafc;border-radius:16px;font-size:16px;font-weight:700;color:#0f172a;">{{ $jobTitle }}</td>
                </tr>
                <tr>
                  <td style="width:180px;padding:16px 18px;background:#f8fafc;border-radius:16px;font-size:13px;font-weight:700;letter-spacing:0.18em;text-transform:uppercase;color:#64748b;">Công ty</td>
                  <td style="padding:16px 18px;background:#f8fafc;border-radius:16px;font-size:16px;font-weight:700;color:#0f172a;">{{ $companyName }}</td>
                </tr>
              </table>

              <div style="margin-top:20px;padding:22px;border-radius:18px;{{ $isAccepted ? 'background:#f0fdf4;border:1px solid #bbf7d0;' : 'background:#fff7ed;border:1px solid #fed7aa;' }}">
                <p style="margin:0;font-size:16px;line-height:1.8;color:#334155;">
                  @if ($isAccepted)
                    Chúc mừng bạn! Hồ sơ của bạn đã được <strong>chấp nhận</strong>. Hãy theo dõi email và khu vực ứng tuyển để chuẩn bị cho các bước tiếp theo.
                  @else
                    Cảm ơn bạn đã dành thời gian ứng tuyển. Ở vòng này, hồ sơ của bạn <strong>chưa phù hợp</strong> với nhu cầu tuyển dụng hiện tại. Bạn vẫn có thể tiếp tục hoàn thiện hồ sơ và ứng tuyển các cơ hội khác phù hợp hơn.
                  @endif
                </p>
              </div>

              <div style="margin-top:28px;text-align:center;">
                <a href="{{ $actionUrl }}" style="display:inline-block;padding:14px 26px;border-radius:14px;background:#2463eb;color:#ffffff;text-decoration:none;font-size:15px;font-weight:700;">
                  Xem đơn ứng tuyển
                </a>
              </div>

              <p style="margin:28px 0 0;font-size:14px;line-height:1.8;color:#475569;">
                {{ $isAccepted ? 'Chúc bạn có một quá trình phỏng vấn thật thuận lợi.' : 'Chúc bạn sớm tìm được vị trí phù hợp với mình.' }}
              </p>
            </td>
          </tr>
        </table>
      </td>
    </tr>
  </table>
</body>
</html>

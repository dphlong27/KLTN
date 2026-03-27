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
              <div style="margin-top:18px;display:inline-block;padding:8px 14px;border-radius:999px;font-size:13px;font-weight:700;background:#dbeafe;color:#1d4ed8;">
                {{ $isRescheduled ? 'Lịch phỏng vấn đã cập nhật' : 'Thư mời phỏng vấn' }}
              </div>
              <h1 style="margin:18px 0 0;font-size:30px;line-height:1.25;font-weight:800;">
                {{ $isRescheduled ? 'Lịch phỏng vấn của bạn đã được cập nhật' : 'Bạn có lịch phỏng vấn mới' }}
              </h1>
              <p style="margin:14px 0 0;font-size:15px;line-height:1.7;opacity:0.92;">
                {{ $isRescheduled ? 'Nhà tuyển dụng vừa thay đổi thông tin buổi phỏng vấn. Vui lòng kiểm tra lại các mốc thời gian bên dưới.' : 'Nhà tuyển dụng đã đặt lịch phỏng vấn cho hồ sơ ứng tuyển của bạn.' }}
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
                <tr>
                  <td style="width:180px;padding:16px 18px;background:#eff6ff;border-radius:16px;font-size:13px;font-weight:700;letter-spacing:0.18em;text-transform:uppercase;color:#2563eb;">Thời gian</td>
                  <td style="padding:16px 18px;background:#eff6ff;border-radius:16px;font-size:18px;font-weight:800;color:#1d4ed8;">{{ $interviewTime }}</td>
                </tr>
                @if (!empty($interviewMode))
                  <tr>
                    <td style="width:180px;padding:16px 18px;background:#f8fafc;border-radius:16px;font-size:13px;font-weight:700;letter-spacing:0.18em;text-transform:uppercase;color:#64748b;">Hình thức</td>
                    <td style="padding:16px 18px;background:#f8fafc;border-radius:16px;font-size:16px;font-weight:700;color:#0f172a;">{{ $interviewMode }}</td>
                  </tr>
                @endif
                @if (!empty($interviewerName))
                  <tr>
                    <td style="width:180px;padding:16px 18px;background:#f8fafc;border-radius:16px;font-size:13px;font-weight:700;letter-spacing:0.18em;text-transform:uppercase;color:#64748b;">Người phỏng vấn</td>
                    <td style="padding:16px 18px;background:#f8fafc;border-radius:16px;font-size:16px;font-weight:700;color:#0f172a;">{{ $interviewerName }}</td>
                  </tr>
                @endif
                @if (!empty($locationOrLink))
                  <tr>
                    <td style="width:180px;padding:16px 18px;background:#f8fafc;border-radius:16px;font-size:13px;font-weight:700;letter-spacing:0.18em;text-transform:uppercase;color:#64748b;">Link / địa điểm</td>
                    <td style="padding:16px 18px;background:#f8fafc;border-radius:16px;font-size:16px;font-weight:700;color:#0f172a;word-break:break-word;">{{ $locationOrLink }}</td>
                  </tr>
                @endif
              </table>

              <div style="margin-top:22px;padding:22px;border-radius:18px;background:#f8fafc;border:1px solid #dbeafe;">
                <p style="margin:0;font-size:15px;line-height:1.8;color:#334155;">
                  Vui lòng đăng nhập vào hệ thống để xác nhận bạn sẽ tham gia hay không thể tham gia buổi phỏng vấn này. Nếu có thay đổi đột xuất, hãy cập nhật phản hồi sớm để nhà tuyển dụng hỗ trợ sắp xếp.
                </p>
              </div>

              <div style="margin-top:28px;text-align:center;">
                <a href="{{ $actionUrl }}" style="display:inline-block;padding:14px 26px;border-radius:14px;background:#2463eb;color:#ffffff;text-decoration:none;font-size:15px;font-weight:700;">
                  Xem và xác nhận lịch phỏng vấn
                </a>
              </div>
            </td>
          </tr>
        </table>
      </td>
    </tr>
  </table>
</body>
</html>

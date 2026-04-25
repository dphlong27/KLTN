# Tổng Hợp Trạng Thái Tính Năng So Với Roadmap

source .venv/bin/activate
uvicorn app.main:app --reload --host 127.0.0.1 --port 8001

Ngày rà soát: 25/04/2026

Tài liệu này tổng hợp trạng thái tính năng hiện tại của hệ thống sau khi đối chiếu codebase với `BE/docs/FEATURE_ENHANCEMENT_ROADMAP.md`.

Phạm vi rà soát:

- Backend Laravel: `BE/routes/api.php`, controllers, services, models, migrations, console schedule, broadcasting channels.
- Frontend Vue: router, services API, các màn hình Guest/Candidate/Employer/Admin, composables realtime/notification.
- AI FastAPI: routers, schemas, services cho parsing, matching, generation, chatbot, mock interview, semantic search, CV tailoring, interview copilot.

Ghi chú: Đã bổ sung kiểm thử mở rộng cho các flow mới. Backend full suite cần chạy lại trên môi trường PHP >= 8.4 vì Composer hiện chặn `artisan test` khi máy đang dùng PHP 8.3.x.

---

## 1. Tóm Tắt Nhanh

### 1.1. Nhóm đã thực hiện tốt

- Xác thực, phân quyền cơ bản theo vai trò ứng viên / nhà tuyển dụng / admin.
- Quản lý hồ sơ ứng viên, CV upload, CV builder, CV template, CV preview/print PDF phía frontend.
- AI parsing CV/JD, matching, career report, chatbot tư vấn nghề nghiệp, mock interview.
- One-click CV Tailoring theo JD đã có API, AI service, fallback và UI trong trang chi tiết job.
- Employer flow đã mở rộng mạnh: quản lý công ty, HR nội bộ, phân quyền nội bộ, tin tuyển dụng, JD parsing, duyệt ứng tuyển.
- AI Shortlist cho nhà tuyển dụng đã có chấm điểm hybrid, giải thích AI, so sánh ứng viên.
- Interview Copilot đã có sinh câu hỏi, rubric, tóm tắt ứng viên và đánh giá sau phỏng vấn.
- Offer / nhận việc đã hoàn chỉnh flow gửi offer, ứng viên chấp nhận/từ chối qua UI/email, notification, realtime, audit log và onboarding sau khi nhận offer.
- Nhiều vòng phỏng vấn đã chuẩn hóa bằng bảng riêng, timeline UI, xác nhận từng vòng, notification/email và Copilot theo từng vòng.
- AI Usage Dashboard đã có log request AI, fallback, lỗi, latency và màn admin theo dõi theo ngày/tính năng.
- Follow công ty, job alert realtime và Smart Job Alert theo CV/skill khi công ty đăng/mở lại job.
- Re-engagement Engine đã có API insight, notification định kỳ cho tin đã lưu sắp hết hạn / đã lưu lâu chưa ứng tuyển và gợi ý job tương tự.
- Career Path Simulator đã tích hợp trực tiếp vào AI Career Chat: khi người dùng hỏi về lộ trình/roadmap, chatbot tự sinh kế hoạch 30/60/90 ngày theo hồ sơ, skill gap và job phù hợp.
- CV Builder AI Writing đã có API sinh gợi ý summary/objective/kinh nghiệm/dự án/kỹ năng, fallback nội bộ khi AI service chưa sẵn sàng và UI áp gợi ý trực tiếp vào builder.
- Audit log thống nhất cho admin/employer, có UI xem log.
- Admin flow có nhiều màn quản lý: user, company, profile, skill, industry, job, application, matching, career advising, CV template, stats.

### 1.2. Nhóm chưa hoàn thiện / còn thiếu rõ ràng

- Export calendar chưa có.
- Premium / gói trả phí / MoMo chưa có route, model, migration hoặc UI vận hành.
- Follow ngành nghề/kỹ năng độc lập chưa có; Smart Job Alert theo công ty follow + CV/skill đã có.
- Test coverage mở rộng đã được bổ sung cho các flow mới trọng yếu; còn nên chạy lại backend full suite sau khi nâng PHP lên >= 8.4.

---

## 2. Tính Năng Đã Thực Hiện

### 2.1. Nền tảng xác thực và tài khoản

Trạng thái: Hoàn thành ở mức sản phẩm cơ bản.

Đã có:

- Đăng ký, đăng nhập, đăng xuất bằng Sanctum token.
- Đăng nhập Google.
- Quên mật khẩu, đặt lại mật khẩu.
- Xác thực email và gửi lại email xác thực.
- Cập nhật hồ sơ cá nhân, đổi mật khẩu, avatar public.
- Router frontend có guard theo vai trò.
- Response lỗi auth/role đã có `code` và message rõ theo ngữ cảnh: chưa đăng nhập, token hết hạn, sai vai trò, tài khoản bị khóa, chưa thuộc công ty, vai trò nội bộ không đủ quyền.

Các file chính:

- `BE/app/Http/Controllers/Api/AuthController.php`
- `BE/routes/api.php`
- `FE/src/router/index.js`
- `FE/src/composables/useAuth.js`

Nên cải thiện:

- Bổ sung test đăng ký, đăng nhập, xác thực email, Google callback, reset password.

---

### 2.2. Public flow

Trạng thái: Hoàn thành khá đầy đủ.

Đã có:

- Landing page.
- Danh sách và chi tiết việc làm.
- Danh sách và chi tiết công ty.
- Danh sách và chi tiết ngành nghề.
- Danh sách và chi tiết kỹ năng.
- Tìm kiếm việc làm theo bộ lọc.
- Semantic search việc làm qua AI service.
- Lưu tin từ public job list/job detail khi đã đăng nhập.
- Follow/unfollow công ty từ trang công ty.

Các file chính:

- `BE/app/Http/Controllers/Api/TinTuyenDungController.php`
- `BE/app/Http/Controllers/Api/CongTyController.php`
- `BE/app/Http/Controllers/Api/SemanticSearchController.php`
- `FE/src/components/Guest/JobSearchPage.vue`
- `FE/src/components/Guest/JobDetailPage.vue`
- `FE/src/components/Guest/CompanyDetailPage.vue`

Nên cải thiện:

- SEO/meta cho job/company/industry/skill.
- Gợi ý việc làm liên quan và công ty liên quan.
- Alert theo ngành/kỹ năng vẫn chưa có.

---

### 2.3. Candidate flow

Trạng thái: Hoàn thành tốt, có nhiều tính năng AI.

Đã có:

- Quản lý hồ sơ/CV của ứng viên.
- Upload CV và parse CV bằng AI.
- CV Builder lưu dữ liệu có cấu trúc: kỹ năng, kinh nghiệm, học vấn, dự án, chứng chỉ.
- Chọn template CV, layout, màu/phong cách, vị trí mục tiêu, ngành mục tiêu.
- AI Writing trong CV Builder: sinh và áp dụng gợi ý cho mô tả bản thân, mục tiêu nghề nghiệp, mô tả kinh nghiệm, mô tả dự án/thành tựu và kỹ năng.
- Upload ảnh riêng cho CV hoặc dùng ảnh profile.
- Preview CV và mở trang print để tải PDF bằng chức năng in của browser.
- Quản lý kỹ năng cá nhân và chứng chỉ.
- Lưu tin, danh sách tin đã lưu.
- Theo dõi công ty, danh sách công ty đã theo dõi, realtime cập nhật job mới.
- Ứng tuyển, cập nhật thư xin việc, rút đơn.
- Xác nhận/thông báo tham gia phỏng vấn từ UI và qua link email.
- Sinh cover letter bằng AI và xác nhận thư.
- Xem kết quả matching, sinh lại matching theo hồ sơ.
- Career report.
- AI Career Chat.
- Mock Interview có câu hỏi, trả lời, đánh giá, streaming và report.
- One-click CV Tailoring từ job detail, có preview và lưu thành CV mới.

Các file chính:

- `BE/app/Http/Controllers/Api/HoSoController.php`
- `BE/app/Http/Controllers/Api/CvParsingController.php`
- `BE/app/Http/Controllers/Api/CvTailoringController.php`
- `BE/app/Http/Controllers/Api/UngVienUngTuyenController.php`
- `BE/app/Http/Controllers/Api/CoverLetterController.php`
- `BE/app/Http/Controllers/Api/MatchingController.php`
- `BE/app/Http/Controllers/Api/CareerReportController.php`
- `BE/app/Http/Controllers/Api/MockInterviewController.php`
- `FE/src/components/Dashboard/CvBuilderPage.vue`
- `FE/src/components/Dashboard/MyCvPage.vue`
- `FE/src/components/Dashboard/ApplicationsPage.vue`
- `FE/src/components/Dashboard/MatchedJobsPage.vue`
- `FE/src/components/Dashboard/CareerReportPage.vue`
- `FE/src/components/Dashboard/AICenterChatPage.vue`
- `FE/src/components/Dashboard/AICenterMockInterviewPage.vue`
- `FE/src/components/Guest/JobDetailPage.vue`

Nên cải thiện:

- Export PDF nên có phương án server-side nếu cần file PDF nhất quán khi demo/chấm.
- Cần lưu/versioning nhiều bản CV rõ hơn: clone, lịch sử chỉnh sửa, CV gốc vs CV tailor.
- Cần gợi ý học tập theo skill gap từ career report/matching.

---

### 2.4. Employer flow

Trạng thái: Đã mở rộng mạnh so với MVP.

Đã có:

- Employer home/dashboard.
- Tạo và cập nhật công ty.
- Quản lý thành viên HR nội bộ.
- Lời mời tham gia công ty cho email đã/chưa đăng ký.
- Vai trò nội bộ: owner, admin_hr, recruiter, interviewer, viewer.
- Middleware kiểm tra vai trò nội bộ công ty.
- Phân quyền xử lý job/application theo vai trò và HR phụ trách.
- Tạo, sửa, tắt/mở, xóa tin tuyển dụng.
- Gán HR phụ trách tin tuyển dụng.
- Parse JD bằng AI.
- Danh sách ứng viên/cv công khai.
- Danh sách ứng tuyển theo công ty/job/status/HR phụ trách.
- Cập nhật trạng thái ứng tuyển, lịch phỏng vấn, hình thức, link, người phỏng vấn, kết quả, ghi chú.
- Gửi email lịch phỏng vấn và gửi lại email.
- Notification cho ứng viên khi có lịch phỏng vấn/kết quả.
- Realtime cập nhật ứng tuyển trên kênh công ty.
- AI Shortlist theo JD từ CV công khai hoặc CV đã ứng tuyển.
- So sánh 2-5 ứng viên trong shortlist.
- Interview Copilot: sinh tóm tắt, focus area, câu hỏi, rubric, red flags.
- Đánh giá sau phỏng vấn bằng ghi chú/scores/decision.
- Nhiều vòng phỏng vấn: bảng `interview_rounds`, mỗi vòng có tên, loại vòng, thứ tự, lịch, hình thức, interviewer, xác nhận tham gia, kết quả, điểm, ghi chú và rubric Copilot riêng.
- Offer / nhận việc: employer gửi/cập nhật offer, ứng viên chấp nhận/từ chối offer trên UI hoặc link email đã ký, có trạng thái offer riêng, hạn phản hồi, notification, realtime, audit log và checklist onboarding sau khi offer được chấp nhận.
- Audit log phía employer.

Các file chính:

- `BE/app/Models/CongTy.php`
- `BE/app/Http/Middleware/KiemTraVaiTroNoiBoCongTy.php`
- `BE/app/Http/Controllers/Api/NhaTuyenDungCongTyController.php`
- `BE/app/Http/Controllers/Api/NhaTuyenDungTinTuyenDungController.php`
- `BE/app/Http/Controllers/Api/NhaTuyenDungUngTuyenController.php`
- `BE/app/Http/Controllers/Api/NhaTuyenDungShortlistController.php`
- `BE/app/Http/Controllers/Api/NhaTuyenDungAuditLogController.php`
- `BE/app/Models/InterviewRound.php`
- `BE/app/Http/Requests/UngTuyen/GuiOfferRequest.php`
- `BE/app/Http/Requests/UngTuyen/PhanHoiOfferRequest.php`
- `BE/app/Notifications/OfferLetterNotification.php`
- `FE/src/components/Employer/EmployerHomePage.vue`
- `FE/src/components/Employer/EmployerDashboardPage.vue`
- `FE/src/components/Employer/EmployerCompanyPage.vue`
- `FE/src/components/Employer/EmployerHrManagementPanel.vue`
- `FE/src/components/Employer/EmployerJobsPage.vue`
- `FE/src/components/Employer/EmployerJobDetailPage.vue`
- `FE/src/components/Employer/EmployerInterviewsPage.vue`
- `FE/src/components/Employer/EmployerAuditLogPage.vue`
- `FE/src/components/Dashboard/ApplicationsPage.vue`

Nên cải thiện:

- Cần export calendar `.ics` hoặc Google Calendar link.
- Có thể cải thiện timeline ứng tuyển tổng hợp để gom cả trạng thái, vòng phỏng vấn và offer vào một luồng duy nhất.

---

### 2.5. Admin flow

Trạng thái: Hoàn thành rộng ở mức CRUD/quản trị.

Đã có:

- Admin dashboard.
- Quản lý user: danh sách, tạo, sửa, khóa/mở khóa, xóa, thống kê.
- Quản lý công ty: danh sách, tạo, sửa, đổi trạng thái, xóa, thống kê.
- Quản lý hồ sơ ứng viên: danh sách, chi tiết, ẩn/công khai, xóa mềm, khôi phục, xóa vĩnh viễn, thống kê.
- Quản lý ngành nghề và kỹ năng.
- Quản lý kỹ năng người dùng.
- Quản lý tin tuyển dụng.
- Quản lý ứng tuyển.
- Quản lý lịch sử matching.
- Quản lý báo cáo/tư vấn nghề nghiệp.
- Market dashboard/statistics.
- Quản lý CV template: tạo, sửa, bật/tắt, xóa.
- Audit log toàn hệ thống.
- AI Usage Dashboard: thống kê request AI theo kỳ, tỉ lệ thành công/lỗi/fallback, latency, top feature, request chậm và log chi tiết.

Các file chính:

- `BE/app/Http/Controllers/Api/Admin/*`
- `BE/app/Models/AiUsageLog.php`
- `BE/app/Services/Ai/AiUsageLogger.php`
- `FE/src/components/Admin/*`
- `FE/src/components/Audit/AuditLogTablePage.vue`

Nên cải thiện:

- Market dashboard có thể thêm xu hướng theo thời gian, top company, top skill hot, hiệu quả tuyển dụng.
- AI Usage Dashboard có thể bổ sung token/cost thực tế nếu AI service trả metadata token/cost thống nhất.
- Cần test CRUD và phân quyền admin.

---

### 2.6. AI service

Trạng thái: Đã có nhiều module, phần lớn có fallback ở backend.

Đã có:

- Parse CV.
- Parse JD.
- Match CV-JD.
- Cover letter generation.
- Career report.
- Semantic job search.
- Career chatbot và streaming.
- Mock interview question/evaluation/report.
- CV tailoring.
- Interview copilot generate/evaluate.

Các file chính:

- `AI/app/main.py`
- `AI/app/routers/*`
- `AI/app/services/*`
- `BE/app/Services/Ai/AiClientService.php`

Nên cải thiện:

- Chuẩn hóa version/model metadata cho mọi response.
- Bổ sung token/cost thực tế vào AI usage log khi AI service/provider trả usage metadata.
- Tăng regression test cho CV tailoring, shortlist, interview copilot.
- Tách rõ provider thật và fallback để demo dễ kiểm soát.

---

### 2.7. Notification, realtime và audit log

Trạng thái: Đã triển khai tốt, còn cần chuẩn hóa sâu hơn.

Đã có notification:

- Bảng `app_notifications`.
- API lấy danh sách, unread count, mark read, mark all read.
- Notification center frontend dùng DB notification + realtime private channel `user.{id}`.
- App notification cho lịch phỏng vấn, trạng thái ứng tuyển, nộp đơn, ứng viên rút đơn, job mới từ công ty theo dõi, reminder.
- Realtime với Laravel Echo/Reverb.

Đã có audit log:

- Bảng `audit_logs` mới.
- Service `AuditLogService`.
- Migration backfill/drop từ `hr_audit_logs`.
- Admin audit log.
- Employer audit log.
- Audit các thao tác quan trọng: user/company/job/application/template/follow/HR member.

Các file chính:

- `BE/app/Models/AppNotification.php`
- `BE/app/Services/AppNotificationService.php`
- `BE/app/Http/Controllers/Api/NotificationController.php`
- `BE/app/Models/AuditLog.php`
- `BE/app/Services/AuditLogService.php`
- `BE/app/Http/Controllers/Api/Admin/AdminAuditLogController.php`
- `BE/app/Http/Controllers/Api/NhaTuyenDungAuditLogController.php`
- `FE/src/layouts/components/AppNotificationCenter.vue`
- `FE/src/composables/useNotifications.js`
- `FE/src/services/realtime.js`

Nên cải thiện:

- Chuẩn hóa taxonomy `loai` notification và `action` audit log.
- Thêm deep link nhất quán tới đúng modal/entity, không chỉ page.
- Thêm màn timeline theo từng ứng tuyển.
- Thêm retention/cleanup policy cho notification/log.

---

## 3. Đối Chiếu Với Roadmap

| Nhóm trong roadmap | Trạng thái hiện tại | Nhận xét |
| --- | --- | --- |
| Mở rộng nhà tuyển dụng nhiều HR | Đã thực hiện phần lớn | Có bảng thành viên công ty, vai trò nội bộ, lời mời, middleware, UI quản lý HR. Cần kiểm thử phân quyền kỹ hơn. |
| Module phỏng vấn nâng cao | Đã thực hiện phần nhiều | Có bảng vòng phỏng vấn riêng, nhiều lịch/interviewer/kết quả/rubric, xác nhận từng vòng, email/notification/realtime và timeline UI. Chưa có export calendar. |
| Offer / nhận việc | Đã thực hiện | Có trạng thái offer riêng, API employer gửi offer, API/UI/email để ứng viên chấp nhận/từ chối, notification realtime, audit log và onboarding checklist sau khi nhận offer. |
| Audit log | Đã thực hiện tốt | Có audit log thống nhất cho admin/employer và nhiều action quan trọng. Cần thêm timeline theo entity. |
| Notification center | Đã thực hiện tốt | Đã lưu DB, unread/read, realtime, deep link cơ bản. Cần chuẩn hóa type/deep link nâng cao. |
| AI CV Builder | Đã thực hiện | Có form/template/preview/print, preset theo vị trí/phong cách và AI Writing cho summary/objective/experience/project/skills với fallback nội bộ. |
| One-click CV Tailoring | Đã thực hiện | Có API, AI service, fallback, preview/save, tạo matching sau khi lưu. Cần UX versioning tốt hơn. |
| AI Shortlist | Đã thực hiện | Có ranking hybrid, AI explanation, skill gap, confidence, compare. Nên thêm lưu kết quả shortlist và audit/feedback HR. |
| Interview Copilot | Đã thực hiện | Có generate/evaluate, câu hỏi, rubric, red flags, summary và lưu theo từng vòng phỏng vấn khi chọn `interview_round_id`. |
| AI Usage Dashboard | Đã thực hiện | Có bảng `ai_usage_logs`, logging ở `AiClientService`, log fallback chính và màn admin `/admin/ai-usage`. Chưa có token/cost thật vì AI service chưa chuẩn hóa metadata này. |
| Follow nhà tuyển dụng + Job Alert | Đã thực hiện tốt | Có follow/unfollow, page followed companies, realtime job activity, DB notification và Smart Job Alert theo CV/skill fit khi job active. Follow ngành/kỹ năng độc lập có thể làm thêm. |
| Career Path Simulator | Đã thực hiện | Tích hợp vào AI Career Chat, bắt intent lộ trình/roadmap/30-60-90 ngày và sinh kế hoạch theo hồ sơ, skill gap, career report, matching/job gần nhất. |
| Re-engagement Engine | Đã thực hiện | Có service, API insight, command schedule và notification cho tin đã lưu sắp hết hạn, tin đã lưu lâu chưa ứng tuyển, job tương tự với tin đã lưu. |
| Test coverage mở rộng | Đã bổ sung | Có backend feature test cho Re-engagement, Smart Job Alert, Offer/Onboarding, Interview Round, AI Usage Dashboard; AI unittest cho Career Path Simulator; frontend smoke test không phụ thuộc browser cho các flow demo chính. |
| Premium / MoMo / thanh toán | Chưa thực hiện | Chưa thấy module gói, thanh toán, quyền premium. |

---

## 4. Tính Năng Chưa Thực Hiện Xong / Vừa Hoàn Thành

### 4.1. Offer / nhận việc

Trạng thái: Đã hoàn thành trong đợt cập nhật 24/04/2026.

Đã có:

- Migration thêm `trang_thai_offer`, `han_phan_hoi_offer`, `ghi_chu_phan_hoi_offer`.
- Model `UngTuyen` có trạng thái offer riêng: `Chưa gửi`, `Đã gửi offer`, `Đã nhận việc`, `Từ chối offer`.
- Employer API gửi/cập nhật offer: `POST /api/v1/nha-tuyen-dung/ung-tuyens/{id}/gui-offer`.
- Candidate API phản hồi offer: `PATCH /api/v1/ung-vien/ung-tuyens/{id}/phan-hoi-offer`.
- Candidate phản hồi offer qua link email đã ký: `GET /api/v1/ung-vien/ung-tuyens/{id}/phan-hoi-offer/email/{action}`.
- Email offer hiển thị vị trí, công ty, hạn phản hồi, tóm tắt offer, link tài liệu và nút chấp nhận/từ chối.
- App notification cho ứng viên khi nhận offer và cho employer khi ứng viên phản hồi.
- Realtime `ApplicationChanged` có payload `trang_thai_offer`.
- Audit log cho `employer_offer_sent`, `candidate_offer_accepted`, `candidate_offer_declined` và các biến thể phản hồi qua email.
- UI employer trong `EmployerInterviewsPage.vue` để nhập tóm tắt offer, link tài liệu, hạn phản hồi và gửi/cập nhật offer.
- UI candidate trong `ApplicationsPage.vue` để xem offer, mở tài liệu, chấp nhận/từ chối offer.

Có thể cải thiện thêm:

- Module onboarding sau khi ứng viên chấp nhận offer: plan, checklist, tài liệu cần chuẩn bị, ngày bắt đầu, ghi chú HR/ứng viên.

### 4.1.1. Onboarding sau offer

Trạng thái: Đã hoàn thành trong đợt cập nhật 24/04/2026.

Đã có:

- Migration `onboarding_plans` và `onboarding_tasks`.
- Khi ứng viên chấp nhận offer trên UI/email, hệ thống tự tạo onboarding plan mặc định.
- Employer API xem/cập nhật onboarding, thêm/sửa/xóa checklist.
- Candidate API xem onboarding và cập nhật trạng thái task do ứng viên phụ trách.
- Notification cho ứng viên khi onboarding được tạo/cập nhật.
- UI employer trong màn xử lý ứng tuyển: ngày bắt đầu, địa điểm, trạng thái, lời nhắn, tài liệu cần chuẩn bị, ghi chú nội bộ và checklist.
- UI ứng viên trong trang ứng tuyển: xem onboarding, tài liệu cần chuẩn bị, tiến độ và đánh dấu task.

Còn có thể cải thiện:

- Gửi email onboarding riêng nếu cần.
- Timeline tổng hợp gom offer và onboarding vào một luồng duy nhất.
- Cho phép employer cấu hình mẫu offer theo công ty/vị trí.
- Xuất offer thành PDF server-side nếu cần file chính thức.

### 4.2. Nhiều vòng phỏng vấn

Trạng thái: Đã hoàn thành trong đợt cập nhật 24/04/2026.

Đã có:

- Migration và model `InterviewRound`.
- API employer: danh sách, tạo, cập nhật, xóa vòng phỏng vấn theo từng đơn ứng tuyển.
- API candidate: xác nhận tham gia/từ chối từng vòng phỏng vấn qua UI và qua link email đã ký.
- Mỗi vòng có `thu_tu`, `ten_vong`, `loai_vong`, `trang_thai`, `ngay_hen_phong_van`, `hinh_thuc_phong_van`, `nguoi_phong_van`, `interviewer_user_id`, `link_phong_van`, `trang_thai_tham_gia`, `ket_qua`, `diem_so`, `ghi_chu`, `rubric_danh_gia_json`.
- Email lịch phỏng vấn có thể gửi theo từng vòng.
- App notification cho ứng viên khi có vòng mới/đổi lịch và cho employer khi ứng viên phản hồi từng vòng.
- Realtime `ApplicationChanged` cho các sự kiện tạo/cập nhật/xóa/phản hồi vòng.
- Audit log cho tạo, cập nhật, đổi lịch, xóa và phản hồi vòng phỏng vấn.
- Interview Copilot nhận `interview_round_id`, lưu rubric/đánh giá vào từng vòng; đồng thời vẫn sync snapshot mới nhất về `ung_tuyens` để tương thích flow cũ.
- UI employer trong `EmployerInterviewsPage.vue` có timeline vòng, form tạo/sửa/xóa vòng, chọn vòng để chạy Copilot.
- UI candidate trong `ApplicationsPage.vue` hiển thị timeline vòng và cho xác nhận/từ chối từng vòng.

Có thể cải thiện thêm:

- Export calendar `.ics` theo từng vòng.
- Timeline tổng hợp một dòng cho toàn bộ đơn ứng tuyển: nộp hồ sơ, từng vòng, offer, nhận việc.

### 4.3. Export calendar

Hiện trạng:

- Chưa thấy route/service tạo `.ics` hoặc link Google Calendar.

Cần làm:

- Tạo endpoint export lịch phỏng vấn `.ics`.
- Thêm button trong email và UI candidate/employer.
- Có timezone rõ ràng `Asia/Ho_Chi_Minh`.

### 4.4. Premium / thanh toán / MoMo

Hiện trạng:

- Roadmap có `Thanh toán momo`, `Premium`, nhưng chưa thấy model/migration/controller/UI tương ứng.

Cần làm:

- Thiết kế gói premium.
- Bảng subscriptions/orders/payments.
- Tích hợp MoMo sandbox.
- Middleware/feature flag kiểm tra quyền premium.
- UI quản lý gói cho employer/admin.

### 4.5. AI usage dashboard

Trạng thái: Đã hoàn thành trong đợt cập nhật 24/04/2026.

Đã có:

- Migration `ai_usage_logs` lưu feature, endpoint, provider/model/version, status, fallback, duration, HTTP status, lỗi, user/company, request reference và metadata.
- Model `AiUsageLog`.
- Service `AiUsageLogger` ghi log an toàn, không làm hỏng flow chính nếu ghi log lỗi.
- `AiClientService` tự động log success/error cho các call AI service: parse CV/JD, matching, cover letter, career report, CV tailoring, semantic search, chat/stream, mock interview, interview copilot.
- Các fallback chính đã ghi log riêng: CV Tailoring, Interview Copilot generate/evaluate, AI Shortlist explanation, Career Chat stream fallback.
- Admin API:
  - `GET /api/v1/admin/ai-usage/overview`
  - `GET /api/v1/admin/ai-usage/logs`
  - `GET /api/v1/admin/ai-usage/features`
- Frontend admin `/admin/ai-usage` có summary cards, trend theo ngày, top feature, request chậm, lỗi/fallback gần đây, bảng log có filter.

Còn có thể cải thiện:

- Bổ sung token/cost khi AI service trả usage metadata chuẩn.
- Thêm cảnh báo tự động khi error rate/fallback rate vượt ngưỡng.
- Thêm export CSV cho log usage.

### 4.6. Smart alert theo skill/CV

Trạng thái: Đã hoàn thành trong đợt cập nhật 24/04/2026.

Đã có:

- Migration `smart_job_alerts` lưu ứng viên, job, công ty, CV phù hợp nhất, match score, match level, kỹ năng khớp/thiếu, ngành khớp, lý do gợi ý và trạng thái đọc/bỏ qua.
- Service `SmartJobAlertService` chấm nhanh dựa trên kỹ năng CV/user skill, ngành mục tiêu, tiêu đề/vị trí mục tiêu, kinh nghiệm, học vấn và địa điểm.
- Khi employer tạo job active hoặc mở lại job, hệ thống tự sinh Smart Job Alert cho follower đủ ngưỡng và gửi notification riêng.
- Follower không đủ ngưỡng vẫn nhận job activity notification thường.
- API ứng viên:
  - `GET /api/v1/ung-vien/smart-job-alerts`
  - `GET /api/v1/ung-vien/smart-job-alerts/thong-ke`
  - `PATCH /api/v1/ung-vien/smart-job-alerts/{id}/read`
  - `PATCH /api/v1/ung-vien/smart-job-alerts/{id}/dismiss`
- Frontend candidate `/smart-job-alerts` có dashboard thống kê, filter, điểm match, lý do gợi ý, kỹ năng khớp/thiếu và thao tác đọc/bỏ qua.
- Trang công ty đã follow hiển thị badge Smart Match cho các job gần đây nếu có alert.

Còn có thể cải thiện:

- Thêm setting bật/tắt nhận smart alert hoặc chỉnh ngưỡng điểm theo ứng viên.
- Mở rộng follow ngành/kỹ năng độc lập, không chỉ theo công ty đã follow.
- Có thể dùng AI service để giải thích match sâu hơn sau khi rule-based lọc nhanh.

### 4.7. Re-engagement Engine

Trạng thái: Đã hoàn thành trong đợt cập nhật 25/04/2026.

Đã có:

- Service `ReEngagementService` phân tích tin đã lưu của ứng viên, loại trừ các job đã ứng tuyển và các job đã hết hạn.
- API ứng viên `GET /api/v1/ung-vien/re-engagement/insights` trả về:
  - Tin đã lưu sắp hết hạn trong 3 ngày.
  - Tin đã lưu lâu nhưng chưa ứng tuyển.
  - Job tương tự dựa trên ngành nghề, kỹ năng yêu cầu, tiêu đề/vị trí và địa điểm.
- Command `reengagement:run` chạy nền, có `--dry-run` để kiểm tra an toàn.
- Scheduler chạy hằng ngày lúc 08:00 để tạo notification re-engagement.
- Notification types:
  - `candidate_saved_job_expiring`
  - `candidate_saved_job_follow_up`
  - `candidate_similar_job_suggestion`
- Cơ chế chống spam bằng cách kiểm tra notification đã gửi gần đây theo user/job/type.
- Frontend trang `/saved-jobs` hiển thị khối “Nhắc bạn quay lại đúng lúc” với các nhóm sắp hết hạn, cần xem lại và job tương tự.

Còn có thể cải thiện:

- Bổ sung tracking lịch sử xem job để gợi ý theo cả hành vi xem, không chỉ tin đã lưu.
- Cho ứng viên cấu hình tần suất nhận nhắc lại.
- Thêm email digest hằng tuần nếu cần tăng khả năng quay lại hệ thống.

### 4.8. Career Path Simulator trong AI Career Chat

Trạng thái: Đã hoàn thành trong đợt cập nhật 25/04/2026.

Đã có:

- Không tách màn riêng; tích hợp trực tiếp vào AI Career Chat theo yêu cầu.
- Chatbot tự nhận intent `career_path_simulator` khi người dùng hỏi về lộ trình, roadmap, kế hoạch 3 tháng hoặc 30/60/90 ngày.
- Context chatbot đã bổ sung vị trí mục tiêu, ngành mục tiêu, kỹ năng builder, kỹ năng parse CV, career report và matching/job gần nhất.
- AI service sinh lộ trình 30/60/90 ngày gồm:
  - Mục tiêu chính và mức hiện tại.
  - Nền tảng đang có.
  - Skill gap cần bù.
  - Kế hoạch 30 ngày, 60 ngày, 90 ngày.
  - Mốc kiểm tra tiến độ.
  - Hướng thay thế nếu career report có đề xuất.
- Streaming chat và non-stream chat đều trả metadata intent mới, tiếp tục lưu trong `AiChatMessage.metadata`.
- Regression test AI chatbot đã cập nhật để kiểm tra intent và format 30/60/90 ngày.

Còn có thể cải thiện:

- Lưu snapshot roadmap riêng nếu sau này muốn ứng viên đánh dấu tiến độ từng milestone.
- Gợi ý khóa học/tài nguyên học tập thật theo từng skill gap.

---

## 5. Tính Năng Cần Cải Thiện

### 5.1. CV Builder

Đã tốt:

- Nhiều template/layout.
- Gợi ý template theo phong cách/vị trí.
- AI viết lại summary, objective, mô tả kinh nghiệm/dự án và gợi ý kỹ năng; có fallback nội bộ nếu AI service chưa có endpoint tương ứng.
- Có field dự án linh hoạt theo ngành.
- Có print preview.

Cần cải thiện:

- AI kiểm tra thiếu thông tin trước khi xuất CV.
- Server-side PDF export nếu cần file ổn định.
- Lịch sử phiên bản CV.
- Clone CV gốc thành CV theo từng job.

### 5.2. AI Shortlist

Đã tốt:

- Ranking nhiều tiêu chí.
- AI explanation cho top profile.
- So sánh ứng viên.
- Confidence/warning.

Cần cải thiện:

- Lưu snapshot shortlist theo job để HR xem lại.
- Cho HR đánh dấu feedback: đúng/sai/phù hợp/không phù hợp.
- Dùng feedback để cải thiện ranking rule.
- Thêm bộ lọc candidate theo score, skill gap, source CV.

### 5.3. Interview Copilot

Đã tốt:

- Sinh câu hỏi, rubric, red flag, summary.
- Đánh giá sau phỏng vấn từ ghi chú và điểm.

Cần cải thiện:

- Gắn Copilot vào từng vòng phỏng vấn thay vì một JSON trên `ung_tuyens`.
- Cho interviewer chấm trực tiếp theo rubric trong UI.
- Tự đề xuất trạng thái tiếp theo nhưng vẫn để HR xác nhận.
- Export biên bản phỏng vấn.

### 5.4. Audit log

Đã tốt:

- Có log thống nhất, lọc/search ở admin/employer.

Cần cải thiện:

- Timeline theo entity: application/job/company/user/template.
- Hiển thị diff trước/sau thân thiện hơn.
- Chính sách retention và phân quyền xem log nhạy cảm.

### 5.5. Notification

Đã tốt:

- DB notification + realtime + read/unread.

Cần cải thiện:

- Deep link tới đúng modal hoặc tab cụ thể.
- Trang danh sách notification đầy đủ thay vì chỉ dropdown 10 item.
- Setting nhận thông báo theo loại.
- Gom thông báo trùng lặp.

### 5.6. Test và chất lượng

Hiện trạng:

- Backend đã bật `RefreshDatabase` cho Pest test trên SQLite in-memory và bổ sung helper/factory dùng chung cho company/job/application.
- Đã có feature test cho Re-engagement Engine, Smart Job Alert, Offer/Onboarding, Interview Round và AI Usage Dashboard.
- AI đã có regression test cho chatbot/mock interview và test riêng cho Career Path Simulator trong AI Career Chat.
- Frontend đã có smoke test dependency-free kiểm tra route/service/UI hook chính cho Smart Job Alert, Re-engagement, AI chat, interview round và offer/onboarding.

Cần cải thiện:

- Chạy lại `php artisan test` trên môi trường PHP >= 8.4 để xác nhận toàn bộ backend suite, vì vendor hiện yêu cầu PHP >= 8.4.
- Mở rộng tiếp backend feature tests cho auth, HR roles, audit log, notification, CV tailoring, shortlist và interview copilot nếu còn thời gian.
- Có thể bổ sung Playwright/E2E cho các flow demo chính khi frontend đã có test runner/browser test chính thức.

---

## 6. Đề Xuất Ưu Tiên Tiếp Theo

### Ưu tiên 1: Export calendar cho phỏng vấn

Lý do:

- Nhiều vòng phỏng vấn đã có lịch riêng.
- Export calendar là bước nhỏ nhưng làm trải nghiệm phỏng vấn chuyên nghiệp hơn.

Việc nên làm:

- Endpoint `.ics` theo `interview_round_id`.
- Nút thêm lịch trong email và UI ứng viên/employer.
- Timezone rõ `Asia/Ho_Chi_Minh`.

### Ưu tiên 2: CV Builder AI writing

Trạng thái: Đã thực hiện.

Đã có:

- API sinh summary/objective/experience/project/skill suggestions theo CV hiện tại, vị trí mục tiêu, ngành mục tiêu và tone.
- UI gợi ý nội dung theo từng section trong CV Builder.
- Fallback nội bộ và AI usage log/fallback log khi AI service chưa có endpoint `/generate/cv-builder-writing`.

Việc có thể làm thêm:

- Gắn trực tiếp JD cụ thể vào payload khi ứng viên muốn viết CV cho một tin tuyển dụng.
- Lưu lịch sử/chấp nhận/từ chối suggestion.

### Ưu tiên 3: Premium/MoMo

Lý do:

- Có giá trị business nhưng phạm vi lớn.
- Nên làm sau khi các flow tuyển dụng lõi ổn định.

Việc nên làm:

- Thiết kế package/premium feature.
- Tích hợp MoMo sandbox.
- Chặn/mở khóa AI Shortlist nâng cao, market insights, số lượt job nổi bật.

### Ưu tiên 4: Career Path Simulator

Trạng thái: Đã thực hiện.

Đã có:

- Tích hợp trực tiếp vào AI Career Chat, không tạo module tách rời.
- Tự nhận câu hỏi về lộ trình/roadmap/kế hoạch 3 tháng/30-60-90 ngày.
- Sinh mục tiêu 30/60/90 ngày theo hồ sơ, career report, skill gap và job phù hợp.

Việc có thể làm thêm:

- Lưu roadmap thành checklist tiến độ nếu cần theo dõi dài hạn.
- Gắn tài nguyên học tập cụ thể cho từng skill gap.

### Ưu tiên 5: Test coverage mở rộng

Trạng thái: Đã bổ sung.

Đã có:

- Backend feature test cho Offer/Onboarding, Interview Round, AI Usage Dashboard, Smart Job Alert và Re-engagement Engine.
- AI unittest cho Career Path Simulator tích hợp trong AI Career Chat.
- Frontend smoke test cho các flow demo chính.

Việc có thể làm thêm:

- Chạy `php artisan test` sau khi nâng PHP lên >= 8.4.
- Thêm Playwright/E2E nếu muốn kiểm thử thao tác thật trên trình duyệt.

---

## 7. Kết Luận

So với roadmap, hệ thống hiện tại đã triển khai được phần lớn các tính năng tạo giá trị cao:

- AI CV Builder có builder/template/print và AI Writing từng section.
- One-click CV Tailoring.
- AI Shortlist.
- Interview Copilot.
- Nhiều vòng phỏng vấn chuẩn hóa.
- Follow công ty + Job Alert.
- Offer / nhận việc.
- Onboarding sau offer.
- Audit log.
- Notification center DB + realtime.
- HR nội bộ và phân quyền employer.
- Re-engagement Engine cho tin đã lưu sắp hết hạn, tin đã lưu lâu chưa ứng tuyển và job tương tự.
- Career Path Simulator trong AI Career Chat với lộ trình 30/60/90 ngày.

Các khoảng trống lớn nhất còn lại là:

- Premium/MoMo.
- Export calendar.

Nếu mục tiêu là hoàn thiện khóa luận theo hướng demo nghiệp vụ tuyển dụng khép kín, có thể dừng ở phạm vi hiện tại và chỉ chạy xác nhận lại backend test sau khi nâng PHP >= 8.4. Nếu muốn mở rộng sản phẩm sau khóa luận, `Export calendar` và `Premium/MoMo` là hai hướng còn lại rõ nhất.

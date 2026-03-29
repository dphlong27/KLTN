source .venv/bin/activate
uvicorn app.main:app --reload --host 127.0.0.1 --port 8001

# Next Steps After DB Normalization

Tai lieu nay tong hop cac task can lam tiep theo sau khi da bo sung schema AI-ready vao `BE`.
Muc tieu la de ban co mot checklist ro rang, theo doi duoc thu tu thuc hien hop ly va khong bi loan pham vi.

## Nguyen tac trien khai

- Khong nhay vao chatbot hay mock interview ngay.
- Di theo thu tu: `BE relation -> BE-AI bridge -> CV Parser -> JD Parser -> Matching -> Generation -> Bao cao -> Nang cao`.
- Moi giai doan phai co dau ra chay duoc end-to-end.
- AI service chi xu ly AI, BE giu vai tro dieu phoi va tra API cho FE.

## Tong quan thu tu uu tien

1. Hoan thien model va relation trong `BE`
2. Tao `AiClientService` de BE goi sang AI
3. Dung skeleton service trong thu muc `AI`
4. Trien khai `CV Parser`
5. Trien khai `JD Parser`
6. Trien khai `Matching Engine`
7. Trien khai `Cover Letter Generator`
8. Trien khai `Career Report`
9. Neu con thoi gian: `Semantic Search`
10. Neu con thoi gian: `Career Chatbot`
11. Neu con thoi gian: `Mock Interview`
12. Cuoi cung moi den `Market Dashboard`

---

## Phase 1 - Hoan thien lop du lieu trong BE

### 1.1. Bo sung relation cho cac model da co

#### HoSo
- Them relation `hasOne(HoSoParsing::class)`
- Neu can, them relation den `AiChatSession` qua `related_ho_so_id`

#### TinTuyenDung
- Them relation `hasOne(TinTuyenDungParsing::class)`
- Them relation `hasMany(TinTuyenDungKyNang::class)`
- Neu can, them relation den `AiChatSession` qua `related_tin_tuyen_dung_id`

#### NguoiDung
- Them relation `hasMany(AiChatSession::class)`
- Neu can, them relation den `AiInterviewReport`

#### UngTuyen
- Dam bao su dung duoc `thu_xin_viec_ai`

#### KetQuaMatching
- Co the them logic helper neu can de render `chi_tiet_diem`, `explanation`

#### TuVanNgheNghiep
- Dam bao fillable/cast dung voi schema moi

### 1.2. Tao relation cho model moi

- `HoSoParsing -> belongsTo(HoSo)`
- `TinTuyenDungParsing -> belongsTo(TinTuyenDung)`
- `TinTuyenDungKyNang -> belongsTo(TinTuyenDung), belongsTo(KyNang)`
- `AiChatSession -> belongsTo(NguoiDung), belongsTo(HoSo), belongsTo(TinTuyenDung), hasMany(AiChatMessage)`
- `AiChatMessage -> belongsTo(AiChatSession)`
- `AiInterviewReport -> belongsTo(AiChatSession), belongsTo(NguoiDung), belongsTo(TinTuyenDung)`
- `MarketStatsDaily -> belongsTo(NganhNghe)`

### Done condition

- Tat ca model moi co `fillable`, `casts`, `relationships`
- Cac model cu duoc noi voi model moi de code service sau nay de hon

---

## Phase 2 - Tao cau noi BE -> AI

### 2.1. Tao service chung

Tao file:

- `BE/app/Services/Ai/AiClientService.php`

### 2.2. Method can co

- `parseCv(int $hoSoId, string $filePath): array`
- `parseJd(int $tinTuyenDungId, string $jobText): array`
- `matchCvJd(int $hoSoId, int $tinTuyenDungId): array`
- `generateCoverLetter(int $hoSoId, int $tinTuyenDungId): array`
- `generateCareerReport(int $hoSoId): array`
- De sau:
  - `semanticSearch(string $query): array`
  - `chat(array $payload): array`
  - `mockInterview(array $payload): array`

### 2.3. Cau hinh can them

Them env/config:

- `AI_SERVICE_URL`
- `AI_SERVICE_TIMEOUT`
- `LLM_PROVIDER`
- `LOCAL_LLM_MODEL`

### Done condition

- BE co the goi HTTP sang AI service va nhan duoc JSON response
- Co xu ly loi timeout / connection error / invalid response

---

## Phase 3 - Dung AI service skeleton trong thu muc AI

### 3.1. Cau truc thu muc

```text
AI/
  app/
    main.py
    core/
      config.py
      logger.py
    routers/
      cv_parser.py
      jd_parser.py
      matching.py
      generation.py
    services/
      cv_extractor.py
      cv_parser.py
      jd_parser.py
      skill_mapper.py
      matcher.py
      cover_letter.py
      career_report.py
    schemas/
      cv.py
      jd.py
      matching.py
      generation.py
  requirements.txt
  README.md
```

### 3.2. API skeleton can co

- `GET /health`
- `POST /parse/cv`
- `POST /parse/jd`
- `POST /match/cv-jd`
- `POST /generate/cover-letter`
- `POST /generate/career-report`

### Done condition

- AI service boot duoc local
- Cac endpoint tra mock response dung schema

---

## Phase 4 - CV Parser

### Muc tieu

Tu file CV da upload trong `ho_sos.file_cv`, AI co the:

- doc PDF
- trich xuat `raw_text`
- tim `email`, `phone`, `name`
- trich xuat `skills`
- trich xuat `experience`
- trich xuat `education`
- luu vao `ho_so_parsings`

### Task chi tiet

#### AI
- Tao `cv_extractor.py`
- Chon thu vien:
  - uu tien `pdfplumber`
  - fallback `PyMuPDF`
- Tao text normalize helper
- Viet regex:
  - email
  - phone
- Viet skill extraction:
  - doc tu bang `ky_nangs` hoac file dictionary
- Tao output:
  - `raw_text`
  - `parsed_name`
  - `parsed_email`
  - `parsed_phone`
  - `parsed_skills_json`
  - `parsed_experience_json`
  - `parsed_education_json`
  - `parse_status`
  - `parser_version`
  - `confidence_score`

#### BE
- Tao `CvParsingController`
- Them route:
  - `POST /api/v1/ung-vien/ho-sos/{id}/parse`
- Validate:
  - ho so phai thuoc user dang nhap
- Goi `AiClientService::parseCv(...)`
- Luu vao `ho_so_parsings`

#### Optional sync
- Tu `parsed_skills_json`, map sang `nguoi_dung_ky_nangs`
- Gan `nguon_du_lieu = cv_parser`

### Done condition

- Trigger parse CV tu API BE thanh cong
- Co record trong `ho_so_parsings`
- Parse duoc it nhat email/phone/skills co ban

---

## Phase 5 - JD Parser

### Muc tieu

Tu `tin_tuyen_dungs.mo_ta_cong_viec`, AI co the:

- tach `raw_text`
- tach skill
- tach requirement
- tach benefit
- tach salary
- tach location
- luu vao `tin_tuyen_dung_parsings`
- map sang `tin_tuyen_dung_ky_nangs`

### Task chi tiet

#### AI
- Tao `jd_parser.py`
- Viet section parser:
  - requirements
  - benefits
  - salary
  - location
- Viet skill matcher cho JD
- Sinh:
  - `parsed_skills_json`
  - `parsed_requirements_json`
  - `parsed_benefits_json`
  - `parsed_salary_json`
  - `parsed_location_json`

#### BE
- Tao `JdParsingController`
- Them route:
  - `POST /api/v1/nha-tuyen-dung/tin-tuyen-dungs/{id}/parse`
- Validate:
  - tin phai thuoc cong ty cua NTD dang nhap
- Goi `AiClientService::parseJd(...)`
- Luu `tin_tuyen_dung_parsings`
- Sync skill sang `tin_tuyen_dung_ky_nangs`

### Done condition

- Parse JD xong co du lieu trong `tin_tuyen_dung_parsings`
- Skill JD duoc map sang `tin_tuyen_dung_ky_nangs`

---

## Phase 6 - Matching Engine

### Muc tieu

Tinh diem phu hop giua 1 CV va 1 JD dua tren:

- skill match
- experience match
- education match
- text similarity

Va luu vao `ket_qua_matchings`.

### Task chi tiet

#### AI
- Tao `matcher.py`
- Implement:
  - skill score
  - experience score
  - education score
  - text similarity
- Cong thuc goi y:
  - skill: 50%
  - experience: 25%
  - education: 10%
  - text similarity: 15%
- Sinh output:
  - `diem_phu_hop`
  - `diem_ky_nang`
  - `diem_kinh_nghiem`
  - `diem_hoc_van`
  - `chi_tiet_diem`
  - `explanation`
  - `model_version`

#### BE
- Tao `MatchingController`
- Them route:
  - `POST /api/v1/ung-vien/ho-sos/{id}/matching`
- Validate:
  - CV thuoc user
  - JD hop le neu gui kem
- Goi `AiClientService::matchCvJd(...)`
- Upsert vao `ket_qua_matchings`

### Done condition

- Co ket qua matching cho it nhat 1 cap CV-JD
- Ung vien xem duoc danh sach matching bang API hien co

---

## Phase 7 - Cover Letter Generator

### Muc tieu

AI sinh ra ban nhap thu xin viec dua tren:

- CV da parse
- JD
- ket qua matching

### Task chi tiet

#### AI
- Tao `cover_letter.py`
- Tao provider abstraction:
  - local provider (Ollama)
  - cloud provider (de sau)
- Prompt dau vao:
  - ten ung vien neu co
  - diem manh
  - skill match
  - role/job title

#### BE
- Tao `CoverLetterController`
- Them route:
  - `POST /api/v1/ung-vien/ung-tuyens/generate-cover-letter`
- Goi AI service
- Luu output vao `ung_tuyens.thu_xin_viec_ai`

### Done condition

- Generate duoc thu xin viec nhap
- User co the sua va submit thanh `thu_xin_viec`

---

## Phase 8 - Career Report

### Muc tieu

Sinh bao cao tu van nghe nghiep tinh dua tren:

- CV parsed
- skill hien co
- JD trong he thong

### Task chi tiet

#### AI
- Tao `career_report.py`
- Logic:
  - tim nhom nghe nghiep/nhom JD phu hop
  - tinh `muc_do_phu_hop`
  - de xuat skill can bo sung
  - sinh `bao_cao_chi_tiet`

#### BE
- Tao `CareerReportController`
- Them route:
  - `POST /api/v1/ung-vien/ho-sos/{id}/career-report`
- Luu vao `tu_van_nghe_nghieps`

### Done condition

- Co report duoc sinh va luu DB
- Ung vien xem duoc bang endpoint da ton tai

---

## Phase 9 - Chuc nang nang cao de lam sau

### 9.1. Semantic Search
- Su dung `vector_embeddings`
- Embedding JD va query
- Search theo cosine similarity / FAISS / pgvector

### 9.2. Career Chatbot
- Su dung `ai_chat_sessions`, `ai_chat_messages`
- RAG tren CV parsed + JD + career reports

### 9.3. Mock Interview
- Su dung `ai_chat_sessions`, `ai_chat_messages`, `ai_interview_reports`
- AI hoi dap theo JD va cham diem

### 9.4. Market Dashboard
- Su dung `market_stats_daily`
- Tong hop top skills, avg salary, demand theo ngay

---

## Danh sach file du kien can tao trong BE

### Services
- `BE/app/Services/Ai/AiClientService.php`

### Controllers
- `BE/app/Http/Controllers/Api/CvParsingController.php`
- `BE/app/Http/Controllers/Api/JdParsingController.php`
- `BE/app/Http/Controllers/Api/MatchingController.php`
- `BE/app/Http/Controllers/Api/CoverLetterController.php`
- `BE/app/Http/Controllers/Api/CareerReportController.php`

### Routes
- Them route moi vao `BE/routes/api.php`

### Models can bo sung relation
- `BE/app/Models/HoSo.php`
- `BE/app/Models/TinTuyenDung.php`
- `BE/app/Models/NguoiDung.php`

---

## Danh sach file du kien can tao trong AI

### Core
- `AI/app/main.py`
- `AI/app/core/config.py`
- `AI/app/core/logger.py`

### Routers
- `AI/app/routers/cv_parser.py`
- `AI/app/routers/jd_parser.py`
- `AI/app/routers/matching.py`
- `AI/app/routers/generation.py`

### Services
- `AI/app/services/cv_extractor.py`
- `AI/app/services/cv_parser.py`
- `AI/app/services/jd_parser.py`
- `AI/app/services/skill_mapper.py`
- `AI/app/services/matcher.py`
- `AI/app/services/cover_letter.py`
- `AI/app/services/career_report.py`

### Schemas
- `AI/app/schemas/cv.py`
- `AI/app/schemas/jd.py`
- `AI/app/schemas/matching.py`
- `AI/app/schemas/generation.py`

---

## Postman test plan toi thieu

### CV Parser
- Dang nhap ung vien
- Tao ho so co file CV
- Goi parse CV
- Kiem tra `ho_so_parsings`

### JD Parser
- Dang nhap NTD
- Tao JD
- Goi parse JD
- Kiem tra `tin_tuyen_dung_parsings`
- Kiem tra `tin_tuyen_dung_ky_nangs`

### Matching
- Parse CV truoc
- Parse JD truoc
- Goi matching
- Kiem tra `ket_qua_matchings`

### Cover Letter
- Tao ung tuyen
- Goi generate cover letter
- Kiem tra `thu_xin_viec_ai`

### Career Report
- Goi generate report
- Kiem tra `tu_van_nghe_nghieps`

---

## Done criteria tong quat

He thong duoc xem la di dung huong khi:

- DB da san sang cho AI
- BE goi duoc AI service
- CV Parser chay duoc end-to-end
- JD Parser chay duoc end-to-end
- Matching luu duoc ket qua that
- Cover letter sinh duoc ban nhap
- Career report sinh duoc bao cao

Luc do moi nen chuyen sang:

- semantic search
- chatbot
- mock interview
- dashboard


# راهنمای فرانت‌اند: منشأهای ایراد بر اساس موقعیت و مرکز گروه کاری

این دو مسیر، رکوردهای `incident-sources` را برمی‌گردانند که موقعیت یا مرکز گروه کاری مشخص‌شده در انتخاب‌های منشأ آن‌ها ثبت شده است.

| Method | مسیر | شناسه | Route name و Permission |
|---|---|---|---|
| `GET` | `/api/locations/{location}/incident-sources` | شناسه موقعیت | `locations.incident-sources.index` |
| `GET` | `/api/work-group-centers/{workGroupCenter}/incident-sources` | شناسه مرکز گروه کاری | `work-group-centers.incident-sources.index` |

هر دو مسیر به احراز هویت Sanctum، کاربر فعال و مجوز مستقل مشخص‌شده در جدول نیاز دارند. بدنه درخواست لازم نیست.

```http
Authorization: Bearer {token}
Accept: application/json
```

## نمونه درخواست‌ها

```http
GET /api/locations/1/incident-sources?paginate=10&page=1
Authorization: Bearer {token}
Accept: application/json
```

```http
GET /api/work-group-centers/1/incident-sources?paginate=10&page=1
Authorization: Bearer {token}
Accept: application/json
```

## صفحه‌بندی

| پارامتر | نوع | پیش‌فرض | توضیح |
|---|---|---|---|
| `paginate` | integer | `15` | تعداد آیتم در هر صفحه، از `1` تا `100` |
| `page` | integer | `1` | شماره صفحه، حداقل `1` |

پاسخ همیشه صفحه‌بندی‌شده است. برای رفتن به صفحه بعد می‌توانید از `links.next` استفاده کنید یا شماره صفحه را در پارامتر `page` افزایش دهید؛ وقتی صفحه بعدی وجود ندارد، `links.next` برابر `null` است. لینک‌های صفحه‌بندی پارامترهای query درخواست را حفظ می‌کنند. ترتیب نتایج بر اساس `id` نزولی است.

نمونه پاسخ `200 OK` برای مسیر موقعیت، با یک نتیجه:

```json
{
  "data": [
    {
      "id": 42,
      "car_model_id": 3,
      "element_id": 5,
      "incident_id": 7,
      "carModel": { "id": 3, "title": "مدل خودرو" },
      "element": { "id": 5, "title": "قطعه" },
      "incident": { "id": 7, "title": "ایراد" },
      "sources": [
        {
          "id": 91,
          "model_type": "locations",
          "model_id": 1,
          "source": "موقعیت اول",
          "production_line": null,
          "user_id": 4,
          "user": { "id": 4, "name": "نام کاربر" },
          "created_at": "2026-11-28T08:00:00.000000Z"
        }
      ],
      "model_type": "locations",
      "model_id": 1,
      "source": "موقعیت اول"
    }
  ],
  "links": {
    "first": "https://example.com/api/locations/1/incident-sources?paginate=10&page=1",
    "last": "https://example.com/api/locations/1/incident-sources?paginate=10&page=1",
    "prev": null,
    "next": null
  },
  "meta": {
    "current_page": 1,
    "from": 1,
    "last_page": 1,
    "links": [
      { "url": null, "label": "&laquo; Previous", "active": false },
      { "url": "https://example.com/api/locations/1/incident-sources?paginate=10&page=1", "label": "1", "active": true },
      { "url": null, "label": "Next &raquo;", "active": false }
    ],
    "path": "https://example.com/api/locations/1/incident-sources",
    "per_page": 10,
    "to": 1,
    "total": 1
  }
}
```

مقادیر `meta.links.label` به تنظیمات زبان برنامه بستگی دارند؛ برای کنترل صفحه‌بندی از URLها و فیلدهای عددی استفاده کنید. دامنه و داده‌های مثال فرضی‌اند. در صورت نبود نتیجه، `data` یک آرایه خالی و `meta.total` برابر صفر است.

## نکات تفسیر پاسخ

- هر عضو `data` یک رکورد منشأ ایراد است. `carModel`، `element` و `incident` مشخصات مرتبط را نشان می‌دهند؛ اگر رابطه‌ای در دسترس نباشد، `id` و `title` آن `null` خواهند بود.
- `sources` همه انتخاب‌های ثبت‌شده برای آن رکورد را برمی‌گرداند، نه فقط انتخابی را که با مسیر تطبیق داشته است. برای پیدا کردن انتخاب مربوط به صفحه، هر دو فیلد `model_type` و `model_id` را بررسی کنید.
- در مسیر مرکز گروه کاری، انتخاب مربوط دارای `model_type: "work_group_centers"` است. برای این نوع، `production_line` در صورت وجود شامل `id` و `title` خط تولید است؛ در غیر این صورت `null` است. برای موقعیت نیز `production_line` برابر `null` است.
- `user_id` و `user` ممکن است `null` باشند؛ برای مثال، اطلاعات نویسنده در بعضی رکوردهای قدیمی ثبت نشده است یا کاربر دیگر در دسترس نیست. `created_at` نیز ممکن است `null` باشد.
- فیلدهای سطح اول `model_type`، `model_id` و `source` برای سازگاری با پاسخ قدیمی، از **اولین** عضو `sources` ساخته می‌شوند و لزوماً همان انتخاب مربوط به شناسه مسیر نیستند. اگر انتخابی نباشد مقدارشان رشته خالی است.
- نوع و شناسه از مسیر تعیین می‌شوند. در صورت ارسال `model_type` یا `model_id` در query، این پارامترها ابتدا اعتبارسنجی می‌شوند، ولی مقدار معتبرشان جایگزین نوع و شناسه مسیر نمی‌شود؛ ارسال آن‌ها برای این دو مسیر لازم نیست.

## خطاها

| وضعیت | کاربرد |
|---|---|
| `401` | احراز هویت انجام نشده است یا کاربر غیرفعال است. |
| `403` | کاربر مجوز دسترسی ندارد. |
| `404` | موقعیت یا مرکز گروه کاری پیدا نشده است؛ مرکز گروه کاری حذف‌شده نیز پیدا نمی‌شود. |
| `422` | پارامترهای صفحه‌بندی یا فیلترهای ارسالی معتبر نیستند. |

نمونه خطای اعتبارسنجی برای `paginate=101`:

  ```json
  {
    "message": "تعداد رکورد در هر صفحه نباید بیشتر از ۱۰۰ باشد.",
    "errors": {
      "paginate": ["تعداد رکورد در هر صفحه نباید بیشتر از ۱۰۰ باشد."]
    }
  }
  ```

## رسیدگی مستقل به هر منشأ

شناسه `{incidentSource}` همان `data[].id` و شناسه `{selection}` همان `data[].sources[].id` در فهرست منشأهاست. انتخاب باید متعلق به همان `IncidentSource` و همچنان موجود باشد. هر درخواست نیازمند هدرهای زیر، کاربر فعال و permission مشخص‌شده برای همان مسیر است:

```http
Authorization: Bearer {token}
Accept: application/json
Content-Type: application/json
```

| Method | مسیر زیر `/api/incident-sources/{incidentSource}` | Route name و Permission |
|---|---|---|
| `GET` | `/handlings` | `incident-sources.handlings.index` |
| `GET` | `/vins` | `incident-sources.vins.index` |
| `POST` | `/selections/{selection}/viewed` | `incident-sources.selections.viewed` |
| `POST` | `/selections/{selection}/start` | `incident-sources.selections.start` |
| `POST` | `/selections/{selection}/action` | `incident-sources.selections.action` |
| `POST` | `/selections/{selection}/transfer` | `incident-sources.selections.transfer` |

### وضعیت‌ها و قواعد تغییر وضعیت

| `status` | معنی | عملیات بعدی |
|---|---|---|
| `pending` | رسیدگی هنوز شروع نشده است. نبود رکورد رسیدگی برای انتخاب نیز به معنی این وضعیت است. | مشاهده، شروع یا انتقال |
| `viewed` | مشاهده ثبت شده و رسیدگی هنوز شروع نشده است. | شروع یا انتقال |
| `in_progress` | رسیدگی شروع شده است. | ثبت اقدام یا انتقال |
| `action_taken` | اقدام ثبت شده است. | انتقال؛ ثبت اقدام یا شروع مجدد مجاز نیست. |
| `transferred` | مسئولیت انتقال داده شده و انتخاب قبلی حذف شده است. | تاریخچه از مسیر `handlings` قابل مشاهده است؛ عملیات روی انتخاب حذف‌شده `404` می‌دهد. |

- شروع رسیدگی بدون ثبت مشاهده نیز مجاز است. مشاهده در هر مرحله‌ای که انتخاب هنوز وجود داشته باشد قابل ثبت است و اگر وضعیت پیشرفته‌تر باشد آن را به `viewed` بازنمی‌گرداند؛ مشاهده تکراری رویداد جدید ایجاد نمی‌کند.
- انتقال محدود به مراحل قبل از ثبت اقدام نیست. انتقال به یک `IncidentSource` دیگر انجام نمی‌شود؛ یک انتخاب مقصد جدید زیر همان رکورد ساخته می‌شود.
- نوع رویدادها `viewed`، `started`، `action` و `transferred` است. هنگام ثبت اولین عملیات، رکورد رسیدگی ساخته می‌شود؛ رویداد جداگانه‌ای با عنوان ثبت ایراد ساخته نمی‌شود. `source_created_at` و `selection_created_at` به‌ترتیب زمان ایجاد `IncidentSource` و انتخاب منشأ هستند و الزاماً زمان ثبت ایراد خودرو نیستند.

### دریافت تاریخچه رسیدگی‌ها

**Permission:** `incident-sources.handlings.index`

```http
GET /api/incident-sources/42/handlings
Authorization: Bearer {token}
Accept: application/json
```

پاسخ نمونه `200 OK`، شامل رسیدگی‌های ایجادشده برای منشأهای این رکورد و مرتب‌شده با `id` صعودی:

```json
{
  "data": [
    {
      "id": 10,
      "incident_source_id": 42,
      "incident_source_selection_id": 91,
      "model_type": "App\\Models\\Location",
      "model_id": 1,
      "source_created_at": "2026-11-28T07:00:00.000000Z",
      "selection_created_at": "2026-11-28T07:30:00.000000Z",
      "status": "viewed",
      "created_at": "2026-11-28T08:00:00.000000Z",
      "updated_at": "2026-11-28T08:00:00.000000Z",
      "action": null,
      "events": [
        {
          "id": 100,
          "incident_source_handling_id": 10,
          "type": "viewed",
          "user_id": 4,
          "data": null,
          "created_at": "2026-11-28T08:00:00.000000Z",
          "updated_at": "2026-11-28T08:00:00.000000Z",
          "user": {"id": 4, "name": "نام کاربر"}
        }
      ]
    }
  ]
}
```

این مسیر صفحه‌بندی ندارد. اگر رسیدگی‌ای ایجاد نشده باشد `data` برابر `[]` است. برای شناسایی انتخاب‌های در انتظار، فهرست `sources` را با `incident_source_selection_id` رسیدگی‌ها تطبیق دهید. پس از حذف انتخاب، شناسه آن در رسیدگی `null` می‌شود، اما اطلاعات منشأ و رویدادها باقی می‌مانند. `user` و `user_id` در صورت حذف کاربر ممکن است `null` باشند. `model_type` در پاسخ رسیدگی و رویدادها morph class ذخیره‌شده است؛ مقدار واقعی آن به morph map برنامه بستگی دارد. نمونه بالا نام کلاس پیش‌فرض است؛ برای مقادیر ورودی انتقال از نام‌های جدول زیر استفاده کنید.

### ثبت مشاهده

**Permission:** `incident-sources.selections.viewed`

```http
POST /api/incident-sources/42/selections/91/viewed
Authorization: Bearer {token}
Accept: application/json
```

بدنه لازم نیست. نمونه پاسخ `200 OK` برای اولین مشاهده:

```json
{
  "data": {
    "id": 10, "incident_source_id": 42, "incident_source_selection_id": 91,
    "model_type": "App\\\\Models\\\\Location", "model_id": 1,
    "source_created_at": "2026-11-28T07:00:00.000000Z", "selection_created_at": "2026-11-28T07:30:00.000000Z",
    "status": "viewed", "created_at": "2026-11-28T08:00:00.000000Z", "updated_at": "2026-11-28T08:00:00.000000Z",
    "action": null,
    "events": [
      {"id": 100, "incident_source_handling_id": 10, "type": "viewed", "user_id": 4, "data": null, "created_at": "2026-11-28T08:00:00.000000Z", "updated_at": "2026-11-28T08:00:00.000000Z", "user": {"id": 4, "name": "نام کاربر"}}
    ]
  }
}
```

در درخواست تکراری همان رسیدگی بدون رویداد مشاهده جدید بازگردانده می‌شود.

### شروع رسیدگی

**Permission:** `incident-sources.selections.start`

```http
POST /api/incident-sources/42/selections/91/start
Authorization: Bearer {token}
Accept: application/json
```

بدنه لازم نیست. پاسخ `200 OK` پس از مشاهده و شروع:

```json
{
  "data": {
    "id": 10,
    "incident_source_id": 42,
    "incident_source_selection_id": 91,
    "model_type": "App\\Models\\Location",
    "model_id": 1,
    "source_created_at": "2026-11-28T07:00:00.000000Z",
    "selection_created_at": "2026-11-28T07:30:00.000000Z",
    "status": "in_progress",
    "created_at": "2026-11-28T08:00:00.000000Z",
    "updated_at": "2026-11-28T08:01:00.000000Z",
    "action": null,
    "events": [
      {"id": 100, "incident_source_handling_id": 10, "type": "viewed", "user_id": 4, "data": null, "created_at": "2026-11-28T08:00:00.000000Z", "updated_at": "2026-11-28T08:00:00.000000Z", "user": {"id": 4, "name": "نام کاربر"}},
      {"id": 101, "incident_source_handling_id": 10, "type": "started", "user_id": 4, "data": null, "created_at": "2026-11-28T08:01:00.000000Z", "updated_at": "2026-11-28T08:01:00.000000Z", "user": {"id": 4, "name": "نام کاربر"}}
    ]
  }
}
```

### ثبت اقدام انجام‌شده (Action Taken)

**مسیر:** `POST /api/incident-sources/{incidentSource}/selections/{selection}/action`

**Route name و Permission:** `incident-sources.selections.action`

`incidentSource` شناسه رکورد منشأ ایراد و `selection` شناسه انتخاب منشأ مربوط به همان رکورد است. در پاسخ فهرست، این دو به‌ترتیب `data[].id` و `data[].sources[].id` هستند. این مسیر برای انتخاب موقعیت و مرکز گروه کاری یکسان است.

پیش از ثبت اقدام باید با مسیر `POST /api/incident-sources/{incidentSource}/selections/{selection}/start` رسیدگی را شروع کنید؛ این درخواست به permission جداگانه `incident-sources.selections.start` نیاز دارد. رسیدگی باید `in_progress` و فاقد اقدام قبلی باشد؛ فعلاً فقط یک اقدام پذیرفته می‌شود.

| فیلد body | اعتبارسنجی |
|---|---|
| `description` | الزامی؛ رشته، حداکثر ۱۰٬۰۰۰ کاراکتر |

نمونه درخواست:

```http
POST /api/incident-sources/42/selections/91/action
Authorization: Bearer {token}
Accept: application/json
Content-Type: application/json

{"description":"قطعه بررسی و ایراد رفع شد."}
```

پاسخ نمونه `200 OK`، برای رسیدگی‌ای که بدون ثبت مشاهده شروع شده است:

```json
{
  "data": {
    "id": 10, "incident_source_id": 42, "incident_source_selection_id": 91,
    "model_type": "App\\Models\\Location", "model_id": 1,
    "source_created_at": "2026-11-28T07:00:00.000000Z", "selection_created_at": "2026-11-28T07:30:00.000000Z",
    "status": "action_taken", "created_at": "2026-11-28T08:00:00.000000Z", "updated_at": "2026-11-28T08:05:00.000000Z",
    "action": {"id": 3, "incident_source_handling_id": 10, "description": "قطعه بررسی و ایراد رفع شد.", "user_id": 4, "created_at": "2026-11-28T08:05:00.000000Z", "updated_at": "2026-11-28T08:05:00.000000Z", "user": {"id": 4, "name": "نام کاربر"}},
    "events": [
      {"id": 101, "incident_source_handling_id": 10, "type": "started", "user_id": 4, "data": null, "created_at": "2026-11-28T08:00:00.000000Z", "updated_at": "2026-11-28T08:00:00.000000Z", "user": {"id": 4, "name": "نام کاربر"}},
      {"id": 102, "incident_source_handling_id": 10, "type": "action", "user_id": 4, "data": {"description": "قطعه بررسی و ایراد رفع شد."}, "created_at": "2026-11-28T08:05:00.000000Z", "updated_at": "2026-11-28T08:05:00.000000Z", "user": {"id": 4, "name": "نام کاربر"}}
    ]
  }
}
```

پس از موفقیت، `data.status` برابر `action_taken` است؛ متن و کاربر ثبت‌کننده در `data.action` و رویداد ثبت اقدام در `data.events` بازگردانده می‌شوند. شناسه کاربر از حساب احراز هویت‌شده دریافت می‌شود و نیازی به ارسال آن در body نیست.

اگر رسیدگی شروع نشده باشد یا قبلاً اقدام داشته باشد، پاسخ `422 Unprocessable Entity` است:

```json
{
  "message": "برای ثبت اقدام، رسیدگی باید شروع شده و فاقد اقدام قبلی باشد.",
  "errors": {
    "status": ["برای ثبت اقدام، رسیدگی باید شروع شده و فاقد اقدام قبلی باشد."]
  }
}
```

برای `description` نامعتبر نیز `422` و خطای مربوط در `errors.description` برمی‌گردد. نبود احراز هویت یا غیرفعال‌بودن کاربر `401`، نداشتن permission برابر `403` و شناسه ناموجود یا انتخاب نامرتبط با رکورد برابر `404` است.

### انتقال مسئولیت

**Permission:** `incident-sources.selections.transfer`

| فیلد | اعتبارسنجی |
|---|---|
| `model_type` | الزامی؛ `locations` یا `work_group_centers` |
| `model_id` | الزامی؛ عدد صحیح مثبت و شناسه موجود مقصد. مرکز گروه کاری حذف‌شده پذیرفته نمی‌شود. |
| `reason` | الزامی؛ رشته با حداکثر ۱۰٬۰۰۰ کاراکتر |

مقصد نباید از قبل جزو انتخاب‌های همان `IncidentSource` باشد. منشأ قبلی از فهرست انتخاب‌های فعال حذف می‌شود و انتخاب جدید، رسیدگی مستقل و در انتظار دارد.

```http
POST /api/incident-sources/42/selections/91/transfer
Authorization: Bearer {token}
Accept: application/json
Content-Type: application/json

{"model_type":"work_group_centers","model_id":1,"reason":"ارجاع به مرکز مسئول"}
```

پاسخ نمونه `200 OK` برای انتقال مستقیم پیش از مشاهده یا شروع:

```json
{
  "data": {
    "id": 10, "incident_source_id": 42, "incident_source_selection_id": null,
    "model_type": "App\\Models\\Location", "model_id": 1,
    "source_created_at": "2026-11-28T07:00:00.000000Z", "selection_created_at": "2026-11-28T07:30:00.000000Z",
    "status": "transferred", "created_at": "2026-11-28T08:00:00.000000Z", "updated_at": "2026-11-28T08:00:00.000000Z",
    "action": null,
    "events": [
      {
        "id": 103, "incident_source_handling_id": 10, "type": "transferred", "user_id": 4,
        "data": {"from_type": "App\\Models\\Location", "from_id": 1, "to_type": "App\\Models\\WorkGroupCenter", "to_id": 1, "to_selection_id": 92, "reason": "ارجاع به مرکز مسئول"},
        "created_at": "2026-11-28T08:00:00.000000Z", "updated_at": "2026-11-28T08:00:00.000000Z", "user": {"id": 4, "name": "نام کاربر"}
      }
    ]
  }
}
```

`to_selection_id` شناسه انتخاب جدید برای عملیات بعدی است. تاریخچه منشأ قبلی از `/handlings` قابل دریافت است. اگر رسیدگی قبلی رویداد یا اقدام داشته باشد، آن‌ها نیز در پاسخ انتقال و تاریخچه باقی می‌مانند.

### خطاهای عملیات رسیدگی

علاوه بر خطاهای احراز هویت و مجوز در بخش خطاها، `404` برای `IncidentSource` یا انتخاب ناموجود، حذف‌شده یا انتخاب متعلق به رکورد دیگر برمی‌گردد. `422` برای ورودی نامعتبر، شروع تکراری، اقدام قبل از شروع یا اقدام تکراری، و مقصد انتقال ناموجود یا تکراری برمی‌گردد. نمونه پاسخ اقدام قبل از شروع:

```json
{
  "message": "برای ثبت اقدام، رسیدگی باید شروع شده و فاقد اقدام قبلی باشد.",
  "errors": {"status": ["برای ثبت اقدام، رسیدگی باید شروع شده و فاقد اقدام قبلی باشد."]}
}
```

### مسیر VIN

**Permission:** `incident-sources.vins.index`

## VINهای دارای قطعه و ایراد مشترک

```http
GET /api/incident-sources/42/vins?paginate=10&page=1
Authorization: Bearer {token}
Accept: application/json
```

این مسیر VINهای غیرتکراری `VehicleIncident`های حذف‌نشده با `incident_id` و `element_id` یکسان را برمی‌گرداند. مدل خودرو و محل ثبت ایراد محدودکننده نیستند؛ موارد قدیمی و بسته‌شده نیز در صورت حذف‌نشدن شامل می‌شوند. پارامترهای صفحه‌بندی مانند بخش بالا هستند. پاسخ این مسیر paginator معمول Laravel است و فیلدهای صفحه‌بندی در سطح اول قرار دارند:

```json
{
  "current_page": 1,
  "data": [{"vin": "N5RL42DA5SP018444"}],
  "first_page_url": "https://example.com/api/incident-sources/42/vins?paginate=10&page=1",
  "from": 1,
  "last_page": 1,
  "last_page_url": "https://example.com/api/incident-sources/42/vins?paginate=10&page=1",
  "links": [
    {"url": null, "label": "&laquo; Previous", "active": false},
    {"url": "https://example.com/api/incident-sources/42/vins?paginate=10&page=1", "label": "1", "active": true},
    {"url": null, "label": "Next &raquo;", "active": false}
  ],
  "next_page_url": null,
  "path": "https://example.com/api/incident-sources/42/vins",
  "per_page": 10,
  "prev_page_url": null,
  "to": 1,
  "total": 1
}
```

مقادیر `labels` و دامنه نمونه‌اند. عملیات با وضعیت نامعتبر، اقدام تکراری یا مقصد نامعتبر پاسخ `422` می‌دهد؛ انتخابی که متعلق به `IncidentSource` نباشد یا حذف شده باشد، `404` می‌دهد.

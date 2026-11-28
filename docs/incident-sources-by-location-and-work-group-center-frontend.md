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

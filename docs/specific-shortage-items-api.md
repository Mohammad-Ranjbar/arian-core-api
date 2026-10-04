# مستند API آیتم‌های کسری خاص

این مستند قرارداد API مربوط به `SpecificShortageItem` برای استفاده تیم فرانت‌اند است.

## اطلاعات عمومی

- Base path: `/api/specific-shortage-items`
- فرمت درخواست و پاسخ: `application/json`
- تمام مسیرها نیازمند احراز هویت با Sanctum هستند.
- هدرهای مورد نیاز:

```http
Authorization: Bearer {token}
Accept: application/json
Content-Type: application/json
```

## ساختار آیتم

```json
{
  "id": 1,
  "qc_code": "84",
  "item_no": "ITEM-100",
  "description": "توضیحات قطعه کسری",
  "erp_product_item_id": 25,
  "erpProductItem": {
    "id": 25,
    "title": "نام قطعه",
    "description": "توضیحات قطعه ERP",
    "reference": "ERP-100"
  },
  "car_model_id": 3,
  "carModel": {
    "id": 3,
    "title": "مدل خودرو",
    "reference": "CAR-100"
  },
  "is_active": true,
  "actionLogs": [],
  "created_at": "2026-10-04T12:00:00.000000Z",
  "updated_at": "2026-10-04T12:00:00.000000Z"
}
```

نکات:

- مقدار `qc_code` در حال حاضر از سمت فرانت دریافت نمی‌شود و هنگام ایجاد به‌صورت پیش‌فرض `84` است.
- مقادیر `erpProductItem` و `carModel` به‌صورت object در پاسخ بازگردانده می‌شوند.
- فیلد `actionLogs` شامل تاریخچه ایجاد، فعال‌سازی، غیرفعال‌سازی و حذف است.

## ساختار ActionLog

```json
{
  "id": 10,
  "user_id": 7,
  "action": "specific_shortage_item_deactivated",
  "data": {
    "before": {
      "id": 1,
      "qc_code": "84",
      "item_no": "ITEM-100",
      "description": "توضیحات قبلی",
      "erp_product_item_id": 25,
      "car_model_id": 3,
      "is_active": true,
      "created_at": "2026-10-04 12:00:00",
      "updated_at": "2026-10-04 12:00:00"
    },
    "after": {
      "id": 1,
      "qc_code": "84",
      "item_no": "ITEM-100",
      "description": "توضیحات جدید",
      "erp_product_item_id": 25,
      "car_model_id": 3,
      "is_active": false,
      "created_at": "2026-10-04 12:00:00",
      "updated_at": "2026-10-04 12:10:00"
    }
  },
  "created_at": "2026-10-04T12:10:00.000000Z",
  "user": {
    "id": 7,
    "name": "نام کاربر",
    "email": "user@example.com",
    "mobile": "09120000000",
    "national_code": "0012345678",
    "stations": [],
    "is_active": true
  }
}
```

مقادیر ممکن `action`:

| مقدار | توضیح |
|---|---|
| `specific_shortage_item_created` | ایجاد آیتم |
| `specific_shortage_item_activated` | فعال‌سازی آیتم |
| `specific_shortage_item_deactivated` | غیرفعال‌سازی آیتم |
| `specific_shortage_item_deleted` | حذف آیتم |

ساختار snapshotها:

- ایجاد: مقدار `before` برابر `null` و اطلاعات کامل در `after` است.
- فعال یا غیرفعال کردن: اطلاعات کامل قبلی در `before` و اطلاعات کامل جدید در `after` است.
- حذف: اطلاعات کامل در `before` و مقدار `after` برابر `null` است.

## دریافت فهرست

```http
GET /api/specific-shortage-items
```

Permission:

```text
specific-shortage-items.index
```

Query parameters:

| پارامتر | نوع | اجباری | توضیح |
|---|---|---|---|
| `per_page` | integer | خیر | تعداد رکورد در هر صفحه؛ مقدار پیش‌فرض `15` است. |
| `q` | string | خیر | جستجو در `qc_code`، `item_no` و `description` |
| `car_model_id` | integer | خیر | محدود کردن نتایج به یک مدل خودرو |

نمونه درخواست:

```http
GET /api/specific-shortage-items?per_page=20&q=ITEM-100&car_model_id=3
```

نمونه پاسخ موفق (`200 OK`):

```json
{
  "data": [
    {
      "id": 1,
      "qc_code": "84",
      "item_no": "ITEM-100",
      "description": "توضیحات قطعه کسری",
      "erp_product_item_id": 25,
      "erpProductItem": {
        "id": 25,
        "title": "نام قطعه",
        "description": "توضیحات قطعه ERP",
        "reference": "ERP-100"
      },
      "car_model_id": 3,
      "carModel": {
        "id": 3,
        "title": "مدل خودرو",
        "reference": "CAR-100"
      },
      "is_active": true,
      "actionLogs": [],
      "created_at": "2026-10-04T12:00:00.000000Z",
      "updated_at": "2026-10-04T12:00:00.000000Z"
    }
  ],
  "links": {
    "first": "https://example.com/api/specific-shortage-items?page=1",
    "last": "https://example.com/api/specific-shortage-items?page=1",
    "prev": null,
    "next": null
  },
  "meta": {
    "current_page": 1,
    "from": 1,
    "last_page": 1,
    "per_page": 20,
    "to": 1,
    "total": 1
  }
}
```

## ایجاد آیتم

```http
POST /api/specific-shortage-items
```

Permission:

```text
specific-shortage-items.store
```

Body:

```json
{
  "item_no": "ITEM-100",
  "description": "توضیحات قطعه کسری",
  "erp_product_item_id": 25,
  "car_model_id": 3,
  "is_active": true
}
```

Validation:

| فیلد | قوانین |
|---|---|
| `item_no` | الزامی، string، حداکثر ۲۵۵ کاراکتر |
| `description` | الزامی، string، حداکثر ۲۵۵ کاراکتر |
| `erp_product_item_id` | الزامی، integer، باید در `erp_product_items` وجود داشته باشد. |
| `car_model_id` | الزامی، integer، باید در `car_models` وجود داشته باشد. |
| `is_active` | الزامی، boolean |

پاسخ موفق: `201 Created`

```json
{
  "data": {
    "id": 1,
    "qc_code": "84",
    "item_no": "ITEM-100",
    "description": "توضیحات قطعه کسری",
    "erp_product_item_id": 25,
    "erpProductItem": {
      "id": 25,
      "title": "نام قطعه",
      "description": "توضیحات قطعه ERP",
      "reference": "ERP-100"
    },
    "car_model_id": 3,
    "carModel": {
      "id": 3,
      "title": "مدل خودرو",
      "reference": "CAR-100"
    },
    "is_active": true,
    "actionLogs": [
      {
        "id": 10,
        "user_id": 7,
        "action": "specific_shortage_item_created",
        "data": {
          "before": null,
          "after": {
            "id": 1,
            "qc_code": "84",
            "item_no": "ITEM-100",
            "description": "توضیحات قطعه کسری",
            "erp_product_item_id": 25,
            "car_model_id": 3,
            "is_active": true,
            "created_at": "2026-10-04 12:00:00",
            "updated_at": "2026-10-04 12:00:00"
          }
        },
        "created_at": "2026-10-04T12:00:00.000000Z",
        "user": {
          "id": 7,
          "name": "نام کاربر",
          "email": "user@example.com",
          "mobile": "09120000000",
          "national_code": "0012345678",
          "stations": [],
          "is_active": true
        }
      }
    ],
    "created_at": "2026-10-04T12:00:00.000000Z",
    "updated_at": "2026-10-04T12:00:00.000000Z"
  }
}
```

## نمایش یک آیتم

```http
GET /api/specific-shortage-items/{id}
```

Permission:

```text
specific-shortage-items.show
```

Path parameter:

| پارامتر | نوع | توضیح |
|---|---|---|
| `id` | integer | شناسه آیتم |

Body نیاز ندارد.

پاسخ موفق: `200 OK`

```json
{
  "data": {
    "id": 1,
    "qc_code": "84",
    "item_no": "ITEM-100",
    "description": "توضیحات قطعه کسری",
    "erp_product_item_id": 25,
    "erpProductItem": {},
    "car_model_id": 3,
    "carModel": {},
    "is_active": true,
    "actionLogs": [],
    "created_at": "2026-10-04T12:00:00.000000Z",
    "updated_at": "2026-10-04T12:00:00.000000Z"
  }
}
```

## ویرایش یا تغییر وضعیت

```http
PUT /api/specific-shortage-items/{id}
PATCH /api/specific-shortage-items/{id}
```

Permission:

```text
specific-shortage-items.update
```

در پیاده‌سازی فعلی، حتی هنگام استفاده از `PATCH` ارسال تمام فیلدهای زیر الزامی است.

Body نمونه برای غیرفعال‌سازی:

```json
{
  "item_no": "ITEM-100",
  "description": "توضیحات ویرایش‌شده",
  "erp_product_item_id": 25,
  "car_model_id": 3,
  "is_active": false
}
```

Body نمونه برای فعال‌سازی مجدد:

```json
{
  "item_no": "ITEM-100",
  "description": "توضیحات ویرایش‌شده",
  "erp_product_item_id": 25,
  "car_model_id": 3,
  "is_active": true
}
```

قوانین validation با مسیر ایجاد یکسان است.

پاسخ موفق: `200 OK`

- اگر `is_active` از `true` به `false` تغییر کند، لاگ `specific_shortage_item_deactivated` ثبت می‌شود.
- اگر `is_active` از `false` به `true` تغییر کند، لاگ `specific_shortage_item_activated` ثبت می‌شود.
- اگر مقدار `is_active` تغییر نکند، برای update معمولی ActionLog جدید ایجاد نمی‌شود.
- پاسخ شامل فهرست کامل و به‌روز `actionLogs` است.

## حذف آیتم

```http
DELETE /api/specific-shortage-items/{id}
```

Permission:

```text
specific-shortage-items.destroy
```

Body نیاز ندارد.

پاسخ موفق: `200 OK`

```json
{
  "message": "item deleted!"
}
```

پیش از تکمیل حذف، ActionLog با مقدار زیر ثبت می‌شود:

```text
specific_shortage_item_deleted
```

به دلیل حذف شدن خود آیتم، لاگ حذف در پاسخ DELETE بازگردانده نمی‌شود، اما در جدول `action_logs` باقی می‌ماند.

## پاسخ‌های خطا

### خطای احراز هویت

```http
401 Unauthorized
```

### نداشتن دسترسی

```http
403 Forbidden
```

### پیدا نشدن آیتم

```http
404 Not Found
```

### خطای Validation

```http
422 Unprocessable Entity
```

نمونه:

```json
{
  "message": "The item no field is required.",
  "errors": {
    "item_no": [
      "The item no field is required."
    ],
    "erp_product_item_id": [
      "The selected erp product item id is invalid."
    ]
  }
}
```

## خلاصه مسیرها

| Method | Endpoint | کاربرد | Permission |
|---|---|---|---|
| `GET` | `/api/specific-shortage-items` | دریافت فهرست | `specific-shortage-items.index` |
| `POST` | `/api/specific-shortage-items` | ایجاد آیتم | `specific-shortage-items.store` |
| `GET` | `/api/specific-shortage-items/{id}` | نمایش آیتم | `specific-shortage-items.show` |
| `PUT/PATCH` | `/api/specific-shortage-items/{id}` | ویرایش یا تغییر وضعیت | `specific-shortage-items.update` |
| `DELETE` | `/api/specific-shortage-items/{id}` | حذف آیتم | `specific-shortage-items.destroy` |

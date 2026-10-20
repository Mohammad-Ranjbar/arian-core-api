# راهنمای فرانت برای فیلترهای درخواست بیزینس سنترال

ابتدا گزینه‌های فیلتر را از `GET /api/rest-erp-jobs/filters` دریافت کنید؛ سپس مقدار گزینه‌های انتخاب‌شده را به `GET /api/rest-erp-jobs` بفرستید. این راهنما قرارداد دریافت گزینه‌ها، فیلتر کردن جدول، صفحه‌بندی و مدیریت خطا را توضیح می‌دهد.

## دسترسی

هر دو مسیر به احراز هویت با Sanctum، حساب فعال و مجوز `erp-jobs` نیاز دارند. در اتصال با توکن، هدرها به شکل زیر هستند:

```http
Accept: application/json
Authorization: Bearer {token}
```

`{token}` را با توکن کاربر جایگزین کنید. مسیرهای این راهنما شامل `/api` هستند؛ اگر API client پروژه این پیشوند را خودکار اضافه می‌کند، دوباره آن را ننویسید.

## دریافت گزینه‌های فیلتر

```http
GET /api/rest-erp-jobs/filters
```

پاسخ موفق `200 OK` است. درخواست پارامتر اجباری ندارد و پاسخ آن صفحه‌بندی نمی‌شود. هر گزینه شامل `value` برای ارسال به API و `label` برای نمایش است.

نمونهٔ پاسخ زیر برای بخش و سرویس، دادهٔ نمونه دارد؛ مقادیر واقعی این دو آرایه از Jobهای موجود در دیتابیس خوانده می‌شوند:

```json
{
  "data": {
    "sections": [
      { "value": "ثبت بارکد قطعات خودرو", "label": "ثبت بارکد قطعات خودرو" }
    ],
    "services": [
      { "value": "TrackingsService", "label": "TrackingsService" }
    ],
    "queues": [
      { "value": "sync", "label": "اجرای فوری" },
      { "value": "parallel", "label": "صف پردازش موازی" },
      { "value": "unique", "label": "صف پردازش تک‌به‌تک" }
    ],
    "statuses": [
      { "value": "pending", "label": "در انتظار پردازش" },
      { "value": "in-progress", "label": "در حال پردازش" },
      { "value": "success", "label": "موفق" },
      { "value": "failed", "label": "ناموفق" },
      { "value": "uncertain", "label": "نتیجه نامشخص" },
      { "value": "rejected", "label": "ردشده در اعتبارسنجی" },
      { "value": "ignored", "label": "نادیده گرفته‌شده" }
    ]
  }
}
```

- `sections` و `services` بدون تکرار هستند. در دیتابیس خالی، هر دو آرایه خالی‌اند.
- `queues` و `statuses` حتی در دیتابیس خالی موجود هستند.
- نام سرویس در گزینه‌ها کوتاه است؛ مانند `TrackingsService`. نام کامل کلاس یا namespace ارسال نکنید.
- اگر دو کلاس در namespaceهای متفاوت نام کوتاه یکسان داشته باشند، یک گزینه نمایش داده می‌شود و انتخاب آن هر دو سرویس را پوشش می‌دهد.
- گزینه‌ها مستقل از انتخاب‌های فعلی هستند؛ endpoint گزینه‌ها فیلتر وابسته اجرا نمی‌کند.

## اعمال فیلتر و صفحه‌بندی

```http
GET /api/rest-erp-jobs?service=TrackingsService&queue=sync&status=failed&page=1&per_page=15
```

تمام پارامترها اختیاری هستند. چند فیلتر هم‌زمان با شرط AND اعمال می‌شوند.

| پارامتر | مقدار قابل ارسال | توضیح |
| --- | --- | --- |
| `section` | مقدار `value` از `sections`؛ حداکثر ۲۵۵ کاراکتر | تطبیق دقیق بخش |
| `service` | مقدار `value` از `services` | نام کوتاه سرویس |
| `queue` | `sync`، `parallel` یا `unique` | حالت اجرا یا نام صف |
| `status` | یکی از مقادیر `statuses` | وضعیت Job |
| `endpoint` | نام endpoint تعریف‌شده در بک‌اند | گزینه‌های این فیلتر در API گزینه‌ها برگردانده نمی‌شوند |
| `user_id` | عدد صحیح بزرگ‌تر یا مساوی ۱ | کاربر ثبت‌کننده |
| `request_id` | UUID معتبر | تطبیق با `uuid` درخواست |
| `page` | شمارهٔ صفحه از ۱ | صفحهٔ مورد درخواست |
| `per_page` | عدد صحیح از ۱ تا ۱۰۰؛ پیش‌فرض ۱۵ | تعداد ردیف در صفحه |

فیلترها تک‌مقداری هستند. آرایه یا چند مقدار جداشده با کاما ارسال نکنید. برای حالت «همه»، کلید فیلتر را از درخواست حذف کنید؛ مقدار خالی، `null` یا متن «همه» نفرستید.

برای مقادیر فارسی از `URLSearchParams` یا قابلیت `params` در client استفاده کنید. مقدار `section` را عین `value` دریافتی بفرستید و حروف یا فاصله‌های آن را تغییر ندهید.

### تشخیص حالت اجرا

| فیلتر `queue` | مشخصات رکورد |
| --- | --- |
| `sync` | `execution_mode=sync`؛ فیلد `queue` رکورد معمولاً `null` است |
| `parallel` | `execution_mode=queue` و `queue=parallel` |
| `unique` | `execution_mode=queue` و `queue=unique` |

برای فیلتر اجرای فوری، `queue=sync` بفرستید؛ مقدار `null` صف در رکورد، مقدار قابل ارسال برای فیلتر نیست. `unique` در این پروژه صف اجرای تک‌به‌تک است. این API تعداد workerهای فعال را گزارش نمی‌کند.

## پاسخ فهرست

پاسخ فهرست شامل `data` برای ردیف‌ها، `links` برای لینک‌های صفحه‌بندی و `meta` برای اطلاعات صفحه‌بندی است. نمونهٔ خلاصه‌شدهٔ پاسخ خالی:

```json
{
  "data": [],
  "meta": {
    "current_page": 1,
    "last_page": 1,
    "per_page": 15,
    "total": 0
  }
}
```

فهرست بر اساس `id` نزولی مرتب می‌شود. برای جدول از `data` و برای صفحه‌بندی از `meta.current_page`، `meta.per_page`، `meta.last_page` و `meta.total` استفاده کنید.

| فیلد ردیف | کاربرد |
| --- | --- |
| `id` | شناسهٔ Job و کلید ردیف |
| `uuid` | شناسهٔ پیگیری؛ قابل جست‌وجو با `request_id` |
| `section` | عنوان بخش |
| `service` | ممکن است نام کامل کلاس باشد؛ برای نمایش، آخرین قسمت بعد از `\` را نشان دهید |
| `status` | مقدار وضعیت؛ عنوان فارسی را از `statuses` بگیرید |
| `execution_mode` و `queue` | تشخیص حالت اجرا طبق جدول بالا |
| `tries` | تعداد اجرای واقعی درخواست به ERP |
| `description` | متن خطا یا توضیح؛ ممکن است `null` باشد |
| `http_status` | وضعیت HTTP ذخیره‌شدهٔ Job؛ مستقل از HTTP پاسخ فهرست |
| `created_at` و `updated_at` | زمان ثبت و آخرین تغییر |

موفقیت HTTP درخواست فهرست به معنای موفق بودن Jobها نیست؛ نتیجهٔ هر ردیف را از `status` بخوانید. `description` را به‌صورت متن ساده نمایش دهید و به HTML تبدیل نکنید. متن ممکن است حاوی جزئیات انگلیسی و نویسه‌های کنترل جهت باشد؛ آن را برای حذف این نویسه‌ها بازنویسی نکنید.

## رفتار صفحه

1. هنگام باز شدن صفحه، گزینه‌ها و صفحهٔ اول فهرست را دریافت کنید.
2. `label` را در کنترل انتخاب نمایش دهید و `value` را در state ذخیره کنید.
3. با تغییر هر فیلتر، `page` را به ۱ برگردانید و فهرست را دوباره بگیرید.
4. با پاک کردن فیلتر، پارامتر آن را از درخواست حذف کنید.
5. درخواست قبلی را لغو کنید یا فقط آخرین پاسخ را اعمال کنید تا پاسخ قدیمی جای نتیجهٔ جدید را نگیرد.
6. پس از تازه‌سازی گزینه‌ها، اگر سرویس انتخاب‌شده دیگر در گزینه‌ها نبود، انتخاب را پاک کنید؛ سرویس نامعتبر در درخواست فهرست خطای `422` می‌دهد.

## نمونهٔ TypeScript مستقل از فریم‌ورک

این نمونه برای مرورگر و Bearer Token است. `apiOrigin` باید origin بک‌اند مانند `https://api.example.com` باشد، بدون مسیر `/api`. آدرس مثال را با آدرس واقعی محیط جایگزین کنید.

```typescript
export type ExecutionMode = "sync" | "parallel" | "unique";
export type JobStatus =
  | "pending" | "in-progress" | "success" | "failed"
  | "uncertain" | "rejected" | "ignored";

export type FilterOption<T extends string = string> = {
  value: T;
  label: string;
};

export type FilterOptions = {
  sections: FilterOption[];
  services: FilterOption[];
  queues: FilterOption<ExecutionMode>[];
  statuses: FilterOption<JobStatus>[];
};

export type JobFilters = {
  section?: string;
  service?: string;
  queue?: ExecutionMode;
  status?: JobStatus;
  endpoint?: string;
  user_id?: number;
  request_id?: string;
  page?: number;
  per_page?: number;
};

export type RestErpJob = {
  id: number;
  uuid: string;
  section: string;
  service: string;
  execution_mode: "sync" | "queue";
  queue: string | null;
  status: JobStatus;
  tries: number;
  description: string | null;
};

export type JobsPage = {
  data: RestErpJob[];
  meta: {
    current_page: number;
    last_page: number;
    per_page: number;
    total: number;
  };
};

type ErrorPayload = { message?: string; errors?: Record<string, string[]> };

export class ApiError extends Error {
  constructor(public status: number, public payload: ErrorPayload | null) {
    super(payload?.message ?? `خطا در دریافت داده‌ها با کد ${status}`);
  }
}

export function createRestErpApi(apiOrigin: string, token: string) {
  async function get<T>(path: string, signal?: AbortSignal): Promise<T> {
    const response = await fetch(new URL(path, apiOrigin), {
      headers: {
        Accept: "application/json",
        Authorization: `Bearer ${token}`,
      },
      signal,
    });
    const body = await response.json().catch(() => null);
    if (!response.ok) throw new ApiError(response.status, body);
    return body as T;
  }

  return {
    getOptions(signal?: AbortSignal) {
      return get<{ data: FilterOptions }>("/api/rest-erp-jobs/filters", signal);
    },
    getJobs(filters: JobFilters = {}, signal?: AbortSignal) {
      const params = new URLSearchParams();
      for (const [key, value] of Object.entries(filters)) {
        if (value !== undefined && value !== null && value !== "") {
          params.set(key, String(value));
        }
      }
      const query = params.toString();
      return get<JobsPage>(`/api/rest-erp-jobs${query ? `?${query}` : ""}`, signal);
    },
  };
}

export function getServiceLabel(fullService: string): string {
  return fullService.split("\\").pop() || fullService;
}

export function getExecutionMode(job: RestErpJob): string {
  return job.execution_mode === "sync" ? "sync" : (job.queue ?? "");
}
```

این client پاسخ JSON کامل را برمی‌گرداند: گزینه‌ها در `optionsResponse.data`، ردیف‌ها در `jobsResponse.data` و صفحه‌بندی در `jobsResponse.meta` هستند. اگر API client موجود پروژه خودش `response.data` را unwrap می‌کند، این تفاوت را هنگام اتصال در نظر بگیرید. لغو درخواست با `AbortController` نباید به‌عنوان خطا به کاربر نمایش داده شود.

## مدیریت خطا

| HTTP | رفتار فرانت |
| --- | --- |
| `401` | جریان احراز هویت موجود پروژه را اجرا کنید؛ ممکن است توکن نامعتبر یا حساب غیرفعال باشد |
| `403` | پیام عدم دسترسی نمایش دهید؛ مجوز `erp-jobs` لازم است |
| `422` | `errors` را بخوانید و مقدار فیلتر نامعتبر را اصلاح کنید |
| `429` | از تکرار سریع درخواست جلوگیری کنید |
| `5xx` | پیام خطای سرور و امکان تلاش مجدد نمایش دهید |

پاسخ اعتبارسنجی Laravel دارای `message` و `errors` است. هر کلید در `errors` مانند `service` یا `queue` آرایه‌ای از پیام‌ها دارد. متن دقیق پیام‌ها را در فرانت hardcode نکنید.

## معیار پذیرش

- چهار کنترل انتخاب از endpoint گزینه‌ها ساخته شوند و انتخاب «همه» پارامتر را حذف کند.
- برای سرویس فقط نام کوتاه ارسال و در جدول هم نام کوتاه نمایش داده شود.
- فیلتر `queue=sync` ردیف‌های دارای `execution_mode=sync` را پیدا کند، حتی وقتی `queue` آن‌ها `null` است.
- ترکیب بخش، سرویس، صف و وضعیت هم‌زمان اعمال شود.
- با تغییر فیلتر صفحه به ۱ برگردد و پاسخ قدیمی، دادهٔ جدید را جایگزین نکند.
- حالت خالی، خطای دسترسی و خطای اعتبارسنجی مدیریت شوند.

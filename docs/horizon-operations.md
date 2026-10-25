# بهینه‌سازی صف‌ها و استقرار Horizon

این تغییر برای Laravel 10 / Horizon 5 نوشته شده است؛ نسخه نصب‌شده هنگام بررسی
Laravel 10.50.2 و Horizon 5.47.2 بود. سرور از طریق PM2 اجرا می‌شود؛ نسخه PM2، تعداد
CPU، تنظیمات مؤثر و لاگ‌های سرور هنوز دریافت نشده‌اند.

## علت‌های پیدا شده در کد

- در production فقط یک worker برای `default` تعریف شده بود. پیامک، چاپ، export و
  import همگی از همین صف استفاده می‌کردند.
- `balance=smart` جزو حالت‌های معتبر Horizon نیست؛ حالت‌های معتبر `auto`، `simple`
  و `false` هستند.
- timeout قبلی `10000000` ثانیه بود، ولی `retry_after` فقط ۹۰ ثانیه؛ در نتیجه یک
  رزرو می‌توانست در حالی منقضی شود که worker قبلی هنوز در حال اجرای همان جاب است.
- `tries=0` در worker پیش‌فرض تعداد تلاش‌ها را نامحدود می‌کرد. متد `maxAttempts()`
  در ExportJob تنظیم معتبر تعداد تلاش در این نسخه Laravel نبود.
- حد حافظه ۱۶۰۰۰ مگابایت برای هر worker تعیین شده بود و `waits` روی ۶۰۰۰۰ ثانیه
  قرار داشت. این اعداد نه ظرفیت موازی ایجاد می‌کردند، نه هشدار انتظار مفید.
- اتصال چاپگر فقط timeout اتصال داشت؛ نوشتن داده محدود نبود و نوشتن ناقص هم
  کنترل نمی‌شد. SOAP نیز timeout درخواست نداشت.

آمار `top` فقط نشان می‌دهد در لحظه ثبت، CPU حدود ۹۸٪ idle و حافظه available حدود
۱۳ گیگابایت بوده است؛ از آن نمی‌توان تعداد CPU، علت دقیق توقف یک PID، سلامت Redis
یا سرعت query/ERP/چاپگر را نتیجه گرفت. Pending در Horizon به‌تنهایی اثبات بن‌بست نیست.

## چیدمان جدید

| صف | worker در production | timeout جاب / supervisor | حافظه worker (MB) |
| --- | --- | --- | --- |
| `default` | ۲ تا ۴ | پیش‌فرض supervisor: ۱۸۳۰ ثانیه | ۵۱۲ |
| `sms` | ۲ | ۳۰ / ۶۰ ثانیه | ۱۲۸ |
| `printing` | ۱ | ۹۰ / ۱۲۰ ثانیه | ۲۵۶ |
| `exports` | ۱ تا ۲ | ۱۸۰۰ / ۱۸۳۰ ثانیه | ۱۰۲۴ |
| `imports` | ۱ | ۱۸۰۰ / ۱۸۳۰ ثانیه | ۱۰۲۴ |
| `unique` | ۱ | پیش‌فرض supervisor: ۳۳۰ ثانیه | ۲۵۶ |
| `parallel` | ۱ | پیش‌فرض supervisor: ۳۳۰ ثانیه | ۲۵۶ |

در حالت پیش‌فرض حداکثر ۱۲ worker وجود دارد. سقف‌های بازیافت حافظه workerها در مجموع
حدود ۶ گیگابایت هستند؛ این سقف‌ها بعد از جاب بررسی می‌شوند و محدودیت سخت RSS نیستند.
مصرف واقعی peak یک export باید روی سرور اندازه‌گیری شود. تعداد workerها نقطه شروع
محافظه‌کارانه است و تضمین تأخیر مشخص یا جایگزین benchmark نیست.

workerهای `default` و `exports` از `auto` استفاده می‌کنند؛ پیامک ظرفیت مستقل دارد.
پردازش چاپ و دو صف ERP همچنان تک‌worker است. نام `parallel` به معنای تغییر ظرفیت
این صف در این اصلاح نیست. workerها پس از زمان/تعداد جاب مشخص بازیافت می‌شوند.
محیط‌های local/staging/testing و محیط ناشناخته، برای هر صف یک worker می‌گیرند.

`retry_after=1860` برای Redis/database/beanstalkd از طولانی‌ترین timeout بیشتر است.
این گزینه مدت رزرو جاب است و فاصله retry خودکار ERP نیست. تعداد تلاش‌ها یک است؛
retry خودکار چاپ، پیامک و import می‌تواند اثر تکراری ایجاد کند. در خطای ارتباط ERP
هم ممکن است عملیات سمت ERP انجام شده باشد؛ پیش از ارسال مجدد بررسی لازم است.
timeoutهای HTTP پیامک ۵/۲۰ ثانیه و SOAP پنج/۳۰۰ ثانیه‌اند. ارسال چاپ، پس از اتصال
پنج‌ثانیه‌ای، حداکثر حدود ده ثانیه فرصت نوشتن دارد؛ پذیرش داده توسط socket، تأیید
چاپ فیزیکی نیست.

ExportJob و ImportJob هنگام failure عادی یا timeout قابل مدیریت، `custom_jobs.status`
را `failed` و `result.message` را پیام فارسی می‌کنند. فرانت باید `failed` را پایان
polling بداند. `SIGKILL`، OOM سیستم یا قطع DB می‌تواند مانع اجرای callback شود؛
رکوردهای قدیمی `started` خودکار تغییر وضعیت نمی‌گیرند. جاب `finished` مجدداً اجرا
نمی‌شود. این guard قفل همزمانی و تضمین exactly-once نیست.

## قبل از اولین استقرار

دستورها را از ریشه پروژه روی سرور اجرا کنید. چون PM2 فعلی با `sudo pm2` دیده شده،
برای مدیریت همان daemon همین کاربر را حفظ کنید؛ جابه‌جایی کاربر PM2 یک daemon دیگر
می‌سازد. کاربر اجرای PHP و دسترسی نوشتن storage باید مطابق نصب فعلی بماند.

```sh
git branch --show-current
nproc
php -v
php -m | grep -E 'pcntl|posix'
sudo pm2 --version
sudo pm2 describe laravel-horizon
sudo pm2 describe laravel-rabbitmq
sudo pm2 logs laravel-horizon --lines 100 --nostream
```

برنچ باید `BC` باشد. در `.env`، `APP_ENV=production` و در نصب Horizon،
`QUEUE_CONNECTION=redis` لازم است. اگر اتصال عمومی فعلاً `rabbitmq` است، پیش از
تغییر، مسیرهای RabbitMQ و فرمان process مربوط به آن را بررسی کنید؛ Horizon پیام‌های
RabbitMQ را مصرف نمی‌کند. consumer RabbitMQ باید اتصال خودش را صریح انتخاب کند.
از روی `pm2 list` نمی‌توان فرمان آن process را تشخیص داد. این اصلاح consumer آن
را تغییر نمی‌دهد و اتصال Redis را روی همه dispatchها تحمیل نمی‌کند.

متغیرهای اختیاری تنظیم ظرفیت production:

```dotenv
HORIZON_DEFAULT_MAX_PROCESSES=4
HORIZON_SMS_PROCESSES=2
HORIZON_EXPORT_MAX_PROCESSES=2
```

مقدار اول حداقل ۲ و دو مقدار دیگر حداقل ۱ باشند. بدون تعداد CPU و اندازه‌گیری
مصرف DB/RAM، افزایش بیشتر توصیه نمی‌شود. اگر `HORIZON_PHP_BINARY` را برای PM2 تعیین
می‌کنید، مسیر مطلق PHP CLI صحیح باشد. این متغیر از محیط shell خوانده می‌شود، نه `.env`.

## عبور از تنظیمات قدیمی

جاب‌های از قبل enqueue شده، نام صف خود را حفظ می‌کنند و به صف جدید منتقل نمی‌شوند.
`default` همچنان مصرف می‌شود، ولی backlog قدیمی پیامک می‌تواند تا تخلیه آن منتظر بماند.
همچنین رزروهای قدیمی Redis، expiry قبلی خود را دارند؛ تغییر `retry_after` آن‌ها را
بازنویسی نمی‌کند. افزایش ظرفیت همزمان با worker قدیمی و رزروهای منقضی ممکن است همان
چاپ/import/ERP را دوباره اجرا کند.

برای گذار، ابتدا تولید درخواست‌های صف را موقتاً متوقف کنید، Horizon فعلی را pause
کنید و اجازه دهید جاب‌های فعال تمام شوند. در نبود دسترسی به سرور، پایان واقعی جاب‌ها
قابل تأیید نبوده است. جاب stuck را با بررسی لاگ/PID و اثر خارجی آن جداگانه تعیین تکلیف
کنید؛ `queue:clear`، `horizon:clear` و `queue:retry all` راه مهاجرت نیستند.

```sh
php artisan horizon:pause
```

وقتی جاب فعالی باقی نمانده، process قدیمی را متوقف کنید. این مرحله باید پس از پایان
جاب‌ها باشد، چون پیکربندی PM2 قبلی برای ما مشخص نیست. قبل از اجرای نسخه جدید، نبود
workerهای باقی‌مانده را در فهرست processهای همان پروژه تأیید کنید.

```sh
sudo pm2 stop laravel-horizon
sudo pm2 describe laravel-horizon
```

پس از پایان workerهای قبلی، تنظیم PHP CLI و محیط را با نصب قبلی تطبیق دهید، سپس:

```sh
php artisan config:cache
sudo pm2 delete laravel-horizon
sudo pm2 start deployment/ecosystem.config.js --only laravel-horizon
sudo pm2 save
php artisan horizon:status
sudo pm2 logs laravel-horizon --lines 100 --nostream
```

حذف process در این مرحله فقط ثبت process Horizon در PM2 را عوض می‌کند؛ باید جاب‌های
فعال تمام شده باشند. سرویس‌های io-script، laravel-rabbitmq و nuxt-front در این فرمان‌ها
تغییر نمی‌کنند. queue و داده‌های Redis پاک نمی‌شوند. اگر PM2 envهایی به process قبلی
تزریق می‌کرد، قبل از حذف آن‌ها را به تنظیم deployment منتقل کنید؛ فایل پیشنهادی شامل
رمز یا مسیر حدسی سرور نیست.

PM2 به‌صورت پیش‌فرض SIGINT می‌فرستد. فرمان Horizon در نسخه نصب‌شده این signal را
برای توقف graceful master مدیریت می‌کند؛ ولی فرستادن SIGINT به کل درخت processها
می‌تواند workerها را هنگام اجرای جاب متوقف کند. `treekill=false` اجازه می‌دهد Horizon
فرزندانش را خودش با SIGTERM متوقف کند. `kill_timeout`
برابر ۱۹۰۰۰۰۰ میلی‌ثانیه است تا از timeout طولانی‌ترین جاب بیشتر باشد. در توقف اجباری
یا crash خود master، احتمال worker یتیم وجود دارد؛ قبل از start مجدد PIDها را بررسی
کنید. دستور عادی برای انتشارهای بعدی `php artisan horizon:terminate` است، پس از
`config:cache` و با PM2 autorestart فعال.

## تأیید بعد از استقرار

در Horizon صف‌های `sms`، `printing`، `exports` و `imports` و تعداد workerهای مستقل
را بررسی کنید. یک export واقعی و یک پیامک مجاز را همزمان اجرا کنید؛ پیامک جدید باید
از worker مستقل دریافت شود. زمان انتظار/runtime و شکست‌های timeout را پیش و پس از
تغییر مقایسه کنید. اگر export همچنان کند است، queryها، تعداد ردیف‌ها، N+1 و broadcast
پیشرفت هر ردیف را با نمونه واقعی profile کنید؛ افزایش worker زمان اجرای خود query
یا ERP را کم نمی‌کند.

Horizon `waits` بر حسب ثانیه است؛ تنظیم threshold به‌تنهایی پیام هشدار ارسال نمی‌کند.
مسیر notification در HorizonServiceProvider باید جداگانه پیکربندی شود. وضعیت `online`
در PM2 نیز به‌تنهایی سالم بودن worker یا اتصال Redis را ثابت نمی‌کند.

منابع رسمی:
- [Horizon برای Laravel 10](https://laravel.com/docs/10.x/horizon)
- [Queue timeout و retry_after](https://laravel.com/docs/10.x/queues#job-expirations-and-timeouts)
- [PM2 graceful shutdown](https://pm2.keymetrics.io/docs/usage/signals-clean-restart/)
- [PM2 ecosystem](https://pm2.keymetrics.io/docs/usage/application-declaration/)

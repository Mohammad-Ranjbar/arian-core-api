<!DOCTYPE html>
<html lang="fa" dir="rtl">
<head>
    <meta charset="UTF-8">
    <title>گواهی انطباق</title>
</head>

<body style="
  margin:0;
  padding:0;
  background:#ffffff;
">

<div style="
  width:210mm;
  height:297mm;
  margin:0 auto;
  padding:30mm 25mm;
  box-sizing:border-box;
  font-size:14px;
  line-height:2;
  color:#000;
  position:relative;
  unicode-bidi:bidi-override;
">

    <div style="text-align:right;">
        طرف اول گواهی
    </div>

    <div style="
    text-align:center;
    margin-top:20mm;
    margin-bottom:15mm;
    font-weight:bold;
  ">
        <div>طرف اول</div>
        <div>وسایل نقلیه کامل</div>
        <div>گواهی انطباق</div>
    </div>

    <div style="text-align:justify;">
        سعید مهرجو مدیرعامل عامل شرکت آرین پارس موتور بدین وسیله گواهی می‌کند که وسیله نقلیه با مشخصات زیر:
    </div>

    <div style="margin-top:6mm;">
        <div>۱-۱ سازنده (نام تجاری سازنده): {{$data['manufacturer']}}</div>
        <div>۲-۱ نوع:  {{$data['type']}}</div>
        <div>گونه: {{$data['brand']}}</div>
        <div>مدل: {{$data['brand']}}</div>
        <div>۳-۱ نام تجاری: <strong>{{$data['brand']}}</strong></div>
        <div>۳-۲ گروه وسیله نقلیه: M1</div>

        <div>
            ۴-۱ نام و نشانی سازنده وسیله نقلیه پایه:
            تهران، کیلومتر ۹ جاده مخصوص کرج، خیابان نخ زرین، پلاک ۲۳
        </div>

        <div>
            آدرس کارخانه:
            استان مرکزی، شهرستان خمین، کیلومتر ۵ جاده گلپایگان
        </div>

        <div>
            ۵-۱ مکان و روش نصب پلاک‌های قانونی:
            جلو سمت راست، روی ستون وسط (B Pillar)
        </div>

        <div>
            مکان شماره شناسایی وسیله نقلیه:
            جلو سمت راست، داخل اتاق، زیر صندلی سرنشین و عقب سمت چپ، حک شده بر روی بدنه
        </div>

        <div>۶-۱ نام و نشانی نماینده سازنده (در صورت وجود): -</div>

        <div>
            ۷-۱ شماره شناسایی وسیله نقلیه:
            <strong>{{$data['vin']}}</strong>
        </div>
    </div>

    <div style="margin-top:10mm; text-align:justify;">
        با تمامی خصوصیات نوع تشریح شده در تاییدیه
        <span style="direction:ltr; display:inline-block;">IRN*6924*0297</span>
        صادره در ۱۴۰۰/۱۲/۲۱
        می‌تواند به صورت دائم شماره‌گذاری شود.
    </div>


    <div style="
    position:absolute;
    bottom:35mm;
    right:25mm;
  ">
        تاریخ:
    </div>

    <div style="
    position:absolute;
    bottom:25mm;
    left:25mm;
    text-align:left;
  ">
        <div>امضاء:</div>
        <div style="
      margin-top:5mm;
      font-size:36px;
      font-family:'iransans';
    ">
            S
        </div>
    </div>

</div>

</body>
</html>

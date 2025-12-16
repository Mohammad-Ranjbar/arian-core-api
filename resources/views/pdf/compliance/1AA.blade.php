<!DOCTYPE html>
<html dir="rtl" lang="fa">
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
    <div style="text-align:right;">
        طرف اول گواهی
    </div>

    <div style="text-align:justify;">
        امضاء کننده زیر: سعید مهرجو مدیر عامل شرکت آرین پارس موتور بدین وسیله گواهی می کند که وسیله نقلیه با مشخصات زیر:
    </div>

    <div style="margin-top:6mm;">
        <div>۱-۱ سازنده (نام تجاری سازنده):
            <b>مجتمع صنعتی آرین پارس موتور</b>
        </div>
        <div>۲-۱ نوع:
            <b>AA1</b>

        </div>
        <div style="padding-right: 1rem">- گونه:
            <b>DPL4BL</b>
        </div>
        <div style="padding-right: 1rem">- مدل:
            <b>E120T51</b>
        </div>
        <div>۱-۲-۱ نام تجاری:
            <strong> LAMARI NEO </strong></div>
        <div>۳-۱ گروه وسیله نقلیه:
            <b>1M</b>
        </div>

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

        <div style="padding-right: 1rem"> -
            مکان شماره شناسایی وسیله نقلیه:
            جلو سمت راست، داخل اتاق، زیر صندلی سرنشین و عقب سمت چپ، حک شده بر روی بدنه
        </div>

        <div>۶-۱ نام و نشانی نماینده سازنده (در صورت وجود): -</div>

        <div>
            ۷-۱ شماره شناسایی وسیله نقلیه:
            <strong>{{$vin}}</strong>
        </div>
    </div>

    <div style="margin-top:10mm; text-align:justify;">
        با تمامی خصوصیات نوع تشریح شده در تاییدیه
        <span style="direction:ltr; display:inline-block;">IRN*6924*0297</span>
        صادره در ۱۴۰۰/۱۲/۲۱
        می‌تواند به صورت دائم شماره ‌گذاری شود.
    </div>

    <div style="display:flex; width: 100%;  margin-top: 30mm; ">
        <div style="width: 80%;">
            تاریخ:
        </div>

        <div>امضاء:</div>
        <div>
         <img src="data:image/png;base64,{{base64_encode(file_get_contents(public_path('sign.png')))}}" alt="sign" width="100" title="sign">
        </div>

    </div>
</div>

</body>
</html>

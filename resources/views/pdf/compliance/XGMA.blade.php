<!DOCTYPE html>
<html lang="fa" dir="rtl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>گواهینامه تطابق تولید - XG822FL</title>
    <link rel="stylesheet" href="{{'/assets/822-63.css'}}">
</head>
<body>
<main class="page">
    <img class="letterhead" src="{{'/assets/images/arian-pars-header.png'}}" alt="آرین پارس">

    <p class="date">تاریخ: <span id="certificate-date" dir="ltr">
            {{jdate()->format('Y/m/d')}}
        </span></p>

    <section class="content">
        <h1>گواهینامه تطابق تولید - <span class="latin" dir="ltr">COC</span></h1>

        <p class="intro">
            بدینوسیله شرکت <span id="issuer">آرین پارس موتور</span> گواهی می‌نماید که ماشین با مشخصات زیر
            منطبق با موارد مربوط به نوع شرح داده شده در استاندارد ماشین‌های راهسازی، ساختمانی و معدنی
            (<span id="standard" class="latin" dir="ltr">INSO-12544: 1<sup>st</sup> Revision 2015</span>)
            و با شماره تایید نوع <span id="approval-number" class="latin" dir="ltr">
                @if($carModelReference === "XSHEX013MD")
                    IRN*12544*0011
                @elseif($carModelReference === "XSHEX023MD")
                    IRN*12544*0012
                @endif

            </span> است:
        </p>

        <table class="specifications" aria-label="مشخصات ماشین">
            <colgroup>
                <col class="number-column">
                <col class="label-column">
                <col class="value-column">
            </colgroup>
            <tbody>
            <tr>
                <td class="number">۱.</td>
                <th scope="row">شماره شناسایی ماشین (<span class="latin" dir="ltr">PIN</span>):</th>
                <td><span id="vin" class="latin" dir="ltr">{{$vin}}</span></td>
            </tr>
            <tr>
                <td class="number">۲.</td>
                <th scope="row">نوع:</th>
                <td><span id="machine-type" class="latin" dir="ltr">
                          @if($carModelReference === "XSHEX013MD")
                            XG822FL
                        @elseif($carModelReference === "XSHEX023MD")
                            XG836FL
                        @endif

                    </span></td>
            </tr>
            <tr>
                <td class="number">۳.</td>
                <th scope="row">سازنده:</th>
                <td><span id="manufacturer">آرین پارس موتور</span></td>
            </tr>
            <tr>
                <td class="number">۴.</td>
                <th scope="row">گروه:</th>
                <td><span id="category">بیل مکانیکی چرخ زنجیری</span></td>
            </tr>
            <tr>
                <td class="number">۵.</td>
                <th scope="row">آدرس دقیق سازنده:</th>
                <td><span id="manufacturer-address">استان مرکزی - شهرستان خمین - کیلومتر ۵ جاده گلپایگان</span></td>
            </tr>
            <tr>
                <td class="number">۶.</td>
                <th scope="row">مکان نصب پلاک شناسایی (<span class="latin" dir="ltr">PIN</span>):</th>
                <td><span id="pin-plate-location">روی شاسی بالا در موقعیت پایین کابین</span></td>
            </tr>
            <tr class="double-row">
                <td class="number">۷.</td>
                <th scope="row">مکان نصب شماره شاسی بر روی شاسی:</th>
                <td>
                    <span id="upper-chassis-number-location">شاسی بالا: پایین بوم</span><br>
                    <span id="lower-chassis-number-location">شاسی پایین: روی فریم</span>
                </td>
            </tr>
            <tr>
                <td class="number">۸.</td>
                <th scope="row">تعداد محورها:</th>
                <td><span id="axles">کاربرد ندارد</span></td>
            </tr>
            <tr>
                <td class="number">۹.</td>
                <th scope="row">تعداد چرخ‌ها:</th>
                <td><span id="wheels">کاربرد ندارد</span></td>
            </tr>
            <tr>
                <td class="number">۱۰.</td>
                <th scope="row">جرم کاری:</th>
                <td><span id="operating-mass">
                          @if($carModelReference === "XSHEX013MD")
                            21500
                        @elseif($carModelReference === "XSHEX023MD")
                            35000
                        @endif
                        کیلوگرم</span></td>
            </tr>
            <tr>
                <td class="number">۱۱.</td>
                <th scope="row">حداکثر توان خالص یا توان اسمی:</th>
                <td><span id="rated-power">
                             @if($carModelReference === "XSHEX013MD")
                            133
                        @elseif($carModelReference === "XSHEX023MD")
                            212
                        @endif
                        کیلووات در ۲۰۰۰ دور بر دقیقه</span></td>
            </tr>
            <tr>
                <td class="number">۱۲.</td>
                <th scope="row">تعداد سیلندر:</th>
                <td><span id="cylinders">
                              @if($carModelReference === "XSHEX013MD")
                            4
                        @elseif($carModelReference === "XSHEX023MD")
                            6
                        @endif
                    </span></td>
            </tr>
            <tr>
                <td class="number">۱۳.</td>
                <th scope="row">حجم موتور:</th>
                <td><span id="engine-capacity"><bdi dir="ltr">
                                   @if($carModelReference === "XSHEX013MD")
                                5.193
                            @elseif($carModelReference === "XSHEX023MD")
                                7.49
                            @endif
                        </bdi> لیتر</span></td>
            </tr>
            <tr>
                <td class="number">۱۴.</td>
                <th scope="row">سیستم انتقال قدرت:</th>
                <td><span id="transmission">سیستم هیدرولیک</span></td>
            </tr>
            <tr>
                <td class="number">۱۵.</td>
                <th scope="row">اندازه تایرها:</th>
                <td><span id="tyre-size">کاربرد ندارد</span></td>
            </tr>
            <tr>
                <td class="number">۱۶.</td>
                <th scope="row">نوع روغن هیدرولیک (توصیه سازنده):</th>
                <td><span id="hydraulic-oil" class="latin" dir="ltr">ISO HM46</span></td>
            </tr>
            <tr>
                <td class="number">۱۷.</td>
                <th scope="row">نوع روغن موتور (توصیه سازنده):</th>
                <td><span id="engine-oil" class="latin" dir="ltr">API CH4, CI4 - 15w40</span></td>
            </tr>
            <tr>
                <td class="number">۱۸.</td>
                <th scope="row">حداکثر سرعت:</th>
                <td><span id="maximum-speed">۵ کیلومتر بر ساعت</span></td>
            </tr>
            <tr>
                <td class="number">۱۹.</td>
                <th scope="row">شعاع دور زدن:</th>
                <td><span id="turning-radius">کاربرد ندارد</span></td>
            </tr>
            </tbody>
        </table>
    </section>

    <footer class="letterfoot">
        <img src="{{'/assets/images/arian-pars-footer.png'}}" alt="اطلاعات تماس آرین پارس و نشانی دفتر مرکزی و کارخانه">
    </footer>
</main>
</body>
</html>

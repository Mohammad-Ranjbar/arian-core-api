<!DOCTYPE html>
<html lang="fa" dir="rtl">
<head>
    <meta charset="UTF-8">
    <title>Synchronous</title>
</head>
<body dir="rtl">
<table style="width: 100%; border:solid 1px #555">
    <tr>
        <td>
            شماره سنکرون
            {{$number}}
        </td>
        <td>
            عنوان سنکرون
            {{$title}}
        </td>
        <td>
            <img src="{{$barcode}}" alt=""/>
        </td>
    </tr>
</table>
<br/>


<table style="width: 100%">
    <tr>
        <th style="text-align: center">ردیف</th>
        <th style="text-align: center">شماره شاسی</th>
        <th style="text-align: center">CKD/SKD</th>
        <th style="text-align: center">مدل</th>
        <th style="text-align: center">شرح محصول</th>
        <th style="text-align: center">قطعه</th>
        <th style="text-align: center">رنگ</th>
        <th style="text-align: center">گرید</th>
        <th style="text-align: center">تاریخ خروج</th>
        <th style="text-align: center">زمان خروج</th>
    </tr>
    @foreach($items as $key=>$row)
        <tr class="{{$key%2 ? 'odd' : 'even'}}">
            <td style="text-align: center">{{$key+1}}</td>
            @foreach($row as $column)
                <td style="text-align: center">{{$column}}</td>
            @endforeach
        </tr>
    @endforeach
</table>
</body>
</html>

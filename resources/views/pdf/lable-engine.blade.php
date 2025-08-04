<!DOCTYPE html>
<html lang="fa" dir="rtl">
<head>
    <meta charset="UTF-8">
    <title>Lable Engine</title>
</head>
<body dir="rtl">
<div style="text-align: center">
    @if($vin)
        <img src="{{$vin_barcode}}" alt=""/>
        <br>
        <span style="font-size: 10px">vin: {{$vin}}</span>
    @else
        <span>
            شماره شاسی یافت نشد.
        </span>
    @endif
    <br>
    <img src="{{$engine_barcode}}" alt=""/>
    <br>
    <span style="font-size: 10px">engine number: {{$engine}}</span>
</div>
</body>
</html>

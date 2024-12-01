<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Synchronous</title>
</head>
<body>
<table style="width: 100%">
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
<br />


<table style="width: 100%">
    <tr>
        <th>ردیف</th>
        <th>شماره شاسی</th>
        <th>مدل</th>
        <th>شرح محصول</th>
        <th>رنگ</th>
        <th>تاریخ خروج</th>
        <th>زمان خروج</th>
    </tr>
    @foreach($items as $key=>$row)
        <tr class="{{$key%2 ? 'odd' : 'even'}}">
            <td>{{$key+1}}</td>
            @foreach($row as $column)
                <td>{{$column}}</td>
            @endforeach
        </tr>
    @endforeach
</table>
</body>
</html>

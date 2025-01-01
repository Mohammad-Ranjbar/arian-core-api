<!DOCTYPE html>

<html>

<head>

    <title>test</title>


</head>

<body dir="rtl">

<ul dir="ltr">
    <li>
        id : {{ $erpJob->id }}
    </li>
    <li>
        status : {{ $erpJob->status }}
    </li>
</ul>

<table class="table table-striped" style="text-align: center;width: 100%;margin-bottom: 2rem">
    <thead style="background: #bbb;color: black;">
    <tr>
        <th scope="col" style="width: 33%">بخش</th>
        <th scope="col" style="width: 33%">متد</th>
        <th scope="col" style="width: 33%">تعداد فراخوانی</th>
    </tr>
    </thead>
    <tbody>
    <tr>
        <td style="border: 1px solid #000000">
            {{$erpJob->section}}
        </td>
        <td style="border: 1px solid #000000">
            {{$erpJob->method}}
        </td>
        <td style="border: 1px solid #000000">
            {{$erpJob->tries}}
        </td>

    </tr>
    </tbody>
</table>
<table class="table table-striped" style="text-align: center;width: 100%;margin-bottom: 2rem">
    <thead style="background: #bbb;color: black;">
    <tr>
        <th scope="col" style="width: 33%">پارامترها</th>
        <th scope="col" style="width: 33%">کاربر</th>
        <th scope="col" style="width: 33%">تاریخ</th>
    </tr>
    </thead>
    <tbody>
    <tr>
        <td style="border: 1px solid #000000">
            <ul dir="ltr">
                @foreach($erpJob->parameters as $key => $parameter)
                    <li>
                        {{$key}} => {{$parameter}}
                    </li>
                @endforeach
            </ul>
        </td>
        <td style="border: 1px solid #000000">
            {{$erpJob->user->name}}
        </td>
        <td style="border: 1px solid #000000" dir="ltr">
            {{jdate($erpJob->created_at)}}
        </td>
    </tr>
    </tbody>
</table>
<div style="background-color: #bbbbbb;color: #000000;padding: 1rem;direction: ltr">
    {{$erpJob->description}}
</div>

</body>

</html>

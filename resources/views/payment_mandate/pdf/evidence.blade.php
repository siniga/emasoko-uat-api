<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta http-equiv="Content-Type" content="text/html; charset=utf-8" />
    <meta http-equiv="X-UA-Compatible" content="ie=edge">
    <title>Evidence of Agrement</title>
    <style>
        * {
            font-family: "Roboto, Cerebri, Sans-serif" !important;
        }

        body {
            width: 100%;
        }

        header {
            position: fixed;
            top: -40px;
            left: 0px;
            right: 0px;
            height: 80px;
            font-size: 16px !important;
        }

        .header-container {
            display: inline-block;
        }

        .table-header {
            width: 100%;
            padding: 5px 2px 5px 2px;
        }

        .table-header span {
            font-size: 12px;
            font-weight: medium;
            color: #0D47A1;
        }

        .main-body {
            margin-top: 60px;
        }

        .border-div {
            margin-top: 10px;
            border: 2px solid #0D47A1;
        }

        .header-text {
            font-size: 12px;
        }

        .sub-header-text {
            font-size: 12px;
        }

        .detail-table td,
        .detail-table th {
            border: 1px solid gainsboro;
            padding: .5rem .5rem;
        }

        .detail-table th {
            background-color: aliceblue;
        }

        .note-text {
            text-align: justify;
            font-size: 12px;
        }

        .page-break {
            page-break-after: always;
        }

        .contract-desc {
            font-size: 10px;
            text-align: justify;
        }
    </style>
</head>

<body>
    <header>
        <table class="table-header">
            <tbody>
                <tr>
                    <td>
                        <img src="{{ public_path('assets/images/solocode_logo.png') }}" height="60" alt="Emakato">
                    </td>

                    <td>
                        <span>Solocodes Limited|15th Floor Victoria Noble Center |P.O. Box 77499 | Dar es salaam.</span> <br>
                        <span>Mob: +255 684 512 512 | www.emakato.co.tz</span>
                    </td>      
                </tr>
            </tbody>

        </table>
        <div class="border-div"></div>
    </header>

    <div class="main-body">
        <span class="text-center note-text">
            Hii inasimama kama fomu ya ridhaa kati ya Mteja , eMakato, na {{ $debit_order->partner->business_name }}.
        </span>

        <p class="contract-desc">
            Mimi mteja ninaridhia malipo yafanyike kutoka kwenye akaunti yangu kulingana na ombi/maelekezo nitakayotuma kupitia kwa {{ $debit_order->partner->business_name }} kwa kutumia mfumo wa EMakato.
        </p>

        <p class="contract-desc">
            Mimi mteja ninaridhia kuwa fedha zitakazokatwa kutoka kwenye akaunti yangu inaweza kutolewa tarehe tofauti kila mwezi kulingana na muda na
            aina ya njia za kutoa.
        </p>

        <p class="contract-desc">
            Mimi mteja nitawataarifu {{ $debit_order->customerAccount->account->name }} na {{ $debit_order->partner->business_name }} kwa maandishi iwapo nitataka kukatisha
            maagizo haya.
        </p>

        <p class="contract-desc">
            Mimi mteja ninaridhia kwa Solocodes kukusanya na kuchakata taarifa zangu kutoka kwenye Taasisi za mikopo mbalimbali ili
            kuweza kuboresha mfumo wa eMakato.
        </p>

        <p class="contract-desc">
            Mimi mteja ninaridhia kwamba number ya makubaliano (otp) kutumika kama sahihi yangu.
        </p>

        <p class="contract-desc">
            Mimi mteja ninakubali kwamba nambari ya simu niliyotoa kwa {{ $debit_order->partner->business_name }} inahusishwa moja kwa moja na (Benki/MNO) nilitoa. Aidha, nina
            thibitisha kwamba nimepokea number ya makubaliano (otp) kwenye nambari hii ya simu na naelewa kuwa utatumika
            kuthibitisha ridhaa yangu kwa miamala
        </p>

        <p class="contract-desc">
            Jina kamili: {{ $debit_order->customerAccount->account_name }}<br /><br />
            Sahihi iliyoidhinishwa: {{ $debit_order->otp_code }}
        </p>
    </div>

</body>

</html>

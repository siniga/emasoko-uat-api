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

        h2 {
            font-size: 16px;
            margin-top: 0px;
            padding-top: 0px;
        }

        header {
            position: fixed;
            top: -40px;
            left: 0px;
            right: 0px;
            height: 70px;
            font-size: 20px !important;
        }

        .header-container {
            display: inline-block;
        }

        .table-header {
            width: 100%;
            padding: 5px 2px 5px 2px;
            margin-bottom: 10px;
        }

        .table-header span {
            font-size: 14px;
            font-weight: bold;
            color: #0D47A1;
        }

        footer {
            position: fixed;
            bottom: 0px;
            left: 0px;
            right: 0px;
            height: 70px;
            font-size: 20px !important;
        }

        .main-body {
            margin-top: 80px;
        }

        .border-div {
            margin-top: 10px;
            border: 3px solid #0D47A1;
        }

        .header-text {
            font-size: 15px;
            font-weight: bold;
        }

        .sub-header-text {
            font-size: 14px;
            font-weight: 400;
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
            font-size: 15px;
            font-weight: 900;
        }

        .page-break {
            page-break-after: always;
        }

        .contract-desc {
            font-size: 13px;
            text-align: justify;
            /* font-weight: 300; */
        }
    </style>
</head>

<body>
    <header>
        <table class="table-header">
            <tbody>
                <tr>
                    <td>
                        <img src="{{ public_path('assets/images/emakato_logo_colored.png') }}" width="150px"
                            alt="Emakato">
                    </td>
                </tr>
            </tbody>

        </table>
        <div class="border-div"></div>
    </header>

    <footer>
        <div class="border-div"></div>
        <table class="table-header">
            <tbody>
                <tr>
                    <td class="text-center">
                        <span>Solocodes Limited|15th Floor Victoria Noble Center |P.O. Box 77499 | Dar es salaam.</span> <br>
                        <span>Mob: +255 684 512 512 | www.emakato.co.tz</span>
                    </td>
                </tr>
            </tbody>

        </table>
    </footer>

    <div class="main-body">
        <h2 class="text-center">
            Hii inasimama kama fomu ya ridhaa kati ya Mteja , Emakato, na {{ $debit_order->partner->business_name }}.
        </h2>

        <p class="contract-desc">
            Mimi Mteja nina Ridhia malipo yafanyike kutoka kwenye akaunti yangu kulingana na ombi/maelekezo nitakayotuma kupitia kwa {{ $debit_order->partner->business_name }} kwa kutumia mfumo wa EMakato.
        </p>

        <p class="contract-desc">
            Mimi mteja Nina Ridhia kuwa fedha zitakazokatwa kutoka kwenye akaunti yangu inaweza kutolewa tarehe tofauti kila mwezi kulingana na muda na
            aina ya njia za kutoa.
        </p>

        <p class="contract-desc">
            Mimi mteja Nitawataarifu {{ $debit_order->customerAccount->account->name }} na {{ $debit_order->partner->business_name }} kwa maandishi iwapo nitataka kukatisha
            maagizo haya.
        </p>

        <p class="contract-desc">
            Mimi mteja nina ridhia kwa Solocodes kukusanya na kuchakata taarifa zangu kutoka kwenye Taasisi za mikopo mbalimbali ili
            kuweza kuboresha Mfumo wa emakato.
        </p>

        <p class="contract-desc">
            Mimi mteja nina ridhia kwamba
            number ya makubaliano (otp) kutumika kama sahihi yangu.
        </p>

        <p class="contract-desc">
            Mimi mteja ninakubali kwamba nambari ya simu niliyotoa kwa {{ $debit_order->partner->business_name }} inahusishwa moja kwa moja na (Benki/MNO) nilitoa. Aidha, nina
            thibitisha kwamba nimepokea number ya makubaliano (otp) kwenye nambari hii ya simu na naelewa kuwa utatumika
            kuthibitisha ridhaa yangu kwa miamala
        </p>

        <p class="contract-desc">
            Jina kamili: {{ $debit_order->customerAccount->account_name }}<br /><br />
            Sahihi Iliyoidhinishwa: {{ $debit_order->otp_code }}
        </p>
    </div>

</body>

</html>

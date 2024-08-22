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
            /* background-color: red; */
            /* margin: 0px 0px 0px 0px; */
        }

        h2 {
            font-size: 20px;
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
            /* background-color: red; */

            /* color: white; */
            /* text-align: center; */
            /* line-height: 35px; */
        }

        .header-container {
            display: inline-block;
        }

        footer {
            position: fixed;
            bottom: 0px;
            left: 0px;
            right: 0px;
            height: 70px;
            font-size: 20px !important;
            /* background-color: #000; */
            /* color: white;
    text-align: center; */
            /* line-height: 35px; */
        }

        .table-header {
            width: 100%;
            /* border: 1px red solid; */
            padding: 5px 2px 5px 2px;
            margin-bottom: 10px;
        }

        .table-header span {
            font-size: 14px;
            font-weight: bold;
            color: #0D47A1;
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
            font-size: 17px;
            font-weight: 900;
        }

        .page-break {
            page-break-after: always;
        }

        .contract-desc {
            font-size: 16px;
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
                        <img src="{{ public_path('assets/images/emakato_logo_colored.png') }}" width="150px" alt="Emakato">
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
                    <td class="text-left">
                        <span>+255 222 774 012</span> <br><span>+255 768 170 000</span>
                    </td>
                    <td class="text-center">
                    </td>
                    <td class="text-right text-sm-end" align="right">
                        <div>
                            <span>https://www.emakato.co.tz</span> <br><span>info@emakato.co.tz</span>
                        </div>
                    </td>
                </tr>
            </tbody>

        </table>
    </footer>

    <div class="main-body">
        <h2 class="text-center">A: Makubaliano kati ya mteja, Emakato na MFI husika.</h2>
        <p class="contract-desc">
            <p>Mimi Mteja ninaridhia kwa Emakato kama ifuatavyo:- <br></p>
            1. Nina Ridhia malipo yafanyike kutoka kwenye akaunti yangu kulingana na ombi/maelekezo nitakayotuma kupitia
            mfumo wa malipo wa Emakato <br> </p>

        <p class="contract-desc">
            2. Nina Ridhia kuwa fedha zitakazokatwa kutoka kwenye akaunti yangu inaweza kutolewa tarehe tofauti kila
            mwezi kulingana na muda na aina ya njia za kutoa na kulipa fedha kupitia mfumo wa malipo wa emakato.
            uliotajwa hapo juu <br /><br />

            3. Nitawataarifu {{ $debit_order->customerAccount->account->name }} na {{ $debit_order->partner->business_name }}. kwa maandishi iwapo nitataka kukatisha maagizo haya. <br /><br />

            4. Nina ridhia kwamba number ya makubaliano (otp) kutumika kama sahihi yangu. <br />
        </p>

       <p class="contract-desc">
            Jina kamili: {{ $debit_order->customerAccount->account_name }}<br/><br />
            Sahihi Iliyoidhinishwa:  {{ $debit_order->otp_code }} 
       </p>
    </div>

</body>

</html>

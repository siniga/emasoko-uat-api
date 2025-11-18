<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta http-equiv="Content-Type" content="text/html; charset=utf-8" />
    <meta http-equiv="X-UA-Compatible" content="ie=edge">
    <title>Proforma Invoice</title>

    <style>
        @import url(https://fonts.googleapis.com/css?family=Poppins:300,400,500,600,700&display=swap);
        * {
            font-family: "Poppins, Sans-serif" !important;
        }

        .invoice-box {
            max-width: 900px;
            margin: auto;
            padding: 30px;
            font-size: 12px;
            line-height: 12px;
            color: #424242;
        }

        .invoice-box table {
            width: 100%;
            line-height: inherit;
            text-align: left;
        }

        .invoice-box table th {
            font-size: 10px !important;
            line-height: 12px !important;
            text-align: left;
            vertical-align: middle;
            font-weight: 500 !important;
            color: #212121;
            padding: 4px;
        }

        .invoice-box table td {
            vertical-align: top;
            font-size: 10px !important;
            line-height: 12px !important;
            color: #424242;
            font-weight: 400 !important;
            padding: 4px;
        }

        footer {
            position: fixed;
            bottom: 0px;
            text-align: center;
            height: 70px;
            font-size: 10px !important;
        }

        .table-header {
            width: 100%;
            padding: 5px 2px 5px 2px;
            margin-bottom: 10px;
        }

        .table-header span {
            font-size: 10px;
             font-weight: 400 !important;
            color: #424242;
        }

        .font-weight-600{
            font-weight: 600 !important;
        }

        .text-uppercase{
            text-transform: uppercase;
        }

        .text-large{
            font-size: 32px;
        }

        .text-medium{
            font-size: 18px;
        }

        .text-s{
            font-size: 14px;
        }

        .text-xs{
            font-size: 10px;
        }
    </style>
</head>

<body>
    <div class="invoice-box">
        <table cellpadding="0" cellspacing="0">
            <tr class="top">
                <td>
                    <table>
                        <tr>
                            <td style="text-align: left;">
                                <img src="{{ public_path('assets/images/emakato_logo_colored.png') }}" width="150px"
                                    alt="Emakato">
                            </td>

                            <td style="text-align: right;">
                                <span class="text-large text-uppercase font-weight-600">Invoice</span>
                            </td>
                        </tr>
                    </table>
                </td>
            </tr>

            <tr>
                <td>
                    <table>
                        <tr>
                            <td>
                                <p class="text-uppercase text-xs font-weight-600">Issued To:</p>
                                <p class="text-xs">Peter Credit</p>
                                <p class="text-xs">TIN No: 00000000</p>
                                <p class="text-xs">P.o Box 6672</p>
                                <p class="text-xs">Dar es Salaam</p>
                            </td>

                            <td style="text-align: right;">
                                <p>&nbsp;</p>
                                <p>&nbsp;</p>
                                <p class="text-uppercase text-xs font-weight-600">Invoice No: 00001</p>
                                <p class="text-uppercase text-xs">Date: 31.10.2024</p>
                                <p class="text-uppercase text-xs">Due Date: 5.10.2024</p>   
                            </td>
                        </tr>
                    </table>
                </td>
            </tr>

            <tr>
                <td>
                    <table>
                        <tr>
                            <td>
                                <p class="text-uppercase font-weight-600 text-xs">Pay To:</p>
                                <p class="text-xs">NMB Bank</p>
                                <p class="text-xs">Account Name: Solocodes Limited</p>
                                <p class="text-xs">Account No: 22610035564</p>
                            </td>
                        </tr>
                    </table>
                </td>
            </tr>
        </table>

        <table>
            <tr>
                <th width="55%" class="text-uppercase" style="border-bottom: 1px solid #000000;">Description</th>
                <th width="20%" class="text-uppercase" style="border-bottom: 1px solid #000000;">Unit Price</th>
                <th width="12%" class="text-uppercase" style="border-bottom: 1px solid #000000;">Qty</th>
                <th width="13%" class="text-uppercase" style="border-bottom: 1px solid #000000;">Total</th>
            </tr>

            <tr>
                <td style="text-align: left; border-bottom: 1px solid #000000; font-size: 10px;" class="text-xs">
                    Annual Subscription Fee<br/>
                    22.10.2024 - 21.10.2025
                </td>
                <td style="text-align: left; border-bottom: 1px solid #000000; font-size: 10px;" class="text-xs">100,000</td>
                <td style="text-align: left; border-bottom: 1px solid #000000; font-size: 10px;" class="text-xs">1</td>
                <td style="text-align: left; border-bottom: 1px solid #000000; font-size: 10px;" class="text-xs">100,000</td>
            </tr>

            <tr>
                <td></td>
                <td></td>
                <td style="text-align: left; text-transform: uppercase; font-size: 10px;" class="text-xs">SUBTOTAL</td>
                <td style="text-align: left;" class="text-xs">82,000</td>
            </tr>
            <tr>
                <td></td>
                <td></td>
                <td style="text-align: left; text-transform: uppercase; font-size: 10px;" class="text-xs">Tax</td>
                <td style="text-align: left;" class="text-xs">82,000</td>
            </tr>

            <tr>
                <td></td>
                <td></td>
                <td style="text-align: left; text-transform: uppercase; font-size: 10px;" class="text-xs">Total</td>
                <td style="text-align: left;" class="text-xs">100,000</td>
            </tr>
        </table>


    </div>

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
</body>

</html>
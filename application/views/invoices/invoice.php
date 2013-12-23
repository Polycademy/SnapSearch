<?='<?xml version="1.0" encoding="UTF-8"?>'?>
<!DOCTYPE pdf SYSTEM "%resources%/dtd/doctype.dtd">
<pdf>
    <dynamic-page>
        <placeholders>
            <header>
                <div class="header">Receipt from SnapSearch (Polycademy)</div>
            </header>
        </placeholders>
        <div>
            <img class="logo" src="<?=$logo?>"></img>
        </div>
        <div class="address">
            <h2>Polycademy</h2>
            <p>Vibewire</p>
            <p>525 Harris St Ultimo</p>
            <p>Sydney Australia</p>
            <p>Ph: +61420925975</p>
            <p>http://polycademy.com</p>
        </div>
        <div class="bill">
            <h2>To</h2>
            <p>User: #<?=$userId?></p>
            <p>Email: <?=$email?></p>
            <p>Address: <?=$address?></p>
            <p>Post Code: <?=$postCode?></p>
            <p>Country: <?=$country?></p>
        </div>
        <div class="bill">
        	<h2>Reference</h2>
            <p>Number: #<?=$invoiceNumber?></p>
            <p>Date: <?=$date?></p>
        </div>
        <table class="invoice">
            <tr class="head">
                <td>Item</td>
                <td>Usage Rate</td>
                <td>Currency</td>
                <td>Amount</td>
                <td>Includes GST 10%</td>
            </tr>
            <tr>
                <td><?=$item?></td>
                <td class="center"><?=$usageRate?></td>
                <td class="center"><?=$currency?></td>
                <td class="center"><?=$amount?></td>
                <td class="center"><?=$tax?></td>
            </tr>
        </table>
    </dynamic-page>
</pdf>

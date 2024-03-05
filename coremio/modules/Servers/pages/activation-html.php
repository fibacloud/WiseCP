<table width="100%" border="0" cellpadding="3">

    <?php
        if(isset($options["ip"]) && $options["ip"]){
            ?>
            <tr>
                <td width="30%" style="border-bottom: 1px solid #eeeeee; padding: 5px;font-family: Calibri,Arial,Helvetica,sans-serif;">IP</td>
                <td width="70%" style="border-bottom: 1px solid #eeeeee; padding: 5px;font-family: Calibri,Arial,Helvetica,sans-serif;">
                    <?php
                        echo $options["ip"];
                    ?>
                </td>
            </tr>
            <?php
        }

        if(isset($options["hostname"]) && $options["hostname"]){
            ?>
            <tr>
                <td width="30%" style="border-bottom: 1px solid #eeeeee; padding: 5px;font-family: Calibri,Arial,Helvetica,sans-serif;">Hostname</td>
                <td width="70%" style="border-bottom: 1px solid #eeeeee; padding: 5px;font-family: Calibri,Arial,Helvetica,sans-serif;">
                    <?php
                        echo $options["hostname"];
                    ?>
                </td>
            </tr>
            <?php
        }
    ?>


    <?php
        if(isset($options["login"]["username"]) && $options["login"]["username"]){
            ?>
            <tr>
                <td width="30%" style="border-bottom: 1px solid #eeeeee; padding: 5px;font-family: Calibri,Arial,Helvetica,sans-serif;">Username</td>
                <td width="70%" style="border-bottom: 1px solid #eeeeee; padding: 5px;font-family: Calibri,Arial,Helvetica,sans-serif;">
                    <?php echo $options["login"]["username"]; ?>
                </td>
            </tr>
            <?php
        }

        if(isset($options["login"]["password"]) && $options["login"]["password"]){
            ?>
            <tr>
                <td width="30%" style="border-bottom: 1px solid #eeeeee; padding: 5px;font-family: Calibri,Arial,Helvetica,sans-serif;">Password</td>
                <td width="70%" style="border-bottom: 1px solid #eeeeee; padding: 5px;font-family: Calibri,Arial,Helvetica,sans-serif;">
                    <?php echo $options["login"]["password"]; ?>
                </td>
            </tr>
            <?php
        }

        if(isset($options["descriptions"]) && $options["descriptions"]){
            ?>
            <tr>
                <td colspan="2" style="border-bottom: 1px solid #eeeeee; padding: 5px;font-family: Calibri,Arial,Helvetica,sans-serif;">
                    <?php echo nl2br($options["descriptions"]); ?>
                </td>
            </tr>
            <?php
        }
    ?>

</table>

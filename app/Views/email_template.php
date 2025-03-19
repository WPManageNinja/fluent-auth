<!DOCTYPE html>
<html dir="ltr" lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta content="text/html; charset=UTF-8" http-equiv="Content-Type">
    <meta name="x-apple-disable-message-reformatting">
    <style>
        body {
            margin: 0;
            font-family: ui-sans-serif, system-ui, sans-serif, 'Apple Color Emoji', 'Segoe UI Emoji', 'Segoe UI Symbol', 'Noto Color Emoji';
        }

        blockquote {
            background-color: rgb(249, 250, 251);
            padding: 16px;
            border-radius: 4px;
            margin: 24px 0;
            border-left-width: 4px;
            border-color: rgb(59, 130, 246);
        }

        hr {
            border-color: rgb(209, 213, 219);
            margin-top: 24px;
            margin-bottom: 24px;
            width: 100%;
            border: none;
            border-top: 1px solid #eaeaea;
        }

        blockquote p {
            font-size: 15px;
            color: rgb(55, 65, 81);
            margin: 0px;
            line-height: 24px;
        }
    </style>
</head>
<body
    style="background-color:#f3f4f6;font-family:ui-sans-serif, system-ui, sans-serif, 'Apple Color Emoji', 'Segoe UI Emoji', 'Segoe UI Symbol', 'Noto Color Emoji'">
<div
    style="background-color:#f3f4f6;font-family:ui-sans-serif, system-ui, sans-serif, 'Apple Color Emoji', 'Segoe UI Emoji', 'Segoe UI Symbol', 'Noto Color Emoji'">
    <table align="center" width="100%" border="0" cellpadding="0" cellspacing="0" role="presentation"
           style="margin-left:auto;margin-right:auto;padding-top:32px;padding-bottom:32px;padding-left:16px;padding-right:16px;max-width:600px">
        <tbody>
        <tr style="width:100%">
            <td>
                <table align="center" width="100%" border="0" cellpadding="0" cellspacing="0" role="presentation"
                       style="background-color:rgb(255,255,255);border-radius:8px;padding:32px;box-shadow:0 0 #0000, 0 0 #0000, 0 1px 2px 0 rgb(0,0,0,0.05)">
                    <tbody>
                    <tr>
                        <td>
                            <?php echo $body; ?>
                        </td>
                    </tr>
                    </tbody>
                </table>
                <?php if (!empty($footer)): ?>
                    <table align="center" width="100%" border="0" cellpadding="0" cellspacing="0" role="presentation"
                           style="margin-top:32px;text-align:center;color:rgb(107,114,128)">
                        <tbody>
                        <tr>
                            <td>
                                <?php echo $footer; ?>
                            </td>
                        </tr>
                        </tbody>
                    </table>
                <?php endif; ?>
            </td>
        </tr>
        </tbody>
    </table>
</div>
</body>
</html>

<!DOCTYPE html>
<html lang="el">
<head>
    <meta charset="UTF-8">
    <title>Νέο μήνυμα επικοινωνίας</title>
</head>
<body style="font-family: Arial, Helvetica, sans-serif; color:#2B2B2B; background:#FAF8F5; margin:0; padding:24px">
    <div style="max-width:560px; margin:0 auto; background:#fff; border-radius:14px; overflow:hidden; border:1px solid rgba(43,43,43,.08)">
        <div style="background:#1E9E58; color:#fff; padding:20px 24px">
            <h1 style="margin:0; font-size:18px">Νέο μήνυμα από τη φόρμα επικοινωνίας</h1>
        </div>
        <div style="padding:24px">
            <p style="margin:0 0 16px; color:#555049">Λάβατε ένα νέο μήνυμα μέσω του site της Αρωγής.</p>

            <table style="width:100%; border-collapse:collapse; font-size:15px">
                <tr>
                    <td style="padding:8px 0; color:#8A847C; width:120px">Όνομα</td>
                    <td style="padding:8px 0; font-weight:bold">{{ $contactMessage->name }}</td>
                </tr>
                <tr>
                    <td style="padding:8px 0; color:#8A847C">Email</td>
                    <td style="padding:8px 0"><a href="mailto:{{ $contactMessage->email }}" style="color:#178049">{{ $contactMessage->email }}</a></td>
                </tr>
                @if (filled($contactMessage->phone))
                <tr>
                    <td style="padding:8px 0; color:#8A847C">Τηλέφωνο</td>
                    <td style="padding:8px 0">{{ $contactMessage->phone }}</td>
                </tr>
                @endif
            </table>

            <div style="margin-top:16px; padding:16px; background:#EAF6EF; border-radius:10px">
                <div style="color:#8A847C; font-size:13px; margin-bottom:6px">Μήνυμα</div>
                <div style="white-space:pre-wrap; line-height:1.6">{{ $contactMessage->message }}</div>
            </div>

            <p style="margin:20px 0 0; color:#8A847C; font-size:13px">
                Μπορείτε να απαντήσετε απευθείας σε αυτό το email για να επικοινωνήσετε με τον αποστολέα.
            </p>
        </div>
    </div>
</body>
</html>

$f = 'D:\xampp\htdocs\GM_HMS\vendor\vendor_view\login.php'
$c = [System.IO.File]::ReadAllText($f, [System.Text.Encoding]::UTF8)
$c = $c.Replace('#0891b2', '#06b6d4')
$c = $c.Replace('8,145,178', '6,182,212')
$c = $c.Replace('0e5f75', '0891b2')
[System.IO.File]::WriteAllText($f, $c, [System.Text.Encoding]::UTF8)
Write-Host "Done"
